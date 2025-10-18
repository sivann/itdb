<!-- Spiros Ioannou 2009 , sivann _at_ gmail.com -->
<SCRIPT LANGUAGE="JavaScript"> 

  $(document).ready(function() {
    $('input#invoicefilter').quicksearch('table#invoicelisttbl tbody tr');
    $('input#itemsfilter').quicksearch('table#itemslisttbl tbody tr');
    $('input#softfilter').quicksearch('table#softwarelisttbl tbody tr');
    $('input#contrfilter').quicksearch('table#contrlisttbl tbody tr');

    $("#tabs").tabs();
    $("#tabs").show();

    $("#location_id").change(function() {
      var location_id=$(this).val();
      var location_area_id=$('#location_area_id').val();
      var dataString = 'location_id='+ location_id;//+'&location_area_id='+'<?php echo $location_area_id?>';
      //var dataString2 = 'location_id='+ location_id+'&location_area_id='+location_area_id;

      $.ajax ({
	  type: "POST",
	  url: "php/locarea_options_ajax.php",
	  data: dataString,
	  cache: false,
	  success: function(html) {
	    $("#location_area_id").html(html);
	  }
      });

      $.ajax ({
	  type: "POST",
	  url: "php/racks_perlocarea_ajax.php",
	  data: dataString,
	  cache: false,
	  success: function(html) {
	    $("#rack_id").html(html);
	  }
      });



    });

  });

</SCRIPT>
<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}


if ($id!="new") {
  //get current item data
  $id=$_GET['id'];
  $sql="SELECT * FROM items WHERE id='$id'";
  $sth=db_execute($dbh,$sql);
  $item=$sth->fetchAll(PDO::FETCH_ASSOC);
}


$sql="SELECT * FROM item_types order by description";
$sth=$dbh->query($sql);
$itypes=$sth->fetchAll(PDO::FETCH_ASSOC);

for ($i=0;$i<count($itypes);$i++) {
  $typeid2name[$itypes[$i]['id']]=$itypes[$i]['typedesc'];
}

$sql="SELECT * FROM users order by upper(username)";
$sth=$dbh->query($sql);
$userlist=$sth->fetchAll(PDO::FETCH_ASSOC);

$sql="SELECT * FROM locations order by name";
$sth=$dbh->query($sql);
$locations=$sth->fetchAll(PDO::FETCH_ASSOC);



//$sql="SELECT * FROM racks"; $sth=$dbh->query($sql); $racks=$sth->fetchAll(PDO::FETCH_ASSOC);

$sql="SELECT id,title,type FROM agents order by title";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $agents[$r['id']]=$r;

$sql="SELECT * FROM status_types";
$sth=$dbh->query($sql);
$statustypes=$sth->fetchAll(PDO::FETCH_ASSOC);



$sql="SELECT items.* from items,itemtypes where ".
  " (itemtypes.description like '%switch%' or itemtypes.description like '%router%' ) ".
  " and itemtypes.id=items.item_type_id ";
$sth=$dbh->query($sql);
$netitems=$sth->fetchAll(PDO::FETCH_ASSOC);


//change displayed form items in input fields
if ($id=="new") {
  $caption=t("Add New Item");
  foreach ($formvars as $formvar){
    $$formvar="";
  }
  $d="";
  //$mend="";
}
//if editing, fill in form with data from supplied item id
else if ($action=="edititem") {
  $caption=t("Item Data")." ($id)";
  foreach ($formvars as $formvar){
    $$formvar=$item[0][$formvar];
  }
  //seconds from 1970
  $d=strlen($item[0]['purchase_date'])?date($dateparam,$item[0]['purchase_date']):"";
}
?>

<h1><?php echo $caption?></h1>
<?php echo $disperr;?>

<!-- our error errcontainer -->
<div class='errcontainer ui-state-error ui-corner-all' style='padding: 0 .7em;width:700px;margin-bottom:3px;'>
	<p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>
	<h4><?php te("There are errors in your form submission, please see below for details");?>.</h4>
	<ol>
		<li><label for="item_type_id" class="error"><?php te("Please select item type");?></label></li>
		<li><label for="is_part" class="error"><?php te("Please specify if this item is a part of another");?></label></li>
		<li><label for="is_rack_mountable" class="error"><?php te("Please check if this item can be rackmounted");?></label></li>
		<li><label for="manufacturer_id" class="error"><?php te("Manufacturer is missing");?></label></li>
		<li><label for="model" class="error"><?php te("Specify model");?></label></li>
		<li><label for="user_id" class="error"><?php te("Specify user responsible for this item");?></label></li>
	</ol>
</div>

<div id="tabs">

  <ul>
    <li><a href="#tab1"><span><?php te("Item Data");?></span></a></li>
    <li><a href="#tab2"><?php te("Inter-Item Associations");?></a></li>
    <li><a href="#tab3"><?php te("Invoice Associations");?></a></li>
    <li><a href="#tab4"><?php te("Log/Journal");?></a></li>
    <li><a href="#tab5"><?php te("Software Associations");?></a></li>
    <li><a href="#tab6"><?php te("Contract Associations");?></a></li>
    <li><a href="#tab7"><?php te("Upload Files");?></a></li>
  </ul>

