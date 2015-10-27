<SCRIPT LANGUAGE="JavaScript"> 

  function confirm_filled($row)
  {
	  var filled = 0;
	  $row.find('input,select').each(function() {
		  if (jQuery(this).val()) filled++;
	  });
	  if (filled) return confirm('Do you really want to remove this row?');
	  return true;
  };

 $(document).ready(function() {

    //delete table row on image click
    $('.delrow').click(function(){
        var answer = confirm("Are you sure you want to delete this row ?")
        if (answer) 
	  $(this).parent().parent().remove();
    });

    $("#caddrow").click(function($e) {
	var row = $('#contactstable tr:last').clone(true);
        $e.preventDefault();
	row.find("input:text").val("");
	row.find("img").css("display","inline");
	row.insertAfter('#contactstable tr:last');
    });
    $("#uaddrow").click(function($e) {
	var row = $('#urlstable tr:last').clone(true);
        $e.preventDefault();
	row.find("input:text").val("");
	row.find("img").css("display","inline");
	row.insertAfter('#urlstable tr:last');
    });
  });

  $(document).ready(function() {
    $("#locationid").change(function() {
      var locationid=$(this).val();
      var locareaid=$('#locareaid').val();
      var dataString = 'locationid='+ locationid;
	  
      $.ajax ({
	  type: "POST",
	  url: "php/locarea_options_ajax.php",
	  data: dataString,
	  cache: false,
	  success: function(html) {
	    $("#locareaid").html(html);
	  }
      });
    });
	
	  $("#departmentsid").change(function() {
      var departmentsid=$(this).val();
      var departmentabbrid=$('#departmentabbrsid').val();
      var dataString = 'departmentsid='+ departmentsid;
	  
      $.ajax ({
	  type: "POST",
	  url: "php/dept_options_ajax.php",
	  data: dataString,
	  cache: false,
	  success: function(html) {
	    $("#departmentabbrsid").html(html);
	  }
      });
    });

    $("#vlanid").change(function() {
      var vlanid=$(this).val();
      var vlanname=$('#vlanname').val();
      var dataString = 'vlanid='+ vlanid;
	  
      $.ajax ({
	  type: "POST",
	  url: "php/vlan_options_ajax.php",
	  data: dataString,
	  cache: false,
	  success: function(html) {
	    $("#vlanname").html(html);
	  }
      });
    });
  });

</SCRIPT>
<script type="text/javascript" src="../js/ckeditor/ckeditor.js"></script>

<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009-2010 , sivann _at_ gmail.com */
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

$sql="SELECT * FROM users order by upper(username)";
$sth=$dbh->query($sql);
$userlist=$sth->fetchAll(PDO::FETCH_ASSOC);

$sql="SELECT * FROM locations order by name";
$sth=$dbh->query($sql);
$locations=$sth->fetchAll(PDO::FETCH_ASSOC);

$sql="SELECT * FROM departments order by name";
$sth=$dbh->query($sql);
$departments=$sth->fetchAll(PDO::FETCH_ASSOC);

$sql="SELECT * FROM vlans order by vlanid";
$sth=$dbh->query($sql);
$vlans=$sth->fetchAll(PDO::FETCH_ASSOC);

//delete jack
if (isset($_GET['delid'])) { //if we came from a post (save) the update jack 
  $delid=$_GET['delid'];
  

  //delete entry
  $sql="DELETE from jacks where id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  echo "<script>document.location='$scriptname?action=listjacks'</script>";
  echo "<a href='$scriptname?action=listjacks'>Go here</a></body></html>"; 
  exit;

}


