<?php
// admin_health_profiles.php - View Patient Health Records
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['admin']);

// Fetch all health profiles
try {
    $stmt = $pdo->query("
        SELECT h.*, u.first_name, u.last_name, u.email, u.contact, u.avatar
        FROM tbl_health_profile h
        JOIN tblinfo u ON h.user_id = u.user_id
        ORDER BY h.created_at DESC
    ");
    $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Profiles - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="flex-1 overflow-auto p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Patient Health Profiles</h1>
                    <p class="text-gray-500">Medical records and emergency details</p>
                </div>
                <a href="admin_home.php" class="text-gray-500 hover:text-purple-600 flex items-center gap-2">
                    <i data-lucide="arrow-left" width="18"></i> Back to Dashboard
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50 text-gray-500 text-sm uppercase tracking-wider border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 font-medium">Patient</th>
                                <th class="px-6 py-4 font-medium">Blood Type</th>
                                <th class="px-6 py-4 font-medium">Allergies</th>
                                <th class="px-6 py-4 font-medium">Emergency Contact</th>
                                <th class="px-6 py-4 font-medium">Date Updated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php if (empty($profiles)): ?>
                                <tr><td colspan="5" class="p-8 text-center text-gray-500">No health profiles found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($profiles as $p): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center font-bold">
                                                <?= strtoupper(substr($p['first_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></p>
                                                <p class="text-xs text-gray-500"><?= htmlspecialchars($p['email']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-700">
                                        <span class="bg-red-50 text-red-700 px-2 py-1 rounded-lg border border-red-100">
                                            <?= htmlspecialchars($p['blood_type'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs truncate text-gray-600" title="<?= htmlspecialchars($p['allergies']) ?>">
                                        <?= htmlspecialchars($p['allergies'] ?: 'None') ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-gray-800"><?= htmlspecialchars($p['emergency_contact_name']) ?></p>
                                        <p class="text-xs text-gray-500"><?= htmlspecialchars($p['emergency_contact_number']) ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        <?= date('M d, Y', strtotime($p['updated_at'])) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>