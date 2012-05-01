<SCRIPT LANGUAGE="JavaScript"> 
$(function () {
 //$('input#contlistfilter').quicksearch('table#contlisttbl tbody tr');
  $('table#contlisttbl').dataTable({
                "sPaginationType": "full_numbers",
                "bJQueryUI": true,
                "iDisplayLength": 25,
                "aLengthMenu": [[10,25, 50, 100, -1], [10,25, 50, 100, "All"]],
                "bLengthChange": true,
                "bFilter": true,
                "bSort": true,
                "bInfo": true,
                "bAutoWidth": true, 
                "sDom": '<"H"Tlpf>rt<"F"ip>',
                "oTableTools": {
                        "sSwfPath": "swf/copy_cvs_xls_pdf.swf"
                },
                "aoColumns": [ 
                        null,
                        null,
                        null,
                        null,
                        null,
                        { "sType": "title-numeric" },
                        { "sType": "title-numeric" },
                ]

  });


//for date sort
  jQuery.fn.dataTableExt.oSort['title-numeric-asc']  = function(a,b) {
          var x = a.match(/title="*(-?[0-9]+)/)[1];
          var y = b.match(/title="*(-?[0-9]+)/)[1];
          x = parseFloat( x );
          y = parseFloat( y );
          return ((x < y) ? -1 : ((x > y) ?  1 : 0));
  };

  jQuery.fn.dataTableExt.oSort['title-numeric-desc'] = function(a,b) {
          var x = a.match(/title="*(-?[0-9]+)/)[1];
          var y = b.match(/title="*(-?[0-9]+)/)[1];
          x = parseFloat( x );
          y = parseFloat( y );
          return ((x < y) ?  1 : ((x > y) ? -1 : 0));
  };

});

</SCRIPT>
<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2010 , sivann _at_ gmail.com */

$sql="SELECT contracts.id,title,parentid,number,name,startdate,currentenddate FROM contracts,contracttypes WHERE contracts.type=contracttypes.id ORDER by contracts.id desc,parentid desc";
$sth=db_execute($dbh,$sql);

?>

<h1><?php te("Contracts");?> <a title='<?php te("Add new Contract");?>' href='<?php echo $scriptname?>?action=editcontract&amp;id=new'><img border=0 src='images/add.png' ></a>
</h1>
<table class='display' width='100%' id='contlisttbl'>
<thead>

<tr>
  <th width='5%'><?php te("Edit ID");?></th>
  <th width='5%'><?php te("Parent ID");?></th>
  <th><?php te("Type");?></th>
  <th><?php te("Number");?></th>
  <th width='40%'><?php te("Title");?></th>
  <th><?php te("Start Date");?></th>
  <th><?php te("End Date");?></th>
</tr>
</thead>
<tbody>
<?php 

$i=0;
/// print actions list
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  $i++;
  echo "\n<tr id='trid{$r['id']}'>";
  echo "<td><a class='editid' href='$scriptname?action=editcontract&amp;id=".$r['id']."'>{$r['id']}</a></td>\n";
  echo "<td>{$r['parentid']}</td>\n";
  echo "<td>{$r['name']}</td>\n";
  echo "<td>{$r['number']}</td>\n";
  echo "<td>{$r['title']}</td>\n";
  echo "<td><span title='{$r['startdate']}'></span>".date($dateparam,$r['startdate'])."</td>\n";
  echo "<td><span title='{$r['currentenddate']}'></span>".date($dateparam,$r['currentenddate'])."</td>\n";
  echo "</tr>\n";
}
?>

</tbody>
</table>

</form>
</body>
</html>
