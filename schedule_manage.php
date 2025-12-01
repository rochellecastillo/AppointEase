<?php
include __DIR__ . '/controllers/schedule_manage_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visual Schedule Manager - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #e5e7eb; border: 1px solid #e5e7eb; }
        .day-cell { min-height: 120px; background: white; padding: 8px; transition: 0.2s; position: relative; cursor: pointer; }
        .day-cell:hover { background: #f9fafb; }
        .day-header { text-align: center; padding: 10px; background: #f3f4f6; font-weight: 600; font-size: 0.8rem; color: #4b5563; text-transform: uppercase; }
        .event-chip { font-size: 0.7rem; padding: 4px 6px; border-radius: 4px; margin-top: 4px; font-weight: 500; }
        .event-roster { background: #f3e8ff; color: #6b21a8; border-left: 3px solid #9333ea; }
        .event-block { background: #fef2f2; color: #991b1b; border-left: 3px solid #ef4444; }
        .other-month { opacity: 0.4; pointer-events: none; background: #f9fafb; }
        .day-past { background-color: #f3f4f6 !important; color: #9ca3af; cursor: not-allowed !important; opacity: 0.7; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include ($user_type === 'admin' ? 'includes/admin_sidebar.php' : 'includes/doctor_sidebar.php'); ?>

        <main class="flex-1 overflow-auto">
            <div class="p-6 max-w-7xl mx-auto">
                
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
                    <div>
                        <?php if($user_type === 'admin'): ?>
                        <a href="doctors_info_report.php" class="inline-flex items-center text-xs text-gray-500 hover:text-purple-600 mb-2 transition">
                            <i data-lucide="arrow-left" class="w-3 h-3 mr-1"></i> Back to Doctors
                        </a>
                        <?php endif; ?>
                        <h1 class="text-2xl font-bold text-gray-900">Schedule Calendar</h1>
                        <p class="text-gray-500 text-sm">Manage availability and blocked dates.</p>
                    </div>
                    
                    <?php if($user_type === 'admin'): ?>
                    <div class="bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
                        <label class="text-xs font-bold uppercase text-gray-400 ml-2">Managing:</label>
                        <select id="doctorSelector" class="bg-transparent text-sm font-semibold focus:outline-none cursor-pointer">
                            <?php foreach($doctors as $d): ?>
                                <option value="<?= $d['user_id'] ?>" <?= $target_doctor == $d['user_id'] ? 'selected' : '' ?>>
                                    Dr. <?= htmlspecialchars($d['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                        <input type="hidden" id="doctorSelector" value="<?= $user_id ?>">
                    <?php endif; ?>
                </div>

                <?php if($message): ?>
                    <div class="mb-6 p-4 rounded-xl border flex items-center gap-2 <?= $msg_type == 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-t-2xl border-x border-t border-gray-200 flex items-center justify-between shadow-sm z-10 relative">
                    <h2 class="text-xl font-bold text-gray-800" id="monthDisplay">Loading...</h2>
                    <div class="flex gap-2">
                        <button id="prevMonth" class="p-2 hover:bg-gray-100 rounded-lg border border-gray-200"><i data-lucide="chevron-left" width="20"></i></button>
                        <button id="todayBtn" class="px-4 py-2 text-sm font-bold text-purple-600 bg-purple-50 hover:bg-purple-100 rounded-lg transition">Today</button>
                        <button id="nextMonth" class="p-2 hover:bg-gray-100 rounded-lg border border-gray-200"><i data-lucide="chevron-right" width="20"></i></button>
                    </div>
                </div>

                <div class="bg-white rounded-b-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="calendar-grid" id="calendarGrid"></div>
                </div>

            </div>
        </main>
    </div>

    <div id="slotModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>
        
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-4 sm:px-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold leading-6 text-white" id="modalTitle">Manage Availability</h3>
                            <button type="button" onclick="closeModal()" class="text-purple-200 hover:text-white focus:outline-none">
                                <i data-lucide="x" width="24"></i>
                            </button>
                        </div>
                        <p class="text-purple-100 text-sm mt-1" id="modalDateDisplay">Date</p>
                    </div>

                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        
                        <div class="flex border-b border-gray-200 mb-4">
                            <button onclick="switchModalTab('block')" id="tab-block" class="flex-1 py-2 text-sm font-bold text-red-600 border-b-2 border-red-600 focus:outline-none transition">Block Date (Exception)</button>
                            <button onclick="switchModalTab('roster')" id="tab-roster" class="flex-1 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-purple-600 focus:outline-none transition">Weekly Roster</button>
                        </div>

                        <div id="content-block">
                            <form method="POST" id="unblockForm" class="hidden mb-4">
                                <?php if($user_type === 'admin'): ?><input type="hidden" name="doctor_id" id="formUnblockDocId"><?php endif; ?>
                                <input type="hidden" name="date_to_unblock" id="formUnblockDate">
                                
                                <div class="p-4 bg-red-50 border border-red-100 rounded-xl text-center">
                                    <p class="text-red-800 font-bold">This date is currently blocked.</p>
                                    <p class="text-xs text-red-600 mb-4" id="blockReasonDisplay"></p>
                                    <button type="submit" name="unblock_date" class="px-4 py-2 bg-white border border-red-200 text-red-600 font-bold rounded-lg hover:bg-red-50 transition shadow-sm">
                                        Remove Block
                                    </button>
                                </div>
                            </form>

                            <form method="POST" id="blockForm">
                                <?php if($user_type === 'admin'): ?><input type="hidden" name="doctor_id" id="formBlockDocId"><?php endif; ?>
                                <input type="hidden" name="date_start" id="formBlockDate">
                                
                                <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600 flex gap-2 items-start">
                                    <i data-lucide="info" class="flex-shrink-0 w-4 h-4 mt-0.5"></i>
                                    <span>Blocking this specific date overrides the weekly schedule.</span>
                                </div>
                                
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Reason</label>
                                <input type="text" name="reason" placeholder="e.g., Vacation, Seminar" class="w-full p-3 border border-gray-300 rounded-xl mb-6 focus:border-red-500 focus:ring-2 focus:ring-red-200 focus:outline-none transition">
                                
                                <button type="submit" name="block_date" class="w-full py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition shadow-lg shadow-red-200">
                                    Confirm Block
                                </button>
                            </form>
                        </div>

                        <div id="content-roster" class="hidden">
                            <form method="POST">
                                <?php if($user_type === 'admin'): ?><input type="hidden" name="doctor_id" id="formRosterDocId"><?php endif; ?>
                                <input type="hidden" name="day_index" id="formRosterDayIndex">
                                
                                <div class="mb-4 p-3 bg-purple-50 border border-purple-100 rounded-lg text-sm text-purple-800 flex gap-2 items-start">
                                    <i data-lucide="calendar-sync" class="flex-shrink-0 w-4 h-4 mt-0.5"></i>
                                    <span>Modifies schedule for <strong>EVERY <span id="rosterDayName">Monday</span></strong>.</span>
                                </div>
                                
                                <div class="flex items-center mb-4 p-3 bg-gray-50 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-100 transition">
                                    <input type="checkbox" name="is_active" id="rosterActive" class="w-5 h-5 text-purple-600 rounded border-gray-300 focus:ring-purple-500 cursor-pointer">
                                    <label for="rosterActive" class="ml-3 text-sm font-bold text-gray-700 cursor-pointer select-none w-full">Work on this day?</label>
                                </div>
                                
                                <div id="rosterInputs" class="space-y-4 opacity-50 pointer-events-none transition-opacity duration-200">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="text-xs font-bold text-gray-400 uppercase mb-1 block">Start Time</label>
                                            <input type="time" name="time_start" id="rosterStart" class="w-full p-2.5 border border-gray-300 rounded-lg focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold text-gray-400 uppercase mb-1 block">End Time</label>
                                            <input type="time" name="time_end" id="rosterEnd" class="w-full p-2.5 border border-gray-300 rounded-lg focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-gray-400 uppercase mb-1 block">Max Patients (30 min slots)</label>
                                        <input type="number" name="max_appointment" id="rosterMax" value="1" min="1" readonly 
                                               class="w-full p-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed text-gray-600">
                                    </div>
                                </div>

                                <button type="submit" name="update_roster" class="w-full mt-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl transition shadow-lg shadow-purple-200">
                                    Save Recurring Schedule
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        // --- CONSTANTS ---
        const APPOINTMENT_DURATION_MINUTES = 30; // 30 minutes per patient slot
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        let currentDate = new Date();
        let rosterData = [];
        let leaveData = [];
        
        const doctorSelector = document.getElementById('doctorSelector');
        const monthDisplay = document.getElementById('monthDisplay');
        const grid = document.getElementById('calendarGrid');

        // --- CORE CALCULATION LOGIC ---
        /**
         * Calculates the maximum number of appointments based on the time range.
         * @param {string} startTime - Time string (HH:MM).
         * @param {string} endTime - Time string (HH:MM).
         * @returns {number} Max appointments count (min 1 if valid, 0 if invalid range).
         */
        function calculateMaxPatients(startTime, endTime) {
            // Check for valid time formats (optional, but good practice)
            if (!startTime || !endTime || startTime.length !== 5 || endTime.length !== 5) {
                return 1;
            }

            // Convert times to minutes from midnight
            const [startH, startM] = startTime.split(':').map(Number);
            const [endH, endM] = endTime.split(':').map(Number);

            const startTotalMinutes = startH * 60 + startM;
            let endTotalMinutes = endH * 60 + endM;
            
            // Check for invalid range (End time must be after start time)
            if (endTotalMinutes <= startTotalMinutes) {
                return 0; 
            }

            const durationMinutes = endTotalMinutes - startTotalMinutes;
            
            // Calculate max appointments
            const maxAppointments = Math.floor(durationMinutes / APPOINTMENT_DURATION_MINUTES);

            return Math.max(1, maxAppointments); // Ensure a minimum of 1 if duration is valid
        }

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            if (doctorSelector) {
                // Fetch data immediately for the doctor set by PHP
                fetchData();
                
                // FIX: When dropdown changes, redirect so the URL matches the view
                doctorSelector.addEventListener('change', function() {
                    const selectedId = this.value;
                    window.location.search = '?doctor_id=' + selectedId;
                });
            }
        });

        async function fetchData() {
            const docId = doctorSelector.value;
            if (!docId) return;

            monthDisplay.textContent = "Loading...";
            try {
                // Ensure we pass target_id to AJAX so it matches the dropdown
                const response = await fetch(`?action=get_calendar_data&target_id=${docId}`);
                const data = await response.json();
                
                if (data.status === 'error') throw new Error(data.message);
                
                rosterData = data.roster || [];
                leaveData = data.leaves || [];
                renderCalendar();
            } catch (error) {
                grid.innerHTML = `<div class="col-span-7 py-12 text-center text-red-500">Error: ${error.message}</div>`;
            }
        }

        function renderCalendar() {
            grid.innerHTML = '';
            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => 
                grid.innerHTML += `<div class="day-header">${d}</div>`
            );

            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            monthDisplay.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentDate);

            const firstDayIndex = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            today.setHours(0,0,0,0);

            // Empty cells for prev month
            for (let i = 0; i < firstDayIndex; i++) {
                const cell = document.createElement('div');
                cell.className = 'day-cell other-month';
                grid.appendChild(cell);
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const dateObj = new Date(year, month, d);
                const dateStr = [
                    dateObj.getFullYear(), 
                    String(dateObj.getMonth() + 1).padStart(2, '0'), 
                    String(dateObj.getDate()).padStart(2, '0')
                ].join('-');
                
                const dayIndex = dateObj.getDay(); 
                
                // Important: Match DB Logic. (JS 0=Sun, 1=Mon...6=Sat) to (DB 1=Mon, 7=Sun)
                let dbDay = (dayIndex === 0) ? 7 : dayIndex; 
                const shift = rosterData.find(r => parseInt(r.day) === dbDay);

                const isPast = dateObj < today;
                const leave = leaveData.find(l => dateStr >= l.date_start && dateStr <= l.date_end);
                
                const cell = document.createElement('div');
                cell.className = 'day-cell';
                if (isPast) cell.classList.add('day-past');

                let html = `<span class="text-sm font-semibold text-gray-700">${d}</span>`;

                if (leave) {
                    html += `<div class="event-chip event-block">⛔ ${leave.reason}</div>`;
                    cell.style.background = '#fff1f2';
                } else if (shift) {
                    html += `<div class="event-chip event-roster">${formatTime(shift.time)}-${formatTime(shift.time2)}</div>`;
                }

                cell.innerHTML = html;

                if (!isPast) {
                    cell.onclick = () => openModal(dateStr, dbDay, dayIndex, shift, leave);
                }

                grid.appendChild(cell);
            }
        }

        function openModal(dateStr, dbDay, jsDayIndex, shift, leave) {
            const modal = document.getElementById('slotModal');
            const dateObj = new Date(dateStr);
            
            document.getElementById('modalDateDisplay').textContent = dateObj.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
            document.getElementById('rosterDayName').textContent = daysOfWeek[jsDayIndex];

            // Set Hidden Inputs
            document.getElementById('formBlockDate').value = dateStr;
            document.getElementById('formUnblockDate').value = dateStr;
            document.getElementById('formRosterDayIndex').value = dbDay; 
            
            const docId = doctorSelector.value;
            if(document.getElementById('formBlockDocId')) document.getElementById('formBlockDocId').value = docId;
            if(document.getElementById('formUnblockDocId')) document.getElementById('formUnblockDocId').value = docId;
            if(document.getElementById('formRosterDocId')) document.getElementById('formRosterDocId').value = docId;

            // Toggle Block/Unblock UI
            if (leave) {
                document.getElementById('blockForm').classList.add('hidden');
                document.getElementById('unblockForm').classList.remove('hidden');
                document.getElementById('blockReasonDisplay').textContent = `Reason: ${leave.reason}`;
                switchModalTab('block');
            } else {
                document.getElementById('unblockForm').classList.add('hidden');
                document.getElementById('blockForm').classList.remove('hidden');
                switchModalTab('roster'); 
            }

            // Setup Roster Form
            const rosterInputs = document.getElementById('rosterInputs');
            const rosterActive = document.getElementById('rosterActive');
            const rosterStart = document.getElementById('rosterStart');
            const rosterEnd = document.getElementById('rosterEnd');
            const rosterMax = document.getElementById('rosterMax');

            // Function to calculate and update Max Patients based on Start/End times
            const updateMaxPatients = () => {
                const start = rosterStart.value;
                const end = rosterEnd.value;
                
                // Only calculate if times are set
                if (start && end) {
                    rosterMax.value = calculateMaxPatients(start, end);
                } else {
                    rosterMax.value = 1; // Default to 1 if times are not set
                }
            }
            // Attach event listeners to trigger calculation on time change
            rosterStart.onchange = updateMaxPatients;
            rosterEnd.onchange = updateMaxPatients;

            
            if (shift) {
                rosterActive.checked = true;
                rosterStart.value = shift.time;
                rosterEnd.value = shift.time2;
                rosterInputs.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                rosterActive.checked = false;
                rosterInputs.classList.add('opacity-50', 'pointer-events-none');
                // Set default times if inactive
                rosterStart.value = '08:00';
                rosterEnd.value = '17:00';
            }

            // Always run initial calculation when modal opens (after setting times from shift/default)
            updateMaxPatients();

            // Toggle activation logic
            rosterActive.onclick = function() {
                if(this.checked) {
                    rosterInputs.classList.remove('opacity-50', 'pointer-events-none');
                    updateMaxPatients(); // Recalculate based on current/default times
                }
                else {
                    rosterInputs.classList.add('opacity-50', 'pointer-events-none');
                }
            };

            modal.classList.remove('hidden');
        }

        function closeModal() { document.getElementById('slotModal').classList.add('hidden'); }

        function switchModalTab(tab) {
            const blockContent = document.getElementById('content-block');
            const rosterContent = document.getElementById('content-roster');
            const tabBlock = document.getElementById('tab-block');
            const tabRoster = document.getElementById('tab-roster');

            if (tab === 'block') {
                blockContent.classList.remove('hidden');
                rosterContent.classList.add('hidden');
                
                tabBlock.className = "flex-1 py-2 text-sm font-bold text-red-600 border-b-2 border-red-600 focus:outline-none transition";
                tabRoster.className = "flex-1 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-purple-600 focus:outline-none transition";
            } else {
                blockContent.classList.add('hidden');
                rosterContent.classList.remove('hidden');

                tabBlock.className = "flex-1 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-red-600 focus:outline-none transition";
                tabRoster.className = "flex-1 py-2 text-sm font-bold text-purple-600 border-b-2 border-purple-600 transition";
            }
        }

        function formatTime(t) { 
            if(!t) return ''; 
            const [h, m] = t.split(':'); 
            const d = new Date(); 
            d.setHours(h, m); 
            return d.toLocaleTimeString('en-US', {hour:'numeric', minute:'numeric', hour12:true}); 
        }
        
        document.getElementById('prevMonth').onclick = () => { currentDate.setMonth(currentDate.getMonth()-1); fetchData(); };
        document.getElementById('nextMonth').onclick = () => { currentDate.setMonth(currentDate.getMonth()+1); fetchData(); };
        document.getElementById('todayBtn').onclick = () => { currentDate = new Date(); fetchData(); };
    </script>
</body>
</html>