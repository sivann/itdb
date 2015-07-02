<?php

if (isset($_POST['labelaction']) && $_POST['labelaction']=="savepreset") {
  if (!strlen($_POST['name'])) {
    echo "<b><big>Not saved: specity preset name!</big></b>";
  }
  else {
    //damn checkboxes dont' post their name when "off" :
    if (!isset($wantbarcode)) $wantbarcode=0;
    if (!isset($wantheadertext)) $wantheadertext=0;
    if (!isset($wantheaderimage)) $wantheaderimage=0;
    if (!isset($wantnotext)) $wantnotext=0;
    if (!isset($wantraligntext)) $wantraligntext=0;

    foreach($_POST as $k => $v) { 
		${$k} = $v; 
		if (strstr($k,"want") && $v=="on")  //checkboxes are "on" when checked, we want "1"
		$$k=1;
    }

    $sql="INSERT INTO labelpapers ".
    " (rows,cols,lwidth,lheight, vpitch, hpitch, tmargin, bmargin, lmargin, rmargin, name,  ".
    " border, padding, fontsize, headerfontsize, barcodesize, idfontsize, wantbarcode, wantheadertext, wantheaderimage,  ".
    " headertext,image,imagewidth,imageheight,papersize,qrtext,wantnotext,wantraligntext) ".
    " values ($rows,$cols,$lwidth,$lheight, $vpitch, $hpitch, $tmargin, $bmargin, $lmargin, $rmargin, '$name', ".
    " $border, $padding, $fontsize, $headerfontsize,$barcodesize, $idfontsize, $wantbarcode, $wantheadertext, $wantheaderimage, ".
    " '$headertext', '$image', '$imagewidth', '$imageheight', '$papersize','".htmlentities($qrtext, ENT_QUOTES)."','$wantnotext','$wantraligntext' )";
    $sth=db_execute($dbh,$sql);
  }
}

if (!isset($initok)) {echo "do not run this script directly";exit;}

?>
<script>
function ldata(rows,cols,lwidth,lheight, vpitch, hpitch, tmargin, bmargin, lmargin, rmargin,name, 
               border,padding,fontsize, headerfontsize,barcodesize, idfontsize,wantbarcode,wantheadertext,wantheaderimage,
               headertext,image,imageheight,imagewidth,papersize,qrtext,wantnotext,wantraligntext)
{
  document.selitemsfrm.lwidth.value=lwidth;
  document.selitemsfrm.lheight.value=lheight;
  document.selitemsfrm.vpitch.value=vpitch;
  document.selitemsfrm.hpitch.value=hpitch;
  document.selitemsfrm.tmargin.value=tmargin;
  document.selitemsfrm.bmargin.value=bmargin;
  document.selitemsfrm.lmargin.value=lmargin;
  document.selitemsfrm.rmargin.value=rmargin;
  document.selitemsfrm.name.value=name;

  document.selitemsfrm.border.value=border;
  document.selitemsfrm.padding.value=padding;
  document.selitemsfrm.headerfontsize.value=headerfontsize;
  document.selitemsfrm.barcodesize.value=barcodesize;
  document.selitemsfrm.idfontsize.value=idfontsize;
  document.selitemsfrm.fontsize.value=fontsize;
  document.selitemsfrm.image.value=image;
  document.selitemsfrm.imagewidth.value=imagewidth;
  document.selitemsfrm.imageheight.value=imageheight;
  document.selitemsfrm.qrtext.value=qrtext;

  $("#wantbarcode").prop("checked", wantbarcode);
  $("#wantheadertext").prop("checked", wantheadertext);
  $("#wantheaderimage").prop("checked", wantheaderimage);
  $("#wantnotext").prop("checked", 1*wantnotext);
  $("#wantraligntext").prop("checked", 1*wantraligntext);

  document.selitemsfrm.headertext.value=headertext;
  document.selitemsfrm.rows.selectedIndex = rows-1;
  document.selitemsfrm.cols.selectedIndex = cols-1;

  $("#pn_"+papersize).attr("selected", "selected");;
  $('#theimage').attr('src',$('#iimage').val());

}
$(document).ready(function() {

    $("#tabs").tabs();
    $("#tabs").show();

    $("#selitems option").clone().appendTo('#selitems2');

    $("#filter").keyup(function () {
        var filter = $(this).val(), count = 0;
	if (filter=='') { //empty filter, re-copy all from selitems2
	  $("#selitems option").remove();
	  $("#selitems2 option").clone().appendTo('#selitems');
	}
	else {

	  $("#selitems option").remove();
	    $("#selitems2 option").each(function () {
		if ($(this).text().search(new RegExp(filter, "i")) < 0) { //not found
		} else {
		    $(this).clone().appendTo('#selitems');
		    count++;
		}
	    });
	}//else
	$("#filter-count").text(count+ ' <?php te("items");?>');
    });




    //submit pdf link as post
    $('#getitemspdf').click(function(e) {
      e.preventDefault();
      if  (!$("#selitems :selected").length) {
        alert('Select items from the list first');
	return;
      }

      $("#selitemsfrm").attr("action", "php/printitemlabels_pdf.php");
      $('#selitemsfrm').submit();
    });


    $('#savepreset').click(function(e) {
      $("#selitemsfrm").attr("action", "?action=printlabels");
      $("#frmlabelaction").val("savepreset");
      $('#selitemsfrm').submit();
    });

    $('#iimage').keyup(function() {
      $('#theimage').attr('src',$('#iimage').val());
    });


    $( "#tabs" ).tabs();


});

