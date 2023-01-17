<?php

$initok=1;
require("init.php");
if (!isset($authstatus) || (!$authstatus)) {echo "<big><b>Not logged in</b></big><br>";exit(0);}

$content_disp = ( preg_match('/MSIE ([0-9].[0-9]{1,2})/', $HTTP_USER_AGENT) == 'IE') ? 'inline' : 'attachment';
$now = date("Ymd");

header('Content-Type: application/octet-stream');
header('Content-Disposition: ' . $content_disp . "; filename=\"itdb-$now.db\"");
header('Content-Transfer-Encoding: binary');
header('Pragma: no-cache');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Expires: 0');
ob_clean();
flush();
readfile("data/itdb.db");
exit;
?>
