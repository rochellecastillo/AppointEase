<?php
include __DIR__ . '/controllers/doctor_appointments_data.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointments - AppointEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .status-badge { @apply px-2 py-1 rounded-full text-xs font-bold uppercase tracking-wide; }
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
                
                <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Appointments</h1>
                        <p class="text-slate-500">Manage your patient bookings and schedule.</p>
                    </div>
                    
                    <div class="bg-white p-1 rounded-xl border border-slate-200 shadow-sm inline-flex">
                        <a href="?status=upcoming" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'upcoming' ? 'bg-green-100 text-green-700 shadow-sm' : 'text-slate-500 hover:bg-slate-50' ?>">
                            Upcoming
                        </a>
                        <a href="?status=pending" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'pending' ? 'bg-yellow-100 text-yellow-700 shadow-sm' : 'text-slate-500 hover:bg-slate-50' ?>">
                            Pending Requests
                        </a>
                        <a href="?status=history" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'history' ? 'bg-slate-100 text-slate-700 shadow-sm' : 'text-slate-500 hover:bg-slate-50' ?>">
                            History
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl border flex items-center gap-3 <?= $msg_type === 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' ?>">
                        <i data-lucide="<?= $msg_type === 'success' ? 'check-circle' : 'alert-circle' ?>" class="w-5 h-5"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="space-y-4">
                    <?php if (empty($appointments)): ?>
                        <div class="bg-white rounded-2xl border border-slate-200 border-dashed p-12 text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="calendar-off" class="text-slate-400" width="32"></i>
                            </div>
                            <h3 class="text-lg font-bold text-slate-700">No appointments found</h3>
                            <p class="text-slate-500 text-sm mt-1">Try changing the filter or check back later.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($appointments as $apt): 
                            $patientName = htmlspecialchars($apt['first_name'] . ' ' . $apt['last_name']);
                            $timeStr = date('h:i A', strtotime($apt['booking_time']));
                            $dateStr = date('M d, Y (D)', strtotime($apt['booking_date']));
                            $age = getAge($apt['bdate']);
                        ?>
                        <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition flex flex-col md:flex-row gap-6 items-start md:items-center group">
                            
                            <div class="flex-shrink-0 w-full md:w-32 text-center bg-slate-50 rounded-lg p-3 border border-slate-100 group-hover:border-green-200 group-hover:bg-green-50 transition-colors">
                                <p class="text-xs font-bold text-slate-500 uppercase group-hover:text-green-600"><?= date('M d', strtotime($apt['booking_date'])) ?></p>
                                <p class="text-xl font-bold text-slate-800 group-hover:text-green-700"><?= $timeStr ?></p>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3 mb-1">
                                    <h3 class="text-lg font-bold text-slate-800 truncate"><?= $patientName ?></h3>
                                    <?php if($filter === 'pending'): ?>
                                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded-full font-bold uppercase">Pending Approval</span>
                                    <?php elseif($apt['status'] == 0): ?>
                                        <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded-full font-bold uppercase">Cancelled</span>
                                    <?php elseif($apt['status'] == 3): ?>
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full font-bold uppercase">Completed</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-bold uppercase">Confirmed</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex flex-wrap gap-4 text-sm text-slate-500 mt-2">
                                    <span class="flex items-center gap-1"><i data-lucide="user" width="14"></i> <?= $age ?> yrs, <?= htmlspecialchars($apt['gender']) ?></span>
                                    <span class="flex items-center gap-1"><i data-lucide="phone" width="14"></i> <?= htmlspecialchars($apt['contact']) ?></span>
                                    <span class="flex items-center gap-1"><i data-lucide="map-pin" width="14"></i> <?= htmlspecialchars($apt['address']) ?></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 w-full md:w-auto mt-2 md:mt-0">
                                <?php if($filter === 'pending'): ?>
                                    <form method="POST" class="flex gap-2 w-full">
                                        <input type="hidden" name="appt_id" value="<?= $apt['id'] ?>">
                                        <button type="submit" name="action_type" value="decline" class="flex-1 md:flex-none px-4 py-2 border border-slate-300 text-slate-600 font-medium rounded-lg hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition text-sm">
                                            Decline
                                        </button>
                                        <button type="submit" name="action_type" value="approve" class="flex-1 md:flex-none px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition shadow-sm text-sm">
                                            Approve
                                        </button>
                                    </form>
                                <?php elseif($filter === 'upcoming' && $apt['booking_date'] <= $today): ?>
                                    <a href="doctor_consultation.php?id=<?= $apt['id'] ?>" class="w-full md:w-auto px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm text-sm text-center">
                                        Start Consultation
                                    </a>
                                <?php endif; ?>
                                
                                <a href="doctor_view.php?patient_id=<?= $apt['user_id'] ?>" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition" title="View Records">
                                    <i data-lucide="file-clock" width="20"></i>
                                </a>
                            </div>

                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
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