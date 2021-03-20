begin transaction;
CREATE TABLE users2soft (userid INTEGER, softid INTEGER, instdate INTEGER);
update settings set dbversion=7;
commit;
