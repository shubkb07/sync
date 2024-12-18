--- Write SQL Tables for analytics data, which work same like GA4, have event_name, event_params, user_properties timestamp, etc.
--- Have index on required columns for faster query.
--- Make it for MariaDB, MySQL.
--- I want to make my application stateless based on single cookie like GA4, so I want to store all the data in database and query it when needed.
--- It must be for both anomoymous and logged in users together.
--- It must be scalable and efficient.
--- It must be able to handle 1000s of requests per second.
--- It must have handle concurrent writes and reads.
--- It must be optimized for for furthur use in analytics like funnel, retention, etc.
--- It must also optimize for analytics looks like Looker, Tableau, etc + custom queries for custom analytics dashboard.
--- It must be able to handle 100s of millions of rows.
--- It must be also optimized for cost.
--- It must be able to handle data for multiple applications, like cross site tracking.
--- It must be able to handle data for multiple websites, like cross domain tracking.
--- It must be able to handle data for multiple devices, like cross device tracking.
--- Users: Represents both anonymous and authenticated users.
--- Sessions: Groups user interactions within a specific time frame.
--- Pages: Tracks page views.
--- Events: Captures various events, including resource loads.
--- Resources: Details about resources loaded on the page.
--- UTM Parameters: Stores marketing campaign data.
--- Event Parameters: Additional data related to events.
--- Write SQL Tables and indexes for analytics data, which work same like GA4.


CREATE TABLE users (
    global_user_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    user_id VARCHAR(255) DEFAULT NULL,       -- For authenticated users
    device_id VARCHAR(255) DEFAULT NULL,     -- For anonymous users (cookie or device_id)
    user_properties JSON,                    -- Key-value pairs of user properties
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (app_id) REFERENCES applications(app_id),
    INDEX (app_id, user_id),
    INDEX (app_id, device_id),
    INDEX (app_id, global_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sessions (
    session_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    global_user_id BIGINT NOT NULL,
    session_start TIMESTAMP NOT NULL,
    session_end TIMESTAMP,
    user_agent VARCHAR(512),
    ip_address VARBINARY(16),     -- Store IPv4 or IPv6 in binary form for efficiency
    device_type VARCHAR(64),
    browser VARCHAR(255),
    os VARCHAR(255),
    FOREIGN KEY (app_id) REFERENCES applications(app_id),
    FOREIGN KEY (global_user_id) REFERENCES users(global_user_id),
    INDEX (app_id, global_user_id, session_start),
    INDEX (session_start),
    INDEX (session_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
