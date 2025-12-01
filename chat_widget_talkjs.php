<?php
// chat_widget_talkjs.php - Improved Secure Contact Logic (with Assigned Doctor + Admin list UI)
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'db.php'; // Ensure DB connection exists

// 1. Get Current User ID & Role
$current_user_id = $my_user_id ?? $_SESSION['user_id'] ?? null;
$current_user_role = $_SESSION['user_type'] ?? 'user';

// 2. FETCH CURRENT USER DETAILS (The Fix)
$display_name = 'User';
$avatar_url = '';

if ($current_user_id) {
    try {
        $stmtMe = $pdo->prepare("SELECT first_name, last_name, image FROM tblinfo WHERE user_id = ?");
        $stmtMe->execute([$current_user_id]);
        $myInfo = $stmtMe->fetch(PDO::FETCH_ASSOC);

        if ($myInfo) {
            $display_name = trim($myInfo['first_name'] . ' ' . $myInfo['last_name']);
            if (!empty($myInfo['image'])) {
                $avatar_url = 'uploads/' . $myInfo['image'];
            }
        } else {
            // Fallback for admins or legacy users
            $display_name = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Administrator';
        }
    } catch (Exception $e) {
        $display_name = $_SESSION['username'] ?? 'User';
    }
}

if (empty($avatar_url)) {
    $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($display_name) . '&background=0D8ABC&color=fff';
}

$current_user_data = [
    'id' => (($current_user_role === 'user') ? 'U_' : (($current_user_role === 'doctor') ? 'D_' : 'A_')) . $current_user_id,
    'name' => htmlspecialchars($display_name),
    'email' => $current_user_id . '@appointease.com',
    'photoUrl' => $avatar_url,
    'role' => $current_user_role
];

