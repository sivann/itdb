<!-- Spiros Ioannou 2009 , sivann _at_ gmail.com -->
<SCRIPT LANGUAGE="JavaScript"> 

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

<?php 
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!isset($initok)) {echo "do not run this script directly";exit;}


if ($id!="new") {
  //get current jack data
  $id=$_GET['id'];
  $sql="	SELECT *, departments.name AS deptname
			FROM jacks
			JOIN departments
			ON jacks.departmentsid=departments.id
			WHERE jacks.id='$id'";
  $sth=db_execute($dbh,$sql);
  $jack=$sth->fetchAll(PDO::FETCH_ASSOC);
}

$sql="SELECT * FROM users order by upper(username)";
$sth=$dbh->query($sql);
$userlist=$sth->fetchAll(PDO::FETCH_ASSOC);

$sql="SELECT * FROM locations order by name";
$sth=$dbh->query($sql);
$locations=$sth->fetchAll(PDO::FETCH_ASSOC);

$sql="SELECT * FROM departments order by id";
$sth=$dbh->query($sql);
$departments=$sth->fetchAll(PDO::FETCH_ASSOC);

$sql="SELECT * FROM departmentabbrs order by id";
$sth=$dbh->query($sql);
$departmentabbrs=$sth->fetchAll(PDO::FETCH_ASSOC);

$sql="SELECT * FROM vlans order by vlanid";
$sth=$dbh->query($sql);
$vlans=$sth->fetchAll(PDO::FETCH_ASSOC);

//change displayed form items in input fields
if ($id=="new") {
  $caption=t("Add New Jack");
  foreach ($formvars as $formvar){
    $$formvar="";
  }
  $d="";
}
//if editing, fill in form with data from supplied jack id
else if ($action=="editjack") {
  $caption=t("Jack Data")." ($id)";
  foreach ($formvars as $formvar){
    $$formvar=$jack[0][$formvar];
  }
}
?>

<h1><?php echo $caption?></h1>
<?php
	if (!empty($disperr))
	{
		echo $disperr;
	}
?>

<!-- our error errcontainer -->
<div class='errcontainer ui-state-error ui-corner-all' style='padding: 0 .7em;width:700px;margin-bottom:3px;'>
	<p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>
	<h4><?php te("There are errors in your form submission, please see below for details");?>.</h4>
	<ol>
		<li><label for="locationid" class="error"><?php te("Please select a location");?></label></li>
		<li><label for="locareaid" class="error"><?php te("Please select a room/area");?></label></li>
		<li><label for="modport" class="error"><?php te("Please select a module & port");?></label></li>
	</ol>
</div>
  <form class='frm1' enctype='multipart/form-data' method='post' name='addjckfrm' id='mainform'>

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

<!-- Jack Name -->
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
					<td class='tdt'><?php te("Notes");?>:</td><td><textarea style='width:37em' wrap='soft' class=tarea1  name='notes'><?php echo $notes?></textarea></td>
				</tr>
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
				$itype=$location['name'].", Floor:".$location['floor'];
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
<!-- Location Information -->
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
<!-- end, Location Information -->

<!-- Room/Area Information -->
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
<!-- end, Room/Area Information -->
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

<!-- Location Information -->
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
				echo "    <option $s value='$dbid'>$itype</option>\n";
			}
			?>
			</select>
		</td>
	</tr>
<!-- end, Location Information -->

<!-- Room/Area Information -->
	<tr>
		<?php if (is_numeric($departmentsid)) {
			$sql="SELECT * FROM departmentabbrs WHERE departmentsid=$departmentsid order by abbr";
			$sth=$dbh->query($sql);
			$departmentabbrs=$sth->fetchAll(PDO::FETCH_ASSOC);
		} 
		else 
			$departmentabbrs=array();
		?>
		<td class='tdt'><?php te("Department Abbr");?>:</td>
		<td><select style='width:37em' id='departmentabbrsid' name='departmentabbrsid'>
			<option value=''><?php te("Select");?></option>
			<?php 
			foreach ($departmentabbrs as $key=>$departmentabbr) {
				$dbid=$departmentabbr['id']; 
				$itype=$departmentabbr['abbr'];
				$s="";
				if (($departmentabbr=="$dbid")) $s=" SELECTED "; 
				echo "    <option $s value='$dbid'>$itype</option>\n";
			}
			?>
			</select>
		</td>
	</tr>
<!-- end, Room/Area Information -->

<!-- User/Device Information -->
				<tr>
					<td class='tdt'><?php te("User/Device");?>:</td>
					<td><input style='width:35em' type='text' value="<?php echo $userdev?>" name='userdev'></td>
				</tr>
<!-- end, User/Device Information -->

			</table>
		</td>
	</tr>
    
<!-- save buttons -->
<table>
<tr>
<td style='text-align: center' colspan=1><button type="submit"><img src="images/save.png" alt="Save" ><?php te("Save");?></button></td>
<?php 
if ($id!="new") {
  echo "\n<td style='text-align: center' ><button type='button' onclick='javascript:delconfirm2(\"Item {$_GET['id']}\",\"$scriptname?action=$action&amp;delid={$_GET['id']}\");'>".
       "<img title='Delete' src='images/delete.png' border=0>".t("Delete")."</button></td>\n";

  echo "\n<td style='text-align: center' ><button type='button' onclick='javascript:cloneconfirm(\"Item {$_GET['id']}\",\"$scriptname?action=$action&amp;cloneid={$_GET['id']}\");'>".
       "<img  src='images/copy.png' border=0>". t("Clone")."</button></td>\n";
} 
else 
  echo "\n<td>&nbsp;</td>";
?>
 
</tr>
</table>

<input type=hidden name=action value='<?php echo $_GET["action"]?>'>
</form>