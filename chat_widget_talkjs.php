<?php
// chat_widget_talkjs.php - "Clean Clinical" Design with Roles + Quick Search
if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'db.php';

// --- PHP LOGIC (SAME AS BEFORE) ---
$current_user_id = $my_user_id ?? $_SESSION['user_id'] ?? null;
$current_user_role = $_SESSION['user_type'] ?? 'user';

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
            $display_name = $_SESSION['user_name'] ?? 'Administrator';
        }
    } catch (Exception $e) {
        $display_name = $_SESSION['username'] ?? 'User';
    }
}

if (empty($avatar_url)) {
    $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($display_name) . '&background=0d9488&color=fff';
}

$current_user_data = [
    'id' => (($current_user_role === 'user') ? 'U_' : (($current_user_role === 'doctor') ? 'D_' : 'A_')) . $current_user_id,
    'name' => htmlspecialchars($display_name),
    'email' => $current_user_id . '@appointease.com',
    'photoUrl' => $avatar_url,
    'role' => $current_user_role
];

// FETCH CONTACTS
$contacts = [];
try {
    if ($current_user_id) {
        if ($current_user_role === 'user') {
            $stmt = $pdo->prepare("SELECT DISTINCT i.user_id, i.first_name, i.last_name, i.image, 'doctor' AS role FROM tblappointment a JOIN tblinfo i ON i.user_id = a.doctor WHERE a.user_id = ? AND a.status IN (1, 3) ORDER BY i.last_name");
            $stmt->execute([$current_user_id]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($current_user_role === 'doctor') {
            $stmt = $pdo->prepare("SELECT DISTINCT i.user_id, i.first_name, i.last_name, i.image, 'user' AS role FROM tblappointment a JOIN tblinfo i ON i.user_id = a.user_id WHERE a.doctor = ? AND a.status IN (1, 3) ORDER BY i.last_name");
            $stmt->execute([$current_user_id]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($current_user_role === 'admin') {
            $stmt = $pdo->prepare("SELECT i.user_id, i.first_name, i.last_name, i.image, u.user_type AS role FROM tblinfo i JOIN tbluser u ON u.user_id = i.user_id WHERE u.user_type IN ('doctor', 'user') AND i.user_id != ? ORDER BY i.last_name");
            $stmt->execute([$current_user_id]);
            $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (Exception $e) {}

// FETCH ASSIGNED
$assignedDoctors = [];
if ($current_user_id && $current_user_role === 'user') {
    try {
        $stmtAssigned = $pdo->prepare("SELECT DISTINCT i.user_id, i.first_name, i.last_name, i.image FROM tblappointment a JOIN tblinfo i ON i.user_id = a.doctor WHERE a.user_id = ? AND a.status = 1 ORDER BY a.booking_date DESC");
        $stmtAssigned->execute([$current_user_id]);
        $assignedDoctors = $stmtAssigned->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// JSON Maps
$contactList = array_map(function($c) {
    return [
        'id' => (($c['role'] === 'user') ? 'U_' : (($c['role'] === 'doctor') ? 'D_' : 'A_')) . $c['user_id'],
        'name' => htmlspecialchars($c['first_name'] . ' ' . $c['last_name']),
        'photoUrl' => !empty($c['image']) ? 'uploads/' . $c['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($c['first_name'] . '+' . $c['last_name']),
        'role' => $c['role']
    ];
}, $contacts);

$assignedList = array_map(function($d) {
    return [
        'id' => 'D_' . $d['user_id'],
        'name' => htmlspecialchars($d['first_name'] . ' ' . $d['last_name']),
        'photoUrl' => !empty($d['image']) ? 'uploads/' . $d['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($d['first_name'] . '+' . $d['last_name']),
        'role' => 'doctor'
    ];
}, $assignedDoctors);
?>

<style>
    #talkjsWindowWrapper { transition: transform 0.3s ease-out, opacity 0.3s ease-out; }
    .quick-scroll::-webkit-scrollbar { width: 4px; height: 4px; }
    .quick-scroll::-webkit-scrollbar-track { background: transparent; }
    .quick-scroll::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 2px; }
    /* search input styles */
    .chat-search {
        display: flex;
        gap: 8px;
        align-items: center;
        background: #fff;
        border: 1px solid #e2e8f0;
        padding: 6px;
        border-radius: 10px;
    }
    .chat-search input {
        border: none;
        outline: none;
        font-size: 13px;
        background: transparent;
        width: 100%;
    }
    .chat-search button {
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 4px;
    }
</style>

<div id="chatWidgetContainer" class="fixed bottom-5 right-5 z-[9999] font-sans flex flex-col items-end gap-3">
    
    <div id="talkjsWindowWrapper" class="relative hidden transform translate-y-10 opacity-0 origin-bottom">
        <div class="bg-white rounded-t-xl rounded-bl-xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.2)] overflow-hidden border border-slate-200 flex flex-col" 
             style="width: 360px; height: 580px; max-width: calc(100vw - 40px); max-height: calc(100vh - 120px);">
            
            <div class="bg-slate-800 p-4 flex justify-between items-center text-white shrink-0">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="bg-teal-500 p-1.5 rounded-lg">
                            <i data-lucide="activity" width="18" height="18" class="text-white"></i>
                        </div>
                        <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-green-500 border-2 border-slate-800 rounded-full"></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-semibold text-sm leading-none">Medical Support</span>
                        <span class="text-[10px] text-slate-400 mt-1 uppercase tracking-wider">Untalan GH</span>
                    </div>
                </div>
                <button id="chatCloseWidgetBtn" class="text-slate-400 hover:text-white hover:bg-white/10 p-1.5 rounded-md transition">
                    <i data-lucide="minus" width="20"></i>
                </button>
            </div>

            <!-- Quick contacts + Search -->
            <div id="contactQuickListContainer" class="bg-slate-50 border-b border-slate-200 p-3 hidden">
                <div class="mb-2">
                    <div class="chat-search">
                        <i data-lucide="search" width="16" class="text-slate-400"></i>
                        <input id="chatSearchInput" type="search" placeholder="Search contacts..." aria-label="Search contacts">
                        <button id="chatSearchClear" title="Clear search" aria-label="Clear search" style="display:none;">
                            <i data-lucide="x" width="14" class="text-slate-400"></i>
                        </button>
                    </div>
                </div>
                <div id="contactQuickList" class="flex gap-2 overflow-x-auto quick-scroll py-1 px-1"></div>
            </div>

            <div id="talkjs-container" class="flex-1 bg-white relative">
                <div id="chatLoader" class="absolute inset-0 flex flex-col items-center justify-center bg-white z-20">
                    <div class="w-10 h-10 border-4 border-teal-100 border-t-teal-600 rounded-full animate-spin mb-3"></div>
                    <span class="text-xs text-slate-500 font-medium">Loading secure chat...</span>
                </div>
            </div>
        </div>
    </div>

    <button id="chatToggleBtn" class="flex items-center gap-3 bg-teal-600 text-white px-5 py-3.5 rounded-full shadow-lg hover:bg-teal-700 hover:shadow-teal-600/30 transition-all duration-300 transform hover:-translate-y-1 group">
        <span class="font-semibold text-sm tracking-wide">Chat with us</span>
        <div class="bg-white/20 p-1 rounded-full">
            <i data-lucide="message-circle" width="20" height="20" class="group-hover:rotate-12 transition-transform"></i>
        </div>
    </button>

</div>

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
    const quickListContainer = document.getElementById('contactQuickListContainer');
    const searchInput = document.getElementById('chatSearchInput');
    const searchClearBtn = document.getElementById('chatSearchClear');

    const CONTACTS = <?= json_encode($contactList) ?>;
    const ASSIGNED = <?= json_encode($assignedList) ?>;

    let session = null;
    let isLoaded = false;

    // Helper: debounce
    function debounce(fn, delay = 200) {
        let t;
        return function(...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // Render "Chip" style contact with ROLE label
    function renderContactChip(item) {
        const btn = document.createElement('button');
        btn.className = 'flex items-center gap-3 bg-white border border-slate-200 rounded-lg pl-2 pr-4 py-2 hover:border-teal-500 hover:shadow-md transition-all shrink-0 group min-w-[140px] text-left';
        
        const img = document.createElement('img');
        img.src = item.photoUrl;
        img.className = 'w-9 h-9 rounded-full object-cover bg-slate-100 border border-slate-100';
        img.alt = item.name;
        
        const infoDiv = document.createElement('div');
        infoDiv.className = 'flex flex-col';

        const name = document.createElement('span');
        name.className = 'text-xs font-bold text-slate-800 group-hover:text-teal-700 whitespace-nowrap leading-tight';
        const shortName = item.name.length > 15 ? item.name.substring(0,12) + '...' : item.name;
        name.textContent = shortName;

        // Role Label
        const roleLabel = document.createElement('span');
        roleLabel.className = 'text-[10px] font-medium text-slate-400 uppercase tracking-wide group-hover:text-teal-500';
        roleLabel.textContent = (item.role || '').charAt(0).toUpperCase() + (item.role || '').slice(1);

        infoDiv.appendChild(name);
        infoDiv.appendChild(roleLabel);

        btn.appendChild(img);
        btn.appendChild(infoDiv);

        btn.addEventListener('click', () => {
            window.startChatWith(item.id, item.name, item.role, item.photoUrl);
        });

        return btn;
    }

    // Filter array by query (name or role)
    function matchesQuery(item, q) {
        if (!q) return true;
        const text = (item.name + ' ' + (item.role || '')).toLowerCase();
        return text.indexOf(q) !== -1;
    }

    // Populate quick list from a provided array
    function populateFromArray(arr) {
        arr.forEach(item => quickList.appendChild(renderContactChip(item)));
    }

    // Filter and render (combines ASSIGNED or CONTACTS based on role)
    function filterAndRenderQuickList(query) {
        quickList.innerHTML = '';
        const q = (query || '').trim().toLowerCase();

        // Decide which list to show (same logic as before)
        let listToShow = [];
        if (ME.role === 'user' && ASSIGNED.length > 0) {
            listToShow = ASSIGNED;
        } else if (ME.role === 'admin' || (CONTACTS.length > 0)) {
            listToShow = CONTACTS;
        }

        // If there's a search query, include both CONTACTS and ASSIGNED to give broader results
        if (q) {
            const merged = [...new Map([...ASSIGNED, ...CONTACTS].map(i => [i.id, i])).values()];
            const filtered = merged.filter(item => matchesQuery(item, q));
            if (filtered.length > 0) {
                filtered.forEach(item => quickList.appendChild(renderContactChip(item)));
                quickListContainer.classList.remove('hidden');
                return;
            } else {
                // no results
                quickListContainer.classList.remove('hidden');
                const none = document.createElement('div');
                none.className = 'text-xs text-slate-400 px-2';
                none.textContent = 'No contacts found';
                quickList.appendChild(none);
                return;
            }
        }

        // No search query — show default listToShow
        if (listToShow.length > 0) {
            listToShow.forEach(item => quickList.appendChild(renderContactChip(item)));
            quickListContainer.classList.remove('hidden');
        } else {
            quickListContainer.classList.add('hidden');
        }
    }

    function populateQuickList() {
        filterAndRenderQuickList('');
    }

    // Wire search input with debounce
    const onSearchInput = debounce(function(e) {
        const v = (e.target.value || '').trim();
        if (v.length > 0) {
            searchClearBtn.style.display = 'inline';
        } else {
            searchClearBtn.style.display = 'none';
        }
        filterAndRenderQuickList(v.toLowerCase());
    }, 180);

    searchInput.addEventListener('input', onSearchInput);

    searchClearBtn.addEventListener('click', (ev) => {
        ev.preventDefault();
        searchInput.value = '';
        searchClearBtn.style.display = 'none';
        filterAndRenderQuickList('');
        searchInput.focus();
    });

    toggleBtn.addEventListener('click', () => {
        container.classList.remove('hidden');
        toggleBtn.classList.add('hidden');
        
        setTimeout(() => { 
            container.classList.remove('translate-y-10', 'opacity-0'); 
        }, 10);
        
        if (!isLoaded) initTalkJS();

        // When opening, focus search if quick list visible
        setTimeout(() => {
            if (!quickListContainer.classList.contains('hidden')) {
                searchInput.focus();
            }
        }, 400);
    });

    closeBtn.addEventListener('click', () => {
        container.classList.add('translate-y-10', 'opacity-0');
        setTimeout(() => {
            container.classList.add('hidden');
            toggleBtn.classList.remove('hidden');
        }, 300);
    });

    async function initTalkJS() {
        isLoaded = true;
        await Talk.ready;

        const me = new Talk.User({
            id: ME.id,
            name: ME.name,
            email: ME.email,
            role: ME.role,
            photoUrl: ME.photoUrl
        });

        session = new Talk.Session({ appId: APP_ID, me: me });

        const inbox = session.createInbox();
        inbox.mount(chatDiv);

        setTimeout(() => { loader.style.display = 'none'; }, 1000);
        populateQuickList();
    }

    window.startChatWith = async function(targetId, targetName, targetRole, targetPhoto) {
        if (container.classList.contains('hidden')) {
            toggleBtn.click();
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

        const chatbox = session.createChatbox();
        chatbox.select(conversation);
        chatDiv.innerHTML = ''; 
        chatbox.mount(chatDiv);
    };
});
</script>