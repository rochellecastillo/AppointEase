<?php
// chat.php - Chat interface for doctors and patients
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = strtolower($_SESSION['user_type']);

function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Get user info
$stmt = $pdo->prepare("SELECT first_name, last_name FROM tblinfo WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);
$user_name = trim(($user_info['first_name'] ?? '') . ' ' . ($user_info['last_name'] ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Messages - AppointmentEase</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/lucide.min.js"></script>
  <style>
    .chat-container { height: calc(100vh - 8rem); }
    .messages-list { height: calc(100% - 140px); }
    .conversation-item:hover { background-color: #f3f4f6; }
    .conversation-item.active { background-color: #dbeafe; border-left: 4px solid #3b82f6; }
  </style>
</head>
<body class="bg-gray-100">
  <div class="min-h-screen">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <a href="<?= $user_type === 'doctor' ? 'doctor_home.php' : 'client_home.php' ?>" 
             class="text-gray-600 hover:text-gray-800">
            <i data-lucide="arrow-left" class="w-6 h-6"></i>
          </a>
          <h1 class="text-2xl font-bold text-gray-800">Messages</h1>
        </div>
        <div class="flex items-center gap-3">
          <button onclick="openNewChatModal()" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition flex items-center gap-2">
            <i data-lucide="plus" class="w-4 h-4"></i>
            New Chat
          </button>
          <span class="text-sm text-gray-600"><?= e($user_name) ?></span>
        </div>
      </div>
    </div>

    <!-- Chat Container -->
    <div class="max-w-7xl mx-auto p-4">
      <div class="bg-white rounded-lg shadow chat-container flex">
        
        <!-- Conversations List -->
        <div class="w-80 border-r flex flex-col">
          <div class="p-4 border-b">
            <input type="text" id="searchConversations" placeholder="Search conversations..." 
                   class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
          
          <div id="conversationsList" class="flex-1 overflow-y-auto">
            <div class="text-center py-8 text-gray-500">
              <i data-lucide="message-square" class="inline-block w-12 h-12 mb-2 text-gray-400"></i>
              <p>Loading conversations...</p>
            </div>
          </div>
        </div>

        <!-- Chat Area -->
        <div class="flex-1 flex flex-col">
          <div id="chatArea" class="flex-1 flex items-center justify-center text-gray-500">
            <div class="text-center">
              <i data-lucide="messages" class="inline-block w-16 h-16 mb-4 text-gray-400"></i>
              <p class="text-lg">Select a conversation to start messaging</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- New Chat Modal -->
  <div id="newChatModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full m-4">
      <div class="p-6 border-b flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">New Conversation</h2>
        <button onclick="closeNewChatModal()" class="text-gray-500 hover:text-gray-700">
          <i data-lucide="x" class="w-6 h-6"></i>
        </button>
      </div>
      <div class="p-6">
        <input type="text" id="searchUsers" placeholder="Search users..." 
               class="w-full px-4 py-2 border rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <div id="usersList" class="max-h-64 overflow-y-auto space-y-2">
          <div class="text-center py-4 text-gray-500">Type to search users</div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const userId = <?= json_encode($user_id) ?>;
    const userType = <?= json_encode($user_type) ?>;
    let currentConversationId = null;
    let messagePollingInterval = null;

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
      if (typeof lucide !== 'undefined') lucide.replace();
      loadConversations();
      
      // Search conversations
      document.getElementById('searchConversations').addEventListener('input', function(e) {
        const search = e.target.value.toLowerCase();
        document.querySelectorAll('.conversation-item').forEach(item => {
          const text = item.textContent.toLowerCase();
          item.style.display = text.includes(search) ? 'block' : 'none';
        });
      });

      // Search users
      let searchTimeout;
      document.getElementById('searchUsers').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => searchUsers(e.target.value), 300);
      });
    });

    // Load conversations list
    async function loadConversations() {
      try {
        const response = await fetch('chat_api.php?action=get_conversations');
        const data = await response.json();
        
        const container = document.getElementById('conversationsList');
        
        if (data.success && data.conversations.length > 0) {
          container.innerHTML = data.conversations.map(conv => {
            const otherUserName = `${conv.first_name || ''} ${conv.last_name || ''}`.trim();
            const lastMessagePreview = conv.last_message ? 
              (conv.last_message.length > 50 ? conv.last_message.substring(0, 50) + '...' : conv.last_message) : 
              'No messages yet';
            
            return `
              <div class="conversation-item p-4 cursor-pointer border-b" onclick="openConversation(${conv.id})">
                <div class="flex items-start justify-between mb-1">
                  <div class="font-semibold text-gray-800">${escapeHtml(otherUserName || 'Unknown User')}</div>
                  ${conv.unread_count > 0 ? `<span class="bg-blue-600 text-white text-xs rounded-full px-2 py-1">${conv.unread_count}</span>` : ''}
                </div>
                <div class="text-sm text-gray-600 truncate">${escapeHtml(lastMessagePreview)}</div>
                ${conv.last_message_time ? `<div class="text-xs text-gray-400 mt-1">${formatDateTime(conv.last_message_time)}</div>` : ''}
              </div>
            `;
          }).join('');
        } else {
          container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
              <i data-lucide="message-square" class="inline-block w-12 h-12 mb-2 text-gray-400"></i>
              <p>No conversations yet</p>
              <p class="text-sm">Start a new chat to begin</p>
            </div>
          `;
        }
        
        if (typeof lucide !== 'undefined') lucide.replace();
      } catch (error) {
        console.error('Error loading conversations:', error);
      }
    }

    // Open conversation
    async function openConversation(conversationId) {
      currentConversationId = conversationId;
      
      // Update active state
      document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
      });
      event.currentTarget.classList.add('active');
      
      // Load messages
      await loadMessages(conversationId);
      
      // Start polling for new messages
      if (messagePollingInterval) clearInterval(messagePollingInterval);
      messagePollingInterval = setInterval(() => loadMessages(conversationId, true), 3000);
    }

    // Load messages
    async function loadMessages(conversationId, silent = false) {
      try {
        const response = await fetch(`chat_api.php?action=get_messages&conversation_id=${conversationId}`);
        const data = await response.json();
        
        if (data.success) {
          renderChatArea(conversationId, data.messages);
          if (!silent) {
            setTimeout(() => {
              const messagesContainer = document.getElementById('messagesContainer');
              if (messagesContainer) messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 100);
          }
        }
      } catch (error) {
        console.error('Error loading messages:', error);
      }
    }

    // Render chat area
    function renderChatArea(conversationId, messages) {
      const chatArea = document.getElementById('chatArea');
      
      const html = `
        <div class="flex-1 flex flex-col h-full">
          <div id="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3 messages-list">
            ${messages.length === 0 ? 
              '<div class="text-center text-gray-500 py-8">No messages yet. Start the conversation!</div>' :
              messages.map(msg => {
                const isMine = msg.sender_id === userId;
                return `
                  <div class="flex ${isMine ? 'justify-end' : 'justify-start'}">
                    <div class="max-w-xs lg:max-w-md">
                      ${!isMine ? `<div class="text-xs text-gray-600 mb-1">${escapeHtml(msg.first_name || '')} ${escapeHtml(msg.last_name || '')}</div>` : ''}
                      <div class="${isMine ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800'} rounded-lg px-4 py-2">
                        <p class="text-sm">${escapeHtml(msg.message)}</p>
                      </div>
                      <div class="text-xs text-gray-500 mt-1 ${isMine ? 'text-right' : ''}">${formatDateTime(msg.created_at)}</div>
                    </div>
                  </div>
                `;
              }).join('')
            }
          </div>
          
          <div class="p-4 border-t">
            <form onsubmit="sendMessage(event, ${conversationId})" class="flex gap-2">
              <input type="text" id="messageInput" placeholder="Type a message..." required
                     class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
              <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                <i data-lucide="send" class="w-5 h-5"></i>
              </button>
            </form>
          </div>
        </div>
      `;
      
      chatArea.innerHTML = html;
      if (typeof lucide !== 'undefined') lucide.replace();
    }

    // Send message
    async function sendMessage(event, conversationId) {
      event.preventDefault();
      
      const input = document.getElementById('messageInput');
      const message = input.value.trim();
      
      if (!message) return;
      
      // Get receiver_id from conversation
      try {
        const convResponse = await fetch(`chat_api.php?action=get_messages&conversation_id=${conversationId}&limit=1`);
        const convData = await convResponse.json();
        
        if (!convData.success || convData.messages.length === 0) return;
        
        const lastMsg = convData.messages[0];
        const receiver_id = lastMsg.sender_id === userId ? lastMsg.receiver_id : lastMsg.sender_id;
        
        const response = await fetch('chat_api.php?action=send_message', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ receiver_id, message })
        });
        
        const data = await response.json();
        
        if (data.success) {
          input.value = '';
          await loadMessages(conversationId);
          loadConversations(); // Refresh conversations list
        }
      } catch (error) {
        console.error('Error sending message:', error);
        alert('Failed to send message');
      }
    }

    // New chat modal
    function openNewChatModal() {
      document.getElementById('newChatModal').classList.remove('hidden');
      document.getElementById('searchUsers').value = '';
      document.getElementById('usersList').innerHTML = '<div class="text-center py-4 text-gray-500">Type to search users</div>';
      if (typeof lucide !== 'undefined') lucide.replace();
    }

    function closeNewChatModal() {
      document.getElementById('newChatModal').classList.add('hidden');
    }

    // Search users
    async function searchUsers(query) {
      if (!query.trim()) {
        document.getElementById('usersList').innerHTML = '<div class="text-center py-4 text-gray-500">Type to search users</div>';
        return;
      }
      
      try {
        const response = await fetch(`chat_api.php?action=search_users&search=${encodeURIComponent(query)}`);
        const data = await response.json();
        
        const container = document.getElementById('usersList');
        
        if (data.success && data.users.length > 0) {
          container.innerHTML = data.users.map(user => `
            <div class="p-3 hover:bg-gray-100 rounded cursor-pointer" onclick="startNewChat('${user.user_id}')">
              <div class="font-semibold text-gray-800">${escapeHtml(user.first_name || '')} ${escapeHtml(user.last_name || '')}</div>
              <div class="text-sm text-gray-600">${escapeHtml(user.user_id)}</div>
            </div>
          `).join('');
        } else {
          container.innerHTML = '<div class="text-center py-4 text-gray-500">No users found</div>';
        }
      } catch (error) {
        console.error('Error searching users:', error);
      }
    }

    // Start new chat
    async function startNewChat(receiverId) {
      try {
        const response = await fetch('chat_api.php?action=send_message', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ 
            receiver_id: receiverId, 
            message: 'Hello! 👋' 
          })
        });
        
        const data = await response.json();
        
        if (data.success) {
          closeNewChatModal();
          await loadConversations();
          openConversation(data.conversation_id);
        }
      } catch (error) {
        console.error('Error starting chat:', error);
      }
    }

    // Helper functions
    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function formatDateTime(dateStr) {
      const date = new Date(dateStr);
      const now = new Date();
      const diff = now - date;
      
      if (diff < 60000) return 'Just now';
      if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
      if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
      
      return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
      if (messagePollingInterval) clearInterval(messagePollingInterval);
    });
  </script>
</body>
</html>