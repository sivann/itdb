begin transaction;
alter table settings add column theme;   
update settings set dbversion=6;
commit;
