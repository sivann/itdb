begin transaction;
alter table settings add column usedns integer default 0;
alter table settings add column dns_servers;
alter table settings add column dns_suffix;
alter table settings add column dns_autoupdate integer default 0;
commit;
