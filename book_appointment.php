<?php
// book_appointment.php - Visual Calendar for Patients
ob_start();

require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['user']);
$user_id = session_get_user_id();

// =============================================================================
// API: HANDLE AJAX REQUESTS
// =============================================================================
if (isset($_GET['action'])) {
    ob_end_clean();
    header('Content-Type: application/json');

    $doctor_id = $_GET['doctor_id'] ?? '';

    if (!$doctor_id) {
        echo json_encode(['status' => 'error', 'message' => 'No doctor selected']);
        exit;
    }

    try {
        // --- API A: Get Monthly Status ---
        if ($_GET['action'] === 'get_monthly_status') {
            $month = $_GET['month'] ?? date('n');
            $year = $_GET['year'] ?? date('Y');

            // Get Working Days (Normalize to JS 0-6)
            $stmt = $pdo->prepare("SELECT `day` FROM tblschedule WHERE user_id = ?");
            $stmt->execute([$doctor_id]);
            $raw_days = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $working_days = [];
            foreach ($raw_days as $d) {
                // If DB is 1-7 (Mon-Sun), convert 7->0 for JS Sunday
                $working_days[] = (int)($d % 7);
            }
            $working_days = array_values(array_unique($working_days));

            // Get Leave Dates
            $start_date = sprintf('%04d-%02d-01', (int)$year, (int)$month);
            $end_date = date("Y-m-t", strtotime($start_date));

            $stmt = $pdo->prepare("
                SELECT date_start, date_end, reason
                FROM tblnoappointment
                WHERE doctor_id = ?
                  AND NOT (date_end < ? OR date_start > ?)
            ");
            $stmt->execute([$doctor_id, $start_date, $end_date]);
            $leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'working_days' => $working_days,
                'leaves' => $leaves
            ]);
            exit;
        }

        // --- API B: Get Time Slots ---
        if ($_GET['action'] === 'get_slots') {
            $date = $_GET['date'] ?? '';
            if (!$date) exit;

            // 1. Rule: Prevent Same Day Booking
            if ($date <= date('Y-m-d')) {
                echo json_encode(['status' => 'off', 'message' => 'Appointments must be booked at least 1 day in advance.']);
                exit;
            }

            // 2. Rule: Check if User Already Has an Appointment on This Date
            // (Any active appointment, regardless of doctor)
            $userCheck = $pdo->prepare("SELECT id FROM tblappointment WHERE user_id = ? AND booking_date = ? AND status != 0");
            $userCheck->execute([$user_id, $date]);
            if ($userCheck->rowCount() > 0) {
                echo json_encode(['status' => 'off', 'message' => 'You already have an appointment scheduled for this day.']);
                exit;
            }

            // 3. Check Schedule
            $day_of_week = date('N', strtotime($date)); // 1 (Mon) - 7 (Sun)
            $stmt = $pdo->prepare("SELECT * FROM tblschedule WHERE user_id = ? AND `day` = ?");
            $stmt->execute([$doctor_id, $day_of_week]);
            $schedule = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$schedule) {
                echo json_encode(['status' => 'off', 'message' => 'Doctor is not working on this day.']);
                exit;
            }

            // 4. Check Leave
            $check_leave = $pdo->prepare("SELECT 1 FROM tblnoappointment WHERE doctor_id = ? AND ? BETWEEN date_start AND date_end LIMIT 1");
            $check_leave->execute([$doctor_id, $date]);
            if ($check_leave->rowCount() > 0) {
                echo json_encode(['status' => 'leave', 'message' => 'Doctor is on leave.']);
                exit;
            }

            // 5. Calculate Slots
            $booked_stmt = $pdo->prepare("SELECT booking_time FROM tblappointment WHERE doctor = ? AND booking_date = ? AND status != 0");
            $booked_stmt->execute([$doctor_id, $date]);
            $booked_times = $booked_stmt->fetchAll(PDO::FETCH_COLUMN);

            $start = strtotime($schedule['time']);
            $end = strtotime($schedule['time2']);
            $step = 30 * 60;
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
        echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
        exit;
    }
}

ob_end_flush();

