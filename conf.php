<?php 
/* To create new interface translations, create new translation files 
 * inside the "translations/" directory. The file format is:
 * english word#your translation
 * You must use a UTF-8 capable editor to edit this file
 */


/*for debugging translation files. Creates a $lang.missing.txt inside translations/ folder
 * containing (not-unique) missing strings for current language. Use when necessary since it can fill up
 * your disk if left running.
 */
$trans_showmissing=0;


/*************************************************************************/
/* You don't need to change those */
/*************************************************************************/
$dblogsize=1000; /* how many database log entries to keep */
$uploaddir="$scriptdir/data/files/"; /* how to access uploaded files from filesystem (absolute path, trailing slash)*/
$uploaddirwww="data/files/"; /* how to access uploaded files from web browser (may be relative)*/
$dbfile="$scriptdir/data/itdb.db"; /* sqlite db file */
$demomode=0;

?>
