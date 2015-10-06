<SCRIPT LANGUAGE="JavaScript"> 

  function confirm_filled($row)
  {
	  var filled = 0;
	  $row.find('input,select').each(function() {
		  if (jQuery(this).val()) filled++;
	  });
	  if (filled) return confirm('Do you really want to remove this row?');
	  return true;
  };

 $(document).ready(function() {

    //delete table row on image click
    $('.delrow').click(function(){
        var answer = confirm("Are you sure you want to delete this row ?")
        if (answer) 
	  $(this).parent().parent().remove();
    });

    $("#caddrow").click(function($e) {
	var row = $('#contactstable tr:last').clone(true);
        $e.preventDefault();
	row.find("input:text").val("");
	row.find("img").css("display","inline");
	row.insertAfter('#contactstable tr:last');
    });
    $("#uaddrow").click(function($e) {
	var row = $('#urlstable tr:last').clone(true);
        $e.preventDefault();
	row.find("input:text").val("");
	row.find("img").css("display","inline");
	row.insertAfter('#urlstable tr:last');
    });
  });

</SCRIPT>
<?php 
if (!isset($initok)) {echo "do not run this script directly";exit;}

/* Cory Funk 2015 , cafunk@fhsu.edu */
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

//delete voucher
if (isset($_GET['delid'])) { //if we came from a post (save) the update voucher 
  $delid=$_GET['delid'];
  

  //delete entry
  $sql="DELETE from vouchers where id=".$_GET['delid'];
  $sth=db_exec($dbh,$sql);

  echo "<script>document.location='$scriptname?action=listvouchers'</script>";
  echo "<a href='$scriptname?action=listvouchers'>Go here</a></body></html>"; 
  exit;

}


if (isset($_POST['id'])) { //if we came from a post (save) then update voucher 
  $id=$_POST['id'];

  if ($_POST['id']=="new")  {//if we came from a post (save) then add voucher 
    $sql="INSERT INTO vouchers (vouchernum, voucherstartdate, vouchermins, voucheruser, voucherassigner, vouchernotes, voucherroll, voucherrollbits, voucherticketbits, voucherchksumbits) VALUES ('$vouchernum', '$voucherstartdate', '$vouchermins', '$voucheruser', '$voucherassigner', '$vouchernotes', '$voucherroll', '$voucherrollbits', '$voucherticketbits', '$voucherchksumbits')";
		  
    db_exec($dbh,$sql,0,0,$lastid);
    $lastid=$dbh->lastInsertId();
    print "<br><b>Added Voucher <a href='$scriptname?action=$action&amp;id=$lastid'>$lastid</a></b><br>";
    echo "<script>window.location='$scriptname?action=$action&id=$lastid'</script> "; //go to the new item
    $id=$lastid;
  }
  else {
    $sql="UPDATE vouchers SET vouchernum='$vouchernum', voucherstartdate='$voucherstartdate', vouchermins='$vouchermins', voucheruser='$voucheruser', voucherassigner='$voucherassigner', vouchernotes='$vouchernotes', voucherroll='$voucherroll', voucherrollbits='$voucherrollbits', voucherticketbits='$voucherticketbits', voucherchksumbits='$voucherchksumbits' WHERE id=$id $where";
    db_exec($dbh,$sql);
  }

}//save pressed

///////////////////////////////// display data now
$sql="SELECT * FROM vouchers WHERE id='$id'";
$sth=db_execute($dbh,$sql);
$r=$sth->fetch(PDO::FETCH_ASSOC);

$vouchernum=$r['vouchernum'];$voucherstartdate=$r['voucherstartdate'];$vouchermins=$r['vouchermins'];$voucheruser=$r['voucheruser'];$voucherassigner=$r['voucherassigner'];$vouchernotes=$r['vouchernotes'];$voucherroll=$r['voucherroll'];$voucherrollbits=$r['voucherrollbits'];$voucherticketbits=$r['voucherticketbits'];$voucherchksumbits=$r['voucherchksumbits'];

echo "\n<form method=post  action='$scriptname?action=$action&amp;id=$id' enctype='multipart/form-data'  name='addfrm'>\n";

// Get voucher information
$sql="SELECT * FROM vouchers WHERE id = '' OR id != '' ";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $vouchers[$r['id']]=$r;
$sth->closeCursor();

if ($id=="new")
  echo "\n<h1>".t("Add voucher")."</h1>\n";
else
  echo "\n<h1>".t("Edit Voucher")."</h1>\n";

?>
<!-- Voucher Properties -->
	<table border='0' class=tbl1 >
	<tr>

<!-- Voucher -->
		<td class='tdtop'>
			<table border='0' class=tbl2>
				<tr><td colspan=2><h3><?php te("Voucher Information");?></h3></td></tr>
				<tr>
					<td class='tdt'><?php te("Voucher");?>:</td>
					<td title='<?php te("Voucher number from captive portal system.");?>'><input type='text' name='vouchernum' value='<?php echo $vouchernum ?>'></td>
				</tr>
<!-- end, Voucher -->

