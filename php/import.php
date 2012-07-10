<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}


if (!isset($_POST['nextstep']))
	$nextstep=0;
else
	$nextstep=$_POST['nextstep'];

//0: show import form
//1: show imported file
//2: do import


if (strlen($_FILES['file']['name'])>2) { //insert file
  $filefn=strtolower("import-".$_COOKIE["itdbuser"]."-".validfn($_FILES['file']['name']));
  $uploadedfile = "/tmp/".$filefn;
  $result = '';

  //Move the file from the stored location to the new location
  if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadedfile)) {
	  $result = "Cannot upload the file '".$_FILES['file']['name']."'"; 
	  if(!file_exists($uploaddir)) {
		  $result .= " : Folder doesn't exist.";
	  } elseif(!is_writable($uploaddir)) {
		  $result .= " : Folder not writable.";
	  } elseif(!is_writable($uploadedfile)) {
		  $result .= " : File not writable.";
	  }
	  $filefn = '';

	  echo "<br><b>ERROR: $result</b><br>";
  }
  else { //file ok
	  $nextstep=1;
	  print "<br>Uploaded  $uploadedfile<br>";
	}
}//insert file
?>


<?php if ($nextstep==0) { ?>
<table>
<form method=post name='importfrm' action='<?=$scriptname?>?action=<?=$action?>' enctype='multipart/form-data'>
<tr>
<tr><td>File:</td><td> <input name="file" id="file" size="25" type="file"></td></tr>
<tr><td>Delimeter:</td><td> <input size=1 type=text name='delim' value=';' maxlength=1></td></tr>
<tr><td>Skip 1st row:</td><td><select name=skip1st><option value=1>Yes</option><option value=0>No</option></select></td></tr>
<tr><td colspan=2><input type=submit value='Upload and inspect file'></td></tr>
</form>
<?php }?>

<?php if ($nextstep==1) { 
	$delim=$_POST['delim'];
	$imlines=file($uploadedfile);
?>

	<br><b> Please check imported file for consistency before submiting</b>:
	<div style='height:400px;overflow:auto'>
	<table class='brdr sortable'>
	<thead>
	</thead>
	<th>ID</th><th>Room</th><th>Owner</th><th>Status</th><th>DNS Hostname</th><th>IPv4</th><th>OS</th><th>Manufacturer</th><th>Model</th><th>SN</th><th>SN2</th><th>Comments</th></tr>
	<tbody>

<?
	$nfields=12;
	foreach ($imlines as $line_num => $line) {
			if ($line_num==0 && $_POST['skip1st']) 
				continue;

			$cols=explode($delim,$line);
			if (count($cols) != $nfields) {
				echo "Error: field count in line $line_num is ".count($cols).", $nfields expected";
				exit;
			}
			echo "<tr>";
			foreach ($cols as $col) {
				$col=trim($col);
				echo "<td>$col</td>";
			}
			echo "</tr>\n";
		    //echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) . "<br />\n";
	}
	echo "</tbody></table>\n";
	echo "</div>";
	$nextstep=2;
	?>
<form method=post name='importfrm' action='<?=$scriptname?>?action=<?=$action?>' enctype='multipart/form-data'>
	<input type=hidden name='nextstep' value='2'>
	<td colspan=2><input type=submit value='Import' ></td></tr>
</form>

<?
}

if ($nextstep==2) { 
	echo "<b>importing</b>";
}
?>


