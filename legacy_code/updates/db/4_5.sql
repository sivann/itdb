begin transaction;
alter table labelpapers add column qrtext;   
update settings set dbversion=5;
commit;
