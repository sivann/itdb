begin transaction;
alter table settings add column useldap integer default 0;
alter table settings add column ldap_server;
alter table settings add column ldap_dn;
update items set status=0 where status=-1;
update settings set dbversion=3;
commit;
