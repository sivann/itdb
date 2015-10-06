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
  });

</SCRIPT>
<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}
/* Cory Funk 2015, cfunk@fhsu.edu */

//error_reporting(E_ALL);
//ini_set('display_errors', '1');

//delete Jack
if (isset($_GET['delid'])) { //Deletes the record in the current row 
	$delid=$_GET['delid'];
	$sql="DELETE from jacks WHERE id=".$_GET['delid'];
	$sth=db_exec($dbh,$sql);
	echo "<script>document.location='$scriptname?action=listjacks'</script>";
	echo "<a href='$scriptname?action=listjacks'></a>"; 
	exit;
}

// Get jack information
$sql="SELECT * FROM jacks WHERE id = '' OR id != '' ";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $jacks[$r['id']]=$r;
$sth->closeCursor();

// Get Location information
$sql="SELECT * from locations order by name,floor";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $locations[$r['id']]=$r;
$sth->closeCursor();

// Get Area/Room information
$sql="SELECT * FROM locareas order by areaname";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $locareas[$r['id']]=$r;
$sth->closeCursor();

// Get Department information
$sql="SELECT * FROM departments order by division,name";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $departments[$r['id']]=$r;
$sth->closeCursor();

// Get VLAN information
$sql="SELECT * FROM vlans order by vlanid";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $vlans[$r['id']]=$r;
$sth->closeCursor();

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



if ($export)  {
  echo "<html>\n<head><meta http-equiv=\"Content-Type\"".
     " content=\"text/html; charset=UTF-8\" /></head>\n<body>\n";
}



// Display list
if ($export) 
  echo "\n<table border='1'>\n";
