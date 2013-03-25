<SCRIPT LANGUAGE="JavaScript"> 

$(document).ready(function() {

   $("#langsel").change(function() {
     $('#mainform').submit();

   });


    $('#savebtn').click(function(e) {
      $("#selitemsfrm").attr("action", "php/printlabels.php");
      document.transfrm.dosave.value=1;
      $('#mainform').submit();
    });

});


</SCRIPT>
<?php 


if (!isset($initok)) {echo "do not run this script directly";exit;}
/* Spiros Ioannou 2009-2010 , sivann _at_ gmail.com */

if (isset($_POST['dosave'])&&$_POST['dosave']==1) { //if we came from a post (save), save translation 
  $lang=$_POST['lang'];
  $x=array();
  for ($i=0;$i<$_POST['trcount'];$i++) {
    $n1="tr1_$i";
    $n2="tr2_$i";
    $x[]=html_entity_decode($_POST[$n1],ENT_QUOTES,'UTF-8')."#".html_entity_decode($_POST[$n2],ENT_QUOTES,'UTF-8')."\n";
  }
  if (FALSE===file_put_contents("translations/".$lang.".txt", $x)) {
    echo "Error writing translations/".$lang.".txt";
  }

}//save pressed
elseif (isset($_POST['newlang'])&& strlen($_POST['newlang'])) { 
  $newfile=$_POST['newlang'].".txt";
  $newfile=validfn($newfile);

  if (file_exists("translations/$newfile")) {
      echo "<b>$newfile already exists, not overwritten!</b>";
  }
  elseif (!copy("translations/new.txt", "translations/$newfile")) {
      echo "<br>failed to copy $newfile to $scriptdir/translations/$newfile\n";
  }
  else
	  echo "<b><br>copied  $newfile to $scriptdir/translations/$newfile\n</b>";

}

/////////////////////////////
//// display data 

$sql="SELECT * FROM settings";
$sth=$dbh->query($sql);
$settings=$sth->fetchAll(PDO::FETCH_ASSOC);
$settings=$settings[0];

if (isset($_POST['lang'])) 
  $lang=$_POST['lang'];
else
  $lang=$settings['lang'];


echo "\n<h1>".t("Translations")."</h1>\n";
?>
<table>
<tr><td>
How to translate: 
<ol>
<li>Choose translation language from the list below</li>
<li>Translate the strings</li>
<li>Save</li>
</ol>
</td>
<td style='padding-left:80px;'>
How to add more languages:
<?php echo "\n<form method=post  action='$scriptname?action=$action' enctype='multipart/form-data'  name='newlangfrm'>\n"; ?>
<ul>
<li>Enter new language name (e.g. fr):<input size=2 type='text' name='newlang'></li>
<li><input type=submit value='Create new empty translation'></li>
</ul>
<input type=hidden name='action' value='<?php echo $action ?>'>
</form>
</td></tr>
</table>

<?php echo "\n<form id='mainform' method=post  action='$scriptname?action=$action' enctype='multipart/form-data'  name='transfrm'>\n"; ?>

<table class="tbl2" >
<tr><td class="tdt"><?php te("Language")?></td><td>
<select  id='langsel' name='lang'>
  <?php if ($lang=="en") $s="SELECTED"; else $s="" ?>
  <option <?php echo $s?> value=''>en</option>
  <?php
  $tfiles=scandir("translations/");
  foreach ($tfiles as $f) {
    if (strstr($f,"txt") && (!strstr($f,"new")) && (!strstr($f,"missing"))) {
      $bf=basename($f,".txt");
      if ($lang=="$bf") $s="SELECTED"; else $s="" ;
      echo "<option $s value='$bf'>$bf</option>\n";
    }
  }
  ?>
</select>
</td>
</tr>

<tr> <td colspan=2> 
<?php
if (strlen($lang)&&($lang!="en"))
	echo "<button id='savebtn' type='submit'><img src='images/save.png' alt='Save'>".t(Save)."</button> </td> </tr>\n";

$fn="translations/$lang.txt";

if (strlen($lang) && ($lang !="en")) {
	if (is_readable($fn) && (($handle = fopen($fn, "r")) !== FALSE)) {
		while (($data = fgetcsv($handle, 1000, "#")) !== FALSE) {
			$num = count($data);
			$row++;
			if ($num<2)  continue;
			if ($num>2)  echo "<p style='display:inline;background-color:white;color:red'> Error in $fn, row $row: ($num fields found, 2 expected) <br /></p>\n";
			$tt1[]=$data[0];
			$tt2[]=$data[1];
		}
		fclose($handle);
	}
	else {
		echo "Could not open $fn";
	}
}

?>

<?php
$nt=count($tt1);
for ($i=0;$i<$nt;$i++) {
	echo "<tr><td style='width:30%;border:1px solid #ccc;text-align:left'>".$tt1[$i]."</td>".
		"<td><input name='tr2_$i' size=100 value='".htmlspecialchars($tt2[$i], ENT_QUOTES,'UTF-8')."'>".
		"<input  name='tr1_$i' type=hidden value='".htmlspecialchars($tt1[$i], ENT_QUOTES,'UTF-8')."'></td></tr>\n";
}

if ($nt) {

	//read untranslated english strings
	$nfn="translations/new.txt";
	if (is_readable($nfn) && (($handle = fopen($nfn, "r")) !== FALSE)) {
		$eng=array();
		while (($data = fgetcsv($handle, 1000, "#")) !== FALSE) {
			$eng[]=$data[0];
		}
	}

	//find which english words are missing from current translation file
	foreach ($eng  as $engstr){
		if (!in_array($engstr,$tt1)) {
			$nt++;
			echo "<tr><td style='border:1px solid #ee0000'>".$engstr."</td>".
				"<td><input name='tr2_".($nt-1)."' size=100 value=''>".
				"<input  name='tr1_".($nt-1)."' type=hidden value='".htmlspecialchars($engstr)."'></td></tr>\n";
		}
	}

}
echo "<input name='trcount' type=hidden value='$nt'>";


?>




</table>
<input type=hidden name='action' value='<?php echo $action ?>'>
<input type=hidden name='dosave' value=''>
</form>

</body>
</html>
