#!/bin/bash

#don't run this yourselves

echo ""
echo "Hope you remembered to:"
echo "-change dbversion in index.php"
echo "-apply dbupdates to pure.db"
echo "-run contrib/gittag"
echo ""

read -p "Press [Enter] to continue..."


#version=`grep version ../index.php |head -1|cut -d'"' -f2`
version=`cat ../VERSION`
#echo "updating version on database to $version"
#echo "update settings set version='$version';"|sqlite3 ../data/itdb.db 
#echo "update settings set version='$version';"|sqlite3 ../data/pure.db 

echo "Copying to /tmp"
rm -fr /tmp/itdb
cp -a ../../itdb /tmp
cd /tmp #just to be sure we are not someplace dangerous
cd /tmp/itdb/
cd translations
cat el.txt |cut -d'#' -f1 |sed 's/$/#/'>new.txt 
cd ..
rm -f data/files/*
rm -fr .git/
rm -f images/eoa*
rm data/itdb.db
chown www-data.www-data data/
chown www-data.www-data data/pure.db
chown www-data.www-data data/files

cd /tmp
echo "it is ready for tarring in /tmp/itdb. Do a:"
echo "tar zcf itdb-${version}.tar.gz itdb/ "