else {
  echo "<h1>Jacks <a title='Add new jack' href='$scriptname?action=editjack&amp;id=new'>".
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
  $orderby="jacks.id asc";
elseif (isset($orderby)) {

  if (stristr($orderby,"FROM")||stristr($orderby,"WHERE")) {
    $orderby="id";
  }
  if (strstr($orderby,"DESC"))
    $ob="+ASC";
  else
    $ob="+DESC";
}

echo "<thead>\n";
$thead= "\n<tr>".
	 "<th><a href='$fscriptname?$url&amp;orderby=jacks.id$ob'>ID</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=jackname$ob'>Jack</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=locationid$ob'>Building [Floor]</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=locareaid$ob'>Area/Room</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=switchname$ob'>Switch Name</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=modport$ob'>Module & Port</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=userdev$ob'>User / Device</a></th>".
     "<th><a>Notes</a></th>".
	 "<th><button type='submit'><img border=0 src='images/search.png'></button></th>";

if ($export) {
 //clean links from excel export
  $thead = preg_replace('@<a[^>]*>([^<]+)</a>@si', '\\1 ', $thead); 
  $thead = preg_replace('@<img[^>]*>@si', ' ', $thead); 
}

echo $thead;

if ($expand) {
  echo "\n <th style='width:auto'>Wall Location</th>";
  echo "\n <th>Department</th>";
  echo "\n <th>VLAN</th>";
  echo "\n <th style='width:auto'>Public IP<br />Network</th>";
  echo "\n <th style='width:auto'>Public IP<br />Host</th>";
  echo "\n <th style='width:auto'>Private IP<br />Network</th>";
  echo "\n <th style='width:auto'>Private IP<br />Host</th>";
  echo "\n <th style='width:auto'>Group Name</th>";
}
else
echo "</tr>\n</thead>\n";
echo "\n<tbody>\n";
echo "\n<tr>";

//create pre-fill form box vars
$jackname=isset($_GET['jackname'])?($_GET['jackname']):"";
$locationid=isset($_GET['locationid'])?($_GET['locationid']):"";
$locareaid=isset($_GET['locareaid'])?($_GET['locareaid']):"";
$switchname=isset($_GET['switchname'])?($_GET['switchname']):"";
$modport=isset($_GET['modport'])?($_GET['modport']):"";
$userdev=isset($_GET['userdev'])?$_GET['userdev']:"";
$notes=isset($_GET['notes'])?$_GET['notes']:"";
$wallcoord=isset($_GET['wallcoord'])?$_GET['wallcoord']:"";
$departmentsid=isset($_GET['departmentsid'])?$_GET['departmentsid']:"";
$vlanid=isset($_GET['vlanid'])?$_GET['vlanid']:"";
$pubipnet=isset($_GET['pubipnet'])?$_GET['pubipnet']:"";
$pubiphost=isset($_GET['pubiphost'])?$_GET['pubiphost']:"";
$privipnet=isset($_GET['privipnet'])?$_GET['privipnet']:"";
$priviphost=isset($_GET['priviphost'])?$_GET['priviphost']:"";
$groupname=isset($_GET['groupname'])?$_GET['groupname']:"";
$page=isset($_GET['page'])?$_GET['page']:1;

// Display Search Boxes
if (!$export) {

  echo "\n<td title='ID'><input type=text size=3 style='width:8em;' value='$id' name='id'></td>";
  echo "<td title='1A-100-1b'><input style='width:auto' type=text value='$jackname' name='jackname'></td>";?>
		<td><select style='width:auto' id='locationid' name='locationid'>
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
<!-- end, Location Information -->

<!-- Room/Area Information -->
		<?php if (is_numeric($locationid))?>
		<td><select style='width:8em' id='locareaid' name='locareaid'>
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
<?php
  echo "<td title='Switch Name'><input style='width:auto' type=text value='$switchname' name='switchname'></td>";
  echo "<td title='tg.1.50<br />ge.1.1<br />fe.1.1<br />e.0.1'><input type=text value='$modport' name='modport'></td>";
  echo "<td title='User or Device'><input style='width:30em' type=text value='$userdev' name='userdev'></td>";
  echo "<td title='Notes'><input style='width:31em' type=text value='$notes' name='notes'></td>";

  if ($expand) {
    echo "<td></td>";
	echo "<td title='(N)orth<br />(S)outh<br />(E)ast<br />(W)est<br />'><select name='wallcoord'>
			<option value=''>All</option>
			<option value='N'>N</option>
			<option value='S'>S</option>
			<option value='E'>E</option>
			<option value='W'>W</option>
		  </select></td>";?>
		  <td title='Department Name'>
			<select style='width:37em' id='departmentsid' name='departmentsid'>
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
<?php
	echo "<td title='VLAN'>";?>
			<select style='width:auto' id='vlanid' name='vlanid'>
			<option value=''><?php te("Select");?></option>
			<?php 
			foreach ($vlans as $key=>$vlan ) {
				$dbid=$vlan['id']; 
				$itype=$vlan['vlanname'];
				$itype2=$vlan['vlanid'];
				$s="";
				if (($vlanid=="$dbid")) $s=" SELECTED "; 
				echo "<option $s value='$dbid'>$itype2 [$itype]</option>\n";
			}
			?>
			</select>
		</td>
<?php

	echo "<td title='Public IP Network'><input style='width:auto' type=text value='$pubipnet' name='pubipnet'></td>";
	echo "<td title='Public IP Host'><input style='width:auto' type=text value='$pubiphost' name='pubiphost'></td>";
	echo "<td title='Private IP Network'><input style='width:auto' type=text value='$privipnet' name='privipnet'></td>";
	echo "<td title='Private IP Host'><input style='width:auto' type=text value='$priviphost' name='priviphost'></td>";
	echo "<td title='Group Name'><input style='width:auto' type=text value='$groupname' name='groupname'></td>";
}else{
    $url=http_build_query($_GET);
    echo "<td style='vertical-align:top;'>".
         "<a alt='More' title='Show More Columns' href='$fscriptname?$url&amp;expand=1'>".
         "<img src='images/more.png'></a></td>";
  }
  echo "</tr>\n\n";

}//if not export to excel: searchboxes

// Create WHERE clause
if (isset($id) && strlen($id)) $where.="AND id = '$id' ";
if (isset($jackname) && strlen($jackname)) $where.="AND jackname LIKE '%$jackname%' ";
if (isset($locationid) && strlen($locationid)) $where.="AND locationid = '$locationid' ";
if (isset($locareaid) && strlen($locareaid)) $where.="AND (locareaid = '$locareaid') ";
if (isset($switchname) && strlen($switchname)) $where.="AND switchname LIKE '%$switchname%' ";
if (isset($modport) && strlen($modport)) $where.="AND modport LIKE '%$modport%' ";
if (isset($userdev) && strlen($userdev)) $where.="AND userdev LIKE '%$userdev%' ";
if (isset($notes) && strlen($notes)) $where.="AND notes LIKE '%$notes%' ";
if (isset($wallcoord) && strlen($wallcoord)) $where.="AND wallcoord LIKE '%$wallcoord%' ";
if (isset($departmentsid) && strlen($departmentsid)) $where.="AND departmentsid LIKE '%$departmentsid%' ";
if (isset($vlanid) && strlen($vlanid)) $where.="AND vlanid LIKE '%$vlanid%' ";
if (isset($pubipnet) && strlen($pubipnet)) $where.="AND pubipnet LIKE '%$pubipnet%' ";
if (isset($pubiphost) && strlen($pubiphost)) $where.="AND pubiphost LIKE '%$pubiphost%' ";
if (isset($privipnet) && strlen($privipnet)) $where.="AND privipnet LIKE '%$privipnet%' ";
if (isset($priviphost) && strlen($priviphost)) $where.="AND priviphost LIKE '%$priviphost%' ";
if (isset($groupname) && strlen($groupname)) $where.="AND groupname LIKE '%$groupname%' ";

///////////////////////////////////////////////////////////							Pagination							///////////////////////////////////////////////////////////

//	How many records are in table
$sth=db_execute($dbh,"SELECT count(jacks.id) as totalrows FROM jacks WHERE id = '' OR id != '' $where");
$totalrows=$sth->fetchColumn();

//	Page Links
//	Get's the current page number
$get2=$_GET;
unset($get2['page']);
$url=http_build_query($get2);

//	Previous and Next Links
	$prev = $page - 1;
	$next = $page + 1;

//	Previous Page
	if ($page > 1){
	$prevlink .="<a href='$fscriptname?$url&amp;page=$prev'><img src='../images/previous-button.png' width='64' height='25' alt='previous' /></a> ";
	}else{
	$prevlink .="<img src='../images/previous-button.png' width='64' height='25' alt='previous' /> ";
	}

//	Numbers
	for ($plinks="",$pc=1;$pc<=ceil($totalrows/$perpage);$pc++){
		if ($pc==$page){
			$plinks.="<b><u><a href='$fscriptname?$url&amp;page=$pc'>[$pc]</a></u></b> ";
		}else{
			$plinks.="<a href='$fscriptname?$url&amp;page=$pc'>$pc</a> ";
		}
	}

//	Next Page
	if ($page < ceil($totalrows/$perpage)){
	$nextlink .="<a href='$fscriptname?$url&amp;page=$next'><img src='../images/next-button.png' width='64' height='25' alt='next' /></a> ";
	}else{
	$nextlink .=" <img src='../images/next-button.png' width='64' height='25' alt='next' />";
	}

//	Show All
	$alllink .="<a href='$fscriptname?$url&amp;page=all'><br /><img src='../images/view-all-button.gif' width='64' height='25' alt='show all' /></a> ";

///////////////////////////////////////////////////////////							end, Pagination							///////////////////////////////////////////////////////////

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
$plinks.="<a href='$fscriptname?$url&amp;page=all'>[show all]</a> ";

$t=time();
$sql="SELECT * FROM jacks WHERE id = '' OR id != '' $where order by $orderby LIMIT $perpage OFFSET ".($perpage*($page-1));
$sth=db_execute($dbh,$sql);

// Display Results
$currow=0;
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
$currow++;

// Table Row
  if ($currow%2) $c="class='dark'";
  else $c="";

  echo "\n<tr $c>".
       "<td><a class='editiditm icon edit' title='Edit' href='$fscriptname?action=editjack&amp;id=".$r['id']."'><span>".$r['id']."</span></a>";

// Username
  $user=isset($userlist[$r['userid']])?$userlist[$r['userid']]['username']:"";


  echo 	"</td>".
		"\n  <td>".$r['jackname']."</td>".
		"\n  <td>".$locations[$r['locationid']]['name']."</td>\n".
		"\n  <td><center>".$locareas[$r['locareaid']]['areaname']."</center></td>\n".
		"\n  <td>".$r['switchname']."</td>".
		"\n  <td><center>".$r['modport']."</center></td>".
		"\n  <td>".$r['userdev']."</td>".
		"\n  <td>".$r['notes']."</td>";

if ($expand){ //display more columns
	echo	"<td><center><input type='image' src='images/delete.png' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>".
				"<input type=hidden name='action' value='$action'>".
				"<input type=hidden name='id' value='$id'></td>";
	echo	"\n  <td><center>".$r['wallcoord']."</center></td>";
	echo	"\n  <td>".$departments[$r['departmentsid']]['name']."</td>\n";
	echo	"\n  <td><center>".$vlans[$r['vlanid']]['vlanid']." [".$vlans[$r['vlanname']]['vlanname']."]</center></td>\n";
	echo	"\n  <td><center>".$r['pubipnet']."</center></td>";
	echo	"\n  <td><center>".$r['pubiphost']."</center></td>";
	echo	"\n  <td><center>".$r['privipnet']."</center></td>";
	echo	"\n  <td><center>".$r['priviphost']."</center></td>";
	echo	"\n  <td>".$r['groupname']."</td>";
}else{
	echo "<td><center><input type='image' src='images/delete.png' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>".
    "<input type=hidden name='action' value='$action'>".
    "<input type=hidden name='id' value='$id'>";
	}
  echo "</td></tr>";
}

