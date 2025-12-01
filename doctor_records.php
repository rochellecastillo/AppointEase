<?php
include __DIR__ . '/controllers/doctor_records_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Records - AppointEase</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
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
                
                <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Medical Records</h1>
                        <p class="text-slate-500">View and manage patient history and diagnoses.</p>
                    </div>
                    
                    <!-- Search Bar -->
                    <form method="GET" class="flex gap-2 w-full md:w-auto">
                        <?php if($patient_filter): ?>
                            <input type="hidden" name="patient_id" value="<?= e($patient_filter) ?>">
                        <?php endif; ?>
                        <div class="relative flex-1">
                            <i data-lucide="search" class="absolute left-3 top-3 text-slate-400 w-5 h-5"></i>
                            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search records..." class="w-full md:w-64 pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-green-500 transition shadow-sm">
                        </div>
                        <?php if($patient_filter || $search): ?>
                            <a href="doctor_records.php" class="px-4 py-2.5 bg-slate-100 text-slate-600 font-medium rounded-xl hover:bg-slate-200 transition">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Patient Context Header (If filtering by specific patient) -->
                <?php if($patientInfo): 
                    $pName = e($patientInfo['first_name'] . ' ' . $patientInfo['last_name']);
                    $pAvatar = !empty($patientInfo['image']) ? 'uploads/' . e($patientInfo['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($pName) . '&background=random';
                ?>
                <div class="bg-white rounded-2xl border border-slate-200 p-6 mb-8 flex flex-col md:flex-row items-center gap-6 shadow-sm relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-2 h-full bg-blue-500"></div>
                    <img src="<?= $pAvatar ?>" class="w-20 h-20 rounded-full border-4 border-blue-50 shadow-sm object-cover">
                    <div class="flex-1 text-center md:text-left">
                        <h2 class="text-2xl font-bold text-slate-800"><?= $pName ?></h2>
                        <p class="text-slate-500 flex items-center justify-center md:justify-start gap-2 mt-1">
                            <span>ID: <?= e($patientInfo['user_id']) ?></span> &bull; 
                            <span><?= getAge($patientInfo['bdate']) ?> yrs</span> &bull; 
                            <span><?= e($patientInfo['gender']) ?></span>
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <!-- This would typically link to a new appointment/consultation flow -->
                        <a href="doctor_appointments.php?search=<?= urlencode($patientInfo['last_name']) ?>" class="px-5 py-2.5 bg-slate-100 text-slate-700 font-medium rounded-xl hover:bg-slate-200 transition">
                            View Appointments
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Records Timeline -->
                <div class="space-y-6 relative before:absolute before:left-4 md:before:left-8 before:top-0 before:bottom-0 before:w-0.5 before:bg-slate-200">
                    
                    <?php if (empty($records)): ?>
                        <div class="pl-12 md:pl-20 py-8">
                            <div class="bg-white rounded-2xl border border-slate-200 border-dashed p-8 text-center">
                                <p class="text-slate-500">No medical records found matching your criteria.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($records as $rec): 
                            $dateObj = new DateTime($rec['booking_date']);
                            $fullName = e($rec['first_name'] . ' ' . $rec['last_name']);
                        ?>
                        <div class="relative pl-12 md:pl-20 group">
                            <!-- Timeline Dot -->
                            <div class="absolute left-[11px] md:left-[27px] top-6 w-4 h-4 rounded-full bg-white border-4 border-blue-500 shadow-sm z-10 group-hover:scale-110 transition-transform"></div>
                            
                            <!-- Date Label -->
                            <div class="absolute left-0 top-6 -ml-2 md:-ml-6 w-24 text-right hidden md:block">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider"><?= $dateObj->format('M d') ?></p>
                                <p class="text-xs text-slate-300"><?= $dateObj->format('Y') ?></p>
                            </div>

                            <!-- Record Card -->
                            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-all">
                                <div class="flex flex-col md:flex-row justify-between items-start mb-4 border-b border-slate-50 pb-4">
                                    <div>
                                        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                                            <?= e($rec['diagnosis']) ?>
                                            <?php if(!$patient_filter): ?>
                                                <span class="text-sm font-normal text-slate-500">for <a href="?patient_id=<?= $patientInfo['user_id'] ?? '' /* Technically needs user_id from join */ ?>" class="text-blue-600 hover:underline font-medium"><?= $fullName ?></a></span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="text-xs text-slate-400 mt-1 block md:hidden"><?= $dateObj->format('F d, Y') ?></p>
                                    </div>
                                    <span class="px-3 py-1 bg-slate-100 text-slate-600 text-xs rounded-full font-bold uppercase tracking-wide mt-2 md:mt-0">
                                        Consultation
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Prescription -->
                                    <?php if(!empty($rec['prescription'])): ?>
                                    <div class="bg-green-50 p-4 rounded-xl border border-green-100">
                                        <h4 class="text-xs font-bold text-green-800 uppercase mb-2 flex items-center gap-2">
                                            <i data-lucide="pill" width="14"></i> Prescription
                                        </h4>
                                        <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed font-mono">
                                            <?= e($rec['prescription']) ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Clinical Notes -->
                                    <?php if(!empty($rec['notes'])): ?>
                                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                        <h4 class="text-xs font-bold text-slate-500 uppercase mb-2 flex items-center gap-2">
                                            <i data-lucide="clipboard-list" width="14"></i> Clinical Notes
                                        </h4>
                                        <p class="text-sm text-slate-600 leading-relaxed">
                                            <?= e($rec['notes']) ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Footer -->
                                <!-- <div class="mt-4 pt-4 text-right">
                                    <button class="text-sm text-blue-600 font-semibold hover:text-blue-700 flex items-center justify-end gap-1 ml-auto">
                                        Edit Record <i data-lucide="edit-3" width="14"></i>
                                    </button>
                                </div> -->
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