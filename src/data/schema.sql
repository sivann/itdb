CREATE TABLE sqlite_sequence(name,seq);
CREATE TABLE files (id INTEGER PRIMARY KEY AUTOINCREMENT,file_category,title,filename_stored, uploader_username, uploaded_at, updated_at integer, filename_original TEXT, description TEXT, file_size INTEGER, file_type_id INTEGER);
CREATE TABLE IF NOT EXISTS "file_types" (id INTEGER PRIMARY KEY AUTOINCREMENT, name);
CREATE TABLE history (id INTEGER PRIMARY KEY AUTOINCREMENT, date integer, sql, authuser, ip);
CREATE TABLE labelpapers (id INTEGER PRIMARY KEY AUTOINCREMENT,rows integer, cols integer, lwidth real, lheight real,  vpitch real,  hpitch real,  tmargin real,  bmargin real,  lmargin real,  rmargin real, name, border, padding, headerfontsize, idfontsize, wantheadertext, wantheaderimage, headertext, fontsize, wantbarcode, barcodesize, image, imagewidth, imageheight, papersize, qrtext, wantnotext, wantraligntext);
CREATE TABLE racks (id INTEGER PRIMARY KEY AUTOINCREMENT, location_id integer, size_units integer, depth_mm integer, comments,model,label, reverse_numbering integer, location_area_id number);
CREATE TABLE IF NOT EXISTS "status_types" (id INTEGER PRIMARY KEY AUTOINCREMENT, name);
CREATE TABLE IF NOT EXISTS "contracts_files"(contract_id integer,file_id integer);
CREATE TABLE viewhist(id INTEGER PRIMARY KEY AUTOINCREMENT, url,description);
CREATE TABLE IF NOT EXISTS "location_areas"(id  INTEGER PRIMARY KEY AUTOINCREMENT,location_id number,name,x1 number,y1 number,x2 number,y2 number);
CREATE TABLE IF NOT EXISTS "contract_subtypes"(id INTEGER PRIMARY KEY AUTOINCREMENT,contypeid integer, name);
CREATE TABLE settings(companytitle, dateformat, currency, lang, version, timezone, dbversion, useldap integer default 0, ldap_server, ldap_dn, ldap_getusers, ldap_getusers_filter, file_storage_path TEXT DEFAULT './public/storage/uploads');
CREATE TABLE agents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    contact_info TEXT,
    contacts TEXT,
    urls TEXT
);
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    display_name TEXT,
    password_hash TEXT,
    user_type INTEGER DEFAULT 0,
    remember_token TEXT
);
CREATE TABLE IF NOT EXISTS "item_types" (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
, description TEXT DEFAULT '', has_software INTEGER DEFAULT 0);
CREATE TABLE IF NOT EXISTS "contract_types" (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);
CREATE TABLE agent_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    code TEXT UNIQUE NOT NULL,
    description TEXT,
    active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT,
    updated_at TEXT