<div id="tab1" class="tab_content">

  <form class='frm1' enctype='multipart/form-data' method=post name='additmfrm' id='mainform'>

  <table border='0' class=tbl1 >
  <tr>
  <td class='tdtop'>
    <table border='0' class=tbl2>
    <tr><td colspan=2><h3><?php te("Intrinsic Properties");?></h3></td></tr>

    <tr>
    <td class='tdt'><?php te("Item Type");?>:<sup class='red'>*</sup></td>
    <td title='<?php te("Populate list from the Item Types menu");?>'>

    <?php 
    echo "\n<select class='mandatory' validate='required:true' name='item_type_id'>\n";
    echo "<option value=''>Select</option>\n";
    for ($i=0;$i<count($itypes);$i++) {
      $dbid=$itypes[$i]['id']; $itype=$itypes[$i]['typedesc']; $s="";
      if ($item_type_id=="$dbid") $s=" SELECTED ";
      echo "<option $s title='id=$dbid' value='$dbid'>$itype</option>\n";
    }
    ?>
      </select>
      </td>
      </tr>

      <tr>

    <?php 
    //is_part
    $y="";$n="";
    if ($is_part=="1") {$y="checked";$n="";}
    if ($is_part=="0") {$n="checked";$y="";}

    ?>
      <td class='tdt'><?php te("Is Part");?>:<sup class='red'>*</sup></td>
      <td title='Select yes for parts/components'>
      <div class='mandatory'>
	<input  validate='required:true' <?php echo $y?> class='radio' type=radio name='is_part' value='1'><?php te("Yes");?>
	<input  class='radio' type=radio <?php echo $n?> name='is_part' value='0'><?php te("No");?>
      </div>
      </td>
      </tr>
      <tr>

    <?php 

    //is_rack_mountable
    $y="";$n="";
    if ($is_rack_mountable=="1") {$y="checked";$n="";}
    if ($is_rack_mountable=="0") {$n="checked";$y="";}

    ?>


      <td class='tdt'><?php te("Rackmountable");?>:<sup class='red'>*</sup></td>
      <td>
      <div class=mandatory>
	<input validate='required:true' class='radio' <?php echo $y?> type=radio name='is_rack_mountable' value='1'><?php te("Yes");?>
	<input class='radio' type=radio <?php echo $n?> name='is_rack_mountable' value='0'><?php te("No");?>
      </div>
      </td></td>
      </tr>


    <?php 
    //manufacturer
    ?>

      <tr>
      <td class='tdt'>

    <?php   if (is_numeric($manufacturer_id)) 
      echo "<a title='Edit selected manufacturer (agent)' href='$scriptname?action=editagent&amp;id=$manufacturer_id'><img src='images/edit.png'></a> "; ?>
      <?php te("Manufact.");?><sup class='red'>*</sup>:</td>

      <td title='<?php te("Populated from H/W Manufacturers defined in agents menu");?>'>

       <select validate='required:true' class='mandatory' name='manufacturer_id'>
       <option value=''><?php te("Select");?></option>
      <?php 
	foreach ($agents as $a) {
	  if (!($a['type']&8)) continue;
	  $dbid=$a['id']; 
	  $atype=$a['title']; $s="";
	  if (isset($manufacturer_id) && $manufacturer_id==$a['id']) $s=" SELECTED ";
	  echo "<option $s value='$dbid' title='$dbid'>$atype</option>\n";
	}
	echo "</select>\n";
      ?>
      </td>

      </tr>

      <tr> <td class='tdt'><?php te("Model");?><sup class='red'>*</sup>:</td><td><input type=text validate='required:true' class='mandatory' value="<?php echo $model?>" name='model'></td> </tr>

      <tr>

    <?php 
    //usize
    ?>
      <td class='tdt' class='tdt'><?php te("Size (U)");?>:</td><td>
      <select name='usize'>
      <option value=''><?php te("Select");?></option>

    <?php 
    for ($i=1;$i<45;$i++) {
      $s="";
      if ($usize=="$i") $s=" SELECTED ";
      echo "<option $s value='$i'>$i</option>\n";
    }
    ?>

    </select>
    </td>

      </tr>

      <tr> <td class='tdt'><?php te("S/N");?>:</td><td><input type=text value='<?php echo $sn?>' name='sn'></td> </tr>
      <tr> <td class='tdt'><?php te("S/N 2");?>:</td><td><input type=text value='<?php echo $sn2?>' name='sn2'></td> </tr>
      <tr>

    <?php 

    //dell service tag
    if (isset($manufacturer_id)) {
      $st=getagenturlbytag($manufacturer_id,"service");
      if (strlen($st)) $st="<a target=_blank href='$st'>Service Tag</a>";
      else $st="Service Tag";
    }
    ?>
      <td class='tdt'><?php echo $st?></td><td><input type=text value='<?php echo $sn3?>' name='sn3'></td>
      </tr>

      <tr> <td class='tdt'><?php te("Comments");?>:</td><td> <textarea wrap='soft' class=tarea1  name='comments'><?php echo $comments?></textarea></td> </tr>

    <tr> <td class='tdt'><?php te("Label");?>:</td><td title='<?php te("show also this text on printable labels");?>'><input type='text' value="<?php echo $label?>" name='label'></td> </tr>
      </table>
    </td>

    <td class='tdtop'>

      <table border='0' class=tbl2><!-- Usage -->
      <tr><td colspan=2 ><h3><?php te("Usage");?></h3></td></tr>

      <tr>

      <?php 
      //status
      ?>
	<td class='tdt'><?php te("Status");?><sup class='red'>*</sup>:</td>
	<td>
	<select validate='required:true' class='mandatory'  name='status'>

      <?php 
      for ($i=0;$i<count($statustypes);$i++) {
	$dbid=$statustypes[$i]['id']; $itype=$statustypes[$i]['statusdesc']; $s="";
	if ($status==$dbid) $s=" SELECTED ";
	echo "<option $s value='$dbid'>$itype</option>\n";
      }
      ?>
      </select>
	</td>
	</tr>


      <?php 
      //user
      ?>

      <tr>
      <td class='tdt'><?php te("User");?><sup class='red'>*</sup>:</td><td title='<?php te("User responsible for this item");?>'>
      <select validate='required:true' class='mandatory' name='user_id'>
      <option value=''><?php te("Select User");?></option>
      <?php 
      for ($i=0;$i<count($userlist);$i++) {
	$dbid=$userlist[$i]['id']; $itype=$userlist[$i]['username']; $s="";
	if ($user_id==$dbid) $s=" SELECTED ";
	//echo "<option $s value='$dbid'>".sprintf("%02d",$dbid)."-$itype</option>\n";
	echo "<option $s value='$dbid'>$itype</option>\n";
      }
      ?>

      </select>
      </td>
      </tr>

      <tr>
      <?php 
      //location
      ?>
      <td class='tdt' class='tdt'><?php te("Location");?>:</td>
      <td>
	<select id='location_id' name='location_id'>
	<option value=''><?php te("Select");?></option>
	<?php 
	foreach ($locations  as $key=>$location ) {
	  $dbid=$location['id']; 
	  $itype=$location['name'].", Floor:".$location['floor'];

      if (is_numeric($location['floor']))
          $itype=$location['name'].", ".t("Floor").":".$location['floor'];
      else
          $itype=$location['name'];
	  $s="";
	  if (($location_id=="$dbid")) $s=" SELECTED "; 
	  echo "    <option $s value='$dbid'>$itype</option>\n";
	}
	?>
	</select>

      </td>
      </tr>

      <tr>
      <?php 
      //area
      if (is_numeric($location_id)) {
	$sql="SELECT * FROM location_areas WHERE location_id=$location_id order by areaname";
	$sth=$dbh->query($sql);
	$locareas=$sth->fetchAll(PDO::FETCH_ASSOC);
      } 
      else 
	$locareas=array();
      ?>
      <td class='tdt' class='tdt'><?php te("Area/Room");?>:</td>
      <td>
	<select id='location_area_id' name='location_area_id'>
	  <option value=''><?php te("Select");?></option>
	  <?php 
	  foreach ($locareas  as $key=>$locarea ) {
	    $dbid=$locarea['id']; 
	    $itype=$locarea['areaname'];
	    $s="";
	    if (($location_area_id=="$dbid")) $s=" SELECTED "; 
	    echo "    <option $s value='$dbid'>$itype</option>\n";
	  }
	  ?>
     

	</select>

      </td>
      </tr>



      <tr>
      <?php 
      //rack_id
      echo "\n<td class='tdt' class='tdt'>";
      if (is_numeric($rack_id)) 
	//echo "<a alt='View' title='".t("view rack")."' href='$scriptname?action=viewrack&amp;id=$rack_id&amp;highlightid=$id'><img height=12 src='images/eye.png'></a> ";
	echo "<a id=viewrack alt='View' title='".t("view rack")."' href='$scriptname?action=viewrack&amp;id=$rack_id&amp;highlightid=$id&amp;nomenu=1'><img height=12 src='images/eye.png'></a> ";
	echo "<a alt='Edit' title='".t("edit rack")."' href='$scriptname?action=editrack&amp;id=$rack_id&amp;highlightid=$id'><img src='images/edit.png'></a> ";
      ?>

      <script type="text/javascript"> 
	$('a#viewrack').popupWindow({ 
	  centerScreen:1,
	  height:800, 
	  scrollbars:1,
	  width:700, 
	  windowName:'viewrack', 
	}); 
      </script>

      Rack:</td>
      <td>
      <select id='rack_id' name='rack_id'>
      <option value=''><?php te("Select");?></option>
      <?php 
      if (is_numeric($location_id)) {
	$sql="SELECT * FROM racks WHERE location_id=$location_id order by label,id";
	$sth=$dbh->query($sql);
	$racks=$sth->fetchAll(PDO::FETCH_ASSOC);
      } 
      else 
	$racks=array();

      for ($i=0;$i<count($racks);$i++) {
	$dbid=$racks[$i]['id']; 
	$itype=$racks[$i]['label'].",".$racks[$i]['usize']."U ". $racks[$i]['model'];
	$s="";
	if ($rack_id=="$dbid") $s=" SELECTED ";
	echo "<option $s value='$dbid'>$dbid:$itype</option>\n";
      }
      ?>
      </select></td>
      </tr>

      <tr>

      <?php 
      //rack_position
      ?>
      <td class='tdt' class='tdt'><?php te("Rack Pos. (topmost)");?>:</td><td>

      <select name='rack_position' title='Rack Row'  style='width:40%'>
      <option value=''><?php te("Select");?></option>
      <?php 
      for ($i=1;$i<51;$i++) {
	$s="";
	if ($rack_position=="$i") $s=" SELECTED ";
	echo "<option $s value='$i'>$i</option>\n";
      }
      ?>
      </select>

      <?php 
	$s="";$s6="";$s3="";$s4="";$s2="";$s1="";$s7="";
	$x="s$rack_position_depth";
	$$x="SELECTED";
      ?>
      <select name='rack_position_depth'  style='width:40%' title='<?php te("Depth of rack occupation. (F)ront, (M)iddle, (B)ack");?>'>
      <option <?php echo $s6?> value='6'>FM-</option>
      <option <?php echo $s3?> value='3'>-MB</option>
      <option <?php echo $s4?> value='4'>F--</option>
      <option <?php echo $s2?> value='2'>-M-</option>
      <option <?php echo $s1?> value='1'>--B</option>
      <option <?php echo $s7?> value='7'>FMB</option>
      </select>

      </td>
      </tr>

      <tr>
	<td class='tdt' title='<?php te("describe the purpose of this item");?>'><?php te("Function");?>:</td>
	<td title='<?php te("How is this item used. e.g.: Video Encoder");?>'><input type=text size=15 value="<?php echo $function?>" name='function'></td> 
      </tr>

      <tr>
      <td class='tdt'><?php te("Maintenance Instructions");?>:</td><td title='<?php te("Special maintenance instructions");?>'>
       <textarea wrap='soft' class=tarea1 name='maintenance_info'><?php echo $maintenance_info?></textarea></td>
      </tr>

	  <tr><td colspan=2 style='padding-top:10px'><h3><?php te("Accounting");?></h3></td></tr>
	  <tr> <td class='tdt'><?php te("Shop/Origin");?>:</td><td title='<?php te("e.g. like donator, etc. Vendor info is best to be provided in the related invoice");?>'><input size=15 value='<?php echo $origin?>' name='origin'></td> </tr>
	  <tr> <td class='tdt'><?php te("Purchace Price (");?><?php echo $settings['currency']?>):</td><td><input size=15 value='<?php echo $purchase_price?>' name='purchase_price'></td> </tr>

      </table><!--/usage-->


    </td>
    <td class='tdtop'>


      <table border='0' class=tbl2> <!-- 2-Warranty & Support -->
      <tr><td colspan=2><h3><?php te("Warranty");?></h3></td></tr>
      <tr>
      <td class='tdt'><?php te("Date of Purchase");?>:</td>
      <td><input  type=text class='dateinp' title='<?php echo $datetitle?>' size=12 id=aa0 value='<?php echo $d?>' name='purchase_date'>
      </td>
      </tr>
      <tr> <td class='tdt'><?php te("Warranty&nbsp;Months");?>:</td><td><input type=text size=15 value='<?php echo $warranty_months?>' name='warranty_months'></td> </tr>
      <tr><td class="tdt"><?php te("Warranty Info");?>:</td> <td><input  size=12 type=text name='warranty_info' value="<?php echo $warranty_info?>"></td></tr>
      <tr><tr><td colspan=2 style='padding-top:10px'><h3>Misc</h3></td> </tr>
      <tr><td class='tdt'><?php te("HDs (TB)");?></td><td  title='Comma separated. E.g. Enter "2, 0.6" for 1x2T+1x600G'><input type=text size=15 value='<?php echo $hd?>' name='hd'></td> </tr>
      <tr><td class='tdt' class='tdt'><?php te("RAM (GB)");?>:</td><td><input type=text size=15 value='<?php echo $ram?>' name='ram'></td> </tr>
      <tr><td class='tdt' class='tdt'><?php te("CPU Model");?>:</td><td title='e.g. Intel E5450'><input type=text size=15 value='<?php echo $cpu?>' name='cpu'></td> </tr>

      <tr><td class='tdt' class='tdt' title='Number of installed CPUs (used for licensing)'><?php te("# CPUs");?>:</td>
	  <td title='Number of installed CPUs (used for licensing)'>
	  <select name='cpu_count'>
	  <option value='0'><?php te("Select");?></option>
	<?php 
	for ($i=1;$i<=20;$i++) {
	  $s="";
	  if ($cpu_count=="$i") $s=" SELECTED ";
	  echo "<option $s value='$i'>$i</option>\n";
	}
	?>
	</select>
	</td>
     </tr>
     <tr><td class='tdt' class='tdt' title='<?php te("Cores per CPU (used for licensing)");?>'><?php te("Cores/CPU");?>:</td>
	    <td title='<?php te("Number of cores per CPU (used for licensing)");?>'>
	    <select name='cores_per_cpu'>
	    <option value='0'><?php te("Select Cores/CPU");?></option>
	  <?php 
	  for ($i=1;$i<=20;$i++) {
	    $s="";
	    if ($cores_per_cpu=="$i") $s=" SELECTED ";
	    echo "<option $s value='$i'>$i</option>\n";
	  }
	  ?>
	  </select>
	  </td>
       </tr>
     </table>

    </td>
    <td class='tdtop'>
      <table border='0' class=tbl2> <!-- 3-Network -->
      <tr><td colspan=2 ><h3><?php te("Network");?></h3></td></tr>
      <tr> <td class='tdt'><?php te("DNS Name");?>:</td><td><input type=text size=15 value='<?php echo $dns_name?>' name='dns_name'></td> </tr>
      <tr> <td class='tdt'>MACs:</td><td><input type=text size=15 value='<?php echo $macs?>' name='macs'></td> </tr>
      <tr> <td class='tdt'>IPv4:</td><td><input type=text size=15 value='<?php echo $ipv4?>' name='ipv4'></td> </tr>
      <tr> <td class='tdt'>IPv6:</td><td><input type=text size=15 value='<?php echo $ipv6?>' name='ipv6'></td> </tr>
      <tr> <td class='tdt'>Rem.Adm.IP:</td><td title='<?php te("Remote Administration IP");?>'><input type=text size=15 value='<?php echo $remote_admin_ip?>' name='remote_admin_ip'></td> </tr>
      <tr> <td class='tdt'><?php te("Ptch.PnlPrt");?>:</td><td title='<?php te("Patch Panel Port");?>'><input type=text size=15 value='<?php echo $panel_port?>' name='panel_port'></td> </tr>

      <tr>

      <td class='tdt' class='tdt'><?php te("Switch");?>:</td><td title='<?php te("populated from items of type switch or router");?>' >
      <select name='switch_id'>
      <option value=''><?php te("Select");?></option>
    <?php 
    for ($i=0;$i<count($netitems);$i++) {
      $dbid=$netitems[$i]['id']; 
      $itype=$agents[$netitems[$i]['manufacturer_id']]['title'].", ". $netitems[$i]['model'];
      $s="";
      if ($switch_id=="$dbid") $s=" SELECTED ";
      echo "  <option class='smalloption' $s value='$dbid'>$dbid:$itype</option>\n";
    }
    ?>
      </select></td>
      </tr>

      <tr><td class='tdt'><?php te("Switch Port");?>:</td><td><input type=text size=15 value='<?php echo $switch_port?>' name='switch_port'></td></tr>

      <tr>
      <td class='tdt' class='tdt'><?php te("Network Ports");?>:</td><td>
      <select name='ports'>
      <option value='0'>0</option>

    <?php 
    for ($i=1;$i<61;$i++) {
      $s="";
      if ($ports=="$i") $s=" SELECTED ";
      echo "  <option $s value='$i'>$i</option>\n";
    }
    ?>
      </select>
      </td>
      </tr>
      </table>

    </td>

    </tr>

  <?php 
  //Associated files
  //
    $f=itemid2files($id,$dbh);
    $flnk=showfiles($f);

    $f2=itemid2invoicefiles($id,$dbh);
    $flnk.=showfiles($f2,'fileslist2',0,'File of related invoice');

    $f3=itemid2contractfiles($id,$dbh);
    $flnk.=showfiles($f3,'fileslist3',0,'File of related contract');


  ?>

    <tr>
    <td class='tdtop' colspan=1>
      <!-- related item & software links -->
      <h3><?php te("Associations Overview");?></h3>
      <div style='text-align:center'>
	<span class="tita" onclick='showid("items");'><?php te("Items");?></span> |
	<span class="tita" onclick='showid("software");'><?php te("Software");?></span> |
	<span class="tita" onclick='showid("invoices1");'><?php te("Invoices");?></span> |
	<span class="tita" onclick='showid("contracts");'><?php te("Contracts");?></span>
      </div>

      <div class="scrltblcontainer4" style='height:13em'>

      <div  id='items' class='relatedlist'><?php te("ITEMS");?></div>
      <?php 
      if (is_numeric($id)) {
	$sql="SELECT items.id, agents.title || ' ' || items.model || ' [' || itemtypes.description || ', ID:' || items.id || ']' as txt ".
	     "FROM agents,items,itemtypes WHERE ".
	     " agents.id=items.manufacturer_id AND items.item_type_id=itemtypes.id AND ".
	     " items.id IN ".
	     "  (SELECT itemid1 FROM itemlink WHERE itemid2=$id UNION SELECT itemid2 FROM itemlink WHERE itemid1=$id)";
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

      <div  id='software' class='relatedlist'><?php te("SOFTWARE");?></div>
      <?php 
      if (is_numeric($id)) {
	//print a table row

	$sql="SELECT software.id, agents.title || ' ' || software.title ||' '|| software.version || ' [ID:' || software.id || ']' as txt ".
	     "FROM agents,software,item2soft WHERE ".
	     " agents.id=software.manufacturer_id AND item2soft.software_id=software.id AND item2soft.item_id='$id'";
	$sthi=db_execute($dbh,$sql);
	$ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
	$nitems=count($ri);
	$institems="";
	for ($i=0;$i<$nitems;$i++) {
	  $x=($i+1).": ".$ri[$i]['txt'];
	  if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
	  $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
		      "<a href='$scriptname?action=editsoftware&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
	}
	echo $institems;
      }
      ?>
     <div id='invoices1' class='relatedlist'><?php te("INVOICES");?></div>
      <?php 
      if (is_numeric($id)) {
	//print a table row
	$sql="SELECT invoices.id, invoices.number, invoices.date FROM invoices,item2inv ".
	     " WHERE item2inv.invoice_id=invoices.id AND item2inv.item_id=$id";
	$sthi=db_execute($dbh,$sql);
	$ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
	$nitems=count($ri);
	$institems="";
	for ($i=0;$i<$nitems;$i++) {
	  $d=strlen($ri[$i]['date'])?date($dateparam,$ri[$i]['date']):"";
	  $x=($i+1).":  ({$ri[$i]['number']}) - $d [ID:{$ri[$i]['id']}]";
	  if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
	  $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
		      "<a href='$scriptname?action=editinvoice&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
	}
	echo $institems;
      }
      ?>
     <div id='contracts' class='relatedlist'><?php te("CONTRACTS");?></div>
      <?php 
      if (is_numeric($id)) {
	//print a table row
	$sql="SELECT contracts.id, type,title,number,start_date,end_date FROM contracts,contract2item ".
	     " WHERE contract2item.contract_id=contracts.id AND contract2item.item_id=$id";
	$sthi=db_execute($dbh,$sql);
	$ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
	$nitems=count($ri);
	$institems="";
	for ($i=0;$i<$nitems;$i++) {
	  $d=date($dateparam,$ri[$i]['start_date'])."-".date($dateparam,$ri[$i]['end_date']);
	  $x=($i+1).":  (".$ri[$i]['title']." ".$ri[$i]['number'].") - $d [ID:{$ri[$i]['id']}]";
	  if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
	  $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
		      "<a href='$scriptname?action=editcontract&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
	}
	echo $institems;
      }
      ?>

      </div><!-- scrltblcontainer -->

    </td>


    <!-- tags -->
    <td class='tdtop' colspan=1>
      <h3>Tags <span title='Changes are saved immediately.<br>Removing tags removes associations not Tags. Use the "Tags" menu for that.' style='font-weight:normal;font-size:70%'>(<a class="edit-tags" href="">edit tags</a>)</span></h3>
      
      <?php 
      echo showtags("item",$id);
      ?>
      <script>
	ajaxtagscript="php/tag2item_ajaxedit.php?id=<?php echo $id?>";
	<?php 
	require_once('js/jquery.tag.front.js');
	?>
      </script>
	      <br>
	      <div style='clear:both;height:20px;'></div>
	      <div style='font-style:italic' id='result'></div>
    </td>

    <td class='tdtop' colspan=2>
      <table cellspacing='0' cellpadding='0' width='100%'>
      <tr><td><h3><?php te("Associated Files");?><img onclick='window.location.href=window.location.href;' 
						  src='images/refresh.png'></h3></td></tr>
      <tr>
      <td>&nbsp; <!-- file links -->
	  <?php echo $flnk?>
      </td>
      </tr>
      </table>

    </td>
  </tr>
  </table>

    <?php
     
    if ($id!="new") {

      //check for different status of linked items
      $sql="SELECT items.id,items.status,statustypes.statusdesc FROM ".
	   " items,statustypes where (items.id =$id or items.id in ".
	   " (select itemid1 from itemlink where itemid2=$id union select itemid2 from itemlink where itemid1=$id)) ".
	   " AND items.status=statustypes.id";
      $sth=db_execute($dbh,$sql);
      while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
	if ($status!=$r['status'])
	  $warr.= t("<li><b>status</b> of this item  different from status of associated item").
	  " <a href='$scriptname?action=edititem&amp;id={$r['id']}'>{$r['id']}</a> ".
	  "({$r['statusdesc']})</li>";
      }

      //check for different location of linked items
      $sql="SELECT items.id,items.location_id,locations.name FROM ".
           "items,locations where (items.id =$id or items.id IN ".
	   "(select itemid1 from itemlink where itemid2=$id union select itemid2 from itemlink where itemid1=$id)) ".
	   "AND items.location_id=locations.id";
      $sth=db_execute($dbh,$sql);
      while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
	if ($location_id!=$r['location_id'])
	  $warr.= t("<li><b>location</b> of this item  different from location of associated item").
	  " <a href='$scriptname?action=edititem&amp;id={$r['id']}'>{$r['id']}</a> ".
	  "({$r['name']})</li>";
      }

      //check for different user of linked items
      $sql="SELECT items.id,items.user_id,users.username FROM ".
           "items,users where (items.id =$id or items.id IN ".
	   "(select itemid1 from itemlink where itemid2=$id union select itemid2 from itemlink where itemid1=$id)) ".
	   "AND items.user_id=users.id";
      $sth=db_execute($dbh,$sql);
      while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
	if ($user_id!=$r['user_id'])
	  $warr.= t("<li><b>user</b> of this item  different from user of associated item").
	  " <a href='$scriptname?action=edititem&amp;id={$r['id']}'>{$r['id']}</a> ".
	  "({$r['username']})</li>";
      }


      if (strlen($warr)) {
	echo "<div class='ui-state-highlight ui-corner-all' style='text-align:left;'>
	       <p>
	       <span style='float: left; margin-right: .3em;margin-top:2px;' class='ui-icon ui-icon-notice'></span>
	       </p>
	       <h4>Warning:</h4>
	       <ol>
	       $warr
	       </ol>
	      </div>
	";
      }
    }
    ?>
       
   
