<?php 
//serve XHR to update tag2software table 
require("../init.php");

//addtag, removetag
$addtag=$_POST['addtag'];
$removetag=$_POST['removetag'];
$softwareid=$_GET['id'];
if (!is_numeric($softwareid)) {
  echo "tag2software_ajaxedit:invalid softwareid ($softwareid)";exit;
}

$result="";

if (isset($_POST['addtag']) && strlen($_POST['addtag'])) {
    $addtag=trim($_POST['addtag']);
    $tagid=tagname2id($addtag);
    if (!is_numeric($tagid)) { //new tag, add it
      $sql="INSERT INTO tags (name) values ('$addtag')";
      $sth=db_execute($dbh,$sql);
      $result.="added new tag: $addtag<br>";
      $tagid=tagname2id($addtag); //re-get id
    }
    //make association
    if (is_numeric($tagid)) { //make association
      $sql="INSERT INTO tag2software (tagid,softwareid) values ($tagid,$softwareid)";
      $sth=db_execute($dbh,$sql);
      $result.="associated tag: $addtag<br>";
    }
    else 
      $result.= "error: cannot find added tag!<br>";
}
elseif (isset($_POST['removetag']) && strlen($_POST['removetag'])) {
    $removetag=trim($_POST['removetag']);
    $tagid=tagname2id($removetag);
    if (is_numeric($tagid)) { //make association
      $sql="DELETE from tag2software where tag2software.tagid=$tagid AND tag2software.softwareid=$softwareid";
      $sth=db_exec($dbh,$sql);
      $result.="de-associated tag:$removetag<br>";
    }
    else 
      $result.="error: cannot find requested tag for removal!<br>";

}
echo $result;
?>
