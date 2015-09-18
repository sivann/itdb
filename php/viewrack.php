<?php 

/* Spiros Ioannou 2009 , sivann _at_ gmail.com */

if (!isset($initok)) {echo "do not run this script directly";exit;}


$id=$_GET['id'];

$sql="SELECT * from racks where id=$id";
$sth=$dbh->query($sql);
//while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $racks[$id]=$r;
$rack=$sth->fetch(PDO::FETCH_ASSOC);


//$sql="SELECT items.*,agents.title as agtitle from items,agents WHERE agents.id=items.manufacturerid AND rackid='$id'";

$sql="SELECT items.*,agents.title as agtitle,files.fname as fname, item2file.itemid
	FROM items,agents,files,item2file
	WHERE agents.id=items.manufacturerid 
	AND item2file.itemid = items.id 
	AND files.id = item2file.fileid
	AND rackid='$id'";

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
	// error message
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

//echo "<h1>Rack ID:$id - {$rack['model']} {$rack['label']} </h1>";

?>
<div style='float:left;padding-left:10px;'>
<table class=rack>
<caption><?php t("FRONT VIEW");?></caption>

<tr><th title='Rack Unit<br>1 RU=44.45mm'>RU</th><th colspan="3">Front</th></tr>

<?php 

function printitemcell($rr,$depth) {
  global $rackrow,$items,$scriptname,$_GET;
  global $dbh;

  $dns=$items[$rackrow[$rr][$depth]]['dnsname'];
  $label=$items[$rackrow[$rr][$depth]]['label'];
  $dr=explode(".",$dns); if(count($dr)) $dr=$dr[0];
  $itemid=$items[$rackrow[$rr][$depth]]['id'];
  $pictureName = $items[$rackrow[$rr][$depth]]['fname'];
  $pictureSize = ($items[$rackrow[$rr][$depth]]['usize']*10)+($items[$rackrow[$rr][$depth]]['usize']*15);
  $abbr;
  

  $mixlabel=" ";
  if (strlen($label)) $mixlabel=" $label ";
  if (strlen($dr))
    $mixlabel.=" [DNS:$dr]";
 
  $sid=getstatusidofitem($itemid,$dbh);
  $x=attrofstatus($sid,$dbh);
  $attr=$x[0];
  $statustxt=$x[1];


if (strpos($pictureName, 'Vertical') !== false)
{
   $imageString = "<img width='auto' height='288px' src='data/files/".$pictureName."'>";
}
elseif (strpos($pictureName, 'Small') !== false)
{
   $imageString = "<img width='auto'".$pictureSize."' src='data/files/".$pictureName."'>";
}
else
{
   $imageString = "<img width='325px'".$pictureSize."' src='data/files/".$pictureName."'>";
}

//This is Display
  return  "<div align='center'> 
	<span $attr>&nbsp;</span>&nbsp;
	<a href='$scriptname?action=edititem&amp;id={$rackrow[$rr][$depth]}'>".
	$imageString . 
	"</a></div>";
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

		if ($hid==$rackrow[$rr]['F']) $c="highlight" ; 
		else $c="occupied";
		echo " <td class='$c' colspan='$colspan' rowspan='$rowspan'>".printitemcell($rr,'F')."</td> ";
      } 
      elseif (!isset ($rackrow[$rr]['F']) || (!$rackrow[$rr]['F'])) { //empty cell
		echo " <td class='emptyrow'>&nbsp;</td> ";
		$colspan=1;
      }
      $cell+=$colspan;

      if ($cell==2) { //we have already printed one table cell in this row
		if ($rackrow[$rr]['MT']) { //is top row of this item?
	  	$rowspan=$items[$rackrow[$rr]['M']]['usize'];
	  		if ($rackrow[$rr]['M'] != $rackrow[$rr]['B'])
  				$colspan=1;
	  			//$abbr='hit1';
	  		elseif ($rackrow[$rr]['M'] == $rackrow[$rr]['B'])
				$colspan=2;
	  			//$abbr='hit2';
	  		else
				$colspan=1;
				//$abbr='hit3';
	  	if ($hid==$rackrow[$rr]['M'])
	 		$c="highlight" ;
	  	else 
			$c="occupied";
	  
	  	echo "<td class='$c' colspan='$colspan' rowspan='$rowspan' abbr='$cell' >".printitemcell($rr,'M')."</td>";
	  	$cell+=$colspan;
	}
	elseif (!isset ($rackrow[$rr]['M']) || (!$rackrow[$rr]['M'])) { //empty cell
	  	echo " <td class='emptyrow'>&nbsp;</td> ";
	  	$colspan=1;
	}
	//$cell+=$colspan;
      $cell=$colspan+2;

//echo "<br>$rr C:$cell B:".$rackrow[$rr]['B']. " BT:".$rackrow[$rr]['BT'];

	if ($cell==3) {
		if ($rackrow[$rr]['BT']) { //is top row of this item?
	  	$rowspan=$items[$rackrow[$rr]['B']]['usize'];
	  		if ($hid==$rackrow[$rr]['B']) $c="highlight" ; 
			else $c="occupied";
	  	echo "<td class='$c' colspan='1' rowspan='$rowspan'>".printitemcell($rr,'B')."</td>";
	}
	elseif (!isset ($rackrow[$rr]['B']) || (!$rackrow[$rr]['B'])) { //empty cell
	  	echo " <td class='emptyrow' abbr='$cell'>&nbsp;</td> ";
	  	$colspan=1;
	}
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

      if ($cell==2) { //we have already printed one table cell in this row
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
<!--<td style='border:0'><td style='padding:0;border:0;text-align:center'><img height=30 src='images/rackwheel.png'></td>
<td style='border:0'><td style='padding:0;border:0;text-align:center'><img height=30 src='images/rackwheel.png'></td>-->
</tr>
</table>

</div>
<div style='float:right;'>

<?php

	if ($mi) {
	echo "<table class=rack>
		<tr>
			<th>Item ID</th>
			<th>Model</th>
			<th>Image</th>
			<th>Reason(s)</th>
		</tr>
		<tr><style='text-align:left;'>";
	
	for ($i=0;$i<$mi;$i++) {
		$sid=getstatusidofitem($moreitems[$i]['id'],$dbh);
		$x=attrofstatus($sid,$dbh);
		$attr=$x[0];
		$statustxt=$x[1];
		$nusize=$moreitems[$i]['usize'];
		$rpos=$moreitems[$i]['rackposition'];
		$yispart=$moreitems[$i]['ispart'];
		$rmount=$moreitems[$i]['rackmountable'];
		$istatus=$moreitems[$i]['status'];
		
	if ($nusize==NULL || $nusize=='0')
	{
		  $e_usize="No 'U' size.<br>";
	}
	else
	{
		$e_usize="";
	}
	if ($rpos==NULL)
	{
		$e_rpos="No rack position assigned.<br>";
	}
	  	elseif ($rpos=($moreitems[$i]['rackposdepth']&4) && isset($rackrow[$pos]['F']) && $rackrow[$pos]['F'] && $rackrow[$pos]['F']!=$moreitems[$i]['id'])
		{
			$e_rpos="Position conflict in row $pos Front for items <br>";
		}
		elseif (($moreitems[$i]['rackposdepth']&2) && isset($rackrow[$pos]['M']) && $rackrow[$pos]['M'] && $rackrow[$pos]['M']!=$moreitems[$i]['id'])
		{
			$e_rpos="Position conflict in row $pos Middle for items <br>";
		}
		elseif (($moreitems[$i]['rackposdepth']&1) && isset($rackrow[$pos]['B']) && $rackrow[$pos]['B'] && $rackrow[$pos]['B']!=$moreitems[$i]['id'])
	  	{
			$e_rpos="Position conflict in row $pos Back for items <br>";
		}
	if ($yispart=='1')
	{
		$e_part="Is part of another item.<br>";
	}
	  else
	  {
		  $e_part="";
	  }
	if ($rmount=='0')
	{
		$e_rmount="Not rackmountable.<br>";
	}
		elseif ($rmount==NULL)
		{
			$e_rmount="Rackmount option not selected.<br>";
		}
		else
		{
			$e_rmount="";
		}
	
	if ($moreitems[$i]['status']=='5')
		{
			$e_status="Item is missing!";
		}	
		else
		{
			$e_status="";
		}
		
	if ($e_usize!="" || $e_rpos!="" || $e_part!="" || $e_rmount!="" || $e_status!="")
		{
		echo 
		"<td><a href='$scriptname?action=edititem&amp;id={$moreitems[$i]['id']}'>"." ".$moreitems[$i]['id']."</td>".
		//$moreitems[$i]['manufacturerid']." ".
		"<td><span $attr>&nbsp</span>&nbsp<a href='$scriptname?action=edititem&amp;id={$moreitems[$i]['id']}'>".$moreitems[$i]['model']."</td>".
		//$moreitems[$i]['label']." ".
		//$moreitems[$i]['fname']." ".
		"<td><a href='$scriptname?action=edititem&amp;id={$moreitems[$i]['id']}'><center><img style='max-width:100px; max-height:100px;' src='data/files/".$moreitems[$i]['fname']."'></center></td>".
		"<td>
		$e_usize
		$e_rpos
		$e_part
		$e_rmount
		$e_status</td></tr>";
		}
	else
		{
		echo "";	
		}

  }

}
if ($mi) {	
	echo "<tr><td colspan='4'><h4><center>**Extra items associated with this rack.</center></h4></td></tr></table>";
}
	


?>
</div>

<?php
	if ($action=="viewrack")
	{
?>
		<script type="text/javascript">
			$('a').attr("target", "_new");
		</script>

<?php
	}
?>