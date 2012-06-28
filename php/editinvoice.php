<SCRIPT LANGUAGE="JavaScript"> 
$(document).ready(function() {

  $(document).ready(function() {
    $("#tabs").tabs();
    $("#tabs").show();
  });

  $('input#itemsfilter').quicksearch('table#itemslisttbl tbody tr');
  $('input#softfilter').quicksearch('table#softlisttbl tbody tr');
  $('input#contrfilter').quicksearch('table#contrlisttbl tbody tr');


});
</script>

<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Spiros Ioannou 2009-2010 , sivann _at_ gmail.com */

//delete invoice
if (isset($_GET['delid'])) { //if we came from a post (save) the update invoice 
  $delid=$_GET['delid'];
  
  //first handle file associations

  //get a list of files associated with us
  $f=invid2files($delid,$dbh);
  for ($fids=array(),$c=0;$c<count($f);$c++) {
    array_push($fids,$f[$c]['id']);
  }

  //remove file links
  $sql="DELETE from invoice2file where invoiceid=$delid";
  $sth=db_exec($dbh,$sql);

  //for each file: check if others link to it, and if not remove it:
  for ($c=0;$c<count($fids);$c++) {
    $nlinks=countfileidlinks($fids[$c],$dbh);
    if ($nlinks==0) delfile($fids[$c],$dbh);
  }

  //delete entry
  $sql="DELETE from invoices where id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  $sql="DELETE from item2inv where invid=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  $sql="UPDATE software SET invoiceid='' where invoiceid=$delid";
  db_exec($dbh,$sql);

  echo "<script>document.location='$scriptname?action=listinvoices'</script>";
  echo "<a href='$scriptname?action=listinvoices'>Go here</a></body></html>"; 
  exit;

}

//remove association and delete file
if (isset($_GET['delfid'])) {

  //remove file link
  $sql="DELETE from invoice2file where fileid=".$_GET['delfid'];
  $sth=db_exec($dbh,$sql);

  //check if others point to this file
  $nlinks=countfileidlinks($_GET['delfid'],$dbh);
  if ($nlinks==0) delfile($_GET['delfid'],$dbh);
  //echo "$nlinks DELETED ".$_GET['delfid'];
  echo "<script>window.location='$scriptname?action=$action&id=$id'</script> ";
  echo "<br><a href='$scriptname?action=$action&id=$id'>Go here</a></body></html>"; 
  exit;
}

if (isset($_POST['id'])) { //if we came from a post (save) then update invoice 
  $id=$_POST['id'];

  $description=$_POST['description'];

  //don't accept empty fields
  if ((empty($vendorid))|| (empty($buyerid))|| (!strlen($number)) ||  (!strlen($date)) ) {
    echo "\n<br><b>Some mandatory fields are missing.</b><br><a href='javascript:history.go(-1);'>Go back</a>\n</body></html>"; 
  exit;
  }

  $d=ymd2sec($date);

  if ($_POST['id']=="new")  {//if we came from a post (save) then add invoice 
    $sql="INSERT into invoices (vendorid,buyerid,number,description,date)".
         " VALUEs ('$vendorid','$buyerid','$number','$description','$d')";
    db_exec($dbh,$sql,0,0,$lastid);
    $lastid=$dbh->lastInsertId();
    print "\n<br><b>Added Invoice <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>\n";
    echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new invoice
    $id=$lastid;
  }
  else {
    $sql="UPDATE invoices SET vendorid='$vendorid', buyerid='$buyerid', ".
       " number='$number', description='$description', date='$d' WHERE id=$id";
    db_exec($dbh,$sql);
  }

  //SAVE ASSOCIATIONS

  //update item - invoice links 
  //remove old links for this object
  $sql="delete from item2inv where invid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($itlnk);$i++) {
    $sql="INSERT into item2inv (invid,itemid) values ($id,".$itlnk[$i].")";
    db_exec($dbh,$sql);
  }

  //update software - invoice links 
  //remove old links for this object
  $sql="delete from soft2inv where invid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($softlnk);$i++) {
    $sql="INSERT into soft2inv (invid,softid) values ($id,".$softlnk[$i].")";
    db_exec($dbh,$sql);
  }

  //update contract - invoice links 
  //remove old links for this object
  $sql="delete from contract2inv where invid=$id";
  db_exec($dbh,$sql);
  //add new links for each checked checkbox
  for ($i=0;$i<count($contrlnk);$i++) {
    $sql="INSERT into contract2inv (invid,contractid) values ($id,".$contrlnk[$i].")";
    db_exec($dbh,$sql);
  }



}//save pressed