if (isset($_POST['id'])) { //if we came from a post (save) then update jack 
  $id=$_POST['id'];

  if ($_POST['id']=="new")  {//if we came from a post (save) then add jack 
    $sql="INSERT INTO jacks (switchname, locareaid, locationid, jackname, departmentsid, departmentabbrsid, userdev, modport, pubipnet, pubiphost, vlanname, privipnet, priviphost, groupname, vlanid, notes, temp_perm, userid,
		  wallcoord) VALUES ('$switchname', '$locareaid', '$locationid', '$jackname', '$departmentsid', '$departmentabbrsid', '$userdev', '$modport', '$pubipnet', '$pubiphost', '$vlanname', '$privipnet', '$priviphost',
		  '$groupname', '$vlanid', '$notes', '$temp_perm', $userid', '$wallcoord')";
		  
    db_exec($dbh,$sql,0,0,$lastid);
    $lastid=$dbh->lastInsertId();
    print "<br><b>Added jack <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
    echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new item
    $id=$lastid;
  }
  else {
    $sql="UPDATE jacks SET ".
       " switchname='$switchname',locareaid='$locareaid',locationid='$locationid',jackname='$jackname',departmentsid='$departmentsid', departmentabbrsid='$departmentabbrsid',
	   	 userdev='$userdev', modport='$modport', pubipnet='$pubipnet', pubiphost='$pubiphost', vlanname='$vlanname', privipnet='$privipnet', priviphost='$priviphost', groupname='$groupname', vlanid='$vlanid', notes='$notes',	
		 temp_perm='$temp_perm', userid='$userid', wallcoord='$wallcoord' WHERE id=$id";
    db_exec($dbh,$sql);
  }


}//save pressed

///////////////////////////////// display data now


if (!isset($_REQUEST['id'])) {echo "ERROR:ID not defined";exit;}
$id=$_REQUEST['id'];

$sql="SELECT * FROM jacks WHERE id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);
if (($id !="new") && (count($r)<5)) {echo "ERROR: non-existent ID";exit;}

$switchname=$r['switchname'];$locareaid=$r['locareaid'];$locationid=$r['locationid'];$jackname=$r['jackname'];$departmentsid=$r['departmentsid'];$departmentabbrsid=$r['departmentabbrsid'];$userdev=$r['userdev'];$modport=$r['modport'];$pubipnet=$r['pubipnet'];$pubiphost=$r['pubiphost'];$vlanname=$r['vlanname'];$privipnet=$r['privipnet'];$priviphost=$r['priviphost'];$groupname=$r['groupname'];$vlanid=$r['vlanid'];$notes=$r['notes'];$temp_perm=$r['temp_perm'];$userid=$r['userid'];$wallcoord=$r['wallcoord'];

echo "\n<form method=post  action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";

if ($id=="new")
  echo "\n<h1>".t("Add Jack")."</h1>\n";
else
  echo "\n<h1>".t("Edit Jack $id")."</h1>\n";

?>
<!-- Jack Properties -->
	<table border='0' class=tbl1 >
	<tr>

<!-- Jack Name -->
		<td class='tdtop'>
			<table border='0' class=tbl2>
				<tr><td colspan=2><h3><?php te("Jack Properties");?></h3></td></tr>
				<tr>
					<td class='tdt'><?php te("Jack");?>:</td>
					<td title='<?php te("Jack name on wall plate (e.g. 1A-200-1a");?>'><input type='text' value="<?php echo $jackname?>" name='jackname'></td>
				</tr>
<!-- end, Jack Name -->

<!-- Temporary or Permanent Change -->
				<tr>
					<?php 
						$T="";$P="";
						if ($temp_perm=="Temp") {$T="checked";$P="";}
						if ($temp_perm=="Perm") {$P="checked";$T="";}
					?>
					<td class='tdt'><?php te("Temp / Perm Change");?>:<br /></td>
					<td title='Select (T)emporary / (P)ermanent'>
                    	<input <?php echo $T?> class='radio' type=radio name='temp_perm' value='Temp'><?php te("Temporary");?>
                    	<input <?php echo $P?> class='radio' type=radio name='temp_perm' value='Perm'><?php te("Permanent");?>
 					</td>
				</tr>
<!-- end, Temporary or Permanent Change -->

<!-- Wall Location -->
				<tr>
					<?php 
						$N="";$S="";$E="";$W="";
						if ($wallcoord=="N") {$N="checked";$S="";$E="";$W="";}
						if ($wallcoord=="S") {$S="checked";$N="";$E="";$W="";}
						if ($wallcoord=="E") {$E="checked";$N="";$S="";$W="";}
						if ($wallcoord=="W") {$W="checked";$N="";$S="";$E="";}
					?>
					<td class='tdt'><?php te("Wall Location");?>:</td>
					<td title='Select (N)orth, (S)outh, (E)ast, (W)est'>
                    	<input <?php echo $N?> class='radio' type=radio name='wallcoord' value='N'><?php te("N");?>
                    	<input <?php echo $S?> class='radio' type=radio name='wallcoord' value='S'><?php te("S");?>
                    	<input <?php echo $E?> class='radio' type=radio name='wallcoord' value='E'><?php te("E");?>
                    	<input <?php echo $W?> class='radio' type=radio name='wallcoord' value='W'><?php te("W");?>
					</td>
				</tr>
