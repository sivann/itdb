<?php 
/* Spiros Ioannou 2009 , sivann _at_ gmail.com */

require("../init.php");

if ($itemid=="new") {
  te("Cannot add log entries to unsaved items.");
  exit;
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" >

<script>
function filltoday()
{
var mydate= new Date()
var theyear=mydate.getFullYear()
var themonth=mydate.getMonth()+1
var thetoday=mydate.getDate()

var x=document.getElementById('newdate').value;

  if (x.length==0) {
<?php if ($settings['dateformat']=="ymd") {?>
    document.getElementById('newdate').value=theyear+"-"+themonth+"-"+thetoday;
<?php  } elseif ($settings['dateformat']=="dmy") {?>
    document.getElementById('newdate').value=thetoday+"/"+themonth+"/"+theyear;
<?php  } else {?>
    document.getElementById('newdate').value=themonth+"/"+thetoday+"/"+theyear;
<?php  }?>

  }

}

</script>
</head>

<body bgcolor="#ffffff">

<link rel="stylesheet" href="<?php echo $wscriptdir?>/css/itdb.css" type="text/css">
<?php 
if (!$authstatus) {
  echo "<b>$authmsg</b> <br>";
  echo "AuthStatus=$authstatus";
  exit;
}

//echo "<pre>"; print_r($_GET); print_r($_POST);


$formvars=array("id", "actiondate","description","invoiceinfo");

//if came here from a form post, update db with new values
if (isset($_POST['description'])) {
  $nrows=count($_POST['id']); //number of rows

  for ($rn=0;$rn<$nrows;$rn++) {
    $id=$_POST['id'][$rn];
      if (($id == "new") && (strlen($_POST['description'][$rn])>1) )  {//new item -- insert
	if (empty($_POST['actiondate'][$rn])) $adate=time();
	else $adate=ymd2sec($_POST['actiondate'][$rn]);
	$sql="INSERT into actions ".
          "(itemid, actiondate,description,invoiceinfo,isauto,entrydate) ".
	  " values (".
	  "$itemid,".
	  $adate.",".
	  "'".($_POST['description'][$rn])."',".
	  "'".($_POST['invoiceinfo'][$rn])."',0,".time().")";
      }
      elseif ($id!="new"){ //existing item -- update
	$sql="UPDATE actions set ".
	  " actiondate=".ymd2sec($_POST['actiondate'][$rn]).", ".
	  " description='".($_POST['description'][$rn])."', ".
	  " invoiceinfo='".($_POST['invoiceinfo'][$rn])."', ".
	  " isauto=0".
	  " WHERE id=$id";
      }
      else {continue;}


    $r=db_exec($dbh,$sql);
  }//for
} //if


if (!isset ($_GET['itemid']) || !strlen($_GET['itemid'])) {echo "$scriptname: wrong arguments";exit;}
$itemid=$_GET['itemid'];

$sql="SELECT * from actions where itemid=$itemid order by actiondate";

/// make db query
$sth=db_execute($dbh,$sql);

//display "detach" icon if inside the frame
if (!isset($_GET['detached']))
  $det="<a target=_blank href='$scriptname?itemid=$itemid&amp;detached=1'>".
  "<img src='$wscriptdir/images/detach.gif' title='Show in new window' border=0 align=absmiddle></a></caption>\n";
else 
  $det="";

echo "\n<form method=post name='actionaddfrm'>\n";
echo "<table align=center class=brdr border=0>\n";
echo "\n<caption><h2>Item Log  (Item $itemid)</h2>$det</caption>\n";
echo "\n<tr><th>&nbsp;</th><th>Action Date</th><th>Description</th><th>Invoice info</th><th>Entry Date</th></tr>\n";



$i=0;
/// print actions list
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
$i++;
    $d=strlen($r['actiondate'])?date($dateparam,$r['actiondate']):"-"; //seconds to d/m/y
    $ed=strlen($r['entrydate'])?date($dateparam,$r['entrydate']):"-"; //seconds to d/m/y
  if ($r['isauto']) {
    echo "\n<tr>\n";
    echo "<td>{$r['id']}</td>\n";
    echo "<td>$d</td>\n";
    echo "<td>{$r['description']}</td>\n";
    echo "<td>{$r['invoiceinfo']}</td>\n";
    echo "<td>$ed</td>\n";
    echo "</tr>\n\n";
  }
  else {
    echo "\n<tr>\n";
    echo "<td><input type=hidden name='id[]' value='".$r['id']."' readonly size=3>{$r['id']}</td>\n";
    echo "<td><input title='d/m/y or yyyy' size=9 type=text name='actiondate[]' value=\"".$d."\"></td>\n";
    echo "<td><textarea wrap='soft' class=tarea3  name='description[]'>".$r['description']."</textarea></td>\n";
    echo "<td><input size=10 type=text name='invoiceinfo[]' value=\"".$r['invoiceinfo']."\"></td>\n";
    echo "<td>$ed</td>\n";
    echo "</tr>\n\n";
  }
}

//empty line to add new items at bottom
echo "<tr><td><input type=text name='id[]' value='new' readonly size=3></td>\n";
echo "<td><input title='d/m/y or yyyy' size=9 type=text id='newdate' onclick='filltoday();' name='actiondate[]'></td>\n";
echo "<td><textarea wrap='soft' class=tarea3  name='description[]'>".$r['description']."</textarea></td>\n";
echo "<td><input size=10 type=text name='invoiceinfo[]' ></td>\n";
echo "<td></td>\n";

echo "<tr><td colspan=4><input value='Save Action Log' type=submit></td></tr>\n";

?>
</table>
</body>
</html>
