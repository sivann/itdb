<?php

$initok=1;
require("init.php");
if (!isset($authstatus) || (!$authstatus)) {echo "<big><b>Not logged in</b></big><br>";exit(0);}


header('Content-Type: application/x-gzip');
$content_disp = ( ereg('MSIE ([0-9].[0-9]{1,2})', $HTTP_USER_AGENT) == 'IE') ? 'inline' : 'attachment';
$now = date("Ymd");
header('Content-Disposition: ' . $content_disp . "; filename=\"itdb-$now.tar.gz\"");
header('Pragma: no-cache');
header('Expires: 0');

passthru( "tar czf - ../itdb");
?>