</div> <!--tab1-->



<div id='tab2' class='tab_content'><!-- Associations -->

  <table border='0' class='tbl2' style='width:100%;border-bottom:1px solid #cecece'><!-- connect to other items -->
    <tr><td colspan=2 title='Add hierarchical or sibling relations to: parts, cards, monitors, et.c.'><h2>Connected To<sup>1</sup>
    <input style='color:#909090' id="itemsfilter" name="itemsfilter" value='Filter' onclick='this.style.color="#000"; this.value=""' size="20">
      <span style='font-weight:normal;' class='nres'></span>
    </h2></td></tr>
    <tr><td colspan=2>
      <div class='scrltblcontainer'>
      <table width='100%' class='brdr' id='itemslisttbl'>
      <thead><tr><th><?php te("Rel");?></th><th><?php te("ID");?></th><th><?php te("Type");?></th><th><?php te("Manufacturer");?></th>
                 <th><?php te("Model");?></th><th><?php te("Label");?></th><th><?php te("DNS");?></th>
                 <th><?php te("Users");?></th><th><?php te("S/N");?></th></tr>
	  </thead>
      <tbody>
<?php 
//////////////////////////////////////////////
//connect to other Items
//insert into tt ids that link to $id. It will remain empty if $id not defined
$dbh->exec("CREATE TEMP TABLE if not exists tt (ids integer)");
$error = $dbh->errorInfo();if($error[0] && isset($error[2])) echo "Error ei0:".$error[2]."<br>";
$dbh->exec("DELETE from tt");
$error = $dbh->errorInfo();if($error[0] && isset($error[2])) echo "Error ei0:".$error[2]."<br>";

