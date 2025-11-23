<!-- chat_widget.php - Embeddable chat widget for dashboards -->
<!-- Add this to any dashboard page where you want a chat notification icon -->

<style>
  .chat-widget-button {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
  }
  .chat-notification-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    border-radius: 9999px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
  }
  .chat-popup {
    position: fixed;
    bottom: 90px;
    right: 20px;
    width: 400px;
    max-width: calc(100vw - 40px);
    height: 600px;
    max-height: calc(100vh - 140px);
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    display: none;
    flex-direction: column;
    z-index: 1000;
  }
  .chat-popup.active { display: flex; }
  .chat-popup-header {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .chat-popup-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
  }
  .chat-popup-footer {
    padding: 16px;
    border-top: 1px solid #e5e7eb;
  }
  .quick-chat-message {
    margin-bottom: 12px;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
  }
  .quick-chat-message:hover {
    background: #f3f4f6;
  }
  .unread-indicator {
    width: 10px;
    height: 10px;
    background: #3b82f6;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
  }
</style>

<!-- Chat Widget Button -->
<div class="chat-widget-button">
  <button id="chatWidgetBtn" onclick="toggleChatPopup()" 
          class="bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-full shadow-lg transition relative">
    <i data-lucide="message-circle" width="24" height="24"></i>
    <span id="chatNotificationBadge" class="chat-notification-badge" style="display: none;">0</span>
  </button>
</div>

<!-- Chat Popup -->
<div id="chatPopup" class="chat-popup">
  <div class="chat-popup-header">
    <div>
      <h3 class="font-bold text-gray-800">Messages</h3>
      <p class="text-xs text-gray-500">Recent conversations</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="chat.php" class="text-blue-600 hover:text-blue-700 p-2">
        <i data-lucide="maximize-2" width="18" height="18"></i>
      </a>
      <button onclick="toggleChatPopup()" class="text-gray-500 hover:text-gray-700 p-2">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
  </div>
  
  <div id="quickChatList" class="chat-popup-body">
    <div class="text-center text-gray-500 py-8">
      <i data-lucide="loader" class="animate-spin w-8 h-8 mx-auto mb-2 text-gray-400"></i>
      <p>Loading messages...</p>
    </div>
  </div>
  
  <div class="chat-popup-footer">
    <a href="chat.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 rounded-lg transition">
      View All Messages
    </a>
  </div>
</div>

<script>
(function() {
  let chatPopupOpen = false;
  let unreadCount = 0;
  let updateInterval = null;

  window.toggleChatPopup = function() {
    chatPopupOpen = !chatPopupOpen;
    const popup = document.getElementById('chatPopup');
    
    if (chatPopupOpen) {
      popup.classList.add('active');
      loadQuickChats();
      
      // Start auto-refresh
      if (updateInterval) clearInterval(updateInterval);
      updateInterval = setInterval(loadQuickChats, 5000);
    } else {
      popup.classList.remove('active');
      if (updateInterval) clearInterval(updateInterval);
    }
  };

  async function loadQuickChats() {
    try {
      const response = await fetch('chat_api.php?action=get_conversations');
      const data = await response.json();

      if (data.success && data.conversations) {
        const list = document.getElementById('quickChatList');
        
        if (data.conversations.length === 0) {
          list.innerHTML = `
            <div class="text-center text-gray-500 py-8">
              <i data-lucide="message-circle" class="w-12 h-12 mx-auto mb-2 text-gray-400"></i>
              <p class="text-sm">No conversations yet</p>
              <a href="chat.php" class="text-blue-600 hover:text-blue-700 text-sm mt-2 inline-block">
                Start a conversation
              </a>
            </div>
          `;
        } else {
          // Show only 5 most recent
          const recentConversations = data.conversations.slice(0, 5);
          
          list.innerHTML = recentConversations.map(conv => {
            const name = `${conv.contact_first_name} ${conv.contact_last_name}`;
            const lastMsg = conv.last_message ? 
              (conv.last_message.length > 50 ? conv.last_message.substring(0, 50) + '...' : conv.last_message) : 
              'No messages yet';
            const unreadBadge = conv.unread_count > 0 ? 
              `<span class="bg-blue-600 text-white text-xs px-2 py-1 rounded-full ml-2">${conv.unread_count}</span>` : '';
            
            return `
              <a href="chat.php?conversation=${conv.id}" class="quick-chat-message block border-b last:border-b-0">
                <div class="flex items-start justify-between mb-1">
                  <div class="flex items-center">
                    ${conv.unread_count > 0 ? '<span class="unread-indicator"></span>' : ''}
                    <h4 class="font-semibold text-gray-800 text-sm">${name}</h4>
                  </div>
                  ${unreadBadge}
                </div>
                <p class="text-xs text-gray-600">${lastMsg}</p>
                <p class="text-xs text-gray-400 mt-1">${formatQuickDate(conv.last_message_time)}</p>
              </a>
            `;
          }).join('');
        }
        
        if (typeof lucide !== 'undefined') lucide.replace();
      }
    } catch (error) {
      console.error('Error loading quick chats:', error);
    }
  }

  async function updateUnreadCount() {
    try {
      const response = await fetch('chat_api.php?action=get_unread_count');
      const data = await response.json();

      if (data.success) {
        unreadCount = data.unread_count;
        const badge = document.getElementById('chatNotificationBadge');
        
        if (unreadCount > 0) {
          badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
          badge.style.display = 'flex';
        } else {
          badge.style.display = 'none';
        }
      }
    } catch (error) {
      console.error('Error updating unread count:', error);
    }
  }

  function formatQuickDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffMinutes = Math.floor(diffTime / (1000 * 60));
    const diffHours = Math.floor(diffTime / (1000 * 60 * 60));
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffMinutes < 60) {
      return `${diffMinutes}m ago`;
    } else if (diffHours < 24) {
      return `${diffHours}h ago`;
    } else if (diffDays === 1) {
      return 'Yesterday';
    } else if (diffDays < 7) {
      return `${diffDays}d ago`;
    } else {
      return date.toLocaleDateString([], {month: 'short', day: 'numeric'});
    }
  }

  // Initialize
  updateUnreadCount();
  
  // Update unread count every 10 seconds
  setInterval(updateUnreadCount, 10000);

  // Close popup when clicking outside
  document.addEventListener('click', function(e) {
    const popup = document.getElementById('chatPopup');
    const btn = document.getElementById('chatWidgetBtn');
    
    if (chatPopupOpen && !popup.contains(e.target) && !btn.contains(e.target)) {
      toggleChatPopup();
    }
  });
})();
</script>