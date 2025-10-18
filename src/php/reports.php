<script>
$(document).ready(function() {
  $('input#repfilter').quicksearch('table#reptbl tbody tr');
});
</script>

<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */


if (isset($sqlsrch) && !empty($sqlsrch)) 
  $where = "where sql like '%$sqlsrch%'";
else { 
 $sqlsrch="";
 $where="";
}


$reports=array (
'itemperagent' => t('Number of items per Manufacturer (Agent)'),
'softwareperagent' => t('Number of installed Software per Manufacturer (Agent)'),
'invoicesperagent' => t('Number of invoices per Vendor (Agent)'),
'itemsperlocation' => t('Number of items per Location'),
'percsupitems' => t('Number of Items under support'),
'itemlistperlocation' => t('Item list per location'),
'itemsendwarranty' => t('Items with warranty end date close to (before or after) today'),
'allips' => t('List items with defined IPv4 numbers'),
'noinvoice' => t('Items without invoices'),
'nolocation' => t('Items without location'),
'depreciation3' => t('Item depreciation value 3 years'),
'depreciation5' => t('Item depreciation value 5 years'),
);


?>
<h1><?php te("Reports");?></h1>

<div style='width:100%;clear:both;'>

  <div style='float:left;text-align:left;padding:5px;height:350px;overflow-y:auto;width:300px;border:1px solid #cecece;'>
  <h2><?php te("Select Report");?></h2>
  <ul>

<?php 
  $curdesc="";
  foreach ($reports as $q => $desc) {
    if ($q==$query) {
      echo "<li><b><a href='$scriptname?action=$action&amp;query=$q'>$desc</a></b></li>";
      $curdesc=$desc;
    }
    else
      echo "<li><a href='$scriptname?action=$action&amp;query=$q'>$desc</a></li>";
  }
?>

  </ul>
  </div>
  <div id="chartdiv" style="padding:5px;float:left;height:350px;width:640px;border:1px solid #cecece; "></div>

</div>

<div style='width:100%;clear:both;'>

