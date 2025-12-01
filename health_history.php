<?php
include __DIR__ . '/controllers/health_history_data.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health History - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        
        /* PRINT STYLING */
        @media print {
            aside, #mobileMenuBtn, .no-print { display: none !important; }
            main { margin: 0 !important; padding: 0 !important; height: auto !important; overflow: visible !important; }
            body { background-color: white !important; color: black !important; }
            .print-header { display: block !important; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .card-bg { background: white !important; border: 1px solid #ddd !important; box-shadow: none !important; color: black !important; }
            .text-white { color: black !important; }
            .bg-white\/10 { background: #f3f4f6 !important; }
        }
        .print-header { display: none; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/client_sidebar.php'; ?>

        <main class="flex-1 overflow-auto relative">
            <div class="md:hidden p-4 flex items-center justify-between bg-white border-b sticky top-0 z-20 no-print">
                <span class="font-bold text-lg text-purple-700">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-gray-100 rounded-lg"><i data-lucide="menu"></i></button>
            </div>

            <div class="p-6 max-w-5xl mx-auto">
                
                <div class="print-header">
                    <h1 class="text-2xl font-bold">Medical Health Report</h1>
                    <p>Patient: <?= e($userInfo['first_name'] . ' ' . $userInfo['last_name']) ?></p>
                    <p>Date: <?= date('F d, Y') ?></p>
                </div>

                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8 no-print">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Health History</h1>
                        <p class="text-gray-500">View your personal health profile and emergency contacts.</p>
                    </div>
                    <button onclick="window.print()" class="flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition shadow-sm">
                        <i data-lucide="printer" class="w-4 h-4"></i> Print / Download PDF
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg card-bg">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-white/20 rounded-lg"><i data-lucide="activity" class="w-5 h-5"></i></div>
                            <h3 class="font-bold text-lg">Vital Stats</h3>
                        </div>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div class="p-3 bg-white/10 rounded-xl backdrop-blur-sm">
                                <p class="text-xs text-blue-100 uppercase mb-1">Blood Type</p>
                                <p class="text-2xl font-bold"><?= val('blood_type', $profile) ?: '-' ?></p>
                            </div>
                            <div class="p-3 bg-white/10 rounded-xl backdrop-blur-sm">
                                <p class="text-xs text-blue-100 uppercase mb-1">Height</p>
                                <p class="text-xl font-bold"><?= val('height', $profile) ?: '-' ?> <span class="text-xs font-normal">cm</span></p>
                            </div>
                            <div class="p-3 bg-white/10 rounded-xl backdrop-blur-sm">
                                <p class="text-xs text-blue-100 uppercase mb-1">Weight</p>
                                <p class="text-xl font-bold"><?= val('weight', $profile) ?: '-' ?> <span class="text-xs font-normal">kg</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 lg:col-span-2 card-bg">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-red-100 text-red-600 rounded-lg"><i data-lucide="phone-call" class="w-5 h-5"></i></div>
                            <h3 class="font-bold text-gray-800">Emergency Contact</h3>
                        </div>
                        <?php if(val('emergency_contact_name', $profile)): ?>
                            <div class="flex items-center justify-between bg-red-50 p-4 rounded-xl border border-red-100 card-bg">
                                <div>
                                    <p class="text-sm text-gray-500">Name</p>
                                    <p class="font-bold text-gray-800 text-lg"><?= val('emergency_contact_name', $profile) ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Phone</p>
                                    <a href="tel:<?= val('emergency_contact_phone', $profile) ?>" class="font-bold text-red-600 text-lg hover:underline">
                                        <?= val('emergency_contact_phone', $profile) ?>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-400 italic text-sm">No emergency contact set.</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 lg:col-span-3 card-bg">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i data-lucide="alert-circle" class="text-orange-500 w-5 h-5"></i> Medical Alerts
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-4 bg-orange-50 rounded-xl border border-orange-100 card-bg">
                                <span class="text-xs font-bold text-orange-600 uppercase tracking-wide block mb-2">Allergies</span>
                                <p class="text-gray-700 leading-relaxed">
                                    <?= nl2br(val('allergies', $profile)) ?: '<span class="text-gray-400 italic">None reported</span>' ?>
                                </p>
                            </div>
                            <div class="p-4 bg-purple-50 rounded-xl border border-purple-100 card-bg">
                                <span class="text-xs font-bold text-purple-600 uppercase tracking-wide block mb-2">Chronic Conditions</span>
                                <p class="text-gray-700 leading-relaxed">
                                    <?= nl2br(val('chronic_conditions', $profile)) ?: '<span class="text-gray-400 italic">None reported</span>' ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 lg:col-span-3 card-bg">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                    <i data-lucide="pill" class="text-blue-500 w-5 h-5"></i> Current Medications
                                </h3>
                                <div class="prose prose-sm text-gray-600 p-3 bg-gray-50 rounded-lg border border-gray-100 card-bg">
                                    <?= nl2br(val('current_medications', $profile)) ?: '<p class="italic text-gray-400">No medications listed.</p>' ?>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                                    <i data-lucide="scissors" class="text-gray-500 w-5 h-5"></i> Past Surgeries
                                </h3>
                                <div class="prose prose-sm text-gray-600 p-3 bg-gray-50 rounded-lg border border-gray-100 card-bg">
                                    <?= nl2br(val('past_surgeries', $profile)) ?: '<p class="italic text-gray-400">No surgeries listed.</p>' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 lg:col-span-3 card-bg">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i data-lucide="users" class="text-green-500 w-5 h-5"></i> Family Medical History
                        </h3>
                        <p class="text-gray-600 leading-relaxed">
                            <?= nl2br(val('family_history', $profile)) ?: '<span class="text-gray-400 italic">No family history recorded.</span>' ?>
                        </p>
                    </div>

                </div>

                <div class="mt-8 text-center text-xs text-gray-400 no-print">
                    <p>
                        Last updated: <?= !empty($profile['updated_at']) ? date('F d, Y h:i A', strtotime($profile['updated_at'])) : 'Never' ?>
                    </p>
                    <p class="mt-1">If you need to update this information, please contact your doctor during your next visit.</p>
                </div>

            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar');
            if(sidebar) sidebar.classList.toggle('hidden');
        });
    </script>
</body>
</html>