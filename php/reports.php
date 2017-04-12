<script>
$(document).ready(function() {
  $('input#repfilter').quicksearch('table#reptbl tbody tr');
});
</script>

<?php
    if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */


    if (isset($sqlsrch) && !empty($sqlsrch)){
        $where = "where sql like '%$sqlsrch%'";
    }
    else {
        $sqlsrch="";
        $where="";
    }

/* List of reports on the reports page. Clicking the link will run the respective SQL query. */
$reports=array
(
    'contracttotals' => t('List Contract Purchase Totals'),
    'contractinvoices' => t('List Invoice POs by Contract'),
    'inventoryitems' => t('List of Items in Inventory'),
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


<!-- Report SQL Queries--> 
<?php 
switch ($query) {

  case "contracttotals":
    $sql="SELECT". 
        " c.number as 'Contract Number', c.title as 'Contract Title',".
	" SUM(invoices.inv_total) as 'Running Total'". 
	" FROM invoices".
        " JOIN contract2inv c2i ON invoices.id=c2i.invid". 
        " JOIN contracts c ON c2i.contractid=c.id".
        " WHERE invoices.vendorid='22' OR invoices.vendorid='24'".
	" GROUP by c.title".
	" ORDER by Date asc;";
    $editlnk="$scriptname?action=editcontract&id";
    $graph['type']="pie";
    $graph['colx']="Contract Title";
    $graph['coly']="Running Total";
    $graph['limit']=15;
  break;

  case "contractinvoices":
    $sql="SELECT invoices.id as ID, invoices.number as 'PO Number', invoices.description as Description, invoices.inv_total as Total, strftime('%m-%d-%Y', invoices.date, 'unixepoch') as Date,".
        " c.number as 'Contract Number', c.title as 'Contract Title', NULL as 'Contract Total'".
        " FROM invoices".
        " JOIN contract2inv c2i ON invoices.id=c2i.invid". 
        " JOIN contracts c ON c2i.contractid=c.id".
        " WHERE invoices.vendorid='22' OR invoices.vendorid='24'".
        " UNION".
        " SELECT NULL, NULL, NULL, NULL, NULL, c.number as 'Contract Number', c.title as 'Contract Title',".
        " SUM(invoices.inv_total) as 'Running Total'".
        " FROM invoices".
        " JOIN contract2inv c2i ON invoices.id=c2i.invid".
        " JOIN contracts c ON c2i.contractid=c.id".
        " WHERE invoices.vendorid='22' OR invoices.vendorid='24'".
        " GROUP by c.title".
        " ORDER by c.number, Date asc;";
    $editlnk="$scriptname?action=editinvoice&id";
    $graph['type']="pie";
    $graph['colx']="Contract Title";
    $graph['coly']="Total";
    $graph['limit']=15;
  break;

  case "inventoryitems":
    $sql="SELECT i.id as ID, it.typedesc as 'Item Type', model as Model, sn as 'Serial Number', purchprice as 'Purchase Price',".
	" l.name as 'Location Name',  la.areaname as 'Room'".
    " FROM items i".
    " JOIN itemtypes it ON i.itemtypeid=it.id".
    " JOIN locations l ON i.locationid=l.id".
    " JOIN locareas la ON i.locareaid=la.id".
    " WHERE status=5".
    " ORDER by status";
    $editlnk="$scriptname?action=edititem&id";
  break;
  
  case "depreciation5":
    $sql="select items.id as ID,typedesc as type, agents.title as manufacturer ,model, strftime('%Y-%m-%d', purchasedate,'unixepoch') AS PurchaseDate, ".
	     "purchprice as PurchasePrice, ".
		 " cast( ((strftime('%s','now') - purchasedate)/(60*60*24*30.4)*(purchasedate AND 1)) AS INTEGER)  as Months , ".
		 " (purchprice-purchprice/60*cast( ((strftime('%s','now') - purchasedate)/(60*60*24*30.4)*(purchasedate AND 1)) AS INTEGER))  as CurrentValue  ".
         " FROM items,itemtypes,agents ".
         " WHERE agents.id=manufacturerid AND itemtypes.id=items.itemtypeid ";
    $editlnk="$scriptname?action=edititem&id";
  break;


  case "depreciation3":
    $sql="select items.id as ID,typedesc as type, agents.title as manufacturer ,model, strftime('%Y-%m-%d', purchasedate,'unixepoch') AS PurchaseDate, ".
	     "purchprice as PurchasePrice, ".
		 " cast( ((strftime('%s','now') - purchasedate)/(60*60*24*30.4)*(purchasedate AND 1)) AS INTEGER)  as Months , ".
		 " (purchprice-purchprice/36*cast( ((strftime('%s','now') - purchasedate)/(60*60*24*30.4)*(purchasedate AND 1)) AS INTEGER))  as CurrentValue  ".
         " FROM items,itemtypes,agents ".
         " WHERE agents.id=manufacturerid AND itemtypes.id=items.itemtypeid ";
    $editlnk="$scriptname?action=edititem&id";
  break;


  case "noinvoice":
    $sql="select items.id as ID,typedesc as type, agents.title as manufacturer ,model, strftime('%Y-%m-%d', purchasedate,'unixepoch') AS PurchaseDate".
         " FROM items,itemtypes,agents ".
         " WHERE agents.id=manufacturerid AND itemtypes.id=items.itemtypeid AND items.ID not in (select itemid from item2inv)";
    $editlnk="$scriptname?action=edititem&id";
  break;

  case "nolocation":
    $sql="select items.id as ID,typedesc as type, agents.title as manufacturer ,model ".
         " FROM items,itemtypes,agents ".
         " WHERE agents.id=manufacturerid AND itemtypes.id=items.itemtypeid AND (locationid='' OR locationid is null)";
    $editlnk="$scriptname?action=edititem&id";
  break;



  case "allips":
    $sql="select items.id as ID,ipv4,ipv6, typedesc as type, agents.title as manufacturer, model, dnsname, label  ".
         " FROM items,itemtypes,agents ".
         " WHERE  agents.id=manufacturerid AND itemtypes.id=items.itemtypeid AND ipv4 <> '' order by ipv4";
    $editlnk="$scriptname?action=edititem&id";
  break;

  case "itemsperlocation":
    $sql="select count(*) as totalcount, ".
         " locations.name || ' Floor:' || locations.floor  as Location  ".
         " FROM items,agents,locations ".
         " WHERE agents.id=items.manufacturerid AND items.locationid=locations.id GROUP BY locationid order by totalcount desc;";
    $editlnk="$scriptname?action=editlocations";
    $graph['type']="pie";
    $graph['colx']="Location";
    $graph['coly']="totalcount";
    $graph['limit']=15;
  break;

  case "itemlistperlocation":
    $sql="select items.id as ID, typedesc as 'Product Type', agents.title as Manufacturer, model as Model, sn as 'Serial Number', sn3 as 'Service Tag#',".
	" strftime('%m-%d-%Y', purchasedate, 'unixepoch') as 'Purchase Date', replace(purchprice,'$','') as 'Purchase Price'  ".
        " FROM items,agents,itemtypes".
        " WHERE itemtypes.id=items.itemtypeid".
	" AND agents.id=items.manufacturerid AND date(purchasedate)>= date('2015-01-01') ".
        " ORDER by purchasedate, purchprice desc, typedesc desc;";
    $editlnk="$scriptname?action=editlocations";
    $graph['type']="pie";
    $graph['colx']="Location";
    $graph['coly']="totalcount";
    $graph['limit']=15;
  break;


  case "itemperagent":
    $sql="select count(*) as totalcount,agents.title as Agent, agents.id as ID from items,agents ".
         "WHERE agents.id=items.manufacturerid group by manufacturerid order by totalcount desc;";
    $editlnk="$scriptname?action=editagent&id";
    $graph['type']="pie";
    $graph['colx']="Agent";
    $graph['coly']="totalcount";
    $graph['limit']=15;
  break;

  case "softwareperagent":
    $sql="select count(*) as totalcount,agents.title as Agent, agents.id as ID from software,agents".	" WHERE agents.id=software.manufacturerid group by manufacturerid order by totalcount desc;";
    $editlnk="$scriptname?action=editagent&id";
    $graph['type']="pie";
    $graph['colx']="Agent";
    $graph['coly']="totalcount";
    $graph['limit']=15;
  break;
  
  case "invoicesperagent":
    $sql="select count(*) as totalcount,agents.title as Agent, agents.id as ID from invoices,agents ".
         "WHERE agents.id=invoices.vendorid group by vendorid order by totalcount desc;";
    $editlnk="$scriptname?action=editagent&id";
    $graph['type']="pie";
    $graph['colx']="Agent";
    $graph['coly']="totalcount";
    $graph['limit']=15;
  break;

  case "itemsendwarranty":
    $t=time();
    $sql="select items.id as ID,ipv4, typedesc as type, agents.title as manufacturer, model, dnsname, label,  ".
         " (purchasedate+warrantymonths*30*24*60*60-$t)/(60*60*24) RemainingDays FROM items,itemtypes,agents ".
         " WHERE  agents.id=manufacturerid AND itemtypes.id=items.itemtypeid  AND RemainingDays>-360 AND RemainingDays<360 order by RemainingDays ";
    $editlnk="$scriptname?action=edititem&id";
  break;

  case "percsupitems":
    $sql="select 
    'NotExpired' as Type, (select count(id) from items where ((purchasedate+warrantymonths*30*24*60*60-strftime(\"%s\"))/(60*60*24)) >1 AND purchasedate>0 AND warrantymonths>0) as Items
    UNION SELECT
    'Expired' as Type, (select count(id) from items where ((purchasedate+warrantymonths*30*24*60*60-strftime(\"%s\"))/(60*60*24)) <=1 AND purchasedate>0 AND warrantymonths>0) as Items
    UNION SELECT
    'Undefined' as Type, (select count(id) from items where purchasedate=0 OR purchasedate is null OR warrantymonths=0 OR warrantymonths is null) as Items
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

