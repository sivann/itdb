<?php 
//serve XHR to update tag2item table 
require("../init.php");

//addtag, removetag
$addtag=$_POST['addtag'];
$removetag=$_POST['removetag'];
$item_id=$_GET['id'];
if (!is_numeric($item_id)) {
  echo "tag2item_ajaxedit:invalid item_id ($item_id)";exit;
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
      $sql="INSERT INTO items_tags (tag_id,item_id) values ($tag_id,$item_id)";
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
      $sql="DELETE from tag2item where tag2item.tag_id=$tag_id AND tag2item.item_id=$itemid";
      $sth=db_exec($dbh,$sql);
      $result.="de-associated tag:$removetag<br>";
    }
    else 
      $result.="error: cannot find requested tag for removal!<br>";

}

echo $result;
//print_r($_REQUEST); 
?>
