begin transaction;
alter table settings add column theme default 'default';
update settings set dbversion=7;
commit;
