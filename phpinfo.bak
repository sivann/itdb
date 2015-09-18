<?php  

try {
  $dbh = new PDO("sqlite:/tmp/test.db");
} 
catch (PDOException $e) {
  print "Open test database Error!: " . $e->getMessage() . "<br>";
}
$error = $dbh->errorInfo();
if($error[0] && isset($error[2]))  echo "Error 00: ".$error[2]."<br>";



$attributes = array( "CLIENT_VERSION", "SERVER_VERSION");

foreach ($attributes as $val) {
    echo "<b>PDO::ATTR_$val: ";
    echo $dbh->getAttribute(constant("PDO::ATTR_$val")) . "</b><br>\n";
}


$modules=parsePHPModules();

// prints e.g. 'Current PHP version: 4.1.1'
echo "<br>\n<b>";
echo 'Current PHP version: ' . phpversion();
echo "<br>";

echo "PDO_SQLITE version:".$modules['pdo_sqlite']['SQLite Library'];
echo "</b>";
echo "<br>";
echo "<br>";

phpinfo();

function parsePHPModules(){
 ob_start();
 phpinfo(INFO_MODULES);
 $s = ob_get_contents();
 ob_end_clean();

 $s = strip_tags($s,'<h2><th><td>');
 $s = preg_replace('/<th[^>]*>([^<]+)<\/th>/',"<info>\\1</info>",$s);
 $s = preg_replace('/<td[^>]*>([^<]+)<\/td>/',"<info>\\1</info>",$s);
 $vTmp = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/',$s,-1,PREG_SPLIT_DELIM_CAPTURE);
 $vModules = array();
 for ($i=1;$i<count($vTmp);$i++) {
  if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/',$vTmp[$i],$vMat)) {
   $vName = trim($vMat[1]);
   $vTmp2 = explode("\n",$vTmp[$i+1]);
   foreach ($vTmp2 AS $vOne) {
   $vPat = '<info>([^<]+)<\/info>';
   $vPat3 = "/$vPat\s*$vPat\s*$vPat/";
   $vPat2 = "/$vPat\s*$vPat/";
   if (preg_match($vPat3,$vOne,$vMat)) { // 3cols
     $vModules[$vName][trim($vMat[1])] = array(trim($vMat[2]),trim($vMat[3]));
   } elseif (preg_match($vPat2,$vOne,$vMat)) { // 2cols
     $vModules[$vName][trim($vMat[1])] = trim($vMat[2]);
   }
   }
  }
 }
 return $vModules;
}



?>