</script>


<!-- secondary select for filtering -->
<select id='selitems2' name='selitems2[]' multiple size='1' style='display:none'> </select>
<?php
if (isset($_GET['delpaperid'])) {
  $sql="DELETE from labelpapers where id=".$_GET['delpaperid'];
  $sth=db_exec($dbh,$sql);
  echo "<script>document.location='$scriptname?action=$action'</script>\n";
  echo "<a href='$scriptdir?action=$action'>Go here</a>\n</body></html>"; 
  exit;
}

//damn checkboxes dont' post their name when "off" :
if (!isset($wantbarcode)) $wantbarcode=0;
if (!isset($wantheadertext)) $wantheadertext=0;
if (!isset($wantheaderimage)) $wantheaderimage=0;

if (isset($_POST['name']))  {
  foreach($_POST as $k => $v) { 
    ${$k} = $v; 
    if (strstr($k,"want") && $v=="on")  //checkboxes are "on" when checked, we want "1"
      $$k=1;
  }
}

$sql="SELECT * from itemtypes";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $itypes[$r['id']]=$r;


$sql="SELECT id,title,type FROM agents";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $agents[$r['id']]=$r;



$sql="SELECT * from labelpapers";
$sth=$dbh->query($sql);
$alllabels=$sth->fetchAll(PDO::FETCH_ASSOC);
for ($i=0;$i<count($alllabels);$i++) {
  $labelpapers[$alllabels[$i]['id']]=$alllabels[$i];
}

if (!isset($_POST['name'])) {
  foreach(array_keys($alllabels[0]) as $key) {
    $$key=$alllabels[0][$key];
  }
}


if (isset($_GET['orderby'])) 
  $orderby=$_GET['orderby'];
else 
  $orderby='status';

?>

<h1><?php te("Print Labels");?></h1>
<div id='labelcontainer'>

