<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chatbot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .chat-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 40px);
            max-height: 700px;
        }
        
        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .chat-header h3 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f9f9f9;
        }
        
        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .message.user {
            justify-content: flex-end;
        }
        
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        
        .message.bot .message-content {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 18px 18px 18px 4px;
        }
        
        .message.user .message-content {
            background: #667eea;
            color: white;
            border-radius: 18px 18px 4px 18px;
        }
        
        .message-buttons {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .message-buttons a {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
            border: 2px solid;
        }
        
        .message-buttons a.btn-primary {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .message-buttons a.btn-primary:hover {
            background: #5568d3;
            border-color: #5568d3;
            transform: translateY(-1px);
        }
        
        .message-buttons a.btn-secondary {
            background: white;
            color: #667eea;
            border-color: #667eea;
        }
        
        .message-buttons a.btn-secondary:hover {
            background: #f5f5f5;
            transform: translateY(-1px);
        }
        
        .chat-input-container {
            padding: 20px;
            border-top: 1px solid #eee;
            background: white;
            border-radius: 0 0 10px 10px;
        }
        
        .chat-input-form {
            display: flex;
            gap: 10px;
        }
        
        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
        }
        
        .chat-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .send-button {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s;
        }
        
        .send-button:hover {
            background: #5568d3;
        }
        
        .send-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h3>{{ config('laravel-chatbot.bot_name', 'ChatBot') }}</h3>
        </div>
        
        <div class="chat-messages" id="chatMessages">
            <div class="message bot">
                <div class="message-content">
                    {{ config('laravel-chatbot.welcome_message', 'Hello! How can I help you today?') }}
                </div>
            </div>
        </div>
        
        <div class="chat-input-container">
            <form class="chat-input-form" id="chatForm">
                <input 
                    type="text" 
                    class="chat-input" 
                    id="messageInput" 
                    placeholder="Type your message..." 
                    autocomplete="off"
                    required
                >
                <button type="submit" class="send-button" id="sendButton">Send</button>
            </form>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');

        chatForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const message = messageInput.value.trim();
            if (!message) return;
            
            // Add user message
            addMessage(message, 'user');
            messageInput.value = '';
            sendButton.disabled = true;
            sendButton.innerHTML = '<span class="loading"></span>';
            
            try {
                const response = await fetch('{{ route("botman.web-chat") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ message: message })
                });
                
                const data = await response.json();
                
                // Add bot response
                addMessage(data.text, 'bot', data.buttons || []);
            } catch (error) {
                console.error('Error:', error);
                addMessage('Sorry, I encountered an error. Please try again.', 'bot');
            } finally {
                sendButton.disabled = false;
                sendButton.innerHTML = 'Send';
                messageInput.focus();
            }
        });

        function addMessage(text, type, buttons = []) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            contentDiv.textContent = text;
            
            messageDiv.appendChild(contentDiv);
            
            // Add buttons if available
            if (buttons.length > 0) {
                const buttonsDiv = document.createElement('div');
                buttonsDiv.className = 'message-buttons';
                
                buttons.forEach(button => {
                    const buttonLink = document.createElement('a');
                    buttonLink.href = button.url;
                    buttonLink.textContent = button.label;
                    buttonLink.className = `btn-${button.style || 'primary'}`;
                    buttonLink.target = button.target || '_self';
                    
                    // Add rel="noopener noreferrer" for security when opening in new tab
                    if (button.target === '_blank') {
                        buttonLink.rel = 'noopener noreferrer';
                    }
                    
                    buttonsDiv.appendChild(buttonLink);
                });
                
                messageDiv.appendChild(buttonsDiv);
            }
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Auto-focus input
        messageInput.focus();
    </script>
</body>
</html>