if (isset($id) && is_numeric($id) && strlen($id)) { //item links
  //fill in tt with linked items
  $sql="INSERT INTO tt SELECT id from items WHERE id IN ".
        "(SELECT itemid1 FROM itemlink WHERE itemid2=$id UNION ".
        "SELECT itemid2 FROM itemlink WHERE itemid1=$id)";
  db_exec($dbh,$sql);
} 

//linked items
$sql="SELECT items.id,manufacturer_id,model,item_type_id,sn || ' '||sn2 ||' ' || sn3 as sn,label,dns_name,users.username AS username ".
     " FROM items,users WHERE user_id=users.id AND items.id in (SELECT * from tt) ".
     " order by item_type_id,items.id DESC, manufacturer_id,model, dns_name";
$sth=db_execute($dbh,$sql);


while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  if (isset($id) && ($r['id']==$id)) continue; //dont link recursively 
  echo "\n <tr><td><input name='itlnk[]' value='".$r['id'].
   "' checked type='checkbox' /></td>".
   "<td class='bld'><a title='Edit item {$r['id']} in new window' ".
   "target=_blank href='$scriptname?action=edititem&amp;id=".$r['id']."'><div class='editid'>".
   $r['id']."</div></a></b></td>".
   "<td class='bld'>".$typeid2name[$r['item_type_id']]."</td>".
   "<td class='bld'>".$agents[$r['manufacturer_id']]['title']."&nbsp;</td>".
   "<td class='bld'>".$r['model']."&nbsp;</td>".
   "<td class='bld'>".$r['label']."&nbsp;</td>".
   "<td class='bld'>".$r['dns_name']."&nbsp;</td>".
   "<td class='bld'>".$r['username']."&nbsp;</td>".
   "<td class='bld'>".$r['sn']."&nbsp;</td></tr>\n";
}

