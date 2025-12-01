<?php
include __DIR__ . '/controllers/doctors_info_data.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Doctors List - AppointmentEase</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    * { font-family: 'Inter', sans-serif; }
  </style>
</head>
<body class="bg-gray-50 text-gray-800">
  <div class="flex h-screen overflow-hidden">
    
    <?php include 'includes/admin_sidebar.php'; ?>

    <main class="flex-1 overflow-auto">
      <div class="p-8">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Medical Staff</h1>
                <p class="text-gray-500 mt-1">Manage doctor profiles and availability.</p>
            </div>
            <a href="add_doctor.php" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-5 py-2.5 rounded-xl shadow-lg shadow-blue-200 transition flex items-center gap-2 font-medium">
                <i data-lucide="plus" width="20"></i> Add New Doctor
            </a>
        </div>

        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-xl flex items-center gap-2">
                <i data-lucide="check-circle" width="20"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-700 rounded-xl flex items-center gap-2">
                <i data-lucide="alert-circle" width="20"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-12 gap-4">
                
                <div class="md:col-span-5 relative">
                    <i data-lucide="search" class="absolute left-3 top-3 text-gray-400" width="18"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition"
                           placeholder="Search by name or ID...">
                </div>

                <div class="md:col-span-4">
                    <select name="specialization" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-blue-500 transition appearance-none cursor-pointer">
                        <option value="">All Specializations</option>
                        <?php foreach ($specializations as $spec): ?>
                            <option value="<?= htmlspecialchars($spec) ?>" <?= $specialization_filter === $spec ? 'selected' : '' ?>>
                                <?= htmlspecialchars($spec) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="flex-1 bg-gray-800 hover:bg-gray-900 text-white rounded-xl transition font-medium">
                        Filter
                    </button>
                    <?php if($search || $specialization_filter): ?>
                    <a href="doctors_info_report.php" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl flex items-center justify-center border border-gray-200" title="Clear Filters">
                        <i data-lucide="x" width="18"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Doctor Profile</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Specialization</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Status</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($doctors)): ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <i data-lucide="user-x" class="text-gray-300" width="32"></i>
                                        <p>No doctors found matching your criteria.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: foreach ($doctors as $doc): 
                            $fullName = trim(($doc['last_name'] ?? '') . ', ' . ($doc['first_name'] ?? ''));
                            $initial = strtoupper(substr($doc['first_name'] ?? 'D', 0, 1));
                            $spec = $doc['specialization'] ?: 'General Practitioner';
                        ?>
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="p-5">
                                <div class="flex items-center gap-4">
                                    <?php if (!empty($doc['image'])): ?>
                                        <img src="uploads/<?= htmlspecialchars($doc['image']) ?>" class="w-12 h-12 rounded-xl object-cover shadow-sm">
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 text-blue-600 flex items-center justify-center font-bold text-lg shadow-sm">
                                            <?= $initial ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($fullName) ?></p>
                                        <p class="text-xs text-gray-400">ID: <?= htmlspecialchars($doc['user_id']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    <?= htmlspecialchars($spec) ?>
                                </span>
                            </td>
                            <td class="p-5">
                                <div class="flex flex-col gap-1">
                                    <?php if($doc['contact']): ?>
                                    <div class="flex items-center gap-2 text-xs text-gray-600">
                                        <i data-lucide="phone" width="12"></i> <?= htmlspecialchars($doc['contact']) ?>
                                    </div>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">No contact info</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-5 text-center">
                                <?php if ($doc['account_status'] == 1): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-5">
                                <div class="flex justify-center gap-2">
                                    <a href="edit_doctor.php?id=<?= $doc['user_id'] ?>" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-blue-600 hover:border-blue-200 transition shadow-sm" title="Edit Profile">
                                        <i data-lucide="pencil" width="16"></i>
                                    </a>
                                    
                                    <a href="schedule_manage.php?doctor_id=<?= $doc['user_id'] ?>" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-purple-600 hover:border-purple-200 transition shadow-sm" title="Manage Schedule">
                                        <i data-lucide="calendar" width="16"></i>
                                    </a>

                                    <form method="POST" class="inline" onsubmit="return confirm('Change status for this doctor?');">
                                        <input type="hidden" name="user_id" value="<?= $doc['user_id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $doc['account_status'] ?>">
                                        <button type="submit" name="toggle_status" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-orange-600 hover:border-orange-200 transition shadow-sm" title="Toggle Active Status">
                                            <i data-lucide="power" width="16"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

      </div>
    </main>
  </div>

  <script>
    if (typeof lucide !== 'undefined') lucide.createIcons();
  </script>
</body>
</html>