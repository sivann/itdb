<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */

//echo "<pre>"; print_r($_GET); print_r($_POST);


$formvars=array("id", "username","userdesc","pass");

//if came here from a form post, update db with new values
if (isset($_POST['username'])) {
  $nrows=count($_POST['id']); //number of rows

  for ($rn=0;$rn<$nrows;$rn++) {
    $id=$_POST['id'][$rn];
      if (($id == "new") && (strlen($_POST['username'][$rn])>1) )  {//new item -- insert
      $sql="INSERT into users ".
          "(username,userdesc,pass, usertype) ".
	  " values (".
	  "'".($_POST['username'][$rn])."',".
	  "'".($_POST['userdesc'][$rn])."',".
	  "'".($_POST['pass'][$rn])."',".
	  "'".($_POST['usertype'][$rn])."')";
      }
      elseif ($id!="new"){ //existing item -- update
	$sql="UPDATE users set ".
	  " username='".($_POST['username'][$rn])."', ".
	  " userdesc='".($_POST['userdesc'][$rn])."', ".
	  " pass='".($_POST['pass'][$rn])."', ".
	  " usertype='".($_POST['usertype'][$rn])."' ".
	  " WHERE id=$id";
      }
      else {continue;}

    //echo "$rn $sql<br>";
   db_exec($dbh,$sql);
  }//for
} //if

$sql="select * from users order by username";
$sth=db_execute($dbh,$sql);
?>

<form autocomplete='off' method=post name='actionaddfrm'>
<h1><?php te("Users");?></h1><b><?php te("Users are used for both web login and as item assignees");?></b>
<table class=brdr width='100%' border=0>
<tr><th><?php te("Username");?></th><th><?php te("User Description");?></th>
    <th><?php te("Password");?><sup>1</sup></th>
    <th><?php te("Type");?></th></tr>

<?php
$i=0;
/// print actions list
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  $i++;
  if ($r['usertype']==0) 
    {$s0="selected"; $s1="";} 
  else 
    {$s1="selected"; $s0="";} 
  echo "\n<tr>\n";
  echo "<td><input type=hidden name='id[]' value='".$r['id']."' readonly size=3>";
  echo "<input size=15 type=text name='username[]' value=\"".$r['username']."\"></td>\n";
  echo "<td><input size=50 type=text name='userdesc[]' value=\"".$r['userdesc']."\"></td>\n";
  echo "<td><input size=12 type=password name='pass[]' value=\"".$r['pass']."\"></td>\n";
  echo "<td><select  name='usertype[]'>".
       " <option value=0 $s0>".t("Full Access")."</option>\n".
       " <option value=1 $s1>".t("Read Only")."</option>\n".
       "</select></td>";
  echo "</tr>\n\n";
}

?>
<tr><td><input type=hidden name='id[]' value='new' readonly size=3>
<input size=15 type=text name='username[]' ></td>
<td><input size=50 type=text name='userdesc[]' ></td>
<td><input size=12 type=text name='pass[]' ></td>
<td><select  name='usertype[]'>
 <option value=0 ><?php te("Full Access");?></option>
 <option value=1 ><?php te("Read Only");?></option>
</select></td>

<tr><td colspan=4><button type="submit"><img src="images/save.png" alt="Save" > <?php te("Save");?></button></td></tr>
<tr><td colspan=4><sup>1</sup><?php te("Blank passwords prohibit login");?></td></tr>

</table>
</form>
</body>
</html>
