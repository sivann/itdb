<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009-2010 , sivann _at_ gmail.com */

//delete user
if (isset($_GET['delid'])) {
  $delid=$_GET['delid'];
  if (!is_numeric($delid)) {
    echo "Non numeric id delid=($delid)";
    exit;
  }

  //first handle item associations
  /*
  $nitems=countitemsofuser($delid);
  if ($nitems>0) {
    echo "<b>User not deleted: Please reassign $nitems items first from this user<br></b>\n";
    echo "<br><a href='javascript:history.go(-1);'>Go back</a>\n</body></html>";
    exit;
  }
  else {
  }
    */

  deluser($delid,$dbh); //reassigns items to administrator
  echo "<script>document.location='$scriptname?action=listusers'</script>\n";
  echo "<a href='$scriptname?action=listusers'>Go here</a>\n</body></html>"; 
  exit;

}

if (isset($_POST['id'])) { //if we came from a post (save), update the user 
  $id=$_POST['id'];
  $username=$_POST['username'];
  $usertype=$_POST['usertype'];

  //don't accept empty fields
  if (empty($_POST['username']))  {
    echo "<br><b><span class='mandatory'>Username</span> field cannot be empty.</b><br>".
         "<a href='javascript:history.go(-1);'>Go back</a></body></html>";
    exit;
  }


  if ($_POST['id']=="new")  {//if we came from a post (save) the add user 
    $sql="INSERT into users (username , userdesc , pass, usertype) ".
	 " VALUES ('$username','$userdesc','$pass', '$usertype')";
    db_exec($dbh,$sql,0,0,$lastid);
    $lastid=$dbh->lastInsertId();
    print "<br><b>Added user <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
    echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new user
    echo "\n</body></html>";
    //$id=$lastid;
    exit;

  }//new rack
  else {
    //check for duplicate username
    $sql="SELECT count(id) AS count from users where username='{$_POST['username']}' AND id<>{$_POST['id']}";
    $sth1=db_execute($dbh,$sql);
    $r1=$sth1->fetch(PDO::FETCH_ASSOC);
    $sth1->closeCursor();
    $c=$r1['count'];
    if ($c) {
      echo "<b>Not saved -- Username already exists</b>";
    }
    //else if ($_POST['id']==1 && $_POST['username']!="admin") { echo "<b>Cannot change admin username</b>"; }
    else {
        if ($username=='admin' && $usertype) {
            echo "<h2>".t("user admin has always full access")."</h2><br>";
            $usertype=0;
        }
          $sql="UPDATE users set ".
        " username='".$_POST['username']."', ".
        " userdesc='".$_POST['userdesc']."', ".
        " pass='".$_POST['pass']."', ".
        " usertype='".$usertype."' ".
        " WHERE id=$id";
          db_exec($dbh,$sql);
    }
  }
}//save pressed

/////////////////////////////
//// display data 

if (!isset($_REQUEST['id'])) {echo "ERROR:ID not defined";exit;}
$id=$_REQUEST['id'];

//$sql="SELECT * FROM racks where racks.id='$id'";
$sql="SELECT * from users where users.id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);

if (($id !="new") && (count($r)<2)) {echo "ERROR: non-existent ID<br>($sql)";exit;}

echo "\n<form id='mainform' method=post  action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";

if ($id=="new")
  echo "\n<h1>".t("Add User")."</h1>\n";
else
  echo "\n<h1>".t("Edit User")."  ($id)"."</h1>\n";

?>

<!-- error errcontainer -->
<div class='errcontainer ui-state-error ui-corner-all' style='padding: 0 .7em;width:700px;margin-bottom:3px;'>
        <p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>
        <h4><?php te("There are errors in your form submission, please see below for details");?>.</h4>
        <ol>
                <li><label for="username" class="error"><?php te("Username is missing");?></label></li>
        </ol>
</div>

<table style='width:100%' border=0>


<tr>
<td class="tdtop" width=20%>

    <table class="tbl2" style='width:300px;'>
    <tr><td colspan=2><h3>User Properties</h3></td></tr>
    <tr><td class="tdt">ID:</td> 
        <td><input  style='display:none' type=text name='id' 
	     value='<?php echo $id?>' readonly size=3><?php echo $id?></td></tr>
    <tr><td class="tdt"><?php te("Username");?>:</td> <td><input  class='input2 mandatory' validate='required:true' size=20 type=text name='username' value="<?php echo $r['username']?>"></td></tr>
    <tr><td class="tdt"><?php te("Type")?></td>
        <td>
	<select class='mandatory' validate='required:true' name='usertype'>
	<?php
	if ($r['usertype']==1 || empty($r['username'])) {$s1="selected"; $s0="";} else {$s0="selected"; $s1="";} 
	echo " <option value=1 $s1>".t("Read Only")."</option>\n".
	     " <option value=0 $s0>".t("Full Access")."</option>\n".
	     "</select></td>";
	?>
	</select>
    </td></tr>

    <tr><td class="tdt"><?php te("User Description");?>:</td> 
        <td><input autocomplete="off" class='input2' size=20 
	     type=text name='userdesc' value="<?php echo $r['userdesc']?>">
        </td></tr>
    <tr><td class="tdt"><?php te("Password");?>:</td> 
        <td><input autocomplete="off" class='input2' size=20 type="password"
	     name='pass' value="<?php echo $r['pass']?>">
	 </td></tr>
    <tr><td class="tdt"><?php te("Items");?>:</td> <td><?php echo countitemsofuser($r['id']) ?></td>
    </table>
    <ul>
      <li><b><?php te("Users are used for both web login and as item assignees");?></b></li>
      <li><sup>1</sup><?php te("Blank passwords prohibit login");?></li>
    </ul>
</td>

<td class='smallrack' style='padding-left:10px;border-left:1px dashed #aaa'>
    <div class=scrltblcontainer>
      <div  id='items' class='relatedlist'><?php te("ITEMS");?></div>
      <?php 
      if (is_numeric($id)) {
        $sql="SELECT items.id, agents.title || ' ' || items.model || ' [' || itemtypes.typedesc || ', ".
             " ID:' || items.id || ']' as txt ".
             "FROM agents,items,itemtypes WHERE ".
             " agents.id=items.manufacturerid AND items.itemtypeid=itemtypes.id AND ".
             " items.userid='$id' ";
        $sthi=db_execute($dbh,$sql);
        $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
        $nitems=count($ri);
        $institems="";
        for ($i=0;$i<$nitems;$i++) {
          $x=($i+1).": ".$ri[$i]['txt'];
          if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
          $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
                      "<a href='$scriptname?action=edititem&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
        }
        echo $institems;
      }
      ?>
      </div>
    </div>
</td>
</tr>
<tr>
<td colspan=2>
<button type="submit"><img src="images/save.png" alt="Save"> <?php te("Save");?></button>
<?php 
 if ($id!=1)
echo "\n<button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid=$id\",\"All items will be assigned to user [id:1].\");'>".
     "<img title='delete' src='images/delete.png' border=0>".t("Delete"). "</button>\n";
?>

</td>
</tr>


</table>

<input type=hidden name='id' value='<?php echo $id ?>'>
<input type=hidden name='action' value='<?php echo $action ?>'>

</form>

</body>
</html>
