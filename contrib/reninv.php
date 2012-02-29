<?php 
exit;

//rename all invoice files


chdir("..");

require("init.php");

echo "<pre>";

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */
  $sql="SELECT * from invoices ";
  $sth=db_execute($dbh,$sql);
  while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
    if (!strlen($r['fname'])) continue; //if no file associated continue
    $path_parts = pathinfo($r["fname"]);
    $invoiceext=$path_parts['extension'];
    $dymd=strftime("%Y%m%d",$r['date']);

//new filename
    $invoicefn="inv-".validfn($r["vendor"]).
    "-".  validfn($r["number"]).  "-$dymd-".
    $r['id'].".$invoiceext";
    $invoicefn=strtolower($invoicefn);

    echo "will move {$r['fname']} to $invoicefn\n";
    $ret=rename ($uploaddir.$r['fname'],$uploaddir.$invoicefn);
    if ($ret) {
	$sql="UPDATE invoices set fname='$invoicefn'  WHERE id='{$r['id']}'";
        db_exec($dbh,$sql);
    }
    else
      echo "<b>ERROR moving </b><br>\n";
}