$sth->closeCursor();

if ($export) {
  echo "</tbody>\n</table>\n";
  exit;
}
else {
    $cs=9;

?>
  <tr><td colspan='<?php echo $cs?>' class=tdc><button type=submit><img src='images/search.png'>Search</button></td></tr>
  </tbody>
  </table>
  <input type='hidden' name='action' value='<?php echo $_GET['action']?>'>
  </form>

<?php  ///////////////////////////////////////////////////////////							Pagination Links							///////////////////////////////////////////////////////////?>

<div class='gray'>
  <br /><b><?php echo $totalrows?> results<br>
	<?php if ($page >= 1 && $page != "all"){
		echo $prevlink;
	}
	if ($page != "all"){
	// Function to generate pagination array - that is a list of links for pages navigation
    function paginate ($base_url, $query_str, $total_pages, $page, $perpage)
    {
        // Array to store page link list
        $page_array = array ();
        // Show dots flag - where to show dots?
        $dotshow = true;
        // walk through the list of pages
        for ( $i = 1; $i <= $total_pages; $i ++ )
        {
           // If first or last page or the page number falls 
           // within the pagination limit
           // generate the links for these pages
           if ($i == 1 || $i == $total_pages || 
                 ($i >= $page - $perpage && $i <= $page + $perpage) )
           {
              // reset the show dots flag
              $dotshow = true;
              // If it's the current page, leave out the link
              // otherwise set a URL field also
              if ($i != $page)
                  $page_array[$i]['url'] = $base_url . $query_str .
                                             "=" . $i;
              $page_array[$i]['text'] = strval ($i);
           }
           // If ellipses dots are to be displayed
           // (page navigation skipped)
           else if ($dotshow == true)
           {
               // set it to false, so that more than one 
               // set of ellipses is not displayed
               $dotshow = false;
               $page_array[$i]['text'] = "...";
           }
        }
        // return the navigation array
        return $page_array;
    }
    // To use the pagination function in a 
    // PHP script to display the list of links
    // paginate total number of pages ($pc) - current page is $page and show
    // 3 links around the current page
    $pages = paginate ("?$url&amp;", "page", ($pc - 1), $page, 3); ?>

    <?php 
    // list display
    foreach ($pages as $page) {
        // If page has a link
        if (isset ($page['url'])) { ?>
            <a href="<?php echo $page['url']?>">
    		<?php echo $page['text'] ?>
    	</a>
    <?php }
        // no link - just display the text
         else 
            echo $page['text'];
    }
	}?>
	<?php if ($page >= 1 && $page != "all"){
		echo $nextlink."<br />";
	}else
	?>
	<?php if ($page != "all"){
		echo $alllink."<br />";
	}else
	?>
	<a href='<?php echo "$fscriptname?action=$action&amp;export=1"?>'><img src='images/xcel2.jpg' height=25 border=0>Export to Excel
    
<?php  ///////////////////////////////////////////////////////////							end, Pagination	Links						///////////////////////////////////////////////////////////?>

<?php 
}

if ($export) {
  echo "\n</body>\n</html>\n";
  exit;
}

?>