//not linked items
$sql="SELECT items.id,manufacturer_id,model,item_type_id, serial_number || ' '||sn2 ||' ' || sn3 as sn,label,function,dns_name, users.username AS username".
     " FROM items,users WHERE user_id=users.id AND items.id not in (SELECT * FROM tt) ".
     " order by item_type_id,items.id DESC, manufacturer_id, model, dns_name";

$sth=db_execute($dbh,$sql);

while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  if (isset($id) && ($r['id']==$id)) continue; //dont link recursively 
  echo "\n  <tr><td><input name='itlnk[]' value='".$r['id'].
   "' type='checkbox' /></td>".
   "<td ><a title='Edit item {$r['id']} in new window' ".
   "target=_blank href='$scriptname?action=edititem&amp;id=".$r['id']."' class='editid'>".
   $r['id']."</a></td>".
   "<td>".$typeid2name[$r['item_type_id']]."</td>".
   "<td>".$agents[$r['manufacturer_id']]['title']."</td>".
   "<td >".$r['model']."&nbsp;</td>".
   "<td >".$r['label']."&nbsp;</td>".
   "<td >".$r['dns_name']."&nbsp;</td>".
   "<td >".$r['username']."&nbsp;</td>".
   "<td >".$r['sn']."&nbsp;</td></tr>\n";
}
?>

    </tbody>
    </table>
    </div>
    </td>
    </tr>
  </table><!-- /connect to other items -->

  <?php te("Add hierarchical or sibling relations to: parts, cards, monitors, et.c.");?>
