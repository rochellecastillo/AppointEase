<?php
// manage_schedule.php
require 'helpers.php';
require_admin();
$csrf = ensure_csrf();

// fetch doctors
$stmt = $pdo->prepare("SELECT i.user_id, i.last_name, i.first_name, u.user_name, u.status FROM tblinfo i JOIN tbluser u ON u.user_id = i.user_id WHERE u.user_type = 'doctor' ORDER BY i.last_name");
$stmt->execute();
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Manage Doctors & Schedules</title>
<style>table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px}</style>
</head><body>
  <h1>Doctors</h1>
  <p><a href="add_doctor.php">Add doctor</a> | <a href="admin_home.php">Admin Home</a></p>
  <table>
    <thead><tr><th>User ID</th><th>Username</th><th>Name</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php if(empty($doctors)): ?><tr><td colspan="5">No doctors</td></tr><?php else:
        foreach($doctors as $d): ?>
          <tr>
            <td><?= e($d['user_id']) ?></td>
            <td><?= e($d['user_name']) ?></td>
            <td><?= e($d['last_name'] . ', ' . $d['first_name']) ?></td>
            <td><?= ((int)$d['status']===1)?'Active':'Blocked' ?></td>
            <td>
              <a href="doctor_schedule_manage.php?user_id=<?= urlencode($d['user_id']) ?>">Manage Schedule</a> |
              <a href="edit_doctor.php?user_id=<?= urlencode($d['user_id']) ?>">Edit</a> |
              <form method="post" action="delete_doctor.php" style="display:inline" onsubmit="return confirm('Delete doctor? This will mark deleted and remove schedules.')">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="user_id" value="<?= e($d['user_id']) ?>">
                <button type="submit">Delete</button>
              </form>
            </td>
          </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</body></html>
