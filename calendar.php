<?php
// calendar.php - Full calendar interface with FullCalendar.js
// Use session_handler.php if available, otherwise start session manually and generate token
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// --- Basic Security/Setup (if session_handler.php is missing) ---
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    // Generate a simple CSRF token if one is not present
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
}
// -------------------------------------------------------------

require 'db.php';

$my_user_id = $_SESSION['user_id'];
$my_user_type = strtolower($_SESSION['user_type']);
$csrf_token = $_SESSION['csrf_token']; // Use the generated token

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Get user info
$stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ? LIMIT 1");
$stmt->execute([$my_user_id]);
$my_info = $stmt->fetch(PDO::FETCH_ASSOC);

$back_url = $my_user_type === 'doctor' ? 'doctor_home.php' :
            ($my_user_type === 'admin' ? 'admin_home.php' : 'client_home.php');

$page_title = $my_user_type === 'doctor' ? 'My Schedule Calendar' :
              ($my_user_type === 'admin' ? 'Hospital Calendar' : 'My Appointments Calendar');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Calendar - AppointmentEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }

        #calendar {
            max-width: 100%;
            margin: 0 auto;
        }
        .fc-event {
            cursor: pointer;
            border-radius: 4px !important;
        }
        .fc-daygrid-day-number {
            font-weight: 600;
        }
        .calendar-legend {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }
        /* Custom FullCalendar Styling for Tailwind consistency */
        .fc-toolbar-title {
            font-size: 1.5rem !important;
            font-weight: 700 !important;
        }
        .fc-button {
            background-color: #6b46c1 !important; /* Tailwind purple-700 */
            border-color: #6b46c1 !important;
            color: white !important;
            text-transform: none !important;
            font-weight: 500 !important;
            padding: 0.5rem 1rem !important;
            border-radius: 0.5rem !important;
            transition: background-color 0.15s ease-in-out;
        }
        .fc-button:hover {
             background-color: #55359a !important; /* Darker purple */
        }
        .fc-button-primary:not(:disabled).fc-button-active, .fc-button-primary:not(:disabled):active {
            background-color: #55359a !important;
            box-shadow: none !important;
        }
        .fc-today-button {
            background-color: #374151 !important; /* Tailwind gray-700 */
            border-color: #374151 !important;
        }
        .fc-today-button:hover {
            background-color: #1f2937 !important; /* Darker gray */
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-4 md:p-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <a href="<?= e($back_url) ?>" class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-lg transition" aria-label="Back to dashboard">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            <span class="hidden sm:inline text-sm font-medium">Back to dashboard</span>
                        </a>

                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800"><?= e($page_title) ?></h1>
                            <p class="text-sm text-gray-600">View and manage appointments and schedules</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="exportCalendar()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center gap-2 text-sm">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span class="hidden md:inline">Export iCal</span>
                        </button>
                    </div>
                </div>

                <div class="calendar-legend mt-4 pt-4 border-t border-gray-100">
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #10b981;"></div>
                        <span>Confirmed</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #f59e0b;"></div>
                        <span>Pending</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #6b7280;"></div>
                        <span>Cancelled</span>
                    </div>
                    <?php if ($my_user_type === 'doctor'): ?>
                    <div class="legend-item">
                        <div class="legend-color" style="background-color: #ef4444;"></div>
                        <span>Unavailable Day</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div id="calendar"></div>
            </div>

            <?php if ($my_user_type === 'doctor'): ?>
            <div class="bg-white rounded-lg shadow p-6 mt-6 border border-gray-200">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Weekly Schedule Overview</h2>
                <div id="scheduleInfo" class="text-gray-600">
                    <p>Loading schedule information...</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="eventModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full transform transition-transform duration-300 scale-95 opacity-0" id="modalContainer">
            <div class="p-6 border-b flex items-center justify-between">
                <h2 id="modalTitle" class="text-xl font-bold text-gray-800">Details</h2>
                <button onclick="closeEventModal()" class="text-gray-500 hover:text-gray-700">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="modalContent" class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    
    <script>
        if (typeof lucide !== 'undefined') lucide.replace();

        let calendar;
        const userType = '<?= e($my_user_type) ?>';
        const csrfToken = '<?= e($csrf_token) ?>';

        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek'
                },
                editable: false,
                selectable: true,
                selectMirror: true,
                dayMaxEvents: true,
                navLinks: true, 
                weekends: true,
                
                events: function(fetchInfo, successCallback, failureCallback) {
                    loadEvents(fetchInfo.startStr, fetchInfo.endStr, successCallback, failureCallback);
                },
                
                eventClick: function(info) {
                    showEventDetails(info.event);
                },
                
                dateClick: function(info) {
                    if (calendar.view.type === 'dayGridMonth' || calendar.view.type === 'timeGridWeek') {
                        showDayDetails(info.dateStr);
                    }
                }
            });

            calendar.render();

            // Flag to load schedules only once
            window.schedulesLoaded = false;
        });

        async function loadEvents(start, end, successCallback, failureCallback) {
            try {
                const response = await fetch(`calendar_api.php?action=get_events&start=${start}&end=${end}`);
                const data = await response.json();

                if (data.success) {
                    // Store schedules if provided (Doctor View)
                    if (data.schedules) {
                        window.doctorSchedules = data.schedules;
                        if (!window.schedulesLoaded) {
                             loadScheduleInfo();
                             window.schedulesLoaded = true;
                        }
                    }
                    successCallback(data.events);
                } else {
                    console.error('API Error:', data.error);
                    failureCallback();
                }
            } catch (error) {
                console.error('Error loading events:', error);
                failureCallback();
            }
        }

        function openModal() {
             const modal = document.getElementById('eventModal');
             const container = document.getElementById('modalContainer');
             modal.classList.remove('hidden');
             setTimeout(() => {
                 modal.classList.add('opacity-100');
                 container.classList.remove('scale-95', 'opacity-0');
                 container.classList.add('scale-100', 'opacity-100');
             }, 10); 
        }

        function closeEventModal() {
            const modal = document.getElementById('eventModal');
            const container = document.getElementById('modalContainer');
            modal.classList.remove('opacity-100');
            container.classList.add('scale-95', 'opacity-0');
            container.classList.remove('scale-100', 'opacity-100');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300); 
        }

        function showEventDetails(event) {
            const title = document.getElementById('modalTitle');
            const content = document.getElementById('modalContent');
            const extendedProps = event.extendedProps;
            
            title.textContent = event.title;

            let html = '<div class="space-y-4">';

            if (extendedProps.type === 'appointment') {
                const isCancelled = extendedProps.status_text === 'Cancelled';
                
                html += `
                    <div class="bg-gray-50 rounded-lg p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Date</p>
                            <p class="font-semibold text-gray-800">${event.start.toLocaleDateString('en-US', {
                                weekday: 'long',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            })}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Status</p>
                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full ${extendedProps.status_class}">${extendedProps.status_text || 'N/A'}</span>
                        </div>
                    </div>
                    
                    <h3 class="font-semibold text-lg text-gray-800 pt-2 border-t mt-4">Participant Details</h3>
                    <div class="space-y-3 text-sm">`;

                if (userType === 'doctor' || userType === 'admin') {
                    // Doctor/Admin sees patient details
                    html += `
                        <div class="flex items-center gap-2 p-3 bg-blue-50 rounded-lg">
                            <i data-lucide="user" class="w-5 h-5 text-blue-600"></i>
                            <div>
                                <p class="font-medium text-blue-900">Patient: ${extendedProps.patient_name || 'N/A'}</p>
                                <p class="text-blue-700">${extendedProps.patient_contact ? `<i data-lucide="phone" class="w-3 h-3 inline mr-1"></i>${extendedProps.patient_contact}` : 'No Contact Info'}</p>
                            </div>
                        </div>`;
                }

                if (userType === 'user' || userType === 'admin') {
                    // Patient/Admin sees doctor details
                    html += `
                        <div class="flex items-center gap-2 p-3 bg-purple-50 rounded-lg">
                            <i data-lucide="stethoscope" class="w-5 h-5 text-purple-600"></i>
                            <div>
                                <p class="font-medium text-purple-900">Doctor: ${extendedProps.doctor_name || 'N/A'}</p>
                                <p class="text-purple-700">${extendedProps.doctor_contact ? `<i data-lucide="phone" class="w-3 h-3 inline mr-1"></i>${extendedProps.doctor_contact}` : 'No Contact Info'}</p>
                            </div>
                        </div>`;
                }
                
                html += `</div>
                    
                    <div class="flex gap-3 pt-4 border-t border-gray-100">
                        <button onclick="viewAppointmentDetails(${extendedProps.appointment_id})" 
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg transition text-sm flex items-center justify-center gap-2">
                            <i data-lucide="info" class="w-4 h-4"></i> View Full Details
                        </button>
                        ${(userType === 'doctor' || userType === 'admin') && !isCancelled ? `
                            <button onclick="cancelAppointment(${extendedProps.appointment_id})" 
                                    class="bg-red-600 hover:bg-red-700 text-white py-3 px-4 rounded-lg transition text-sm flex items-center justify-center gap-2">
                                <i data-lucide="x-circle" class="w-4 h-4"></i> Cancel
                            </button>
                        ` : ''}
                    </div>
                `;
            } else if (extendedProps.type === 'unavailable') {
                 html += `
                    <div class="bg-red-50 rounded-lg p-4">
                        <h3 class="font-semibold text-red-800 mb-2 flex items-center gap-2">
                            <i data-lucide="ban" class="w-5 h-5"></i> Doctor is Unavailable
                        </h3>
                        <p class="text-sm text-red-700 mt-1">This date has been marked as a non-appointment day.</p>
                        <p class="text-sm text-gray-600 mt-2">Reason: ${extendedProps.reason || 'Not specified'}</p>
                    </div>
                 `;
            }

            html += `</div>`;
            content.innerHTML = html;
            openModal();
            if (typeof lucide !== 'undefined') lucide.replace();
        }

        async function showDayDetails(dateStr) {
            const title = document.getElementById('modalTitle');
            const content = document.getElementById('modalContent');

            title.textContent = 'Day Summary: ' + new Date(dateStr).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            content.innerHTML = '<div class="text-center py-8 text-gray-500">Loading details...</div>';
            openModal();

            try {
                const response = await fetch(`calendar_api.php?action=get_day_details&date=${dateStr}`);
                const data = await response.json();

                if (data.success) {
                    let html = '<div class="space-y-6">';

                    // 1. Doctor Unavailable Status
                    if (userType === 'doctor' && data.unavailable_reason) {
                        html += `
                            <div class="bg-red-100 p-4 rounded-lg border-l-4 border-red-500">
                                <h3 class="font-semibold text-red-800 flex items-center gap-2">
                                    <i data-lucide="alert-triangle" class="w-5 h-5"></i> Unavailable
                                </h3>
                                <p class="text-sm text-red-700 mt-1">Reason: ${data.unavailable_reason}</p>
                            </div>
                        `;
                    }

                    // 2. Doctor Schedule (if doctor view)
                    if (userType === 'doctor' && data.schedules && data.schedules.length > 0) {
                        html += '<div><h3 class="font-semibold text-gray-800 mb-3 border-b pb-2">Your Schedule Slots</h3><div class="space-y-2">';
                        
                        data.schedules.forEach(schedule => {
                            html += `
                                <div class="bg-blue-50 rounded-lg p-3">
                                    <p class="font-semibold text-blue-900">
                                        <i data-lucide="clock" class="w-4 h-4 inline-block mr-1"></i> 
                                        ${schedule.time.substring(0, 5)} 
                                        ${schedule.time2 && schedule.time2 !== '00:00:00' ? ' - ' + schedule.time2.substring(0, 5) : ''}
                                    </p>
                                    <p class="text-sm text-blue-700">Max Appointments: ${schedule.max_appointment}</p>
                                </div>
                            `;
                        });
                        
                        html += '</div></div>';
                    }

                    // 3. Appointments List
                    html += '<div><h3 class="font-semibold text-gray-800 mb-3 border-b pb-2">Appointments List</h3><div class="space-y-3">';
                    
                    if (data.appointments && data.appointments.length > 0) {
                        data.appointments.forEach(apt => {
                            const statusText = apt.status_text;
                            const statusClass = apt.status_class;
                            const name = userType === 'doctor' ? apt.patient_name : apt.doctor_name;
                            const contactInfo = userType === 'doctor' ? apt.contact : apt.doctor_contact;
                            
                            html += `
                                <div class="border border-gray-200 rounded-lg p-4 transition duration-150 hover:shadow-md">
                                    <div class="flex items-start justify-between mb-2">
                                        <span class="font-semibold text-gray-800 text-base">${name}</span>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusClass}">${statusText}</span>
                                    </div>
                                    ${contactInfo ? `<p class="text-sm text-gray-600 flex items-center gap-1"><i data-lucide="phone" class="w-4 h-4"></i> ${contactInfo}</p>` : ''}
                                    ${userType === 'doctor' && apt.email ? `<p class="text-sm text-gray-600 flex items-center gap-1"><i data-lucide="mail" class="w-4 h-4"></i> ${apt.email}</p>` : ''}
                                    <button onclick="viewAppointmentDetails(${apt.id})" class="mt-3 text-sm text-purple-600 hover:text-purple-800 font-medium flex items-center gap-1">
                                        View Details <i data-lucide="external-link" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            `;
                        });
                    } else {
                        html += '<p class="text-gray-500 text-center py-4">No appointments for this day.</p>';
                    }
                    
                    html += '</div></div>';
                    html += '</div>';
                    content.innerHTML = html;
                } else {
                    content.innerHTML = `<div class="text-center py-8 text-red-500">Error loading day details: ${data.error || 'Unknown error'}</div>`;
                }
            } catch (error) {
                content.innerHTML = '<div class="text-center py-8 text-red-500">Network error while fetching day details.</div>';
            }
            
            if (typeof lucide !== 'undefined') lucide.replace();
        }

        function viewAppointmentDetails(appointmentId) {
            window.location.href = `appointment_details.php?id=${appointmentId}`;
        }

        async function cancelAppointment(appointmentId) {
            if (!confirm('Are you sure you want to cancel appointment ID ' + appointmentId + '?')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'cancel_appointment');
                formData.append('id', appointmentId);
                formData.append('csrf_token', csrfToken); // Use the correct CSRF token

                const response = await fetch('calendar_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();

                if (data.success) {
                    alert('Appointment cancelled successfully!');
                    closeEventModal();
                    calendar.refetchEvents(); 
                } else {
                    alert('Error cancelling appointment: ' + (data.message || data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Network Error: Could not reach the server.');
            }
        }

        async function exportCalendar() {
            try {
                const view = calendar.view;
                const start = view.currentStart.toISOString().split('T')[0];
                const end = view.currentEnd.toISOString().split('T')[0];

                const response = await fetch(`calendar_api.php?action=export_ical&start=${start}&end=${end}`);
                
                if (!response.ok) {
                    throw new Error('Server returned an error.');
                }
                
                const data = await response.json();

                if (data.success) {
                    const blob = new Blob([data.ical], { type: 'text/calendar' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = data.filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    alert('Calendar exported successfully!');
                } else {
                    alert('Export failed: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Export Error:', error);
                alert('An error occurred during calendar export.');
            }
        }

        function loadScheduleInfo() {
            if (userType !== 'doctor' || !window.doctorSchedules) return;

            const scheduleInfo = document.getElementById('scheduleInfo');
            const phpDayNames = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            
            let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">';
            
            const schedulesByDay = {};
            window.doctorSchedules.forEach(s => {
                if (!schedulesByDay[s.day]) schedulesByDay[s.day] = [];
                schedulesByDay[s.day].push(s);
            });

            let scheduleFound = false;

            for (let day = 1; day <= 7; day++) {
                if (schedulesByDay[day] && schedulesByDay[day].length > 0) {
                    scheduleFound = true;
                    html += `
                        <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
                            <h3 class="font-bold text-lg text-purple-700 mb-3">${phpDayNames[day]}</h3>
                            <div class="space-y-2">
                    `;
                    
                    schedulesByDay[day].forEach(s => {
                        html += `
                            <div class="text-sm text-gray-800 bg-purple-50 p-2 rounded flex justify-between items-center">
                                <p class="font-medium flex items-center gap-1">
                                    <i data-lucide="clock" class="w-4 h-4"></i>
                                    ${s.time.substring(0, 5)}
                                    ${s.time2 && s.time2 !== '00:00:00' ? ' - ' + s.time2.substring(0, 5) : ''}
                                </p>
                                <span class="text-xs font-semibold text-purple-600 bg-purple-200 px-2 py-0.5 rounded-full">Max: ${s.max_appointment}</span>
                            </div>
                        `;
                    });
                    
                    html += `</div></div>`;
                }
            }
            
            html += '</div>';

            if (!scheduleFound) {
                 scheduleInfo.innerHTML = '<p class="text-gray-500">No recurring weekly schedule has been set up.</p>';
            } else {
                 scheduleInfo.innerHTML = html;
                 if (typeof lucide !== 'undefined') lucide.replace();
            }
        }

        document.getElementById('eventModal').addEventListener('click', function(e) {
            if (e.target === this) closeEventModal();
        });
    </script>
</body>
</html>