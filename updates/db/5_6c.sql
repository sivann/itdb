begin transaction;
-- Create checksum types table and add initial values --
CREATE TABLE cksumtypes(id INTEGER PRIMARY KEY, cksumtype TEXT);
INSERT INTO cksumtypes VALUES (1, 'MD5');
INSERT INTO cksumtypes VALUES (2, 'SHA-1');
INSERT INTO cksumtypes VALUES (3, 'SHA-256');
INSERT INTO cksumtypes VALUES (4, 'SHA-512');
--Add columns to software table --
ALTER TABLE software ADD COLUMN cksumtype INTEGER;
ALTER TABLE software ADD COLUMN checksum TEXT;
commit;
