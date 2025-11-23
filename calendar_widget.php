<!-- calendar_widget.php - Mini calendar widget for dashboards -->
<!-- Add this to any dashboard page where you want a mini calendar -->

<style>
  .mini-calendar {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    padding: 1rem;
  }
  .calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }
  .calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.25rem;
  }
  .calendar-day-header {
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    padding: 0.5rem 0;
  }
  .calendar-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    border-radius: 0.375rem;
    cursor: pointer;
    position: relative;
    transition: background-color 0.2s;
  }
  .calendar-day:hover {
    background-color: #f3f4f6;
  }
  .calendar-day.today {
    background-color: #3b82f6;
    color: white;
    font-weight: bold;
  }
  .calendar-day.has-event {
    background-color: #dbeafe;
  }
  .calendar-day.has-event.today {
    background-color: #2563eb;
  }
  .calendar-day.other-month {
    color: #d1d5db;
  }
  .event-dot {
    position: absolute;
    bottom: 2px;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background-color: #10b981;
  }
  .calendar-day.has-event .event-dot {
    background-color: #3b82f6;
  }
  .calendar-day.today .event-dot {
    background-color: white;
  }
  .upcoming-events {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
  }
  .event-item {
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    border-left: 3px solid #10b981;
    background-color: #f9fafb;
    border-radius: 0.25rem;
    font-size: 0.875rem;
  }
  .event-item.pending {
    border-left-color: #f59e0b;
  }
  .event-item.cancelled {
    border-left-color: #6b7280;
  }
</style>

<div class="mini-calendar">
  <!-- Calendar Header -->
  <div class="calendar-header">
    <button onclick="miniCalendar.previousMonth()" class="p-1 hover:bg-gray-100 rounded">
      <i data-lucide="chevron-left" width="20" height="20"></i>
    </button>
    <h3 id="miniCalendarTitle" class="font-semibold text-gray-800"></h3>
    <button onclick="miniCalendar.nextMonth()" class="p-1 hover:bg-gray-100 rounded">
      <i data-lucide="chevron-right" width="20" height="20"></i>
    </button>
  </div>

  <!-- Calendar Grid -->
  <div class="calendar-grid">
    <div class="calendar-day-header">Su</div>
    <div class="calendar-day-header">Mo</div>
    <div class="calendar-day-header">Tu</div>
    <div class="calendar-day-header">We</div>
    <div class="calendar-day-header">Th</div>
    <div class="calendar-day-header">Fr</div>
    <div class="calendar-day-header">Sa</div>
  </div>
  <div id="miniCalendarDays" class="calendar-grid"></div>

  <!-- Upcoming Events -->
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
  events: [],
  
  init: async function() {
    await this.loadEvents();
    this.render();
    
    // Auto-refresh every 5 minutes
    setInterval(() => this.loadEvents(), 300000);
  },
  
  async loadEvents() {
    try {
      const year = this.currentDate.getFullYear();
      const month = this.currentDate.getMonth();
      const start = new Date(year, month, 1).toISOString().split('T')[0];
      const end = new Date(year, month + 1, 0).toISOString().split('T')[0];
      
      const response = await fetch(`calendar_api.php?action=get_events&start=${start}&end=${end}`);
      const data = await response.json();
      
      if (data.success) {
        this.events = data.events;
        this.render();
      }
    } catch (error) {
      console.error('Error loading calendar events:', error);
    }
  },
  
  render() {
    this.renderHeader();
    this.renderDays();
    this.renderUpcoming();
    
    // Use new Lucide API if available (createIcons). Guard it so it won't throw.
    try {
      if (typeof lucide !== 'undefined' && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
      }
    } catch (e) {
      // Fail silently if icon library not available or another issue
      console.warn('lucide icon render failed:', e);
    }
  },
  
  renderHeader() {
    const monthNames = ["January", "February", "March", "April", "May", "June",
      "July", "August", "September", "October", "November", "December"];
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
    
    const firstDayOfWeek = firstDay.getDay();
    const daysInMonth = lastDay.getDate();
    const daysInPrevMonth = prevLastDay.getDate();
    
    const today = new Date();
    const isCurrentMonth = today.getMonth() === month && today.getFullYear() === year;
    
    let html = '';
    
    // Previous month days
    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
      const day = daysInPrevMonth - i;
      html += `<div class="calendar-day other-month">${day}</div>`;
    }
    
    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const hasEvent = this.events.some(e => e.date === dateStr);
      const isToday = isCurrentMonth && day === today.getDate();
      
      const classes = ['calendar-day'];
      if (isToday) classes.push('today');
      if (hasEvent) classes.push('has-event');
      
      html += `
        <div class="${classes.join(' ')}" onclick="miniCalendar.showDayEvents('${dateStr}')">
          ${day}
          ${hasEvent ? '<span class="event-dot"></span>' : ''}
        </div>
      `;
    }
    
    // Next month days
    const totalCells = html.split('calendar-day').length - 1;
    const remainingCells = 42 - totalCells; // 6 rows * 7 days
    for (let day = 1; day <= remainingCells; day++) {
      html += `<div class="calendar-day other-month">${day}</div>`;
    }
    
    container.innerHTML = html;
  },
  
  renderUpcoming() {
    const container = document.getElementById('upcomingEvents');
    const today = new Date().toISOString().split('T')[0];
    
    const upcomingEvents = this.events
      .filter(e => e.date >= today)
      .sort((a, b) => a.date.localeCompare(b.date))
      .slice(0, 3);
    
    if (upcomingEvents.length === 0) {
      container.innerHTML = '<p class="text-gray-500 text-xs text-center py-2">No upcoming events</p>';
      return;
    }
    
    container.innerHTML = upcomingEvents.map(event => {
      const date = new Date(event.date);
      const dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
      const statusClass = event.status === 'Confirmed' ? '' : 
                         event.status === 'Pending' ? 'pending' : 'cancelled';
      
      return `
        <div class="event-item ${statusClass}">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <p class="font-semibold text-gray-800 text-xs">${event.title}</p>
              <p class="text-gray-600 text-xs mt-1">${dateStr}</p>
            </div>
            <span class="text-xs px-2 py-1 rounded ${
              event.status === 'Confirmed' ? 'bg-green-100 text-green-700' :
              event.status === 'Pending' ? 'bg-yellow-100 text-yellow-700' :
              'bg-gray-100 text-gray-700'
            }">${event.status}</span>
          </div>
        </div>
      `;
    }).join('');
  },
  
  showDayEvents(dateStr) {
    const dayEvents = this.events.filter(e => e.date === dateStr);
    
    if (dayEvents.length === 0) {
      window.location.href = `calendar.php?date=${dateStr}`;
      return;
    }
    
    // Show modal or redirect to calendar page
    window.location.href = `calendar.php?date=${dateStr}`;
  },
  
  previousMonth() {
    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() - 1, 1);
    this.loadEvents();
  },
  
  nextMonth() {
    this.currentDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 1);
    this.loadEvents();
  }
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => miniCalendar.init());
} else {
  miniCalendar.init();
}
</script>
