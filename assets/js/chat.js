/**
 * Client-side logic for the real-time chat application
 */

const ChatClient = {
    socket: null,
    onMessageReceived: null, // Callback function for the UI to handle new messages

    /**
     * Initializes the chat client, fetches a token, and connects to the server.
     * @param {function} onMessageCallback - A function to call when a new message is received.
     */
    async init(onMessageCallback) {
        this.onMessageReceived = onMessageCallback;

        try {
            // 1. Fetch the JWT for socket authentication
            const response = await fetch('/api/auth/socket_token');
            if (!response.ok) {
                throw new Error('Could not fetch authentication token.');
            }
            const { token } = await response.json();

            // 2. Connect to the Socket.io server
            // The URL should be configured based on the environment.
            this.socket = io('http://localhost:3000');

            // 3. Set up event listeners
            this.setupListeners();

            // 4. Authenticate with the server
            this.socket.emit('authenticate', token);

        } catch (error) {
            console.error('Chat initialization failed:', error);
        }
    },

    /**
     * Sets up all the necessary event listeners for the socket connection.
     */
    setupListeners() {
        this.socket.on('connect', () => {
            console.log('Connected to chat server.');
        });

        this.socket.on('authenticated', () => {
            console.log('Successfully authenticated with chat server.');
        });

        this.socket.on('unauthorized', (data) => {
            console.error('Chat authentication failed:', data.message);
        });

        this.socket.on('disconnect', () => {
            console.log('Disconnected from chat server.');
        });

        this.socket.on('new_message', (message) => {
            console.log('New message received:', message);
            if (this.onMessageReceived && typeof this.onMessageReceived === 'function') {
                this.onMessageReceived(message);
            }
        });
    },

    /**
     * Sends a private message to a specific recipient.
     * @param {number} recipientId - The user ID of the recipient.
     * @param {string} messageContent - The content of the message.
     */
    sendMessage(recipientId, messageContent) {
        if (!this.socket || !this.socket.connected) {
            console.error('Cannot send message. Not connected to chat server.');
            return;
        }

        const payload = {
            recipient_id: recipientId,
            message_content: messageContent,
        };

        this.socket.emit('private_message', payload, (response) => {
            if (response.status === 'ok') {
                console.log('Message sent successfully.');
            } else {
                console.error('Failed to send message:', response.message);
            }
        });
    }
};
