-- quests table: Main quest information
CREATE TABLE quests (
    quest_id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    type TEXT NOT NULL,
    starting_location_id TEXT,
    quest_giver_id TEXT,
    difficulty TEXT,
    is_main_story INTEGER DEFAULT 0,
    prerequisites TEXT,
    rewards TEXT,
    related_quests TEXT,
    spoiler_level INTEGER DEFAULT 0,
    version_added TEXT,
    last_updated TEXT,
    time_sensitive INTEGER DEFAULT 0,
    category TEXT,
    level TEXT,
    location_note TEXT
);

-- quest_steps table: Detailed quest progression steps
CREATE TABLE quest_steps (
    step_id TEXT PRIMARY KEY,
    quest_id TEXT NOT NULL,
    step_number INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    objective TEXT NOT NULL,
    hints TEXT,
    location_id TEXT,
    required_items TEXT,
    required_npcs TEXT,
    completion_flags TEXT,
    next_step_id TEXT,
    alternative_paths TEXT,
    spoiler_level INTEGER DEFAULT 0,
    FOREIGN KEY (quest_id) REFERENCES quests(quest_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

-- locations table: Game areas and points of interest
CREATE TABLE locations (
    location_id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    region TEXT NOT NULL,
    parent_location_id TEXT,
    coordinates TEXT,
    points_of_interest TEXT,
    connected_locations TEXT,
    difficulty_level TEXT,
    recommended_level TEXT,
    notable_items TEXT,
    notable_npcs TEXT,
    FOREIGN KEY (parent_location_id) REFERENCES locations(location_id)
);

-- npcs table: Non-player characters
CREATE TABLE npcs (
    npc_id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    role TEXT,
    default_location_id TEXT,
    faction TEXT,
    is_hostile INTEGER DEFAULT 0,
    is_merchant INTEGER DEFAULT 0,
    gives_quests TEXT,
    services TEXT,
    dialogue_summary TEXT,
    relationship_to_other_npcs TEXT,
    schedule TEXT,
    drops_on_defeat TEXT,
    category TEXT,
    image_url TEXT,
    health TEXT,
    drops TEXT,
    FOREIGN KEY (default_location_id) REFERENCES locations(location_id)
);

-- items table: Game items
CREATE TABLE items (
    item_id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    type TEXT NOT NULL,
    subtype TEXT,
    stats TEXT,
    requirements TEXT,
    effects TEXT,
    locations_found TEXT,
    dropped_by TEXT,
    quest_related INTEGER DEFAULT 0,
    related_quests TEXT,
    rarity TEXT,
    image_path TEXT,
    is_missable INTEGER DEFAULT 0,
    location_note TEXT
);

-- npc_locations table: Tracks where NPCs can be found at different stages
CREATE TABLE npc_locations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    npc_id TEXT NOT NULL,
    location_id TEXT NOT NULL,
    condition_type TEXT,
    condition_value TEXT,
    notes TEXT,
    FOREIGN KEY (npc_id) REFERENCES npcs(npc_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

-- quest_prerequisites table: Tracks requirements for quests
CREATE TABLE quest_prerequisites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quest_id TEXT NOT NULL,
    prerequisite_type TEXT NOT NULL,
    prerequisite_id TEXT NOT NULL,
    notes TEXT,
    FOREIGN KEY (quest_id) REFERENCES quests(quest_id)
);

-- quest_consequences table: Tracks effects of quest decisions
CREATE TABLE quest_consequences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quest_id TEXT NOT NULL,
    step_id TEXT,
    decision TEXT NOT NULL,
    affects_type TEXT NOT NULL,
    affects_id TEXT NOT NULL,
    effect TEXT NOT NULL,
    spoiler_level INTEGER DEFAULT 0,
    FOREIGN KEY (quest_id) REFERENCES quests(quest_id),
    FOREIGN KEY (step_id) REFERENCES quest_steps(step_id)
);

-- tasks table: Missable tasks and time-sensitive events
CREATE TABLE tasks (
    task_id TEXT PRIMARY KEY,
    description TEXT NOT NULL,
    location TEXT,
    act TEXT,
    is_missable INTEGER DEFAULT 0,
    completed INTEGER DEFAULT 0
);

-- abilities table: Missable abilities and special skills
CREATE TABLE abilities (
    ability_id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    location TEXT,
    act TEXT,
    url TEXT
);

-- classes table: Character starting classes (Elden Ring)
CREATE TABLE classes (
    class_id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT NOT NULL,
    stats TEXT,
    equipment TEXT,
    image_url TEXT
);

-- merchant_items table: Links merchants (NPCs) to their available items
CREATE TABLE merchant_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    merchant_id TEXT NOT NULL,
    item_id TEXT NOT NULL,
    is_missable INTEGER DEFAULT 0,
    act TEXT,
    location TEXT,
    FOREIGN KEY (merchant_id) REFERENCES npcs(npc_id),
    FOREIGN KEY (item_id) REFERENCES items(item_id)
);

-- search_index table: Optimized table for text search
CREATE VIRTUAL TABLE search_index USING fts5(
    content_id,
    content_type,
    name,
    description,
    keywords,
    tokenize='porter'
);

-- Create indexes for performance
CREATE INDEX idx_quest_steps_quest_id ON quest_steps(quest_id);
CREATE INDEX idx_locations_region ON locations(region);
CREATE INDEX idx_items_type ON items(type);
CREATE INDEX idx_items_quest_related ON items(quest_related);
CREATE INDEX idx_npcs_default_location ON npcs(default_location_id);
CREATE INDEX idx_npc_locations_npc_id ON npc_locations(npc_id);
CREATE INDEX idx_npc_locations_location_id ON npc_locations(location_id);
CREATE INDEX idx_quest_prerequisites_quest_id ON quest_prerequisites(quest_id); 