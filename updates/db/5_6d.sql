begin transaction;
-- Create Operating System types table and add inital values --
CREATE TABLE ostypes(id INTEGER PRIMARY KEY, ostype TEXT);
INSERT INTO ostypes VALUES (1, "Windows");
INSERT INTO ostypes VALUES (2, "Ubuntu");
INSERT INTO ostypes VALUES (3, "MAC");
--Add columns to software table --
ALTER TABLE software ADD COLUMN ostype INTEGER;
ALTER TABLE software ADD COLUMN locationurl TEXT;
commit;