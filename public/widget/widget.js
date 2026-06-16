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
            isReady: false,
            historyLoaded: false,
            liveAgentAvailable: false,
            liveMode: false,
            pusherReady: false
        },
        pusher: null,
        websiteChannel: null,
        conversationChannel: null,
        conversationChannelName: null,
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

            await this.initRealtime();

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
                    live_agent_available: !!data.live_agent_available,
                    realtime: data.realtime || null,
                    theme: {
                        primary: data.theme?.primary || '#2563eb',
                        secondary: data.theme?.secondary || '#eff6ff',
                        text: data.theme?.text || '#ffffff'
                    }
                };
                this.state.liveAgentAvailable = !!data.live_agent_available;

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
                    <div id="chat-live-agent-bar" style="display: none;">
                        <button id="chat-live-agent-button" type="button">
                            Talk to a live agent
                        </button>
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
            const liveButton = document.getElementById('chat-live-agent-button');

            if (liveButton) {
                liveButton.addEventListener('click', async () => {
                    await this.requestLiveAgent();
                });
            }
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

                if (response.conversation_channel) {
                    this.subscribeConversationChannel(response.conversation_channel);
                }

                if (response.mode === 'live' || response.mode === 'live_waiting') {
                    this.state.liveMode = true;
                    this.updateLiveAgentBar();

                    if (response.reply) {
                        this.appendMessage('ai', response.reply);
                    }

                    return;
                }

                if (response.reply) {
                    this.appendMessage(
                        'ai',
                        response.reply || 'Sorry, I could not generate a response right now.'
                    );
                }

            } catch (error) {
                console.error('ChatAgent message failed:', error);

                this.appendMessage(
                    'ai',
                    'Sorry, I had trouble processing that message. Please try again in a moment.'
                );

            } finally {
                this.showTyping(false);
                this.setSendingState(false);
            }
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

                if (response.conversation_channel) {
                    this.subscribeConversationChannel(response.conversation_channel);
                }

                if (response.mode) {
                    this.state.liveMode = ['live_waiting', 'live'].includes(response.mode);
                }

                this.updateLiveAgentBar();

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
        async loadPusherClient() {
            if (window.Pusher) {
                return;
            }

            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://js.pusher.com/8.4.0/pusher.min.js';
                script.async = true;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        },

        async initRealtime() {
            if (!this.widgetConfig.realtime || !this.widgetConfig.realtime.enabled) {
                return;
            }

            try {
                await this.loadPusherClient();

                const realtime = this.widgetConfig.realtime;

                this.pusher = new Pusher(realtime.key, {
                    wsHost: realtime.host,
                    wsPort: realtime.port,
                    wssPort: realtime.port,
                    forceTLS: realtime.scheme === 'https',
                    enabledTransports: ['ws', 'wss'],
                    disableStats: true,
                    cluster: 'mt1'
                });

                this.state.pusherReady = true;

                this.subscribeWebsiteChannel();

            } catch (error) {
                console.error('Realtime initialization failed:', error);
            }
        },

        subscribeWebsiteChannel() {
            if (!this.pusher || !this.widgetConfig.realtime?.website_channel) {
                return;
            }

            this.websiteChannel = this.pusher.subscribe(this.widgetConfig.realtime.website_channel);

            this.websiteChannel.bind('agent.status.changed', (event) => {
                this.state.liveAgentAvailable = !!event.available;
                this.updateLiveAgentBar();
            });
        },

        subscribeConversationChannel(channelName) {
            if (!this.pusher || !channelName) {
                return;
            }

            if (this.conversationChannelName === channelName) {
                return;
            }

            if (this.conversationChannelName) {
                this.pusher.unsubscribe(this.conversationChannelName);
            }

            this.conversationChannelName = channelName;

            this.conversationChannel = this.pusher.subscribe(channelName);

            this.conversationChannel.bind(
                'conversation.message.created',
                function (event) {
                    if (event.sender === 'visitor') {
                        return;
                    }

                    if (messageExists(event.id)) {
                        return;
                    }

                    appendMessage({
                        id: event.id,
                        sender: event.sender,
                        message: event.message,
                        is_system: event.is_system,
                        agent_name: event.agent_name,
                        created_at: event.created_at
                    });
                }
            );

            this.conversationChannel.bind(
                'conversation.mode.changed',
                function (event) {
                    state.mode = event.mode;

                    if (event.mode === 'live_waiting') {
                        showSystemStatus(
                            'Waiting for a live agent...'
                        );
                    }

                    if (event.mode === 'live') {
                        showSystemStatus(
                            event.assigned_agent_name
                                ? `${event.assigned_agent_name} joined the chat.`
                                : 'A live agent joined the chat.'
                        );
                    }

                    if (event.mode === 'ai') {
                        showSystemStatus(
                            'Live chat ended. The AI assistant is active again.'
                        );
                    }
                }
            );
        },
        updateLiveAgentBar() {
            const bar = document.getElementById('chat-live-agent-bar');

            if (!bar) {
                return;
            }

            if (this.state.liveAgentAvailable && !this.state.liveMode) {
                bar.style.display = 'block';
            } else {
                bar.style.display = 'none';
            }
        },

        async requestLiveAgent() {
            try {
                const response = await fetch(this.config.server + '/api/live/request', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-EMBED-TOKEN': this.config.token
                    },
                    body: JSON.stringify({
                        visitor_id: this.getVisitorId()
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    this.appendMessage(
                        'ai',
                        data.message || 'No live agent is currently available.'
                    );
                    return;
                }

                this.state.liveMode = true;

                if (data.conversation_channel) {
                    this.subscribeConversationChannel(data.conversation_channel);
                }

                this.updateLiveAgentBar();

                this.appendMessage(
                    'ai',
                    data.message || 'A live agent has been notified.'
                );

            } catch (error) {
                console.error('Live agent request failed:', error);

                this.appendMessage(
                    'ai',
                    'Sorry, I could not connect you to a live agent right now.'
                );
            }
        },

    };

    window.ChatAgent = ChatAgent;

})();