<?php 

if (file_exists('init.php'))
  require_once("init.php");
else
  require_once("../init.php");

//print_r($_POST);

//form submit
if (isset($_POST['eventid'])) {
  $eventid=$_POST['eventid'];

  foreach($_POST as $k => $v) { $$k = $v;}

  if ($eventid=="new") {
    $sql="INSERT into contractevents ".
         " (contract_id,siblingid , start_date , enddate, description) ".
         " VALUES ('$contractid','$ev_siblingid','".ymd2sec($ev_start_date)."','".ymd2sec($ev_enddate)."','$ev_description') ";
    db_exec($dbh,$sql,0,0,$lastid);
    //echo "Added $lastid, $ev_start_date";
  }
  elseif(is_numeric($eventid)) {
    $sql="UPDATE contractevents ".
         " SET siblingid='$ev_siblingid', start_date='".ymd2sec($ev_start_date)."',enddate='".ymd2sec($ev_enddate)."',description='$ev_description' ".
	 " WHERE id='$eventid'";
    db_exec($dbh,$sql,0,0,$lastid);
    //echo "UPDATED $eventid";
  }
}//isset id
elseif (isset($_POST['deleventid'])) {

  $sql="DELETE FROM contractevents WHERE id='{$_POST['deleventid']}'";
  db_exec($dbh,$sql);
  //echo "DELETED ".$_POST['deleventid'];
}
?>
    <!-- print contract events table -->
    <table width='100%' class='tbl2 brdr sortable'  id='eventslisttbl'>
      <thead>
	<tr><th style='width:40px;'><?php te("Edit");?></th><th>ID</th><th><?php te("Related");?></th>
            <th><?php te("Start");?></th><th><?php te("End");?></th><th><?php te("Description");?></th></tr>
      </thead>
      <tbody>
      <?php  
      if (!isset($id)) $id=$contractid;
      $sql="SELECT * from contractevents WHERE contract_id='$id' order by start_date,id DESC";
      $sth=db_execute($dbh,$sql); 
      while ($ir=$sth->fetch(PDO::FETCH_ASSOC)) {
	$rowid=$ir['id'];
	echo "\n<tr>";
	echo "<td><img src='images/delete.png'  onClick=\"javascript:$('#ev_deldialog').data('rowid',$rowid).dialog({ position: {my:'left',at:'right',of:event,offset:'20 60'} }).dialog('open')\">";
	echo "<img src='images/edit.png'  onClick=\"javascript:$('#ev_dialog').data('rowid',$rowid).dialog({ position: {my:'left',at:'right',of:event,offset:'20 60'} }).dialog('open')\"></td> ";
	echo 
	 "<td id='eventid_$rowid'>".$ir['id']."</td>".
	 "<td id='ev_siblingid_$rowid'>".$ir['siblingid']."</td>".
	 "<td id='ev_start_date_$rowid'>".date($dateparam,$ir['start_date'])."</td>".
	 "<td id='ev_enddate_$rowid'>".date($dateparam,$ir['enddate'])."</td>".
	 "<td id='ev_description_$rowid'>".$ir['description']."</td></tr>\n";
      }
      ?>
      </tbody>
    </table>
