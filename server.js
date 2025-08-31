/**
 * Real-time Chat and Notification Server using Node.js and Socket.io
 */

const http = require('http');
const { Server } = require("socket.io");
const jwt = require('jsonwebtoken'); // Assume this library is available

// !!! IMPORTANT !!!
// This secret MUST be the same as the JWT_SECRET defined in your PHP `config.php`.
// In a production environment, this should be loaded from a secure environment variable,
// not hardcoded.
const JWT_SECRET = 'your-super-secret-key-please-change-me';

// In a real cPanel setup, you might need to use a specific port provided by the environment.
// For development, we'll use a common port like 3000.
const PORT = process.env.PORT || 3000;

const server = http.createServer((req, res) => {
    // This is a basic HTTP server.
    // Socket.io will attach to it, but it doesn't serve any files itself.
    res.writeHead(200, { 'Content-Type': 'text/plain' });
    res.end('Socket.io server is running.');
});

const io = new Server(server, {
    cors: {
        origin: "*", // In production, restrict this to your actual domain (e.g., http://yourdomain.com)
        methods: ["GET", "POST"]
    }
});

// --- Socket.io Connection Logic ---
io.on('connection', (socket) => {
    console.log(`A user connected with socket id: ${socket.id}`);

    // Placeholder for user data after authentication
    socket.userData = null;

    socket.on('disconnect', () => {
        if (socket.userData) {
            console.log(`User ${socket.userData.user_id} (${socket.userData.user_role}) disconnected.`);
        } else {
            console.log(`User ${socket.id} disconnected.`);
        }
    });

    // 1. Authentication
    socket.on('authenticate', (token) => {
        if (!token) {
            socket.emit('unauthorized', { message: 'No token provided' });
            return socket.disconnect();
        }
        try {
            const decoded = jwt.verify(token, JWT_SECRET);
            socket.userData = decoded.data; // Attach user data to the socket

            // Join a room specific to this user_id for private messaging
            const userRoom = `user_${socket.userData.user_id}`;
            socket.join(userRoom);

            console.log(`User ${socket.userData.user_id} authenticated and joined room ${userRoom}`);
            socket.emit('authenticated');
        } catch (err) {
            socket.emit('unauthorized', { message: 'Invalid token' });
            return socket.disconnect();
        }
    });

    // 2. Handling Private Messages
    socket.on('private_message', (data, callback) => {
        // Ensure user is authenticated before allowing them to send messages
        if (!socket.userData) {
            return callback({ status: 'error', message: 'Not authenticated' });
        }

        const { recipient_id, message_content } = data;
        if (!recipient_id || !message_content) {
            return callback({ status: 'error', message: 'Missing recipient_id or message_content' });
        }

        const recipientRoom = `user_${recipient_id}`;

        const messagePayload = {
            sender_id: socket.userData.user_id,
            sender_role: socket.userData.user_role,
            content: message_content,
            timestamp: new Date()
        };

        // Emit the message to the specific recipient's room
        io.to(recipientRoom).emit('new_message', messagePayload);

        // Acknowledge the message was sent
        callback({ status: 'ok' });

        // Here you could also have a hook to save the message to the PHP/MySQL database
        // via an API call or another mechanism.
    });
});

server.listen(PORT, () => {
    console.log(`Server listening on port ${PORT}`);
});

// This is a simplified setup. A production setup would involve more robust error handling,
// and potentially a reverse proxy (like Nginx or Apache) to route requests.
