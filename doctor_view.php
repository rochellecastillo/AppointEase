<?php
include __DIR__ . '/controllers/doctor_view_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Profile - AppointEase</title>
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
            
            <div class="md:hidden bg-white p-4 border-b flex justify-between items-center sticky top-0 z-30">
                <span class="font-bold text-lg text-slate-800">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-slate-100 rounded-lg"><i data-lucide="menu" width="20"></i></button>
            </div>

            <div class="p-6 md:p-8 max-w-5xl mx-auto">
                
                <div class="mb-8">
                    <a href="patients.php" class="inline-flex items-center text-sm text-slate-500 hover:text-blue-600 mb-4 transition">
                        <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i> Back to Patients
                    </a>
                    
                    <div class="flex flex-col md:flex-row gap-6 items-start md:items-center">
                        <img src="<?= $avatar ?>" class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-md">
                        <div>
                            <h1 class="text-3xl font-bold text-slate-900"><?= htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']) ?></h1>
                            <p class="text-slate-500">ID: <?= htmlspecialchars($patient_id) ?> &bull; <?= $age ?> Years Old &bull; <?= ucfirst(htmlspecialchars($patient['gender'])) ?></p>
                        </div>
                        
                        <div class="md:ml-auto flex gap-3">
                            <a href="doctor_update_profile.php?patient_id=<?= $patient_id ?>" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-medium shadow-md shadow-blue-200 transition flex items-center gap-2">
                                <i data-lucide="edit-2" width="18"></i> Edit Profile
                            </a>
                            
                            <a href="doctor_records.php?patient_id=<?= $patient_id ?>" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-xl hover:bg-slate-50 font-medium shadow-sm transition flex items-center gap-2">
                                <i data-lucide="file-clock" width="18"></i> History
                            </a>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="space-y-6">
                        
                        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg">
                            <div class="flex items-center gap-2 mb-4">
                                <i data-lucide="activity" width="20"></i>
                                <h3 class="font-bold">Vital Stats</h3>
                            </div>
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div class="p-3 bg-white/10 rounded-xl backdrop-blur-sm">
                                    <p class="text-[10px] text-blue-100 uppercase font-bold mb-1">Blood</p>
                                    <p class="text-xl font-bold"><?= val('blood_type', $patient) ?></p>
                                </div>
                                <div class="p-3 bg-white/10 rounded-xl backdrop-blur-sm">
                                    <p class="text-[10px] text-blue-100 uppercase font-bold mb-1">Height</p>
                                    <p class="text-xl font-bold"><?= val('height', $patient) ?> <span class="text-xs font-normal">cm</span></p>
                                </div>
                                <div class="p-3 bg-white/10 rounded-xl backdrop-blur-sm">
                                    <p class="text-[10px] text-blue-100 uppercase font-bold mb-1">Weight</p>
                                    <p class="text-xl font-bold"><?= val('weight', $patient) ?> <span class="text-xs font-normal">kg</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                            <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                                <i data-lucide="contact" class="text-slate-400" width="18"></i> Contact Details
                            </h3>
                            <div class="space-y-3 text-sm">
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase">Phone</p>
                                    <p class="font-medium text-slate-700"><?= val('contact', $patient) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase">Email</p>
                                    <p class="font-medium text-slate-700"><?= val('email', $patient) ?></p>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-400 uppercase">Address</p>
                                    <p class="font-medium text-slate-700"><?= val('address', $patient) ?></p>
                                </div>
                            </div>
                            
                            <div class="mt-6 pt-6 border-t border-slate-100">
                                <h4 class="text-xs font-bold text-red-500 uppercase mb-2 flex items-center gap-1"><i data-lucide="phone" width="12"></i> Emergency Contact</h4>
                                <p class="font-bold text-slate-800"><?= val('emergency_contact_name', $patient) ?></p>
                                <p class="text-sm text-slate-500"><?= val('emergency_contact_phone', $patient) ?></p>
                            </div>
                        </div>

                    </div>

                    <div class="lg:col-span-2 space-y-6">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-red-50 border border-red-100 p-5 rounded-2xl">
                                <h3 class="text-xs font-bold text-red-600 uppercase tracking-wider mb-2 flex items-center gap-2">
                                    <i data-lucide="alert-triangle" width="14"></i> Allergies
                                </h3>
                                <p class="text-slate-700 text-sm leading-relaxed">
                                    <?= nl2br(val('allergies', $patient)) ?>
                                </p>
                            </div>
                            <div class="bg-orange-50 border border-orange-100 p-5 rounded-2xl">
                                <h3 class="text-xs font-bold text-orange-600 uppercase tracking-wider mb-2 flex items-center gap-2">
                                    <i data-lucide="activity" width="14"></i> Chronic Conditions
                                </h3>
                                <p class="text-slate-700 text-sm leading-relaxed">
                                    <?= nl2br(val('chronic_conditions', $patient)) ?>
                                </p>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                            <h3 class="font-bold text-slate-800 mb-6 flex items-center gap-2">
                                <i data-lucide="clipboard-list" class="text-purple-600" width="20"></i> Medical Background
                            </h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-700 mb-1">Current Medications</h4>
                                    <div class="p-3 bg-slate-50 rounded-lg text-sm text-slate-600 border border-slate-100">
                                        <?= nl2br(val('current_medications', $patient)) ?>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-700 mb-1">Past Surgeries</h4>
                                        <div class="p-3 bg-slate-50 rounded-lg text-sm text-slate-600 border border-slate-100 h-full">
                                            <?= nl2br(val('past_surgeries', $patient)) ?>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-700 mb-1">Family History</h4>
                                        <div class="p-3 bg-slate-50 rounded-lg text-sm text-slate-600 border border-slate-100 h-full">
                                            <?= nl2br(val('family_history', $patient)) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-slate-800">Recent Visits</h3>
                            </div>
                            <?php if (empty($history)): ?>
                                <p class="text-slate-400 text-sm italic">No recent visits recorded.</p>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($history as $h): 
                                        $statusColor = ($h['status'] == 3 || $h['status'] == 1) ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600';
                                    ?>
                                    <div class="bg-white p-4 rounded-xl border border-slate-200 flex items-start justify-between gap-4 hover:shadow-sm transition">
                                        <div>
                                            <p class="font-bold text-slate-800 text-sm mb-1">
                                                <?= date('F d, Y', strtotime($h['booking_date'])) ?>
                                            </p>
                                            <p class="text-xs text-slate-500 line-clamp-1">
                                                <?= htmlspecialchars($h['diagnosis'] ?: 'Routine Checkup') ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        // Mobile Menu
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        if (mobileBtn && sidebar) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
                sidebar.classList.toggle('z-50');
                sidebar.classList.toggle('w-full');
            });
        }
    </script>
</body>
</html>