// =============================================================================
// PHP: HANDLE FORM SUBMISSION
// =============================================================================
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor = $_POST['doctor'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    if ($doctor && $date && $time) {
        
        // Server-Side Validation: Same Day Check
        if ($date <= date('Y-m-d')) {
            $message = "Error: You cannot book appointments for today or past dates.";
        } 
        else {
            // Server-Side Validation: One Appointment Per Day Check
            $limitCheck = $pdo->prepare("SELECT id FROM tblappointment WHERE user_id = ? AND booking_date = ? AND status != 0");
            $limitCheck->execute([$user_id, $date]);

            if ($limitCheck->rowCount() > 0) {
                $message = "Error: You already have an appointment on this date.";
            } 
            else {
                // Final Redundancy Check (Slot taken?)
                $check = $pdo->prepare("SELECT id FROM tblappointment WHERE doctor=? AND booking_date=? AND booking_time=? AND status!=0");
                $check->execute([$doctor, $date, $time]);
                
                if ($check->rowCount() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO tblappointment (booking_date, booking_time, user_id, doctor, status) VALUES (?, ?, ?, ?, 2)");
                    if ($stmt->execute([$date, $time, $user_id, $doctor])) {
                        header("Location: client_appointments.php?booking=success");
                        exit;
                    }
                } else {
                    $message = "Error: This slot was just booked by someone else.";
                }
            }
        }
    } else {
        $message = "Please complete all fields.";
    }
}

