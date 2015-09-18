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
</SCRIPT>

<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}
/* Cory Funk 2015, cfunk@fhsu.edu */

//error_reporting(E_ALL);
//ini_set('display_errors', '1');

//delete department
if (isset($_GET['delid'])) { //Deletes the record in the current row 
	$delid=$_GET['delid'];
	$sql="DELETE from departments WHERE id=".$_GET['delid'];
	$sth=db_exec($dbh,$sql);
	echo "<script>document.location='$scriptname?action=listdepartments'</script>";
	echo "<a href='$scriptname?action=listdepartments'></a>"; 
	exit;
}

// get Departments
$sql="SELECT * from departments order by id";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $departments[$r['id']]=$r;
$sth->closeCursor();

$sql="SELECT * from users order by username";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $userlist[$r['id']]=$r;
$sth->closeCursor();

//expand: show more columns
if (isset($_GET['expand']) && $_GET['expand']==1) 
  $expand=1;
else 
  $expand=0;

//export: export to excel (as html table readable by excel)
if (isset($_GET['export']) && $_GET['export']==1) {
  header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
  header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
  header("Cache-Control: Must-Revalidate");
  header('Content-Disposition: attachment; filename=itdb.xls');
  header('Connection: close');
  $export=1;
  $expand=1;//always export expanded view
}
else 
  $export=0;


if (!$export) 
  $perpage=25;
else 
  $perpage=100000;

if ($page=="all") {
  $perpage=100000;
}

/// display list
if ($export) 
  echo "\n<table border='1'>\n";
else {
  echo "<h1>Departments <a title='Add new Jack' href='$scriptname?action=editdepartment&amp;id=new'>".
       "<img border=0 src='images/add.png'></a></h1>\n";
  echo "<form name='frm'>\n";
  echo "\n<table class='brdr'>\n";
}

if (!$export) {
  $get2=$_GET;
  unset($get2['orderby']);
  $url=http_build_query($get2);
}

if (!isset($orderby) && empty($orderby)) 
  $orderby="departments.id";
elseif (isset($orderby)) {

  if (stristr($orderby,"FROM")||stristr($orderby,"WHERE")) {
    $orderby="departments.id";
  }
  if (strstr($orderby,"DESC"))
    $ob="+ASC";
  else
    $ob="+DESC";
}

echo "<thead>\n";
$thead= "\n<tr><th><a href='$fscriptname?$url&amp;orderby=departments.id$ob'>ID</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=departments.division$ob'>Division</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=departments.name$ob'>Department Name</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=departments.abbr$ob'>Department<br />Abbr.</a></th>".
	 "<th><button type='submit'><img border=0 src='images/search.png'></button></th>";

if ($export) {
 //clean links from excel export
  $thead = preg_replace('@<a[^>]*>([^<]+)</a>@si', '\\1 ', $thead); 
  $thead = preg_replace('@<img[^>]*>@si', ' ', $thead); 
}

echo $thead;
echo "</tr>\n</thead>\n";


echo "\n<tbody>\n";
echo "\n<tr>";

//create pre-fill form box vars
$id=isset($_GET['id'])?($_GET['id']):"";
$division=isset($_GET['division'])?$_GET['division']:"";
$name=isset($_GET['name'])?$_GET['name']:"";
$abbr=isset($_GET['abbr'])?$_GET['abbr']:"";

///display search boxes
if (!$export) {

  echo "\n<td title='ID'><input type=text size=3 style='width:8em;' value='$id' name='id'></td>
		<td title='Level that is above the department.'>
		<select name='division'>\n<option value=''>Select</option>"?>
        <?php
		$sql="SELECT DISTINCT departments.division FROM departments";
		$sth=$dbh->query($sql);
		$departments=$sth->fetchAll(PDO::FETCH_ASSOC);
		foreach ($departments as $d) {
			$dbid=$d['id'];
			$itype=$d['division'];
			$s="";
			if (isset($_GET['division']) && $_GET['division']=="$itype") $s=" SELECTED ";
			echo "<option $s value='".$itype."' title='$itype'>$itype</option>\n";
		}
  echo "</select></td>
		<td title='Department Name'><input style='width:37em' type=text value='$name' name='name'></td>
		<td title='Department Abbreviation'><input style='width:auto' type=text value='$abbr' name='abbr'></td>
		<td></td>
<?php        
    $url=http_build_query($_GET)";
  echo "<td style='vertical-align:top;' rowspan=".($perpage+1).">
  		</tr>\n\n";

}//if not export to excel: searchboxes

/// create WHERE clause
if (strlen($id)) $where.="WHERE id = '$id' ";
//if (strlen($delid)) $where.="WHERE delid = '$id' ";
if (isset($division) && strlen($division)) $where.="WHERE division='$division' ";
if (isset($name) && strlen($name)) $where.="WHERE name LIKE '%$name%' ";
if (isset($abbr) && strlen($abbr)) $where.="WHERE abbr LIKE '%$abbr%' ";

//calculate total returned rows
$sth=db_execute($dbh,"SELECT count(departments.id) as totalrows FROM departments $where");
$totalrows=$sth->fetchColumn();

//page links
$get2=$_GET;
unset($get2['page']);
$url=http_build_query($get2);

for ($plinks="",$pc=1;$pc<=ceil($totalrows/$perpage);$pc++) {
 if ($pc==$page)
   $plinks.="<b><u><a href='$fscriptname?$url&amp;page=$pc'>$pc</a></u></b> ";
 else
   $plinks.="<a href='$fscriptname?$url&amp;page=$pc'>$pc</a> ";
}
$plinks.="<a href='$fscriptname?action=listdepartments&page=all'>[show all]</a> ";

$t=time();
$sql="SELECT * FROM departments $where order by $orderby LIMIT $perpage OFFSET ".($perpage*($page-1));
$sth=db_execute($dbh,$sql);

/// display results
$currow=0;
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
$currow++;

  //table row
  if ($currow%2) $c="class='dark'";
  else $c="";

  echo "\n<tr $c>".
       "<td><a class='editiditm icon edit' title='Edit' href='$fscriptname?action=editdepartment&amp;id=".$r['id']."'><span>".$r['id']."</span></a>".

// Username
  $user=isset($userlist[$r['userid']])?$userlist[$r['userid']]['username']:"


		<td>".$r['division']."</td>
        <td>".$r['name']."</td>
        <td><center>".$r['abbr']."</center></td>";?>
		<?php
			echo "<td><center><input type='image' src='images/delete.png' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'></td>\n</tr>\n";
			echo "\n<input type=hidden name='action' value='$action'>";
			echo "\n<input type=hidden name='id' value='$id'>";
		?>
        </tr>
<?php        
}
$sth->closeCursor();

if ($export) {
  echo "</tbody>\n</table>\n";
  exit;
}
else {
    $cs=5;

?>
  <tr><td colspan='<?php echo $cs?>' class=tdc><button type=submit><img src='images/search.png'>Search
  </tbody></table>
  <input type='hidden' name='action' value='<?php echo $_GET['action']?>'>
  </form>

<div class='gray'>
  <b><?php echo $totalrows?> results<br>
  <b>Page:<?php echo $plinks?> <br>
  <a href='<?php echo "$fscriptname?action=$action&amp;export=1"?>'><img src='images/xcel2.jpg' height=25 border=0>Export to Excel

  <?php 
}

if ($export) {
  echo "\n</body>\n</html>\n";
  exit;
}

?>