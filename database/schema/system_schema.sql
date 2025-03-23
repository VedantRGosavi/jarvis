-- users table: Stores user account information
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    created_at TEXT NOT NULL,
    last_login TEXT,
    subscription_status TEXT NOT NULL DEFAULT 'none',
    stripe_customer_id TEXT
);

-- subscriptions table: Tracks user subscription details
CREATE TABLE subscriptions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    stripe_subscription_id TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL,
    current_period_end TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- purchases table: Records one-time purchases
CREATE TABLE purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_id TEXT NOT NULL,
    payment_intent_id TEXT,
    status TEXT NOT NULL,
    amount INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    completed_at TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- user_settings table: Stores user preferences
CREATE TABLE user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    overlay_position TEXT,
    overlay_size TEXT,
    overlay_opacity REAL DEFAULT 0.85,
    hotkey_combination TEXT DEFAULT 'ctrl+shift+g',
    theme TEXT DEFAULT 'dark',
    default_info_display_mode TEXT DEFAULT 'concise',
    show_spoilers BOOLEAN DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- user_game_progress table: Tracks user progress within games
CREATE TABLE user_game_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_id TEXT NOT NULL,
    quest_id TEXT NOT NULL,
    step_id TEXT,
    completed INTEGER DEFAULT 0,
    marked_status TEXT DEFAULT 'untracked',
    notes TEXT,
    last_accessed TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(user_id, game_id, quest_id)
);

-- user_bookmarks table: Stores user-saved content
CREATE TABLE user_bookmarks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_id TEXT NOT NULL,
    resource_type TEXT NOT NULL,
    resource_id TEXT NOT NULL,
    display_name TEXT NOT NULL,
    bookmark_group TEXT DEFAULT 'default',
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(user_id, game_id, resource_type, resource_id)
);

-- usage_logs table: Records user activity for analytics
CREATE TABLE usage_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    game_id TEXT NOT NULL,
    action_type TEXT NOT NULL,
    resource_type TEXT,
    resource_id TEXT,
    session_id TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create indexes for performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX idx_purchases_user_id ON purchases(user_id);
CREATE INDEX idx_purchases_game_id ON purchases(game_id);
CREATE INDEX idx_usage_logs_user_id ON usage_logs(user_id);
CREATE INDEX idx_usage_logs_game_id ON usage_logs(game_id);
CREATE INDEX idx_user_game_progress_user_game ON user_game_progress(user_id, game_id);
CREATE INDEX idx_user_bookmarks_user_game ON user_bookmarks(user_id, game_id); 