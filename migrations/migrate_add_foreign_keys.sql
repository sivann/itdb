-- ITDB2 Foreign Key Migration Script
-- This script recreates tables with proper foreign key constraints
--
-- IMPORTANT: BACKUP YOUR DATABASE BEFORE RUNNING THIS SCRIPT!
--
-- Usage: sqlite3 your_database.db < migrate_add_foreign_keys.sql
--
-- This script:
-- 1. Creates backup tables with current data
-- 2. Drops original tables
-- 3. Recreates tables with foreign key constraints
-- 4. Restores data from backups
-- 5. Cleans up backup tables

-- Enable foreign keys for this session
PRAGMA foreign_keys = OFF; -- Disable during migration
BEGIN TRANSACTION;

-- =============================================================================
-- BACKUP ORIGINAL TABLES
-- =============================================================================

-- Backup all tables that will be modified
CREATE TABLE agents_backup AS SELECT * FROM agents;
CREATE TABLE users_backup AS SELECT * FROM users;
CREATE TABLE items_backup AS SELECT * FROM items;
CREATE TABLE software_backup AS SELECT * FROM software;
CREATE TABLE invoices_backup AS SELECT * FROM invoices;
CREATE TABLE contracts_backup AS SELECT * FROM contracts;
CREATE TABLE itemtypes_backup AS SELECT * FROM itemtypes;
CREATE TABLE contracttypes_backup AS SELECT * FROM contracttypes;
CREATE TABLE agent_types_backup AS SELECT * FROM agent_types;
CREATE TABLE agent_agent_type_backup AS SELECT * FROM agent_agent_type;
CREATE TABLE contract2item_backup AS SELECT * FROM contract2item;
CREATE TABLE contract2soft_backup AS SELECT * FROM contract2soft;
CREATE TABLE item2soft_backup AS SELECT * FROM item2soft;
CREATE TABLE tag2item_backup AS SELECT * FROM tag2item;
CREATE TABLE tags_backup AS SELECT * FROM tags;
CREATE TABLE contractevents_backup AS SELECT * FROM contractevents;
CREATE TABLE actions_backup AS SELECT * FROM actions;

-- =============================================================================
-- DROP ORIGINAL TABLES (in dependency order)
-- =============================================================================

-- Drop dependent tables first
DROP TABLE IF EXISTS contractevents;
DROP TABLE IF EXISTS actions;
DROP TABLE IF EXISTS tag2item;
DROP TABLE IF EXISTS item2soft;
DROP TABLE IF EXISTS contract2soft;
DROP TABLE IF EXISTS contract2item;
DROP TABLE IF EXISTS agent_agent_type;

-- Drop main tables
DROP TABLE IF EXISTS contracts;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS software;
DROP TABLE IF EXISTS items;

-- Note: Keep reference tables (agents, users, itemtypes, contracttypes, agent_types, tags)
-- as they will be recreated with constraints but are referenced by others

-- =============================================================================
-- RECREATE REFERENCE TABLES WITH CONSTRAINTS
-- =============================================================================

-- Recreate agents table (no changes needed - it's a root table)
DROP TABLE IF EXISTS agents;
CREATE TABLE agents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type INTEGER,
    title TEXT,
    contactinfo TEXT,
    contacts TEXT,
    urls TEXT
);

-- Recreate users table (no changes needed - it's a root table)
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    userdesc TEXT,
    pass TEXT,
    usertype INTEGER DEFAULT 0,
    cookie1 TEXT
);

-- Recreate itemtypes table (no changes needed - it's a root table)
DROP TABLE IF EXISTS itemtypes;
CREATE TABLE itemtypes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);

-- Recreate contracttypes table (no changes needed - it's a root table)
DROP TABLE IF EXISTS contracttypes;
CREATE TABLE contracttypes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);

-- Recreate agent_types table (no changes needed - it's a root table)
DROP TABLE IF EXISTS agent_types;
CREATE TABLE agent_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    code TEXT UNIQUE NOT NULL,
    description TEXT,
    active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT,
    updated_at TEXT
);

-- Recreate tags table (no changes needed - it's a root table)
DROP TABLE IF EXISTS tags;
CREATE TABLE tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    color TEXT
);

-- =============================================================================
-- RECREATE MAIN TABLES WITH FOREIGN KEY CONSTRAINTS
-- =============================================================================

