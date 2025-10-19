#!/bin/bash

# Database Schema Normalization - Code Migration Script
# macOS Compatible Version
# Generated: 2025-10-18

set -e  # Exit on error

BACKUP_DIR="migration_backups_$(date +%Y%m%d_%H%M%S)"
echo "Creating backup directory: $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"

# Backup all PHP and Twig files before making changes
echo "Backing up PHP and Twig files..."
tar czf "$BACKUP_DIR/code_backup.tar.gz" src/ templates/

echo "Starting code migration..."

# Function to perform search and replace across files
replace_in_files() {
    local pattern=$1
    local replacement=$2
    local file_pattern=$3

    echo "  Replacing: $pattern → $replacement"
    find src/ templates/ -type f -name "$file_pattern" -print0 | xargs -0 sed -i '' "s|${pattern}|${replacement}|g"
}

# =============================================================================
# TABLE NAMES
# =============================================================================
echo ""
echo "=== Updating Table Names ==="

# Type tables
replace_in_files "FROM itemtypes" "FROM item_types" "*.php"
replace_in_files "JOIN itemtypes" "JOIN item_types" "*.php"
replace_in_files "INTO itemtypes" "INTO item_types" "*.php"
replace_in_files "UPDATE itemtypes" "UPDATE item_types" "*.php"

replace_in_files "FROM statustypes" "FROM status_types" "*.php"
replace_in_files "JOIN statustypes" "JOIN status_types" "*.php"
replace_in_files "INTO statustypes" "INTO status_types" "*.php"

replace_in_files "FROM contracttypes" "FROM contract_types" "*.php"
replace_in_files "JOIN contracttypes" "JOIN contract_types" "*.php"

replace_in_files "FROM filetypes" "FROM file_types" "*.php"
replace_in_files "JOIN filetypes" "JOIN file_types" "*.php"

replace_in_files "FROM locareas" "FROM location_areas" "*.php"
replace_in_files "JOIN locareas" "JOIN location_areas" "*.php"
replace_in_files "INTO locareas" "INTO location_areas" "*.php"

# Junction tables
replace_in_files "FROM item2file" "FROM items_files" "*.php"
replace_in_files "JOIN item2file" "JOIN items_files" "*.php"
replace_in_files "INTO item2file" "INTO items_files" "*.php"
replace_in_files "DELETE FROM item2file" "DELETE FROM items_files" "*.php"

replace_in_files "FROM item2inv" "FROM items_invoices" "*.php"
replace_in_files "JOIN item2inv" "JOIN items_invoices" "*.php"
replace_in_files "INTO item2inv" "INTO items_invoices" "*.php"
replace_in_files "DELETE FROM item2inv" "DELETE FROM items_invoices" "*.php"

replace_in_files "FROM item2soft" "FROM items_software" "*.php"
replace_in_files "JOIN item2soft" "JOIN items_software" "*.php"
replace_in_files "INTO item2soft" "INTO items_software" "*.php"
replace_in_files "DELETE FROM item2soft" "DELETE FROM items_software" "*.php"

replace_in_files "FROM software2file" "FROM software_files" "*.php"
replace_in_files "JOIN software2file" "JOIN software_files" "*.php"
replace_in_files "INTO software2file" "INTO software_files" "*.php"
replace_in_files "DELETE FROM software2file" "DELETE FROM software_files" "*.php"

replace_in_files "FROM contract2file" "FROM contracts_files" "*.php"
replace_in_files "JOIN contract2file" "JOIN contracts_files" "*.php"
replace_in_files "INTO contract2file" "INTO contracts_files" "*.php"
replace_in_files "DELETE FROM contract2file" "DELETE FROM contracts_files" "*.php"

replace_in_files "FROM contract2item" "FROM contracts_items" "*.php"
replace_in_files "JOIN contract2item" "JOIN contracts_items" "*.php"
replace_in_files "INTO contract2item" "INTO contracts_items" "*.php"
replace_in_files "DELETE FROM contract2item" "DELETE FROM contracts_items" "*.php"

replace_in_files "FROM contract2soft" "FROM contracts_software" "*.php"
replace_in_files "JOIN contract2soft" "JOIN contracts_software" "*.php"
replace_in_files "INTO contract2soft" "INTO contracts_software" "*.php"
replace_in_files "DELETE FROM contract2soft" "DELETE FROM contracts_software" "*.php"

