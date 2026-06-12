(function () {

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

        async init(options) {

            this.config = options;

            this.loadStyles();

            await this.loadWidgetConfig();

            this.createWidget();
        },

        loadStyles() {

            const css = document.createElement('link');

            css.rel = 'stylesheet';

            css.href = this.config.public_server + '/widget.css';

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
                    throw new Error('Widget config request failed');
                }

                const data = await response.json();

                this.widgetConfig = {
                    chatbot_name:
                        data.chatbot_name || 'AI Assistant',

                    avatar_url:
                        data.avatar_url || null,

                    theme: {
                        primary:
                            data.theme?.primary || '#2563eb',

                        secondary:
                            data.theme?.secondary || '#eff6ff',

                        text:
                            data.theme?.text || '#ffffff'
                    }
                };

            } catch (error) {

                console.error('Chatbot config load failed:', error);

                // Continue with default config
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

            const btn = document.createElement('div');

            btn.id = 'chat-agent-button';

            btn.innerHTML = '💬';

            btn.style.backgroundColor =
                this.widgetConfig.theme.primary;

            btn.style.color =
                this.widgetConfig.theme.text;

            document.body.appendChild(btn);

            this.createWindow();

            btn.addEventListener('click', () => {

                const win =
                    document.getElementById('chat-window');

                win.style.display =
                    win.style.display === 'block'
                        ? 'none'
                        : 'block';
            });
        },

        createWindow() {

            const avatarHtml = this.widgetConfig.avatar_url
                ? `
                    <img
                        id="chat-avatar"
                        src="${this.escapeHtml(this.widgetConfig.avatar_url)}"
                        alt="${this.escapeHtml(this.widgetConfig.chatbot_name)}"
                    >
                `
                : `
                    <div id="chat-avatar-placeholder">
                        🤖
                    </div>
                `;

            const html = `

                <div id="chat-window">

                    <div id="chat-header">
                        <div id="chat-header-left">
                            ${avatarHtml}

                            <div id="chat-header-title">
                                ${this.escapeHtml(this.widgetConfig.chatbot_name)}
                            </div>
                        </div>
                    </div>

                    <div id="chat-messages"></div>

                    <div id="chat-input-wrapper">

                        <input
                            id="chat-input"
                            type="text"
                            placeholder="Type your message..."
                        >

                        <button id="chat-send">
                            Send
                        </button>

                    </div>

                </div>

            `;

            document.body.insertAdjacentHTML(
                'beforeend',
                html
            );

            this.applyTheme();

            const self = this;

            document
                .getElementById('chat-send')
                .addEventListener('click', async () => {

                    await self.handleSend();
                });

            document
                .getElementById('chat-input')
                .addEventListener('keypress', async (e) => {

                    if (e.key === 'Enter') {

                        await self.handleSend();
                    }
                });

            this.appendMessage(
                'ai',
                'Hello 👋 How can I help you today?'
            );
        },

        applyTheme() {

            const theme = this.widgetConfig.theme;

            const header =
                document.getElementById('chat-header');

            const sendButton =
                document.getElementById('chat-send');

            const button =
                document.getElementById('chat-agent-button');

            if (header) {
                header.style.backgroundColor = theme.primary;
                header.style.color = theme.text;
            }

            if (sendButton) {
                sendButton.style.backgroundColor = theme.primary;
                sendButton.style.color = theme.text;
            }

            if (button) {
                button.style.backgroundColor = theme.primary;
                button.style.color = theme.text;
            }
        },

        async handleSend() {

            const input =
                document.getElementById('chat-input');

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

            try {

                const response =
                    await this.sendMessage(message);

                this.appendMessage(
                    'ai',
                    response.reply || 'Sorry, I could not generate a response.'
                );

            } catch (error) {

                console.error(error);

                this.appendMessage(
                    'ai',
                    'Sorry, something went wrong.'
                );

            } finally {

                this.setSendingState(false);
            }
        },

        setSendingState(isSending) {

            const sendButton =
                document.getElementById('chat-send');

            const input =
                document.getElementById('chat-input');

            if (sendButton) {
                sendButton.disabled = isSending;
                sendButton.innerText = isSending ? '...' : 'Send';
            }

            if (input) {
                input.disabled = isSending;
            }
        },

        appendMessage(sender, text) {

            const messages =
                document.getElementById('chat-messages');

            const div =
                document.createElement('div');

            div.className =
                'chat-message ' + sender;

            div.innerText = text;

            messages.appendChild(div);

            messages.scrollTop =
                messages.scrollHeight;
        },

        getVisitorId() {

            let id =
                localStorage.getItem(
                    'chat_visitor'
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
                    'chat_visitor',
                    id
                );
            }

            return id;
        },

        async sendMessage(message) {

            const response =
                await fetch(
                    this.config.server + '/api/chat',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
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

                throw new Error(
                    'API request failed'
                );
            }

            return response.json();
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
        }

    };

    window.ChatAgent = ChatAgent;

})();