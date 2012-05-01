<SCRIPT LANGUAGE="JavaScript"> 
$(function () {
 //$('input#locationslistfilter').quicksearch('table#locationslisttbl tbody tr');
  $('table#locationslisttbl').dataTable({
                "sPaginationType": "full_numbers",
                "bJQueryUI": true,
                "iDisplayLength": 25,
                "aLengthMenu": [[10,25, 50, 100, -1], [10,25, 50, 100, "All"]],
                "bLengthChange": true,
                "bFilter": true,
                "bSort": true,
                "bInfo": true,
                //"bAutoWidth": true, 
                "sDom": '<"H"Tlpf>rt<"F"ip>',
                "oTableTools": {
                        "sSwfPath": "swf/copy_cvs_xls_pdf.swf"
                }

  });
});

</SCRIPT>
<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2010 , sivann _at_ gmail.com */

//$sql="SELECT locations.*,locareas.areaname FROM locations LEFT OUTER JOIN locareas ON locareas.locationid=locations.id";
$sql="SELECT locations.*,group_concat(locareas.areaname,', ') AS areaname FROM locations ".
     " LEFT OUTER JOIN locareas ON locareas.locationid=locations.id GROUP BY locations.id ";
$sth=db_execute($dbh,$sql);
?>

<h1><?php te("Locations");?> <a title='<?php te("Add new Location");?>' href='<?php echo $scriptname?>?action=editlocation&amp;id=new'><img border=0 src='images/add.png' ></a>
</h1>



<div class='scrtblcontainerlist'>
<table class='display' id='locationslisttbl'>
<thead>

<tr>
  <th width='2%'><?php te("Edit");?></th>
  <th width='20%' nowrap><?php te("Location Name/Building Name");?></th>
  <th width='10%'><?php te("Floor");?></th>
  <th width='40%'><?php te("Area Names/Offices");?></th>
  <th><?php te("Floor Plan");?></th>
</tr>
</thead>
<tbody>
<?php 

$i=0;
/// print actions list
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  $i++;
  echo "\n<tr>";
  echo "<td><a class='editid' href='$scriptname?action=editlocation&amp;id=".$r['id']."'>{$r['id']}</a></td>\n";
  echo "<td>{$r['name']}</td>\n";
  echo "<td>{$r['floor']}</td>\n";
  echo "<td>{$r['areaname']}</td>\n";
  echo "<td>{$r['floorplanfn']}</td>\n";
  echo "</tr>\n";
}
?>

</tbody>
</table>
</div>

</form>
</body>
</html>
