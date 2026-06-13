(function () {
    'use strict';

    const ChatAgent = {

        config: {},

        widgetConfig: {
            chatbot_name: 'AI Assistant',
            avatar_url: null,
            theme: {
                primary: '#2563eb',
                secondary: '#eff6ff',
                text: '#ffffff'
            }
        },

        state: {
            isOpen: false,
            isSending: false,
            isReady: false
        },

        async init(options) {
            this.config = {
                server: '',
                public_server: '',
                token: '',
                ...options
            };

            if (!this.config.server || !this.config.token) {
                console.error('ChatAgent: server and token are required.');
                return;
            }

            this.loadStyles();

            await this.loadWidgetConfig();

            this.createWidget();

            this.state.isReady = true;
        },

        loadStyles() {
            if (document.getElementById('chat-agent-widget-css')) {
                return;
            }

            const css = document.createElement('link');

            css.id = 'chat-agent-widget-css';
            css.rel = 'stylesheet';
            css.href = this.config.public_server + '/widget.css?v=2.0.0';

            document.head.appendChild(css);
        },

        async loadWidgetConfig() {
            try {
                const response = await fetch(
                    this.config.server + '/api/widget/config',
                    {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-EMBED-TOKEN': this.config.token
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error('Widget config request failed with status ' + response.status);
                }

                const data = await response.json();

                this.widgetConfig = {
                    chatbot_name: data.chatbot_name || 'AI Assistant',
                    avatar_url: data.avatar_url || null,
                    theme: {
                        primary: data.theme?.primary || '#2563eb',
                        secondary: data.theme?.secondary || '#eff6ff',
                        text: data.theme?.text || '#ffffff'
                    }
                };

            } catch (error) {
                console.error('ChatAgent config load failed:', error);

                this.widgetConfig = {
                    chatbot_name: 'AI Assistant',
                    avatar_url: null,
                    theme: {
                        primary: '#2563eb',
                        secondary: '#eff6ff',
                        text: '#ffffff'
                    }
                };
            }
        },
            createWidget() {
            if (document.getElementById('chat-agent-root')) {
                return;
            }

            const root = document.createElement('div');
            root.id = 'chat-agent-root';

            root.innerHTML = `
                <div id="chat-window" aria-live="polite">
                    <div id="chat-header">
                        <div class="chat-header-brand">
                            ${this.getAvatarHtml()}

                            <div class="chat-header-copy">
                                <div id="chat-header-title">
                                    ${this.escapeHtml(this.widgetConfig.chatbot_name)}
                                </div>

                                <div id="chat-header-status">
                                    <span class="chat-status-dot"></span>
                                    Online
                                </div>
                            </div>
                        </div>

                        <button id="chat-close" type="button" aria-label="Close chat">
                            ×
                        </button>
                    </div>

                    <div id="chat-messages"></div>

                    <div id="chat-typing" style="display: none;">
                        <div class="typing-bubble">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>

                    <div id="chat-input-wrapper">
                        <input
                            id="chat-input"
                            type="text"
                            placeholder="Type your message..."
                            autocomplete="off"
                        >

                        <button id="chat-send" type="button" aria-label="Send message">
                            <span class="chat-send-text">Send</span>
                            <span class="chat-send-icon">➜</span>
                        </button>
                    </div>
                </div>

                <button id="chat-agent-button" type="button" aria-label="Open chat">
                    <span id="chat-launcher-icon">💬</span>
                    <span id="chat-launcher-close">×</span>
                </button>
            `;

            document.body.appendChild(root);

            this.applyTheme();
            this.bindEvents();

        },

        getAvatarHtml() {
            if (this.widgetConfig.avatar_url) {
                return `
                    <img
                        id="chat-avatar"
                        src="${this.escapeHtml(this.widgetConfig.avatar_url)}"
                        alt="${this.escapeHtml(this.widgetConfig.chatbot_name)}"
                    >
                `;
            }

            return `
                <div id="chat-avatar-placeholder">
                    🤖
                </div>
            `;
        },

        bindEvents() {
            const launcher = document.getElementById('chat-agent-button');
            const close = document.getElementById('chat-close');
            const send = document.getElementById('chat-send');
            const input = document.getElementById('chat-input');

            launcher.addEventListener('click', () => {
                this.toggleWindow();
            });

            close.addEventListener('click', () => {
                this.closeWindow();
            });

            send.addEventListener('click', async () => {
                await this.handleSend();
            });

            input.addEventListener('keydown', async (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    await this.handleSend();
                }
            });
        },

        applyTheme() {
            const root = document.getElementById('chat-agent-root');

            if (!root) {
                return;
            }

            const theme = this.widgetConfig.theme;

            root.style.setProperty('--chat-primary', theme.primary);
            root.style.setProperty('--chat-secondary', theme.secondary);
            root.style.setProperty('--chat-primary-text', theme.text);
        },

        toggleWindow() {
            if (this.state.isOpen) {
                this.closeWindow();
            } else {
                this.openWindow();
            }
        },

        openWindow() {
            const win = document.getElementById('chat-window');
            const launcher = document.getElementById('chat-agent-button');
            const input = document.getElementById('chat-input');

            win.classList.add('is-open');
            launcher.classList.add('is-open');

            this.state.isOpen = true;

            if (!this.state.historyLoaded) {
                this.loadConversationHistory();
            }

            setTimeout(() => {
                if (input) {
                    input.focus();
                }
            }, 250);
        },

        closeWindow() {
            const win = document.getElementById('chat-window');
            const launcher = document.getElementById('chat-agent-button');

            win.classList.remove('is-open');
            launcher.classList.remove('is-open');

            this.state.isOpen = false;
        },

        async handleSend() {
            if (this.state.isSending) {
                return;
            }

            const input = document.getElementById('chat-input');
            const message = input.value.trim();

            if (!message) {
                return;
            }

            this.appendMessage('visitor', message);

            input.value = '';

            this.setSendingState(true);
            this.showTyping(true);

            try {
                const response = await this.sendMessage(message);

                this.showTyping(false);

                this.appendMessage(
                    'ai',
                    response.reply || 'Sorry, I could not generate a response right now.'
                );

            } catch (error) {
                console.error('ChatAgent message failed:', error);

                this.showTyping(false);

                this.appendMessage(
                    'ai',
                    'Sorry, I had trouble processing that message. Please try again in a moment.'
                );

            } finally {
                this.setSendingState(false);
            }
        },
        state: {
            isOpen: false,
            isSending: false,
            isReady: false,
            historyLoaded: false
        },

        setSendingState(isSending) {
            this.state.isSending = isSending;

            const sendButton = document.getElementById('chat-send');
            const input = document.getElementById('chat-input');

            if (sendButton) {
                sendButton.disabled = isSending;
            }

            if (input) {
                input.disabled = isSending;
            }
        },

        showTyping(show) {
            const typing = document.getElementById('chat-typing');

            if (!typing) {
                return;
            }

            typing.style.display = show ? 'block' : 'none';

            if (show) {
                this.scrollMessagesToBottom();
            }
        },

        appendMessage(sender, text) {
            const messages = document.getElementById('chat-messages');

            if (!messages) {
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'chat-message-row ' + sender;

            const bubble = document.createElement('div');
            bubble.className = 'chat-message-bubble ' + sender;
            bubble.innerText = text;

            wrapper.appendChild(bubble);
            messages.appendChild(wrapper);

            this.scrollMessagesToBottom();
        },

        scrollMessagesToBottom() {
            const messages = document.getElementById('chat-messages');

            if (!messages) {
                return;
            }

            messages.scrollTop = messages.scrollHeight;
        },

        getVisitorId() {
            const storageKey = 'chat_visitor_' + this.hashToken(this.config.token);

            let id = localStorage.getItem(storageKey);

            if (!id) {
                id =
                    'visitor_' +
                    Date.now() +
                    '_' +
                    Math.random().toString(36).substring(2);

                localStorage.setItem(storageKey, id);
            }

            return id;
        },

        async sendMessage(message) {
            const response = await fetch(
                this.config.server + '/api/chat',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-EMBED-TOKEN': this.config.token
                    },
                    body: JSON.stringify({
                        visitor_id: this.getVisitorId(),
                        message: message
                    })
                }
            );

            if (!response.ok) {
                let errorMessage = 'API request failed';

                try {
                    const errorData = await response.json();

                    if (errorData.message) {
                        errorMessage = errorData.message;
                    }
                } catch (e) {
                    // Ignore JSON parse failure
                }

                throw new Error(errorMessage);
            }

            return response.json();
        },

        hashToken(value) {
            let hash = 0;

            if (!value) {
                return 'default';
            }

            for (let i = 0; i < value.length; i++) {
                hash = ((hash << 5) - hash) + value.charCodeAt(i);
                hash |= 0;
            }

            return Math.abs(hash).toString(36);
        },

        escapeHtml(value) {
            if (!value) {
                return '';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },
        async loadConversationHistory() {
            const messages = document.getElementById('chat-messages');

            if (!messages) {
                return;
            }

            messages.innerHTML = '';

            this.appendSystemMessage('Loading conversation...');

            try {
                const response = await this.fetchConversationHistory();

                messages.innerHTML = '';

                if (response.messages && response.messages.length > 0) {
                    response.messages.forEach((item) => {
                        this.appendMessage(
                            item.sender === 'visitor' ? 'visitor' : 'ai',
                            item.message
                        );
                    });
                } else {
                    this.appendWelcomeMessage();
                }

                this.state.historyLoaded = true;

            } catch (error) {
                console.error('ChatAgent history load failed:', error);

                messages.innerHTML = '';

                this.appendWelcomeMessage();

                this.state.historyLoaded = true;
            }
        },
        async fetchConversationHistory() {
            const visitorId = this.getVisitorId();

            const url =
                this.config.server +
                '/api/chat/history?visitor_id=' +
                encodeURIComponent(visitorId) +
                '&limit=50';

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-EMBED-TOKEN': this.config.token
                }
            });

            if (!response.ok) {
                throw new Error('Conversation history request failed with status ' + response.status);
            }

            return response.json();
        },
        appendWelcomeMessage() {
                    this.appendMessage(
                        'ai',
                        'Hello 👋 How can I help you today?'
                    );
                },
                appendSystemMessage(text) {
            const messages = document.getElementById('chat-messages');

            if (!messages) {
                return;
            }

            const div = document.createElement('div');
            div.className = 'chat-system-message';
            div.innerText = text;

            messages.appendChild(div);

            this.scrollMessagesToBottom();
        },
    };

    window.ChatAgent = ChatAgent;

})();