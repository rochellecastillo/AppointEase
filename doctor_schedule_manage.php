<?php
include __DIR__ . '/controllers/doctor_schedule_data.php';
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
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
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

                    <div class="calendar-grid" id="calendarGrid"></div>
                </div>

            </div>
        </main>
    </div>

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
                        <div class="flex border-b border-slate-200 mb-6">
                            <button onclick="switchModalTab('block')" id="tab-block" class="flex-1 py-3 text-sm font-bold text-red-600 border-b-2 border-red-600 focus:outline-none">Block Date</button>
                            <button onclick="switchModalTab('roster')" id="tab-roster" class="flex-1 py-3 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:text-green-600 focus:outline-none">Weekly Roster</button>
                        </div>

                        <div id="content-block">
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
                                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Max Patients (30 min slots)</label>
                                        <input type="number" name="max_appointment" id="rosterMax" value="1" min="1" readonly 
                                               class="w-full p-2.5 bg-slate-100 border border-slate-300 rounded-lg text-slate-600 cursor-not-allowed">
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

    <script src="js/doctor_schedule_manage.js" defer></script>

</body>
</html>