replace_in_files "FROM contract2inv" "FROM contracts_invoices" "*.php"
replace_in_files "JOIN contract2inv" "JOIN contracts_invoices" "*.php"
replace_in_files "INTO contract2inv" "INTO contracts_invoices" "*.php"
replace_in_files "DELETE FROM contract2inv" "DELETE FROM contracts_invoices" "*.php"

replace_in_files "FROM invoice2file" "FROM invoices_files" "*.php"
replace_in_files "JOIN invoice2file" "JOIN invoices_files" "*.php"
replace_in_files "INTO invoice2file" "INTO invoices_files" "*.php"
replace_in_files "DELETE FROM invoice2file" "DELETE FROM invoices_files" "*.php"

replace_in_files "FROM tag2item" "FROM items_tags" "*.php"
replace_in_files "JOIN tag2item" "JOIN items_tags" "*.php"
replace_in_files "INTO tag2item" "INTO items_tags" "*.php"
replace_in_files "DELETE FROM tag2item" "DELETE FROM items_tags" "*.php"

replace_in_files "FROM tag2software" "FROM software_tags" "*.php"
replace_in_files "JOIN tag2software" "JOIN software_tags" "*.php"
replace_in_files "INTO tag2software" "INTO software_tags" "*.php"
replace_in_files "DELETE FROM tag2software" "DELETE FROM software_tags" "*.php"

replace_in_files "FROM soft2inv" "FROM software_invoices" "*.php"
replace_in_files "JOIN soft2inv" "JOIN software_invoices" "*.php"
replace_in_files "INTO soft2inv" "INTO software_invoices" "*.php"
replace_in_files "DELETE FROM soft2inv" "DELETE FROM software_invoices" "*.php"

# =============================================================================
# JUNCTION TABLE COLUMNS (Do these first to avoid conflicts)
# =============================================================================
echo ""
echo "=== Updating Junction Table Columns ==="

replace_in_files "itemid " "item_id " "*.php"
replace_in_files "itemid," "item_id," "*.php"
replace_in_files "itemid)" "item_id)" "*.php"
replace_in_files "itemid=" "item_id=" "*.php"

replace_in_files "fileid " "file_id " "*.php"
replace_in_files "fileid," "file_id," "*.php"
replace_in_files "fileid)" "file_id)" "*.php"
replace_in_files "fileid=" "file_id=" "*.php"

replace_in_files "invid " "invoice_id " "*.php"
replace_in_files "invid," "invoice_id," "*.php"
replace_in_files "invid)" "invoice_id)" "*.php"
replace_in_files "invid=" "invoice_id=" "*.php"

replace_in_files "softid " "software_id " "*.php"
replace_in_files "softid," "software_id," "*.php"
replace_in_files "softid)" "software_id)" "*.php"
replace_in_files "softid=" "software_id=" "*.php"

replace_in_files "softwareid " "software_id " "*.php"
replace_in_files "softwareid," "software_id," "*.php"
replace_in_files "softwareid)" "software_id)" "*.php"
replace_in_files "softwareid=" "software_id=" "*.php"

replace_in_files "contractid " "contract_id " "*.php"
replace_in_files "contractid," "contract_id," "*.php"
replace_in_files "contractid)" "contract_id)" "*.php"
replace_in_files "contractid=" "contract_id=" "*.php"

replace_in_files "invoiceid " "invoice_id " "*.php"
replace_in_files "invoiceid," "invoice_id," "*.php"
replace_in_files "invoiceid)" "invoice_id)" "*.php"
replace_in_files "invoiceid=" "invoice_id=" "*.php"

replace_in_files "tagid " "tag_id " "*.php"
replace_in_files "tagid," "tag_id," "*.php"
replace_in_files "tagid)" "tag_id)" "*.php"
replace_in_files "tagid=" "tag_id=" "*.php"

# =============================================================================
# COLUMN NAMES - ITEMS TABLE
# =============================================================================
echo ""
echo "=== Updating Items Table Columns ==="