<!-- Voucher Time Limit in Minutes  -->
	<tr>
		<td class='tdt'><?php te("Time Limit");?>:</td>
		<td title='<?php te("Amount of time this voucher is good for.");?>'><select style='width:16em' id='vouchermins' name='vouchermins' />
        		<option value='<?php echo $vouchermins?>'><?php
				switch ($vouchermins) {
				  case "1440": 
					echo "1 Day";
					break;
				  case "4320": 
					echo "3 Days";
					break;
				  case "10080": 
					echo "1 Week";
					break;
				  case "44640": 
					echo "1 Month";
					break;
				  case "131040": 
					echo "3 Months";
					break;
				  case "393120": 
					echo "9 Months";
					break;
				  case "525600": 
					echo "1 Year";
					break;
				}?></option>
                <option value='1440'>1 Day</option>
                <option value='4320'>3 Day</option>
                <option value='10080'>1 Week</option>
                <option value='44640'>1 Month</option>
                <option value='131040'>3 Months</option>
                <option value='393120'>9 Months</option>
                <option value='525600'>1 Year</option>
			</select>
		</td>
	</tr>
<!-- end, Voucher Time Limit in Minutes -->

<!-- Voucher Roll Number -->
				<tr>
					<td class='tdt'><?php te("Voucher Roll Number");?>:</td>
					<td title='<?php te("Voucher roll number from captive portal system.");?>'><input type='text' name='voucherroll' value='<?php echo $voucherroll ?>'></td>
				</tr>
<!-- end, Voucher Roll Number -->

<!-- Voucher Roll Bits -->
				<tr>
					<td class='tdt'><?php te("Voucher Roll Bits");?>:</td>
					<td title='<?php te("Voucher roll bits from captive portal system.");?>'><input type='text' name='voucherrollbits' value='<?php echo $voucherrollbits ?>'></td>
				</tr>
<!-- end, Voucher Roll Bits -->

<!-- Voucher Ticket Bits -->
				<tr>
					<td class='tdt'><?php te("Voucher Ticket Bits");?>:</td>
					<td title='<?php te("Voucher ticket bits from captive portal system.");?>'><input type='text' name='voucherticketbits' value='<?php echo $voucherticketbits ?>'></td>
				</tr>
<!-- end, Voucher Ticket Bits -->

<!-- Voucher Checksum Bits -->
				<tr>
					<td class='tdt'><?php te("Voucher Checksum Bits");?>:</td>
					<td title='<?php te("Voucher checksum bits from captive portal system.");?>'><input type='text' name='voucherchksumbits' value='<?php echo $voucherchksumbits ?>'></td>

<!-- Blank Space Between Columns -->
        <td width="100px"></td>
<!-- end, Blank Space Between Columns -->

	</tr>    
<!-- end, Voucher Checksum Bits -->
			</table>
		</td>
<!-- end, Voucher Properties -->

<!-- Voucher Information -->
		<td class='tdtop'>
			<table border='0' class=tbl2>
				<tr>
                	<td colspan=2 ><h3><?php te("User Information");?></h3></td>
				</tr>

<!-- User Information -->
	<tr>
		<td class='tdt'><?php te("User");?>:</td>
		<td><input title='<?php te("Who is using this voucher?");?>' style='width:20em' id='voucheruser' name='voucheruser' value='<?php echo $voucheruser ?>' /></td>
	</tr>
<!-- end, User Information -->

<!-- Voucher Start Date -->
	<tr>
		<td class='tdt'><?php te("Valid From");?>:</td>
		<td><input title='<?php te("When was this voucher first used?");?>' style='width:20em' id='voucherstartdate' name='voucherstartdate' value='<?php echo $voucherstartdate ?>' /></td>
	</tr>
<!-- end, Voucher Start Date -->

<!-- Assigned By Name -->
	<tr>
		<td class='tdt'><?php te("Assigned by");?>:</td>
		<td><input title='<?php te("Who assigned this voucher?");?>' style='width:20em' id='voucherassigner' name='voucherassigner' value='<?php echo $voucherassigner ?>' /></td>
	</tr>
<!-- end, Assigned By Name -->

<!-- Notes -->
                <tr>
					<td class='tdt'><?php te("Notes");?>:</td><td><textarea style='width:30em;height:15em' wrap='soft' class=tarea1  name='vouchernotes'><?php echo $vouchernotes ?></textarea></td>
				</tr>
<!-- end, Notes -->

</table>
</td>
<!-- end, User Information -->
      <tr>
        <td><table border="0" class="tbl2">
          <tr>
            <td><button type="submit"><img src="images/save.png" alt="Save" />
              <?php te("Save");?>
            </button></td>
            <?php echo "\n<td><button type='button' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>"."<img title='Delete' src='images/delete.png' border=0>".t("Delete")."
		</button></td>\n</tr>\n";
		echo "\n</table>\n";
		echo "\n<input type=hidden name='action' value='$action'>";
		echo "\n<input type=hidden name='id' value='$id'>";
		?> </tr>
        </table></td>
      </tr>
    </table>
    </form>
</body>
</html>