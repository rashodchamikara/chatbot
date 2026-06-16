(function () {
    'use strict';

    const ChatAgent = {
        config: {},

        widgetConfig: {
            chatbot_name: 'AI Assistant',
            avatar_url: null,
            live_agent_available: false,
            realtime: null,
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
            conversationMode: 'ai',
            pusherReady: false,
            renderedMessageIds: new Set()
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
                console.error(
                    'ChatAgent: server and token are required.'
                );

                return;
            }

            this.loadStyles();

            await this.loadWidgetConfig();

            this.createWidget();

            await this.initRealtime();

            this.state.isReady = true;

            console.log('ChatAgent initialized.', {
                realtimeEnabled:
                    !!this.widgetConfig.realtime?.enabled,

                liveAgentAvailable:
                    this.state.liveAgentAvailable
            });
        },

        loadStyles() {
            if (
                document.getElementById(
                    'chat-agent-widget-css'
                )
            ) {
                return;
            }

            const css =
                document.createElement('link');

            css.id = 'chat-agent-widget-css';
            css.rel = 'stylesheet';

            css.href =
                this.config.public_server +
                '/widget.css?v=2.1.0';

            document.head.appendChild(css);
        },

        async loadWidgetConfig() {
            try {
                const response = await fetch(
                    this.config.server +
                        '/api/widget/config',
                    {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                            'X-EMBED-TOKEN':
                                this.config.token
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error(
                        'Widget config request failed with status ' +
                            response.status
                    );
                }

                const data =
                    await response.json();

                this.widgetConfig = {
                    chatbot_name:
                        data.chatbot_name ||
                        'AI Assistant',

                    avatar_url:
                        data.avatar_url || null,

                    live_agent_available:
                        !!data.live_agent_available,

                    realtime:
                        data.realtime || null,

                    theme: {
                        primary:
                            data.theme?.primary ||
                            '#2563eb',

                        secondary:
                            data.theme?.secondary ||
                            '#eff6ff',

                        text:
                            data.theme?.text ||
                            '#ffffff'
                    }
                };

                this.state.liveAgentAvailable =
                    !!data.live_agent_available;

                console.log(
                    'ChatAgent widget configuration loaded:',
                    this.widgetConfig
                );
            } catch (error) {
                console.error(
                    'ChatAgent config load failed:',
                    error
                );

                this.widgetConfig = {
                    chatbot_name: 'AI Assistant',
                    avatar_url: null,
                    live_agent_available: false,
                    realtime: null,
                    theme: {
                        primary: '#2563eb',
                        secondary: '#eff6ff',
                        text: '#ffffff'
                    }
                };

                this.state.liveAgentAvailable =
                    false;
            }
        },

        createWidget() {
            if (
                document.getElementById(
                    'chat-agent-root'
                )
            ) {
                return;
            }

            const root =
                document.createElement('div');

            root.id = 'chat-agent-root';

            root.innerHTML = `
                <div id="chat-window" aria-live="polite">
                    <div id="chat-header">
                        <div class="chat-header-brand">
                            ${this.getAvatarHtml()}

                            <div class="chat-header-copy">
                                <div id="chat-header-title">
                                    ${this.escapeHtml(
                                        this.widgetConfig
                                            .chatbot_name
                                    )}
                                </div>

                                <div id="chat-header-status">
                                    <span class="chat-status-dot"></span>
                                    Online
                                </div>
                            </div>
                        </div>

                        <button
                            id="chat-close"
                            type="button"
                            aria-label="Close chat"
                        >
                            ×
                        </button>
                    </div>

                    <div id="chat-messages"></div>

                    <div
                        id="chat-typing"
                        style="display: none;"
                    >
                        <div class="typing-bubble">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>

                    <div
                        id="chat-live-agent-bar"
                        style="display: none;"
                    >
                        <button
                            id="chat-live-agent-button"
                            type="button"
                        >
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

                        <button
                            id="chat-send"
                            type="button"
                            aria-label="Send message"
                        >
                            <span class="chat-send-text">
                                Send
                            </span>

                            <span class="chat-send-icon">
                                ➜
                            </span>
                        </button>
                    </div>
                </div>

                <button
                    id="chat-agent-button"
                    type="button"
                    aria-label="Open chat"
                >
                    <span id="chat-launcher-icon">
                        💬
                    </span>

                    <span id="chat-launcher-close">
                        ×
                    </span>
                </button>
            `;

            document.body.appendChild(root);

            this.applyTheme();
            this.bindEvents();
            this.updateLiveAgentBar();
        },

        getAvatarHtml() {
            if (this.widgetConfig.avatar_url) {
                return `
                    <img
                        id="chat-avatar"
                        src="${this.escapeHtml(
                            this.widgetConfig.avatar_url
                        )}"
                        alt="${this.escapeHtml(
                            this.widgetConfig
                                .chatbot_name
                        )}"
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
            const launcher =
                document.getElementById(
                    'chat-agent-button'
                );

            const close =
                document.getElementById(
                    'chat-close'
                );

            const send =
                document.getElementById(
                    'chat-send'
                );

            const input =
                document.getElementById(
                    'chat-input'
                );

            const liveButton =
                document.getElementById(
                    'chat-live-agent-button'
                );

            launcher?.addEventListener(
                'click',
                () => {
                    this.toggleWindow();
                }
            );

            close?.addEventListener(
                'click',
                () => {
                    this.closeWindow();
                }
            );

            send?.addEventListener(
                'click',
                async () => {
                    await this.handleSend();
                }
            );

            input?.addEventListener(
                'keydown',
                async event => {
                    if (
                        event.key === 'Enter' &&
                        !event.shiftKey
                    ) {
                        event.preventDefault();

                        await this.handleSend();
                    }
                }
            );

            liveButton?.addEventListener(
                'click',
                async () => {
                    await this.requestLiveAgent();
                }
            );
        },

        applyTheme() {
            const root =
                document.getElementById(
                    'chat-agent-root'
                );

            if (!root) {
                return;
            }

            const theme =
                this.widgetConfig.theme;

            root.style.setProperty(
                '--chat-primary',
                theme.primary
            );

            root.style.setProperty(
                '--chat-secondary',
                theme.secondary
            );

            root.style.setProperty(
                '--chat-primary-text',
                theme.text
            );
        },

        toggleWindow() {
            if (this.state.isOpen) {
                this.closeWindow();
            } else {
                this.openWindow();
            }
        },

        openWindow() {
            const win =
                document.getElementById(
                    'chat-window'
                );

            const launcher =
                document.getElementById(
                    'chat-agent-button'
                );

            const input =
                document.getElementById(
                    'chat-input'
                );

            win?.classList.add('is-open');
            launcher?.classList.add('is-open');

            this.state.isOpen = true;

            if (!this.state.historyLoaded) {
                this.loadConversationHistory();
            }

            setTimeout(() => {
                input?.focus();
            }, 250);
        },

        closeWindow() {
            const win =
                document.getElementById(
                    'chat-window'
                );

            const launcher =
                document.getElementById(
                    'chat-agent-button'
                );

            win?.classList.remove('is-open');
            launcher?.classList.remove('is-open');

            this.state.isOpen = false;
        },

        async handleSend() {
            if (this.state.isSending) {
                return;
            }

            const input =
                document.getElementById(
                    'chat-input'
                );

            if (!input) {
                return;
            }

            const message =
                input.value.trim();

            if (!message) {
                return;
            }

            this.appendMessage(
                'visitor',
                message
            );

            input.value = '';

            this.setSendingState(true);

            if (!this.state.liveMode) {
                this.showTyping(true);
            }

            try {
                const response =
                    await this.sendMessage(message);

                if (
                    response.conversation_channel
                ) {
                    this.subscribeConversationChannel(
                        response.conversation_channel
                    );
                }

                if (response.mode) {
                    this.setConversationMode(
                        response.mode
                    );
                }

                if (
                    response.mode === 'live' ||
                    response.mode ===
                        'live_waiting'
                ) {
                    /*
                     * In live mode the visitor message
                     * is saved and sent to the agent.
                     * No AI answer is expected.
                     */
                    if (response.reply) {
                        this.appendMessage(
                            'ai',
                            response.reply
                        );
                    }

                    return;
                }

                if (response.reply) {
                    this.appendMessage(
                        'ai',
                        response.reply
                    );
                }
            } catch (error) {
                console.error(
                    'ChatAgent message failed:',
                    error
                );

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
            this.state.isSending =
                isSending;

            const sendButton =
                document.getElementById(
                    'chat-send'
                );

            const input =
                document.getElementById(
                    'chat-input'
                );

            if (sendButton) {
                sendButton.disabled =
                    isSending;
            }

            if (input) {
                input.disabled =
                    isSending;
            }
        },

        showTyping(show) {
            const typing =
                document.getElementById(
                    'chat-typing'
                );

            if (!typing) {
                return;
            }

            typing.style.display =
                show ? 'block' : 'none';

            if (show) {
                this.scrollMessagesToBottom();
            }
        },

        appendMessage(
            sender,
            text,
            messageId = null
        ) {
            const messages =
                document.getElementById(
                    'chat-messages'
                );

            if (!messages) {
                return;
            }

            if (
                messageId &&
                this.messageExists(messageId)
            ) {
                return;
            }

            const normalizedSender =
                sender === 'visitor'
                    ? 'visitor'
                    : 'ai';

            const wrapper =
                document.createElement('div');

            wrapper.className =
                'chat-message-row ' +
                normalizedSender;

            if (messageId) {
                wrapper.dataset.messageId =
                    String(messageId);
            }

            const bubble =
                document.createElement('div');

            bubble.className =
                'chat-message-bubble ' +
                normalizedSender;

            bubble.innerText =
                text || '';

            wrapper.appendChild(bubble);
            messages.appendChild(wrapper);

            this.markMessageRendered(
                messageId
            );

            this.scrollMessagesToBottom();
        },

        appendSystemMessage(
            text,
            messageId = null
        ) {
            const messages =
                document.getElementById(
                    'chat-messages'
                );

            if (!messages) {
                return;
            }

            if (
                messageId &&
                this.messageExists(messageId)
            ) {
                return;
            }

            const div =
                document.createElement('div');

            div.className =
                'chat-system-message';

            div.innerText =
                text || '';

            if (messageId) {
                div.dataset.messageId =
                    String(messageId);
            }

            messages.appendChild(div);

            this.markMessageRendered(
                messageId
            );

            this.scrollMessagesToBottom();
        },

        appendWelcomeMessage() {
            this.appendMessage(
                'ai',
                'Hello 👋 How can I help you today?'
            );
        },

        messageExists(messageId) {
            if (!messageId) {
                return false;
            }

            return this.state
                .renderedMessageIds
                .has(String(messageId));
        },

        markMessageRendered(messageId) {
            if (!messageId) {
                return;
            }

            this.state
                .renderedMessageIds
                .add(String(messageId));
        },

        resetRenderedMessageIds() {
            this.state.renderedMessageIds =
                new Set();
        },

        scrollMessagesToBottom() {
            const messages =
                document.getElementById(
                    'chat-messages'
                );

            if (!messages) {
                return;
            }

            messages.scrollTop =
                messages.scrollHeight;
        },

        getVisitorId() {
            const storageKey =
                'chat_visitor_' +
                this.hashToken(
                    this.config.token
                );

            let id =
                localStorage.getItem(
                    storageKey
                );

            if (!id) {
                id =
                    'visitor_' +
                    Date.now() +
                    '_' +
                    Math.random()
                        .toString(36)
                        .substring(2);

                localStorage.setItem(
                    storageKey,
                    id
                );
            }

            return id;
        },

        async sendMessage(message) {
            const response = await fetch(
                this.config.server +
                    '/api/chat',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type':
                            'application/json',

                        Accept:
                            'application/json',

                        'X-EMBED-TOKEN':
                            this.config.token
                    },

                    body: JSON.stringify({
                        visitor_id:
                            this.getVisitorId(),

                        message: message
                    })
                }
            );

            if (!response.ok) {
                let errorMessage =
                    'API request failed';

                try {
                    const errorData =
                        await response.json();

                    if (errorData.message) {
                        errorMessage =
                            errorData.message;
                    }
                } catch (error) {
                    console.warn(
                        'ChatAgent: error response was not JSON.',
                        error
                    );
                }

                throw new Error(
                    errorMessage
                );
            }

            return response.json();
        },

        hashToken(value) {
            let hash = 0;

            if (!value) {
                return 'default';
            }

            for (
                let i = 0;
                i < value.length;
                i++
            ) {
                hash =
                    ((hash << 5) - hash) +
                    value.charCodeAt(i);

                hash |= 0;
            }

            return Math.abs(hash)
                .toString(36);
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
            const messages =
                document.getElementById(
                    'chat-messages'
                );

            if (!messages) {
                return;
            }

            messages.innerHTML = '';

            this.resetRenderedMessageIds();

            this.appendSystemMessage(
                'Loading conversation...'
            );

            try {
                const response =
                    await this.fetchConversationHistory();

                messages.innerHTML = '';

                this.resetRenderedMessageIds();

                if (
                    response.conversation_channel
                ) {
                    this.subscribeConversationChannel(
                        response.conversation_channel
                    );
                }

                if (response.mode) {
                    this.setConversationMode(
                        response.mode
                    );
                } else {
                    this.setConversationMode(
                        'ai'
                    );
                }

                if (
                    Array.isArray(
                        response.messages
                    ) &&
                    response.messages.length > 0
                ) {
                    response.messages.forEach(
                        item => {
                            if (
                                item.is_system ||
                                item.sender ===
                                    'system'
                            ) {
                                this.appendSystemMessage(
                                    item.message,
                                    item.id
                                );

                                return;
                            }

                            this.appendMessage(
                                item.sender ===
                                    'visitor'
                                    ? 'visitor'
                                    : 'ai',

                                item.message,

                                item.id
                            );
                        }
                    );
                } else {
                    this.appendWelcomeMessage();
                }

                this.state.historyLoaded =
                    true;
            } catch (error) {
                console.error(
                    'ChatAgent history load failed:',
                    error
                );

                messages.innerHTML = '';

                this.resetRenderedMessageIds();

                this.appendWelcomeMessage();

                this.state.historyLoaded =
                    true;
            }
        },

        async fetchConversationHistory() {
            const visitorId =
                this.getVisitorId();

            const url =
                this.config.server +
                '/api/chat/history?visitor_id=' +
                encodeURIComponent(
                    visitorId
                ) +
                '&limit=50';

            const response =
                await fetch(url, {
                    method: 'GET',
                    headers: {
                        Accept:
                            'application/json',

                        'X-EMBED-TOKEN':
                            this.config.token
                    }
                });

            if (!response.ok) {
                throw new Error(
                    'Conversation history request failed with status ' +
                        response.status
                );
            }

            return response.json();
        },

        async loadPusherClient() {
            if (window.Pusher) {
                return;
            }

            await new Promise(
                (resolve, reject) => {
                    const script =
                        document.createElement(
                            'script'
                        );

                    script.src =
                        'https://js.pusher.com/8.4.0/pusher.min.js';

                    script.async = true;
                    script.onload = resolve;
                    script.onerror = reject;

                    document.head.appendChild(
                        script
                    );
                }
            );
        },

        async initRealtime() {
            if (
                !this.widgetConfig.realtime ||
                !this.widgetConfig.realtime
                    .enabled
            ) {
                console.warn(
                    'ChatAgent: realtime configuration is disabled.'
                );

                return;
            }

            try {
                await this.loadPusherClient();

                const realtime =
                    this.widgetConfig.realtime;

                this.pusher =
                    new window.Pusher(
                        realtime.key,
                        {
                            wsHost:
                                realtime.host,

                            wsPort:
                                realtime.port,

                            wssPort:
                                realtime.port,

                            forceTLS:
                                realtime.scheme ===
                                'https',

                            enabledTransports: [
                                'ws',
                                'wss'
                            ],

                            disableStats: true,

                            cluster:
                                realtime.cluster ||
                                'mt1'
                        }
                    );

                this.pusher.connection.bind(
                    'connecting',
                    () => {
                        console.log(
                            'ChatAgent: realtime connecting...'
                        );
                    }
                );

                this.pusher.connection.bind(
                    'connected',
                    () => {
                        console.log(
                            'ChatAgent: realtime connected.'
                        );

                        this.state.pusherReady =
                            true;
                    }
                );

                this.pusher.connection.bind(
                    'disconnected',
                    () => {
                        console.warn(
                            'ChatAgent: realtime disconnected.'
                        );

                        this.state.pusherReady =
                            false;
                    }
                );

                this.pusher.connection.bind(
                    'error',
                    error => {
                        console.error(
                            'ChatAgent: realtime connection error:',
                            error
                        );
                    }
                );

                this.subscribeWebsiteChannel();
            } catch (error) {
                console.error(
                    'Realtime initialization failed:',
                    error
                );
            }
        },

        subscribeWebsiteChannel() {
            if (
                !this.pusher ||
                !this.widgetConfig.realtime
                    ?.website_channel
            ) {
                console.warn(
                    'ChatAgent: website channel is not available.'
                );

                return;
            }

            const channelName =
                this.widgetConfig.realtime
                    .website_channel;

            this.websiteChannel =
                this.pusher.subscribe(
                    channelName
                );

            this.websiteChannel.bind(
                'pusher:subscription_succeeded',
                () => {
                    console.log(
                        'ChatAgent: website channel subscribed:',
                        channelName
                    );
                }
            );

            this.websiteChannel.bind(
                'pusher:subscription_error',
                error => {
                    console.error(
                        'ChatAgent: website channel subscription failed:',
                        error
                    );
                }
            );

            this.websiteChannel.bind(
                'agent.status.changed',
                event => {
                    console.log(
                        'ChatAgent: agent status event received:',
                        event
                    );

                    this.state
                        .liveAgentAvailable =
                        !!event.available;

                    this.updateLiveAgentBar();
                }
            );
        },

        subscribeConversationChannel(
            channelName
        ) {
            if (
                !this.pusher ||
                !channelName
            ) {
                console.warn(
                    'ChatAgent: cannot subscribe to conversation channel.',
                    {
                        pusherReady:
                            !!this.pusher,

                        channelName:
                            channelName
                    }
                );

                return;
            }

            if (
                this.conversationChannelName ===
                channelName
            ) {
                console.log(
                    'ChatAgent: already subscribed to conversation channel:',
                    channelName
                );

                return;
            }

            if (
                this.conversationChannelName
            ) {
                this.pusher.unsubscribe(
                    this.conversationChannelName
                );
            }

            this.conversationChannelName =
                channelName;

            this.conversationChannel =
                this.pusher.subscribe(
                    channelName
                );

            this.conversationChannel.bind(
                'pusher:subscription_succeeded',
                () => {
                    console.log(
                        'ChatAgent: conversation channel subscribed:',
                        channelName
                    );
                }
            );

            this.conversationChannel.bind(
                'pusher:subscription_error',
                error => {
                    console.error(
                        'ChatAgent: conversation channel subscription failed:',
                        error
                    );
                }
            );

            this.conversationChannel.bind(
                'conversation.message.created',
                event => {
                    console.log(
                        'ChatAgent: conversation.message.created event received:',
                        event
                    );

                    if (!event) {
                        return;
                    }

                    /*
                     * Visitor messages are displayed
                     * immediately when sent.
                     * Ignore their broadcast duplicate.
                     */
                    if (
                        event.sender ===
                        'visitor'
                    ) {
                        return;
                    }

                    if (
                        event.id &&
                        this.messageExists(
                            event.id
                        )
                    ) {
                        return;
                    }

                    if (
                        event.is_system ||
                        event.sender ===
                            'system'
                    ) {
                        this.appendSystemMessage(
                            event.message || '',
                            event.id
                        );

                        return;
                    }

                    /*
                     * Agent and AI messages display
                     * on the assistant side.
                     */
                    this.appendMessage(
                        'ai',
                        event.message || '',
                        event.id
                    );
                }
            );

            this.conversationChannel.bind(
                'conversation.mode.changed',
                event => {
                    console.log(
                        'ChatAgent: conversation.mode.changed event received:',
                        event
                    );

                    if (!event?.mode) {
                        return;
                    }

                    this.setConversationMode(
                        event.mode
                    );
                }
            );
        },

        setConversationMode(mode) {
            this.state.conversationMode =
                mode || 'ai';

            this.state.liveMode = [
                'live_waiting',
                'live'
            ].includes(
                this.state.conversationMode
            );

            this.updateLiveAgentBar();
            this.updateHeaderStatus();
        },

        updateHeaderStatus() {
            const status =
                document.getElementById(
                    'chat-header-status'
                );

            if (!status) {
                return;
            }

            let statusText = 'Online';

            if (
                this.state.conversationMode ===
                'live_waiting'
            ) {
                statusText =
                    'Waiting for agent';
            } else if (
                this.state.conversationMode ===
                'live'
            ) {
                statusText =
                    'Live agent';
            }

            status.innerHTML = `
                <span class="chat-status-dot"></span>
                ${this.escapeHtml(statusText)}
            `;
        },

        updateLiveAgentBar() {
            const bar =
                document.getElementById(
                    'chat-live-agent-bar'
                );

            if (!bar) {
                return;
            }

            const shouldShow =
                this.state
                    .liveAgentAvailable &&
                !this.state.liveMode;

            bar.style.display =
                shouldShow
                    ? 'block'
                    : 'none';
        },

        async requestLiveAgent() {
            const button =
                document.getElementById(
                    'chat-live-agent-button'
                );

            if (button) {
                button.disabled = true;
                button.textContent =
                    'Connecting...';
            }

            try {
                const response = await fetch(
                    this.config.server +
                        '/api/live/request',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/json',

                            Accept:
                                'application/json',

                            'X-EMBED-TOKEN':
                                this.config.token
                        },

                        body: JSON.stringify({
                            visitor_id:
                                this.getVisitorId()
                        })
                    }
                );

                const data =
                    await response.json();

                if (!response.ok) {
                    this.appendMessage(
                        'ai',
                        data.message ||
                            'No live agent is currently available.'
                    );

                    return;
                }

                this.setConversationMode(
                    data.mode ||
                        'live_waiting'
                );

                if (
                    data.conversation_channel
                ) {
                    this.subscribeConversationChannel(
                        data.conversation_channel
                    );
                }

                /*
                 * The backend also creates and broadcasts
                 * a system message. Only display this
                 * response message when provided and when
                 * it is not expected to duplicate the
                 * broadcasted system message.
                 */
                if (data.message) {
                    this.appendSystemMessage(
                        data.message
                    );
                }
            } catch (error) {
                console.error(
                    'Live agent request failed:',
                    error
                );

                this.appendMessage(
                    'ai',
                    'Sorry, I could not connect you to a live agent right now.'
                );
            } finally {
                if (button) {
                    button.disabled = false;
                    button.textContent =
                        'Talk to a live agent';
                }
            }
        }
    };

    window.ChatAgent = ChatAgent;
})();