-- Foreign Key Constraints for ITDB2 Database
-- Based on analysis of legacy code relationships
--
-- IMPORTANT NOTES AND CONCERNS:
-- 1. This will enforce referential integrity but may cause issues if there's orphaned data
-- 2. Some relationships might be optional (nullable) - need to verify data first
-- 3. Legacy bitwise agent types (type&1, type&2, etc.) suggest polymorphic relationships
-- 4. Need to check if all referenced IDs actually exist before applying constraints

-- Disable foreign keys temporarily for modifications
PRAGMA foreign_keys = OFF;

-- =============================================================================
-- AGENT RELATIONSHIPS
-- =============================================================================

-- Items -> Agents (manufacturer relationship)
-- From: "WHERE agents.id=items.manufacturerid"
-- RESTRICT: Prevent deleting agents that manufacture items
-- Concern: Items might have manufacturerid=0 or NULL for unknown manufacturers
ALTER TABLE items
ADD CONSTRAINT fk_items_manufacturer
FOREIGN KEY (manufacturerid) REFERENCES agents(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Software -> Agents (manufacturer relationship)
-- From: "WHERE manufacturerid='$agent_id'" in software queries
-- RESTRICT: Prevent deleting agents that manufacture software
-- Note: Software table has manufacturerid field referencing agents
ALTER TABLE software
ADD CONSTRAINT fk_software_manufacturer
FOREIGN KEY (manufacturerid) REFERENCES agents(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Invoices -> Agents (vendor relationship)
-- From: "FROM invoices WHERE vendorid='$agent_id'"
-- RESTRICT: Prevent deleting agents that have invoices as vendors
-- Invoice vendor (seller) must be an agent
ALTER TABLE invoices
ADD CONSTRAINT fk_invoices_vendor
FOREIGN KEY (vendorid) REFERENCES agents(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Invoices -> Agents (buyer relationship)
-- From: "WHERE invoices.buyerid='$agent_id' AND agents.id=invoices.vendorid"
-- RESTRICT: Prevent deleting agents that have invoices as buyers
-- Invoice buyer can be an agent (for B2B transactions)
ALTER TABLE invoices
ADD CONSTRAINT fk_invoices_buyer
FOREIGN KEY (buyerid) REFERENCES agents(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Contracts -> Agents (contractor relationship)
-- From: "FROM contracts WHERE contractorid='$agent_id'"
-- RESTRICT: Prevent deleting agents that are contractors on contracts
-- Based on recent code changes, contractorid references agents, not users
ALTER TABLE contracts
ADD CONSTRAINT fk_contracts_contractor
FOREIGN KEY (contractorid) REFERENCES agents(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Contracts -> Agents (vendor relationship)
-- RESTRICT: Prevent deleting agents that are vendors on contracts
-- Contract vendor/supplier relationship
ALTER TABLE contracts
ADD CONSTRAINT fk_contracts_vendor
FOREIGN KEY (vendorid) REFERENCES agents(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- =============================================================================
-- USER RELATIONSHIPS
-- =============================================================================

-- Items -> Users (assigned user)
-- From: "WHERE userid=$user_id" - items assigned to users
-- RESTRICT: Prevent deleting users who have assigned items
ALTER TABLE items
ADD CONSTRAINT fk_items_user
FOREIGN KEY (userid) REFERENCES users(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- =============================================================================
-- TYPE/CATEGORY RELATIONSHIPS
-- =============================================================================

-- Items -> Item Types
-- From: "WHERE items.itemtypeid=itemtypes.id"
-- Every item should have a valid item type
ALTER TABLE items
ADD CONSTRAINT fk_items_itemtype
FOREIGN KEY (itemtypeid) REFERENCES itemtypes(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Contracts -> Contract Types
-- RESTRICT: Prevent deleting contract types that are in use
-- Contract type classification
ALTER TABLE contracts
ADD CONSTRAINT fk_contracts_type
FOREIGN KEY (type) REFERENCES contracttypes(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- =============================================================================
-- HIERARCHICAL RELATIONSHIPS
-- =============================================================================

-- Contracts -> Contracts (parent-child)
-- RESTRICT: Prevent deleting parent contracts that have child contracts
-- Self-referencing for contract hierarchies
ALTER TABLE contracts
ADD CONSTRAINT fk_contracts_parent
FOREIGN KEY (parentid) REFERENCES contracts(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- =============================================================================
-- MANY-TO-MANY RELATIONSHIP TABLES
-- =============================================================================

-- Agent-AgentType pivot table
-- Links agents to their types (replaces bitwise type field)
-- CASCADE: When agent is deleted, remove its type associations
-- RESTRICT: Prevent deleting agent types that are in use
ALTER TABLE agent_agent_type
ADD CONSTRAINT fk_agent_agent_type_agent
FOREIGN KEY (agent_id) REFERENCES agents(id)
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE agent_agent_type
ADD CONSTRAINT fk_agent_agent_type_type
FOREIGN KEY (agent_type_id) REFERENCES agent_types(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Contract-Item associations
-- From: contract2item table structure
-- RESTRICT: Prevent deleting contracts/items that have associations
-- Note: This means you must first remove associations before deleting entities
ALTER TABLE contract2item
ADD CONSTRAINT fk_contract2item_contract
FOREIGN KEY (contractid) REFERENCES contracts(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE contract2item
ADD CONSTRAINT fk_contract2item_item
FOREIGN KEY (itemid) REFERENCES items(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Contract-Software associations
-- From: contract2soft table structure
-- RESTRICT: Prevent deleting contracts/software that have associations
ALTER TABLE contract2soft
ADD CONSTRAINT fk_contract2soft_contract
FOREIGN KEY (contractid) REFERENCES contracts(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE contract2soft
ADD CONSTRAINT fk_contract2soft_software
FOREIGN KEY (softid) REFERENCES software(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Item-Software associations
-- From: "FROM software,item2soft WHERE item2soft.itemid=items.id AND software.id=item2soft.softid"
-- RESTRICT: Prevent deleting items/software that have associations
ALTER TABLE item2soft
ADD CONSTRAINT fk_item2soft_item
FOREIGN KEY (itemid) REFERENCES items(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE item2soft
ADD CONSTRAINT fk_item2soft_software
FOREIGN KEY (softid) REFERENCES software(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Tag-Item associations
-- From: "FROM tags,tag2item WHERE tag2item.itemid=items.id AND tags.id=tag2item.tagid"
-- RESTRICT: Prevent deleting tags/items that have associations
ALTER TABLE tag2item
ADD CONSTRAINT fk_tag2item_tag
FOREIGN KEY (tagid) REFERENCES tags(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE tag2item
ADD CONSTRAINT fk_tag2item_item
FOREIGN KEY (itemid) REFERENCES items(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- =============================================================================
-- EVENT/LOG RELATIONSHIPS
-- =============================================================================

-- Contract Events -> Contracts
-- From: "WHERE contractid='$id'" in contractevents
-- RESTRICT: Prevent deleting contracts that have events/history
ALTER TABLE contractevents
ADD CONSTRAINT fk_contractevents_contract
FOREIGN KEY (contractid) REFERENCES contracts(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- Actions -> Items
-- From: "WHERE itemid=$itemid" in actions table
-- RESTRICT: Prevent deleting items that have action history
-- Assuming actions are related to items based on query pattern
ALTER TABLE actions
ADD CONSTRAINT fk_actions_item
FOREIGN KEY (itemid) REFERENCES items(id)
ON DELETE RESTRICT ON UPDATE CASCADE;

-- =============================================================================
-- FILE RELATIONSHIPS (if files table exists)
-- =============================================================================

-- Note: Files can be associated with multiple entity types (polymorphic)
-- May need separate constraints for different file association tables
-- This needs verification of actual file table structure

-- Re-enable foreign keys
PRAGMA foreign_keys = ON;

-- =============================================================================
-- MAJOR CONCERNS AND AMBIGUITIES:
-- =============================================================================
--
-- 1. DATA INTEGRITY: Before applying these constraints, run queries to check for:
--    - Orphaned records (child records with non-existent parent IDs)
--    - NULL vs 0 values (some fields might use 0 instead of NULL for "no relation")
--    - Invalid ID references that would cause constraint violations
--
-- 2. AGENT TYPE MIGRATION: The bitwise agent types (type&1, type&2, etc.) suggest:
--    - type&1  = Vendor
--    - type&2  = Software Manufacturer
--    - type&4  = Hardware Manufacturer
--    - type&8  = Buyer
--    - type&16 = Contractor
--    Need to ensure agent_agent_type table is properly populated before removing legacy type field
--
-- 3. POLYMORPHIC RELATIONSHIPS: Some tables might reference multiple entity types:
--    - Files might be associated with items, software, contracts, etc.
--    - May need discriminator columns or separate junction tables
--
-- 4. NULLABLE FIELDS: Many relationships might be optional:
--    - Items without manufacturers (manufacturerid = NULL)
--    - Contracts without specific contractors
--    - Software without known manufacturers
--    Using SET NULL for most ON DELETE actions to preserve data
--
-- 5. CASCADE BEHAVIOR (Updated to be more restrictive):
--    - Using RESTRICT for most relationships to prevent accidental data loss
--    - Using CASCADE only for agent type assignments (when agent is deleted)
--    - Entities cannot be deleted if they are referenced by other records
--    - This requires explicit cleanup of associations before deletion
--
-- 6. MISSING RELATIONSHIPS: May need additional constraints for:
--    - Location/rack relationships
--    - User permissions/roles
--    - File upload tracking
--    - Invoice line items
--
-- RECOMMENDED APPROACH:
-- 1. First run data integrity checks
-- 2. Clean up orphaned data
-- 3. Apply constraints incrementally
-- 4. Test thoroughly before deploying