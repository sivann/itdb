begin transaction;
alter table settings add column ldap_getusers;
alter table settings add column ldap_getusers_filter;
update items set status=0 where status=-1;
update settings set dbversion=4;
commit;
