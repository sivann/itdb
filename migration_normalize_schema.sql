-- =====================================================
-- Database Schema Normalization Migration
-- Generated: 2025-10-18
-- =====================================================

PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;

-- =====================================================
-- 1. RENAME TYPE TABLES (simple renames)
-- =====================================================

ALTER TABLE itemtypes RENAME TO item_types;
ALTER TABLE statustypes RENAME TO status_types;
ALTER TABLE contracttypes RENAME TO contract_types;
ALTER TABLE filetypes RENAME TO file_types;
ALTER TABLE locareas RENAME TO location_areas;

-- =====================================================
-- 2. ITEMS TABLE - Column Renames
-- =====================================================

ALTER TABLE items RENAME COLUMN itemtypeid TO item_type_id;
ALTER TABLE items RENAME COLUMN manufacturerid TO manufacturer_id;
ALTER TABLE items RENAME COLUMN sn TO serial_number;
ALTER TABLE items RENAME COLUMN sn2 TO serial_number_2;
ALTER TABLE items RENAME COLUMN sn3 TO serial_number_3;
ALTER TABLE items RENAME COLUMN warrantymonths TO warranty_months;
ALTER TABLE items RENAME COLUMN purchasedate TO purchase_date;
ALTER TABLE items RENAME COLUMN purchprice TO purchase_price;
ALTER TABLE items RENAME COLUMN dnsname TO dns_name;
ALTER TABLE items RENAME COLUMN maintenanceinfo TO maintenance_info;
ALTER TABLE items RENAME COLUMN ispart TO is_part;
ALTER TABLE items RENAME COLUMN hd TO hard_drive;
ALTER TABLE items RENAME COLUMN locationid TO location_id;
ALTER TABLE items RENAME COLUMN userid TO user_id;
ALTER TABLE items RENAME COLUMN ipv4 TO ipv4_address;
ALTER TABLE items RENAME COLUMN ipv6 TO ipv6_address;
ALTER TABLE items RENAME COLUMN usize TO rack_units;
ALTER TABLE items RENAME COLUMN rackmountable TO is_rack_mountable;
ALTER TABLE items RENAME COLUMN macs TO mac_addresses;
ALTER TABLE items RENAME COLUMN remadmip TO remote_admin_ip;
ALTER TABLE items RENAME COLUMN panelport TO panel_port;
ALTER TABLE items RENAME COLUMN ports TO port_count;
ALTER TABLE items RENAME COLUMN switchport TO switch_port;
ALTER TABLE items RENAME COLUMN switchid TO switch_id;
ALTER TABLE items RENAME COLUMN rackid TO rack_id;
ALTER TABLE items RENAME COLUMN rackposition TO rack_position;
ALTER TABLE items RENAME COLUMN status TO status_id;
ALTER TABLE items RENAME COLUMN cpuno TO cpu_count;
ALTER TABLE items RENAME COLUMN corespercpu TO cores_per_cpu;
ALTER TABLE items RENAME COLUMN rackposdepth TO rack_position_depth;
ALTER TABLE items RENAME COLUMN warrinfo TO warranty_info;
ALTER TABLE items RENAME COLUMN locareaid TO location_area_id;
ALTER TABLE items RENAME COLUMN coa TO certificate_of_authenticity;

-- =====================================================
-- 3. SOFTWARE TABLE - Column Renames
-- =====================================================

ALTER TABLE software RENAME COLUMN stitle TO title;
ALTER TABLE software RENAME COLUMN sversion TO version;
ALTER TABLE software RENAME COLUMN slicense TO license_key;
ALTER TABLE software RENAME COLUMN scomments TO comments;
ALTER TABLE software RENAME COLUMN slicensetype TO license_type;
ALTER TABLE software RENAME COLUMN scat TO category;
ALTER TABLE software RENAME COLUMN manufacturerid TO manufacturer_id;

-- =====================================================
-- 4. CONTRACTS TABLE - Column Renames
-- =====================================================

ALTER TABLE contracts RENAME COLUMN type TO contract_type_id;
ALTER TABLE contracts RENAME COLUMN parentid TO parent_contract_id;
ALTER TABLE contracts RENAME COLUMN number TO contract_number;
ALTER TABLE contracts RENAME COLUMN totalcost TO total_cost;
ALTER TABLE contracts RENAME COLUMN contractorid TO contractor_id;
ALTER TABLE contracts RENAME COLUMN startdate TO start_date;
ALTER TABLE contracts RENAME COLUMN currentenddate TO end_date;
ALTER TABLE contracts RENAME COLUMN subtype TO contract_subtype_id;
ALTER TABLE contracts RENAME COLUMN vendorid TO vendor_id;

-- =====================================================
-- 5. INVOICES TABLE - Column Renames
-- =====================================================

ALTER TABLE invoices RENAME COLUMN date TO invoice_date;
ALTER TABLE invoices RENAME COLUMN vendorid TO vendor_id;
ALTER TABLE invoices RENAME COLUMN buyerid TO buyer_id;
ALTER TABLE invoices RENAME COLUMN totalcost TO total_cost;

-- Add new columns for invoices
ALTER TABLE invoices ADD COLUMN invoice_number TEXT;
ALTER TABLE invoices ADD COLUMN title TEXT;

-- =====================================================
-- 6. FILES TABLE - Column Renames
-- =====================================================

-- Remove duplicate column first (keep type, remove ftype)
-- In SQLite, we need to check if column exists before dropping
-- Since we can't easily drop columns in SQLite, we'll work with what we have

