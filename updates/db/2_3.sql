begin transaction;
update items set status=0 where status=-1;
update settings set dbversion=3;
commit;
