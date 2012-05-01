<SCRIPT LANGUAGE="JavaScript"> 
$(function () {
 //$('input#fileslistfilter').quicksearch('table#fileslisttbl tbody tr');
  $('table#fileslisttbl').dataTable({
                "sPaginationType": "full_numbers",
                "bJQueryUI": true,
                "iDisplayLength": 25,
                "aLengthMenu": [[10,25, 50, 100, -1], [10,25, 50, 100, "All"]],
                "bLengthChange": true,
                "bFilter": true,
                "bSort": true,
                "bInfo": true,
                "sDom": '<"H"Tlpf>rt<"F"ip>',
                "oTableTools": {
                        "sSwfPath": "swf/copy_cvs_xls_pdf.swf"
                }
                //"bAutoWidth": true, 

  });
});

</SCRIPT>
<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2010 , sivann _at_ gmail.com */

$sql="SELECT files.id,title,fname,typedesc from files,filetypes WHERE files.type=filetypes.id order by files.id desc";
$sth=db_execute($dbh,$sql);
?>

<h1><?php te("Files");?> <a title='<?php te("Add new File");?>' href='<?php echo $scriptname?>?action=editfile&amp;id=new'><img border=0 src='images/add.png' ></a>
</h1>


<table class='display' width="100%" id='fileslisttbl'>

<thead>
<tr>
  <th width='5%' ><?php te("Edit ID");?></th>
  <th><?php te("Type");?></th>
  <th style='min-width:300px'><?php te("Title");?></th>
  <th><?php te("File");?></th>
  <th><?php te("Associations");?></th>
</tr>
</thead>
<tbody>
<?php 

$i=0;
/// print actions list
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  $i++;
  $nlinks=countfileidlinks($r['id'],$dbh);
  $type=$r['typedesc'];
  if ($type=="invoice") $type="<span style='color:#0076A0'>$type</span>";
  if (!($i%2)) $cl="class=dark";else $cl="";
 
  echo "\n<tr $cl id='trid{$r['id']}'>";
  echo "<td><a class='editid' href='$scriptname?action=editfile&amp;id=".$r['id']."'>{$r['id']}</a></td>\n";
  echo "<td style='padding-left:2px;padding-right:2px;'>$type</td>\n";
  echo "<td style='padding-left:2px;padding-right:2px;'>{$r['title']}</td>\n";
  echo "<td><a class='smaller' target=_blank href='$uploaddirwww{$r['fname']}'>{$r['fname']}</a></td>\n";
  if (!$nlinks)
    echo "<td style='background-color:pink'>$nlinks</td>\n";
  else
    echo "<td>$nlinks</td>\n";
  echo "</tr>\n";
}
?>

</tbody>
</table>

</form>
</body>
</html>
