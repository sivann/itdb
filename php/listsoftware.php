<SCRIPT LANGUAGE="JavaScript"> 
$(function () {
 //$('input#softlistfilter').quicksearch('table#softlisttbl tbody tr');

  $('table#softlisttbl').dataTable({
                "sPaginationType": "full_numbers",
                "bJQueryUI": true,
                "iDisplayLength": 6,
                "aLengthMenu": [[6,9, 25, 50, 100, -1], [6,9, 25, 50, 100, "All"]],
                "bLengthChange": true,
                "bFilter": true,
                "bSort": true,
                "bInfo": true,
                "sDom": '<"H"Tlpf>rt<"F"ip>',
                "oTableTools": {
                        "sSwfPath": "swf/copy_cvs_xls_pdf.swf"
                }

  });

});

</SCRIPT>
<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */

$sql="SELECT id,title FROM agents";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $agents[$r['id']]=$r;

$sql="SELECT * FROM invoices";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $invoices[$r['id']]=$r;

$sql="SELECT software.*,agents.id as agid ,agents.type as agtype, agents.title as agtitle FROM software, agents ".
     " WHERE manufacturerid=agid order by agtype,stitle";
$sth=db_execute($dbh,$sql);
?>

<h1><?php te("Software");?> <a  title='<?php te("Add new software");?>' href='<?php echo $scriptname?>?action=editsoftware&amp;id=new'><img border=0 src='images/add.png'></a> </h1>

<div class='scrtblcontainerlist'>

<table  class="display" border=0 id="softlisttbl">

<thead>
     <tr>
     <th nowrap><?php te("ID");?></th><th><?php te("Manufacturer");?></th><th><?php te("Title");?></th><th><?php te("Version");?></th><th><?php te("Purchase Date");?></th>
     <th title='<?php te("maintenance end date");?>'><?php te("Maint. End");?></th>
     <th><?php te("License Info");?></th><th><?php te("Other Info");?></th>
     <th><?php te("Tags");?></th>
     <th><?php te("QTY");?></th>
     <th title='<?php te("taken from related invoice");?>'><?php te("Vendor");?></th>
     <th><?php te("Invoice");?></th>
     <th style='width:25em'><?php te("Installed on");?></th>
     </tr>
</thead>
<tbody>

<?php 
$row=0;
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  $row++;

  foreach($r as $k => $v) { ${$k} = $v; } // get all columns as variables

  //print a table row
  $sql="SELECT items.id i_id, status,manufacturerid,model,dnsname,cpuno,corespercpu from items,item2soft ".
       " where item2soft.itemid=items.id  AND item2soft.softid={$r['id']}";
  $sthi=db_execute($dbh,$sql);
  $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
  $nitems=count($ri);
  $institems="";
  $licitems=0;

  for ($i=0;$i<$nitems;$i++) {
    $rstatus=(int)$ri[$i]['status'];
    if ($rstatus==1) { $attr="style='background-color:green;font-weight:bold;color:#efefef' title='Status: Stored'"; }
    elseif ($rstatus==2) { $attr="style='background-color:red;font-weight:bold;' title='Status: Defective'"; }
    elseif ($rstatus==3) { $attr="style='background-color:#cecece;font-weight:bold;' title='Status: Obsolete'"; }
    else { $attr=" title='Status: In Use' "; }

    $x=($i+1).": <span $attr >({$ri[$i]['i_id']}) </span>".$agents[$ri[$i]['manufacturerid']]['title']." ".$ri[$i]['model']." ".$ri[$i]['dnsname'];

    if ($i%2) $bcolor="#D9E3F6";
    //if ($i%2) $bcolor="#ECF1FB";
    else $bcolor="#ffffff";
    $institems.="<div style='margin:0;padding:0;background-color:$bcolor'>".
                "<a href='$scriptname?action=edititem&amp;id={$ri[$i]['i_id']}'>$x</a></div>";

    if (empty($lictype) || $lictype==0) { $licitems++; } //per box
    elseif ($lictype==1) { $licitems+=$ri[$i]['cpuno']; } //per cpu
    elseif ($lictype==2) { $licitems+=$ri[$i]['cpuno']*$ri[$i]['corespercpu']; } //per core
  }//items


  //print a table row
  $sql="SELECT invoices.id,invoices.date,agents.title as agtitle FROM invoices, soft2inv, agents ".
       " WHERE agents.id=invoices.vendorid AND soft2inv.invid=invoices.id AND soft2inv.softid={$r['id']}";
  $sthi=db_execute($dbh,$sql);
  $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
  $ninv=count($ri);
  
  for ($invinfo="",$i=0;$i<$ninv;$i++) {
    $f=invid2files($ri[$i]['id'],$dbh);
    $iid=$ri[$i]['id'];
    $idate=date($dateparam,$ri[$i]['id']);
    for ($flnk="",$c=0;$c<count($f);$c++) {
       $fname=$f[$c]['fname'];
       $ftitle=$f[$c]['title'];
       $flnk.=" <a target=_blank title='View FILE: $ftitle $fname' href='".$uploaddirwww.$fname."'><img src='images/down.png'></a>";
    }
    $invinfo.="<div style='min-width:70px;'><a title='Edit INVOICE' href='$scriptname?action=editinvoice&amp;id=$iid'><div class='editid'>$iid</div><div>$flnk</div></a></div>";
    $flnk="";
  }

  //if (!($row%2)) $s=" class='dark' "; else $s="";
?>

  <tr>
  <td><div class='editid'><a title='<?php te("Edit Software");?>' href='<?php echo $scriptname?>?action=editsoftware&amp;id=<?php echo $r['id']?>'><?php echo $id?></a></div>
  </td>

<?php 

  if ($licqty<$licitems) $style="style='font-weight:bold;color:red'";
  elseif ($licqty==$licitems) $style="style='font-weight:normal;color:black'";
  elseif ($licqty>$licitems) $style="style='font-weight:bold;color:#00aa00'";

  $mend=strlen($maintend)?date($dateparam,$maintend):"";
  $mendkey=strlen($maintend)?date("Ymd",$maintend):"0";
  $d=strlen($purchdate)?date($dateparam,$purchdate):"";
  $dkey=strlen($purchdate)?date("Ymd",$purchdate):"0";
  $vendor=$agents[$invoices[$invoiceid]['vendorid']]['title'];

  //show red dates for expired maintenance contracts
  $nowymd=date("Ymd");
  if ($nowymd>$mendkey)
    $mend="<span style='color:red;font-weight:bold'>$mend</span>";
  else
    $mend="<span style='color:green;font-weight:bold'>$mend</span>";


?>

  <td><?php echo $agtitle?></td>
  <td><?php echo $stitle?></td>
  <td><?php echo $sversion?></td>
  <td sorttable_customkey="<?php echo $dkey?>"><?php echo $d?></td>
  <td sorttable_customkey="<?php echo $mendkey?>"><?php echo $mend?></td>
  <td><?php echo $slicenseinfo?></td>
  <td><?php echo $sinfo?></td> <!-- other info -->
  <td><small><?php  echo showtags("software",$r['id']); ?></small></td>
  <td <?php echo $style?>><?php echo $licitems?>/<?php echo $licqty?></td>
  <td><?php echo $vendor?></td>
  <td ><?php echo $invinfo?></td>
  <td><div class='scrlswlist'><?php echo $institems?></div></td>
  </tr>
<?php 
} //while

?>
</tbody>
</table>

</div>
</body>
</html>