replace_in_files "itemtypeid" "item_type_id" "*.php"
replace_in_files "manufacturerid" "manufacturer_id" "*.php"
replace_in_files "\.sn " ".serial_number " "*.php"
replace_in_files " sn " " serial_number " "*.php"
replace_in_files "\.sn," ".serial_number," "*.php"
replace_in_files "warrantymonths" "warranty_months" "*.php"
replace_in_files "purchasedate" "purchase_date" "*.php"
replace_in_files "purchprice" "purchase_price" "*.php"
replace_in_files "dnsname" "dns_name" "*.php"
replace_in_files "maintenanceinfo" "maintenance_info" "*.php"
replace_in_files "ispart" "is_part" "*.php"
replace_in_files "\.hd " ".hard_drive " "*.php"
replace_in_files " hd " " hard_drive " "*.php"
replace_in_files "locationid" "location_id" "*.php"
replace_in_files "userid" "user_id" "*.php"
replace_in_files "rackmountable" "is_rack_mountable" "*.php"
replace_in_files "remadmip" "remote_admin_ip" "*.php"
replace_in_files "panelport" "panel_port" "*.php"
replace_in_files "switchport" "switch_port" "*.php"
replace_in_files "switchid" "switch_id" "*.php"
replace_in_files "rackid" "rack_id" "*.php"
replace_in_files "rackposition" "rack_position" "*.php"
replace_in_files "i\.status " "i.status_id " "*.php"
replace_in_files "i\.status," "i.status_id," "*.php"
replace_in_files "i\.status)" "i.status_id)" "*.php"
replace_in_files "cpuno" "cpu_count" "*.php"
replace_in_files "corespercpu" "cores_per_cpu" "*.php"
replace_in_files "rackposdepth" "rack_position_depth" "*.php"
replace_in_files "warrinfo" "warranty_info" "*.php"
replace_in_files "locareaid" "location_area_id" "*.php"

# =============================================================================
# COLUMN NAMES - SOFTWARE TABLE
# =============================================================================
echo ""
echo "=== Updating Software Table Columns ==="

replace_in_files "\.stitle" ".title" "*.php"
replace_in_files " stitle" " title" "*.php"
replace_in_files "\.sversion" ".version" "*.php"
replace_in_files " sversion" " version" "*.php"
replace_in_files "\.slicense " ".license_key " "*.php"
replace_in_files " slicense " " license_key " "*.php"
replace_in_files "\.scomments" ".comments" "*.php"
replace_in_files " scomments" " comments" "*.php"
replace_in_files "\.slicensetype" ".license_type" "*.php"
replace_in_files " slicensetype" " license_type" "*.php"
replace_in_files "\.scat" ".category" "*.php"
replace_in_files " scat" " category" "*.php"

# =============================================================================
# COLUMN NAMES - CONTRACTS TABLE
# =============================================================================
echo ""
echo "=== Updating Contracts Table Columns ==="

replace_in_files "c\.type " "c.contract_type_id " "*.php"
replace_in_files "c\.type," "c.contract_type_id," "*.php"
replace_in_files "c\.type)" "c.contract_type_id)" "*.php"
replace_in_files "parentid" "parent_contract_id" "*.php"
replace_in_files "c\.number" "c.contract_number" "*.php"
replace_in_files "totalcost" "total_cost" "*.php"
replace_in_files "contractorid" "contractor_id" "*.php"
replace_in_files "startdate" "start_date" "*.php"
replace_in_files "currentenddate" "end_date" "*.php"
replace_in_files "\.subtype" ".contract_subtype_id" "*.php"
replace_in_files " subtype" " contract_subtype_id" "*.php"
replace_in_files "vendorid" "vendor_id" "*.php"
replace_in_files "buyerid" "buyer_id" "*.php"

# =============================================================================
# COLUMN NAMES - INVOICES TABLE
# =============================================================================
echo ""
echo "=== Updating Invoices Table Columns ==="

replace_in_files "inv\.date " "inv.invoice_date " "*.php"
replace_in_files "inv\.date," "inv.invoice_date," "*.php"
replace_in_files "inv\.date)" "inv.invoice_date)" "*.php"
replace_in_files "i\.date " "i.invoice_date " "*.php"
replace_in_files "i\.date," "i.invoice_date," "*.php"
replace_in_files "i\.date)" "i.invoice_date)" "*.php"

# =============================================================================
# COLUMN NAMES - FILES TABLE
# =============================================================================
echo ""
echo "=== Updating Files Table Columns ==="

