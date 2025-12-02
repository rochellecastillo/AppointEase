// Globals from PHP
    const userType = '<?= e($my_user_type) ?>';
    const csrfToken = '<?= e($csrf_token) ?>';

    // client-side filters
    let filterDoctor = '';
    let activeStatusFilters = new Set(); // e.g. "Confirmed", "Pending", "Cancelled"

    // small helper
    function eSafe(s) { return (s===null || s===undefined) ? '' : String(s); }

    let calendar;

    document.addEventListener('DOMContentLoaded', () => {
        // initialize lucide icons for static content
        if (typeof lucide !== 'undefined' && lucide.replace) lucide.replace();

        // wire filter pills
        document.querySelectorAll('.filter-pill').forEach(btn => {
            btn.addEventListener('click', () => {
                const status = btn.dataset.status;
                if (activeStatusFilters.has(status)) {
                    activeStatusFilters.delete(status);
                    btn.classList.remove('ring-2','ring-offset-1');
                    btn.classList.remove('opacity-100');
                } else {
                    activeStatusFilters.add(status);
                    btn.classList.add('ring-2','ring-offset-1');
                }
                calendar.refetchEvents();
            });
        });

        const doctorSelect = document.getElementById('filterDoctor');
        if (doctorSelect) {
            doctorSelect.addEventListener('change', (e) => {
                filterDoctor = e.target.value;
                calendar.refetchEvents();
            });
        }

        document.getElementById('resetFilters').addEventListener('click', () => {
            activeStatusFilters.clear();
            document.querySelectorAll('.filter-pill').forEach(btn => btn.classList.remove('ring-2','ring-offset-1'));
            if (doctorSelect) { doctorSelect.value = ''; filterDoctor = ''; }
            calendar.refetchEvents();
        });

        document.getElementById('exportBtnTop').addEventListener('click', exportCalendar);
        const todayBtn = document.getElementById('todayBtn');
        if (todayBtn) todayBtn.addEventListener('click', () => calendar.today());

        // Initialize FullCalendar
        const calendarEl = document.getElementById('calendar');
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            buttonText: { today: 'Today' },
            height: 'auto',
            editable: false,
            selectable: true,
            dayMaxEvents: true,
            navLinks: true,
            events: function(fetchInfo, successCallback, failureCallback) {
                // fetch events via API and then apply client-side filters
                loadEvents(fetchInfo.startStr, fetchInfo.endStr, (events) => {
                    // apply doctor filter (only meaningful for admin)
                    let filtered = events;
                    if (filterDoctor) {
                        filtered = filtered.filter(ev => {
                            // some events include doctor id in extendedProps.doctor_id or event.extendedProps.doctor_name mapping
                            return ev.extendedProps && (ev.extendedProps.doctor === filterDoctor || ev.extendedProps.doctor_id === filterDoctor || ev.extendedProps.doctor_id === ('apt_' + filterDoctor) );
                        });
                    }

                    if (activeStatusFilters.size > 0) {
                        filtered = filtered.filter(ev => {
                            const st = (ev.extendedProps && (ev.extendedProps.status_text || ev.extendedProps.status)) || ev.status || 'Unknown';
                            return activeStatusFilters.has(st);
                        });
                    }

                    successCallback(filtered);
                }, failureCallback);
            },
            eventClick: function(info) { showEventDetails(info.event); },
            dateClick: function(info) { if (calendar.view.type) showDayDetails(info.dateStr); },
            eventDidMount: function(info) {
                // decorate appointment events with small status dot or tooltip if needed
                // (FullCalendar may render title already)
            }
        });

        calendar.render();

        // Modal wiring
        document.getElementById('modalCloseBtn').addEventListener('click', closeEventModal);
        document.getElementById('eventModal').addEventListener('click', (e) => { if (e.target === e.currentTarget) closeEventModal(); });

        // initial load of sidebar content
        fetchUpcoming();
    });

    async function loadEvents(start, end, successCallback, failureCallback) {
        try {
            const res = await fetch(`calendar_api.php?action=get_events&start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`);
            const data = await res.json();
            if (data.success) {
                // keep schedules global for doctor panel
                if (data.schedules) {
                    window.doctorSchedules = data.schedules;
                    if (userType === 'doctor') loadScheduleInfo();
                }
                successCallback(data.events || []);
                // also refresh sidebar upcoming list from returned events
                renderSidebarUpcoming(data.events || []);
            } else {
                console.error('API error:', data.error || data.message);
                failureCallback();
            }
        } catch (err) {
            console.error('Fetch events error:', err);
            failureCallback();
        }
    }

    // Sidebar "Upcoming" rendering (keeps top-of-page compact view in sync)
    function renderSidebarUpcoming(events) {
        const container = document.getElementById('sidebarUpcoming');
        if (!container) return;
        if (!events || events.length === 0) {
            container.innerHTML = '<p class="text-xs text-gray-500 text-center py-6">No upcoming events</p>';
            return;
        }

        // normalize and sort by date (and time if present)
        const normalized = events.map(ev => {
            const raw = ev.start ?? ev.date ?? '';
            const date = (typeof raw === 'string') ? raw.split('T')[0] : '';
            const time = (typeof raw === 'string' && raw.includes('T')) ? raw.split('T')[1].substring(0,5) : (ev.extendedProps && ev.extendedProps.booking_time ? ev.extendedProps.booking_time : '');
            const title = ev.title ?? (ev.extendedProps && (ev.extendedProps.patient_name || ev.extendedProps.doctor_name)) ?? 'Appointment';
            const status = (ev.extendedProps && (ev.extendedProps.status_text || ev.extendedProps.status)) || ev.status || 'Unknown';
            return { raw, date, time, title, status };
        }).filter(x => x.date).sort((a,b) => a.date.localeCompare(b.date) || (a.time || '').localeCompare(b.time || ''));

        // take first 6 upcoming
        const upcoming = normalized.slice(0, 6);
        container.innerHTML = upcoming.map(ev => {
            const date = new Date(ev.date);
            const dateStr = date.toLocaleDateString('en-US', { month:'short', day:'numeric' });
            const pill = ev.status === 'Confirmed' ? 'bg-green-100 text-green-700' : (ev.status === 'Pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700');
            return `<div class="p-3 rounded-md bg-gray-50 flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-800">${eSafe(ev.title)}</div>
                            <div class="text-xs text-gray-500 mt-1">${dateStr}${ev.time ? ' • ' + ev.time : ''}</div>
                        </div>
                        <div class="text-xs">${'<span class="inline-block px-2 py-1 rounded-full ' + pill + '">' + eSafe(ev.status) + '</span>'}</div>
                    </div>`;
        }).join('');
    }

    async function fetchUpcoming() {
        try {
            // fetch next 30 days events for sidebar
            const start = new Date().toISOString().split('T')[0];
            const d = new Date(); d.setDate(d.getDate() + 30);
            const end = d.toISOString().split('T')[0];
            const res = await fetch(`calendar_api.php?action=get_events&start=${start}&end=${end}`);
            const data = await res.json();
            if (data.success) {
                renderSidebarUpcoming(data.events || []);
            } else {
                document.getElementById('sidebarUpcoming').innerHTML = '<p class="text-xs text-gray-500 text-center py-6">Unable to load.</p>';
            }
        } catch (err) {
            console.error('Upcoming fetch error', err);
            document.getElementById('sidebarUpcoming').innerHTML = '<p class="text-xs text-gray-500 text-center py-6">Network error.</p>';
        }
    }

    function openModal(){
        const modal = document.getElementById('eventModal');
        const container = document.getElementById('modalContainer');
        modal.classList.remove('hidden');
        setTimeout(() => {
            container.classList.remove('scale-95','opacity-0');
            container.classList.add('scale-100','opacity-100');
        }, 10);
    }
    function closeEventModal(){
        const modal = document.getElementById('eventModal');
        const container = document.getElementById('modalContainer');
        container.classList.add('scale-95','opacity-0');
        container.classList.remove('scale-100','opacity-100');
        setTimeout(()=> modal.classList.add('hidden'), 220);
    }

    function renderExtendedIcons(){
        if (typeof lucide !== 'undefined' && lucide.replace) lucide.replace();
    }

    function buildStatusPill(text, cls){
        return `<span class="inline-block px-3 py-1 text-xs font-semibold rounded-full ${cls}">${text}</span>`;
    }

    function showEventDetails(event){
        const titleEl = document.getElementById('modalTitle');
        const contentEl = document.getElementById('modalContent');
        const ext = event.extendedProps || {};
        titleEl.textContent = event.title || 'Details';
        let html = '<div class="space-y-4">';

        if (ext.type === 'appointment' || (ext.appointment_id || event.id)) {
            const statusText = ext.status_text || ext.status || 'N/A';
            const isCancelled = (statusText === 'Cancelled' || statusText === 'Unknown');
            html += `<div class="bg-gray-50 rounded-lg p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Date</p>
                            <p class="font-semibold text-gray-800">${new Date(event.start).toLocaleDateString('en-US', {weekday:'long', year:'numeric', month:'long', day:'numeric'})}${ext.booking_time ? ' • ' + ext.booking_time : ''}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            ${buildStatusPill(statusText, ext.status_class || 'bg-gray-100 text-gray-700')}
                        </div>
                    </div>`;

            html += `<h3 class="font-semibold text-lg text-gray-800 pt-2 border-t mt-4">Participant Details</h3><div class="space-y-3 text-sm">`;

            if (userType === 'doctor' || userType === 'admin') {
                html += `<div class="flex items-center gap-2 p-3 bg-blue-50 rounded-lg">
                            <i data-lucide="user" class="w-5 h-5 text-blue-600"></i>
                            <div>
                                <p class="font-medium text-blue-900">Patient: ${ext.patient_name || ext.title || 'N/A'}</p>
                                ${ext.patient_contact ? `<p class="text-blue-700"><i data-lucide="phone" class="w-3 h-3 inline mr-1"></i>${eSafe(ext.patient_contact)}</p>` : ''}
                            </div>
                         </div>`;
            }
            if (userType === 'user' || userType === 'admin') {
                html += `<div class="flex items-center gap-2 p-3 bg-purple-50 rounded-lg">
                            <i data-lucide="stethoscope" class="w-5 h-5 text-purple-600"></i>
                            <div>
                                <p class="font-medium text-purple-900">Doctor: ${ext.doctor_name || 'N/A'}</p>
                                ${ext.doctor_contact ? `<p class="text-purple-700"><i data-lucide="phone" class="w-3 h-3 inline mr-1"></i>${eSafe(ext.doctor_contact)}</p>` : ''}
                            </div>
                         </div>`;
            }

            html += `</div><div class="flex gap-3 pt-4 border-t border-gray-100">`;
            const aptId = parseInt(ext.appointment_id || event.id || 0);
            html += `<button onclick="viewAppointmentDetails(${aptId})" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg transition text-sm flex items-center justify-center gap-2"><i data-lucide="info" class="w-4 h-4"></i> View Full Details</button>`;
            if ((userType === 'doctor' || userType === 'admin') && !isCancelled) {
                html += `<button onclick="cancelAppointment(${aptId})" class="bg-red-600 hover:bg-red-700 text-white py-3 px-4 rounded-lg transition text-sm flex items-center justify-center gap-2"><i data-lucide="x-circle" class="w-4 h-4"></i> Cancel</button>`;
            }
            html += `</div>`;
        } else if (ext.type === 'unavailable') {
            html += `<div class="bg-red-50 rounded-lg p-4">
                        <h3 class="font-semibold text-red-800 mb-2 flex items-center gap-2"><i data-lucide="ban" class="w-5 h-5"></i> Doctor is Unavailable</h3>
                        <p class="text-sm text-red-700 mt-1">This day is marked unavailable.</p>
                        <p class="text-sm text-gray-600 mt-2">Reason: ${eSafe(ext.reason || 'Not specified')}</p>
                     </div>`;
        } else {
            html += `<p class="text-gray-600">No further details available.</p>`;
        }

        html += '</div>';
        contentEl.innerHTML = html;
        renderExtendedIcons();
        openModal();
    }

    async function showDayDetails(dateStr) {
        const title = document.getElementById('modalTitle');
        const content = document.getElementById('modalContent');
        title.textContent = 'Day Summary: ' + new Date(dateStr).toLocaleDateString('en-US', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
        content.innerHTML = '<div class="text-center py-8 text-gray-500">Loading details...</div>';
        openModal();

        try {
            const res = await fetch(`calendar_api.php?action=get_day_details&date=${encodeURIComponent(dateStr)}`);
            const data = await res.json();
            if (!data.success) {
                content.innerHTML = `<div class="text-center py-8 text-red-500">Error: ${eSafe(data.error || data.message || 'Unknown')}</div>`;
                return;
            }

            let html = '<div class="space-y-6">';

            if (userType === 'doctor' && data.unavailable_reason) {
                html += `<div class="bg-red-100 p-4 rounded-lg border-l-4 border-red-500"><h3 class="font-semibold text-red-800 flex items-center gap-2"><i data-lucide="alert-triangle" class="w-5 h-5"></i> Unavailable</h3><p class="text-sm text-red-700 mt-1">Reason: ${eSafe(data.unavailable_reason)}</p></div>`;
            }

            if (userType === 'doctor' && data.schedules && data.schedules.length) {
                html += '<div><h3 class="font-semibold text-gray-800 mb-3 border-b pb-2">Your Schedule Slots</h3><div class="space-y-2">';
                data.schedules.forEach(s => {
                    html += `<div class="bg-blue-50 rounded-lg p-3"><p class="font-semibold text-blue-900"><i data-lucide="clock" class="w-4 h-4 inline-block mr-1"></i>${eSafe(s.time.substring(0,5))}${s.time2 && s.time2 !== '00:00:00' ? ' - ' + eSafe(s.time2.substring(0,5)) : ''}</p><p class="text-sm text-blue-700">Max Appointments: ${eSafe(s.max_appointment)}</p></div>`;
                });
                html += '</div></div>';
            }

            html += '<div><h3 class="font-semibold text-gray-800 mb-3 border-b pb-2">Appointments List</h3><div class="space-y-3">';
            if (data.appointments && data.appointments.length) {
                data.appointments.forEach(apt => {
                    const statusText = apt.status_text || 'N/A';
                    const statusClass = apt.status_class || 'bg-gray-100 text-gray-700';
                    const name = (userType === 'doctor') ? (apt.patient_name || 'N/A') : (apt.doctor_name || 'N/A');
                    const contactInfo = (userType === 'doctor') ? (apt.contact || apt.patient_contact) : (apt.doctor_contact || '');
                    html += `<div class="border border-gray-200 rounded-lg p-4 transition duration-150 hover:shadow-md">
                                <div class="flex items-start justify-between mb-2">
                                    <span class="font-semibold text-gray-800">${eSafe(name)}</span>
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusClass}">${eSafe(statusText)}</span>
                                </div>
                                ${contactInfo ? `<p class="text-sm text-gray-600 flex items-center gap-1"><i data-lucide="phone" class="w-4 h-4"></i> ${eSafe(contactInfo)}</p>` : ''}
                                ${(userType === 'doctor' && apt.email) ? `<p class="text-sm text-gray-600 flex items-center gap-1"><i data-lucide="mail" class="w-4 h-4"></i> ${eSafe(apt.email)}</p>` : ''}
                                <button onclick="viewAppointmentDetails(${parseInt(apt.id || 0)})" class="mt-3 text-sm text-purple-600 hover:text-purple-800 font-medium flex items-center gap-1">View Details <i data-lucide="external-link" class="w-4 h-4"></i></button>
                            </div>`;
                });
            } else {
                html += '<p class="text-gray-500 text-center py-4">No appointments for this day.</p>';
            }
            html += '</div></div></div>';
            content.innerHTML = html;
            renderExtendedIcons();
        } catch (err) {
            content.innerHTML = '<div class="text-center py-8 text-red-500">Network error while fetching day details.</div>';
            console.error(err);
        }
    }

    function viewAppointmentDetails(appointmentId) {
        if (!appointmentId) return;
        window.location.href = `calendar_appointment_details.php?id=${appointmentId}`;
    }

    async function cancelAppointment(appointmentId){
        if (!appointmentId) return alert('Invalid appointment id');
        if (!confirm('Are you sure you want to cancel appointment ID ' + appointmentId + '?')) return;

        try {
            const form = new FormData();
            form.append('action', 'cancel_appointment');
            form.append('id', appointmentId);
            form.append('csrf_token', csrfToken);

            const res = await fetch('calendar_api.php', { method: 'POST', body: form });
            const data = await res.json();
            if (data.success) {
                alert(data.message || 'Appointment cancelled successfully!');
                closeEventModal();
                calendar.refetchEvents();
                fetchUpcoming();
            } else {
                alert('Error cancelling appointment: ' + (data.message || data.error || 'Unknown'));
            }
        } catch (err) {
            alert('Network Error: Could not reach the server.');
            console.error(err);
        }
    }

    async function exportCalendar(){
        try {
            const view = calendar.view;
            const start = view.currentStart.toISOString().split('T')[0];
            const end = view.currentEnd.toISOString().split('T')[0];
            const res = await fetch(`calendar_api.php?action=export_ical&start=${start}&end=${end}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Export failed');
            const blob = new Blob([data.ical], { type: 'text/calendar;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = data.filename || 'appointments.ics';
            document.body.appendChild(a);
            a.click();
            URL.revokeObjectURL(url);
            document.body.removeChild(a);
            alert('Calendar exported successfully!');
        } catch (err) {
            console.error('Export Error', err);
            alert('An error occurred during calendar export: ' + (err.message || 'Unknown'));
        }
    }

    function loadScheduleInfo(){
        if (userType !== 'doctor' || !window.doctorSchedules) return;
        const scheduleInfo = document.getElementById('scheduleInfo');
        const phpDayNames = ['', 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        const schedulesByDay = {};
        window.doctorSchedules.forEach(s => {
            if (!s) return;
            if (!schedulesByDay[s.day]) schedulesByDay[s.day] = [];
            schedulesByDay[s.day].push(s);
        });

        let html = '<div class="grid grid-cols-1 gap-3">';
        let scheduleFound = false;
        for (let day = 1; day <= 7; day++){
            if (schedulesByDay[day] && schedulesByDay[day].length) {
                scheduleFound = true;
                html += `<div class="bg-white border border-gray-100 rounded-lg p-3">
                            <div class="flex items-center justify-between">
                                <div class="font-semibold text-gray-800">${phpDayNames[day]}</div>
                                <div class="text-xs text-gray-600">Slots: ${schedulesByDay[day].reduce((a,b)=>a+parseInt(b.max_appointment||0),0)}</div>
                            </div>
                            <div class="mt-2 space-y-2">`;
                schedulesByDay[day].forEach(s => {
                    html += `<div class="flex items-center justify-between text-sm text-gray-700 bg-purple-50 rounded p-2">
                                <div class="flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4"></i>${eSafe(s.time.substring(0,5))}${s.time2 && s.time2 !== '00:00:00' ? ' - ' + eSafe(s.time2.substring(0,5)) : ''}</div>
                                <div class="text-xs font-semibold text-purple-700">Max: ${eSafe(s.max_appointment)}</div>
                             </div>`;
                });
                html += `</div></div>`;
            }
        }
        html += '</div>';
        scheduleInfo.innerHTML = scheduleFound ? html : '<p class="text-gray-500">No recurring weekly schedule has been set up.</p>';
        renderExtendedIcons();
    }