</div> <!-- /tab2 -->


<div id='tab3' class='tab_content'><!-- Invoices -->
  <!-- invoice links -->
  <table border='0' class=tbl2 style='border-bottom:1px solid #cecece;width:100%;'>
    <tr>
    <td colspan=2 style='text-align:center;' title='Select invoice(s) related to this item'>
      <h2>
      <input style='color:#909090' id="invoicefilter" name="invoicefilter" value='Filter' onclick='this.style.color="#000"; this.value=""' size="20">
      <span style='font-weight:normal;' class='nres'></span>
      </h2>
    </td>
    </tr>
    <tr><td colspan=2>
    <div class='scrltblcontainer'>
    <table  id='invoicelisttbl' width='100%' class='brdr'>
      <thead><tr><th><?php te("Rel");?></th><th><?php te("ID");?></th><th><?php te("Vendor");?></th><th><?php te("Date");?></th>
                  <th><?php te("Order No");?></th><th><?php te("Invoice Description / File(s)");?></th></tr></thead>
      <tbody>

<?php 
//////////////////////////////////////////////
//connect to invoices II
//insert into tt ids that link to $id. It will remain empty if $id not defined

if (isset($id) && is_numeric($id) && strlen($id)) { //item links

$sql="SELECT i.id,i.vendor_id, i.invoice_date,i.number,i.description,   group_concat(fname,'|') AS filename_stored  ".
   " FROM invoices i LEFT OUTER JOIN ".
   "  (SELECT invoice_id,fname FROM invoices_files JOIN files ON  invoice2file.file_id=files.id) iidwithfiles ON iidwithfiles.invoice_id=i.id ".
   " WHERE  i.id in (SELECT invoice_id from item2inv where item_id=$id) ".
   " GROUP BY i.id ".
   " order by i.id desc, date desc, vendor_id, number ";

  $sth=db_execute($dbh,$sql);

  while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
    $d=strlen($r['date'])?date($dateparam,$r['date']):"";
    echo "\n <tr><td class='bld'><input name='invlnk[]' value='".$r['id'].
     "' checked type='checkbox' /></td>".  
     "<td class='bld'><a href='$scriptname?action=editinvoice&amp;id={$r['id']}' class='editid'>".$r['id']."</a></td>".  
     "<td class='bld'>".$agents[$r['vendor_id']]['title']."&nbsp;</td>".
     "<td class='bld'>$d&nbsp;</td>".
     "<td class='bld'>".$r['number'].  "&nbsp;</td>".
     "<td class='bld'>";
     if ($r['description'] != '')  echo $r['description']. "<BR>";
     $fx=explode("|",$r['fname']);
     foreach ($fx as $f){
       echo "<a target=invphoto href='$uploaddirwww/$f'>$f</a> ";
     }
     echo "</td></tr>\n";
  }

   $where=" WHERE i.id not in (SELECT invoice_id from item2inv where item_id=$id) ";
} 
else
  $where=" WHERE 1=1 ";

