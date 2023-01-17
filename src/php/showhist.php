<script type="text/javascript">
$(function () {
  $('table#histtbl').dataTable({
                "sPaginationType": "full_numbers",
                "bJQueryUI": true,
                "iDisplayLength": 25,
                "bLengthChange": true,
                "bFilter": true,
                "bSort": true,
                "bInfo": true,
                //"sDom": '<"H"lfr>t<"F"ip>',
                "sDom": '<"H"Tlpf>rt<"F"ip>',
                "oTableTools": {
                        "sSwfPath": "swf/copy_cvs_xls_pdf.swf"
                }

  });
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

?>
<h1>History of Changes</h1>
<table class='display' width='100%' id='histtbl'>
<thead>
<tr><th>ID</th>
     <th>Date</th>
     <th>SQL</th>
     <th>IP</th>
     <th>User</th>
     </tr>


</thead>
<tbody>


<?php


$sql="SELECT * FROM history  $where order by id desc ";

/// make db query
$sth=db_execute($dbh,$sql);

/// display results
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  //2seconds
  $d=strlen($r['date'])?date($dateparam,$r['date']):"-"; 


  //table row
  echo "\n<tr>".
       "\n  <td>".$r['id']."</td>".
       "\n  <td>$d&nbsp;</td>".
       "\n  <td style='font-size:0.8em'>".$r['sql']."</td>".
       "\n  <td>".$r['ip']."</td>".
       "\n  <td>".$r['authuser']."&nbsp;</td>".
       "\n</tr>";
}

echo "</tbody>\n";
echo "</table>\n";

?>
