<?php 
exit;

//rename all license files


chdir("..");

require("init.php");

echo "<pre>";

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */
  $sql="SELECT * from software ";
  $sth=db_execute($dbh,$sql);
  while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
    if (!strlen($r['slicensefile'])) continue; //if no file associated continue
    $path_parts = pathinfo($r["slicensefile"]);
    $ext=$path_parts['extension'];
    $dymd=strftime("%Y%m%d",$r['purchdate']);

//new filename
    $slicensefilefn="lic-".validfn($r["scompany"]).
    "-".validfn($r["stitle"])."-".validfn($r["sversion"]).
    "-$dymd-".$r['id'].".$ext";
    $slicensefilefn=strtolower($slicensefilefn);

    echo "will move '{$r['slicensefile']}' to '$slicensefilefn'\n";
    $ret=rename ($uploaddir.$r['slicensefile'],$uploaddir.$slicensefilefn);
    if ($ret) {
	$sql="UPDATE software set slicensefile='$slicensefilefn'  WHERE id='{$r['id']}'";
        db_exec($dbh,$sql);
    }
    else
      echo "<b>ERROR moving </b><br>\n";
}
