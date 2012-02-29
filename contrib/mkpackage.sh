#!/bin/bash

#don't run this yourselves

version=`grep version ../itdb.php |head -1|cut -d'"' -f2`
echo "updating version on database to $version"
echo "update settings set version='$version';"|sqlite3 ../data/itdb.db 
echo "update settings set version='$version';"|sqlite3 ../data/pure.db 

rm -fr /tmp/itdb
cp -a ../../itdb /tmp
cd /tmp #just to be sure we are not someplace dangerous
cd /tmp/itdb/
cd translations
cat el.txt |cut -d'#' -f1 |sed 's/$/#/'>new.txt 
cd ..
rm -f data/files/*
rm -f images/eoa*
rm data/itdb.db
#cp conf.php.sample conf.php
cd /tmp
echo "it is ready for tarring in /tmp/itdb"

echo "you may do a  tar zcvf itdb-${version}.tar.gz itdb/ in there"