// 3. FETCH CONTACTS (Existing Logic)
$contacts = [];
try {
    if ($current_user_id) {
        if ($current_user_role === 'user') {
            // For patients: fetch distinct doctors from appointments that are confirmed (status=1) or attended (status=3)
            $stmt = $pdo->prepare("
                SELECT DISTINCT i.user_id, i.first_name, i.last_name, i.image, 'doctor' AS role
                FROM tblappointment a
                JOIN tblinfo i ON i.user_id = a.doctor
                WHERE a.user_id = ? AND a.status IN (1, 3)
                ORDER BY i.last_name
            ");
            $stmt->execute([$current_user_id]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($current_user_role === 'doctor') {
            $stmt = $pdo->prepare("
                SELECT DISTINCT i.user_id, i.first_name, i.last_name, i.image, 'user' AS role
                FROM tblappointment a
                JOIN tblinfo i ON i.user_id = a.user_id
                WHERE a.doctor = ? AND a.status IN (1, 3)
                ORDER BY i.last_name
            ");
            $stmt->execute([$current_user_id]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($current_user_role === 'admin') {
            // Admin can see all doctors and patients
            $stmt = $pdo->prepare("
                SELECT i.user_id, i.first_name, i.last_name, i.image, u.user_type AS role
                FROM tblinfo i
                JOIN tbluser u ON u.user_id = i.user_id
                WHERE u.user_type IN ('doctor', 'user') AND i.user_id != ?
                ORDER BY i.last_name
            ");
            $stmt->execute([$current_user_id]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {
    // ignore and keep contacts empty
}

// 4. EXTRA: For patients, also fetch the *latest assigned doctor per confirmed appointment* (useful for showing assigned doctor list)
$assignedDoctors = [];
if ($current_user_id && $current_user_role === 'user') {
    try {
        // Get latest confirmed appointment(s) and corresponding doctor info
        $stmtAssigned = $pdo->prepare("
            SELECT DISTINCT i.user_id, i.first_name, i.last_name, i.image, a.appointment_id, a.booking_date, a.booking_time
            FROM tblappointment a
            JOIN tblinfo i ON i.user_id = a.doctor
            WHERE a.user_id = ? AND a.status = 1
            ORDER BY a.booking_date DESC, a.booking_time DESC
        ");
        $stmtAssigned->execute([$current_user_id]);
        $assignedDoctors = $stmtAssigned->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $assignedDoctors = [];
    }
}

// Map contacts for JS
$contactList = array_map(function($c) {
    $role_prefix = ($c['role'] === 'user') ? 'U_' : (($c['role'] === 'doctor') ? 'D_' : 'A_');
    $photo = !empty($c['image']) ? 'uploads/' . $c['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($c['first_name'] . '+' . $c['last_name']);
    return [
        'user_id' => $c['user_id'],
        'id' => $role_prefix . $c['user_id'],
        'name' => htmlspecialchars($c['first_name'] . ' ' . $c['last_name']),
        'email' => $c['user_id'] . '@appointease.com',
        'photoUrl' => $photo,
        'role' => $c['role']
    ];
}, $contacts);

// Map assigned doctors for JS (patients only)
$assignedList = array_map(function($d) {
    $photo = !empty($d['image']) ? 'uploads/' . $d['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($d['first_name'] . '+' . $d['last_name']);
    return [
        'user_id' => $d['user_id'],
        'id' => 'D_' . $d['user_id'],
        'name' => htmlspecialchars($d['first_name'] . ' ' . $d['last_name']),
        'photoUrl' => $photo,
        'appointment_id' => $d['appointment_id'] ?? null,
        'booking_date' => $d['booking_date'] ?? null,
        'booking_time' => $d['booking_time'] ?? null,
        'role' => 'doctor'
    ];
}, $assignedDoctors);
?>

<div id="chatWidgetContainer" class="fixed bottom-6 right-6 z-50 font-sans">
    <div id="talkjsWindowWrapper" class="relative hidden transition-all duration-300 transform origin-bottom-right scale-95 opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border border-gray-200 flex flex-col" style="width: 380px; height: 550px; max-width: 90vw; max-height: 80vh;">
            <div class="bg-blue-600 p-3 flex justify-between items-center text-white shrink-0">
                <span class="font-bold text-sm flex items-center gap-2">
                    <i data-lucide="message-circle" width="18"></i> Messages
                </span>
                <button id="chatCloseWidgetBtn" class="hover:bg-blue-700 p-1 rounded-full transition">
                    <i data-lucide="x" width="18"></i>
                </button>
            </div>

            <!-- Top contact quick-list (Assigned Doctor for patient, or full list for admin) -->
            <div id="contactQuickList" class="p-3 border-b bg-white" style="max-height: 140px; overflow-y:auto;">
                <!-- Rendered by JS using CONTACTS_JSON and ASSIGNED_JSON -->
            </div>

            <!-- Main chat area where TalkJS mounts -->
            <div id="talkjs-container" class="flex-1 bg-gray-50 relative">
                <div id="chatLoader" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-2"></div>
                    <span class="text-xs">Loading chat...</span>
                </div>
            </div>
        </div>
    </div>

    <button id="chatToggleBtn" class="bg-blue-600 text-white w-14 h-14 rounded-full shadow-lg hover:bg-blue-700 transition duration-300 flex items-center justify-center hover:scale-110 active:scale-95 relative">
        <i data-lucide="message-square" width="24" height="24"></i>
    </button>
</div>

<?php include 'talkjs_script_loader.php'; ?>

<?php
// includes/talkjs_script_loader.php - kept as inline include for the widget
if (!isset($current_user_data)) return;
?>
<script>
(function(t,a,l,k,j,s){
    s=a.createElement('script');s.async=1;s.src="https://cdn.talkjs.com/talk.js";a.head.appendChild(s)
    ;k=t.Promise;t.Talk={v:3,ready:{then:function(f){if(k)return new k(function(r,e){l.push([f,r,e])});l
    .push([f])},catch:function(){return k&&new k()},c:l}};})(window,document,[]);
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const ME = <?= json_encode($current_user_data) ?>;
    const APP_ID = 'tvvLOd8E';

    const container = document.getElementById('talkjsWindowWrapper');
    const toggleBtn = document.getElementById('chatToggleBtn');
    const closeBtn = document.getElementById('chatCloseWidgetBtn');
    const chatDiv = document.getElementById('talkjs-container');
    const loader = document.getElementById('chatLoader');
    const quickList = document.getElementById('contactQuickList');

    // Data passed from PHP
    const CONTACTS = <?= json_encode($contactList) ?>;     // contacts (doctors/patients) relevant to the user or admin
    const ASSIGNED = <?= json_encode($assignedList) ?>;    // assigned doctors for a patient (if any)

    let session = null;
    let isLoaded = false;

    // Utility: render a small contact card in quick list
    function renderContactCard(item) {
        const wrapper = document.createElement('div');
        wrapper.className = 'flex items-center gap-3 mb-2';

        const img = document.createElement('img');
        img.src = item.photoUrl;
        img.alt = item.name;
        img.className = 'w-9 h-9 rounded-full object-cover border';

        const info = document.createElement('div');
        info.style.flex = '1';
        const name = document.createElement('div');
        name.className = 'text-sm font-medium';
        name.textContent = item.name;
        const meta = document.createElement('div');
        meta.className = 'text-xs text-gray-500';
        meta.textContent = item.role === 'doctor' ? 'Doctor' : (item.role === 'user' ? 'Patient' : 'Admin');

        info.appendChild(name);
        info.appendChild(meta);

        const btn = document.createElement('button');
        btn.className = 'px-2 py-1 rounded text-xs bg-blue-600 text-white hover:bg-blue-700 transition';
        btn.textContent = 'Message';
        btn.addEventListener('click', () => {
            // Use the global helper
            window.startChatWith(item.id, item.name, item.role, item.photoUrl);
        });

        wrapper.appendChild(img);
        wrapper.appendChild(info);
        wrapper.appendChild(btn);

        return wrapper;
    }

    // Populate quick list: priority
    // 1) If user is patient and has assigned doctors, show them
    // 2) Else if admin show CONTACTS (full list)
    // 3) Else show CONTACTS if any
    function populateQuickList() {
        quickList.innerHTML = '';

        if (ME.role === 'user' && ASSIGNED && ASSIGNED.length) {
            const heading = document.createElement('div');
            heading.className = 'text-xs font-semibold mb-2';
            heading.textContent = 'Assigned Doctor(s)';
            quickList.appendChild(heading);

            ASSIGNED.forEach(d => {
                // Map to shape expected by renderContactCard
                const item = {
                    id: d.id,
                    name: d.name,
                    role: 'doctor',
                    photoUrl: d.photoUrl,
                    appointment_id: d.appointment_id,
                    booking_date: d.booking_date,
                    booking_time: d.booking_time
                };
                quickList.appendChild(renderContactCard(item));
            });
            return;
        }

        // Admin: show all contacts (doctors + patients)
        if (ME.role === 'admin' && CONTACTS && CONTACTS.length) {
            const heading = document.createElement('div');
            heading.className = 'text-xs font-semibold mb-2';
            heading.textContent = 'Users';
            quickList.appendChild(heading);

            CONTACTS.forEach(c => {
                const item = {
                    id: c.id,
                    name: c.name,
                    role: c.role,
                    photoUrl: c.photoUrl
                };
                quickList.appendChild(renderContactCard(item));
            });
            return;
        }

        // Default: for doctors or users with no assigned doctors, show CONTACTS (if any)
        if (CONTACTS && CONTACTS.length) {
            const heading = document.createElement('div');
            heading.className = 'text-xs font-semibold mb-2';
            heading.textContent = 'Contacts';
            quickList.appendChild(heading);

            CONTACTS.forEach(c => {
                const item = {
                    id: c.id,
                    name: c.name,
                    role: c.role,
                    photoUrl: c.photoUrl
                };
                quickList.appendChild(renderContactCard(item));
            });
            return;
        }

        // Nothing to show
        const none = document.createElement('div');
        none.className = 'text-xs text-gray-500';
        none.textContent = (ME.role === 'user') ? 'No assigned doctors yet.' : 'No contacts available.';
        quickList.appendChild(none);
    }

    // Toggle Logic
    toggleBtn.addEventListener('click', () => {
        container.classList.remove('hidden');
        toggleBtn.classList.add('hidden');
        setTimeout(() => { container.classList.remove('scale-95', 'opacity-0'); }, 10);
        if (!isLoaded) initTalkJS();
    });

    closeBtn.addEventListener('click', () => {
        container.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            container.classList.add('hidden');
            toggleBtn.classList.remove('hidden');
        }, 300);
    });

    async function initTalkJS() {
        isLoaded = true;
        await Talk.ready;

        // Create current user for TalkJS
        const me = new Talk.User({
            id: ME.id,
            name: ME.name,
            email: ME.email,
            role: ME.role,
            photoUrl: ME.photoUrl
        });

        session = new Talk.Session({ appId: APP_ID, me: me });

        // Mount the Inbox by default (list + chat)
        const inbox = session.createInbox();
        inbox.mount(chatDiv);

        setTimeout(() => { loader.style.display = 'none'; }, 1200);

        // Populate quick contact list after session ready
        populateQuickList();
    }

    // Global helper to start chat from other buttons
    window.startChatWith = async function(targetId, targetName, targetRole, targetPhoto) {
        // open the widget if closed
        if (container.classList.contains('hidden') || toggleBtn.offsetParent !== null) {
            // If toggle button visible (widget closed), simulate click to open
            if (!container.classList.contains('hidden')) {
                // already visible
            } else {
                toggleBtn.click();
            }
        }

        if (!session) await initTalkJS();

        const other = new Talk.User({
            id: targetId,
            name: targetName,
            role: targetRole,
            photoUrl: targetPhoto
        });

        const conversation = session.getOrCreateConversation(Talk.oneOnOneId(session.me, other));
        conversation.setParticipant(session.me);
        conversation.setParticipant(other);

        // Mount a chatbox to show the specific conversation (replaces inbox mount visually)
        const chatbox = session.createChatbox();
        chatbox.select(conversation);
        // Ensure chatbox replaces the inbox area
        chatDiv.innerHTML = ''; // remove inbox to prevent duplicate mounts
        chatbox.mount(chatDiv);
    };
});
</script>