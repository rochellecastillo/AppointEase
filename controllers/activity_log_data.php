<?php
// activity_log.php - Safe activity log viewer (uses created_at if present)
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['admin']);

$debug = false; // set true only while troubleshooting

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$logs = [];
$total_rows = 0;
$total_pages = 1;
$dbError = null;

try {
    // ensure table exists
    $check = $pdo->query("SHOW TABLES LIKE 'tblactivity_log'")->fetchColumn();
    if (!$check) throw new Exception("Table tblactivity_log not found.");

    // count rows
    $stmt = $pdo->query("SELECT COUNT(*) FROM tblactivity_log");
    $total_rows = (int)$stmt->fetchColumn();
    $total_pages = max(1, (int)ceil($total_rows / $limit));
    if ($page > $total_pages) { $page = $total_pages; $offset = ($page - 1) * $limit; }

    // detect created_at column
    $colStmt = $pdo->prepare("SHOW COLUMNS FROM tblactivity_log LIKE 'created_at'");
    $colStmt->execute();
    $hasCreatedAt = (bool)$colStmt->fetch(PDO::FETCH_ASSOC);

    // choose order column and proper SQL with alias
    $orderColumn = $hasCreatedAt ? 'l.created_at' : 'l.id';

    $sql = "SELECT l.*, COALESCE(NULLIF(l.user_type,''), u.user_type) AS user_type
            FROM tblactivity_log AS l
            LEFT JOIN tbluser AS u ON l.user_id = u.user_id
            ORDER BY {$orderColumn} DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $dbError = $e->getMessage();
}
?>