ALTER TABLE files RENAME COLUMN type TO file_category;
ALTER TABLE files RENAME COLUMN fname TO filename_stored;
ALTER TABLE files RENAME COLUMN uploader TO uploader_username;
ALTER TABLE files RENAME COLUMN uploaddate TO uploaded_at;
ALTER TABLE files RENAME COLUMN date TO updated_at;
ALTER TABLE files RENAME COLUMN filename TO filename_original;
ALTER TABLE files RENAME COLUMN filesize TO file_size;
ALTER TABLE files RENAME COLUMN ftype TO file_type_id;

-- =====================================================
-- 7. AGENTS TABLE - Column Renames
-- =====================================================

ALTER TABLE agents RENAME COLUMN title TO name;
ALTER TABLE agents RENAME COLUMN contactinfo TO contact_info;

-- =====================================================
-- 8. USERS TABLE - Column Renames
-- =====================================================

ALTER TABLE users RENAME COLUMN userdesc TO display_name;
ALTER TABLE users RENAME COLUMN pass TO password_hash;
ALTER TABLE users RENAME COLUMN usertype TO user_type;
ALTER TABLE users RENAME COLUMN cookie1 TO remember_token;

-- =====================================================
-- 9. TYPE TABLES - Column Renames
-- =====================================================

-- item_types (formerly itemtypes)
ALTER TABLE item_types RENAME COLUMN typedesc TO description;
ALTER TABLE item_types RENAME COLUMN hassoftware TO has_software;

-- status_types (formerly statustypes)
ALTER TABLE status_types RENAME COLUMN statusdesc TO name;

-- file_types (formerly filetypes)
ALTER TABLE file_types RENAME COLUMN typedesc TO name;

-- =====================================================
-- 10. LOCATIONS TABLE - Column Renames
-- =====================================================

ALTER TABLE locations RENAME COLUMN floorplanfn TO floor_plan_filename;

-- =====================================================
-- 11. LOCATION_AREAS TABLE - Column Renames
-- =====================================================

ALTER TABLE location_areas RENAME COLUMN locationid TO location_id;
ALTER TABLE location_areas RENAME COLUMN areaname TO name;

-- =====================================================
-- 12. RACKS TABLE - Column Renames
-- =====================================================

ALTER TABLE racks RENAME COLUMN locationid TO location_id;
ALTER TABLE racks RENAME COLUMN usize TO size_units;
ALTER TABLE racks RENAME COLUMN depth TO depth_mm;
ALTER TABLE racks RENAME COLUMN revnums TO reverse_numbering;
ALTER TABLE racks RENAME COLUMN locareaid TO location_area_id;

-- =====================================================
-- 13. JUNCTION TABLES - Rename Tables
-- =====================================================

ALTER TABLE item2file RENAME TO items_files;
ALTER TABLE item2inv RENAME TO items_invoices;
ALTER TABLE item2soft RENAME TO items_software;
ALTER TABLE software2file RENAME TO software_files;
ALTER TABLE contract2file RENAME TO contracts_files;
ALTER TABLE contract2item RENAME TO contracts_items;
ALTER TABLE contract2soft RENAME TO contracts_software;
ALTER TABLE contract2inv RENAME TO contracts_invoices;
ALTER TABLE invoice2file RENAME TO invoices_files;
ALTER TABLE tag2item RENAME TO items_tags;
ALTER TABLE tag2software RENAME TO software_tags;
ALTER TABLE soft2inv RENAME TO software_invoices;

-- =====================================================
-- 14. JUNCTION TABLES - Column Renames
-- =====================================================

-- items_files
ALTER TABLE items_files RENAME COLUMN itemid TO item_id;
ALTER TABLE items_files RENAME COLUMN fileid TO file_id;

-- items_invoices
ALTER TABLE items_invoices RENAME COLUMN itemid TO item_id;
ALTER TABLE items_invoices RENAME COLUMN invid TO invoice_id;

-- items_software
ALTER TABLE items_software RENAME COLUMN itemid TO item_id;
ALTER TABLE items_software RENAME COLUMN softid TO software_id;

-- software_files
ALTER TABLE software_files RENAME COLUMN softwareid TO software_id;
ALTER TABLE software_files RENAME COLUMN fileid TO file_id;

-- contracts_files
ALTER TABLE contracts_files RENAME COLUMN contractid TO contract_id;
ALTER TABLE contracts_files RENAME COLUMN fileid TO file_id;

-- contracts_items
ALTER TABLE contracts_items RENAME COLUMN contractid TO contract_id;
ALTER TABLE contracts_items RENAME COLUMN itemid TO item_id;

-- contracts_software
ALTER TABLE contracts_software RENAME COLUMN contractid TO contract_id;
ALTER TABLE contracts_software RENAME COLUMN softid TO software_id;

-- contracts_invoices
ALTER TABLE contracts_invoices RENAME COLUMN contractid TO contract_id;
ALTER TABLE contracts_invoices RENAME COLUMN invid TO invoice_id;

-- invoices_files
ALTER TABLE invoices_files RENAME COLUMN invoiceid TO invoice_id;
ALTER TABLE invoices_files RENAME COLUMN fileid TO file_id;

-- items_tags
ALTER TABLE items_tags RENAME COLUMN itemid TO item_id;
ALTER TABLE items_tags RENAME COLUMN tagid TO tag_id;

-- software_tags
ALTER TABLE software_tags RENAME COLUMN softwareid TO software_id;
ALTER TABLE software_tags RENAME COLUMN tagid TO tag_id;

-- software_invoices
ALTER TABLE software_invoices RENAME COLUMN softid TO software_id;
ALTER TABLE software_invoices RENAME COLUMN invid TO invoice_id;

COMMIT;
PRAGMA foreign_keys=ON;

-- =====================================================
-- Migration Complete
-- =====================================================
