-- Foreign Key Constraints for ITDB2 Database - SQLite Compatible Version
-- SQLite doesn't support ALTER TABLE ADD CONSTRAINT for foreign keys
-- Instead, we need to recreate tables or use CREATE INDEX for some constraints

-- Enable foreign keys for this session
PRAGMA foreign_keys = ON;

-- =============================================================================
-- NOTE: SQLite Limitation Workaround
-- =============================================================================
-- SQLite doesn't support adding named foreign key constraints to existing tables
-- The foreign key constraints need to be defined when the table is created
-- Since the tables already exist, we have two options:
-- 1. Recreate tables with foreign keys (risky - could lose data)
-- 2. Rely on application-level constraint enforcement + PRAGMA foreign_keys = ON
--
-- For now, let's verify the foreign key relationships work by testing some queries
-- The PRAGMA foreign_keys = ON we added to the code will enforce basic FK constraints
-- even without named constraints

-- =============================================================================
-- VERIFICATION QUERIES - Test if foreign key enforcement is working
-- =============================================================================

-- Test 1: Try to insert an item with invalid manufacturer (should fail)
-- This will fail if foreign keys are enforced:
-- INSERT INTO items (title, manufacturerid) VALUES ('Test Item', 99999);

-- Test 2: Try to delete an agent that has items (should fail if RESTRICT behavior)
-- This should be prevented by foreign key constraints:
-- DELETE FROM agents WHERE id IN (SELECT DISTINCT manufacturerid FROM items WHERE manufacturerid IS NOT NULL LIMIT 1);

-- =============================================================================
-- ALTERNATIVE: Create indexes to improve foreign key lookup performance
-- =============================================================================

-- Create indexes on foreign key columns to improve performance
CREATE INDEX IF NOT EXISTS idx_items_manufacturer ON items(manufacturerid);
CREATE INDEX IF NOT EXISTS idx_items_userid ON items(userid);
CREATE INDEX IF NOT EXISTS idx_items_itemtypeid ON items(itemtypeid);

CREATE INDEX IF NOT EXISTS idx_software_manufacturer ON software(manufacturerid);

CREATE INDEX IF NOT EXISTS idx_invoices_vendor ON invoices(vendorid);
CREATE INDEX IF NOT EXISTS idx_invoices_buyer ON invoices(buyerid);

CREATE INDEX IF NOT EXISTS idx_contracts_contractor ON contracts(contractorid);
CREATE INDEX IF NOT EXISTS idx_contracts_vendor ON contracts(vendorid);
CREATE INDEX IF NOT EXISTS idx_contracts_type ON contracts(type);
CREATE INDEX IF NOT EXISTS idx_contracts_parent ON contracts(parentid);

CREATE INDEX IF NOT EXISTS idx_agent_agent_type_agent ON agent_agent_type(agent_id);
CREATE INDEX IF NOT EXISTS idx_agent_agent_type_type ON agent_agent_type(agent_type_id);

CREATE INDEX IF NOT EXISTS idx_contract2item_contract ON contract2item(contractid);
CREATE INDEX IF NOT EXISTS idx_contract2item_item ON contract2item(itemid);

CREATE INDEX IF NOT EXISTS idx_contract2soft_contract ON contract2soft(contractid);
CREATE INDEX IF NOT EXISTS idx_contract2soft_software ON contract2soft(softid);

CREATE INDEX IF NOT EXISTS idx_item2soft_item ON item2soft(itemid);
CREATE INDEX IF NOT EXISTS idx_item2soft_software ON item2soft(softid);

CREATE INDEX IF NOT EXISTS idx_tag2item_tag ON tag2item(tagid);
CREATE INDEX IF NOT EXISTS idx_tag2item_item ON tag2item(itemid);

CREATE INDEX IF NOT EXISTS idx_contractevents_contract ON contractevents(contractid);
CREATE INDEX IF NOT EXISTS idx_actions_item ON actions(itemid);

-- =============================================================================
-- VERIFICATION: Check that foreign keys are enabled
-- =============================================================================
PRAGMA foreign_keys;

-- =============================================================================
-- IMPORTANT NOTES:
-- =============================================================================
--
-- 1. FOREIGN KEY ENFORCEMENT:
--    - SQLite will enforce foreign keys with PRAGMA foreign_keys = ON
--    - This prevents inserting invalid foreign key values
--    - This prevents deleting referenced records
--    - However, it doesn't provide named constraints or custom CASCADE behavior
--
-- 2. CONSTRAINT BEHAVIOR:
--    - Default SQLite FK behavior is similar to RESTRICT
--    - Records cannot be deleted if they are referenced
--    - Invalid foreign key values cannot be inserted
--
-- 3. PERFORMANCE:
--    - Indexes created above will improve foreign key lookup performance
--    - These are especially important for large tables
--
-- 4. FUTURE IMPROVEMENTS:
--    - Could recreate tables with proper FK constraints if needed
--    - Could implement application-level cascade behavior
--    - Could add triggers for custom constraint behavior
--
-- 5. VERIFICATION:
--    - Test foreign key enforcement by trying to insert invalid data
--    - Test deletion prevention by trying to delete referenced records
--    - Monitor application for constraint violation errors

-- Re-enable foreign keys to make sure they're active
PRAGMA foreign_keys = ON;