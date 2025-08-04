NexusTalk - (Random Voice & Text Chat)

NexusTalk is a web-based application that connects strangers for anonymous, one-on-one voice and text conversations. It's built with PHP and MySQL on the backend and utilizes WebRTC for direct, peer-to-peer communication, ensuring that voice data is not sent through the server.

This project is designed to be lightweight and compatible with standard cPanel hosting environments that do not allow for persistent WebSocket connections, using a database-driven polling system for signaling instead.

<!-- Optional: Add a screenshot of the app -->
Features

    Random Matchmaking: Connects you with another random user who is currently searching for a chat.

    Peer-to-Peer Voice Chat: Crystal-clear, low-latency voice communication powered by WebRTC.

    Real-time Text Chat: A simple and intuitive text chat that works alongside the voice call.

    Futuristic UI: A modern, dark-themed, and responsive user interface that looks great on all devices.

    Online User Count: A live counter that shows how many users are currently on the website.

    Age Verification: A mandatory popup to ensure users are 18 or older before they can access the service.

    Microphone Mute: Easily mute and unmute your microphone during a call.

    Auto-Reconnect: An optional feature to automatically find a new partner after a call ends.

    Stable User IDs: Uses PHP sessions to provide a stable user ID for a more reliable connection experience.

Technology Stack

    Frontend: HTML, Tailwind CSS, JavaScript

    Backend: PHP

    Database: MySQL

    Real-time Communication: WebRTC

Setup and Installation

Follow these steps to get NexusTalk running on your server.
1. Server Requirements

    A web server with PHP support (tested with PHP 7.4+).

    A MySQL or MariaDB database.

    Standard cPanel or similar hosting environment.

2. Database Setup

You need to create a database and then create the following four tables. You can use a tool like phpMyAdmin to run these SQL queries.

Table for waiting_users:

CREATE TABLE waiting_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(255) NOT NULL UNIQUE,
    offer_sdp TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

Table for online_users:

CREATE TABLE online_users (
    client_id VARCHAR(255) NOT NULL PRIMARY KEY,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

Table for active_calls:

CREATE TABLE active_calls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peer1_id VARCHAR(255) NOT NULL,
    peer2_id VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_peers (peer1_id, peer2_id)
);

Table for signals:

CREATE TABLE signals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id VARCHAR(255) NOT NULL,
    signal_data TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient_id (recipient_id)
);

3. File Configuration

    Upload Files: Upload the index.php, api.php, and functions.php files to the main directory of your website.

    Configure Database Connection: Open the db_connect.php file and enter your database credentials:

    // db_connect.php

    // --- Database Configuration ---
    // Replace with your actual database credentials
    $dbHost = 'localhost';
    $dbUser = 'your_db_user';
    $dbPass = 'your_db_password';
    $dbName = 'your_db_name';

That's it! Your application should now be live and running.
How It Works

The application uses a database-polling mechanism for signaling, which is necessary for WebRTC to establish a connection.

    User A clicks "Find Chat." Their browser generates a WebRTC "offer" and sends it to api.php.

    The server sees that no one is waiting, so it adds User A to the waiting_users table.

    User A's browser starts polling api.php every few seconds to check for signals.

    User B clicks "Find Chat." Their browser also generates an "offer."

    The server sees that User A is waiting. It pairs them up, creates a record in the active_calls table, and sends User A's offer to User B.

    User B's browser receives the offer, creates a WebRTC "answer," and sends it back to the server, addressed to User A.

    During its next poll, User A's browser receives the "answer" from User B.

    Both browsers then exchange network candidates through the same polling mechanism. Once they have enough information, a direct, peer-to-peer WebRTC connection is established, and the voice and chat data begin to flow.

Contributing

Contributions are welcome! If you'd like to help improve NexusTalk, please feel free to fork the repository, make your changes, and submit a pull request.

Some areas for potential improvement include:

    Implementing a "call back" feature for the call history.

    Adding video chat functionality.

    Improving the UI/UX.

License

This project is open-source and available under the
