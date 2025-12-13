<?php
include __DIR__ . '/controllers/users_list_data.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Patient Registry - AppointmentEase</title>
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
                <h1 class="text-3xl font-bold text-gray-800">Patient Registry</h1>
                <p class="text-gray-500 mt-1">Manage registered patients and medical records.</p>
            </div>
            <a href="add_patient.php" class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white px-5 py-2.5 rounded-xl shadow-lg shadow-green-200 transition flex items-center gap-2 font-medium">
                <i data-lucide="user-plus" width="20"></i> Register Patient
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
            <form method="GET" class="flex gap-4">
                <div class="flex-1 relative">
                    <i data-lucide="search" class="absolute left-3 top-3 text-gray-400" width="18"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:border-green-500 transition"
                           placeholder="Search by name or Patient ID...">
                </div>
                <button type="submit" class="px-6 py-2.5 bg-gray-800 hover:bg-gray-900 text-white rounded-xl transition font-medium">Search</button>
                <?php if($search): ?>
                <a href="users_list.php" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl border border-gray-200 flex items-center"><i data-lucide="x" width="18"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Patient Info</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Personal Details</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider">Address</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Status</th>
                            <th class="p-5 text-xs font-bold text-gray-500 uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($patients)): ?>
                            <tr><td colspan="5" class="p-8 text-center text-gray-500">No patients found.</td></tr>
                        <?php else: foreach ($patients as $user): 
                            $fullName = trim(($user['last_name'] ?? '') . ', ' . ($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? ''));
                            $initial = strtoupper(substr($user['first_name'] ?? 'P', 0, 1));
                            $age = getAge($user['bdate']);
                        ?>
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="p-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-100 to-teal-100 text-green-600 flex items-center justify-center font-bold text-sm shadow-sm"><?= $initial ?></div>
                                    <div>
                                        <p class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($fullName) ?></p>
                                        <p class="text-xs text-gray-400">ID: <?= htmlspecialchars($user['user_id']) ?></p>
                                        <div class="flex items-center gap-1 text-xs text-gray-500 mt-0.5">
                                            <i data-lucide="phone" width="10"></i> <?= htmlspecialchars($user['contact'] ?? '-') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-5">
                                <div class="flex flex-col gap-1">
                                    <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars(ucfirst($user['gender'] ?? '-')) ?></span>
                                    <span class="text-xs text-gray-500">Age: <?= $age ?> years</span>
                                    <span class="text-xs text-gray-400">Born: <?= htmlspecialchars($user['bdate'] ?? 'N/A') ?></span>
                                </div>
                            </td>
                            <td class="p-5 max-w-xs">
                                <p class="text-sm text-gray-600 truncate" title="<?= htmlspecialchars($user['address']) ?>"><?= htmlspecialchars($user['address'] ?? 'No address provided') ?></p>
                            </td>
                            <td class="p-5 text-center">
                                <?php if ($user['account_status'] == 1): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">Active</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-5">
                                <div class="flex justify-center gap-2">
                                    <a href="edit_patient.php?id=<?= $user['user_id'] ?>" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-blue-600 hover:border-blue-200 transition" title="Edit"><i data-lucide="pencil" width="16"></i></a>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('Change status?');">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= $user['account_status'] ?>">
                                        <button type="submit" name="toggle_status" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-orange-600 hover:border-orange-200 transition" title="Toggle Status"><i data-lucide="power" width="16"></i></button>
                                    </form>

                                    <form method="POST" class="inline" onsubmit="return confirm('WARNING: Are you sure you want to delete this patient? This action cannot be undone.');">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <button type="submit" name="delete_user" class="p-2 bg-white border border-gray-200 rounded-lg text-gray-500 hover:text-red-600 hover:border-red-200 transition" title="Delete Account">
                                            <i data-lucide="trash-2" width="16"></i>
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
  <script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>
</body>
</html>