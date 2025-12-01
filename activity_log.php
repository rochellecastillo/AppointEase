<?php
include __DIR__ . '/controllers/activity_log_data.php';
?>
<!-- (HTML omitted here for brevity) -->

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Activity Log - Admin</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
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

            <?php if ($dbError && $debug): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
                    <strong>Database error:</strong> <?= htmlspecialchars($dbError) ?>
                    <div class="text-xs text-gray-500 mt-2">
                        Try these queries in your DB client:
                        <div class="mt-1"><code>SELECT COUNT(*) FROM tblactivity_log;</code></div>
                        <div class="mt-1"><code>SELECT * FROM tblactivity_log ORDER BY <?= htmlspecialchars($orderBy) ?> DESC LIMIT 10;</code></div>
                    </div>
                </div>
            <?php endif; ?>

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
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="p-8 text-center text-gray-500">
                                    <?php if ($dbError && !$debug): ?>
                                        Unable to load logs (server error).
                                    <?php else: ?>
                                        No activity logs found.
                                    <?php endif; ?>

                                    <?php if ($debug): ?>
                                        <div class="mt-4 text-xs text-gray-400">
                                            - You can insert a test log below to verify logging.<br>
                                            - Recent rows (debug sample): <?= count($quick) ?> row(s).
                                        </div>

                                        <form method="post" class="mt-3 inline-block">
                                            <input type="hidden" name="__test_insert_log" value="1">
                                            <button name="test_insert" class="mt-2 px-3 py-2 bg-purple-600 text-white rounded">Insert Test Log</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <?php if (!empty($quick) && $debug): ?>
                                <?php foreach ($quick as $srow): 
                                    $ts = isset($srow['created_at']) ? htmlspecialchars($srow['created_at']) : 'N/A';
                                    $uid = htmlspecialchars($srow['user_id'] ?? 'SYSTEM');
                                    $atype = htmlspecialchars($srow['action_type'] ?? '');
                                    $details = htmlspecialchars(substr($srow['details'] ?? '', 0, 200));
                                    $utype = htmlspecialchars(strtoupper($srow['user_type'] ?? ''));
                                ?>
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-3 text-xs text-gray-500"><?= $ts ?></td>
                                    <td class="px-6 py-3"><?= $uid ?></td>
                                    <td class="px-6 py-3"><?= $utype ?></td>
                                    <td class="px-6 py-3"><?= $atype ?></td>
                                    <td class="px-6 py-3"><?= $details ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        <?php else: ?>

                            <?php foreach($logs as $log):
                                $role = $log['user_type'] ?? '';
                                $role = $role === '' ? 'Unknown' : $role;
                                $roleClass = match(strtolower($role)) {
                                    'admin' => 'bg-purple-100 text-purple-700',
                                    'doctor' => 'bg-green-100 text-green-700',
                                    'user' => 'bg-blue-100 text-blue-700',
                                    default => 'bg-gray-100 text-gray-600'
                                };
                                // show timestamp if exists, else N/A
                                $ts = isset($log['created_at']) && $log['created_at'] !== null ? date('Y-m-d H:i:s', strtotime($log['created_at'])) : 'N/A';
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-gray-500 font-mono text-xs"><?= htmlspecialchars($ts) ?></td>
                                <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($log['user_id'] ?? 'System') ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs font-bold uppercase <?= $roleClass ?>"><?= htmlspecialchars(strtoupper($role)) ?></span>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-700"><?= htmlspecialchars($log['action_type'] ?? '') ?></td>
                                <td class="px-6 py-4 text-gray-600 max-w-xs truncate" title="<?= htmlspecialchars($log['details'] ?? '') ?>"><?= htmlspecialchars($log['details'] ?? '') ?></td>
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

            <?php
            // Handle test insertion (debug only)
            if ($debug && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__test_insert_log'])) {
                try {
                    $ok = log_activity('TEST_INSERT', 'This is a debug test log (inserted from activity_log.php)', $_SESSION['user_id'] ?? 'SYSTEM', $_SESSION['user_type'] ?? 'SYSTEM');
                    if ($ok) {
                        echo '<div class="mt-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded">Test log inserted successfully. <a href="" class="underline">Reload</a></div>';
                    } else {
                        echo '<div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded">Failed to insert test log. Check server error log.</div>';
                    }
                } catch (Exception $ex) {
                    echo '<div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded">Exception: ' . htmlspecialchars($ex->getMessage()) . '</div>';
                }
            }
            ?>

        </div>
    </main>
</div>

<script>
if (typeof lucide !== 'undefined' && lucide.createIcons) lucide.createIcons();
</script>
</body>
</html>