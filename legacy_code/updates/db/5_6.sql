begin transaction;
alter table labelpapers add column wantnotext;
alter table labelpapers add column wantraligntext;
update settings set dbversion=6;
commit;