// Fetch Doctors
$doctors = $pdo->query("SELECT t.user_id, t.first_name, t.last_name, t.specialization FROM tblinfo t JOIN tbluser u ON t.user_id = u.user_id WHERE u.user_type='doctor'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
        .day-cell { 
            aspect-ratio: 1/1; display: flex; align-items: center; justify-content: center; 
            border-radius: 8px; font-size: 0.9rem; font-weight: 500; transition: all 0.2s; cursor: pointer;
        }
        .day-header { font-size: 0.75rem; font-weight: 600; text-align: center; color: #9ca3af; padding-bottom: 8px; text-transform: uppercase; }
        
        /* States */
        .day-available { background-color: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
        .day-available:hover { background-color: #16a34a; color: white; border-color: #16a34a; }
        .day-selected { background-color: #7c3aed !important; color: white !important; border-color: #7c3aed !important; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); }
        
        .day-off { background-color: #f3f4f6; color: #d1d5db; cursor: not-allowed; } 
        .day-leave { background-color: #fef2f2; color: #ef4444; border: 1px solid #fee2e2; cursor: not-allowed; opacity: 0.8; }
        .day-past { opacity: 0.3; pointer-events: none; }

        .slot-radio:checked + div { background-color: #7c3aed; color: white; border-color: #7c3aed; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/client_sidebar.php'; ?>
        
        <main class="flex-1 overflow-auto">
            <div class="md:hidden p-4 flex items-center justify-between bg-white border-b sticky top-0 z-20">
                <span class="font-bold text-lg text-purple-700">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-gray-100 rounded-lg"><i data-lucide="menu"></i></button>
            </div>

            <div class="p-6 max-w-6xl mx-auto">
                <header class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900">Schedule Appointment</h1>
                    <p class="text-gray-500">Select a doctor and choose a date.</p>
                    <?php if ($message): ?>
                        <div class="mt-4 p-3 bg-red-100 text-red-700 rounded-lg flex items-center gap-2">
                            <i data-lucide="alert-circle" width="18"></i> <?= e($message) ?>
                        </div>
                    <?php endif; ?>
                </header>

                <form method="POST" id="bookingForm" class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-full">
                    
                    <div class="lg:col-span-7 space-y-6">
                        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Select Specialist</label>
                            <div class="relative">
                                <i data-lucide="stethoscope" class="absolute left-3 top-3.5 text-gray-400 w-5 h-5"></i>
                                <select name="doctor" id="doctorSelect" class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 transition">
                                    <option value="" disabled selected>Choose a doctor...</option>
                                    <?php foreach ($doctors as $doc): ?>
                                        <option value="<?= e($doc['user_id']) ?>">Dr. <?= e($doc['first_name'].' '.$doc['last_name']) ?> (<?= e($doc['specialization']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div id="calendarContainer" class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 opacity-50 pointer-events-none transition-all">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="font-bold text-lg text-gray-800" id="currentMonthYear">Month</h3>
                                <div class="flex gap-1">
                                    <button type="button" id="prevMonthBtn" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="chevron-left" class="w-5 h-5"></i></button>
                                    <button type="button" id="todayBtn" class="text-xs font-bold text-purple-600 px-3 hover:bg-purple-50 rounded-lg">Today</button>
                                    <button type="button" id="nextMonthBtn" class="p-2 hover:bg-gray-100 rounded-lg"><i data-lucide="chevron-right" class="w-5 h-5"></i></button>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 text-xs mb-4 px-1">
                                <div class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-green-100 border border-green-200"></span> Available</div>
                                <div class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-gray-100 border border-gray-200"></span> Off</div>
                                <div class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-100 border border-red-200"></span> Leave</div>
                            </div>

                            <div class="calendar-grid mb-2">
                                <div class="day-header">Sun</div><div class="day-header">Mon</div><div class="day-header">Tue</div>
                                <div class="day-header">Wed</div><div class="day-header">Thu</div><div class="day-header">Fri</div><div class="day-header">Sat</div>
                            </div>
                            <div id="calendarDays" class="calendar-grid"></div>
                            <input type="hidden" name="date" id="selectedDateInput" required>
                        </div>
                    </div>

                    <div class="lg:col-span-5">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 h-full flex flex-col relative">
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                <i data-lucide="clock" class="text-purple-600 w-5 h-5"></i> Available Times
                            </h3>
                            
                            <div id="slotEmptyState" class="flex-1 flex flex-col items-center justify-center text-center text-gray-400 py-12">
                                <i data-lucide="calendar-check" class="w-12 h-12 mb-3 text-gray-200"></i>
                                <p class="text-sm">Select a date from the calendar<br>to view available slots.</p>
                            </div>

                            <div id="slotLoading" class="hidden flex-1 flex flex-col items-center justify-center text-purple-600 py-12">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mb-2"></div>
                                <span class="text-sm">Checking availability...</span>
                            </div>

                            <div id="slotsGrid" class="hidden grid grid-cols-2 gap-3 overflow-y-auto max-h-[500px] pr-2"></div>
                            
                            <div id="submitArea" class="hidden mt-auto pt-6 border-t border-gray-100">
                                <p id="selectionSummary" class="text-sm text-gray-600 mb-3 text-center"></p>
                                <input type="hidden" name="time" id="selectedTimeInput">
                                <button type="submit" class="w-full py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-lg shadow-purple-200 transition transform active:scale-95">
                                    Confirm Appointment
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

        const calendarContainer = document.getElementById('calendarContainer');
        const calendarDays = document.getElementById('calendarDays');
        const monthDisplay = document.getElementById('currentMonthYear');
        const doctorSelect = document.getElementById('doctorSelect');
        const selectedDateInput = document.getElementById('selectedDateInput');
        const selectedTimeInput = document.getElementById('selectedTimeInput');
        
        const slotsGrid = document.getElementById('slotsGrid');
        const slotEmptyState = document.getElementById('slotEmptyState');
        const slotLoading = document.getElementById('slotLoading');
        const submitArea = document.getElementById('submitArea');
        const summary = document.getElementById('selectionSummary');

        doctorSelect.addEventListener('change', () => {
            if(doctorSelect.value) {
                calendarContainer.classList.remove('opacity-50', 'pointer-events-none');
                loadDoctorSchedule();
            }
        });

        async function loadDoctorSchedule() {
            const docId = doctorSelect.value;
            const month = currentDate.getMonth() + 1;
            const year = currentDate.getFullYear();

            try {
                const res = await fetch(`?action=get_monthly_status&doctor_id=${docId}&month=${month}&year=${year}`);
                const contentType = res.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                   throw new Error("Server Error: Non-JSON response received.");
                }

                const data = await res.json();
                
                if(data.status === 'success') {
                    doctorWorkingDays = data.working_days.map(Number);
                    doctorLeaves = data.leaves;
                    renderCalendar();
                } else {
                    alert('Error loading schedule: ' + data.message);
                }
            } catch(e) { 
                console.error(e);
                alert('Failed to connect to the server. Please check your connection or try again.');
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

            for(let i = 0; i < firstDay; i++) {
                calendarDays.appendChild(document.createElement('div'));
            }

            for(let d = 1; d <= daysInMonth; d++) {
                const dateObj = new Date(year, month, d);
                const dateString = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
                const dayOfWeek = dateObj.getDay();

                const cell = document.createElement('div');
                cell.className = 'day-cell';
                cell.textContent = d;

                // Logic: Cannot be past OR today
                const isPastOrToday = dateObj <= today;
                const isWorkingDay = doctorWorkingDays.includes(dayOfWeek);
                const isLeave = checkLeave(dateString);

                if(isPastOrToday) {
                    cell.classList.add('day-past');
                    if(dateObj.getTime() === today.getTime()) cell.title = "Cannot book today";
                } else if(isLeave) {
                    cell.classList.add('day-leave');
                    cell.title = "Doctor on Leave";
                } else if(!isWorkingDay) {
                    cell.classList.add('day-off');
                    cell.title = "Doctor's Day Off";
                } else {
                    cell.classList.add('day-available');
                    cell.onclick = () => selectDate(cell, dateString);
                    if(selectedDate === dateString) cell.classList.add('day-selected');
                }
                calendarDays.appendChild(cell);
            }
        }

        function checkLeave(dateStr) {
            return doctorLeaves.some(leave => dateStr >= leave.date_start && dateStr <= leave.date_end);
        }

        document.getElementById('prevMonthBtn').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            loadDoctorSchedule();
        });
        document.getElementById('nextMonthBtn').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            loadDoctorSchedule();
        });
        document.getElementById('todayBtn').addEventListener('click', () => {
            currentDate = new Date();
            loadDoctorSchedule();
        });

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
            
            const docId = doctorSelect.value;
            
            try {
                const res = await fetch(`?action=get_slots&doctor_id=${docId}&date=${dateStr}`);
                const data = await res.json();
                
                slotLoading.classList.add('hidden');
                
                if(data.status === 'success') {
                    renderSlots(data.slots);
                } else {
                    // Display custom messages like "You already have an appointment"
                    slotsGrid.innerHTML = `<div class="col-span-2 text-red-500 text-center py-4 bg-red-50 rounded-lg border border-red-100 text-sm p-4">${data.message}</div>`;
                    slotsGrid.classList.remove('hidden');
                }
            } catch(e) { console.error(e); }
        }

        function renderSlots(slots) {
            slotsGrid.innerHTML = '';
            slotsGrid.classList.remove('hidden');
            
            if(slots.length === 0) {
                slotsGrid.innerHTML = `<div class="col-span-2 text-gray-500 text-center py-4">No available slots for this date.</div>`;
                return;
            }

            slots.forEach(slot => {
                const label = document.createElement('label');
                label.className = 'cursor-pointer block';
                
                let content = '';
                if(slot.available) {
                    content = `
                        <input type="radio" name="time_slot" value="${slot.time}" class="slot-radio sr-only">
                        <div class="py-2 px-4 rounded-lg border border-gray-200 text-center text-sm hover:border-purple-500 hover:text-purple-600 transition">
                            ${slot.display}
                        </div>
                    `;
                    label.addEventListener('change', () => {
                        selectedTimeInput.value = slot.time;
                        submitArea.classList.remove('hidden');
                        summary.textContent = `Selected: ${slot.display} on ${selectedDate}`;
                    });
                } else {
                    content = `
                        <div class="py-2 px-4 rounded-lg border border-gray-100 bg-gray-50 text-gray-400 text-center text-sm line-through cursor-not-allowed">
                            ${slot.display}
                        </div>
                    `;
                }
                label.innerHTML = content;
                slotsGrid.appendChild(label);
            });
        }

        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>