-- Recreate items table with foreign key constraints
CREATE TABLE items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    itemtypeid INTEGER NOT NULL,
    function TEXT,
    manufacturerid INTEGER,
    model TEXT,
    sn TEXT,
    sn2 TEXT,
    sn3 TEXT,
    origin TEXT,
    warrantymonths INTEGER,
    purchasedate INTEGER,
    purchprice TEXT,
    dnsname TEXT,
    maintenanceinfo TEXT,
    comments TEXT,
    ispart INTEGER DEFAULT 0,
    hd TEXT,
    cpu TEXT,
    ram TEXT,
    locationid INTEGER,
    userid INTEGER,
    ipv4 TEXT,
    ipv6 TEXT,
    usize INTEGER,
    rackmountable INTEGER,
    macs TEXT,
    remadmip TEXT,
    panelport TEXT,
    ports INTEGER,
    switchport TEXT,
    switchid INTEGER,
    rackid INTEGER,
    rackposition INTEGER,
    label TEXT,
    status INTEGER DEFAULT 1,
    cpuno INTEGER,
    corespercpu INTEGER,
    rackposdepth INTEGER,
    warrinfo TEXT,
    locareaid NUMBER,
    coa TEXT,

    -- Foreign key constraints
    FOREIGN KEY (itemtypeid) REFERENCES itemtypes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (manufacturerid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (userid) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Recreate software table with foreign key constraints
CREATE TABLE software (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    stitle TEXT,
    sversion TEXT,
    slicense TEXT,
    scomments TEXT,
    url TEXT,
    slicensetype TEXT,
    scat TEXT,
    manufacturerid INTEGER,

    -- Foreign key constraints
    FOREIGN KEY (manufacturerid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Recreate invoices table with foreign key constraints
CREATE TABLE invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date INTEGER,
    vendorid INTEGER,
    buyerid INTEGER,
    comments TEXT,
    totalcost REAL,

    -- Foreign key constraints
    FOREIGN KEY (vendorid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (buyerid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Recreate contracts table with foreign key constraints
CREATE TABLE contracts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type INTEGER,
    parentid INTEGER,
    title TEXT,
    number TEXT,
    description TEXT,
    comments TEXT,
    totalcost REAL,
    contractorid INTEGER,
    startdate INTEGER,
    currentenddate INTEGER,
    renewals TEXT,
    subtype INTEGER,
    vendorid INTEGER,

    -- Foreign key constraints
    FOREIGN KEY (type) REFERENCES contracttypes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (parentid) REFERENCES contracts(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (contractorid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (vendorid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- =============================================================================
-- RECREATE JUNCTION TABLES WITH FOREIGN KEY CONSTRAINTS
-- =============================================================================

-- Agent-AgentType many-to-many
CREATE TABLE agent_agent_type (
    agent_id INTEGER,
    agent_type_id INTEGER,
    PRIMARY KEY (agent_id, agent_type_id),

    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (agent_type_id) REFERENCES agent_types(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Contract-Item associations
CREATE TABLE contract2item (
    contractid INTEGER,
    itemid INTEGER,
    PRIMARY KEY (contractid, itemid),

    FOREIGN KEY (contractid) REFERENCES contracts(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (itemid) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Contract-Software associations
CREATE TABLE contract2soft (
    contractid INTEGER,
    softid INTEGER,
    PRIMARY KEY (contractid, softid),

    FOREIGN KEY (contractid) REFERENCES contracts(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (softid) REFERENCES software(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Item-Software associations
CREATE TABLE item2soft (
    itemid INTEGER,
    softid INTEGER,
    PRIMARY KEY (itemid, softid),

    FOREIGN KEY (itemid) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (softid) REFERENCES software(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Tag-Item associations
CREATE TABLE tag2item (
    tagid INTEGER,
    itemid INTEGER,
    PRIMARY KEY (tagid, itemid),

    FOREIGN KEY (tagid) REFERENCES tags(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (itemid) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- =============================================================================
-- RECREATE EVENT/LOG TABLES WITH FOREIGN KEY CONSTRAINTS
-- =============================================================================

-- Contract events
CREATE TABLE contractevents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contractid INTEGER NOT NULL,
    startdate INTEGER,
    enddate INTEGER,
    description TEXT,

    FOREIGN KEY (contractid) REFERENCES contracts(id) ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Actions/history
CREATE TABLE actions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    itemid INTEGER NOT NULL,
    actiondate INTEGER,
    description TEXT,
    userid INTEGER,

    FOREIGN KEY (itemid) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (userid) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- =============================================================================
-- RESTORE DATA FROM BACKUPS
-- =============================================================================

-- Restore reference tables first
INSERT INTO agents SELECT * FROM agents_backup;
INSERT INTO users SELECT * FROM users_backup;
INSERT INTO itemtypes SELECT * FROM itemtypes_backup;
INSERT INTO contracttypes SELECT * FROM contracttypes_backup;
INSERT INTO agent_types SELECT * FROM agent_types_backup;
INSERT INTO tags SELECT * FROM tags_backup;

-- Clean data for main tables (remove invalid foreign key references)
-- Insert items, but only with valid foreign key references
INSERT INTO items
SELECT
    i.id,
    i.itemtypeid,
    i.function,
    CASE WHEN a.id IS NOT NULL THEN i.manufacturerid ELSE NULL END as manufacturerid,
    i.model, i.sn, i.sn2, i.sn3, i.origin, i.warrantymonths, i.purchasedate,
    i.purchprice, i.dnsname, i.maintenanceinfo, i.comments, i.ispart,
    i.hd, i.cpu, i.ram, i.locationid,
    CASE WHEN u.id IS NOT NULL THEN i.userid ELSE NULL END as userid,
    i.ipv4, i.ipv6, i.usize, i.rackmountable, i.macs, i.remadmip,
    i.panelport, i.ports, i.switchport, i.switchid, i.rackid,
    i.rackposition, i.label, i.status, i.cpuno, i.corespercpu,
    i.rackposdepth, i.warrinfo, i.locareaid, i.coa
FROM items_backup i
LEFT JOIN agents a ON i.manufacturerid = a.id
LEFT JOIN users u ON i.userid = u.id
INNER JOIN itemtypes it ON i.itemtypeid = it.id;

-- Insert software with valid foreign key references
INSERT INTO software
SELECT
    s.id, s.stitle, s.sversion, s.slicense, s.scomments, s.url,
    s.slicensetype, s.scat,
    CASE WHEN a.id IS NOT NULL THEN s.manufacturerid ELSE NULL END as manufacturerid
FROM software_backup s
LEFT JOIN agents a ON s.manufacturerid = a.id;

-- Insert invoices with valid foreign key references
INSERT INTO invoices
SELECT
    i.id, i.date,
    CASE WHEN v.id IS NOT NULL THEN i.vendorid ELSE NULL END as vendorid,
    CASE WHEN b.id IS NOT NULL THEN i.buyerid ELSE NULL END as buyerid,
    i.comments, i.totalcost
FROM invoices_backup i
LEFT JOIN agents v ON i.vendorid = v.id
LEFT JOIN agents b ON i.buyerid = b.id;

-- Insert contracts with valid foreign key references
INSERT INTO contracts
SELECT
    c.id,
    CASE WHEN ct.id IS NOT NULL THEN c.type ELSE NULL END as type,
    CASE WHEN p.id IS NOT NULL THEN c.parentid ELSE NULL END as parentid,
    c.title, c.number, c.description, c.comments, c.totalcost,
    CASE WHEN contractor.id IS NOT NULL THEN c.contractorid ELSE NULL END as contractorid,
    c.startdate, c.currentenddate, c.renewals, c.subtype,
    CASE WHEN vendor.id IS NOT NULL THEN c.vendorid ELSE NULL END as vendorid
FROM contracts_backup c
LEFT JOIN contracttypes ct ON c.type = ct.id
LEFT JOIN contracts_backup p ON c.parentid = p.id
LEFT JOIN agents contractor ON c.contractorid = contractor.id
LEFT JOIN agents vendor ON c.vendorid = vendor.id;

-- Restore junction tables (only valid references)
INSERT INTO agent_agent_type
SELECT aat.agent_id, aat.agent_type_id
FROM agent_agent_type_backup aat
INNER JOIN agents a ON aat.agent_id = a.id
INNER JOIN agent_types at ON aat.agent_type_id = at.id;

INSERT INTO contract2item
SELECT c2i.contractid, c2i.itemid
FROM contract2item_backup c2i
INNER JOIN contracts c ON c2i.contractid = c.id
INNER JOIN items i ON c2i.itemid = i.id;

INSERT INTO contract2soft
SELECT c2s.contractid, c2s.softid
FROM contract2soft_backup c2s
INNER JOIN contracts c ON c2s.contractid = c.id
INNER JOIN software s ON c2s.softid = s.id;

INSERT INTO item2soft
SELECT i2s.itemid, i2s.softid
FROM item2soft_backup i2s
INNER JOIN items i ON i2s.itemid = i.id
INNER JOIN software s ON i2s.softid = s.id;

INSERT INTO tag2item
SELECT t2i.tagid, t2i.itemid
FROM tag2item_backup t2i
INNER JOIN tags t ON t2i.tagid = t.id
INNER JOIN items i ON t2i.itemid = i.id;

-- Restore event tables (only valid references)
INSERT INTO contractevents
SELECT ce.id, ce.contractid, ce.startdate, ce.enddate, ce.description
FROM contractevents_backup ce
INNER JOIN contracts c ON ce.contractid = c.id;

INSERT INTO actions
SELECT
    a.id, a.itemid, a.actiondate, a.description,
    CASE WHEN u.id IS NOT NULL THEN a.userid ELSE NULL END as userid
FROM actions_backup a
INNER JOIN items i ON a.itemid = i.id
LEFT JOIN users u ON a.userid = u.id;

-- =============================================================================
-- CREATE PERFORMANCE INDEXES
-- =============================================================================

-- Foreign key indexes for performance
CREATE INDEX idx_items_manufacturer ON items(manufacturerid);
CREATE INDEX idx_items_userid ON items(userid);
CREATE INDEX idx_items_itemtypeid ON items(itemtypeid);

CREATE INDEX idx_software_manufacturer ON software(manufacturerid);

CREATE INDEX idx_invoices_vendor ON invoices(vendorid);
CREATE INDEX idx_invoices_buyer ON invoices(buyerid);

CREATE INDEX idx_contracts_contractor ON contracts(contractorid);
CREATE INDEX idx_contracts_vendor ON contracts(vendorid);
CREATE INDEX idx_contracts_type ON contracts(type);
CREATE INDEX idx_contracts_parent ON contracts(parentid);

CREATE INDEX idx_agent_agent_type_agent ON agent_agent_type(agent_id);
CREATE INDEX idx_agent_agent_type_type ON agent_agent_type(agent_type_id);

CREATE INDEX idx_contract2item_contract ON contract2item(contractid);
CREATE INDEX idx_contract2item_item ON contract2item(itemid);

CREATE INDEX idx_contract2soft_contract ON contract2soft(contractid);
CREATE INDEX idx_contract2soft_software ON contract2soft(softid);

CREATE INDEX idx_item2soft_item ON item2soft(itemid);
CREATE INDEX idx_item2soft_software ON item2soft(softid);

CREATE INDEX idx_tag2item_tag ON tag2item(tagid);
CREATE INDEX idx_tag2item_item ON tag2item(itemid);

CREATE INDEX idx_contractevents_contract ON contractevents(contractid);
CREATE INDEX idx_actions_item ON actions(itemid);
CREATE INDEX idx_actions_user ON actions(userid);

-- =============================================================================
-- CLEANUP BACKUP TABLES
-- =============================================================================

DROP TABLE agents_backup;
DROP TABLE users_backup;
DROP TABLE items_backup;
DROP TABLE software_backup;
DROP TABLE invoices_backup;
DROP TABLE contracts_backup;
DROP TABLE itemtypes_backup;
DROP TABLE contracttypes_backup;
DROP TABLE agent_types_backup;
DROP TABLE agent_agent_type_backup;
DROP TABLE contract2item_backup;
DROP TABLE contract2soft_backup;
DROP TABLE item2soft_backup;
DROP TABLE tag2item_backup;
DROP TABLE tags_backup;
DROP TABLE contractevents_backup;
DROP TABLE actions_backup;

-- =============================================================================
-- FINAL SETUP
-- =============================================================================

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- Update database version if settings table exists
UPDATE settings SET dbversion = 7 WHERE EXISTS(SELECT 1 FROM sqlite_master WHERE name = 'settings');

COMMIT;

-- =============================================================================
-- VERIFICATION QUERIES
-- =============================================================================

-- Check that foreign keys are enabled
PRAGMA foreign_keys;

-- Count records in main tables to verify data integrity
SELECT
    'agents' as table_name, COUNT(*) as record_count FROM agents
UNION ALL SELECT 'users', COUNT(*) FROM users
UNION ALL SELECT 'items', COUNT(*) FROM items
UNION ALL SELECT 'software', COUNT(*) FROM software
UNION ALL SELECT 'invoices', COUNT(*) FROM invoices
UNION ALL SELECT 'contracts', COUNT(*) FROM contracts;

-- Test foreign key constraints (these should fail if constraints are working)
-- Uncomment to test:
-- INSERT INTO items (function, manufacturerid, itemtypeid) VALUES ('Test', 99999, 1);
-- DELETE FROM agents WHERE id IN (SELECT DISTINCT manufacturerid FROM items WHERE manufacturerid IS NOT NULL LIMIT 1);

SELECT 'Migration completed successfully!' as status;