<!-- end, Wall Location -->

<!-- Notes -->
                <tr>
					<td class='tdt'><?php te("Notes");?>:</td><td><textarea style='width:33em;height:20em' wrap='soft' class=tarea1  id='notes' name='notes'><?php echo $notes?></textarea></td>
<!--						<script>
                            CKEDITOR.replace( 'notes' );
                        </script>
-->				</tr>
<!-- end, Notes -->
			</table>
		</td>
<!-- end, Jack Properties -->

<!-- Building Information -->
		<td class='tdtop'>
			<table border='0' class=tbl2>
				<tr>
                	<td colspan=2 ><h3><?php te("Building Information");?></h3></td>
				</tr>

<!-- Location Information -->
	<tr>
		<td class='tdt'><?php te("Location");?>:</td>
		<td><select style='width:37em' id='locationid' name='locationid'>
			<option value=''><?php te("Select");?></option>
			<?php 
			foreach ($locations  as $key=>$location ) {
				$dbid=$location['id']; 
				$itype=$location['name'];
				$s="";
				if (($locationid=="$dbid")) $s=" SELECTED "; 
				echo "    <option $s value='$dbid'>$itype</option>\n";
			}
			?>
			</select>
		</td>
	</tr>
<!-- end, Location Information -->

<!-- Room/Area Information -->
	<tr>
		<?php if (is_numeric($locationid)) {
			$sql="SELECT * FROM locareas WHERE locationid=$locationid order by areaname";
			$sth=$dbh->query($sql);
			$locareas=$sth->fetchAll(PDO::FETCH_ASSOC);
		} 
		else 
			$locareas=array();
		?>
		<td class='tdt'><?php te("Area/Room");?>:</td>
		<td><select style='width:37em' id='locareaid' name='locareaid'>
			<option value=''><?php te("Select");?></option>
			<?php 
			foreach ($locareas  as $key=>$locarea ) {
				$dbid=$locarea['id']; 
				$itype=$locarea['areaname'];
				$s="";
				if (($locareaid=="$dbid")) $s=" SELECTED "; 
				echo "    <option $s value='$dbid'>$itype</option>\n";
			}
			?>
			</select>
		</td>
	</tr>
</table>
</td>
<!-- end, Room/Area Information -->
<!-- end, Building Information -->

<!-- Switch Information -->
		<td class='tdtop'>
			<table border='0' class=tbl2>
				<tr><td colspan=2 ><h3><?php te("Switch Information");?></h3></td></tr>
                <tr><td class='tdt'><?php te("Switch Name");?>:</td><td><input type=text size=15 value='<?php echo $switchname?>' name='switchname'></td></tr>
                <tr><td class='tdt'><?php te("Module & Port");?>:</td><td><input type=text size=15 value='<?php echo $modport?>' name='modport'></td></tr>
<!-- VLAN ID Information -->
	<tr>
		<td class='tdt'><?php te("VLAN");?>:</td>
		<td><select style='width:16em' id='vlanid' name='vlanid'>
			<option value=''><?php te("Select");?></option>
			<?php 
			foreach ($vlans as $key=>$v) {
				$dbid=$v['id']; 
				$itype=$v['vlanid'];
				$s="";
				if (($vlanid=="$dbid")) $s=" SELECTED "; 
				echo "<option $s value='$dbid'>$itype</option>\n";
			}
			?>
			</select>
		</td>
	</tr>
<!-- end, VLAN ID Information -->

