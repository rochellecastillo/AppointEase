<?php
// activity_log.php - System Audit Log
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php'; // harmless to include; required where logging happens

// Enforce Admin Access
session_require_auth(['admin']);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- ATTEMPT TO FETCH LOGS ---
try {
    // 1. Get Total Count
    $stmt = $pdo->query("SELECT COUNT(*) FROM tblactivity_log");
    $total_rows = (int) $stmt->fetchColumn();
    $total_pages = (int) ceil($total_rows / $limit);
    if ($total_pages < 1) $total_pages = 1;

    // If requested page is beyond total pages, clamp it and recalc offset
    if ($page > $total_pages) {
        $page = $total_pages;
        $offset = ($page - 1) * $limit;
    }

    // 2. Try fetching logs with JOIN (Preferred)
    try {
        // Prefer the user_type stored in the log; if empty, fall back to tbluser.user_type
        $sql = "SELECT 
                    l.*, 
                    COALESCE(NULLIF(l.user_type, ''), u.user_type) AS user_type
                FROM tblactivity_log l
                LEFT JOIN tbluser u ON l.user_id = u.user_id
                ORDER BY l.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // 3. Fallback: If JOIN fails due to collation, fetch without JOIN
        // This prevents the page from crashing with "Illegal mix of collations"
        $sql = "SELECT l.*, '' as user_type 
                FROM tblactivity_log l
                ORDER BY l.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Optional: Attempt to fix collation silently for next time
        // $pdo->exec("ALTER TABLE tblactivity_log CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <?php include 'includes/admin_sidebar.php'; ?>

        <main class="flex-1 overflow-auto">
            <div class="p-8 max-w-6xl mx-auto">
                
                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">System Activity Log</h1>
                        <p class="text-gray-500">Audit trail of user actions and system events.</p>
                    </div>
                    <div class="text-sm text-gray-400">
                        Total Records: <?= htmlspecialchars($total_rows) ?>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold border-b border-gray-100">
                                <tr>
                                    <th class="px-6 py-4">Timestamp</th>
                                    <th class="px-6 py-4">User ID</th>
                                    <th class="px-6 py-4">Role</th>
                                    <th class="px-6 py-4">Action</th>
                                    <th class="px-6 py-4">Details</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <?php if(empty($logs)): ?>
                                    <tr><td colspan="5" class="p-8 text-center text-gray-500">No activity logs found.</td></tr>
                                <?php else: ?>
                                    <?php foreach($logs as $log):
                                        // Prefer log's user_type (already handled by SQL), but ensure non-empty display string
                                        $role = $log['user_type'] ?? '';
                                        $role = $role === '' ? 'Unknown' : $role;

                                        $roleClass = match(strtolower($role)) {
                                            'admin' => 'bg-purple-100 text-purple-700',
                                            'doctor' => 'bg-green-100 text-green-700',
                                            'user' => 'bg-blue-100 text-blue-700',
                                            default => 'bg-gray-100 text-gray-600'
                                        };

                                        // safe timestamp display
                                        $ts = isset($log['created_at']) && $log['created_at'] !== null
                                              ? date('Y-m-d H:i:s', strtotime($log['created_at']))
                                              : 'N/A';
                                    ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 text-gray-500 font-mono text-xs">
                                            <?= htmlspecialchars($ts) ?>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            <?= htmlspecialchars($log['user_id'] ?? 'System') ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $roleClass ?>">
                                                <?= htmlspecialchars(strtoupper($role)) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-gray-700">
                                            <?= htmlspecialchars($log['action_type'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 max-w-xs truncate" title="<?= htmlspecialchars($log['details'] ?? '') ?>">
                                            <?= htmlspecialchars($log['details'] ?? '') ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center">
                        <span class="text-sm text-gray-500">Page <?= $page ?> of <?= $total_pages ?></span>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm">Previous</a>
                            <?php endif; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 bg-white border border-gray-300 rounded hover:bg-gray-50 text-sm">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <script>
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            lucide.createIcons();
        }
    </script>
</body>
</html>