<form method=post id='selitemsfrm' name='selitemsfrm'>
  <div class='labellist' style='float:left;'>
    <div id='tabs'>
	<ul>
		<li><a href="#tabs-1"><?php te("Items");?></a></li>
	</ul>

      <div id="tabs-1">
	<div style='float:left;text-align:left'>
	  <b><?php te("Order By");?>:
	  <a title='<?php te("order: status, item type, manufacturer,id");?>' href='<?php  echo "$fscriptname?action=$action"?>'><?php te("[type]");?></a>
	  <a title='<?php te("order: status, id, item type, manufacturer");?>' href='<?php  echo "$fscriptname?action=$action&amp;orderby=items.id"?>'><?php te("[id]");?></a>
	  <a title='<?php te("order: status, id descending, item type, manufacturer");?>' href='<?php  echo "$fscriptname?action=$action&amp;orderby=items.id+desc"?>'><?php te("[id desc]");?></a>
	  <a title='<?php te("order: status, model, item type, manufacturer");?>' href='<?php  echo "$fscriptname?action=$action&amp;orderby=model"?>'><?php te("[model]");?></a>
	  </b>
	</div>

	<div style='float:right;text-align:left'>
	<b><?php te("Filter");?></b>:<input title='<?php te("enter text to filter listed items");?>' id="filter" name="filter" size="20">
	<span id='filter-count'></span> 
	</div>
      <br>

      <div id='selcontainer'>

      <?php

      $sth=db_execute($dbh,"SELECT count(id) as count from items");
      $r=$sth->fetch(PDO::FETCH_ASSOC) ;
      $sth->closeCursor();
      $nitems=$r['count'];

      echo "<select id='selitems' class='monospaced' name='selitems[]' multiple=multiple size='$nitems'>\n";

      $sql="SELECT items.id,manufacturerid,model,status,sn,sn3,itemtypeid,label ".
	   " FROM items,itemtypes ".
	   " WHERE items.itemtypeid=itemtypes.id ".
	   " ORDER BY status,$orderby,itemtypes.typedesc, manufacturerid,items.id, sn, sn2, sn3";
      $sth=db_execute($dbh,$sql);

      $pstatus=0;
      while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
	  $idesc=$itypes[$r['itemtypeid']]['typedesc'];
	  $idesc=sprintf("%-20s",$idesc);
	  $idesc=str_replace(" ","&nbsp;",$idesc);
	  $id=sprintf("%04d",$r['id']);

	  if (((int)$r['status']==2)&& ($pstatus!=2)) {
	    echo "\n<option disabled style='background-color:red;color:black;font-weight:bold;text-align:center'>Defective:</option>";
	    $pstatus=(int)$r['status'];
	  }
	  elseif (((int)$r['status']==1) && ($pstatus!=1)) {
	    echo "\n<option disabled style='background-color:#00BB5F;color:black;font-weight:bold;text-align:center'>Stored:</option>";
	    $pstatus=(int)$r['status'];
	  }
	  elseif (((int)$r['status']==3) && ($pstatus!=3)) {
	    echo "\n<option disabled style='background-color:#cecece;color:black;font-weight:bold;text-align:center'>Obsolete:</option>";
	    $pstatus=(int)$r['status'];
	  }
	  $sn=strlen($r['sn'])>0?$r['sn']:$r['sn3'];
	  if (isset ($_POST['selitems']) && (in_array($id, $_POST['selitems']))) $s="selected";
	  else $s="";

	  if (strlen($r['label']))$label="-".$r['label'];else $label="";

	  echo "<option class='monospaced' $s value='{$r['id']}'>".
	       "$id-$idesc|$status {$agents[$r['manufacturerid']]['title']}-{$r['model']}-$sn$label</option>\n";


      }

	$sth->closeCursor();

      ?>

      </select>
      </div><!--selcontainer-->


      <br><input class='prepbtn' id='getitemspdf' type=submit value='Make Item Labels'>
      <input type='hidden' name='labelaction' id='frmlabelaction' value=''>
      <br>
      <ol style='text-align:left'>
      <li><?php te("Select items from the list above");?></li>
      <li><?php te("Select Label properties (manual or preset)");?></li>
      <li><?php te("Click 'Make Item Labels'");?></li>
      <li><?php te("Download &amp; print the resulting PDF");?></li>
      </ol>

      <?php
	echo t("<br>In the PDF printing dialog,");?>
      <ul><li><?php te("set <b>'Page Scaling'</b> to <b>'None'</b>");?></li>
	  <li><?php te("<b>uncheck</b> 'auto-rotate &amp; center'</b>");?></li>
      </ul>

    </div><!-- tabs-1-->

    <div id="tabs-2">
    </div><!-- tabs-2 -->

  </div><!--tabs-->
</div><!--/labellist-->

<div class='blue' style='float:left;margin-left:10px;'>

<table class='propstable' border=0>
<caption>Label properties:</caption>
<tr><th>Property</th><th>Value</th><th>Presets</th></tr>
<tr><td class='tdt'><label for=name>Preset Name:</label></td><td><input size=8 value='<?php echo $name?>' name=name></td>

