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
        
        .message-wrapper {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 18px 18px 18px 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .message.bot .message-content {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 18px 18px 18px 4px;
            padding: 16px;
            line-height: 1.6;
        }
        
        .message.bot .message-wrapper .message-content {
            border: none;
            border-radius: 0;
            padding-bottom: 0;
            margin-bottom: 0;
        }
        
        .message.user .message-content {
            background: #667eea;
            color: white;
            border-radius: 18px 18px 4px 18px;
        }
        
        .message-buttons {
            padding: 16px;
            padding-top: 12px;
            border-top: 1px solid rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: white;
        }
        
        .message-buttons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .message-buttons a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .message-buttons a:hover::before {
            left: 100%;
        }
        
        .message-buttons a.btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .message-buttons a.btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #653882 100%);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
            transform: translateY(-2px);
        }
        
        .message-buttons a.btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }
        
        .message-buttons a.btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }
        
        .message-buttons a.btn-secondary:hover {
            background: #f8f9ff;
            border-color: #5568d3;
            color: #5568d3;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            transform: translateY(-2px);
        }
        
        .message-buttons a.btn-secondary:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(102, 126, 234, 0.2);
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
            
            // Create message wrapper for bot messages with buttons
            if (type === 'bot' && buttons.length > 0) {
                const messageWrapper = document.createElement('div');
                messageWrapper.className = 'message-wrapper';
                
                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                contentDiv.textContent = text;
                messageWrapper.appendChild(contentDiv);
                
                // Add buttons container
                const buttonsDiv = document.createElement('div');
                buttonsDiv.className = 'message-buttons';
                
                buttons.forEach(button => {
                    const buttonLink = document.createElement('a');
                    buttonLink.href = button.url;
                    buttonLink.className = `btn-${button.style || 'primary'}`;
                    buttonLink.target = button.target || '_self';
                    
                    // Add rel="noopener noreferrer" for security when opening in new tab
                    if (button.target === '_blank') {
                        buttonLink.rel = 'noopener noreferrer';
                    }
                    
                    // Add icon based on URL or label
                    let iconSvg = '';
                    const label = button.label.toLowerCase();
                    const iconStyle = 'width: 20px; height: 20px; margin-right: 8px; display: inline-block; vertical-align: middle;';
                    
                    if (label.includes('join') || label.includes('call') || label.includes('meeting')) {
                        iconSvg = '<svg style="' + iconStyle + '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>';
                    } else if (label.includes('view') || label.includes('see') || label.includes('show')) {
                        iconSvg = '<svg style="' + iconStyle + '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
                    } else if (label.includes('contact') || label.includes('support') || label.includes('help')) {
                        iconSvg = '<svg style="' + iconStyle + '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>';
                    } else if (label.includes('buy') || label.includes('purchase') || label.includes('order')) {
                        iconSvg = '<svg style="' + iconStyle + '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>';
                    } else {
                        iconSvg = '<svg style="' + iconStyle + '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>';
                    }
                    
                    buttonLink.innerHTML = iconSvg + '<span style="display: inline-block; vertical-align: middle;">' + button.label + '</span>';
                    buttonsDiv.appendChild(buttonLink);
                });
                
                messageWrapper.appendChild(buttonsDiv);
                messageDiv.appendChild(messageWrapper);
            } else {
                // Regular message without buttons
                const contentDiv = document.createElement('div');
                contentDiv.className = 'message-content';
                contentDiv.textContent = text;
                messageDiv.appendChild(contentDiv);
            }
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Auto-focus input
        messageInput.focus();
    </script>
</body>
</html>
