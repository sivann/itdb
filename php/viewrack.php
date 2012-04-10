<?php 

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */

if (!isset($initok)) {echo "do not run this script directly";exit;}


$id=$_GET['id'];

$sql="SELECT * from racks where id=$id";
$sth=$dbh->query($sql);
//while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $racks[$id]=$r;
$rack=$sth->fetch(PDO::FETCH_ASSOC);

$sql="SELECT items.*,agents.title as agtitle from items,agents WHERE agents.id=items.manufacturerid AND rackid='$id'";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $items[$r['id']]=$r;

$err="";

//find item positions in rack for all racks
$mi=0;
if (isset($items) && $rack['revnums']) { //reverse rack row numbering
  foreach ($items as $it) {

    if (!is_numeric($it['rackposition']) || !is_numeric($it['usize'])) {
      $moreitems[$mi++]=$it;
      continue; //items with wrong position info
    }

    if (($it['rackposition']+$it['usize']-1)>$rack['usize']) {
      $err.= "Item {$it['id']}  ({$it['model']}) exceeds rack boundaries!<br>";
      continue;
    }


    for ($pos=$it['rackposition'];$pos<($it['rackposition']+$it['usize']) ;$pos++) {

      if (($it['rackposdepth']&4) && isset($rackrow[$pos]['F']) && $rackrow[$pos]['F'] && $rackrow[$pos]['F']!=$it['id']) {
	$err.="Position conflict in row $pos Front for items ".
	     "<a href='$scriptname?action=edititem&amp;id={$it['id']}'>{$it['id']}</a> and ".
	     "<a href='$scriptname?action=edititem&amp;id={$rackrow[$pos]['F']}'>{$rackrow[$pos]['F']}</a><br>";
      }
      if (($it['rackposdepth']&2) && isset($rackrow[$pos]['M']) && $rackrow[$pos]['M'] && $rackrow[$pos]['M']!=$it['id']) {
	$err.="Position conflict in row $pos Middle for items ".
	     "<a href='$scriptname?action=edititem&amp;id={$it['id']}'>{$it['id']}</a> and ".
	     "<a href='$scriptname?action=edititem&amp;id={$rackrow[$pos]['M']}'>{$rackrow[$pos]['M']}</a><br>";
      }
      if (($it['rackposdepth']&1) && isset($rackrow[$pos]['B']) && $rackrow[$pos]['B'] && $rackrow[$pos]['B']!=$it['id']) {
	$err.="Position conflict in row $pos Back for items ".
	     "<a href='$scriptname?action=edititem&amp;id={$it['id']}'>{$it['id']}</a> and ".
	     "<a href='$scriptname?action=edititem&amp;id={$rackrow[$pos]['B']}'>{$rackrow[$pos]['B']}</a><br>";
      }

      if ($pos==$it['rackposition']) $isitemtop=1; else $isitemtop=0;
      if ($it['rackposdepth']&4) {$rackrow[$pos]['F']=$it['id']; $rackrow[$pos]['FT']=$isitemtop;}
      if ($it['rackposdepth']&2) {$rackrow[$pos]['M']=$it['id']; $rackrow[$pos]['MT']=$isitemtop;}
      if ($it['rackposdepth']&1) {$rackrow[$pos]['B']=$it['id']; $rackrow[$pos]['BT']=$isitemtop;}

    }//for usize

  } //foreach

}
else if (isset($items)) { //normal row numbering (bottom==1)
  foreach ($items as $it) {

    if (!is_numeric($it['rackposition']) || !is_numeric($it['usize'])) {
      $moreitems[$mi++]=$it;
      continue; //items with wrong position info
    }

    if ($it['rackposition']-$it['usize']<0) {
      $err.= "Item {$it['id']}  ({$it['model']}) exceeds rack boundaries!<br>";
      continue;
    }

    for ($pos=$it['rackposition'];$pos>($it['rackposition']-$it['usize']) ;$pos--) {

      if (($it['rackposdepth']&4) && isset($rackrow[$pos]['F']) && $rackrow[$pos]['F'] && $rackrow[$pos]['F']!=$it['id']) {
	$err.="Position conflict in row $pos Front for items ".
	     "<a href='$scriptname?action=edititem&amp;id={$it['id']}'>{$it['id']}</a> and ".
	     "<a href='$scriptname?action=edititem&amp;id={$rackrow[$pos]['F']}'>{$rackrow[$pos]['F']}</a><br>";
      }
      if (($it['rackposdepth']&2) && isset($rackrow[$pos]['M']) && $rackrow[$pos]['M'] && $rackrow[$pos]['M']!=$it['id']) {
	$err.="Position conflict in row $pos Middle for items ".
	     "<a href='$scriptname?action=edititem&amp;id={$it['id']}'>{$it['id']}</a> and ".
	     "<a href='$scriptname?action=edititem&amp;id={$rackrow[$pos]['M']}'>{$rackrow[$pos]['M']}</a><br>";
      }
      if (($it['rackposdepth']&1) && isset($rackrow[$pos]['B']) && $rackrow[$pos]['B'] && $rackrow[$pos]['B']!=$it['id']) {
	$err.="Position conflict in row $pos Back for items ".
	     "<a href='$scriptname?action=edititem&amp;id={$it['id']}'>{$it['id']}</a> and ".
	     "<a href='$scriptname?action=edititem&amp;id={$rackrow[$pos]['B']}'>{$rackrow[$pos]['B']}</a><br>";
      }

      if ($pos==$it['rackposition']) $isitemtop=1; else $isitemtop=0;
      if ($it['rackposdepth']&4) {$rackrow[$pos]['F']=$it['id']; $rackrow[$pos]['FT']=$isitemtop;}
      if ($it['rackposdepth']&2) {$rackrow[$pos]['M']=$it['id']; $rackrow[$pos]['MT']=$isitemtop;}
      if ($it['rackposdepth']&1) {$rackrow[$pos]['B']=$it['id']; $rackrow[$pos]['BT']=$isitemtop;}

    }//for usize

  }
}

