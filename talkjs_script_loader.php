<?php
// includes/talkjs_script_loader.php
if (!isset($current_user_data)) return;
?>
<script>
    // TalkJS SDK loader (DO NOT MODIFY)
    (function(t,a,l,k,j,s){
    s=a.createElement('script');s.async=1;s.src="https://cdn.talkjs.com/talk.js";a.head.appendChild(s)
    ;k=t.Promise;t.Talk={v:3,ready:{then:function(f){if(k)return new k(function(r,e){l.push([f,r,e])});l
    .push([f])},catch:function(){return k&&new k()},c:l}};})(window,document,[]);
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();

        const ME = <?= json_encode($current_user_data) ?>;
        // IMPORTANT: Replace with your actual TalkJS App ID
        const APP_ID = 'tvvLOd8E'; 

        const container = document.getElementById('talkjsWindowWrapper');
        const toggleBtn = document.getElementById('chatToggleBtn');
        const closeBtn = document.getElementById('chatCloseWidgetBtn');
        const chatDiv = document.getElementById('talkjs-container');
        const loader = document.getElementById('chatLoader');

        let session = null;
        let isLoaded = false;

        async function initTalkJS(targetId = null) {
            isLoaded = true;
            await Talk.ready;

            // 1. Create Current User
            const me = new Talk.User({
                id: ME.id,
                name: ME.name,
                email: ME.email,
                role: ME.role,
                photoUrl: ME.photoUrl
            });

            // 2. Create Session
            session = new Talk.Session({ appId: APP_ID, me: me });
            
            // 3. Mount UI
            loader.style.display = 'none';
            chatDiv.innerHTML = ''; // Clear container

            if (targetId) {
                // If a targetId is provided (from a button click), mount a Chatbox
                
                // 3a. Create the other user
                const other = new Talk.User({ id: targetId }); 
                
                // 3b. Create the conversation
                const conversation = session.getOrCreateConversation(Talk.oneOnOneId(session.me, other));
                conversation.setParticipant(session.me);
                conversation.setParticipant(other);
                
                // 3c. Mount the specific chatbox
                const chatbox = session.createChatbox();
                chatbox.select(conversation);
                chatbox.mount(chatDiv);
            } else {
                // If no targetId, mount the standard Inbox (list view)
                const inbox = session.createInbox();
                inbox.mount(chatDiv);
            }
        }

        // --- WIDGET TOGGLE LOGIC ---
        toggleBtn.addEventListener('click', () => {
            container.classList.remove('hidden');
            toggleBtn.classList.add('hidden');
            setTimeout(() => { container.classList.remove('scale-95', 'opacity-0'); }, 10);
            
            // Only initialize on first open
            if (!isLoaded) {
                initTalkJS();
            } else {
                // If already loaded, re-mount the Inbox (list view)
                initTalkJS();
            }
        });

        closeBtn.addEventListener('click', () => {
            container.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                container.classList.add('hidden');
                toggleBtn.classList.remove('hidden');
            }, 300);
        });

        // --- GLOBAL FUNCTION FOR CHAT BUTTONS (FIXED) ---
        window.startChatWith = async function(targetId, targetName, targetRole, targetPhoto) {
            // 1. Show the widget
            container.classList.remove('hidden');
            toggleBtn.classList.add('hidden');
            setTimeout(() => { container.classList.remove('scale-95', 'opacity-0'); }, 10);
            
            // Show loader while re-rendering
            chatDiv.innerHTML = '';
            loader.style.display = 'flex';

            // 2. Initialize (or re-mount) the chat focused on the target user
            await initTalkJS(targetId);
        };
    });
</script>