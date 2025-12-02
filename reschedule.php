<?php
include __DIR__ . '/controllers/reschedule_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
<body class="bg-gray-50 text-gray-800" data-appt-id="<?= htmlspecialchars($appt_id) ?>">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/client_sidebar.php'; ?>
        <main class="flex-1 overflow-auto">
            <div class="p-6 max-w-5xl mx-auto">
                <div class="mb-8">
                    <a href="client_appointments.php" class="inline-flex items-center text-sm text-gray-500 hover:text-purple-600 mb-4">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Cancel
                    </a>
                    <h1 class="text-3xl font-bold text-gray-900">Reschedule Appointment</h1>
                    <p class="text-gray-500">With <strong><?= htmlspecialchars($doctor_name) ?></strong></p>
                </div>

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
        // 1. Handle Errors
        <?php if ($message && $msg_type == 'error'): ?>
            Swal.fire({ icon: 'error', title: 'Error', text: '<?= addslashes($message) ?>' });
        <?php endif; ?>

        // 2. Handle Success Redirect
        <?php if (isset($success_redirect)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Rescheduled!',
                text: '<?= addslashes($message) ?>',
                confirmButtonColor: '#7c3aed',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= $success_redirect ?>';
                }
            });
        <?php endif; ?>
    </script>

    <script src="js/reschedule.js"></script>
</body>
</html>