<?php
// manage_specializations.php - Manage doctor specializations
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_type']) !== 'admin') {
    header('Location: login.php');
    exit;
}

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$error = '';
$success = '';

// Handle specialization addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_specialization'])) {
    $specialization = trim($_POST['specialization'] ?? '');
    
    if (empty($specialization)) {
        $error = "Please enter a specialization name";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tblspecialization WHERE specialization = ?");
            $stmt->execute([$specialization]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "This specialization already exists";
            } else {
                $stmt = $pdo->prepare("INSERT INTO tblspecialization (specialization) VALUES (?)");
                $stmt->execute([$specialization]);
                $success = "Specialization added successfully!";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle specialization deletion
if (isset($_GET['delete'])) {
    $spec_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tblspecialization WHERE id = ?");
        $stmt->execute([$spec_id]);
        $success = "Specialization deleted successfully!";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all specializations
try {
    $stmt = $pdo->query("SELECT * FROM tblspecialization ORDER BY specialization");
    $specializations = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Count doctors per specialization
try {
    $stmt = $pdo->query("SELECT specialization, COUNT(*) as count 
                         FROM tblinfo 
                         WHERE specialization != '' 
                         GROUP BY specialization");
    $spec_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $spec_counts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Specializations - AppointmentEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-8">
        <div class="max-w-5xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Manage Specializations</h1>
                    <p class="text-gray-600">Add and manage doctor specializations</p>
                </div>
                <a href="admin_home.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    ← Back to Dashboard
                </a>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= e($success) ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Add Specialization Form -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Add New Specialization</h2>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Specialization Name</label>
                            <input type="text" name="specialization" required 
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="e.g., Cardiology">
                        </div>

                        <button type="submit" name="add_specialization" 
                                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg font-semibold">
                            Add Specialization
                        </button>
                    </form>

                    <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                        <h3 class="font-semibold text-blue-900 mb-2">Common Specializations:</h3>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>• Internal Medicine</li>
                            <li>• Pediatrics</li>
                            <li>• Cardiology</li>
                            <li>• Dermatology</li>
                            <li>• Orthopedics</li>
                            <li>• Neurology</li>
                        </ul>
                    </div>
                </div>

                <!-- Current Specializations List -->
                <div class="lg:col-span-2 bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-bold text-gray-800">Current Specializations</h2>
                        <p class="text-sm text-gray-600 mt-1">Total: <?= count($specializations) ?></p>
                    </div>
                    <div class="p-6">
                        <?php if (empty($specializations)): ?>
                            <p class="text-center text-gray-500 py-8">No specializations found</p>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($specializations as $spec): 
                                    $doctor_count = $spec_counts[$spec['specialization']] ?? 0;
                                ?>
                                <div class="border rounded-lg p-4 hover:border-blue-300 transition">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-semibold text-gray-800"><?= e($spec['specialization']) ?></h3>
                                        <a href="?delete=<?= e($spec['id']) ?>" 
                                           onclick="return confirm('Are you sure you want to delete this specialization?')"
                                           class="text-red-600 hover:text-red-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </a>
                                    </div>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <?= $doctor_count ?> doctor<?= $doctor_count != 1 ? 's' : '' ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>