<td style='vertical-align:top;' rowspan=19 align=left>
<?php 
//ldata(rows,cols,lwidth,lheight, vpitch, hpitch, tmargin, bmargin, lmargin, rmargin)
if (isset($labelpapers))
foreach ($labelpapers as $lp) {
  //echo $lp['id'];
  echo "\n<a href='javascript:ldata({$lp['rows']}, {$lp['cols']}, ".  
       "{$lp['lwidth']},{$lp['lheight']}, {$lp['vpitch']}, {$lp['hpitch']}, ".  
       "{$lp['tmargin']}, {$lp['bmargin']}, {$lp['lmargin']}, ".
       "{$lp['rmargin']},".
       "\"{$lp['name']}\",".
       "{$lp['border']},".
       "{$lp['padding']},".
       "{$lp['fontsize']},".
       "{$lp['headerfontsize']},".
       "{$lp['barcodesize']},".
       "{$lp['idfontsize']},".
       "{$lp['wantbarcode']},".
       "{$lp['wantheadertext']},".
       "{$lp['wantheaderimage']},".
       "\"{$lp['headertext']}\",".
       "\"{$lp['image']}\",".
       "\"{$lp['imageheight']}\",".
       "\"{$lp['imagewidth']}\",".
       "\"{$lp['papersize']}\",".
       "\"{$lp['qrtext']}\",".
       "\"{$lp['wantnotext']}\",".
       "\"{$lp['wantraligntext']}\"".
       ")'>{$lp['name']}</a>"; 

  echo " <a href='javascript:delconfirm(\"{$lp['id']}\",".
       "\"$scriptname?action=$action&amp;delpaperid={$lp['id']}\");'><img src='images/delete.png'></a><br>\n";
}

//echo "<a href='javascript:ldata(6, 2, 96,42.3, 42.3, 98.5, 21.5, 7.7, 2.7, 7.7)'>Avery 6106</a>"; 
echo "</td></tr>\n";

echo "<tr><td class='tdt'>".t("Paper Size").":</td>\n<td>";

//read paper names
$papernames=file("php/papernames.txt");
echo "<select id='papersize' name=papersize>\n";
foreach ($papernames as $papername) {
  $papername=trim($papername);
  if (isset($_POST['papersize']) && $_POST['papersize']=="$papername") $s=" SELECTED "; 
  else $s="";
  if ($s=="" && $papername=="A4") $s="SELECTED";
  echo "<option $s id='pn_$papername' value='$papername'>$papername</option>\n";
}
echo "\n</select>\n</td></tr>";

echo "<tr><td class='tdt'>".t("Rows").":</td><td>";
echo "<select name=rows>\n";
for ($i=1;$i<40;$i++) {
  if (isset($_POST['rows']) && $_POST['rows']=="$i") $s=" SELECTED "; 
  elseif (!isset($_POST['rows']) && $i=="$rows") $s=" SELECTED "; 
  else $s="";
  echo "\n<option $s value=$i>$i</option>";
}
echo "</select>\n</td></tr>";

echo "<tr><td class='tdt'>".t('Columns').":</td><td>";
echo "<select name=cols>\n";
for ($i=1;$i<10;$i++) {
  if (isset($_POST['cols']) && $_POST['cols']=="$i") $s=" SELECTED "; 
  elseif (!isset($_POST['cols']) && $i=="$cols") $s=" SELECTED "; 
  else $s="";
  echo "\n<option $s value=$i>$i</option>";
}
echo "</select>\n</td></tr>\n";

?>
<tr><td class='tdt'><label for=lwidth><?php te("Width");?>:</label></td><td><input size=4 value='<?php echo $lwidth?>' name=lwidth>mm</td></tr>
<tr><td class='tdt'><label for=lheight><?php te("Height");?>:</label></td><td><input size=4 value='<?php echo $lheight?>' name=lheight>mm</td></tr>
<tr><td class='tdt'><label for=vpitch><?php te("Vert. Pitch");?>:</label></td><td><input size=4 value='<?php echo $vpitch?>' name=vpitch>mm</td></tr>
<tr><td class='tdt'><label for=hpitch><?php te("Horz. Pitch");?>:</label></td><td><input size=4 value='<?php echo $hpitch?>' name=hpitch>mm</td></tr>
<tr><td class='tdt'><label for=tmargin><?php te("Top Margin");?>:</label></td><td><input size=4 value='<?php echo $tmargin?>' name=tmargin>mm</td></tr>
<tr><td class='tdt'><label for=bmargin><?php te("Bottom Margin");?>:</label></td><td><input size=4 value='<?php echo $bmargin?>' name=bmargin>mm</td></tr>
<tr><td class='tdt'><label for=lmargin><?php te("Left Margin");?>:</label></td><td><input size=4 value='<?php echo $lmargin?>' name=lmargin>mm</td></tr>
<tr><td class='tdt'><label for=rmargin><?php te("Right Margin");?>:</label></td><td><input size=4 value='<?php echo $rmargin?>' name=rmargin>mm</td></tr>


