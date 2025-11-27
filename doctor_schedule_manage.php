<?php
// doctor_schedule_manage.php - Doctor's Availability Manager
ob_start();

require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['doctor']);
$user_id = session_get_user_id();

// --- API: AJAX HANDLER ---
if (isset($_GET['action']) && $_GET['action'] === 'get_calendar_data') {
    ob_end_clean();
    header('Content-Type: application/json');

    try {
        // 1. Get Weekly Roster
        $roster_stmt = $pdo->prepare("SELECT day, time, time2, max_appointment FROM tblschedule WHERE user_id = ?");
        $roster_stmt->execute([$user_id]);
        
        // 2. Get Blocked Dates
        $leaves_stmt = $pdo->prepare("SELECT id, date_start, date_end, reason FROM tblnoappointment WHERE doctor_id = ?");
        $leaves_stmt->execute([$user_id]);
        
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
    $todayStr = date('Y-m-d');

    // 1. BLOCK DATE
    if (isset($_POST['block_date'])) {
        $date = $_POST['date_start'];
        $reason = $_POST['reason'] ?? 'Unavailable';
        
        if ($date < $todayStr) {
            $message = "Error: You cannot block dates in the past.";
            $msg_type = 'error';
        } else {
            try {
                // Check for existing block
                $check = $pdo->prepare("SELECT id FROM tblnoappointment WHERE doctor_id=? AND date_start=?");
                $check->execute([$user_id, $date]);
                
                if ($check->rowCount() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO tblnoappointment (doctor_id, date_start, date_end, reason) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $date, $date, $reason]);
                    $message = "Date blocked successfully.";
                    $msg_type = 'success';
                } else {
                    $message = "Date is already blocked.";
                    $msg_type = 'error';
                }
            } catch (Exception $e) { 
                $message = "DB Error: " . $e->getMessage(); 
                $msg_type = 'error'; 
            }
        }
    }

    // 2. UNBLOCK DATE
    if (isset($_POST['unblock_date'])) {
        $date = $_POST['date_to_unblock'];
        try {
            $stmt = $pdo->prepare("DELETE FROM tblnoappointment WHERE doctor_id = ? AND date_start = ?");
            $stmt->execute([$user_id, $date]);
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
            $pdo->prepare("DELETE FROM tblschedule WHERE user_id = ? AND day = ?")->execute([$user_id, $day]);

            if ($is_active && $time_start && $time_end) {
                $stmt = $pdo->prepare("INSERT INTO tblschedule (user_id, day, time, time2, max_appointment) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $day, $time_start, $time_end, $max]);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Availability - AppointEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #e2e8f0; border: 1px solid #e2e8f0; }
        .day-cell { min-height: 100px; background: white; padding: 8px; transition: 0.2s; position: relative; cursor: pointer; }
        .day-cell:hover { background: #f8fafc; }
        .day-header { text-align: center; padding: 10px; background: #f1f5f9; font-weight: 600; font-size: 0.75rem; color: #64748b; text-transform: uppercase; }
        .event-chip { font-size: 0.7rem; padding: 4px 6px; border-radius: 4px; margin-top: 4px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .event-roster { background: #f0fdf4; color: #15803d; border-left: 3px solid #16a34a; }
        .event-block { background: #fef2f2; color: #991b1b; border-left: 3px solid #ef4444; }
        .other-month { opacity: 0.4; pointer-events: none; background: #f8fafc; }
        .day-past { background-color: #f1f5f9 !important; color: #94a3b8; cursor: not-allowed !important; }
        
        @media (max-width: 640px) {
            .day-cell { min-height: 80px; }
            .event-chip { font-size: 0.6rem; padding: 2px 4px; }
        }
    </style>
</head>
<body class="text-slate-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/doctor_sidebar.php'; ?>

        <main class="flex-1 overflow-auto w-full">
            <!-- Mobile Header -->
            <div class="md:hidden bg-white p-4 border-b flex justify-between items-center sticky top-0 z-30">
                <span class="font-bold text-lg text-slate-800">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-slate-100 rounded-lg"><i data-lucide="menu" width="20"></i></button>
            </div>

            <div class="p-6 md:p-8 max-w-7xl mx-auto">
                
                <div class="flex flex-col md:flex-row justify-between items-end mb-6 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">My Schedule</h1>
                        <p class="text-slate-500">Manage your weekly roster and block dates for leave.</p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl border flex items-center gap-2 <?= $msg_type == 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700' ?>">
                        <?= e($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Calendar -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <!-- Controls -->
                    <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-slate-800" id="monthDisplay">Loading...</h2>
                        
                        <div class="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                            <div class="flex items-center gap-3 text-xs">
                                <div class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-100 border border-green-200"></span> Working</div>
                                <div class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-100 border border-red-200"></span> Blocked</div>
                            </div>
                            <div class="flex gap-2">
                                <button id="prevMonthBtn" class="p-2 hover:bg-slate-100 rounded-lg border border-slate-200"><i data-lucide="chevron-left" width="20"></i></button>
                                <button id="todayBtn" class="px-4 py-2 text-sm font-bold text-green-700 bg-green-50 hover:bg-green-100 rounded-lg transition">Today</button>
                                <button id="nextMonthBtn" class="p-2 hover:bg-slate-100 rounded-lg border border-slate-200"><i data-lucide="chevron-right" width="20"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Grid -->
                    <div class="calendar-grid" id="calendarGrid"></div>
                </div>

            </div>
        </main>
    </div>

    <!-- MODAL -->
    <div id="slotModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/50 transition-opacity backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    
                    <div class="bg-slate-50 px-4 py-4 sm:px-6 border-b border-slate-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold leading-6 text-slate-900">Manage Availability</h3>
                            <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                                <i data-lucide="x" width="24"></i>
                            </button>
                        </div>
                        <p class="text-slate-500 text-sm mt-1" id="modalDateDisplay">Date</p>
                    </div>

                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <!-- Tabs -->
                        <div class="flex border-b border-slate-200 mb-6">
                            <button onclick="switchModalTab('block')" id="tab-block" class="flex-1 py-3 text-sm font-bold text-red-600 border-b-2 border-red-600 focus:outline-none">Block Date</button>
                            <button onclick="switchModalTab('roster')" id="tab-roster" class="flex-1 py-3 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:text-green-600 focus:outline-none">Weekly Roster</button>
                        </div>

                        <!-- BLOCK TAB -->
                        <div id="content-block">
                            <!-- Unblock Form -->
                            <form method="POST" id="unblockForm" class="hidden mb-4">
                                <input type="hidden" name="date_to_unblock" id="formUnblockDate">
                                <div class="p-4 bg-red-50 border border-red-100 rounded-xl text-center">
                                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3 text-red-600">
                                        <i data-lucide="calendar-x" width="24"></i>
                                    </div>
                                    <p class="text-red-900 font-bold">This date is currently blocked.</p>
                                    <p class="text-sm text-red-600 mb-4" id="blockReasonDisplay"></p>
                                    <div class="flex gap-3 justify-center">
                                        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 font-medium rounded-lg hover:bg-slate-50">Close</button>
                                        <button type="submit" name="unblock_date" class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 shadow-sm">Remove Block</button>
                                    </div>
                                </div>
                            </form>

                            <!-- Block Form -->
                            <form method="POST" id="blockForm">
                                <input type="hidden" name="date_start" id="formBlockDate">
                                
                                <div class="mb-6 p-4 bg-slate-50 border border-slate-200 rounded-xl flex gap-3 items-start">
                                    <i data-lucide="info" class="w-5 h-5 text-slate-400 shrink-0 mt-0.5"></i>
                                    <div class="text-sm text-slate-600">
                                        <p class="font-bold text-slate-800 mb-1">Override Schedule</p>
                                        Blocking this date will prevent patients from booking appointments, even if it's a regular working day.
                                    </div>
                                </div>
                                
                                <label class="block text-sm font-bold text-slate-700 mb-2">Reason for unavailability</label>
                                <input type="text" name="reason" placeholder="e.g., Vacation, Seminar, Personal" class="w-full p-3 border border-slate-300 rounded-xl mb-6 focus:border-red-500 focus:ring-2 focus:ring-red-100 focus:outline-none transition">
                                
                                <div class="flex gap-3 justify-end">
                                    <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 font-medium rounded-xl hover:bg-slate-50">Cancel</button>
                                    <button type="submit" name="block_date" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition shadow-md shadow-red-100">Confirm Block</button>
                                </div>
                            </form>
                        </div>

                        <!-- ROSTER TAB -->
                        <div id="content-roster" class="hidden">
                            <form method="POST">
                                <input type="hidden" name="day_index" id="formRosterDayIndex">
                                
                                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex gap-3 items-start">
                                    <i data-lucide="calendar-check" class="w-5 h-5 text-green-600 shrink-0 mt-0.5"></i>
                                    <div class="text-sm text-green-800">
                                        <p class="font-bold mb-1">Recurring Schedule</p>
                                        Changes here affect <strong>EVERY <span id="rosterDayName">Monday</span></strong> moving forward.
                                    </div>
                                </div>
                                
                                <label class="flex items-center p-4 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition mb-6">
                                    <input type="checkbox" name="is_active" id="rosterActive" class="w-5 h-5 text-green-600 rounded border-slate-300 focus:ring-green-500">
                                    <span class="ml-3 text-sm font-bold text-slate-700">I work on this day</span>
                                </label>
                                
                                <div id="rosterInputs" class="space-y-4 opacity-50 pointer-events-none transition-all duration-200">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Start Time</label>
                                            <input type="time" name="time_start" id="rosterStart" class="w-full p-2.5 bg-white border border-slate-300 rounded-lg focus:border-green-500 focus:ring-1 focus:ring-green-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">End Time</label>
                                            <input type="time" name="time_end" id="rosterEnd" class="w-full p-2.5 bg-white border border-slate-300 rounded-lg focus:border-green-500 focus:ring-1 focus:ring-green-500">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Max Patients / Slots</label>
                                        <input type="number" name="max_appointment" id="rosterMax" value="10" min="1" class="w-full p-2.5 bg-white border border-slate-300 rounded-lg focus:border-green-500 focus:ring-1 focus:ring-green-500">
                                    </div>
                                </div>

                                <div class="flex gap-3 justify-end mt-6">
                                    <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 font-medium rounded-xl hover:bg-slate-50">Cancel</button>
                                    <button type="submit" name="update_roster" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition shadow-md shadow-green-100">Save Schedule</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        let currentDate = new Date();
        let rosterData = [];
        let leaveData = [];
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        const monthDisplay = document.getElementById('monthDisplay');
        const grid = document.getElementById('calendarGrid');

        document.addEventListener('DOMContentLoaded', () => {
            fetchData();
        });

        async function fetchData() {
            monthDisplay.textContent = "Loading...";
            try {
                const response = await fetch(`?action=get_calendar_data`);
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
            const today = new Date();
            today.setHours(0,0,0,0);

            for (let i = 0; i < firstDayIndex; i++) {
                grid.appendChild(Object.assign(document.createElement('div'), { className: 'day-cell other-month' }));
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const dateObj = new Date(year, month, d);
                const dateStr = [dateObj.getFullYear(), String(dateObj.getMonth() + 1).padStart(2, '0'), String(dateObj.getDate()).padStart(2, '0')].join('-');
                const dayIndex = dateObj.getDay();
                const isPast = dateObj < today;

                const cell = document.createElement('div');
                cell.className = 'day-cell';
                if (isPast) cell.classList.add('day-past');
                
                let content = `<span class="text-xs font-bold text-slate-400 absolute top-2 left-2">${d}</span>`;

                // LOGIC: NO STACKING (Block Priority)
                const leave = leaveData.find(l => l.date_start === dateStr);
                const shift = rosterData.find(r => parseInt(r.day) === dayIndex);

                if (leave) {
                    content += `<div class="mt-6 event-chip event-block">⛔ ${leave.reason}</div>`;
                    cell.style.background = '#fff1f2';
                } else if (shift) {
                    content += `<div class="mt-6 event-chip event-roster">${formatTime(shift.time)}-${formatTime(shift.time2)}</div>`;
                }

                cell.innerHTML = content;
                
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
                switchModalTab('roster'); // Default to Roster
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
                document.getElementById('rosterStart').value = '09:00';
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
            
            document.getElementById('tab-block').className = "flex-1 py-3 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:text-red-600 transition focus:outline-none";
            document.getElementById('tab-roster').className = "flex-1 py-3 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:text-green-600 transition focus:outline-none";

            document.getElementById('content-' + tab).classList.remove('hidden');
            document.getElementById('tab-' + tab).className = "flex-1 py-3 text-sm font-bold " + (tab === 'block' ? 'text-red-600 border-red-600' : 'text-green-600 border-green-600');
        }

        function formatTime(t) { if(!t)return''; const [h,m] = t.split(':'); const d=new Date(); d.setHours(h,m); return d.toLocaleTimeString('en-US', {hour:'numeric', minute:'numeric', hour12:true}); }
        
        document.getElementById('prevMonthBtn').onclick = () => { currentDate.setMonth(currentDate.getMonth()-1); renderCalendar(); };
        document.getElementById('nextMonthBtn').onclick = () => { currentDate.setMonth(currentDate.getMonth()+1); renderCalendar(); };
        document.getElementById('todayBtn').onclick = () => { currentDate = new Date(); renderCalendar(); };
        
        // Mobile Sidebar Toggle
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        
        if (mobileBtn && sidebar) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('flex');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
                sidebar.classList.toggle('z-50');
                sidebar.classList.toggle('w-full'); 
            });
        }
    </script>
</body>
</html>