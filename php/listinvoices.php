<script type="text/javascript">
$(function () {
 //$('input#invlistfilter').quicksearch('table#invlisttbl tbody tr');
  $('table#invlisttbl').dataTable({
                "sPaginationType": "full_numbers",
		"bJQueryUI": true,
		"iDisplayLength": 25,
                "aLengthMenu": [[10,25, 50, 100, -1], [10,25, 50, 100, "All"]],
                "bLengthChange": true,
                "bFilter": true,
                "bSort": true,
                "bInfo": true,
		//"sDom": '<"H"lfr>t<"F"ip>',
		"sDom": '<"H"Tlpf>rt<"F"ip>',
		"oTableTools": {
			"sSwfPath": "swf/copy_cvs_xls_pdf.swf"
		},
		"aoColumns": [ 
			null,
			null,
			null,
			{ "sType": "title-numeric" },
			null,
			null,
			null
		]

  });

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

</script>

<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */


$sql="SELECT id,title FROM agents";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $agents[$r['id']]=$r;

$sql="SELECT * FROM invoices ORDER BY date";
$sth=db_execute($dbh,$sql);
?>

<h1><?php te("Invoices");?> <a title='<?php te("Add new Invoice");?>' href='<?php echo $scriptname?>?action=editinvoice&amp;id=new'><img border=0 src='images/add.png' ></a> </h1>

<div class='scrtblcontainerlist'>
<table class="display" width='100%' border=0 id="invlisttbl">
<thead>
<tr><th style='width:80px' nowrap><?php te("Edit ID");?></th><th><?php te("Vendor");?></th><th><?php te("Buyer");?></th><th><?php te("Date");?></th>
     <th><?php te("Order No");?></th><th><?php te("Description");?></th><th><?php te("Associated Files");?></th></tr>
</thead>
<tbody>
<?php 

$i=0;
/// print actions list
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  $i++;

  $f=invid2files($r['id'],$dbh);
  //create file links
  $flnk="";
  for ($lnk="",$c=0;$c<count($f);$c++) {
   $fname=$f[$c]['fname'];
   $fid=$f[$c]['id'];
   $ftype=$f[$c]['type'];
   $ftypestr=ftype2str($ftype,$dbh);
   $ftitle=$f[$c]['title'];
   if (strlen($ftitle)) $t="Title:$ftitle"; else $t="";
   $flnk.="<span style='width:400px' class='fileslist1' >".
	 "<a target=_blank title='$ftitle' href='".$uploaddirwww.$fname."'>$fname</a>".
	 "&nbsp;&nbsp;$t".
	 "</span>\n ";
  }

  $d=strlen($r['date'])?date($dateparam,$r['date']):"";

  echo "\n<tr id='trid{$r['id']}'>";
  echo "<td><a class='editid' href='$scriptname?action=editinvoice&amp;id=".$r['id']."'>";
  echo "{$r['id']}</a></td>\n";
  echo "<td>".$agents[$r['vendorid']]['title']."</td>\n";
  echo "<td>".$agents[$r['buyerid']]['title']."</td>\n";
  echo "<td ><span title='{$r['date']}'></span>$d</td>\n";
  echo "<td>{$r['number']}</td>\n";
  echo "<td>{$r['description']}</td>\n";
  echo "<td>$flnk</td>\n";
  echo "</tr>\n\n";
}

//empty line to add new items at bottom
?>

</tbody>
</table>
</div>
</form>
</body>
</html>
