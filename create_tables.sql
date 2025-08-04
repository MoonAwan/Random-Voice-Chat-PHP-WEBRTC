-- Note: The columns defined below are examples based on the table names.
-- You should modify the columns, data types, and constraints to fit your application's needs.

-- 1. Table for active calls
-- This table can store information about calls that are currently in progress.
CREATE TABLE `active_calls` (
    `call_id` INT(11) NOT NULL AUTO_INCREMENT,
    `caller_id` VARCHAR(255) NOT NULL,
    `receiver_id` VARCHAR(255) NOT NULL,
    `start_time` DATETIME NOT NULL,
    `call_status` VARCHAR(50) DEFAULT 'ongoing',
    PRIMARY KEY (`call_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- 2. Table for call history
-- This table logs completed calls for historical records.
CREATE TABLE `call_history` (
    `history_id` INT(11) NOT NULL AUTO_INCREMENT,
    `caller_id` VARCHAR(255) NOT NULL,
    `receiver_id` VARCHAR(255) NOT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NOT NULL,
    `duration_seconds` INT(11),
    `termination_reason` VARCHAR(100),
    PRIMARY KEY (`history_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- 3. Table for online users
-- This table tracks which users are currently online and available.
CREATE TABLE `online_users` (
    `user_id` VARCHAR(255) NOT NULL,
    `session_id` VARCHAR(255) NOT NULL,
    `login_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_seen` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('available', 'busy', 'away') DEFAULT 'available',
    PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- 4. Table for signals
-- This could be for WebRTC signaling or other communication protocol data.
CREATE TABLE `signals` (
    `signal_id` INT(11) NOT NULL AUTO_INCREMENT,
    `sender_id` VARCHAR(255) NOT NULL,
    `receiver_id` VARCHAR(255) NOT NULL,
    `signal_type` VARCHAR(50),
    `signal_data` TEXT,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`signal_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- 5. Table for waiting users
-- This table can manage users who are in a queue, waiting for an action (e.g., waiting for an agent).
CREATE TABLE `waiting_users` (
    `user_id` VARCHAR(255) NOT NULL,
    `queue_id` VARCHAR(100) NOT NULL,
    `entered_queue_at` DATETIME NOT NULL,
    `priority` INT(3) DEFAULT 1,
    PRIMARY KEY (`user_id`, `queue_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
