<?php
// Start the session to create a stable client ID
session_start();
if (empty($_SESSION['clientId'])) {
    $_SESSION['clientId'] = 'user_' . bin2hex(random_bytes(8));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusTalk - Random Voice Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #111827; /* gray-900 */
            color: #d1d5db; /* gray-300 */
        }
        .glass-panel {
            background: rgba(31, 41, 55, 0.6); /* gray-800 with opacity */
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        #chat-log { 
            height: 280px; 
            scrollbar-width: thin;
            scrollbar-color: #4f46e5 #374151;
        }
        /* Custom Scrollbar for Webkit browsers */
        #chat-log::-webkit-scrollbar {
            width: 8px;
        }
        #chat-log::-webkit-scrollbar-track {
            background: #374151; /* gray-700 */
            border-radius: 10px;
        }
        #chat-log::-webkit-scrollbar-thumb {
            background-color: #4f46e5; /* indigo-600 */
            border-radius: 10px;
            border: 2px solid #374151;
        }
        .message {
            padding: 8px 12px;
            border-radius: 10px;
            max-width: 80%;
            word-wrap: break-word;
        }
        .message.sent {
            text-align: right;
            background-color: #2563eb; /* blue-600 */
            color: white;
            align-self: flex-end;
        }
        .message.received {
            text-align: left;
            background-color: #4b5563; /* gray-600 */
            color: white;
            align-self: flex-start;
        }
        #status-text { min-height: 24px; }
        label { user-select: none; }
        #mute-button svg { width: 24px; height: 24px; }
        .online-indicator {
            background: #22c55e; /* green-500 */
            border-radius: 50%;
            width: 10px;
            height: 10px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div id="age-verification-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
        <div class="glass-panel rounded-lg p-8 shadow-2xl text-center max-w-sm mx-auto">
            <h2 class="text-2xl font-bold mb-4 text-white">Age Verification</h2>
            <p class="mb-6 text-gray-300">Please confirm you are 18 or older to continue.</p>
            <div class="flex justify-center space-x-4">
                <button id="yes-button" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition-transform transform hover:scale-105">Yes</button>
                <button id="no-button" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-6 rounded-lg transition-transform transform hover:scale-105">No</button>
            </div>
        </div>
    </div>

    <div id="online-users-container" class="fixed top-4 left-4 glass-panel rounded-full p-2 shadow-lg flex items-center space-x-2 z-30">
        <div class="online-indicator"></div>
        <span id="online-count" class="font-semibold text-white">0</span>
        <span class="text-gray-300">Online</span>
    </div>

    <div id="main-content" class="hidden container mx-auto p-4">
        <h1 class="text-4xl font-bold text-center mb-2 text-white">NexusTalk</h1>
        <p id="status-text" class="text-center text-gray-400 mb-6">Welcome! Click "Find Chat" to start.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="glass-panel rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4 text-white">Voice Chat</h2>
                <audio id="local-audio" autoplay muted class="w-full mb-4 bg-gray-900 rounded"></audio>
                <audio id="remote-audio" autoplay class="w-full bg-gray-900 rounded"></audio>
                <div class="mt-4">
                    <div class="flex space-x-4">
                        <button id="toggle-chat-button" class="flex-grow bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg">Find Chat</button>
                        <button id="mute-button" class="bg-gray-700 hover:bg-gray-600 text-white font-bold p-3 rounded-lg hidden items-center justify-center transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <svg id="mic-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zM17.3 11c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.49 6-3.31 6-6.72h-1.7z"/></svg>
                            <svg id="mic-off-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="hidden"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v.18l5.82 5.82c.06-.12.18-.23.18-.39zm-2 0V7.18l4.82 4.82H13c0 1.66-1.34 3-3 3zm-6.7-3H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c.91-.13 1.77-.45 2.54-.9L1.39 3.27 3.27 1.39l19.34 19.34-1.88 1.88L12 14.82 5.18 8H3.3c0 .3.05.6.13.89L1 5.59 2.41 4.18 12 13.77l8.41 8.41L22.27 20.3l-1.45-1.45-8.82-8.82L3.3 3.3z"/></svg>
                        </button>
                    </div>
                    <div class="mt-4 text-center">
                        <input type="checkbox" id="auto-reconnect-checkbox" class="mr-2 h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500 border-gray-600 bg-gray-700">
                        <label for="auto-reconnect-checkbox">Auto-reconnect on disconnect</label>
                    </div>
                </div>
            </div>
            <div class="glass-panel rounded-lg p-6 flex flex-col">
                <h2 class="text-xl font-semibold mb-4 text-white">Text Chat</h2>
                <div id="chat-log" class="border border-gray-700 rounded p-2 overflow-y-scroll bg-gray-900/50 flex-grow flex flex-col space-y-2"></div>
                <div class="mt-4 flex">
                    <input type="text" id="chat-input" class="flex-grow p-2 border border-gray-700 rounded-l-lg bg-gray-800 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Type a message..." disabled>
                    <button id="send-button" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-r-lg" disabled>Send</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ageVerificationModal = document.getElementById('age-verification-modal'),
            yesButton = document.getElementById('yes-button'),
            noButton = document.getElementById('no-button'),
            mainContent = document.getElementById('main-content'),
            toggleChatButton = document.getElementById('toggle-chat-button'),
            muteButton = document.getElementById('mute-button'),
            micIcon = document.getElementById('mic-icon'),
            micOffIcon = document.getElementById('mic-off-icon'),
            autoReconnectCheckbox = document.getElementById('auto-reconnect-checkbox'),
            statusText = document.getElementById('status-text'),
            localAudio = document.getElementById('local-audio'),
            remoteAudio = document.getElementById('remote-audio'),
            chatLog = document.getElementById('chat-log'),
            chatInput = document.getElementById('chat-input'),
            sendButton = document.getElementById('send-button'),
            onlineCountEl = document.getElementById('online-count');

        let localStream, peerConnection, dataChannel, peerId;
        let isConnected = false, isConnecting = false, isMuted = false;
        let pollingInterval, onlineCountInterval, heartbeatInterval;
        let candidateBuffer = [];
        const clientId = '<?php echo $_SESSION["clientId"]; ?>';
        const configuration = { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] };

        async function sendSignal(signal) {
            await fetch(`api.php?action=send_signal`, {
                method: 'POST',
                body: JSON.stringify({ peerId, signal }),
                headers: {'Content-Type': 'application/json'}
            });
        }

        async function createPeerConnection() {
            peerConnection = new RTCPeerConnection(configuration);
            try {
                localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
                localAudio.srcObject = localStream;
                localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream));
            } catch (e) {
                statusText.textContent = "Could not access microphone. Please allow permission.";
                throw new Error("Microphone access denied.");
            }
            
            peerConnection.onicecandidate = event => {
                if (event.candidate) {
                    if (peerId) {
                        sendSignal({ type: 'candidate', candidate: event.candidate });
                    } else {
                        candidateBuffer.push(event.candidate);
                    }
                }
            };
            
            peerConnection.ontrack = event => {
                remoteAudio.srcObject = event.streams[0];
                setConnectionState(true);
            };

            peerConnection.ondatachannel = event => {
                dataChannel = event.channel;
                setupDataChannel();
            };
        }

        async function findChat() {
            // Always clean up previous connection before starting a new search
            if (peerConnection) {
                disconnect(false);
            }

            statusText.textContent = 'Searching for a partner...';
            toggleChatButton.disabled = true;
            isConnecting = true;

            await createPeerConnection();
            
            dataChannel = peerConnection.createDataChannel('chat');
            setupDataChannel();
            
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            const response = await fetch(`api.php?action=find_chat`, {
                method: 'POST',
                body: JSON.stringify({ offer: offer }),
                headers: {'Content-Type': 'application/json'}
            });
            const data = await response.json();

            if (data.status === 'paired') {
                peerId = data.peerId;
                statusText.textContent = 'Partner found! Connecting...';
                await peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer));
                const answer = await peerConnection.createAnswer();
                await peerConnection.setLocalDescription(answer);
                await sendSignal({ type: 'answer', answer: answer });
                startPolling();
            } else if (data.status === 'waiting') {
                statusText.textContent = 'Waiting for a partner...';
                startPolling();
            }
        }
        
        function startPolling() {
            if (pollingInterval) clearInterval(pollingInterval);
            pollingInterval = setInterval(pollServer, 2000);
        }

        async function pollServer() {
            if (!peerConnection) {
                clearInterval(pollingInterval);
                return;
            }
            const response = await fetch(`api.php?action=poll`);
            const data = await response.json();

            if (data.status === 'signals' && data.signals) {
                for (const signal of data.signals) {
                    if (signal.type === 'paired') {
                        peerId = signal.peerId;
                        while (candidateBuffer.length > 0) {
                            sendSignal({ type: 'candidate', candidate: candidateBuffer.shift() });
                        }
                    } else if (signal.type === 'answer' && peerConnection.signalingState !== 'stable') {
                        await peerConnection.setRemoteDescription(new RTCSessionDescription(signal.answer));
                    } else if (signal.type === 'candidate') {
                        if (peerConnection.remoteDescription) {
                            await peerConnection.addIceCandidate(new RTCIceCandidate(signal.candidate));
                        }
                    } else if (signal.type === 'leave') {
                        statusText.textContent = 'Partner has disconnected.';
                        disconnect(false); // Don't send a leave signal back
                    }
                }
            }
        }
        
        function setConnectionState(connected) {
            isConnected = connected;
            isConnecting = false;
            toggleChatButton.disabled = false;
            if (connected) {
                clearInterval(pollingInterval);
                statusText.textContent = 'Connected!';
                toggleChatButton.textContent = 'Disconnect';
                toggleChatButton.classList.replace('bg-indigo-600', 'bg-red-600');
                toggleChatButton.classList.replace('hover:bg-indigo-700', 'hover:bg-red-700');
                muteButton.classList.remove('hidden');
                startPolling(); // Keep polling for disconnects
            } else {
                toggleChatButton.textContent = 'Find Chat';
                toggleChatButton.classList.replace('bg-red-600', 'bg-indigo-600');
                toggleChatButton.classList.replace('hover:bg-red-700', 'hover:bg-indigo-700');
                muteButton.classList.add('hidden');
                disableChat();
            }
        }
        
        function disconnect(notifyPeer = true) {
            if (notifyPeer && isConnected && peerId) {
                sendSignal({ type: 'leave' });
            }
            clearInterval(pollingInterval);
            pollingInterval = null;
            if (peerConnection) {
                peerConnection.close();
                peerConnection = null;
            }
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }
            
            const wasConnected = isConnected;
            setConnectionState(false);
            
            if (wasConnected) {
                statusText.textContent = 'Disconnected.';
            }

            peerId = null;
            candidateBuffer = [];
            chatLog.innerHTML = '';

            if (wasConnected && autoReconnectCheckbox.checked) {
                statusText.textContent = 'Auto-reconnecting...';
                setTimeout(findChat, 2000);
            }
        }
        
        async function updateOnlineCount() {
            try {
                const response = await fetch(`api.php?action=get_online_count`);
                const data = await response.json();
                onlineCountEl.textContent = data.online || 0;
            } catch (e) {
                console.error("Could not fetch online count.");
            }
        }
        
        async function sendHeartbeat() {
            try {
                await fetch(`api.php?action=heartbeat`, { 
                    method: 'POST', 
                    body: JSON.stringify({}),
                    headers: {'Content-Type': 'application/json'}
                });
            } catch (e) {
                console.error("Heartbeat failed.");
            }
        }

        function setupDataChannel() {
            dataChannel.onopen = () => {
                console.log("Data channel is open!");
                enableChat();
            };
            dataChannel.onmessage = event => appendMessage(event.data, 'received');
            dataChannel.onclose = () => {
                console.log("Data channel is closed!");
                disableChat();
            };
        }

        function enableChat() {
            chatInput.disabled = false;
            sendButton.disabled = false;
        }
        function disableChat() {
            chatInput.disabled = true;
            sendButton.disabled = true;
        }
        function appendMessage(message, type) {
            const messageElement = document.createElement('div');
            messageElement.className = `message ${type}`;
            messageElement.textContent = message;
            chatLog.appendChild(messageElement);
            chatLog.scrollTop = chatLog.scrollHeight;
        }

        sendButton.addEventListener('click', () => {
            const message = chatInput.value;
            if (message && dataChannel && dataChannel.readyState === 'open') {
                dataChannel.send(message);
                appendMessage(message, 'sent');
                chatInput.value = '';
            }
        });
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendButton.click();
        });

        muteButton.addEventListener('click', () => {
            if (!localStream) return;
            isMuted = !isMuted;
            localStream.getAudioTracks()[0].enabled = !isMuted;
            micIcon.classList.toggle('hidden', isMuted);
            micOffIcon.classList.toggle('hidden', !isMuted);
            muteButton.classList.toggle('bg-yellow-500', isMuted);
            muteButton.classList.toggle('bg-gray-700', !isMuted);
        });

        yesButton.addEventListener('click', () => {
            ageVerificationModal.classList.add('hidden');
            mainContent.classList.remove('hidden');
            sendHeartbeat(); // Send initial heartbeat
            updateOnlineCount(); // Initial count
            onlineCountInterval = setInterval(updateOnlineCount, 10000); // Update every 1 second
            heartbeatInterval = setInterval(sendHeartbeat, 15000); // Heartbeat every 15 seconds
        });
        noButton.addEventListener('click', () => window.location.href = 'https://www.google.com');
        toggleChatButton.addEventListener('click', () => {
            if (isConnected || isConnecting) disconnect();
            else findChat();
        });
        window.addEventListener('beforeunload', () => {
             fetch(`api.php?action=leave`, { method: 'POST', keepalive: true });
        });
    </script>
</body>
</html>
