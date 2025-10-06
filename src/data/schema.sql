CREATE TABLE sqlite_sequence(name,seq);
CREATE TABLE files (id INTEGER PRIMARY KEY AUTOINCREMENT,type,title,fname, uploader, uploaddate, date integer, filename TEXT, description TEXT, filesize INTEGER, ftype INTEGER);
CREATE TABLE filetypes (id INTEGER PRIMARY KEY AUTOINCREMENT, typedesc);
CREATE TABLE history (id INTEGER PRIMARY KEY AUTOINCREMENT, date integer, sql, authuser, ip);
CREATE TABLE invoice2file(invoiceid INTEGER,fileid INTEGER);
CREATE TABLE labelpapers (id INTEGER PRIMARY KEY AUTOINCREMENT,rows integer, cols integer, lwidth real, lheight real,  vpitch real,  hpitch real,  tmargin real,  bmargin real,  lmargin real,  rmargin real, name, border, padding, headerfontsize, idfontsize, wantheadertext, wantheaderimage, headertext, fontsize, wantbarcode, barcodesize, image, imagewidth, imageheight, papersize, qrtext, wantnotext, wantraligntext);
CREATE TABLE racks (id INTEGER PRIMARY KEY AUTOINCREMENT, locationid integer, usize integer, depth integer, comments,model,label, revnums integer, locareaid number);
CREATE TABLE statustypes (id INTEGER PRIMARY KEY AUTOINCREMENT, statusdesc);
CREATE TABLE contract2file(contractid integer,fileid integer);
CREATE TABLE viewhist(id INTEGER PRIMARY KEY AUTOINCREMENT, url,description);
CREATE TABLE locations (id INTEGER PRIMARY KEY AUTOINCREMENT, name, floor, floorplanfn);
CREATE TABLE locareas(id  INTEGER PRIMARY KEY AUTOINCREMENT,locationid number,areaname,x1 number,y1 number,x2 number,y2 number);
CREATE TABLE contractsubtypes(id INTEGER PRIMARY KEY AUTOINCREMENT,contypeid integer, name);
CREATE TABLE settings(companytitle, dateformat, currency, lang, version, timezone, dbversion, useldap integer default 0, ldap_server, ldap_dn, ldap_getusers, ldap_getusers_filter);
CREATE TABLE agents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type INTEGER,
    title TEXT,
    contactinfo TEXT,
    contacts TEXT,
    urls TEXT
);
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL,
    userdesc TEXT,
    pass TEXT,
    usertype INTEGER DEFAULT 0,
    cookie1 TEXT
);
CREATE TABLE itemtypes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
, typedesc TEXT DEFAULT '', hassoftware INTEGER DEFAULT 0);
CREATE TABLE contracttypes (
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
);
CREATE TABLE tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    color TEXT
);
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
    coa TEXT, updated_at INTEGER,

    -- Foreign key constraints
    FOREIGN KEY (itemtypeid) REFERENCES itemtypes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (manufacturerid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (userid) REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE software (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    stitle TEXT,
    sversion TEXT,
    slicense TEXT,
    scomments TEXT,
    url TEXT,
    slicensetype TEXT,
    scat TEXT,
    manufacturerid INTEGER, updated_at INTEGER,

    -- Foreign key constraints
    FOREIGN KEY (manufacturerid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date INTEGER,
    vendorid INTEGER,
    buyerid INTEGER,
    comments TEXT,
    totalcost REAL, updated_at INTEGER,

    -- Foreign key constraints
    FOREIGN KEY (vendorid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (buyerid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
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
    vendorid INTEGER, updated_at INTEGER,

    -- Foreign key constraints
    FOREIGN KEY (type) REFERENCES contracttypes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (parentid) REFERENCES contracts(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (contractorid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (vendorid) REFERENCES agents(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE agent_agent_type (
    agent_id INTEGER,
    agent_type_id INTEGER,
    PRIMARY KEY (agent_id, agent_type_id),

    FOREIGN KEY (agent_id) REFERENCES agents(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (agent_type_id) REFERENCES agent_types(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE contract2item (
    contractid INTEGER,
    itemid INTEGER,
    PRIMARY KEY (contractid, itemid),

    FOREIGN KEY (contractid) REFERENCES contracts(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (itemid) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE contract2soft (
    contractid INTEGER,
    softid INTEGER,
    PRIMARY KEY (contractid, softid),

    FOREIGN KEY (contractid) REFERENCES contracts(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (softid) REFERENCES software(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE item2soft (
    itemid INTEGER,
    softid INTEGER,
    PRIMARY KEY (itemid, softid),

    FOREIGN KEY (itemid) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (softid) REFERENCES software(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE tag2item (
    tagid INTEGER,
    itemid INTEGER,
    PRIMARY KEY (tagid, itemid),

    FOREIGN KEY (tagid) REFERENCES tags(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (itemid) REFERENCES items(id) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE TABLE contractevents (
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
CREATE TABLE IF NOT EXISTS "soft2inv" (
    invid INTEGER,
    softid INTEGER,
    PRIMARY KEY (invid, softid),
    FOREIGN KEY (invid) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (softid) REFERENCES software(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS "item2inv" (
            itemid INTEGER,
            invid INTEGER,
            PRIMARY KEY (itemid, invid),
            FOREIGN KEY (itemid) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (invid) REFERENCES invoices(id) ON DELETE CASCADE
        );
CREATE INDEX idx_item2inv_item ON item2inv(itemid);
CREATE INDEX idx_item2inv_invoice ON item2inv(invid);
CREATE TABLE IF NOT EXISTS "item2file" (
            itemid INTEGER,
            fileid INTEGER,
            PRIMARY KEY (itemid, fileid),
            FOREIGN KEY (itemid) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (fileid) REFERENCES files(id) ON DELETE CASCADE
        );
CREATE INDEX idx_item2file_item ON item2file(itemid);
CREATE INDEX idx_item2file_file ON item2file(fileid);
CREATE TABLE IF NOT EXISTS "contract2inv" (
            contractid INTEGER,
            invid INTEGER,
            PRIMARY KEY (contractid, invid),
            FOREIGN KEY (contractid) REFERENCES contracts(id) ON DELETE CASCADE,
            FOREIGN KEY (invid) REFERENCES invoices(id) ON DELETE CASCADE
        );
CREATE INDEX idx_contract2inv_contract ON contract2inv(contractid);
CREATE INDEX idx_contract2inv_invoice ON contract2inv(invid);
CREATE TABLE IF NOT EXISTS "tag2software" (
            tagid INTEGER,
            softwareid INTEGER,
            PRIMARY KEY (tagid, softwareid),
            FOREIGN KEY (tagid) REFERENCES tags(id) ON DELETE CASCADE,
            FOREIGN KEY (softwareid) REFERENCES software(id) ON DELETE CASCADE
        );
CREATE INDEX idx_tag2software_tag ON tag2software(tagid);
CREATE INDEX idx_tag2software_software ON tag2software(softwareid);
CREATE TABLE IF NOT EXISTS "itemlink" (
            itemid1 INTEGER,
            itemid2 INTEGER,
            PRIMARY KEY (itemid1, itemid2),
            FOREIGN KEY (itemid1) REFERENCES items(id) ON DELETE CASCADE,
            FOREIGN KEY (itemid2) REFERENCES items(id) ON DELETE CASCADE
        );
CREATE INDEX idx_itemlink_item1 ON itemlink(itemid1);
CREATE INDEX idx_itemlink_item2 ON itemlink(itemid2);
CREATE TABLE IF NOT EXISTS "software2file" (
                softwareid INTEGER,
                fileid INTEGER,
                PRIMARY KEY (softwareid, fileid),
                FOREIGN KEY (softwareid) REFERENCES software(id) ON DELETE CASCADE,
                FOREIGN KEY (fileid) REFERENCES files(id) ON DELETE CASCADE
            );