<?php 
switch ($query) {

  case "depreciation5":
    $sql="select items.id as ID,typedesc as type, agents.title as manufacturer ,model, strftime('%Y-%m-%d', purchase_date,'unixepoch') AS PurchaseDate, ".
	     "purchase_price as PurchasePrice, ".
		 " cast( ((strftime('%s','now') - purchase_date)/(60*60*24*30.4)*(purchase_date AND 1)) AS INTEGER)  as Months , ".
		 " (purchase_price-purchase_price/60*cast( ((strftime('%s','now') - purchase_date)/(60*60*24*30.4)*(purchase_date AND 1)) AS INTEGER))  as CurrentValue  ".
         " FROM items,itemtypes,agents ".
         " WHERE agents.id=manufacturer_id AND itemtypes.id=items.item_type_id ";
    $editlnk="$scriptname?action=edititem&id";
  break;


  case "depreciation3":
    $sql="select items.id as ID,typedesc as type, agents.title as manufacturer ,model, strftime('%Y-%m-%d', purchase_date,'unixepoch') AS PurchaseDate, ".
	     "purchase_price as PurchasePrice, ".
		 " cast( ((strftime('%s','now') - purchase_date)/(60*60*24*30.4)*(purchase_date AND 1)) AS INTEGER)  as Months , ".
		 " (purchase_price-purchase_price/36*cast( ((strftime('%s','now') - purchase_date)/(60*60*24*30.4)*(purchase_date AND 1)) AS INTEGER))  as CurrentValue  ".
         " FROM items,itemtypes,agents ".
         " WHERE agents.id=manufacturer_id AND itemtypes.id=items.item_type_id ";
    $editlnk="$scriptname?action=edititem&id";
  break;


  case "noinvoice":
    $sql="select items.id as ID,typedesc as type, agents.title as manufacturer ,model, strftime('%Y-%m-%d', purchase_date,'unixepoch') AS PurchaseDate".
         " FROM items,itemtypes,agents ".
         " WHERE agents.id=manufacturer_id AND itemtypes.id=items.item_type_id AND items.ID not in (select item_id from item2inv)";
    $editlnk="$scriptname?action=edititem&id";
  break;

  case "nolocation":
    $sql="select items.id as ID,typedesc as type, agents.title as manufacturer ,model ".
         " FROM items,itemtypes,agents ".
         " WHERE agents.id=manufacturer_id AND itemtypes.id=items.item_type_id AND (location_id='' OR location_id is null)";
    $editlnk="$scriptname?action=edititem&id";
  break;



  case "allips":
    $sql="select items.id as ID,ipv4,ipv6, description as type, agents.title as manufacturer, model, dns_name, label  ".
         " FROM items,itemtypes,agents ".
         " WHERE  agents.id=manufacturer_id AND itemtypes.id=items.item_type_id AND ipv4 <> '' order by ipv4";
    $editlnk="$scriptname?action=edititem&id";
  break;

  case "itemsperlocation":
    $sql="select count(*) as totalcount, ".
         " locations.name || ' Floor:' || locations.floor  as Location  ".
         " FROM items,agents,locations ".
         " WHERE agents.id=items.manufacturer_id AND items.location_id=locations.id GROUP BY location_id order by totalcount desc;";
    $editlnk="$scriptname?action=editlocations";
    $graph['type']="pie";
    $graph['colx']="Location";
    $graph['coly']="totalcount";
    $graph['limit']=15;
  break;

  case "itemlistperlocation":
	$sql="select items.id as ID, description as type, agents.title as manufacturer, model, dns_name, ".
	"locations.name || ' Floor:' || locations.floor || ' Area:' || (select locareas.name from locareas where locareas.id=items.location_area_id) as Location  ".
	"FROM items ".
	"INNER JOIN agents on agents.id=items.manufacturer_id ".
	"INNER JOIN locations on items.location_id=locations.id ".
	"INNER JOIN item_types on itemtypes.id=items.item_type_id ".
	"order by items.location_id,typedesc desc";
    $editlnk="$scriptname?action=edititem&id";
    $graph['type']="pie";
    $graph['colx']="Location";
    $graph['coly']="totalcount";
    $graph['limit']=15;
  break;


  case "itemperagent":
    $sql="select count(*) as totalcount,agents.title as Agent, agents.id as ID from items,agents ".
         "WHERE agents.id=items.manufacturer_id group by manufacturer_id order by totalcount desc;";
    $editlnk="$scriptname?action=editagent&id";
    $graph['type']="pie";
    $graph['colx']="Agent";
    $graph['coly']="totalcount";
    $graph['limit']=15;
  break;

  case "softwareperagent":
    $sql="select count(*) as totalcount,agents.title as Agent, agents.id as ID from software,agents ".
         "WHERE agents.id=software.manufacturer_id group by manufacturer_id order by totalcount desc;";
    $editlnk="$scriptname?action=editagent&id";
    $graph['type']="pie";
    $graph['colx']="Agent";
    $graph['coly']="totalcount";
    $graph['limit']=15;
  break;

  case "invoicesperagent":
    $sql="select count(*) as totalcount,agents.title as Agent, agents.id as ID from invoices,agents ".
         "WHERE agents.id=invoices.vendor_id group by vendor_id order by totalcount desc;";
    $editlnk="$scriptname?action=editagent&id";
    $graph['type']="pie";
    $graph['colx']="Agent";
    $graph['coly']="totalcount";
    $graph['limit']=15;
  break;

  case "itemsendwarranty":
    $t=time();
    $sql="select items.id as ID,ipv4, description as type, agents.title as manufacturer, model, dns_name, label,  ".
         " (strftime('%s',purchase_date,'unixepoch','+'||warranty_months||' months')-$t)/(60*60*24)  RemainingDays FROM items,itemtypes,agents ".
         " WHERE  agents.id=manufacturer_id AND itemtypes.id=items.item_type_id  AND RemainingDays>-360 AND RemainingDays<360 order by RemainingDays ";
    $editlnk="$scriptname?action=edititem&id";
  break;

  case "percsupitems":
    $sql="select 
    'NotExpired' as Type, (select count(id) from items where ((purchase_date+warranty_months*30*24*60*60-strftime(\"%s\"))/(60*60*24)) >1 AND purchase_date>0 AND warranty_months>0) as Items
    UNION SELECT
    'Expired' as Type, (select count(id) from items where ((purchase_date+warranty_months*30*24*60*60-strftime(\"%s\"))/(60*60*24)) <=1 AND purchase_date>0 AND warranty_months>0) as Items
    UNION SELECT
    'Undefined' as Type, (select count(id) from items where purchase_date=0 OR purchase_date is null OR warranty_months=0 OR warranty_months is null) as Items
    UNION SELECT 'Total' as Type, (select count(id) from items)  as Items
    ";
    $graph['type']="pie";
    $graph['colx']="Type";
    $graph['coly']="Items";
    $graph['limit']=15;
  break;

  default:
   exit;
}
?>


<div style='padding-top:15px;clear:both'>
<h2><?php echo $curdesc?></h2>
  <input style='color:#909090' id="repfilter" name="repfilter" class='filter' 
       value='Filter' onclick='this.style.color="#000"; this.value=""' size="20">
  <table id='reptbl' class='sortable' >

  <?php 


  /// make db query
  $sth=db_execute($dbh,$sql);

  $plot_param="";
  if (isset($graph['type']))
    $plot_param="[";

  /// display results
  $row=0;
  while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {

    echo "\n<tr>";

    if (!$row) { //header
      echo "\n\t<th>#</th>";
      foreach($r as $k => $v) {
	echo "\n\t<th>$k</th>";
      }
      echo "\n</tr>\n<tr>";
    }
    
    if (($graph['type']=='pie') && $graph['limit']-->0) {
      if (!($r[$graph['colx']]=='Total'))  { //don't include totals in pies
	$plot_param.="['".$r[$graph['colx']]."',".$r[$graph['coly']]."],";
      }
    }

    echo "\n\t<td>".($row+1)."</td>";
    foreach($r as $k => $v) {   //values
      if ($k=="ID")
	echo "\n\t<td><a class='editid' href='$editlnk=$v'>$v</a></td>";
      else {
	echo "\n\t<td>$v</td>";
      }
    } 
    echo "</tr>\n";
    $row++;

  }

  if (isset($graph['type'])) {
    $plot_param[strlen($plot_param)-1]=" "; //eat last comma
    $plot_param.="];\n";
  }

//echo "plot_param=".$plot_param;
  ?>
  </table>
</div>

</div>

<script>

<?php
if (strlen($plot_param)) {
?>
$(document).ready(function() {
  line1 = <?php  echo $plot_param; ?> ;
  $.jqplot.config.enablePlugins = true;
  $.jqplot.config.catchErrors = true;
  plot1 = $.jqplot('chartdiv', [line1], {
      //title: 'Default Pie Chart',
      seriesDefaults:{renderer:$.jqplot.PieRenderer,rendererOptions:{sliceMargin:3}},
      grid:{background:'#ffffff', borderWidth:0,shadow:false},
      legend:{show:true,rowSpacing : '0.1em'}
  });
});

<?php
}
?>
</script>

