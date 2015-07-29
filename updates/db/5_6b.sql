begin transaction;
CREATE TABLE invoices_tmp ("id" INTEGER PRIMARY KEY ,"number" ,"date" integer,vendorid integer,buyerid integer,inv_total TEXT, description);
INSERT INTO invoices_tmp SELECT id, "number", "date", vendorid, buyerid, NULL, description FROM invoices;
DROP TABLE invoices;
ALTER TABLE invoices_tmp RENAME TO invoices;
commit;
