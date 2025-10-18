<SCRIPT LANGUAGE="JavaScript"> 
$(function () {
  $('table#userslisttbl').dataTable({
	"sPaginationType": "full_numbers",
	"bJQueryUI": true,
	"iDisplayLength": 25,
	"bLengthChange": true,
	"bFilter": true,
	"bSort": true,
	"bInfo": true,
	"sDom": '<"H"Tlpf>rt<"F"ip>',
	"aaSorting": [],
	"oTableTools": {
	    "sSwfPath": "swf/copy_cvs_xls_pdf.swf"
	}

  });
});

</SCRIPT>
<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}

$sql="SELECT * from users ORDER by username ASC";
$sth=db_execute($dbh,$sql);
?>

<h1><?php te("Users");?> <a title='<?php te("Add new User");?>' href='<?php echo $scriptname?>?action=edituser&amp;id=new'><img border=0 src='images/add.png' ></a>
</h1>

<table class='display' width="100%" id='userslisttbl'>
<thead>
<tr>
  <th width='2%'><?php te("Edit");?></th>
  <th width='5%'><?php te("Username");?></th>
  <th><?php te("User Description");?></th>
  <th><?php te("Type");?></th>
  <th width='5%'><?php te("Items");?></th>
</tr>
</thead>
<tbody>

<?php 
$usertype[0]=t("Full Access");
$usertype[1]=t("Read Only");
$usertype[2]=t("copied from LDAP (read only)");

$i=0;
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  $i++;
  $itemcount=countitemsofuser($r['id']);
  echo "\n<tr>";
  echo "<td><a class='editid' href='$scriptname?action=edituser&amp;id=".$r['id']."'>{$r['id']}</a></td>\n";
  echo "<td>{$r['username']}</td>\n";
  echo "<td>{$r['userdesc']}</td>\n";
  echo "<td>{$usertype[$r['usertype']]}</td>\n";
  echo "<td>$itemcount</td>\n";
  echo "</tr>\n";
}
?>

</tbody>
</table>

</form>
</body>
</html>