/////////////////////////////
//// display data now


$sql="SELECT id,title,type FROM agents order by title";
$sth=db_execute($dbh,$sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $agents[$r['id']]=$r;

if (!isset($_REQUEST['id'])) {echo "ERROR:ID not defined";exit;}
$id=$_REQUEST['id'];

$sql="SELECT * FROM invoices WHERE id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);
if (($id !="new") && (count($r)<5)) {echo "ERROR: non-existent ID";exit;}

$number=$r['number'];$date=$r['date'];$vendorid=$r['vendorid'];$buyerid=$r['buyerid'];$description=$r['description'];

echo "\n<form id='mainform' method=post  action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";

if ($id=="new")
  echo "\n<h1>".t("Add Invoice")."</h1>\n";
else
  echo "\n<h1>".t("Edit Invoice")."</h1>\n";

?>

<!-- error errcontainer -->
<div class='errcontainer ui-state-error ui-corner-all' style='padding: 0 .7em;width:700px;margin-bottom:3px;'>
        <p><span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>
        <h4>There are errors in your form submission, please see below for details.</h4>
        <ol>
                <li><label for="vendorid" class="error"><?php te("Contract Title is missing");?></label></li>
                <li><label for="buyerid" class="error"><?php te("Contract number is missing");?></label></li>
                <li><label for="number" class="error"><?php te("Contract Type is missing");?></label></li>
                <li><label for="contractorid" class="error"><?php te("Contractor is missing");?></label></li>
                <li><label for="startdate" class="error"><?php te("Start Date of contract is missing");?></label></li>
                <li><label for="currentenddate" class="error"><?php te("Current End Date of contract is missing");?></label></li>
        </ol>
</div>

<div id="tabs">
  <ul>
  <li><a href="#tab1"><?php te("Invoice  Data");?></a></li>
  <li><a href="#tab2"><?php te("Item Associations");?></a></li>
  <li><a href="#tab3"><?php te("Software Associations");?></a></li>
  <li><a href="#tab4"><?php te("Contract Associations");?></a></li>
  <li><a href="#tab5"><?php te("Upload Files");?></a></li>
  </ul>


  <div id="tab1" class="tab_content">
  <table class=tbl1 border=0>
  <?php 

  $d=strlen($date)?date($dateparam,$date):"";
  //Associated files
  //
  $f=invid2files($id,$dbh);
  //create file links
  for ($lnk="",$c=0;$c<count($f);$c++) {
   $fname=$f[$c]['fname'];
   $ftitle=$f[$c]['title'];
   $fid=$f[$c]['id'];
   $ftype=$f[$c]['type'];
   $ftypestr=ftype2str($ftype,$dbh);
   $fdate=empty($f[$c]['date'])?"":date($dateparam,$f[$c]['date']);
   if (strlen($ftitle)) $t="<br>".t("Title").":$ftitle"; else $t="";
   $flnk.="<div class='fileslist' >".
         "<a title='Remove association. If file is orphaned (nothing links to it), it gets deleted.' ".
         " href='javascript:delconfirm2(\"[$fid] $fname\", \"$scriptname?action=$action&amp;id=$id&amp;delfid=$fid\");'>".
         "<img src='images/delete.png'></a> ".
         "<a target=_blank title='Edit File' href='$scriptname?action=editfile&amp;id=$fid'><img  src='images/edit.png'></a>".
         " <a target=_blank title='Download $fname' href='".$uploaddirwww.$fname."'><img src='images/down.png'></a>".
         "<br>".t("Type").":<b>$ftypestr</b>".
         "<br>".t("Date").":<b>$fdate</b>".
         "<br>".t("Title").":$ftitle\n".
         "</div>\n ";

  }

  ?>

  <tr>
  <td class="tdtop">
      <table class="tbl2" width='100%'>
      <tr><td colspan=2><h3><?php te("Invoice Properties");?></h3></td></tr>
      <tr><td class="tdt"><?php te("ID");?>:</td> <td><input  class='input2' type=text name='id' value='<?php echo $id?>' readonly size=3></td></tr>
      <tr><td class="tdt">
  <?php   if (is_numeric($vendorid))
    echo "<a title='edit vendor (agent)' href='$scriptname?action=editagent&amp;id=$vendorid'><img src='images/edit.png'></a> "; ?>
      <?php te("Vendor");?>*:</td> <td>
	   <select class='mandatory' validate='required:true' name='vendorid'>
	   <option value=''><?php te("Select");?></option>
	  <?php 
	    foreach ($agents as $a) {
	      if (!($a['type']&4)) continue;
	      $dbid=$a['id']; 
	      $atype=$a['title']; $s="";
	      if (isset($vendorid) && $vendorid==$a['id']) $s=" SELECTED ";
	      echo "<option $s value='$dbid' title='$dbid'>$atype</option>\n";
	    }
	    echo "</select>\n";
	  ?>

      </td></tr>
      <tr><td class="tdt">
  <?php   if (is_numeric($buyerid))
    echo "<a title='edit buyer (agent)' href='$scriptname?action=editagent&amp;id=$buyerid'><img src='images/edit.png'></a> "; ?>
      <?php te("Buyer");?>*:</td> <td>
	   <select class='mandatory' validate='required:true' name='buyerid'>
	   <option value=''><?php te("Select");?></option>
	  <?php 
	    foreach ($agents as $a) {
	      if (!($a['type']&1)) continue;
	      $dbid=$a['id']; 
	      $atype=$a['title']; $s="";
	      if (isset($buyerid) && $buyerid==$a['id']) $s=" SELECTED ";
	      echo "<option $s value='$dbid' title='$dbid'>$atype</option>\n";
	    }
	    echo "</select>\n";
	  ?>
      
      </td></tr>
      <tr><td class="tdt"><?php te("Order Num");?>*:</td> <td><input  class='input2 mandatory' validate='required:true' size=20 type=text name='number' value="<?php echo $number?>"></td></tr>
      <tr>
	  <td class="tdt"><?php te("Date");?>*:</td> <td><input  class='dateinp mandatory' validate='required:true' size=10 id='date' title='<?php echo $datetitle?>' type=text name='date' value='<?php echo $d?>'>
	  </td>
      </tr>

      <tr><td class="tdt"><?php te("Description");?>:</td> <td colspan=2> <textarea name='description' class='tarea2' wrap='soft'><?php echo $description?></textarea> </td></tr>

      </table>
  </td>

  <td style='vertical-align:top;'><h3><?php te("Associations Overview");?></h3>
    <div style='text-align:center;'>
      <span class="tita" onclick='showid("items");'><?php te("Items");?></span> |
      <span class="tita" onclick='showid("software");'><?php te("Software");?></span> |
      <span class="tita" onclick='showid("contracts");'><?php te("Contracts");?></span>
    </div>

    <div class="scrltblcontainer4" >

    <div  id='items' class='relatedlist'>ITEMS</div>
    <?php 
    if (is_numeric($id)) {
      $sql="SELECT items.id, agents.title || ' ' || items.model || ' [' || itemtypes.typedesc || ', ID:' || items.id || ']' as txt ".
           "FROM agents,items,itemtypes, item2inv WHERE ".
           " agents.id=items.manufacturerid AND items.itemtypeid=itemtypes.id AND ".
           " item2inv.itemid=items.id AND item2inv.invid='$id'";
      $sthi=db_execute($dbh,$sql);
      $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
      $nitems=count($ri);
      $institems="";
      for ($i=0;$i<$nitems;$i++) {
        $x=($i+1).": ".$ri[$i]['txt'];
        if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
        $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
                    "<a href='$scriptname?action=edititem&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
      }
      echo $institems;
    }
    ?>

    <div  id='software' class='relatedlist'><?php te("SOFTWARE");?></div>
    <?php 
    if (is_numeric($id)) {
      //print a table row

      $sql="SELECT software.id, agents.title || ' ' || software.stitle ||' '|| software.sversion || ' [ID:' || software.id || ']' as txt ".
           "FROM agents,software,soft2inv WHERE ".
           " agents.id=software.manufacturerid AND soft2inv.softid=software.id AND soft2inv.invid='$id'";
      $sthi=db_execute($dbh,$sql);
      $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
      $nitems=count($ri);
      $institems="";
      for ($i=0;$i<$nitems;$i++) {
        $x=($i+1).": ".$ri[$i]['txt'];
        if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
        $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
                    "<a href='$scriptname?action=editsoftware&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
      }
      echo $institems;
    }
    ?>

   <div id='contracts' class='relatedlist'><?php te("CONTRACTS");?></div>
    <?php 
    if (is_numeric($id)) {
      //print a table row
      $sql="SELECT contracts.id, type,title,number,startdate,currentenddate FROM contracts,contract2inv ".
           " WHERE contract2inv.contractid=contracts.id AND contract2inv.invid=$id";
      $sthi=db_execute($dbh,$sql);
      $ri=$sthi->fetchAll(PDO::FETCH_ASSOC);
      $nitems=count($ri);
      $institems="";
      for ($i=0;$i<$nitems;$i++) {
        $d=date($dateparam,$ri[$i]['startdate'])."-".date($dateparam,$ri[$i]['currentenddate']);
        $x=($i+1).":  (".$ri[$i]['title']." ".$ri[$i]['number'].") - $d [ID:{$ri[$i]['id']}]";
        if ($i%2) $bcolor="#D9E3F6"; else $bcolor="#ffffff";
        $institems.="\t<div style='margin:0;padding:0;background-color:$bcolor'>".
                    "<a href='$scriptname?action=editcontract&amp;id={$ri[$i]['id']}'>$x</a></div>\n";
      }
      echo $institems;
    }
    ?>

    </div><!-- scrltblcontainer -->
  </td>
  </tr>

  <tr><td colspan=2 class="tdtop">

    <table class="tbl2" width='100%'>
    <tr><td ><h3><?php te("Associated Files ");?><img onclick='window.location.href=window.location.href;' title='Refresh' src='images/refresh.png'></h3> </td></tr>
    <tr> 
    <td>&nbsp; <!-- file links -->
	<?php echo $flnk?>
    </td>
    </tr>
    </table>
  </td>
  </tr>
</table>

</div> <!-- tab1 -->

<div id="tab2" class="tab_content">
  <h2> <input style='color:#909090' id="itemsfilter" name="itemsfilter" class='filter' 
             value='Filter' onclick='this.style.color="#000"; this.value=""' size="20">
      <span style='font-weight:normal;' class='nres'></span>
  </h2>
  <?php 
  //////////////////////////////////////////////
  //connect to Items
  $sql=" SELECT COALESCE((SELECT itemid FROM item2inv WHERE invid='$id' AND itemid=items.id ),0) islinked , ".
       " items.id,status,manufacturerid,model,itemtypeid,typedesc,sn || ' '||sn2 ||' ' || sn3 as sn,dnsname,users.username ,label ".
       " FROM items,itemtypes,users  WHERE items.itemtypeid=itemtypes.id AND users.id=userid ".
       " order by islinked desc,itemtypeid,items.id desc, manufacturerid,model, dnsname ";
  $sth=db_execute($dbh,$sql);
  ?>

  <div style='margin-left:auto;margin-right:auto;' class='scrltblcontainer2'>
     <table width='100%' class='brdr sortable'  id='itemslisttbl'>
       <thead>
          <tr><th width='5%'><?php te("Associate");?></th><th style="width:65px"><?php te("ID");?></th>
              <th><?php te("Type");?></th><th><?php te("Manufacturer");?></th><th><?php te("Model");?></th>
	      <th><?php te("Label");?></th><th><?php te("DNS");?></th><th><?php te("User");?></th><th><?php te("S/N");?></th>
          </tr>
        </thead>
        <tbody>
  <?php 

  while ($ir=$sth->fetch(PDO::FETCH_ASSOC)) {
    if ($ir['islinked']) {
      $cls="class='bld'";
    }
    else
      $cls="";

    $x=attrofstatus((int)$ir['status'],$dbh);
    $attr=$x[0];
    $statustxt=$x[1];

    echo "<tr><td><input name='itlnk[]' value='".$ir['id']."' ";
    if ($ir['islinked']) echo " checked ";
    echo  " type='checkbox'></td>".
     "<td nowrap $cls><span $attr>&nbsp;</span><a title='Edit item {$ir['id']} in a new window' ".
     "target=_blank href='$scriptname?action=edititem&amp;id=".$ir['id']."'><div class='editid'>";
    echo $ir['id'];
    echo "</div></a></td>".
     "<td $cls>".$ir['typedesc']."</td>".
     "<td $cls>".$agents[$ir['manufacturerid']]['title']. "&nbsp;</td>".
     "<td $cls>".$ir['model'].  "&nbsp;</td>".
     "<td $cls>".$ir['label']."&nbsp;</td>".
     "<td $cls>".$ir['dnsname']."&nbsp;</td>".
     "<td $cls>".$ir['username']."&nbsp;</td>".
     "<td $cls>".$ir['sn']."&nbsp;</td></tr>\n";
  }
  ?>
  </tbody>
  </table>
  </div>


</div><!-- tab2 -->

<div id="tab3" class="tab_content">
  <h2><input style='color:#909090' id="softfilter" name="softfilter" class='filter' 
             value='Filter' onclick='this.style.color="#000"; this.value=""' size="20">
      <span style='font-weight:normal;' class='nres'></span>
  </h2>

  <?php 
  //////////////////////////////////////////////
  //connect to Items
  $sql=" SELECT COALESCE((SELECT softid from soft2inv WHERE invid='$id' AND softid=software.id ),0) islinked , ".
       " software.id, stitle || ' ' || sversion as titver, agents.title AS agtitle  ".
       " FROM software,agents WHERE agents.id=software.manufacturerid ".
       " ORDER BY islinked desc,manufacturerid,stitle,sversion ";
  $sth=db_execute($dbh,$sql);
  ?>
  <div style='margin-left:auto;margin-right:auto;' class='scrltblcontainer2'>
     <table width='100%' class='tbl2 brdr sortable'  id='softlisttbl'>
       <thead>
          <tr><th width='5%'><?php te("Associated");?></th><th><?php te("ID");?></th><th><?php te("Manufacturer");?></th><th><?php te("Title/Ver.");?></th>
          </tr>
        </thead>
        <tbody>
  <?php 

  while ($ir=$sth->fetch(PDO::FETCH_ASSOC)) {
    if ($ir['islinked']) {
      $cls="class='bld'";
    }
    else
      $cls="";

    echo "<tr><td><input name='softlnk[]' value='".$ir['id']."' ";
    if ($ir['islinked']) echo " checked ";
    echo  " type='checkbox' /></td>".
     "<td $cls><a title='Edit software {$ir['id']} in a new window' ".
     "target=_blank href='$scriptname?action=editsoftware&amp;id=".$ir['id']."'>";
    echo $ir['id'];
    echo "</a></td>".
     "<td $cls>".$ir['agtitle'].  "&nbsp;</td>".
     "<td $cls>".$ir['titver']."&nbsp;</td></tr>\n";
  }
  ?>
  </tbody>
  </table>
  </div>




</div><!-- tab3 -->

<div id="tab4" class="tab_content">

  <h2><input style='color:#909090' id="contrfilter" name="contrfilter" class='filter' 
             value='Filter' onclick='this.style.color="#000"; this.value=""' size="20">
      <span style='font-weight:normal;' class='nres'></span>
  </h2>
  <?php 
  //////////////////////////////////////////////
  //connect to Items
  $sql=" SELECT COALESCE((SELECT contractid FROM contract2inv WHERE invid='$id' AND contractid=contracts.id ),0) islinked , ".
       " contracts.id, contracts.title AS ctitle, agents.title AS agtitle  ".
       " FROM contracts,agents WHERE agents.id=contracts.contractorid ".
       " ORDER BY islinked desc,contractorid,ctitle";
  $sth=db_execute($dbh,$sql);
  ?>
  <div style='margin-left:auto;margin-right:auto;' class='scrltblcontainer2'>
     <table width='100%' class='tbl2 brdr sortable'  id='contrlisttbl'>
       <thead>
          <tr><th width='5%'><?php te("Associated");?></th><th><?php te("ID");?></th><th><?php te("Contractor");?></th><th><?php te("Title");?></th>
          </tr>
        </thead>
        <tbody>
  <?php 

  while ($ir=$sth->fetch(PDO::FETCH_ASSOC)) {
    if ($ir['islinked']) {
      $cls="class='bld'";
    }
    else
      $cls="";

    echo "<tr><td><input name='contrlnk[]' value='".$ir['id']."' ";
    if ($ir['islinked']) echo " checked ";
    echo  " type='checkbox' /></td>".
     "<td $cls><a title='Edit Contract {$ir['id']} in a new window' ".
     "target=_blank href='$scriptname?action=editcontract&amp;id=".$ir['id']."'>";
    echo $ir['id'];
    echo "</a></td>".
     "<td $cls>".$ir['agtitle'].  "&nbsp;</td>".
     "<td $cls>".$ir['ctitle']."&nbsp;</td></tr>\n";
  }
  ?>
  </tbody>
  </table>
  </div>




</div><!-- tab4 -->

<div id="tab5" class="tab_content">
      <table class="tbl2" width='100%'>
      <tr><td colspan=2><h2><?php te("Upload a File");?></h2></td></tr>
      <tr><td colspan=2 style='text-align:center'><?php te("suggested resolution: 1024 pixels for A4 scans");?></td></tr>
      <!-- file upload -->
      <tr><td class="tdc">
      <iframe class="upload_frame" name="upload_frame" 
	    src="php/uploadframe.php?id=<?php echo $id?>&amp;type=invoice&amp;assoctable=invoice2file&amp;colname=invoiceid&amp;defdate=<?php echo urlencode($d)?>"  
	    frameborder="0" allowtransparency="true"></iframe>
      </td>
      </tr>
      </table>

</div><!-- tab5 -->


</div><!-- tab container -->

<table>
  <tr><td><button type="submit"><img src="images/save.png" alt="Save" > <?php te("Save Invoice");?></button></td>
  <?php 
  echo "\n<td><button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>".
       "<img title='delete' src='images/delete.png' border=0> Delete Invoice</button></td>\n</tr>\n";
  echo "\n</table>\n";
  echo "\n<input type=hidden name='action' value='$action'>";
  echo "\n<input type=hidden name='id' value='$id'>";
  ?>

  </form>

</body>
</html>
