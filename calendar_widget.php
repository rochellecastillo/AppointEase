<!-- calendar_widget.php - Mini calendar widget for dashboards (updated) -->
<style>
/* (unchanged CSS from yours) */
  .mini-calendar { background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1rem; }
  .calendar-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
  .calendar-grid { display:grid; grid-template-columns:repeat(7, 1fr); gap:0.25rem; }
  .calendar-day-header { text-align:center; font-size:0.75rem; font-weight:600; color:#6b7280; padding:0.5rem 0; }
  .calendar-day { aspect-ratio: 1; display:flex; align-items:center; justify-content:center; font-size:0.875rem; border-radius:0.375rem; cursor:pointer; position:relative; transition: background-color 0.2s; }
  .calendar-day:hover { background-color:#f3f4f6; }
  .calendar-day.today { background-color:#3b82f6; color:white; font-weight:bold; }
  .calendar-day.has-event { background-color:#dbeafe; }
  .calendar-day.has-event.today { background-color:#2563eb; }
  .calendar-day.other-month { color:#d1d5db; }
  .event-dot { position:absolute; bottom:2px; width:4px; height:4px; border-radius:50%; background-color:#10b981; }
  .calendar-day.has-event .event-dot { background-color:#3b82f6; }
  .calendar-day.today .event-dot { background-color:white; }
  .upcoming-events { margin-top:1rem; padding-top:1rem; border-top:1px solid #e5e7eb; }
  .event-item { padding:0.5rem; margin-bottom:0.5rem; border-left:3px solid #10b981; background-color:#f9fafb; border-radius:0.25rem; font-size:0.875rem; }
  .event-item.pending { border-left-color:#f59e0b; }
  .event-item.cancelled { border-left-color:#6b7280; }
</style>

<div class="mini-calendar">
  <div class="calendar-header">
    <button id="miniPrevBtn" class="p-1 hover:bg-gray-100 rounded"><i data-lucide="chevron-left" width="20" height="20"></i></button>
    <h3 id="miniCalendarTitle" class="font-semibold text-gray-800"></h3>
    <button id="miniNextBtn" class="p-1 hover:bg-gray-100 rounded"><i data-lucide="chevron-right" width="20" height="20"></i></button>
  </div>

  <div class="calendar-grid">
    <div class="calendar-day-header">Su</div>
    <div class="calendar-day-header">Mo</div>
    <div class="calendar-day-header">Tu</div>
    <div class="calendar-day-header">We</div>
    <div class="calendar-day-header">Th</div>
    <div class="calendar-day-header">Fr</div>
    <div class="calendar-day-header">Sa</div>
  </div>
  <div id="miniCalendarDays" class="calendar-grid" aria-live="polite"></div>

  <div class="upcoming-events">
    <div class="flex items-center justify-between mb-2">
      <h4 class="font-semibold text-gray-800 text-sm">Upcoming</h4>
      <a href="calendar.php" class="text-blue-600 hover:text-blue-700 text-xs">View All</a>
    </div>
    <div id="upcomingEvents">
      <p class="text-gray-500 text-xs text-center py-2">Loading...</p>
    </div>
  </div>
</div>

<script>
const miniCalendar = {
  currentDate: new Date(),
  events: [], // normalized events will have .date (YYYY-MM-DD), plus original props

  init: async function() {
    // attach button handlers (safer than inline onclick)
    document.getElementById('miniPrevBtn').addEventListener('click', () => this.previousMonth());
    document.getElementById('miniNextBtn').addEventListener('click', () => this.nextMonth());

    // initial load
    await this.loadEventsForCurrentMonth();

    // Auto-refresh every 5 minutes (reload events only)
    setInterval(() => this.loadEventsForCurrentMonth(), 300000);
  },

  // load events for the currently displayed month (normalizes to .date YYYY-MM-DD)
  async loadEventsForCurrentMonth() {
    try {
      const year = this.currentDate.getFullYear();
      const month = this.currentDate.getMonth();
      const start = new Date(year, month, 1).toISOString().split('T')[0];
      const end = new Date(year, month + 1, 0).toISOString().split('T')[0];

      const response = await fetch(`calendar_api.php?action=get_events&start=${start}&end=${end}`);
      if (!response.ok) throw new Error('Network response was not ok');

      const data = await response.json();
      if (!data.success) {
        console.warn('Calendar API returned no events:', data);
        this.events = [];
        this.render();
        return;
      }

      // Normalize events: prefer 'start' (ISO) but accept 'date' returned by some endpoints.
      this.events = (data.events || []).map(ev => {
        // support both ISO date (YYYY-MM-DD) and ISO datetime (YYYY-MM-DDTHH:MM:SS)
        let raw = ev.start ?? ev.date ?? ev.start_date ?? '';
        let dateOnly = '';
        if (typeof raw === 'string' && raw.length) {
          dateOnly = raw.split('T')[0];
        } else if (ev.start && ev.start.date) {
          // support objects (FullCalendar event objects)
          dateOnly = String(ev.start.date);
        }
        // normalize status/title fields for widget upcoming list
        const title = ev.title ?? (ev.extendedProps && (ev.extendedProps.patient_name || ev.extendedProps.doctor_name)) ?? 'Appointment';
        const status = (ev.extendedProps && (ev.extendedProps.status_text || ev.extendedProps.status)) || ev.status || 'Unknown';
        return Object.assign({}, ev, { rawStart: raw, date: dateOnly, title: title, status: status });
      });

      // Keep schedules too if provided
      this.schedules = data.schedules ?? [];

      this.render(); // draw calendar and upcoming list
    } catch (error) {
      console.error('Error loading calendar events:', error);
      this.events = [];
      this.render();
    }
  },

  render() {
    this.renderHeader();
    this.renderDays();
    this.renderUpcoming();

    // Safe icon render
    try {
      if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
      } else if (typeof lucide !== 'undefined' && typeof lucide.replace === 'function') {
        lucide.replace();
      }
    } catch (e) {
      console.warn('lucide icon render failed:', e);
    }
  },

  renderHeader() {
    const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    document.getElementById('miniCalendarTitle').textContent =
      `${monthNames[this.currentDate.getMonth()]} ${this.currentDate.getFullYear()}`;
  },

  renderDays() {
    const container = document.getElementById('miniCalendarDays');
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const prevLastDay = new Date(year, month, 0);

    const firstDayOfWeek = firstDay.getDay(); // 0..6 (Sun..Sat)
    const daysInMonth = lastDay.getDate();
    const daysInPrevMonth = prevLastDay.getDate();

    const today = new Date();
    const isCurrentMonth = today.getMonth() === month && today.getFullYear() === year;

    let htmlParts = [];

    // Previous month days
    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
      const day = daysInPrevMonth - i;
      htmlParts.push(`<div class="calendar-day other-month" aria-disabled="true">${day}</div>`);
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const hasEvent = this.events.some(e => e.date === dateStr);
      const isToday = isCurrentMonth && day === today.getDate();

      const classes = ['calendar-day'];
      if (isToday) classes.push('today');
      if (hasEvent) classes.push('has-event');

      htmlParts.push(`
        <div class="${classes.join(' ')}" role="button" tabindex="0" data-date="${dateStr}">
          ${day}
          ${hasEvent ? '<span class="event-dot" aria-hidden="true"></span>' : ''}
        </div>
      `);
    }

    // Calculate how many cells we've already added: previousDays + daysInMonth
    const filledCells = firstDayOfWeek + daysInMonth;
    const totalCells = 42; // 6 rows * 7 days
    const remainingCells = totalCells - filledCells;

    // Next month days (fillers)
    for (let d = 1; d <= remainingCells; d++) {
      htmlParts.push(`<div class="calendar-day other-month" aria-disabled="true">${d}</div>`);
    }

    container.innerHTML = htmlParts.join('');

    // Attach click handlers for current-month days
    container.querySelectorAll('.calendar-day').forEach(node => {
      const date = node.getAttribute('data-date');
      if (date) {
        node.addEventListener('click', () => this.showDayEvents(date));
        node.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') this.showDayEvents(date); });
      }
    });
  },

  renderUpcoming() {
    const container = document.getElementById('upcomingEvents');
    const today = new Date().toISOString().split('T')[0];

    // Use normalized .date fields, filter future or today
    const upcomingEvents = this.events
      .filter(e => e.date && e.date >= today)
      .sort((a, b) => a.date.localeCompare(b.date) || (a.rawStart || '').localeCompare(b.rawStart || ''))
      .slice(0, 3);

    if (upcomingEvents.length === 0) {
      container.innerHTML = '<p class="text-gray-500 text-xs text-center py-2">No upcoming events</p>';
      return;
    }

    container.innerHTML = upcomingEvents.map(event => {
      const date = new Date(event.date);
      const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      const status = (event.status || 'Unknown');
      const statusClass = status === 'Confirmed' ? '': (status === 'Pending' ? 'pending' : 'cancelled');
      const statusPill = status === 'Confirmed' ? 'bg-green-100 text-green-700' : (status === 'Pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700');

      return `
        <div class="event-item ${statusClass}" role="group" aria-label="${event.title} on ${dateStr}">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="font-semibold text-gray-800 text-xs">${event.title}</p>
              <p class="text-gray-600 text-xs mt-1">${dateStr}${event.rawStart && event.rawStart.includes('T') && event.rawStart.split('T')[1] ? ' • ' + (event.rawStart.split('T')[1].substring(0,5)) : ''}</p>
            </div>
            <span class="text-xs px-2 py-1 rounded ${statusPill}">${status}</span>
          </div>
        </div>
      `;
    }).join('');
  },

  showDayEvents(dateStr) {
    const dayEvents = this.events.filter(e => e.date === dateStr);
    // If no events, open calendar page for that date
    if (dayEvents.length === 0) {
      window.location.href = `calendar.php?date=${dateStr}`;
      return;
    }
    // If events exist, go to calendar page (could be replaced by modal)
    // You can replace this to show a modal for quick view if desired
    window.location.href = `calendar.php?date=${dateStr}`;
  },

  previousMonth() {
    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
    this.loadEventsForCurrentMonth();
  },

  nextMonth() {
    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
    this.loadEventsForCurrentMonth();
  }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => miniCalendar.init());
} else {
  miniCalendar.init();
}
</script>
