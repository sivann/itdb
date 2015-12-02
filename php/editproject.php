<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

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
  });


</SCRIPT>
<script type="text/javascript" src="../js/ckeditor/ckeditor.js"></script>
<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009-2010 , sivann _at_ gmail.com */
error_reporting(E_ALL);
ini_set('display_errors', '1');

$sql="SELECT * FROM users order by upper(username)";
$sth=$dbh->query($sql);
$userlist=$sth->fetchAll(PDO::FETCH_ASSOC);

$sql="SELECT * FROM locations order by name";
$sth=$dbh->query($sql);
$locations=$sth->fetchAll(PDO::FETCH_ASSOC);

//delete Project
if (isset($_GET['delid'])) { //if we came from a post (save) the update project 
  $delid=$_GET['delid'];
  

  //delete entry
  $sql="DELETE from projects where id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  echo "<script>document.location='$scriptname?action=listprojects'</script>";
  echo "<a href='$scriptname?action=listprojects'>Go here</a></body></html>"; 
  exit;

}


if (isset($_POST['id'])) { //if we came from a post (save) then update project 
  $id=$_POST['id'];

if ($_POST['id']=="new")  {//if we came from a post (save) then add project 
	$sql="INSERT INTO projects (projectname, proj_status, locationid, locareaid, summary, notes) VALUES ('$projectname', '$proj_status', '$locationid', '$locareaid', '$summary', '$notes')";
    db_exec($dbh,$sql,0,0,$lastid);
    $lastid=$dbh->lastInsertId();
    print "<br><b>Added project <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
    echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new item
    $id=$lastid;
  }
  else {
    $sql="UPDATE projects SET projectname='$projectname',proj_status='$proj_status',locationid='$locationid',locareaid='$locareaid',summary='$summary', notes='$notes' WHERE id=$id";
    db_exec($dbh,$sql);
  }


}//save pressed

///////////////////////////////// display data now

//if (!isset($_REQUEST['id'])) {echo "ERROR:ID not defined";exit;}
//$id=$_REQUEST['id'];

$sql="SELECT * FROM projects WHERE id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);

if ($id !="new")
$projectname=$r['projectname'];$proj_status=$r['proj_status'];$locationid=$r['locationid'];$locareaid=$r['locareaid'];$summary=$r['summary'];$notes=$r['notes'];

echo "\n<form method=post action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";

if ($id=="new")
  echo "\n<h1>".t("Add Project")."</h1>\n";
else
  echo "\n<h1>".t("Edit Project $id")."</h1><left>
    	  <p align='left' style='color:#DF0101'>NOTE: The use of single/double quotes will cause an error posting to the database if you must use these characters<br/>
  								                   please escape them by doubling them (e.g. ' = '')  **If you miss doing this jst use your [Back] Button to fix the problem.</p>";
  
?>

<table border="0" cellpadding="5" cellspacing="5" class="tbl1">

<!-- Project Properties Title -->
    <tr> 
      <td class='tdtop'>
        <table border='0' class="tbl2">
          
<!-- Building Information -->
    <tr> 
      <td class='tdtop'>
          <tr>
            <td colspan=2><h3>
                <?php te("Project Information");?>
              </h3></td>
          </tr>

<!-- Project Properties Title -->
      <tr>
          <td class='tdt'><?php te("Project Name");?>:</td>
          <td><input style="width:33em" id='projectname' name='projectname' value='<?php echo $r['projectname']?>'></input></td>
      </tr>
<!-- end, Project Properties Title -->

<!-- Project Status -->
	<tr>
		<td class='tdt'><?php te("Project Status");?>:</td>
		<td title='<?php te("What is the current status of the project?");?>'><select style='width:16em' id='proj_status' name='proj_status' />
        		<option value=''><?php echo $r['proj_status']?></option>
                <option title='<?php te("Cost, Best Possible Method, Time/Time Constraints, etc...");?>' value='Planning'>Planning</option>
                <option title='<?php te("Steps towards completing the project.");?>' value='In Progress'>In Progress</option>
                <option title='<?php te("Final touches to complete project.");?>' value='Finalizing'>Finalizing</option>
                <option title='<?php te("Nothing more needed.");?>' value='Complete'>Complete</option>
			</select>
		</td>
	</tr>
<!-- end, Project Status -->

<!-- Building Information -->
    <tr> 
      <td class='tdtop'>
          <tr>
            <td colspan=2><h3>
                <?php te("Building Information");?>
              </h3></td>
          </tr>
          
<!-- Building Name & Floor -->
      <tr>
      <td class='tdt'>
		<?php echo "<a title='Add New Building' href='$scriptname?action=editlocation&id=new'><img src='images/add.png' alt='+'></a> ";
			  echo "<a alt='Edit' title='".t("Edit Building or Room")."' href='$scriptname?action=editlocation&id=$locationid'><img src='images/edit2.png'></a> ";?>
			  <?php te("Location");?>:</td>
      <td>
	<select style="width:33em" id='locationid' name='locationid'>
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
<!-- end, Building Name & Floor -->

<!-- Area/Room -->
      <tr>
      <?php 
      if (is_numeric($locationid)) {
	$sql="SELECT * FROM locareas WHERE locationid=$locationid order by areaname";
	$sth=$dbh->query($sql);
	$locareas=$sth->fetchAll(PDO::FETCH_ASSOC);
      } 
      else 
	$locareas=array();
      ?>
		<td class='tdt' class='tdt'><?php te("Area/Room");?>:</td>
		<td>
			<select style="width:33em" id='locareaid' name='locareaid'>
			<option value=''><?php te("Select");?></option>
			<?php 
			foreach ($locareas  as $key=>$locarea )
			{
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
<!-- end, Area/Room -->
<!-- end, Building Information -->

<!-- Project Details -->
          <tr>
            <td colspan=2><h3>
                <?php te("Project Information");?>
              </h3></td>
          </tr>

<!-- Summary -->
          <tr>
            <td class="tdt2"><?php te("Brief Summary");?>:</td>
            <td><textarea wrap='soft' class='tarea1' style='height:200px;width:1024px' name='summary'><?php echo $summary?></textarea></td>
          </tr>
<!-- end, Summary -->

<!-- Notes -->
          <tr>
            <td class="tdt2"><?php te("Project Details");?>:</td>
            <td><textarea wrap='soft' class='tarea1' style='height:768px;width:1024px' id='notes' name='notes'><?php echo $notes?></textarea></td>
				<script>
					CKEDITOR.replace( 'notes' );
				</script>

          </tr>
        </table>
<!-- end, Notes -->
</table>
		</td>
	</tr>
      <tr>
        <td><table border="0" class="tbl2">
          <tr>
            <td><button type="submit"><img src="images/save.png" alt="Save" /> <?php te("Save");?></button></td>
				<input type=hidden name='action' value='$action'>
				<input type=hidden name='id' value='$id'>

		<?php
            echo "\n<td><button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>"."<img title='Delete' src='images/delete.png' border=0>".t("Delete")."
		</button></td>\n</tr>\n";
		echo "\n</table>\n";
		echo "\n<input type=hidden name='action' value='$action'>";
		echo "\n<input type=hidden name='id' value='$id'>";
		?>
       	  </tr>
        </table></td>
      </tr>
    </table>
    </form>
</body>
</html>