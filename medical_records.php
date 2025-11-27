<?php
// medical_records.php - Patient Health History & Reports
require_once 'session_handler.php';
require_once 'security_helper.php';
require_once 'db.php';
require_once 'logging_helper.php';

session_require_auth(['user']);
$user_id = session_get_user_id();

// --- 1. FETCH RECORDS ---
// Get appointments and join with medical records if they exist
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date'] ?? '';

$sql = "SELECT 
            a.id AS appointment_id, 
            a.booking_date, 
            a.booking_time, 
            a.status,
            d.first_name, 
            d.last_name, 
            d.specialization, 
            mr.diagnosis, 
            mr.prescription, 
            mr.notes, 
            mr.created_at AS record_date
        FROM tblappointment a
        JOIN tblinfo d ON a.doctor = d.user_id
        LEFT JOIN tbl_medical_records mr ON a.id = mr.appointment_id
        WHERE a.user_id = ? 
        AND a.status != 0 -- Exclude cancelled
        AND a.booking_date <= CURRENT_DATE()";

$params = [$user_id];

if ($search) {
    $sql .= " AND (d.first_name LIKE ? OR d.last_name LIKE ? OR d.specialization LIKE ? OR mr.diagnosis LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($date_filter) {
    $sql .= " AND a.booking_date = ?";
    $params[] = $date_filter;
}

