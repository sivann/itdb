begin transaction;
update tag2item set itemid=null where itemid not in (select id from items);
update settings set dbversion=2;
commit;
