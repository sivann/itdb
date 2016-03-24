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
/* Spiros Ioannou 2009 , sivann _at_ gmail.com */
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
//	Get item types
$sql="SELECT * from itemtypes order by typedesc";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $itypes[$r['id']]=$r;
$sth->closeCursor();
$sql="SELECT * from items order by id";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $itemlist[$r['id']]=$r;
$sth->closeCursor();
$sql="SELECT * from users order by username";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $userlist[$r['id']]=$r;
$sth->closeCursor();
$sql="SELECT * from locations order by name,floor";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $locations[$r['id']]=$r;
$sth->closeCursor();
$sql="SELECT * from locareas order by areaname";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $locareas[$r['id']]=$r;
$sth->closeCursor();
$sql="SELECT * from racks";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $racks[$r['id']]=$r;
$sth->closeCursor();
$sql="SELECT * from tags order by name";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $tags[$r['id']]=$r;
$sth->closeCursor();
$sql="SELECT * from vlans order by id";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $vlans[$r['id']]=$r;
$sth->closeCursor();
$sql="SELECT * from departments order by id";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $departments[$r['id']]=$r;
$sth->closeCursor();
$sql="SELECT id,title FROM agents";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $agents[$r['id']]=$r;
$sth->closeCursor();
$sql="SELECT * FROM statustypes";
$sth=$dbh->query($sql);
$statustypes=$sth->fetchAll(PDO::FETCH_ASSOC);
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
// Records Shown Amount
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
/// display list
if ($export) 
  echo "\n<table border='1'>\n";
