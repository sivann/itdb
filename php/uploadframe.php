<?php 
require("../init.php");

if (!$authstatus) {
  echo "<b>$authmsg</b> <br>";
  echo "AuthStatus=$authstatus";
  exit;
}



?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<!-- (c) Spiros Ioannou 2008-2009 -->
<!-- sivann at gmail . com  -->
<html>
<head>
<title>ITDB - IT Items Database</title>
<link rel="stylesheet" href="../css/itdb.css" type="text/css">
<link type="text/css" href="../css/jquery-themes/blue2/jquery-ui-1.8.12.custom.css" rel="stylesheet" >
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style>
body {
  margin: 0;
  padding: 0;
  font-size:8pt;
  font-family:Verdana,Arial,Helvetica;

}
table{
  text-align:center;
  font-size:8pt;
  font-family:Verdana,Arial,Helvetica;
  margin: 0;
  padding: 0;
  margin-left:auto; margin-right:auto; /*center */
}

</style>
<script type="text/javascript" src="../js/jquery-1.6.1.min.js"></script> 
<script type="text/javascript" src="../js/jquery-ui-1.8.12.custom.min.js"></script> 
<script type="text/javascript" src="../js/jquery.maskedinput-1.3.js"></script> 
<script>
  jQuery(function($){
    $("#aa0").mask("99/99/9999",{placeholder:"_"});

    $( ".dateinp" ).datepicker({
            showOn: "button",
            buttonImage: "images/calendar.png",
            buttonImageOnly: true,
            changeMonth: true,
            changeYear: true,
            dateFormat: '<?php echo $datecalparam?>'

    });
    //$("input:checkbox").css({'width' : '20px'}); /* remove width of 150px from input of type checkbox */


  });
function rst() {
  //document.uplform.reset();
}
</script>
</head>
<body >
<?php 
$type=$_GET['type'];
isset($_GET['defdate'])?$defdate=$_GET['defdate']:$defdate="";
$id=$_GET['id'];

if (!is_numeric($id)) {
  echo "Cannot upload files to unsaved items.";
  exit;
}
if ($type == "invoice")
	$sql="SELECT * from filetypes WHERE id = 3 order by typedesc";
else
	$sql="SELECT * from filetypes WHERE id <> 3 order by typedesc";

$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $ftypes[$r['id']]=$r;
?>

<form name=uplform target="upload_target" id="file_upload_form" method="post" 
      enctype="multipart/form-data" 
      onsubmit="setTimeout('rst();',1000);"
      action="uploadframe_frame.php">
<input type="hidden" name="ftype" value="<?php echo $ftype?>"> 
<input type="hidden" name="assoctable" value="<?php echo $assoctable?>"> 
<input type="hidden" name="colname" value="<?php echo $colname?>"> 
<input type="hidden" name="id" value="<?php echo $id?>"> 
<table  style='width:380px;' border=0>
  <tr>
    <td class="tdt">File:</td>
    <td><input name="file" id="file" size="25" type="file"></td>
    <td><button type="submit" name="action" ><img src='../images/upload.png'><span style='color:black'>Upload</button></td>
  </tr>
  <tr>
    <td class="tdt">Title:</td>
    <td colspan=2><input name="title" id="title" class='mandatory' size="20" style='width:140px;' type="text">

    <input title='Issue Date' name="date" class='dateinp mandatory' value='<?php  echo $defdate ?>' id="aa0" size="10" type="text">
    <select class='mandatory' name='ftype'>
    <option value=''>Type</option>
<?php 
  if (count($ftypes)==1) $s="selected"; else $s=""; //if just 1 type, pre-select it

  foreach ($ftypes as $t) {

    $dbid=$t['id']; 
    $desc=$t['typedesc']; 
    echo "\t<option $s value='$dbid' title='$dbid'>".ucfirst($desc)."</option>\n";
  }
  echo "  </select>\n";

?>
    </td>
  </tr>
  <tr>
    <td style='text-align:center;border:1px solid #cecece;' colspan=3>
    <iframe id="upload_target" name="upload_target" src="uploadframe_frame.php"  frameborder="0"></iframe>
    </td>
  </tr>
</table>

</form>
</body>
</html>
