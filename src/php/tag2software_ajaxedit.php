<?php 
//serve XHR to update tag2software table 
require("../init.php");

//addtag, removetag
$addtag=$_POST['addtag'];
$removetag=$_POST['removetag'];
$software_id=$_GET['id'];
if (!is_numeric($software_id)) {
  echo "tag2software_ajaxedit:invalid software_id ($software_id)";exit;
}

$result="";

if (isset($_POST['addtag']) && strlen($_POST['addtag'])) {
    $addtag=trim($_POST['addtag']);
    $tag_id=tagname2id($addtag);
    if (!is_numeric($tag_id)) { //new tag, add it
      $sql="INSERT INTO tags (name) values ('$addtag')";
      $sth=db_execute($dbh,$sql);
      $result.="added new tag: $addtag<br>";
      $tag_id=tagname2id($addtag); //re-get id
    }
    //make association
    if (is_numeric($tag_id)) { //make association
      $sql="INSERT INTO software_tags (tag_id,software_id) values ($tag_id,$software_id)";
      $sth=db_execute($dbh,$sql);
      $result.="associated tag: $addtag<br>";
    }
    else 
      $result.= "error: cannot find added tag!<br>";
}
elseif (isset($_POST['removetag']) && strlen($_POST['removetag'])) {
    $removetag=trim($_POST['removetag']);
    $tag_id=tagname2id($removetag);
    if (is_numeric($tag_id)) { //make association
      $sql="DELETE from tag2software where tag2software.tag_id=$tag_id AND tag2software.software_id=$softwareid";
      $sth=db_exec($dbh,$sql);
      $result.="de-associated tag:$removetag<br>";
    }
    else 
      $result.="error: cannot find requested tag for removal!<br>";

}
echo $result;
?>
