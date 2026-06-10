(function () {

    const ChatAgent = {

        config: {},

        init(options) {

            this.config = options;

            this.loadStyles();

            this.createWidget();
        },

        loadStyles() {

            const css = document.createElement('link');

            css.rel = 'stylesheet';

            css.href = this.config.public_server + '/widget.css';

            document.head.appendChild(css);
        },

        createWidget() {

            const btn = document.createElement('div');

            btn.id = 'chat-agent-button';

            btn.innerHTML = '💬';

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

            const html = `

                <div id="chat-window">

                    <div id="chat-header">
                        AI Assistant
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

            try {

                const response =
                    await this.sendMessage(message);

                this.appendMessage(
                    'ai',
                    response.reply
                );

            } catch (error) {

                console.error(error);

                this.appendMessage(
                    'ai',
                    'Sorry, something went wrong.'
                );
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
        }

    };

    window.ChatAgent = ChatAgent;

})();