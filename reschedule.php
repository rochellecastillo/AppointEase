<?php
// reschedule.php - Reschedule Existing Appointment
ob_start(); // Start output buffering immediately
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['user']);
$user_id = session_get_user_id();

// =============================================================================
// 1. API: HANDLE AJAX REQUESTS
// =============================================================================
if (isset($_GET['action'])) {
    // Clear any previous output
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');

    try {
        $appt_id = $_GET['id'] ?? '';
        if (!$appt_id) throw new Exception("Missing Appointment ID");

        // Fetch Doctor ID
        $stmt = $pdo->prepare("SELECT doctor FROM tblappointment WHERE id = ? AND user_id = ?");
        $stmt->execute([$appt_id, $user_id]);
        $appt_check = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appt_check) throw new Exception("Appointment not found.");
        
        $doctor_id = $appt_check['doctor'];

        // --- Action: Get Monthly Status ---
        if ($_GET['action'] === 'get_monthly_status') {
            $month = $_GET['month'] ?? date('n');
            $year = $_GET['year'] ?? date('Y');

            // 1. Get Working Days
            $stmt = $pdo->prepare("SELECT day FROM tblschedule WHERE user_id = ?");
            $stmt->execute([$doctor_id]);
            // Fetch as numbers
            $working_days_raw = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // FIX: Convert database '7' (Sunday) to JS '0' (Sunday) if necessary, 
            // or keep as is and handle in JS. 
            // For simplicity, we send what's in DB, but let's handle the mapping in PHP 'get_slots' logic mostly.
            // If your DB uses 7 for Sunday, we need to make sure the JS knows that.
            // Standard JS getDay(): 0=Sun, 1=Mon... 6=Sat.
            // Your DB seems to use: 1=?, ..., 6=Sat, 7=Sun.
            $working_days = array_map('intval', $working_days_raw);

            // 2. Get Leave Dates (FIXED COLUMN NAMES based on your SQL)
            $start_date = "$year-$month-01";
            $end_date = date("Y-m-t", strtotime($start_date));
            
            // Corrected Query: Uses date_start and date_end
            $stmt = $pdo->prepare("SELECT date_start, date_end, reason 
                                   FROM tblnoappointment 
                                   WHERE doctor_id = ? 
                                   AND (
                                       (date_start BETWEEN ? AND ?) OR 
                                       (date_end BETWEEN ? AND ?)
                                   )");
            $stmt->execute([$doctor_id, $start_date, $end_date, $start_date, $end_date]);
            $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'working_days' => $working_days, 'leaves' => $leaves]);
            exit;
        }

        // --- Action: Get Time Slots ---
        if ($_GET['action'] === 'get_slots') {
            $date = $_GET['date'] ?? '';
            if (!$date) throw new Exception("Date required");

            // FIX: Handle Day of Week (PHP 0=Sun, DB 7=Sun)
            $day_of_week = date('w', strtotime($date)); 
            if ($day_of_week == 0) {
                $day_of_week = 7; // Map PHP's Sunday(0) to your DB's Sunday(7)
            }

            // Check Schedule
            $stmt = $pdo->prepare("SELECT * FROM tblschedule WHERE user_id = ? AND day = ?");
            $stmt->execute([$doctor_id, $day_of_week]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$schedule) {
                echo json_encode(['status' => 'off', 'message' => 'Doctor is not working on this day.']);
                exit;
            }

            // Check Leave (FIXED COLUMN NAMES)
            $check_leave = $pdo->prepare("SELECT * FROM tblnoappointment 
                                          WHERE doctor_id = ? 
                                          AND ? BETWEEN date_start AND date_end");
            $check_leave->execute([$doctor_id, $date]);
            
            if ($check_leave->rowCount() > 0) {
                echo json_encode(['status' => 'leave', 'message' => 'Doctor is on leave.']);
                exit;
            }

            // Get Booked Slots
            $booked_stmt = $pdo->prepare("SELECT booking_time FROM tblappointment WHERE doctor = ? AND booking_date = ? AND status != 0 AND id != ?");
            $booked_stmt->execute([$doctor_id, $date, $appt_id]);
            $booked_times = $booked_stmt->fetchAll(PDO::FETCH_COLUMN);

            // Generate Slots
            $start = strtotime($schedule['time']);
            $end = strtotime($schedule['time2']);
            $step = 30 * 60; // 30 minutes
            $slots = [];

            for ($i = $start; $i < $end; $i += $step) {
                $timeVal = date('H:i:s', $i);
                $timeDisp = date('h:i A', $i);
                $slots[] = [
                    'time' => $timeVal, 
                    'display' => $timeDisp, 
                    'available' => !in_array($timeVal, $booked_times)
                ];
            }

            echo json_encode(['status' => 'success', 'slots' => $slots]);
            exit;
        }

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// =============================================================================
// 2. PAGE LOGIC
// =============================================================================

$appt_id = $_GET['id'] ?? '';
if (!$appt_id) {
    header('Location: client_appointments.php');
    exit;
}

$stmt = $pdo->prepare("SELECT a.*, i.first_name, i.last_name, i.specialization 
                       FROM tblappointment a 
                       JOIN tblinfo i ON a.doctor = i.user_id 
                       WHERE a.id = ? AND a.user_id = ?");
$stmt->execute([$appt_id, $user_id]);
$appt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$appt) die("Appointment not found or access denied.");

$doctor_id = $appt['doctor'];
$doctor_name = "Dr. " . htmlspecialchars($appt['first_name']) . " " . htmlspecialchars($appt['last_name']);

// Handle Form Submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_date = $_POST['date'] ?? '';
    $new_time = $_POST['time'] ?? '';

    if ($new_date && $new_time) {
        $check = $pdo->prepare("SELECT id FROM tblappointment WHERE doctor=? AND booking_date=? AND booking_time=? AND status!=0 AND id != ?");
        $check->execute([$doctor_id, $new_date, $new_time, $appt_id]);
        
        if ($check->rowCount() == 0) {
            $stmt = $pdo->prepare("UPDATE tblappointment SET booking_date = ?, booking_time = ?, status = 2 WHERE id = ?");
            if ($stmt->execute([$new_date, $new_time, $appt_id])) {
                header("Location: client_appointments.php?reschedule=success");
                exit;
            }
        } else {
            $message = "Sorry, that slot was just taken.";
        }
    } else {
        $message = "Please select a new date and time.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
        .day-cell { aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 0.9rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .day-header { font-size: 0.75rem; font-weight: 600; text-align: center; color: #9ca3af; padding-bottom: 8px; text-transform: uppercase; }
        .day-available { background-color: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
        .day-available:hover { background-color: #16a34a; color: white; border-color: #16a34a; }
        .day-selected { background-color: #7c3aed !important; color: white !important; border-color: #7c3aed !important; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); }
        .day-off { background-color: #f3f4f6; color: #d1d5db; cursor: not-allowed; }
        .day-leave { background-color: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; cursor: not-allowed; }
        .day-past { opacity: 0.3; pointer-events: none; }
        .slot-radio:checked + div { background-color: #7c3aed; color: white; border-color: #7c3aed; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/client_sidebar.php'; ?>
        <main class="flex-1 overflow-auto">
            <div class="p-6 max-w-5xl mx-auto">
                <div class="mb-8">
                    <a href="client_home.php" class="inline-flex items-center text-sm text-gray-500 hover:text-purple-600 mb-4">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Cancel
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900">Reschedule Appointment</h1>
                    <p class="text-gray-500">With <strong><?= $doctor_name ?></strong></p>
                </div>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-xl flex items-center gap-2">
                        <i data-lucide="alert-circle" width="20"></i> <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                                <i data-lucide="calendar-clock" width="24"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-blue-600 uppercase">Current Booking</p>
                                <p class="font-bold text-gray-800 text-lg">
                                    <?= date('F d, Y', strtotime($appt['booking_date'])) ?> at <?= date('h:i A', strtotime($appt['booking_time'])) ?>
                                </p>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="font-bold text-lg text-gray-800" id="currentMonthYear">Month</h3>
                                <div class="flex gap-1">
                                    <button type="button" id="prevMonthBtn" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="chevron-left" class="w-5 h-5"></i></button>
                                    <button type="button" id="todayBtn" class="text-xs font-bold text-purple-600 px-3 hover:bg-purple-50 rounded-lg">Today</button>
                                    <button type="button" id="nextMonthBtn" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="chevron-right" class="w-5 h-5"></i></button>
                                </div>
                            </div>
                            <div class="calendar-grid mb-2">
                                <div class="day-header">Sun</div><div class="day-header">Mon</div><div class="day-header">Tue</div>
                                <div class="day-header">Wed</div><div class="day-header">Thu</div><div class="day-header">Fri</div><div class="day-header">Sat</div>
                            </div>
                            <div id="calendarDays" class="calendar-grid min-h-[300px]"></div>
                            <input type="hidden" name="date" id="selectedDateInput" required>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 h-full flex flex-col">
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <i data-lucide="clock" class="text-purple-600 w-5 h-5"></i> New Time
                            </h3>
                            <div id="slotEmptyState" class="flex-1 flex flex-col items-center justify-center text-center text-gray-400 py-12">
                                <i data-lucide="calendar-check" class="w-12 h-12 mb-3 text-gray-200"></i>
                                <p class="text-sm">Select a date</p>
                            </div>
                            <div id="slotLoading" class="hidden flex-1 flex flex-col items-center justify-center text-purple-600 py-12">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mb-2"></div>
                            </div>
                            <div id="slotsGrid" class="hidden grid grid-cols-2 gap-3 overflow-y-auto max-h-[400px] pr-1"></div>
                            <div id="submitArea" class="hidden mt-auto pt-6 border-t border-gray-100">
                                <p id="selectionSummary" class="text-sm text-gray-600 mb-3 text-center"></p>
                                <input type="hidden" name="time" id="selectedTimeInput">
                                <button type="submit" class="w-full py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-lg transition">
                                    Confirm Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        let currentDate = new Date();
        let selectedDate = null;
        let doctorWorkingDays = [];
        let doctorLeaves = [];
        
        // DOM Elements
        const calendarDays = document.getElementById('calendarDays');
        const monthDisplay = document.getElementById('currentMonthYear');
        const selectedDateInput = document.getElementById('selectedDateInput');
        const selectedTimeInput = document.getElementById('selectedTimeInput');
        const slotsGrid = document.getElementById('slotsGrid');
        const slotEmptyState = document.getElementById('slotEmptyState');
        const slotLoading = document.getElementById('slotLoading');
        const submitArea = document.getElementById('submitArea');
        const summary = document.getElementById('selectionSummary');

        document.addEventListener('DOMContentLoaded', loadDoctorSchedule);

        async function loadDoctorSchedule() {
            const month = currentDate.getMonth() + 1;
            const year = currentDate.getFullYear();
            try {
                const res = await fetch(`?action=get_monthly_status&month=${month}&year=${year}&id=<?= $appt_id ?>`);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const data = await res.json();
                if(data.status === 'success') {
                    doctorWorkingDays = data.working_days.map(Number);
                    doctorLeaves = data.leaves;
                    renderCalendar();
                }
            } catch(e) { 
                console.error(e); 
                calendarDays.innerHTML = `<p class="col-span-7 text-center text-red-500 py-4">Error loading schedule.</p>`;
            }
        }

        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            monthDisplay.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentDate);
            calendarDays.innerHTML = '';
            
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            today.setHours(0,0,0,0);

            for(let i=0; i<firstDay; i++) calendarDays.appendChild(document.createElement('div'));

            for(let d=1; d<=daysInMonth; d++) {
                const dateObj = new Date(year, month, d);
                const dateStr = [year, String(month+1).padStart(2,'0'), String(d).padStart(2,'0')].join('-');
                const dayOfWeek = dateObj.getDay(); // 0=Sun, 1=Mon...
                
                // FIX: Map JS day (0=Sun) to DB day (7=Sun) logic for checking "isWorkingDay"
                // If your DB array contains 7 for Sunday, we must treat JS 0 as 7
                const dbDay = (dayOfWeek === 0) ? 7 : dayOfWeek;

                const cell = document.createElement('div');
                cell.className = 'day-cell';
                cell.textContent = d;

                const isPast = dateObj < today;
                const isLeave = checkLeave(dateStr);
                // Check against the mapped day
                const isWorkingDay = doctorWorkingDays.includes(dbDay);

                if(isPast) cell.classList.add('day-past');
                else if(isLeave) { cell.classList.add('day-leave'); cell.title = "Leave"; }
                else if(!isWorkingDay) { cell.classList.add('day-off'); cell.title = "Off"; }
                else {
                    cell.classList.add('day-available');
                    cell.onclick = () => selectDate(cell, dateStr);
                    if(selectedDate === dateStr) cell.classList.add('day-selected');
                }
                calendarDays.appendChild(cell);
            }
        }

        function checkLeave(dateStr) {
            return doctorLeaves.some(leave => dateStr >= leave.date_start && dateStr <= leave.date_end);
        }

        document.getElementById('prevMonthBtn').onclick = () => { currentDate.setMonth(currentDate.getMonth()-1); loadDoctorSchedule(); };
        document.getElementById('nextMonthBtn').onclick = () => { currentDate.setMonth(currentDate.getMonth()+1); loadDoctorSchedule(); };
        document.getElementById('todayBtn').onclick = () => { currentDate = new Date(); loadDoctorSchedule(); };

        function selectDate(cell, dateStr) {
            document.querySelectorAll('.day-selected').forEach(el => el.classList.remove('day-selected'));
            cell.classList.add('day-selected');
            selectedDate = dateStr;
            selectedDateInput.value = dateStr;
            loadSlots(dateStr);
        }

        async function loadSlots(dateStr) {
            slotEmptyState.classList.add('hidden');
            slotsGrid.classList.add('hidden');
            submitArea.classList.add('hidden');
            slotLoading.classList.remove('hidden');
            try {
                const res = await fetch(`?action=get_slots&date=${dateStr}&id=<?= $appt_id ?>`);
                const data = await res.json();
                slotLoading.classList.add('hidden');
                if(data.status === 'success') renderSlots(data.slots);
                else {
                    slotsGrid.innerHTML = `<div class="col-span-2 text-red-500 text-center py-4">${data.message}</div>`;
                    slotsGrid.classList.remove('hidden');
                }
            } catch(e) { console.error(e); }
        }

        function renderSlots(slots) {
            slotsGrid.innerHTML = '';
            slotsGrid.classList.remove('hidden');
            if(slots.length === 0) {
                slotsGrid.innerHTML = `<div class="col-span-2 text-gray-500 text-center py-4">No slots available.</div>`;
                return;
            }
            slots.forEach(slot => {
                const label = document.createElement('label');
                label.className = 'cursor-pointer block';
                if(slot.available) {
                    label.innerHTML = `
                        <input type="radio" name="time_slot" value="${slot.time}" class="slot-radio sr-only">
                        <div class="py-2 px-4 rounded-lg border border-gray-200 text-center text-sm hover:border-purple-500 hover:text-purple-600 transition">${slot.display}</div>`;
                    label.addEventListener('change', () => {
                        selectedTimeInput.value = slot.time;
                        submitArea.classList.remove('hidden');
                        summary.textContent = `Selected: ${slot.display} on ${selectedDate}`;
                    });
                } else {
                    label.innerHTML = `<div class="py-2 px-4 rounded-lg border border-gray-100 bg-gray-50 text-gray-400 text-center text-sm line-through cursor-not-allowed">${slot.display}</div>`;
                }
                slotsGrid.appendChild(label);
            });
        }

        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>