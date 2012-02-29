<?php 
/* Spiros Ioannou 12/2010 */

if (file_exists('init.php'))
  require_once("init.php");
else
  require_once("../init.php");


//echo "<pre>\n"; print_r($_POST); echo "\n</pre>"; echo "id=$id";

if ((!isset($_POST['deleteareaid'])) && isset($_POST['areaids'])) {
  $nrows=count($_POST['areaids']); //number of rows
  //echo "<br><b>$nrows rows<br></b>";

  for ($rn=0;$rn<$nrows;$rn++) {
      if (($_POST['areaids'][$rn] == "new") && (strlen($_POST['areanames'][$rn])>1) )  {//new item -- insert
      $sql="INSERT into locareas ".
          "(locationid,areaname) ".
          " values (".
          "'$id',".
          "'".($_POST['areanames'][$rn])."')";
      }
      elseif ($_POST['areaids'][$rn]!="new"){ //existing item -- update
        $sql="UPDATE locareas SET ".
          " locationid='$id', ".
          " areaname='".($_POST['areanames'][$rn])."' ".
          " WHERE id='{$_POST['areaids'][$rn]}'";
      }
      else {continue;}

    db_exec($dbh,$sql);
    //echo $sql."<br>";
  }//for

}//isset id
elseif (isset($_POST['deleteareaid'])) {
  $nareas=countlocarealinks($_POST['deleteareaid'],$dbh);
  if (!$nareas) {
    $sql="DELETE FROM locareas WHERE id='{$_POST['deleteareaid']}'";
    db_exec($dbh,$sql);
    echo "DELETED id:".$_POST['deleteareaid'];
  }
  else {
    echo "NOT deleted area id:".$_POST['deleteareaid'].", associated with $nareas items/racks";
  }
}



/* List  entries  - print form */

  $sql="SELECT * FROM locareas where locationid=$id";
  $sthi=db_execute($dbh,$sql);
  $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
  $nitems=count($ri);
  $institems="";
?>

  <form method="POST" name='areafrm' id='areafrm' action='php/locareas.php'>
  <input name='id' value='<?php echo $id?>' type=hidden>


  <table>
    <?php 
    for ($i=0;$i<$nitems;$i++) {
      $id=$ri[$i]['id'];
      $areaname=$ri[$i]['areaname'];
    ?>
    <tr>
      <td><input type='image' onclick="return confirm('Are you sure you want to delete this area?');" src='images/delete.png' value='<?php echo $id?>' name='deleteareaid'></td>
      <td><input name='areaids[]' value='<?php echo $id?>' type=hidden><input style='width:8em' name='areanames[]' value='<?php echo $areaname?>'></td>
    </tr>
    <?php 
    }
    ?>
    <tr><td></td><td><input name='areaids[]' value='new' type=hidden><input style='width:8em' name='areanames[]' value=''></td>
  </table>
  <input type=submit value='Save areas'>
  </form>