replace_in_files "f\.type " "f.file_type_id " "*.php"
replace_in_files "f\.type," "f.file_type_id," "*.php"
replace_in_files "f\.type)" "f.file_type_id)" "*.php"
replace_in_files "\.fname" ".filename_stored" "*.php"
replace_in_files " fname" " filename_stored" "*.php"
replace_in_files "\.uploader " ".uploader_username " "*.php"
replace_in_files " uploader " " uploader_username " "*.php"
replace_in_files "\.uploaddate" ".uploaded_at" "*.php"
replace_in_files " uploaddate" " uploaded_at" "*.php"
replace_in_files "f\.date " "f.updated_at " "*.php"
replace_in_files "f\.date," "f.updated_at," "*.php"
replace_in_files "f\.date)" "f.updated_at)" "*.php"
replace_in_files "\.filename " ".filename_original " "*.php"
replace_in_files "\.filesize" ".file_size" "*.php"
replace_in_files " filesize" " file_size" "*.php"
replace_in_files "\.ftype" ".file_type_id" "*.php"

# =============================================================================
# COLUMN NAMES - AGENTS TABLE
# =============================================================================
echo ""
echo "=== Updating Agents Table Columns ==="

replace_in_files "a\.title" "a.name" "*.php"
replace_in_files "\.contactinfo" ".contact_info" "*.php"
replace_in_files " contactinfo" " contact_info" "*.php"

# =============================================================================
# COLUMN NAMES - USERS TABLE
# =============================================================================
echo ""
echo "=== Updating Users Table Columns ==="

replace_in_files "\.userdesc" ".display_name" "*.php"
replace_in_files " userdesc" " display_name" "*.php"
replace_in_files "u\.pass " "u.password_hash " "*.php"
replace_in_files "u\.pass," "u.password_hash," "*.php"
replace_in_files "\.usertype" ".user_type" "*.php"
replace_in_files " usertype" " user_type" "*.php"
replace_in_files "cookie1" "remember_token" "*.php"

# =============================================================================
# COLUMN NAMES - TYPE TABLES
# =============================================================================
echo ""
echo "=== Updating Type Table Columns ==="

replace_in_files "\.typedesc" ".description" "*.php"
replace_in_files " typedesc" " description" "*.php"
replace_in_files "hassoftware" "has_software" "*.php"
replace_in_files "st\.statusdesc" "st.name" "*.php"
replace_in_files "ft\.typedesc" "ft.name" "*.php"

# =============================================================================
# COLUMN NAMES - LOCATIONS & RACKS
# =============================================================================
echo ""
echo "=== Updating Locations & Racks Columns ==="

replace_in_files "floorplanfn" "floor_plan_filename" "*.php"
replace_in_files "\.areaname" ".name" "*.php"
replace_in_files "r\.usize" "r.size_units" "*.php"
replace_in_files "r\.depth" "r.depth_mm" "*.php"
replace_in_files "revnums" "reverse_numbering" "*.php"

# =============================================================================
# TWIG TEMPLATES
# =============================================================================
echo ""
echo "=== Updating Twig Templates ==="

# Update template variable references
replace_in_files "\.stitle" ".title" "*.twig"
replace_in_files "\.sversion" ".version" "*.twig"
replace_in_files "\.scomments" ".comments" "*.twig"
replace_in_files "\.slicense" ".license_key" "*.twig"
replace_in_files "\.purchasedate" ".purchase_date" "*.twig"
replace_in_files "\.purchprice" ".purchase_price" "*.twig"
replace_in_files "\.userdesc" ".display_name" "*.twig"
replace_in_files "\.statusdesc" ".name" "*.twig"
replace_in_files "\.uploaddate" ".uploaded_at" "*.twig"
replace_in_files "\.filesize" ".file_size" "*.twig"
replace_in_files "\.itemtypeid" ".item_type_id" "*.twig"
replace_in_files "\.manufacturerid" ".manufacturer_id" "*.twig"
replace_in_files "\.locationid" ".location_id" "*.twig"

echo ""
echo "=== Migration Complete! ==="
echo ""
echo "Backup created: $BACKUP_DIR/code_backup.tar.gz"
echo "Review changes and test the application."
echo ""
echo "To restore from backup if needed:"
echo "  tar xzf $BACKUP_DIR/code_backup.tar.gz"
