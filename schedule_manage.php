<?php
// schedule_manage.php - Visual Calendar Manager
ob_start();

require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';

session_require_auth(['admin', 'doctor']);

$user_id = session_get_user_id();
$user_type = session_get_user_type();

// --- API: AJAX HANDLER FOR CALENDAR DATA ---
if (isset($_GET['action']) && $_GET['action'] === 'get_calendar_data') {
    ob_end_clean();
    header('Content-Type: application/json');

    try {
        $target_doctor = ($user_type === 'admin') ? ($_GET['doctor_id'] ?? '') : $user_id;

        if (!$target_doctor) throw new Exception("No doctor ID selected.");

        // 1. Get Weekly Roster
        $roster_stmt = $pdo->prepare("SELECT day, time, time2, max_appointment FROM tblschedule WHERE user_id = ?");
        $roster_stmt->execute([$target_doctor]);
        
        // 2. Get Blocked Dates
        $leaves_stmt = $pdo->prepare("SELECT id, date_start, date_end, reason FROM tblnoappointment WHERE doctor_id = ?");
        $leaves_stmt->execute([$target_doctor]);
        
        echo json_encode([
            'status' => 'success',
            'roster' => $roster_stmt->fetchAll(PDO::FETCH_ASSOC),
            'leaves' => $leaves_stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

ob_end_flush();

// --- HANDLE FORM SUBMISSIONS ---
$message = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_doctor = ($user_type === 'admin') ? $_POST['doctor_id'] : $user_id;
    $todayStr = date('Y-m-d');

    // 1. BLOCK DATE
    if (isset($_POST['block_date'])) {
        $date = $_POST['date_start'];
        $reason = $_POST['reason'] ?? 'Unavailable';
        
        // VALIDATION: Prevent blocking past dates
        if ($date < $todayStr) {
            $message = "Error: You cannot block dates in the past.";
            $msg_type = 'error';
        } else {
            try {
                // Uses date_start/date_end columns from appointment(2).sql
                $stmt = $pdo->prepare("INSERT INTO tblnoappointment (doctor_id, date_start, date_end, reason) VALUES (?, ?, ?, ?)");
                // We insert the same date for start/end for a single day block
                $stmt->execute([$target_doctor, $date, $date, $reason]);
                $message = "Date blocked successfully.";
                $msg_type = 'success';
            } catch (Exception $e) { 
                $message = "DB Error: " . $e->getMessage(); 
                $msg_type = 'error'; 
            }
        }
    }

    // 2. UNBLOCK DATE (Remove Block)
    if (isset($_POST['unblock_date'])) {
        $date = $_POST['date_to_unblock'];
        try {
            // Remove block where date_start matches (assuming single day blocks for this UI)
            $stmt = $pdo->prepare("DELETE FROM tblnoappointment WHERE doctor_id = ? AND date_start = ?");
            $stmt->execute([$target_doctor, $date]);
            $message = "Date unblocked successfully.";
            $msg_type = 'success';
        } catch (Exception $e) {
            $message = "DB Error: " . $e->getMessage();
            $msg_type = 'error';
        }
    }

    // 3. UPDATE ROSTER
    if (isset($_POST['update_roster'])) {
        $day = $_POST['day_index']; 
        $time_start = $_POST['time_start'];
        $time_end = $_POST['time_end'];
        $max = $_POST['max_appointment'];
        $is_active = isset($_POST['is_active']);

        try {
            $pdo->prepare("DELETE FROM tblschedule WHERE user_id = ? AND day = ?")->execute([$target_doctor, $day]);

            if ($is_active && $time_start && $time_end) {
                $stmt = $pdo->prepare("INSERT INTO tblschedule (user_id, day, time, time2, max_appointment) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$target_doctor, $day, $time_start, $time_end, $max]);
                $message = "Weekly schedule updated.";
            } else {
                $message = "Weekly schedule removed for this day.";
            }
            $msg_type = 'success';
        } catch (Exception $e) { 
            $message = "DB Error: " . $e->getMessage(); 
            $msg_type = 'error'; 
        }
    }
}

// Fetch Doctors
$doctors = [];
if ($user_type === 'admin') {
    $doctors = $pdo->query("SELECT u.user_id, i.first_name, i.last_name FROM tbluser u JOIN tblinfo i ON u.user_id = i.user_id WHERE u.user_type='doctor'")->fetchAll();
}
$selected_doctor = ($user_type === 'admin') ? ($_GET['doctor_filter'] ?? ($doctors[0]['user_id'] ?? '')) : $user_id;
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
        /* Past Date Styling */
        .day-past { background-color: #f3f4f6 !important; color: #9ca3af; cursor: not-allowed !important; opacity: 0.7; }
        .day-past:hover { background-color: #f3f4f6 !important; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/admin_sidebar.php'; ?>

        <main class="flex-1 overflow-auto">
            <div class="p-6 max-w-7xl mx-auto">
                
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Schedule Calendar</h1>
                        <p class="text-gray-500 text-sm">Click on any future date to manage availability.</p>
                    </div>
                    
                    <?php if($user_type === 'admin'): ?>
                    <div class="bg-white p-2 rounded-xl border border-gray-200 shadow-sm">
                        <label class="text-xs font-bold uppercase text-gray-400 ml-2">Managing:</label>
                        <select id="doctorSelector" class="bg-transparent text-sm font-semibold focus:outline-none cursor-pointer">
                            <?php foreach($doctors as $d): ?>
                                <option value="<?= $d['user_id'] ?>" <?= $selected_doctor == $d['user_id'] ? 'selected' : '' ?>>
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
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                
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
                            <button onclick="switchModalTab('block')" id="tab-block" class="flex-1 py-2 text-sm font-bold text-red-600 border-b-2 border-red-600 focus:outline-none">Block Date</button>
                            <button onclick="switchModalTab('roster')" id="tab-roster" class="flex-1 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-purple-600 focus:outline-none">Weekly Roster</button>
                        </div>

                        <div id="content-block">
                            <form method="POST" id="unblockForm" class="hidden mb-4">
                                <?php if($user_type === 'admin'): ?><input type="hidden" name="doctor_id" id="formUnblockDocId"><?php endif; ?>
                                <input type="hidden" name="date_to_unblock" id="formUnblockDate">
                                
                                <div class="p-4 bg-red-50 border border-red-100 rounded-xl text-center">
                                    <p class="text-red-800 font-bold">This date is currently blocked.</p>
                                    <p class="text-xs text-red-600 mb-4" id="blockReasonDisplay"></p>
                                    
                                    <div class="flex gap-3 justify-center">
                                        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">
                                            Close
                                        </button>
                                        <button type="submit" name="unblock_date" class="px-4 py-2 bg-white border border-red-200 text-red-600 font-bold rounded-lg hover:bg-red-50 transition shadow-sm">
                                            Remove Block
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <form method="POST" id="blockForm">
                                <?php if($user_type === 'admin'): ?><input type="hidden" name="doctor_id" id="formBlockDocId"><?php endif; ?>
                                <input type="hidden" name="date_start" id="formBlockDate">
                                
                                <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-600 flex gap-2 items-start">
                                    <i data-lucide="info" class="flex-shrink-0 w-4 h-4 mt-0.5"></i>
                                    <span>Blocking this date will override any weekly roster schedule.</span>
                                </div>
                                
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Reason</label>
                                <input type="text" name="reason" placeholder="e.g., Vacation, Seminar" class="w-full p-3 border border-gray-300 rounded-xl mb-6 focus:border-red-500 focus:ring-2 focus:ring-red-200 focus:outline-none transition">
                                
                                <div class="flex gap-3">
                                    <button type="button" onclick="closeModal()" class="w-1/3 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" name="block_date" class="w-2/3 py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition shadow-lg shadow-red-200">
                                        Confirm Block
                                    </button>
                                </div>
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
                                
                                <div class="flex items-center mb-4 p-3 bg-gray-50 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-100 transition" onclick="document.getElementById('rosterActive').click()">
                                    <input type="checkbox" name="is_active" id="rosterActive" class="w-5 h-5 text-purple-600 rounded border-gray-300 focus:ring-purple-500 cursor-pointer">
                                    <label for="rosterActive" class="ml-3 text-sm font-bold text-gray-700 cursor-pointer select-none">Work on this day?</label>
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
                                        <label class="text-xs font-bold text-gray-400 uppercase mb-1 block">Max Patients</label>
                                        <input type="number" name="max_appointment" id="rosterMax" value="10" min="1" class="w-full p-2.5 border border-gray-300 rounded-lg focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                                    </div>
                                </div>

                                <div class="flex gap-3 mt-6">
                                    <button type="button" onclick="closeModal()" class="w-1/3 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" name="update_roster" class="w-2/3 py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl transition shadow-lg shadow-purple-200">
                                        Save Recurring
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.createIcons();
        
        let currentDate = new Date();
        let rosterData = [];
        let leaveData = [];
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        const doctorSelector = document.getElementById('doctorSelector');
        const monthDisplay = document.getElementById('monthDisplay');
        const grid = document.getElementById('calendarGrid');

        document.addEventListener('DOMContentLoaded', () => {
            if (doctorSelector) {
                fetchData();
                doctorSelector.addEventListener('change', fetchData);
            }
        });

        async function fetchData() {
            const docId = doctorSelector.value;
            if (!docId) { renderCalendar(); return; }

            monthDisplay.textContent = "Loading...";
            try {
                const response = await fetch(`?action=get_calendar_data&doctor_id=${docId}`);
                const data = await response.json();
                if (data.status === 'error') throw new Error(data.message);
                rosterData = data.roster || [];
                leaveData = data.leaves || [];
                renderCalendar();
            } catch (error) {
                monthDisplay.textContent = "Error";
                grid.innerHTML = `<div class="col-span-7 py-12 text-center text-red-500">${error.message}</div>`;
            }
        }

        function renderCalendar() {
            grid.innerHTML = '';
            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => grid.innerHTML += `<div class="day-header">${d}</div>`);

            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            monthDisplay.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentDate);

            const firstDayIndex = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            // Calculate Today (Reset Time)
            const today = new Date();
            today.setHours(0,0,0,0);

            for (let i = 0; i < firstDayIndex; i++) {
                grid.appendChild(Object.assign(document.createElement('div'), { className: 'day-cell other-month' }));
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const dateObj = new Date(year, month, d);
                const dateStr = [dateObj.getFullYear(), String(dateObj.getMonth() + 1).padStart(2, '0'), String(dateObj.getDate()).padStart(2, '0')].join('-');
                const dayIndex = dateObj.getDay();
                
                // Check Past
                const isPast = dateObj < today;

                const cell = document.createElement('div');
                cell.className = 'day-cell';
                if (isPast) cell.classList.add('day-past');
                
                let content = `<span class="text-sm font-semibold text-gray-700">${d}</span>`;

                // LOGIC: NO STACKING (Block Priority)
                // 1. Check if Blocked
                const leave = leaveData.find(l => dateStr >= l.date_start && dateStr <= l.date_end);
                const shift = rosterData.find(r => parseInt(r.day) === dayIndex);

                if (leave) {
                    content += `<div class="event-chip event-block">⛔ ${leave.reason}</div>`;
                    cell.style.background = '#fff1f2';
                } else if (shift) {
                    // Only show roster if NOT blocked
                    content += `<div class="event-chip event-roster">${formatTime(shift.time)}-${formatTime(shift.time2)}</div>`;
                }

                cell.innerHTML = content;
                
                // Only clickable if NOT past
                if (!isPast) {
                    cell.onclick = () => openModal(dateStr, dayIndex, shift, leave);
                }

                grid.appendChild(cell);
            }
        }

        function openModal(dateStr, dayIndex, shift, leave) {
            const modal = document.getElementById('slotModal');
            const parts = dateStr.split('-');
            const dateObj = new Date(parts[0], parts[1]-1, parts[2]);
            
            document.getElementById('modalDateDisplay').textContent = dateObj.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
            document.getElementById('rosterDayName').textContent = daysOfWeek[dayIndex];
            
            // Set Inputs
            document.getElementById('formBlockDate').value = dateStr;
            document.getElementById('formRosterDayIndex').value = dayIndex;
            document.getElementById('formUnblockDate').value = dateStr;
            
            const docId = doctorSelector.value;
            if(document.getElementById('formBlockDocId')) document.getElementById('formBlockDocId').value = docId;
            if(document.getElementById('formRosterDocId')) document.getElementById('formRosterDocId').value = docId;
            if(document.getElementById('formUnblockDocId')) document.getElementById('formUnblockDocId').value = docId;

            // Logic: If blocked, show Unblock Form
            const blockForm = document.getElementById('blockForm');
            const unblockForm = document.getElementById('unblockForm');
            
            if(leave) {
                blockForm.classList.add('hidden');
                unblockForm.classList.remove('hidden');
                document.getElementById('blockReasonDisplay').textContent = `Reason: ${leave.reason}`;
                switchModalTab('block');
            } else {
                unblockForm.classList.add('hidden');
                blockForm.classList.remove('hidden');
                switchModalTab('roster'); // Default to Roster if free
            }

            // Setup Roster UI
            const rosterInputs = document.getElementById('rosterInputs');
            const rosterActive = document.getElementById('rosterActive');
            if (shift) {
                rosterActive.checked = true;
                document.getElementById('rosterStart').value = shift.time;
                document.getElementById('rosterEnd').value = shift.time2;
                document.getElementById('rosterMax').value = shift.max_appointment;
                rosterInputs.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                rosterActive.checked = false;
                rosterInputs.classList.add('opacity-50', 'pointer-events-none');
                document.getElementById('rosterStart').value = '08:00';
                document.getElementById('rosterEnd').value = '17:00';
            }
            rosterActive.onchange = function() {
                if(this.checked) rosterInputs.classList.remove('opacity-50', 'pointer-events-none');
                else rosterInputs.classList.add('opacity-50', 'pointer-events-none');
            };

            modal.classList.remove('hidden');
        }

        function closeModal() { document.getElementById('slotModal').classList.add('hidden'); }
        function switchModalTab(tab) {
            document.getElementById('content-block').classList.add('hidden');
            document.getElementById('content-roster').classList.add('hidden');
            document.getElementById('tab-block').className = "flex-1 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-red-600 transition";
            document.getElementById('tab-roster').className = "flex-1 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-purple-600 transition";
            document.getElementById('content-' + tab).classList.remove('hidden');
            document.getElementById('tab-' + tab).className = "flex-1 py-2 text-sm font-bold " + (tab === 'block' ? 'text-red-600 border-red-600' : 'text-purple-600 border-purple-600');
        }
        function formatTime(t) { if(!t)return''; const [h,m] = t.split(':'); const d=new Date(); d.setHours(h,m); return d.toLocaleTimeString('en-US', {hour:'numeric', minute:'numeric', hour12:true}); }
        
        document.getElementById('prevMonth').onclick = () => { currentDate.setMonth(currentDate.getMonth()-1); renderCalendar(); };
        document.getElementById('nextMonth').onclick = () => { currentDate.setMonth(currentDate.getMonth()+1); renderCalendar(); };
        document.getElementById('todayBtn').onclick = () => { currentDate = new Date(); renderCalendar(); };
    </script>
</body>
</html>