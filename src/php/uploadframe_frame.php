<?php 
require('../init.php');
?>
<html>
<head>
<style>
body {
  font-family:Lucida Console, Courier New, Courier;
  background-color:white;
  font-size:8pt;
  color:#606060;
  margin:0;
  padding:0;
}
</style>
</head>
<body>
<?php
include('upload_inc.php');

/*returns: array(filename,errstr);*/
$errstr="";
if (isset($id)&& is_numeric($id)) {
  $ret=upload('file',$uploaddir,$ftype,$title,$date,$id,$assoctable,$colname,$userdata[0]['username']); //$assoctable: software2file, colname:softwareid
  $fn=$ret[0];
  $errstr=$ret[1];
}

if (strlen($errstr)){
  echo "<span style='color:red;font-weight:bold;'>ERROR:$errstr</span>";
}
elseif (strlen($fn)) 
  echo "Uploaded: $fn";
else 
  echo "Don't forget: press the <b>'UPLOAD'</b> button (not save) to complete the upload!";

/*
echo "<pre>\n";
echo "_FILES";
print_r($_FILES);
echo "\n_REQUEST\n";
print_r($_REQUEST);
*/

?>
</body>
</html>