, badge_color TEXT DEFAULT 'secondary');
CREATE TABLE tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    color TEXT
);
CREATE TABLE invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    invoice_date INTEGER,
    vendor_id INTEGER,
    buyer_id INTEGER,
    comments TEXT,
    total_cost REAL, updated_at INTEGER, invoice_number TEXT, title TEXT,

    -- Foreign key constraints
    FOREIGN KEY (vendor_id) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE contracts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contract_type_id INTEGER,
    parent_contract_id INTEGER,
    title TEXT,
    contract_number TEXT,
    description TEXT,
    comments TEXT,
    total_cost REAL,
    contractor_id INTEGER,
    start_date INTEGER,
    end_date INTEGER,
    renewals TEXT,
    contract_subtype_id INTEGER,
    vendor_id INTEGER, updated_at INTEGER,

    -- Foreign key constraints
    FOREIGN KEY (contract_type_id) REFERENCES contracttypes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (parent_contract_id) REFERENCES contracts(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (contractor_id) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE agent_agent_type (
    agent_id INTEGER,
    agent_type_id INTEGER,
    PRIMARY KEY (agent_id, agent_type_id),

    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (agent_type_id) REFERENCES agent_types(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE IF NOT EXISTS "contracts_items" (
    contract_id INTEGER,
    item_id INTEGER,
    PRIMARY KEY (contract_id, item_id),

    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE IF NOT EXISTS "contracts_software" (
    contract_id INTEGER,
    software_id INTEGER,
    PRIMARY KEY (contract_id, software_id),

    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (software_id) REFERENCES software(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE IF NOT EXISTS "items_software" (
    item_id INTEGER,
    software_id INTEGER,
    PRIMARY KEY (item_id, software_id),

    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (software_id) REFERENCES software(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE IF NOT EXISTS "items_tags" (
    tag_id INTEGER,
    item_id INTEGER,
    PRIMARY KEY (tag_id, item_id),

    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE IF NOT EXISTS "contract_events" (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contractid INTEGER NOT NULL,
    startdate INTEGER,
    enddate INTEGER,
    description TEXT,

    FOREIGN KEY (contractid) REFERENCES contracts(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE actions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    itemid INTEGER NOT NULL,
    actiondate INTEGER,
    description TEXT,
    userid INTEGER,

    FOREIGN KEY (itemid) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (userid) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
);
CREATE INDEX idx_invoices_vendor ON invoices(vendor_id);
CREATE INDEX idx_invoices_buyer ON invoices(buyer_id);
CREATE INDEX idx_contracts_contractor ON contracts(contractor_id);
CREATE INDEX idx_contracts_vendor ON contracts(vendor_id);
CREATE INDEX idx_contracts_type ON contracts(contract_type_id);
CREATE INDEX idx_contracts_parent ON contracts(parent_contract_id);
CREATE INDEX idx_agent_agent_type_agent ON agent_agent_type(agent_id);
CREATE INDEX idx_agent_agent_type_type ON agent_agent_type(agent_type_id);
CREATE INDEX idx_contract2item_contract ON "contracts_items"(contract_id);
CREATE INDEX idx_contract2item_item ON "contracts_items"(item_id);
CREATE INDEX idx_contract2soft_contract ON "contracts_software"(contract_id);
CREATE INDEX idx_contract2soft_software ON "contracts_software"(software_id);
CREATE INDEX idx_item2soft_item ON "items_software"(item_id);
CREATE INDEX idx_item2soft_software ON "items_software"(software_id);
CREATE INDEX idx_tag2item_tag ON "items_tags"(tag_id);
CREATE INDEX idx_tag2item_item ON "items_tags"(item_id);
CREATE INDEX idx_contractevents_contract ON "contract_events"(contractid);
CREATE INDEX idx_actions_item ON actions(itemid);
CREATE INDEX idx_actions_user ON actions(userid);
CREATE TABLE license_types (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, description TEXT);
CREATE TABLE audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    asset_type TEXT NOT NULL,
    asset_id INTEGER,
    action TEXT NOT NULL,
    details TEXT,
    timestamp INTEGER NOT NULL DEFAULT (strftime('%s', 'now')),
    ip_address TEXT
);
CREATE TABLE IF NOT EXISTS "software_invoices" (
    invoice_id INTEGER,
    software_id INTEGER,
    PRIMARY KEY (invoice_id, software_id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (software_id) REFERENCES software(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS "items_invoices" (
            item_id INTEGER,
            invoice_id INTEGER,
            PRIMARY KEY (item_id, invoice_id),
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        );
CREATE INDEX idx_item2inv_item ON "items_invoices"(item_id);
CREATE INDEX idx_item2inv_invoice ON "items_invoices"(invoice_id);
CREATE TABLE IF NOT EXISTS "items_files" (
            item_id INTEGER,
            file_id INTEGER,
            PRIMARY KEY (item_id, file_id),
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE
        );
CREATE INDEX idx_item2file_item ON "items_files"(item_id);
CREATE INDEX idx_item2file_file ON "items_files"(file_id);
CREATE TABLE IF NOT EXISTS "contracts_invoices" (
            contract_id INTEGER,
            invoice_id INTEGER,
            PRIMARY KEY (contract_id, invoice_id),
            FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        );
CREATE INDEX idx_contract2inv_contract ON "contracts_invoices"(contract_id);
CREATE INDEX idx_contract2inv_invoice ON "contracts_invoices"(invoice_id);
CREATE TABLE IF NOT EXISTS "software_tags" (
            tag_id INTEGER,
            software_id INTEGER,
            PRIMARY KEY (tag_id, software_id),
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
            FOREIGN KEY (software_id) REFERENCES software(id) ON DELETE CASCADE
        );
CREATE INDEX idx_tag2software_tag ON "software_tags"(tag_id);
CREATE INDEX idx_tag2software_software ON "software_tags"(software_id);
CREATE TABLE IF NOT EXISTS "itemlink" (
            itemid1 INTEGER,
            itemid2 INTEGER,
            PRIMARY KEY (itemid1, itemid2),
            FOREIGN KEY (itemid1) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (itemid2) REFERENCES items(id) ON DELETE CASCADE
        );
CREATE INDEX idx_itemlink_item1 ON itemlink(itemid1);
CREATE INDEX idx_itemlink_item2 ON itemlink(itemid2);
CREATE TABLE IF NOT EXISTS "software_files" (
                software_id INTEGER,
                file_id INTEGER,
                PRIMARY KEY (software_id, file_id),
                FOREIGN KEY (software_id) REFERENCES software(id) ON DELETE CASCADE,
                FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE
            );
CREATE TABLE IF NOT EXISTS "invoices_files"(invoice_id INTEGER, file_id INTEGER, PRIMARY KEY (invoice_id, file_id));
CREATE TABLE IF NOT EXISTS "locations" (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    floor TEXT,
    floor_plan_filename TEXT
);
CREATE UNIQUE INDEX idx_racks_label_unique ON racks(label);
CREATE TABLE IF NOT EXISTS "software" ( id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, version TEXT, license_key TEXT, comments TEXT, url TEXT, license_type_id TEXT, category TEXT, manufacturer_id INTEGER, updated_at INTEGER, FOREIGN KEY (manufacturer_id) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE, FOREIGN KEY (license_type_id) REFERENCES license_types(id) ON DELETE SET NULL ON UPDATE CASCADE );
CREATE TABLE IF NOT EXISTS "items" ( id INTEGER PRIMARY KEY AUTOINCREMENT, item_type_id INTEGER NOT NULL, function TEXT, manufacturer_id INTEGER, model TEXT, serial_number TEXT, serial_number_2 TEXT, serial_number_3 TEXT, origin TEXT, warranty_months INTEGER, purchase_date INTEGER, purchase_price TEXT, dns_name TEXT, maintenance_info TEXT, comments TEXT, is_part INTEGER DEFAULT 0, hard_drive TEXT, cpu TEXT, ram TEXT, location_id INTEGER, user_id INTEGER, ipv4_address TEXT, ipv6_address TEXT, rack_units INTEGER, is_rack_mountable INTEGER, mac_addresses TEXT, remote_admin_ip TEXT, panel_port TEXT, port_count INTEGER, switch_port TEXT, switch_id INTEGER, rack_id INTEGER, rack_position INTEGER, label TEXT, status_id INTEGER DEFAULT 1, cpu_count INTEGER, cores_per_cpu INTEGER, rack_position_depth INTEGER, warranty_info TEXT, location_area_id NUMBER, certificate_of_authenticity TEXT, updated_at INTEGER, FOREIGN KEY (item_type_id) REFERENCES item_types(id) ON DELETE RESTRICT ON UPDATE CASCADE, FOREIGN KEY (manufacturer_id) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE );
