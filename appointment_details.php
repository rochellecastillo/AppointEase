<?php
include __DIR__ . '/controllers/appointment_details_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white; }
            .shadow-sm, .shadow-lg { box-shadow: none !important; }
            .border { border: 1px solid #ddd !important; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <div class="no-print">
            <?php include 'includes/client_sidebar.php'; ?>
        </div>

        <main class="flex-1 overflow-auto relative w-full">
            <div class="md:hidden p-4 flex items-center justify-between bg-white border-b sticky top-0 z-20 no-print">
                <span class="font-bold text-lg text-purple-700">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-gray-100 rounded-lg"><i data-lucide="menu"></i></button>
            </div>

            <div class="p-6 max-w-5xl mx-auto">
                <div class="mb-6 flex items-center justify-between no-print">
                    <a href="client_home.php" class="inline-flex items-center text-sm text-gray-500 hover:text-purple-600 transition">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back to Dashboard
                    </a>
                    <div class="text-sm text-gray-400 font-mono">ID: #<?= str_pad((int)$appointment['id'], 5, '0', STR_PAD_LEFT) ?></div>
                </div>

                <?php if ($msg === 'success_cancelled'): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-700 rounded-xl flex items-center gap-3 no-print">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <div>
                            <span class="font-bold">Appointment Cancelled.</span> The doctor has been notified.
                        </div>
                    </div>
                <?php elseif ($msg === 'error_completed'): ?>
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-100 text-blue-700 rounded-xl flex items-center gap-3 no-print">
                        <i data-lucide="info" class="w-5 h-5"></i>
                        <div>
                            <span class="font-bold">Action not allowed.</span> Completed appointments cannot be rescheduled or cancelled.
                        </div>
                    </div>
                <?php elseif ($msg === 'error_already_cancelled'): ?>
                    <div class="mb-6 p-4 bg-gray-50 border border-gray-100 text-gray-700 rounded-xl flex items-center gap-3 no-print">
                        <i data-lucide="slash" class="w-5 h-5"></i>
                        <div>
                            This appointment is already cancelled.
                        </div>
                    </div>
                <?php elseif ($msg === 'error_access'): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-700 rounded-xl flex items-center gap-3 no-print">
                        <i data-lucide="shield-off" class="w-5 h-5"></i>
                        <div>
                            You are not authorized to perform this action.
                        </div>
                    </div>
                <?php elseif ($msg === 'error_update'): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-100 text-red-700 rounded-xl flex items-center gap-3 no-print">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <div>
                            Unable to update appointment. Please try again later.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50/30">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Appointment Details</h1>
                            <p class="text-gray-500 text-sm mt-1">View and manage your scheduled visit.</p>
                        </div>

                        <div class="flex items-center gap-2 px-4 py-2 rounded-full border text-sm font-bold <?= $status_classes ?>">
                            <i data-lucide="<?= $icon ?>" class="w-4 h-4"></i>
                            <?= strtoupper($status_label) ?>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2 space-y-8">
                            <div>
                                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Medical Professional</h3>
                                <div class="flex items-start gap-5">
                                    <?php if($image_path): ?>
                                        <img src="<?= $image_path ?>" class="w-20 h-20 rounded-2xl object-cover border border-gray-100 shadow-sm">
                                    <?php else: ?>
                                        <div class="w-20 h-20 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100">
                                            <i data-lucide="stethoscope" class="w-8 h-8"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <h2 class="text-xl font-bold text-gray-900"><?= e($doctor_name) ?></h2>
                                        <?php if(!empty($appointment['specialization'])): ?>
                                            <span class="inline-block mt-1 px-2.5 py-0.5 bg-blue-50 text-blue-700 rounded-md text-xs font-semibold">
                                                <?= e($appointment['specialization']) ?>
                                            </span>
                                        <?php endif; ?>

                                        <div class="mt-3 space-y-1">
                                            <?php if(!empty($appointment['doc_contact'])): ?>
                                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i> <?= e($appointment['doc_contact']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if(!empty($appointment['doc_email'])): ?>
                                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                                    <i data-lucide="mail" class="w-3.5 h-3.5"></i> <?= e($appointment['doc_email']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($schedule): ?>
                                <div class="bg-blue-50 rounded-2xl p-5 border border-blue-100">
                                    <div class="flex items-start gap-3">
                                        <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
                                            <i data-lucide="clock" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-blue-900 text-sm">Office Hours (<?= $days[$day_of_week] ?>)</h4>
                                            <p class="text-blue-700 text-sm mt-1">
                                                The doctor is available from
                                                <span class="font-semibold"><?= date('h:i A', strtotime($schedule['time'])) ?></span> to
                                                <span class="font-semibold"><?= date('h:i A', strtotime($schedule['time2'])) ?></span>.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <div class="space-y-6">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Visit Details</h3>

                            <div class="bg-gray-50 rounded-2xl p-5 border border-gray-100 space-y-5">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white rounded-lg text-gray-600 shadow-sm"><i data-lucide="calendar" class="w-5 h-5"></i></div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium">Date</p>
                                        <p class="font-bold text-gray-800"><?= date('F d, Y', strtotime($appointment['booking_date'])) ?></p>
                                        <p class="text-xs text-gray-400"><?= date('l', strtotime($appointment['booking_date'])) ?></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 border-t border-gray-200 pt-4">
                                    <div class="p-2 bg-white rounded-lg text-gray-600 shadow-sm"><i data-lucide="clock" class="w-5 h-5"></i></div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium">Time</p>
                                        <p class="font-bold text-gray-800">
                                            <?= !empty($appointment['booking_time']) ? date('h:i A', strtotime($appointment['booking_time'])) : 'TBD' ?>
                                        </p>
                                    </div>
                                </div>

                                <?php if(!empty($appointment['doc_address'])): ?>
                                <div class="flex items-center gap-3 border-t border-gray-200 pt-4">
                                    <div class="p-2 bg-white rounded-lg text-gray-600 shadow-sm"><i data-lucide="map-pin" class="w-5 h-5"></i></div>
                                    <div>
                                        <p class="text-xs text-gray-500 font-medium">Clinic Location</p>
                                        <p class="font-bold text-gray-800 text-sm leading-snug"><?= e($appointment['doc_address']) ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-6 border-t border-gray-100 flex flex-col sm:flex-row gap-3 justify-end no-print">
                        <button onclick="window.print()" class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition flex items-center justify-center gap-2">
                            <i data-lucide="printer" class="w-4 h-4"></i> Print Details
                        </button>

                        <!-- Only show reschedule/cancel when allowed -->
                        <?php if ($can_modify): ?>
                            <a href="reschedule.php?id=<?= $apt_id ?>" class="px-5 py-2.5 bg-white border border-purple-200 text-purple-700 font-semibold rounded-xl hover:bg-purple-50 transition flex items-center justify-center gap-2">
                                <i data-lucide="calendar-clock" class="w-4 h-4"></i> Reschedule
                            </a>

                            <button onclick="confirmCancel()" class="px-5 py-2.5 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 shadow-lg shadow-red-200 transition flex items-center justify-center gap-2">
                                <i data-lucide="x" class="w-4 h-4"></i> Cancel Appointment
                            </button>
                        <?php else: ?>
                            <?php if ($status_code === 3): ?>
                                <span class="px-4 py-2 rounded-md text-sm font-medium bg-blue-50 text-blue-700 border border-blue-100">Completed — No changes allowed</span>
                            <?php elseif ($status_code === 0): ?>
                                <span class="px-4 py-2 rounded-md text-sm font-medium bg-gray-50 text-gray-700 border border-gray-100">Cancelled</span>
                            <?php elseif (strtotime($booking_date) < strtotime(date('Y-m-d'))): ?>
                                <span class="px-4 py-2 rounded-md text-sm font-medium bg-amber-50 text-amber-700 border border-amber-100">This date has passed — modifications disabled</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="mt-6 bg-amber-50 border border-amber-100 rounded-2xl p-6 flex items-start gap-4 no-print">
                    <div class="text-amber-600 mt-1"><i data-lucide="alert-circle" class="w-5 h-5"></i></div>
                    <div>
                        <h3 class="font-bold text-amber-900 mb-1">Important Reminders</h3>
                        <ul class="space-y-1 text-sm text-amber-800 list-disc list-inside">
                            <li>Please arrive 15 minutes before your scheduled time.</li>
                            <li>Bring a valid ID and any previous medical records.</li>
                            <li>Cancellations must be made at least 24 hours in advance.</li>
                        </ul>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <form id="cancelForm" method="POST" style="display:none;">
        <input type="hidden" name="cancel_id" value="<?= $apt_id ?>">
    </form>

    <script>
        lucide.createIcons();

        function confirmCancel() {
            if(confirm('Are you sure you want to cancel this appointment? This action cannot be undone.')) {
                document.getElementById('cancelForm').submit();
            }
        }

        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>
