<?php
// contact_support.php - Contact hospital support
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || strtolower($_SESSION['user_type']) !== 'user') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Fetch patient info
$stmt = $pdo->prepare("SELECT * FROM tblinfo WHERE user_id = ?");
$stmt->execute([$user_id]);
$info = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid security token.";
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $contact_method = trim($_POST['contact_method'] ?? '');
        
        if (empty($subject) || empty($category) || empty($description) || empty($contact_method)) {
            $error = "Please fill in all required fields.";
        } else {
            // In a real application, this would save to a support_tickets table or send email
            $message = "Your support request has been submitted successfully! Our team will contact you soon via {$contact_method}.";
            
            // Log the support request (you can create a support_tickets table)
            try {
                // Example: INSERT INTO support_tickets (user_id, subject, category, description, contact_method, status, created_at)
                // VALUES (?, ?, ?, ?, ?, 'open', NOW())
                $message .= " Reference ID: SUP" . rand(10000, 99999);
            } catch (Exception $ex) {
                // Handle error
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - AppointmentEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-8">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Contact Support</h1>
                    <p class="text-gray-600 mt-1">Get help with your appointments and account</p>
                </div>
                <a href="client_home.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-6 rounded-lg transition">
                    <i data-lucide="arrow-left" class="inline" width="18" height="18"></i>
                    Back
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Contact Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b">
                            <h2 class="text-xl font-bold text-gray-800">Submit a Support Request</h2>
                        </div>
                        <div class="p-6">
                            <?php if ($message): ?>
                                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                                    <i data-lucide="check-circle" class="inline" width="20" height="20"></i>
                                    <?= e($message) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($error): ?>
                                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                                    <i data-lucide="alert-circle" class="inline" width="20" height="20"></i>
                                    <?= e($error) ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Category *</label>
                                    <select name="category" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <option value="">Select a category...</option>
                                        <option value="appointment">Appointment Issues</option>
                                        <option value="booking">Booking Problems</option>
                                        <option value="account">Account & Login</option>
                                        <option value="billing">Billing Questions</option>
                                        <option value="medical">Medical Records</option>
                                        <option value="technical">Technical Issues</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Subject *</label>
                                    <input type="text" name="subject" required maxlength="100"
                                           placeholder="Brief description of your issue"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description *</label>
                                    <textarea name="description" required rows="6"
                                              placeholder="Please provide detailed information about your issue..."
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Preferred Contact Method *</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="contact_method" value="phone" required class="mr-3">
                                            <div>
                                                <p class="font-semibold text-gray-800">Phone</p>
                                                <p class="text-sm text-gray-600"><?= e($info['contact'] ?? 'Not provided') ?></p>
                                            </div>
                                        </label>
                                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="contact_method" value="email" class="mr-3">
                                            <div>
                                                <p class="font-semibold text-gray-800">Email</p>
                                                <p class="text-sm text-gray-600">patient@example.com</p>
                                            </div>
                                        </label>
                                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                            <input type="radio" name="contact_method" value="portal" class="mr-3">
                                            <div>
                                                <p class="font-semibold text-gray-800">Patient Portal</p>
                                                <p class="text-sm text-gray-600">Message through dashboard</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-3 px-6 rounded-lg font-semibold transition">
                                        <i data-lucide="send" class="inline" width="18" height="18"></i>
                                        Submit Request
                                    </button>
                                    <button type="reset" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-3 px-6 rounded-lg font-semibold transition">
                                        Clear
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Contact Information -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b">
                            <h3 class="text-lg font-bold text-gray-800">Contact Information</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="bg-blue-100 p-2 rounded-full">
                                    <i data-lucide="phone" class="text-blue-600" width="20" height="20"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">Phone</p>
                                    <p class="text-sm text-gray-600">(02) 1234-5678</p>
                                    <p class="text-xs text-gray-500">Mon-Fri 8AM-5PM</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-green-100 p-2 rounded-full">
                                    <i data-lucide="mail" class="text-green-600" width="20" height="20"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">Email</p>
                                    <p class="text-sm text-gray-600">support@untalan.com</p>
                                    <p class="text-xs text-gray-500">Response within 24hrs</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-red-100 p-2 rounded-full">
                                    <i data-lucide="alert-circle" class="text-red-600" width="20" height="20"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">Emergency</p>
                                    <p class="text-sm text-gray-600">(02) 911-9999</p>
                                    <p class="text-xs text-gray-500">24/7 Emergency Line</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-purple-100 p-2 rounded-full">
                                    <i data-lucide="map-pin" class="text-purple-600" width="20" height="20"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">Location</p>
                                    <p class="text-sm text-gray-600">Untalan General Hospital</p>
                                    <p class="text-xs text-gray-500">Quezon City, Metro Manila</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Help -->
                    <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg shadow p-6 text-white">
                        <h3 class="font-bold mb-3">Need Quick Help?</h3>
                        <p class="text-sm text-blue-100 mb-4">Check our frequently asked questions</p>
                        <button class="w-full bg-white text-blue-600 hover:bg-blue-50 py-2 px-4 rounded-lg font-semibold transition">
                            View FAQ
                        </button>
                    </div>

                    <!-- Operating Hours -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b">
                            <h3 class="text-lg font-bold text-gray-800">Operating Hours</h3>
                        </div>
                        <div class="p-6 space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Monday - Friday</span>
                                <span class="font-semibold text-gray-800">8:00 AM - 5:00 PM</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Saturday</span>
                                <span class="font-semibold text-gray-800">9:00 AM - 2:00 PM</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Sunday</span>
                                <span class="font-semibold text-gray-800">Closed</span>
                            </div>
                            <div class="flex justify-between pt-3 border-t">
                                <span class="text-gray-600">Emergency Services</span>
                                <span class="font-semibold text-green-700">24/7</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.replace();
    </script>
</body>
</html>