<tr><td class=tdt><label for=border><?php te("Border Color (0-255)");?>:</label></td><td  title='0:black, 255:white' ><input size=4 value='<?php echo $border?>' name=border></td></tr>
<tr><td class='tdt'><label for=padding><?php te("Text Padding");?>:</label></td><td><input size=4 value='<?php echo $padding?>' name=padding>mm</td></tr>

<tr><td class='tdt'><label for=fontsize><?php te("FontSize");?>:</label></td><td><input size=4 value='<?php echo $fontsize?>' name=fontsize>pt <small>(1pt=0.352<span style='text-decoration:overline'>7</span> mm)</small></td></tr>
<tr><td class='tdt'><label for=idfontsize><?php te("ID FontSize");?>:</label></td><td><input size=4 value='<?php echo $idfontsize?>' name='idfontsize'>pt</td></tr>


<tr><td class='tdt'><label for=headerfontsize><?php te("Header FontSize");?>:</label></td><td><input size=4 value='<?php echo $headerfontsize?>' name='headerfontsize'>mm</td>
<tr><td class='tdt'><label for=barcodesize><?php te("Barcode Size");?>:</label></td><td><input size=4 value='<?php echo $barcodesize?>' name='barcodesize'>mm</td>

<tr><td class='tdt'><label for=image><?php te("Header Image");?>:</label></td><td><input size=9 id='iimage' style='width:12em' value='<?php echo $image?>' name='image'>
   <img id='theimage' width=25 height=25 src='<?php echo $image; ?>'>
   </td>
<tr><td class='tdt'><label for=imagewidth><?php te("Image Size (WxH)");?>:</label></td><td>
    <input size=2 value='<?php echo $imagewidth?>' name='imagewidth'> X <input size=2 value='<?php echo $imageheight?>' name='imageheight'>mm</td>

<td style='text-align:center' rowspan=8 valign=top>
<input id='savepreset' value='Save as new Preset' name='savepreset' type=submit><br><br>
<img width=180 src='images/labelinfo.jpg'></td></tr>

<tr><td class='tdt'><label for=headertext><?php te("Header");?><br><small>_NL_ = newline</small>:</label></td><td><textarea wrap=soft rows=2 name='headertext' cols=20><?php echo $headertext?></textarea></td></tr>


<tr><td class='tdt'><label for=wantbarcode><?php te("QR Barcode");?>:</label></td>
     <td><input id='wantbarcode' type=checkbox <?php if($wantbarcode) echo "CHECKED"; ?> name=wantbarcode>
	 <input title='<?php te("Text to prepend in QR barcode ID. <br>e.g. http://www.example.com/itdb/ ?action=edititem&id=")?>' size=8 style='width:140px' value='<?php echo $qrtext?>' name=qrtext></td>
	</td></tr>
<tr><td class='tdt'><label for=wantheadertext><?php te("Header Text");?>:</label></td><td><input id='wantheadertext' type=checkbox <?php if($wantheadertext) echo "CHECKED"; ?> name=wantheadertext></td></tr>
<tr><td class='tdt'><label for=wantheaderimage><?php te("Header Image");?>:</label></td><td><input id='wantheaderimage' type=checkbox <?php if($wantheaderimage) echo "CHECKED"; ?> name=wantheaderimage></td></tr>

<tr><td class='tdt'><label for=wantnotext><?php te("No Text");?>:</label></td><td><input title='<?php te("Just print the barcode, no text")?>' id='wantnotext' type=checkbox <?php if($wantnotext) echo "CHECKED"; ?> name=wantnotext></td></tr>
<tr><td class='tdt'><label for=wantraligntext><?php te("Text to the right of barcode");?>:</label></td><td><input id='wantraligntext' type=checkbox <?php if($wantraligntext) echo "CHECKED"; ?> name=wantraligntext></td></tr>


<tr><td class='tdt'><label for=labelskip><?php te("Skip");?>:</label></td><td title='<?php te("use when the top labels have already been printed");?>' ><input size=4 value='<?php echo $labelskip?>' name=labelskip> <?php te("labels");?></td></tr>
</table>

</div>
</form>

</div><!-- container -->


</body>
</html>