<!-- VLAN Name Information -->
	<tr>
		<?php if (is_numeric($vlanid)) {
			$sql="SELECT * FROM vlans WHERE id=$vlanid order by vlanid";
			$sth=$dbh->query($sql);
			$vlans=$sth->fetchAll(PDO::FETCH_ASSOC);
		} 
		else 
			$vlans=array();
		?>
		<td class='tdt'><?php te("VLAN Name");?>:</td>
		<td><select style='width:16em' id='vlanname' name='vlanname'>
			<option value=''><?php te("Select");?></option>
			<?php 
			foreach ($vlans as $key=>$v ) {
				$dbid=$v['id']; 
				$itype=$v['vlanname'];
				$s="";
				if (($vlanid=="$dbid")) $s=" SELECTED "; 
				echo "<option $s value='$dbid'>$itype</option>\n";
			}
			?>
			</select>
		</td>
	</tr>
<!-- end, VLAN Name Information -->
                <tr><td class='tdt'><?php te("Public IP Network");?>:</td><td><input type=text size=15 value='<?php echo $pubipnet?>' name='pubipnet'></td></tr>
                <tr><td class='tdt'><?php te("Public IP Host");?>:</td><td><input type=text size=15 value='<?php echo $pubiphost?>' name='pubiphost'></td></tr>
                <tr><td class='tdt'><?php te("Private IP Network");?>:</td><td><input type=text size=15 value='<?php echo $privipnet?>' name='privipnet'></td></tr>
                <tr><td class='tdt'><?php te("Priavte IP Host");?>:</td><td><input type=text size=15 value='<?php echo $priviphost?>' name='priviphost'></td></tr>
                <tr><td class='tdt'><?php te("Group Name");?>:</td><td><input type=text size=15 value='<?php echo $groupname?>' name='groupname'></td></tr>
			</table>
		</td>

<!-- Department Information -->
	<tr>
        <td class='tdtop'>
            <table border='0' class=tbl2>
                <tr>
                    <td colspan=2><h3><?php te("Department Information");?></h3></td>
                </tr>

<!-- Department Name -->
	<tr>
		<td class='tdt'><?php te("Department");?>:</td>
		<td><select style='width:37em' id='departmentsid' name='departmentsid'>
			<option value=''><?php te("Select");?></option>
			<?php 
			foreach ($departments as $key=>$department ) {
				$dbid=$department['id']; 
				$itype=$department['name'];
				$s="";
				if (($departmentsid=="$dbid")) $s=" SELECTED "; 
				echo "<option $s value='$dbid'>$itype</option>\n";
			}
			?>
			</select>
		</td>
	</tr>
<!-- end, Department Name -->

<!-- Department Abbreviation -->
	<tr>
		<?php if (is_numeric($departmentsid)) {
			$sql="SELECT * FROM departments WHERE id=$departmentsid order by abbr";
			$sth=$dbh->query($sql);
			$departments=$sth->fetchAll(PDO::FETCH_ASSOC);
		} 
		else 
			$departments=array();
		?>
		<td class='tdt'><?php te("Department Abbr");?>:</td>
		<td><select style='width:37em' id='departmentabbrsid' name='departmentabbrsid'>
			<option value=''><?php te("Select");?></option>
			<?php 
			foreach ($departments as $key=>$d ) {
				$dbid=$d['id']; 
				$itype=$d['abbr'];
				$s="";
				if (($departmentsid=="$dbid")) $s=" SELECTED "; 
				echo "<option $s value='$dbid'>$itype</option>\n";
			}
			?>
			</select>
		</td>
	</tr>
<!-- end, Department Abbreviation -->

<!-- User/Device Information -->
				<tr>
					<td class='tdt'><?php te("User/Device");?>:</td>
					<td><input style='width:35em' type='text' value="<?php echo $userdev?>" name='userdev'></td>
				</tr>
<!-- end, User/Device Information -->

			</table>
		</td>
	</tr>
      <tr>
        <td><table border="0" class="tbl2">
          <tr>
            <td><button type="submit"><img src="images/save.png" alt="Save" />
              <?php te("Save");?>
            </button></td>
            <?php echo "\n<td><button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>"."<img title='Delete' src='images/delete.png' border=0>".t("Delete")."
		</button></td>\n</tr>\n";
		echo "\n</table>\n";
		echo "\n<input type=hidden name='action' value='$action'>";
		echo "\n<input type=hidden name='id' value='$id'>";
		?> </tr>
        </table></td>
      </tr>
    </table>
    </form>
</body>
</html>