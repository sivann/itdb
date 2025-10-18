<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */



/// get item types
$sql="SELECT * from itemtypes order by description";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $itypes[$r['id']]=$r;
$sth->closeCursor();


$sql="SELECT * from users order by username";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $userlist[$r['id']]=$r;
$sth->closeCursor();


$sql="SELECT * from locations order by name,floor";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $locations[$r['id']]=$r;
$sth->closeCursor();


$sql="SELECT * from racks";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $racks[$r['id']]=$r;
$sth->closeCursor();

$sql="SELECT * from tags order by name";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $tags[$r['id']]=$r;
$sth->closeCursor();

$sql="SELECT id,title FROM agents";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $agents[$r['id']]=$r;
$sth->closeCursor();

$sql="SELECT * FROM status_types";
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


if (!$export) 
  $perpage=18;
else 
  $perpage=10000;

if ($page=="all") {
  $perpage=10000;
}



if ($export)  {
  echo "<html>\n<head><meta http-equiv=\"Content-Type\"".
     " content=\"text/html; charset=UTF-8\" /></head>\n<body>\n";
}



/// display list
if ($export) 
  echo "\n<table border='1'>\n";
else {
  echo "<h1>Items <sup>2</sup> <a title='Add new item' href='$scriptname?action=edititem&amp;id=new'>".
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
  $orderby="items.id desc";
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
$thead= "\n<tr><th title='Search'>".
     "<button type='submit' style='padding:1px;'><img border=0  src='images/search.png'></button>".
     "<a href='$fscriptname?$url&amp;orderby=items.id$ob'>ID</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=label$ob'>Label</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=item_type_id$ob'>Item type</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=manufacturer_id$ob'>Manufacturer</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=model$ob'>Model</a></th>".
     "<th>DNS Name</th>".
     "<th><a href='$fscriptname?$url&amp;orderby=sn$ob,sn2$ob,sn3$ob'>S/N</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=purchase_date$ob'>Purch. Date</a></th>".
     "<th title='Warranty expiration in '><small><a href='$fscriptname?action=$action&amp;orderby=remdays$ob'>Warr. Left</a></small></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=user_id$ob'>User</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=status$ob'>Status</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=location_id$ob'>Location</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=rack_id$ob'>Rack</a></th>";

if ($export) {
 //clean links from excel export
  $thead = preg_replace('@<a[^>]*>([^<]+)</a>@si', '\\1 ', $thead); 
  $thead = preg_replace('@<img[^>]*>@si', ' ', $thead); 
}

echo $thead;

if ($expand) {
  echo "\n <th>Tags</th>";
  echo "<th>Invoices</th>".  "<th>S/W</th>";
  echo "\n <th>purchase_price</th>";
  echo "\n <th>macs</th>";
  echo "\n <th>ipv4</th>";
  echo "\n <th>ipv6</th>";
  echo "\n <th>remote_admin_ip</th>";
  echo "\n <th>panel_port</th>";
  echo "\n <th>switch</th>";
  echo "\n <th>switch_port</th>";
  echo "\n <th>ports</th>";
}
else
  echo "<th>&nbsp;</th>";//more icon
echo "</tr>\n</thead>\n";


echo "\n<tbody>\n";
echo "\n<tr>";

//create pre-fill form box vars
$id=isset($_GET['id'])?($_GET['id']):"";
$manufacturer=isset($_GET['manufacturer'])?($_GET['manufacturer']):"";
$dns_name=isset($_GET['dns_name'])?($_GET['dns_name']):"";
$model=isset($_GET['model'])?($_GET['model']):"";
$sn=isset($_GET['sn'])?($_GET['sn']):"";
$year=isset($_GET['year'])?($_GET['year']):"";
$page=isset($_GET['page'])?$_GET['page']:1;
$status=isset($_GET['status'])?$_GET['status']:"";

///display search boxes
if (!$export) {

  echo "\n<td title='ID'><input type=text size=3 style='width:6em;' value='$id' name='id'></td>";
  echo "\n<td>\n<select name='item_type_id'>\n<option value=''>All</option>\n";
  foreach ($itypes as $itype) {
    $dbid=$itype['id'];
    $itype=$itype['typedesc'];
    $s="";
    if (isset($_GET['item_type_id']) && $_GET['item_type_id']=="$dbid") $s=" SELECTED ";
    //echo "<option $s value='$dbid'>".sprintf("%02d",$dbid)."-$itype</option>\n";
    echo "<option $s value='$dbid' title='$dbid'>$itype</option>\n";
  }
  echo "</select>\n</td>\n";

  echo "<td title='H/W Manufacturer'><input style='width:8em' type=text value='$manufacturer' name='manufacturer'></td>";
  echo "<td><input style='width:11em' type=text value='$model' name='model'></td>";
  echo "<td><input style='width:8em' type=text value='$dns_name' name='dns_name'></td>";
  echo "<td><input style='width:8em' type=text value='$sn' name='sn'></td>";
  echo "<td><input style='width:7em' type=text value='$label' name='label'></td>";
  echo "<td><input style='width:7em' type=text value='$year' name='year'></td>";
  echo "<td>-</td>";

  echo "<td>";
  echo "\n<select name='user_id'>\n<option value=''>All</option>\n";
  foreach ($userlist as $u) {
    $dbid=$u['id']; 
    $itype=$u['username']; $s="";
    if (isset($_GET['user_id']) && $_GET['user_id']==$u['id']) $s=" SELECTED ";
    echo "<option $s value='$dbid' title='$dbid'>$itype</option>\n";
  }
?>
  </select>
  </td>

  <td>

  <select name='status'>
  <option $s value=''>All</option>

  <?php 
  for ($i=0;$i<count($statustypes);$i++) {
    $dbid=$statustypes[$i]['id']; $itype=$statustypes[$i]['statusdesc']; $s="";
    if ($status==$dbid) $s=" SELECTED ";
    echo "<option $s value='$dbid'>$itype</option>\n";
  }
  ?>
  </select>
  </td>

  <td><select name='location_id'>
      <option value=''>All</option>
<?php 
  foreach ($locations as $l) {
    $dbid=$l['id']; 
    $s="";

    $itype=$l['name']." Flr ".$l['floor'];
    if (isset($_GET['location_id']) && $_GET['location_id']=="$dbid") $s=" SELECTED ";
    echo "<option $s value='$dbid' title='$dbid'>$itype</option>\n";
  }
  echo "</select>\n";
  echo "</td>";




  echo "\n<td><select name='rack_id'>\n<option value=''>All</option>\n";
  foreach ($racks as $r) {
    $dbid=$r['id']; 
    $itype=$r['label'].",".$r['usize']."U ". $r['model'];
    $s="";
    if ($rack_id=="$dbid") $s=" SELECTED ";
    echo "<option $s value='$dbid'>$dbid-$itype</option>\n";
  }
  echo "</select>\n";
  echo "</td>";



  if ($expand) {
    echo "<td>&nbsp;</td>";
    echo "<td>&nbsp;</td>";
    echo "<td colspan='10'>&nbsp;</td>\n";
  }
  else {
    $url=http_build_query($_GET);
    echo "<td style='vertical-align:top;' rowspan=".($perpage+1).">".
         "<a alt='More' title='Show more Columns' href='$fscriptname?$url&amp;expand=1'>".
         "<img src='images/more.png'></a></td>";
  }
  echo "</tr>\n\n";

}//if not export to excel: searchboxes

/// create WHERE clause
$where=" AND agtitle like '%$manufacturer%' and model like '%$model%' ".
       " AND (sn like '%$sn%' or sn2 like  '%$sn%'  or sn3 like  '%$sn%' )  AND dns_name like '%$dns_name%' ";
if (strlen($year)) {
  if (strstr($year,"/")) {
    $x=explode("/",$year);
    $y=$x[count($x)-1];

  if ($dateformat=="dmy") 
    $m=$x[count($x)-2];
  else
    $m=$x[count($x)-3];

    $where.=" and purchase_date >= ".mktime(0,0,0,$m,1,$y).
            " and purchase_date < ".mktime(0,0,0,($m+1),1,$y)." ";
  }
  else {
   $where.=" and purchase_date >= ".mktime(0,0,0,1,1,$year).
           " and purchase_date < ".mktime(0,0,0,1,1,($year+1))." ";
  }
}

if (strlen($id)) $where.=" AND items.id = '$id' ";
if (strlen($status)) $where.=" AND items.status = '$status' ";

//item_type_id here:
if (isset($item_type_id) && strlen($item_type_id)) $where.=" AND item_type_id=$item_type_id ";
if (isset($user_id) && strlen($user_id)) $where.=" AND user_id=$user_id ";
if (isset($location_id) && strlen($location_id)) $where.=" AND location_id='$location_id' ";
if (isset($rack_id) && strlen($rack_id)) $where.=" AND rack_id=$rack_id ";
if (isset($label) && strlen($label)) $where.=" AND label like '%$label%' ";


//calculate total returned rows
$sth=db_execute($dbh,"SELECT count(items.id) as totalrows, agents.title as agtitle FROM items,agents WHERE agents.id=items.manufacturer_id $where");
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
$plinks.="<a href='$fscriptname?$url&amp;page=all'>[show all]</a> ";

$t=time();
$sql="SELECT items.*,agents.title AS agtitle, (purchase_date+warranty_months*30*24*60*60-$t)/(60*60*24) AS remdays ".
     " FROM items, agents WHERE agents.id=items.manufacturer_id $where ".
     " order by $orderby LIMIT $perpage OFFSET ".($perpage*($page-1));


//echo $sql;
/// make db query
$sth=db_execute($dbh,$sql);

/// display results
$currow=0;
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
$currow++;

  $sqlinv="SELECT invoices.* from invoices,item2inv where item2inv.item_id={$r['id']} AND invoices.id=item2inv.invid";
  $sthinv=db_execute($dbh,$sqlinv);
  $invoices=$sthinv->fetchAll(PDO::FETCH_ASSOC);

  $sqlsoft="SELECT software.*, software.id as software_id , invoices.* FROM software,item2soft,invoices WHERE ".
           " item2soft.item_id={$r['id']} ".
	   " AND software.id=item2soft.software_id ".
	   " AND invoices.id=software.invoice_id ";
  $sthsoft=db_execute($dbh,$sqlsoft);
  $software=$sthsoft->fetchAll(PDO::FETCH_ASSOC);

  //2seconds
  $d=strlen($r['purchase_date'])?date($dateparam,$r['purchase_date']):"-"; 


  if (isset($locations[$r['location_id']])) {
    $i=$r['location_id'];
    $loc=$locations[$i]['name'].",Flr:".$locations[$i]['floor'];
  }
  else $loc="";

  $user=isset($userlist[$r['user_id']])?$userlist[$r['user_id']]['username']:"";

  if (isset($racks[$r['rack_id']])) {
    $i=$r['rack_id'];
    $rack=$racks[$i]['label']." ".$racks[$i]['model']." ".$racks[$i]['usize']."U";
  }
  else $rack="";


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
      $invinfo.=" {$agents[$invoices[$ninv]['vendor_id']]['title']}<span style='font-family:serif'>&rarr;</span>"; //sans-serif arrows suck for somereason
      $invinfo.="{$agents[$invoices[$ninv]['buyer_id']]['title']} $dinv";
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
      $softinfo.="<td>{$agents[$software[$nsof]['vendor_id']]['title']}</td>";
      $softinfo.="<td>{$agents[$software[$nsof]['buyer_id']]['title']}</td><td>$dinv</td></tr>";
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
       "<td nowrap><span $attr>&nbsp;</span><a class='editid' title='Edit' href='$fscriptname?action=edititem&amp;id=".$r['id']."'>".
       $r['id']."</a>";

  $sn="";
  if (strlen($r['sn'])) $sn.=$r['sn'];
  if (strlen($r['sn2'])) {if (strlen($sn)) $sn.=", ";} $sn.=$r['sn2'];
  if (strlen($r['sn3'])) {if (strlen($sn)) $sn.=", ";} $sn.=$r['sn3'];

  //username
  $user=isset($userlist[$r['user_id']])?$userlist[$r['user_id']]['username']:"";


  echo "</td>".
       "\n  <td>".$itypes[$r['item_type_id']]['typedesc']."</td>".
       "\n  <td>".$r['agtitle']."&nbsp;</td>".
       "\n  <td>".$r['model']."</td><td>".$r['dns_name']."&nbsp;</td>".
       "\n  <td class='monospaced'>$sn&nbsp;</td>".
       "\n  <td>{$r['label']}</td>".
       "\n  <td>$d&nbsp;</td>".
       "\n  <td>$remw</td>".
       "\n  <td>$user</td>".
       "\n  <td>$statustxt</td>".
       "\n  <td>$loc</td>".
       "\n  <td>$rack</td>";



  if ($expand) { //display more columns
    $is_part=$r['is_part']==1?"Y":"N";
    $is_rack_mountable=$r['is_rack_mountable']==1?"Y":"N";

    if (is_numeric($r['switch_id'])) {
      $sqlsw="SELECT label,model, agents.title as agtitle from items,agents where agents.id=items.manufacturer_id AND items.id={$r['switch_id']}";
      $sthsw=db_execute($dbh,$sqlsw);
      $sw=$sthsw->fetchAll(PDO::FETCH_ASSOC);
      $sw=$sw[0];
      $switch=$r['switch_id']."-".$sw['label'].", ".$sw['agtitle']." ".$sw['model'];
    }
    else 
      $switch="";


    $ports="";
    if ($r['ports']) $ports=$r['ports'];


    echo "\n<!-- expanded columns -->\n";
    echo "\n  <td><small>". showtags("item",$r['id'],0). "</small></td>";
    echo "\n  <td><small>$invinfo</small></td>".
	 "\n  <td><small>$softinfo</small></td>";
    echo "\n  <td>".$r['purchase_price']."</td>";
    echo "\n  <td>".$r['macs']."</td>";
    echo "\n  <td>".$r['ipv4']."</td>";
    echo "\n  <td>".$r['ipv6']."</td>";
    echo "\n  <td>".$r['remote_admin_ip']."</td>";
    echo "\n  <td>".$r['panel_port']."</td>";
    echo "\n  <td>$switch</td>";
    echo "\n  <td>".$r['switch_port']."</td>";
    echo "\n  <td>$ports</td>";
  }//expand

  echo "\n</tr>\n";
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
  <tr><td colspan='<?php echo $cs?>' class=tdc><button type=submit><img src='images/search.png'>Search</button></td></tr>
  </tbody></table>
  <input type='hidden' name='action' value='<?php echo $_GET['action']?>'>
  </form>

<div class='gray'>
  <b><?php echo $totalrows?> results</b><br>
  <b>Page:</b> <?php echo $plinks?> <br>
  <a href='<?php echo "$fscriptname?action=$action&amp;export=1"?>'><img src='images/xcel2.jpg' height=25 border=0>Export to Excel</a>
</div>

<?php 
}

if ($export) {
  echo "\n</body>\n</html>\n";
  exit;
}

?>