//not linked items

$sql="SELECT i.id,i.vendor_id, i.invoice_date,i.number,i.description,   group_concat(fname,'|') AS filename_stored  ".
   " FROM invoices i LEFT OUTER JOIN ".
   "  (SELECT invoice_id,fname FROM invoices_files JOIN files ON  invoice2file.file_id=files.id) iidwithfiles ON iidwithfiles.invoice_id=i.id ".
   " $where ".
   " GROUP BY i.id ".
   " order by i.id desc, date desc, vendor_id, number ";


$sth=db_execute($dbh,$sql);

while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  $d=strlen($r['date'])?date($dateparam,$r['date']):"";
  echo "\n <tr><td><input name='invlnk[]' value='".$r['id'].
   "' type='checkbox' /></td>".  
   "<td><a class='editid' href='$scriptname?action=editinvoice&amp;id={$r['id']}'>".$r['id']."</a></td>".  
   "<td>".$agents[$r['vendor_id']]['title'].
   "&nbsp;</td><td>$d".
   "&nbsp;</td><td>".$r['number'].  "&nbsp;</td>".
   "<td>";
   if ($r['description'] != '')  echo $r['description']. "<BR>";
     $fx=explode("|",$r['fname']);
     foreach ($fx as $f){
       echo "<a target=invphoto href='$uploaddirwww/$f'>$f</a> ";
     }
     echo "</td></tr>\n";

}
?>
    </tbody>
    </table>
    </div>
   </td></tr>
  </table>
  <!-- /invoice links-->