$sql .= " ORDER BY a.booking_date DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error loading records: " . e($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - AppointEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Print Styles */
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: white; }
            #recordModal { position: static; overflow: visible; height: auto; }
            #recordModalContent { 
                box-shadow: none; border: 2px solid #000; width: 100%; max-width: 100%; 
                position: static; transform: none; margin: 0; 
            }
            .modal-backdrop { display: none; }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen overflow-hidden">
        
        <div class="no-print">
            <?php include 'includes/client_sidebar.php'; ?>
        </div>

        <main class="flex-1 overflow-auto relative">
            <div class="md:hidden p-4 flex items-center justify-between bg-white border-b sticky top-0 z-20 no-print">
                <span class="font-bold text-lg text-purple-700">AppointEase</span>
                <button id="mobileMenuBtn" class="p-2 bg-gray-100 rounded-lg"><i data-lucide="menu"></i></button>
            </div>

            <div class="p-6 max-w-5xl mx-auto">
                
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8 no-print">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Medical Records</h1>
                        <p class="text-gray-500">History of your visits, diagnoses, and prescriptions.</p>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 mb-8 no-print">
                    <form method="GET" class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1 relative">
                            <i data-lucide="search" class="absolute left-3 top-3 text-gray-400 w-5 h-5"></i>
                            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search diagnosis, doctor..." class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div class="relative">
                            <i data-lucide="calendar" class="absolute left-3 top-3 text-gray-400 w-5 h-5"></i>
                            <input type="date" name="date" value="<?= e($date_filter) ?>" class="pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500">
                        </div>
                        <button type="submit" class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl transition">Filter</button>
                        <?php if($search || $date_filter): ?>
                            <a href="medical_records.php" class="px-4 py-2.5 text-gray-500 font-medium hover:text-gray-700">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="space-y-6">
                    <?php if(empty($records)): ?>
                        <div class="text-center py-12 bg-white rounded-2xl border border-gray-200 border-dashed">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="folder-open" class="w-8 h-8 text-gray-400"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-700">No records found</h3>
                            <p class="text-gray-500 text-sm">You haven't completed any appointments yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($records as $rec): 
                            $docName = "Dr. " . e($rec['first_name']) . " " . e($rec['last_name']);
                            $dateObj = new DateTime($rec['booking_date']);
                            $hasRecord = !empty($rec['diagnosis']); // Check if record exists
                            
                            // Prepare JSON data for modal
                            $modalData = htmlspecialchars(json_encode([
                                'date' => $dateObj->format('F d, Y'),
                                'doctor' => $docName,
                                'spec' => e($rec['specialization']),
                                'diagnosis' => e($rec['diagnosis'] ?? 'No diagnosis recorded.'),
                                'prescription' => e($rec['prescription'] ?? 'None'),
                                'notes' => e($rec['notes'] ?? 'No additional notes.')
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative overflow-hidden group">
                            <div class="absolute left-0 top-0 bottom-0 w-1.5 <?= $hasRecord ? 'bg-green-500' : 'bg-gray-300' ?>"></div>

                            <div class="flex flex-col md:flex-row gap-6">
                                <div class="md:w-32 flex-shrink-0 flex flex-col items-start md:items-center text-center md:border-r md:border-gray-100 md:pr-6">
                                    <span class="text-sm font-bold text-purple-600 uppercase tracking-wider"><?= $dateObj->format('M Y') ?></span>
                                    <span class="text-3xl font-bold text-gray-800 my-1"><?= $dateObj->format('d') ?></span>
                                    <span class="text-xs text-gray-500"><?= $dateObj->format('l') ?></span>
                                </div>

                                <div class="flex-1">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900"><?= $docName ?></h3>
                                            <p class="text-sm text-purple-600 font-medium flex items-center gap-1">
                                                <i data-lucide="stethoscope" class="w-3 h-3"></i>
                                                <?= e($rec['specialization'] ?: 'General Practitioner') ?>
                                            </p>
                                        </div>
                                        <?php if($hasRecord): ?>
                                            <span class="px-3 py-1 bg-green-50 text-green-700 border border-green-100 rounded-full text-xs font-bold uppercase tracking-wide flex items-center gap-1">
                                                <i data-lucide="check-circle" class="w-3 h-3"></i> Report Ready
                                            </span>
                                        <?php else: ?>
                                            <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-xs font-bold uppercase tracking-wide">
                                                Pending Report
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if($hasRecord): ?>
                                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 mb-4">
                                            <p class="text-sm text-gray-800">
                                                <span class="font-bold text-gray-500 uppercase text-xs mr-2">Diagnosis:</span>
                                                <?= e($rec['diagnosis']) ?>
                                            </p>
                                        </div>
                                        <button onclick='openRecordModal(<?= $modalData ?>)' class="text-sm font-semibold text-purple-600 hover:text-purple-700 flex items-center gap-1 transition">
                                            View Full Details & Print <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                        </button>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-500 italic">
                                            The doctor has not uploaded the medical record for this visit yet.
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <div id="recordModal" class="fixed inset-0 z-50 hidden overflow-y-auto no-print" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity backdrop-blur-sm modal-backdrop" onclick="closeModal()"></div>
        
        <div class="flex min-h-full items-center justify-center p-4">
            <div id="recordModalContent" class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-2xl">
                
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="file-heart" class="text-purple-600"></i> Medical Record
                    </h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 no-print">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <div class="p-8" id="printableArea">
                    <div class="flex justify-between items-start mb-8 border-b pb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-purple-700 mb-1">AppointEase</h1>
                            <p class="text-xs text-gray-500">Untalan General Hospital Services</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-800" id="mDate">Date</p>
                            <p class="text-xs text-gray-500">Visit Date</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase mb-1">Doctor</p>
                            <p class="font-semibold text-gray-900 text-lg" id="mDoctor">Dr. Name</p>
                            <p class="text-sm text-purple-600" id="mSpec">Specialization</p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase mb-1">Patient</p>
                            <p class="font-semibold text-gray-900 text-lg"><?= e($user_id) /* You might want to fetch real name here */ ?></p>
                            <p class="text-sm text-gray-500">Patient ID</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                            <h4 class="font-bold text-blue-800 mb-2 text-sm uppercase">Diagnosis</h4>
                            <p class="text-gray-800" id="mDiagnosis">...</p>
                        </div>

                        <div>
                            <h4 class="font-bold text-gray-800 mb-2 text-sm uppercase border-b pb-1">Prescription (Rx)</h4>
                            <p class="text-gray-700 leading-relaxed whitespace-pre-wrap" id="mPrescription">...</p>
                        </div>

                        <div>
                            <h4 class="font-bold text-gray-800 mb-2 text-sm uppercase border-b pb-1">Clinical Notes</h4>
                            <p class="text-gray-600 leading-relaxed whitespace-pre-wrap" id="mNotes">...</p>
                        </div>
                    </div>
                    
                    <div class="mt-12 pt-4 border-t border-gray-200 text-center">
                        <p class="text-xs text-gray-400 italic">This is a computer-generated document. No signature is required.</p>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 no-print">
                    <button onclick="closeModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-100">Close</button>
                    <button onclick="window.print()" class="px-4 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 flex items-center gap-2">
                        <i data-lucide="printer" class="w-4 h-4"></i> Print Record
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        const modal = document.getElementById('recordModal');

        function openRecordModal(data) {
            document.getElementById('mDate').textContent = data.date;
            document.getElementById('mDoctor').textContent = data.doctor;
            document.getElementById('mSpec').textContent = data.spec;
            document.getElementById('mDiagnosis').textContent = data.diagnosis;
            document.getElementById('mPrescription').textContent = data.prescription;
            document.getElementById('mNotes').textContent = data.notes;
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>