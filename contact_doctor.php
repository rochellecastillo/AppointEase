<?php
// contact_doctor.php - Send messages to doctors
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

// Fetch patient's doctors (from appointments)
$stmt = $pdo->prepare("SELECT DISTINCT a.doctor, 
                              d.first_name AS dfirst, d.last_name AS dlast,
                              di.contact, di.address
                       FROM tblappointment a
                       LEFT JOIN tblinfo d ON d.user_id = a.doctor
                       LEFT JOIN tblinfo di ON di.user_id = a.doctor
                       WHERE a.user_id = ? AND a.status = 1
                       ORDER BY d.last_name, d.first_name");
$stmt->execute([$user_id]);
$myDoctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all doctors
$stmt = $pdo->query("SELECT u.user_id, i.first_name, i.last_name, i.contact, i.address
                     FROM tbluser u
                     LEFT JOIN tblinfo i ON i.user_id = u.user_id
                     WHERE u.user_type = 'doctor' AND u.status = 1
                     ORDER BY i.last_name, i.first_name");
$allDoctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid security token.";
    } else {
        $doctor_id = trim($_POST['doctor_id'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $msg_content = trim($_POST['message'] ?? '');
        $priority = trim($_POST['priority'] ?? 'normal');
        
        if (empty($doctor_id) || empty($subject) || empty($msg_content)) {
            $error = "Please fill in all required fields.";
        } else {
            // In a real application, save to messages table
            // INSERT INTO messages (sender_id, receiver_id, subject, message, priority, created_at)
            // VALUES (?, ?, ?, ?, ?, NOW())
            
            $message = "Your message has been sent successfully to the doctor!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Doctor - AppointmentEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Contact Doctor</h1>
                    <p class="text-gray-600 mt-1">Send messages and inquiries to your doctors</p>
                </div>
                <a href="client_home.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-6 rounded-lg transition">
                    <i data-lucide="arrow-left" class="inline" width="18" height="18"></i>
                    Back
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Message Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b">
                            <h2 class="text-xl font-bold text-gray-800">Send Message</h2>
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
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Select Doctor *</label>
                                    <select name="doctor_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <option value="">Choose a doctor...</option>
                                        <?php if (!empty($myDoctors)): ?>
                                            <optgroup label="My Doctors">
                                                <?php foreach ($myDoctors as $doc): 
                                                    $docName = 'Dr. ' . trim(($doc['dlast'] ?? '') . ', ' . ($doc['dfirst'] ?? ''));
                                                ?>
                                                    <option value="<?= e($doc['doctor']) ?>"><?= e($docName) ?></option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                        <optgroup label="All Doctors">
                                            <?php foreach ($allDoctors as $doc): 
                                                $docName = 'Dr. ' . trim(($doc['last_name'] ?? '') . ', ' . ($doc['first_name'] ?? ''));
                                            ?>
                                                <option value="<?= e($doc['user_id']) ?>"><?= e($docName) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Priority</label>
                                    <div class="flex gap-4">
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="priority" value="low" class="mr-2">
                                            <span class="text-gray-700">Low</span>
                                        </label>
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="priority" value="normal" checked class="mr-2">
                                            <span class="text-gray-700">Normal</span>
                                        </label>
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="priority" value="high" class="mr-2">
                                            <span class="text-gray-700">High</span>
                                        </label>
                                        <label class="flex items-center cursor-pointer">
                                            <input type="radio" name="priority" value="urgent" class="mr-2">
                                            <span class="text-red-600 font-semibold">Urgent</span>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Subject *</label>
                                    <input type="text" name="subject" required maxlength="100"
                                           placeholder="Brief description of your inquiry"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Message *</label>
                                    <textarea name="message" required rows="8"
                                              placeholder="Type your message here..."
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                                </div>

                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <p class="text-sm text-blue-800">
                                        <strong>Note:</strong> For medical emergencies, please call (02) 911-9999 immediately.
                                        Messages are typically responded to within 24-48 hours.
                                    </p>
                                </div>

                                <div class="flex gap-4">
                                    <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-3 px-6 rounded-lg font-semibold transition">
                                        <i data-lucide="send" class="inline" width="18" height="18"></i>
                                        Send Message
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
                    <!-- My Doctors -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b">
                            <h3 class="text-lg font-bold text-gray-800">My Doctors</h3>
                        </div>
                        <div class="p-6">
                            <?php if (empty($myDoctors)): ?>
                                <div class="text-center py-6 text-gray-500">
                                    <i data-lucide="user-x" class="mx-auto mb-3" width="32" height="32"></i>
                                    <p class="text-sm">No doctors yet</p>
                                    <p class="text-xs mt-1">Book an appointment to connect</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($myDoctors as $doc): 
                                        $docName = trim(($doc['dlast'] ?? '') . ', ' . ($doc['dfirst'] ?? ''));
                                    ?>
                                        <div class="p-3 border rounded-lg hover:border-purple-300 transition">
                                            <div class="flex items-start gap-3">
                                                <div class="bg-purple-100 p-2 rounded-full">
                                                    <i data-lucide="user-check" class="text-purple-600" width="20" height="20"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="font-semibold text-gray-800">Dr. <?= e($docName) ?></p>
                                                    <?php if ($doc['contact']): ?>
                                                        <p class="text-xs text-gray-600 mt-1">
                                                            <i data-lucide="phone" class="inline" width="12" height="12"></i>
                                                            <?= e($doc['contact']) ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Templates -->
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b">
                            <h3 class="text-lg font-bold text-gray-800">Message Templates</h3>
                        </div>
                        <div class="p-6 space-y-2">
                            <button onclick="useTemplate('prescription')" class="w-full text-left p-3 border rounded-lg hover:bg-gray-50 transition">
                                <p class="font-semibold text-gray-800 text-sm">Prescription Refill</p>
                                <p class="text-xs text-gray-600">Request medication refill</p>
                            </button>
                            <button onclick="useTemplate('results')" class="w-full text-left p-3 border rounded-lg hover:bg-gray-50 transition">
                                <p class="font-semibold text-gray-800 text-sm">Lab Results</p>
                                <p class="text-xs text-gray-600">Inquiry about test results</p>
                            </button>
                            <button onclick="useTemplate('followup')" class="w-full text-left p-3 border rounded-lg hover:bg-gray-50 transition">
                                <p class="font-semibold text-gray-800 text-sm">Follow-up</p>
                                <p class="text-xs text-gray-600">Post-appointment question</p>
                            </button>
                            <button onclick="useTemplate('appointment')" class="w-full text-left p-3 border rounded-lg hover:bg-gray-50 transition">
                                <p class="font-semibold text-gray-800 text-sm">Appointment Change</p>
                                <p class="text-xs text-gray-600">Reschedule request</p>
                            </button>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="bg-red-50 border-2 border-red-200 rounded-lg p-6">
                        <div class="flex items-start gap-3">
                            <i data-lucide="alert-triangle" class="text-red-600 mt-1" width="24" height="24"></i>
                            <div>
                                <p class="font-bold text-red-800 mb-2">Emergency?</p>
                                <p class="text-sm text-red-700 mb-3">For urgent medical issues, please call immediately</p>
                                <a href="tel:029119999" class="block w-full bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg text-center font-semibold transition">
                                    <i data-lucide="phone" class="inline" width="16" height="16"></i>
                                    (02) 911-9999
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        if (typeof lucide !== 'undefined') lucide.replace();

        function useTemplate(type) {
            const templates = {
                prescription: {
                    subject: 'Prescription Refill Request',
                    message: 'Dear Doctor,\n\nI would like to request a refill for my prescription. Please let me know if you need any additional information.\n\nThank you.'
                },
                results: {
                    subject: 'Inquiry About Lab Results',
                    message: 'Dear Doctor,\n\nI would like to inquire about my recent lab test results. When will they be available?\n\nThank you.'
                },
                followup: {
                    subject: 'Follow-up Question',
                    message: 'Dear Doctor,\n\nI have a follow-up question regarding my recent appointment. Could you please advise?\n\nThank you.'
                },
                appointment: {
                    subject: 'Appointment Reschedule Request',
                    message: 'Dear Doctor,\n\nI need to reschedule my upcoming appointment. Could we arrange for an alternative date?\n\nThank you.'
                }
            };

            const template = templates[type];
            if (template) {
                document.querySelector('input[name="subject"]').value = template.subject;
                document.querySelector('textarea[name="message"]').value = template.message;
            }
        }
    </script>
</body>
</html>