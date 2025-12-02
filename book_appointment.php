<?php
include __DIR__ . '/controllers/book_appointment_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                                        <option value="<?= e($doc['user_id']) ?>" <?= ($preselected_doctor == $doc['user_id']) ? 'selected' : '' ?>>
                                            Dr. <?= e($doc['first_name'].' '.$doc['last_name']) ?> (<?= e($doc['specialization']) ?>)
                                        </option>
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

    <script src="js/book_appointment.js"></script>

    <?php if (isset($message) && $message): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: "<?= addslashes(e($message)) ?>",
            confirmButtonColor: '#7c3aed'
        });
    </script>
    <?php endif; ?>
</body>
</html>