else {
  echo "<h1>Items <a title='Add new item' href='$scriptname?action=edititem&amp;id=new'>".
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
  $orderby="items.id asc";
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
$thead= "\n<tr><th><a href='$fscriptname?$url&amp;orderby=items.id$ob'>ID</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=itemtypeid$ob'>Item type</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=locationid$ob'>Building [Floor]</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=locareaid$ob'>Area/Room</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=manufacturerid$ob'>Manufacturer</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=model$ob'>Model</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=dnsname$ob'>DNS Name</th>".
     "<th><a href='$fscriptname?$url&amp;orderby=sn$ob,sn2$ob,sn3$ob'>S/N</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=asset$ob'>Asset #</th>".
     //"<th><a href='$fscriptname?$url&amp;orderby=purchasedate$ob'>Purch. Date</a></th>".
     "<th title='Warranty expiration in '><small><a href='$fscriptname?action=$action&amp;orderby=remdays$ob'>Warr. Left</a></small></th>".
     //"<th><a href='$fscriptname?$url&amp;orderby=userid$ob'>User</a></th>".
     //"<th><a href='$fscriptname?$url&amp;orderby=rackid$ob'>Rack</a></th>".
	 "<th><button type='submit'><img border=0 src='images/search.png'></button></th>";
if ($export) {
 //clean links from excel export
  $thead = preg_replace('@<a[^>]*>([^<]+)</a>@si', '\\1 ', $thead); 
  $thead = preg_replace('@<img[^>]*>@si', ' ', $thead); 
}
echo $thead;
if ($expand) {
  echo "\n <th><a href='$fscriptname?$url&amp;orderby=status$ob'>Status</a></th>";
  echo "\n <th>Dept<br />Abbr</th>";
  echo "\n <th>VLAN ID</th>";
//  echo "\n <th>Tags</th>";
//  echo "<th>Invoices</th>".  "<th>S/W</th>";
//  echo "\n <th>purchprice</th>";
  echo "\n <th>MAC</th>";
  echo "\n <th>IPv4</th>";
//  echo "\n <th>ipv6</th>";
//  echo "\n <th>remadmip</th>";
//  echo "\n <th>panelport</th>";
//  echo "\n <th>switch</th>";
//  echo "\n <th>switchport</th>";
  echo "\n <th>Ports<br />Qty</th>";
}
else
//  echo "<th>&nbsp;</th>";//more icon
echo "</tr>\n</thead>\n";
echo "\n<tbody>\n";
echo "\n<tr>";
//create pre-fill form box vars
$id=isset($_GET['id'])?($_GET['id']):"";
$manufacturer=isset($_GET['manufacturer'])?($_GET['manufacturer']):"";
$dnsname=isset($_GET['dnsname'])?($_GET['dnsname']):"";
$model=isset($_GET['model'])?($_GET['model']):"";
$sn=isset($_GET['sn'])?($_GET['sn']):"";
$asset=isset($_GET['asset'])?($_GET['asset']):"";
//$year=isset($_GET['year'])?($_GET['year']):"";
$page=isset($_GET['page'])?$_GET['page']:1;
$status=isset($_GET['status'])?$_GET['status']:"";
$abbr=isset($_GET['abbr'])?$_GET['abbr']:"";
$vlanid=isset($_GET['vlanid'])?$_GET['vlanid']:"";
$macs=isset($_GET['macs'])?$_GET['macs']:"";
$ipv4=isset($_GET['ipv4'])?$_GET['ipv4']:"";
$ports=isset($_GET['ports'])?$_GET['ports']:"";
///display search boxes
if (!$export) {
  echo "\n<td title='ID'><input type=text size=3 style='width:8em;' value='$id' name='id'></td>";
  echo "\n<td title='Item Type'>\n<select name='itemtypeid'>\n<option value=''>All</option>\n";
  foreach ($itypes as $itype) {
    $dbid=$itype['id'];
    $itype=$itype['typedesc'];
    $s="";
    if (isset($_GET['itemtypeid']) && $_GET['itemtypeid']=="$dbid") $s=" SELECTED ";
    echo "<option $s value='$dbid' title='$dbid'>$itype</option>\n";
  }
  echo "</select></td>";?>
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
  echo "<td title='H/W Manufacturer'><input style='width:auto' type=text value='$manufacturer' name='manufacturer'></td>";
  echo "<td title='Model'><input style='width:auto' type=text value='$model' name='model'></td>";
  echo "<td title='DNS Name'><input style='width:auto' type=text value='$dnsname' name='dnsname'></td>";
  echo "<td title='Serial #'><input style='width:auto' type=text value='$sn' name='sn'></td>";
  echo "<td title='Corporate Tracking # (Asset Tag)'><input type=text value='$asset' name='asset'></td>";
  //echo "<td><input style='width:auto' type=text value='$year' name='year'></td>";
  echo "<td><center>-</center></td>";
//  echo "<td>";
//  echo "\n<select name='userdesc'>\n<option value=''>All</option>\n";
//  foreach ($userlist as $u) {
//    $dbid=$u['id']; 
//    $itype=$u['userdesc']; $s="";
//    if (isset($_GET['userid']) && $_GET['userid']==$u['id']) $s=" SELECTED ";
//    echo "<option $s value='$itype' title='$dbid'>$itype</option>\n";
//  }
//  <?php
//  echo "\n<td><select name='rackid'>\n<option value=''>All</option>\n";
//  foreach ($racks as $r) {
//    $dbid=$r['id']; 
//    $itype=$r['label'].",".$r['usize']."U ". $r['model'];
//    $s="";
//    if ($rackid=="$dbid") $s=" SELECTED ";
//    echo "<option $s value='$dbid'>$dbid-$itype</option>\n";
//  }
//  echo "</select>\n";
//  echo "</td>";
  if ($expand) {
    echo "<td>&nbsp;</td>";?>
	<td><select name='status'>
			<option $s value=''>All</option>
			<?php 
                for ($i=0;$i<count($statustypes);$i++) {
                $dbid=$statustypes[$i]['id']; $itype=$statustypes[$i]['statusdesc']; $s="";
                if ($status==$dbid) $s=" SELECTED ";
                    echo "<option $s value='$dbid'>$itype</option>\n";
                }
            ?>
    </select></td>
<?php
	echo "<td title='Department Abbreviation'><input style='width:auto' type=text value='$abbr' name='abbr'></td>";
	echo "<td title='VLAN ID'><input style='width:auto' type=text value='$vlanid' name='vlanid'></td>";
	echo "<td title='MAC Address'><input style='width:auto' type=text value='$macs' name='macs'></td>";
	echo "<td title='IPv4 Address'><input style='width:auto' type=text value='$ipv4' name='ipv4'></td>";
	//echo "<td title='Number of Switch Ports'><input style='width:auto' type=text value='$ports' name='port'></td>";
	echo "	<td><select name='ports'>
				<option value='0'>0</option>";
					for ($i=1;$i<61;$i++)
					{
						$s="";
						if ($ports=="$i") $s=" SELECTED ";
						echo "  <option $s value='$i'>$i</option>\n";
					}
				"</select>\n
			</td>
			<td colspan='10'>&nbsp;</td>\n";
  }
  else {
    $url=http_build_query($_GET);
    echo "<td style='vertical-align:top;'>".
         "<a alt='More' title='Show more Columns' href='$fscriptname?$url&amp;expand=1'>".
         "<img src='images/more.png'></a></td>";
  }
  echo "</tr>\n\n";
}//if not export to excel: searchboxes
/// create WHERE clause
$where=" AND agtitle like '%$manufacturer%' and model like '%$model%' ".
       " AND (sn like '%$sn%' or sn2 like  '%$sn%'  or sn3 like  '%$sn%')  AND dnsname like '%$dnsname%' ";
//if (strlen($year)) {
//  if (strstr($year,"/")) {
//    $x=explode("/",$year);
//    $y=$x[count($x)-1];
//
//  if ($dateformat=="dmy") 
//    $m=$x[count($x)-2];
//  else
//    $m=$x[count($x)-3];
//
//    $where.=" and purchasedate >= ".mktime(0,0,0,$m,1,$y).
//            " and purchasedate < ".mktime(0,0,0,($m+1),1,$y)." ";
//  }
//  else {
//   $where.=" and purchasedate >= ".mktime(0,0,0,1,1,$year).
//           " and purchasedate < ".mktime(0,0,0,1,1,($year+1))." ";
//  }
//}
if (strlen($id)) $where.=" AND (items.id = '$id') ";
if (strlen($status)) $where.=" AND items.status = '$status' ";
if (strlen($asset)) $where.=" AND items.asset = '$asset' ";
if (strlen($ports)) $where.=" AND items.ports = '$ports' ";
//itemtypeid here:
if (isset($itemtypeid) && strlen($itemtypeid)) $where.=" AND itemtypeid=$itemtypeid ";
if (isset($userid) && strlen($userid)) $where.=" AND userid=$userid ";
if (isset($locationid) && strlen($locationid)) $where.=" AND locationid='$locationid' ";
if (isset($rackid) && strlen($rackid)) $where.=" AND rackid=$rackid ";
if (isset($departmentsid) && strlen($departmentsid)) $where.="AND departmentsid LIKE '%$departmentsid%' ";
if (isset($vlanid) && strlen($vlanid)) $where.="AND vlanid LIKE '%$vlanid%' ";
///////////////////////////////////////////////////////////							Pagination							///////////////////////////////////////////////////////////
//	How many records are in table
$sql="SELECT count(items.id) as totalrows, agents.title as agtitle FROM items,agents WHERE agents.id=items.manufacturerid $where";
$sth=db_execute($dbh,$sql);
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
	$alllink .="<a href='$fscriptname?$url&amp;page=all'><img src='../images/view-all-button.gif' width='64' height='25' alt='show all' /></a>";
///////////////////////////////////////////////////////////							end, Pagination							///////////////////////////////////////////////////////////
$t=time();
$sql="SELECT items.*,agents.title AS agtitle, (purchasedate+warrantymonths*30*24*60*60-$t)/(60*60*24) AS remdays ".
     " FROM items, agents WHERE agents.id=items.manufacturerid $where ".
     " order by $orderby LIMIT $perpage OFFSET ".($perpage*($page-1));
$sth=db_execute($dbh,$sql);
/// display results
$currow=0;
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
$currow++;
  $sqlinv="SELECT invoices.* from invoices,item2inv where item2inv.itemid={$r['id']} AND invoices.id=item2inv.invid";
  $sthinv=db_execute($dbh,$sqlinv);
  $invoices=$sthinv->fetchAll(PDO::FETCH_ASSOC);
  $sqlsoft="SELECT software.*, software.id as softwareid , invoices.* FROM software,item2soft,invoices WHERE ".
           " item2soft.itemid={$r['id']} ".
	   " AND software.id=item2soft.softid ".
	   " AND invoices.id=software.invoiceid ";
  $sthsoft=db_execute($dbh,$sqlsoft);
  $software=$sthsoft->fetchAll(PDO::FETCH_ASSOC);
  //2seconds
  //$d=strlen($r['purchasedate'])?date($dateparam,$r['purchasedate']):"-"; 
  if (isset($locations[$r['locationid']])) {
    $i=$r['locationid'];
    $loc=$locations[$i]['name'];
  }
  else $loc="";
//  $user=isset($userlist[$r['userid']])?$userlist[$r['userid']]['username']:"";
//
//  if (isset($racks[$r['rackid']])) {
//    $i=$r['rackid'];
//    $rack=$racks[$i]['label']." ".$racks[$i]['model']." ".$racks[$i]['usize']."U";
//  }
//  else $rack="";
  //invoice links
  $invinfo="";
  if (isset($invoices[0]['id'])) {
    $cinv=count($invoices);
    for ($ninv=0;$ninv<$cinv;$ninv++) {
      $dinv=strlen($invoices[$ninv]['date'])?date($dateparam,$invoices[$ninv]['date']):"";
      $invinfo.="<b>&bull;</b>";
      $invinfo.="<a title='view invoice' href='$fscriptname?action=editinvoice&amp;id={$invoices[$ninv]['id']}'>".
                  "{$invoices[$ninv]['number']}</a>";
      //else $invinfo.="{$invoices[$ninv]['number']}";
      $invinfo.=" {$agents[$invoices[$ninv]['vendorid']]['title']}<span style='font-family:serif'>&rarr;</span>"; //sans-serif arrows suck for somereason
      $invinfo.="{$agents[$invoices[$ninv]['buyerid']]['title']} $dinv";
      if ($ninv<($cinv-1)) $invinfo.= "<br>";
    }
  }
 //software links
 $softinfo="<table border=1 class=brdr style='margin:0'>";
  if (isset($software[0]['softwareid'])) {
    $csof=count($software);
    for ($nsof=0;$nsof<$csof;$nsof++) {
      $softinfo.="\n<tr><td style='width:150px'><b>".($nsof+1)."</b>-";
      $softinfo.="<a title='Edit software' href='$fscriptname?action=editsoftware&amp;id={$software[$nsof]['softwareid']}'>".
		"{$software[$nsof]['scompany']}&nbsp;{$software[$nsof]['stitle']}&nbsp;{$software[$nsof]['sversion']}</a></td>";
      $invflink="<td style='width:20%'><a title='edit invoice' href='$fscriptname?action=editinvoice&amp;id={$software[$nsof]['invoiceid']}'>".
                "{$software[$nsof]['number']}</a></td>";
      $dinv=strlen($software[$nsof]['date'])?date($dateparam,$software[$nsof]['date']):"";
      $softinfo.="<td> $invflink</td>";
      $softinfo.="<td>{$agents[$software[$nsof]['vendorid']]['title']}</td>";
      $softinfo.="<td>{$agents[$software[$nsof]['buyerid']]['title']}</td><td>$dinv</td></tr>";
      //if ($nsof<($csof-1)) $softinfo.= "<br>";
    }
  }
  $softinfo.="</table>\n";
  $remdays=$r['remdays'];
  if (abs($remdays)>360) $remw=sprintf("%.1f",($remdays/360))."yr";
  else if (abs($remdays)>70) $remw=sprintf("%.1f",($remdays/30))."mon";
  else if (strlen($remdays)) $remw="$remdays"."d";
  else $remw="";
  
  if ($remdays<0) $remw="<span style='color:#F90000'>$remw</span>";
  if ($remdays>0) $remw="<span style='color:green'>$remw</span>";
  if (abs($remdays)>360*20) $remw="";
  $x=attrofstatus((int)$r['status'],$dbh);
  $attr=$x[0];
  $statustxt=$x[1];
  //table row
  if ($currow%2) $c="class='dark'";
  else $c="";
  echo "\n<tr $c>".
       "<td><a class='editiditm icon edit' title='Edit' href='$fscriptname?action=edititem&amp;id=".$r['id']."'><span>".$r['id']."</span></a><span $attr></span>";
  $sn="";
  if (strlen($r['sn'])) $sn.=$r['sn'];
  if (strlen($r['sn2'])) {if (strlen($sn)) $sn.=", ";} $sn.=$r['sn2'];
  if (strlen($r['sn3'])) {if (strlen($sn)) $sn.=", ";} $sn.=$r['sn3'];
  //username
  $user=isset($userlist[$r['userid']])?$userlist[$r['userid']]['username']:"";
  echo	"</td>".
		"\n  <td>".$itypes[$r['itemtypeid']]['typedesc']."</td>".
		"\n  <td>".$locations[$r['locationid']]['name']."</td>\n".
		"\n  <td><center>".$locareas[$r['locareaid']]['areaname']."</center></td>\n".
		"\n  <td>".$r['agtitle']."&nbsp;</td>".
		"\n  <td width='auto'><center>".$r['model']."</center></td>".
		"\n  <td><center>".$r['dnsname']."</center></td>".
		"\n  <td><center>$sn</center></td>".
		"\n  <td><center>".$r['asset']."</center></td>".
		//"\n  <td><center>$d</center></td>".
		"\n  <td class='monospaced'><center>$remw</center></td>";
		//"\n  <td>$userdesc</td>".
		//"\n  <td>$rack</td>";
  if ($expand) { //display more columns
//    $ispart=$r['ispart']==1?"Y":"N";
//    $rackmountable=$r['rackmountable']==1?"Y":"N";
//
//    if (is_numeric($r['switchid'])) {
//      $sqlsw="SELECT label,model, agents.title as agtitle from items,agents where agents.id=items.manufacturerid AND items.id={$r['switchid']}";
//      $sthsw=db_execute($dbh,$sqlsw);
//      $sw=$sthsw->fetchAll(PDO::FETCH_ASSOC);
//      $sw=$sw[0];
//      $switch=$r['switchid']."-".$sw['label'].", ".$sw['agtitle']." ".$sw['model'];
//    }
//    else 
//      $switch="";
    if ($r['ports']) $ports=$r['ports'];
		//expanded columns -->\n";
//    echo "\n  <td><small>". showtags("item",$r['id'],0). "</small></td>";
//    echo "\n  <td><small>$invinfo</small></td>".
//	 "\n  <td><small>$softinfo</small></td>";
//    echo "\n  <td>".$r['purchprice']."</td>";
	echo "<td><center><input type='image' src='images/delete.png' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>".
    "<input type=hidden name='action' value='$action'>".
    "<input type=hidden name='id' value='$id'></td>";
	echo "\n  <td><center>$statustxt</center></td>";
	echo "\n <td><center>".$departments[$r['departmentsid']]['abbr']."</center></td>";
	echo "\n <td><center>".$vlans[$r['vlanid']]['vlanid']."</center></td>";
    echo "\n  <td>".$r['macs']."</td>";
    echo "\n  <td>".$r['ipv4']."</td>";
//    echo "\n  <td>".$r['ipv6']."</td>";
//    echo "\n  <td>".$r['remadmip']."</td>";
//    echo "\n  <td>".$r['panelport']."</td>";
//    echo "\n  <td>$switch</td>";
//    echo "\n  <td>".$r['switchport']."</td>";
    echo "\n  <td><center>$ports<center></td>";
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
  if ($expand) 
    $cs=25;
  else 
    $cs=15;
?>
  <tr><td colspan='<?php echo $cs?>' class=tdc><button type=submit><img src='images/search.png'>Search
  </tbody></table>
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
		echo $nextlink."<br></br>";
	}else
	?>
	<?php if ($page != "all"){
		echo $alllink;
	}else
	?>
	<a href='<?php echo "$fscriptname?action=$action&amp;export=1"?>'><img src='images/xcel2.jpg' height=25 border=0>Export to Excel</div>
    
<?php  ///////////////////////////////////////////////////////////							end, Pagination	Links						///////////////////////////////////////////////////////////?>

  <?php 
}
if ($export) {
  echo "\n</body>\n</html>\n";
  exit;
}
?>