</div> <!-- /tab3 -->


<div id='tab4' class='tab_content'><!-- Item Log -->
<?php 
if (isset($id) && strlen($id)) { //show actions
  echo "<iframe class='iframe1' width='100%' height='500' ".
       " src='php/editactions.php?item_id=$id'></iframe>";
}
else {
  echo "<br><p><b>Item Log can be edited after inserting item</b>";
}
?>
</div><!-- tab4 -->

<div id='tab5' class='tab_content'>
  <h2><input style='color:#909090' id="softfilter" name="softfilter" class='filter' 
             value='Filter' onclick='this.style.color="#000"; this.value=""' size="20">
      <span style='font-weight:normal;' class='nres'></span>
  </h2>
  <?php 
  //////////////////////////////////////////////
  //connect to Items
  $sql=" SELECT COALESCE((SELECT software_id from item2soft WHERE item_id='$id' AND software_id=software.id ),0) islinked , ".
       " software.id, title || ' ' || version as titver, agents.title AS agtitle  ".
       " FROM software,agents WHERE agents.id=software.manufacturer_id ".
       " ORDER BY islinked desc,manufacturer_id,stitle,sversion ";
  $sth=db_execute($dbh,$sql);
  ?>
  <div style='margin-left:auto;margin-right:auto;' class='scrltblcontainer2'>
     <table width='100%' class='tbl2 brdr '  id='softwarelisttbl'>
       <thead>
          <tr><th width='5%'><?php te("Associated");?></th><th><?php te("ID");?></th><th><?php te("Manufacturer");?></th><th><?php te("Title/Ver.");?></th>
          </tr>
        </thead>
        <tbody>
  <?php 
$xx=0;
  while ($ir=$sth->fetch(PDO::FETCH_ASSOC)) {
    if ($ir['islinked']) {
      $cls="class='bld'";
    }
    else
      $cls="";

    $xx++;
    echo "<tr><td><input name='softlnk[]' value='".$ir['id']."' ";
    if ($ir['islinked']) echo " checked ";
    echo  " type='checkbox' /></td>".
     "<td $cls><a class='editid' title='Edit software {$ir['id']} in a new window' ".
     "target=_blank href='$scriptname?action=editsoftware&amp;id=".$ir['id']."'>";
    echo $ir['id'];
    echo "</a></td>".
     "<td $cls>".$ir['agtitle'].  "&nbsp;</td>".
     "<td $cls>".$ir['titver']."&nbsp;</td></tr>\n";
  }
  ?>
  </tbody>
  </table>
  <?php echo "$xx"?> Software listed
  </div>


</div><!-- tab5 -->


<div id='tab6' class='tab_content'>

  <h2><input style='color:#909090' id="contrfilter" name="contrfilter" class='filter' 
             value='Filter' onclick='this.style.color="#000"; this.value=""' size="20">
      <span style='font-weight:normal;' class='nres'></span>
  </h2>
  <?php 
  //////////////////////////////////////////////
  //connect to Items
  $sql=" SELECT COALESCE((SELECT contract_id FROM contracts_items WHERE item_id='$id' AND contract_id=contracts.id ),0) islinked , ".
       " contracts.id, contracts.title AS ctitle, agents.title AS agtitle  ".
       " FROM contracts,agents WHERE agents.id=contracts.contractor_id ".
       " ORDER BY islinked desc,contractor_id,ctitle";
  $sth=db_execute($dbh,$sql);
  ?>
  <div style='margin-left:auto;margin-right:auto;' class='scrltblcontainer2'>
     <table width='100%' class='tbl2 brdr '  id='contrlisttbl'>
       <thead>
          <tr><th width='5%'><?php te("Associated");?></th><th><?php te("ID");?></th><th><?php te("Contractor");?></th><th><?php te("Title");?></th>
          </tr>
        </thead>
        <tbody>
  <?php 

  while ($ir=$sth->fetch(PDO::FETCH_ASSOC)) {
    if ($ir['islinked']) {
      $cls="class='bld'";
    }
    else
      $cls="";

    echo "<tr><td><input name='contrlnk[]' value='".$ir['id']."' ";
    if ($ir['islinked']) echo " checked ";
    echo  " type='checkbox' /></td>".
     "<td $cls><a class='editid' title='Edit Contract {$ir['id']} in a new window' ".
     "target=_blank href='$scriptname?action=editcontract&amp;id=".$ir['id']."'>";
    echo $ir['id'];
    echo "</a></td>".
     "<td $cls>".$ir['agtitle'].  "&nbsp;</td>".
     "<td $cls>".$ir['ctitle']."&nbsp;</td></tr>\n";
  }
  ?>
  </tbody>
  </table>
  </div>

</div><!-- tab6 -->


<div id='tab7' class='tab_content'>
    <table class="tbl2" width='100%'>
    <tr><td colspan=2><h2>Upload a File</h2></td></tr>
    <!-- file upload -->
    <tr><td class="tdc">
    <iframe class="upload_frame" name="upload_frame" 
          src="php/uploadframe.php?id=<?php echo $id?>&amp;assoctable=item2file&amp;colname=itemid"  
          frameborder="0" allowtransparency="true"></iframe>
    </td>
    </tr>
    </table>
</div><!-- tab7 -->



</div><!-- tab container -->


<table><!-- save buttons -->
<tr>
<td style='text-align: center' colspan=1><button type="submit"><img src="images/save.png" alt="Save" > <?php te("Save");?></button></td>
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