//echo "<pre>"; print_r($rackrow); echo "<p>";

echo "<h1>Rack ID:$id - {$rack['model']} {$rack['label']} </h1>";

?>
<div style='float:left;padding-left:10px;'>
<table class=rack>
<caption><?php t("SIDE VIEW");?></caption>

<tr><th title='Rack Unit<br>1 RU=44.45mm'>RU</th><th>Front</th><th>Middle</th><th>Back</th></tr>

<?php 

function printitemcell($rr,$depth) {
  global $rackrow,$items,$scriptname,$_GET;
  global $dbh;

  $dns=$items[$rackrow[$rr][$depth]]['dnsname'];
  $label=$items[$rackrow[$rr][$depth]]['label'];
  $dr=explode(".",$dns); if(count($dr)) $dr=$dr[0];
  $itemid=$items[$rackrow[$rr][$depth]]['id'];
  

  $mixlabel=" ";
  if (strlen($label)) $mixlabel=" $label ";
  if (strlen($dr))
    $mixlabel.=" [DNS:$dr]";
 
  $sid=getstatusidofitem($itemid,$dbh);
  $x=attrofstatus($sid,$dbh);
  $attr=$x[0];
  $statustxt=$x[1];

  return  "<span $attr>&nbsp;</span>&nbsp;<a href='$scriptname?action=edititem&amp;id={$rackrow[$rr][$depth]}'>".
	$items[$rackrow[$rr][$depth]]['agtitle']." ".
	$items[$rackrow[$rr][$depth]]['model']." ".
	" [ID:$itemid]".
	$mixlabel.
	"</a>";
}

if (isset($_GET['highlightid'])) $hid=$_GET['highlightid'];

if ($rack['revnums']) {
  for ($rr=1;$rr<=$rack['usize'];$rr++) {
      echo "\n<tr>\n";
      echo "<td style='background-color:white'>$rr</td>\n";
      $cell=1;
      $colspan=1;
      

      if ($rackrow[$rr]['FT']) { //is top row of this item?
        $rowspan=$items[$rackrow[$rr]['F']]['usize'];
	if ($rackrow[$rr]['F'] != $rackrow[$rr]['M'])  $colspan=1;
	elseif (($rackrow[$rr]['F'] == $rackrow[$rr]['M']) &&  ($rackrow[$rr]['M'] != $rackrow[$rr]['B']))  $colspan=2;
	elseif (($rackrow[$rr]['F'] == $rackrow[$rr]['M']) &&  ($rackrow[$rr]['M'] == $rackrow[$rr]['B']))  $colspan=3; //full row

	if ($hid==$rackrow[$rr]['F']) $c="highlight" ; else $c="occupied";
	echo " <td class='$c' colspan='$colspan' rowspan='$rowspan'>".printitemcell($rr,'F')."</td> ";
      } 
      elseif (!isset ($rackrow[$rr]['F']) || (!$rackrow[$rr]['F'])) { //empty cell
	echo " <td class='emptyrow'>&nbsp;</td> ";
	$colspan=1;
      }
      $cell+=$colspan;

      if ($cell==2) { //we have already printed one talbe cell in this row
	if ($rackrow[$rr]['MT']) { //is top row of this item?
	  $rowspan=$items[$rackrow[$rr]['M']]['usize'];
	  if ($rackrow[$rr]['M'] != $rackrow[$rr]['B'])  $colspan=1;
	  elseif ($rackrow[$rr]['M'] == $rackrow[$rr]['B'])  $colspan=2;
	  if ($hid==$rackrow[$rr]['M']) $c="highlight" ; else $c="occupied";
	  echo "<td class='$c' colspan='$colspan' rowspan='$rowspan'>".printitemcell($rr,'M')."</td>";
	  $cell+=$colspan;
	}
	elseif (!isset ($rackrow[$rr]['M']) || (!$rackrow[$rr]['M'])) { //empty cell
	  echo " <td class='emptyrow'>&nbsp;</td> ";
	  $colspan=1;
	}
	$cell+=$colspan;
      }//cell==2

//echo "<br>$rr C:$cell B:".$rackrow[$rr]['B']. " BT:".$rackrow[$rr]['BT'];

      if ($cell==3) {

	if ($rackrow[$rr]['BT']) { //is top row of this item?
	  $rowspan=$items[$rackrow[$rr]['B']]['usize'];
	  if ($hid==$rackrow[$rr]['B']) $c="highlight" ; else $c="occupied";
	  echo "<td class='$c' colspan='1' rowspan='$rowspan'>".printitemcell($rr,'B')."</td>";
	}
	elseif (!isset ($rackrow[$rr]['B']) || (!$rackrow[$rr]['B'])) { //empty cell
	  echo " <td class='emptyrow'>&nbsp;</td> ";
	  $colspan=1;
	}
      }

    echo "\n</tr>\n";
  }//for


}

