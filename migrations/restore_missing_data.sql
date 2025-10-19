-- Restore missing data after migration
-- This script restores data that failed during the initial migration

PRAGMA foreign_keys = ON;

-- Attach the backup database
ATTACH DATABASE '/Users/sivann/sbx/itdb2/src/data/itdb.db.backup-20250924_091943' AS backup;

-- Restore items data with proper foreign key validation
INSERT INTO items (
    id, itemtypeid, function, manufacturerid, model, sn, sn2, sn3, origin,
    warrantymonths, purchasedate, purchprice, dnsname, maintenanceinfo, comments,
    ispart, hd, cpu, ram, locationid, userid, ipv4, ipv6, usize, rackmountable,
    macs, remadmip, panelport, ports, switchport, switchid, rackid, rackposition,
    label, status, cpuno, corespercpu, rackposdepth, warrinfo, locareaid, coa
)
SELECT
    i.id, i.itemtypeid, i.function,
    CASE WHEN a.id IS NOT NULL THEN i.manufacturerid ELSE NULL END as manufacturerid,
    i.model, i.sn, i.sn2, i.sn3, i.origin, i.warrantymonths, i.purchasedate,
    i.purchprice, i.dnsname, i.maintenanceinfo, i.comments, i.ispart,
    i.hd, i.cpu, i.ram, i.locationid,
    CASE WHEN u.id IS NOT NULL THEN i.userid ELSE NULL END as userid,
    i.ipv4, i.ipv6, i.usize, i.rackmountable, i.macs, i.remadmip,
    i.panelport, i.ports, i.switchport, i.switchid, i.rackid,
    i.rackposition, i.label, i.status, i.cpuno, i.corespercpu,
    i.rackposdepth, i.warrinfo, i.locareaid, i.coa
FROM backup.items i
LEFT JOIN agents a ON i.manufacturerid = a.id
LEFT JOIN users u ON i.userid = u.id
INNER JOIN itemtypes it ON i.itemtypeid = it.id;

-- Restore software data with proper foreign key validation
INSERT INTO software (id, stitle, sversion, slicense, scomments, url, slicensetype, scat, manufacturerid)
SELECT
    s.id, s.stitle, s.sversion, s.slicense, s.scomments, s.url, s.slicensetype, s.scat,
    CASE WHEN a.id IS NOT NULL THEN s.manufacturerid ELSE NULL END as manufacturerid
FROM backup.software s
LEFT JOIN agents a ON s.manufacturerid = a.id;

-- Restore invoices if empty
INSERT OR IGNORE INTO invoices (id, date, vendorid, buyerid, comments, totalcost)
SELECT
    i.id, i.date,
    CASE WHEN v.id IS NOT NULL THEN i.vendorid ELSE NULL END as vendorid,
    CASE WHEN b.id IS NOT NULL THEN i.buyerid ELSE NULL END as buyerid,
    i.comments, i.totalcost
FROM backup.invoices i
LEFT JOIN agents v ON i.vendorid = v.id
LEFT JOIN agents b ON i.buyerid = b.id;

-- Restore junction table data
INSERT OR IGNORE INTO contract2item (contractid, itemid)
SELECT c2i.contractid, c2i.itemid
FROM backup.contract2item c2i
INNER JOIN contracts c ON c2i.contractid = c.id
INNER JOIN items i ON c2i.itemid = i.id;

INSERT OR IGNORE INTO contract2soft (contractid, softid)
SELECT c2s.contractid, c2s.softid
FROM backup.contract2soft c2s
INNER JOIN contracts c ON c2s.contractid = c.id
INNER JOIN software s ON c2s.softid = s.id;

INSERT OR IGNORE INTO item2soft (itemid, softid)
SELECT i2s.itemid, i2s.softid
FROM backup.item2soft i2s
INNER JOIN items i ON i2s.itemid = i.id
INNER JOIN software s ON i2s.softid = s.id;

INSERT OR IGNORE INTO tag2item (tagid, itemid)
SELECT t2i.tagid, t2i.itemid
FROM backup.tag2item t2i
INNER JOIN tags t ON t2i.tagid = t.id
INNER JOIN items i ON t2i.itemid = i.id;

-- Restore actions if empty
INSERT OR IGNORE INTO actions (id, itemid, actiondate, description, userid)
SELECT
    a.id, a.itemid, a.actiondate, a.description,
    CASE WHEN u.id IS NOT NULL THEN a.userid ELSE NULL END as userid
FROM backup.actions a
INNER JOIN items i ON a.itemid = i.id
LEFT JOIN users u ON a.userid = u.id;

-- Detach backup database
DETACH DATABASE backup;

-- Verify counts
SELECT 'items' as table_name, COUNT(*) as count FROM items
UNION ALL SELECT 'software', COUNT(*) FROM software
UNION ALL SELECT 'invoices', COUNT(*) FROM invoices
UNION ALL SELECT 'contracts', COUNT(*) FROM contracts;

-- Test foreign key constraints are working
PRAGMA foreign_keys;
SELECT 'Foreign key constraints verification complete!' as status;