else {
  for ($rr=$rack['usize'];$rr>0;$rr--) {
      echo "\n<tr>\n";
      echo "<td style='background-color:white'>$rr</td>\n";
      $cell=1;
      $colspan=1;
      

      if ($rackrow[$rr]['FT']) { //is top row of this item?
        $rowspan=$items[$rackrow[$rr]['F']]['usize'];
	if ($rackrow[$rr]['F'] != $rackrow[$rr]['M'])  $colspan=1;
	elseif (($rackrow[$rr]['F'] == $rackrow[$rr]['M']) &&  ($rackrow[$rr]['M'] != $rackrow[$rr]['B']))  $colspan=2;
	elseif (($rackrow[$rr]['F'] == $rackrow[$rr]['M']) &&  ($rackrow[$rr]['M'] == $rackrow[$rr]['B']))  $colspan=3; //full row

	if ($hid==$rackrow[$rr]['F']) $c="highlight" ; else $c="occupied";
	echo " <td class='$c' colspan='$colspan' rowspan='$rowspan'>".printitemcell($rr,'F')."</td> ";
      } 
      elseif (!isset ($rackrow[$rr]['F']) || (!$rackrow[$rr]['F'])) { //empty cell
	echo " <td class='emptyrow'>&nbsp;</td> ";
	$colspan=1;
      }
      $cell+=$colspan;

      if ($cell==2) { //we have already printed one talbe cell in this row
	if ($rackrow[$rr]['MT']) { //is top row of this item?
	  $rowspan=$items[$rackrow[$rr]['M']]['usize'];
	  if ($rackrow[$rr]['M'] != $rackrow[$rr]['B'])  $colspan=1;
	  elseif ($rackrow[$rr]['M'] == $rackrow[$rr]['B'])  $colspan=2;
	  if ($hid==$rackrow[$rr]['M']) $c="highlight" ; else $c="occupied";
	  echo "<td class='$c' colspan='$colspan' rowspan='$rowspan'>".printitemcell($rr,'M')."</td>";
	  $cell+=$colspan;
	}
	elseif (!isset ($rackrow[$rr]['M']) || (!$rackrow[$rr]['M'])) { //empty cell
	  echo " <td class='emptyrow'>&nbsp;</td> ";
	  $colspan=1;
	}
	$cell+=$colspan;
      }//cell==2

      //echo "<br>$rr C:$cell B:".$rackrow[$rr]['B']. " BT:".$rackrow[$rr]['BT'];

      if ($cell==3) {

	if ($rackrow[$rr]['BT']) { //is top row of this item?
	  $rowspan=$items[$rackrow[$rr]['B']]['usize'];
	  if ($hid==$rackrow[$rr]['B']) $c="highlight" ; else $c="occupied";
	  echo "<td class='$c' colspan='1' rowspan='$rowspan'>".printitemcell($rr,'B')."</td>";
	}
	elseif (!isset ($rackrow[$rr]['B']) || (!$rackrow[$rr]['B'])) { //empty cell
	  echo " <td class='emptyrow'>&nbsp;</td> ";
	  $colspan=1;
	}
      }

    echo "\n</tr>\n";
  }


}


?>
<tr><td colspan=4 style='background-color:#666;border:1px solid #666;padding:0;'>&nbsp;</td></tr>
<tr>
<td style='border:0'><td style='padding:0;border:0;text-align:center'><img height=30 src='images/rackwheel.png'></td>
<td style='border:0'><td style='padding:0;border:0;text-align:center'><img height=30 src='images/rackwheel.png'></td>
</tr>
</table>

</div>

<div style='float:left;padding-left:20px;'>
<?php 
if ($mi) {
  echo "<h4>".t("More items assigned to this rack without position or u-size info").":</h4>";
  echo "<ul style='text-align:left;'>";
  for ($i=0;$i<$mi;$i++) {
    echo  "<li><a href='$scriptname?action=edititem&amp;id={$moreitems[$i]['id']}'>".
	  "Item ".$moreitems[$i]['id'].": ".
	  $moreitems[$i]['manufacturerid']." ".
	  $moreitems[$i]['model']." ".
	  $moreitems[$i]['label']."</a></li>";
  }
  echo "</ul>";
}

echo "<p>";
echo $err;
?>
</div>

<?php
if ($action=="viewrack") {
?>
<script type="text/javascript">
$('a').attr("target", "_new");
</script>

<?php }?>


