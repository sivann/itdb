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

  $(document).ready(function() {
    $("#locationid").change(function() {
      var locationid=$(this).val();
      var locareaid=$('#locareaid').val();
      var dataString = 'locationid='+ locationid;
	  
      $.ajax ({
	  type: "POST",
	  url: "php/locarea_options_ajax.php",
	  data: dataString,
	  cache: false,
	  success: function(html) {
	    $("#locareaid").html(html);
	  }
      });
    });
  });

</SCRIPT>
<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}
/* Cory Funk 2015, cfunk@fhsu.edu */

//error_reporting(E_ALL);
//ini_set('display_errors', '1');

//delete voucher
if (isset($_GET['delid'])) { //Deletes the record in the current row 
	$delid=$_GET['delid'];
	$sql="DELETE from vouchers WHERE id=".$_GET['delid'];
	$sth=db_exec($dbh,$sql);
	echo "<script>document.location='$scriptname?action=listvouchers'</script>";
	echo "<a href='$scriptname?action=listvouchers'></a>"; 
	exit;
}

// Get voucher information
$sql="SELECT * FROM vouchers WHERE id = '' OR id != '' ";
$sth=$dbh->query($sql);
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) $vouchers[$r['id']]=$r;
$sth->closeCursor();

//export: export to excel (as html table readable by excel)
if (isset($_GET['export']) && $_GET['export']==1) {
  header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
  header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
  header("Cache-Control: Must-Revalidate");
  header('Content-Disposition: attachment; filename=itdb.xls');
  header('Connection: close');
  $export=1;
  $expand=1;//always export expanded view
}
else 
  $export=0;


if (!$export) 
  $perpage=25;
else 
  $perpage=100000;

if ($page=="all") {
  $perpage=100000;
}



if ($export)  {
  echo "<html>\n<head><meta http-equiv=\"Content-Type\"".
     " content=\"text/html; charset=UTF-8\" /></head>\n<body>\n";
}



// Display list
if ($export) 
  echo "\n<table border='1'>\n";
else {
  echo "<h1>vouchers <a title='Add new voucher' href='$scriptname?action=editvoucher&amp;id=new'>".
       "<img border=0 src='images/add.png'></a></h1>\n";
  echo "<form name='frm'>\n";
  echo "\n<table class='brdr'>\n";
}

if (!$export) {
  $get2=$_GET;
  unset($get2['orderby']);
  $url=http_build_query($get2);
}

if (!isset($orderby) && empty($orderby)) 
  $orderby="vouchers.id asc";
elseif (isset($orderby)) {

  if (stristr($orderby,"FROM")||stristr($orderby,"WHERE")) {
    $orderby="id";
  }
  if (strstr($orderby,"DESC"))
    $ob="+ASC";
  else
    $ob="+DESC";
}

echo "<thead>\n";
$thead= "\n<tr>".
	 "<th><a>Edit</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=vouchernum$ob'>Voucher</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=voucherstartdate$ob'>Issue Date</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=vouchermins$ob'>Valid Time Length<br />(Days)</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=voucheruser$ob'>User</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=voucherassigner$ob'>Assigned By</a></th>".
     "<th><a href='$fscriptname?$url&amp;orderby=vouchernotes$ob'>Notes</a></th>".
 	 "<th><button type='submit'><img border=0 src='images/search.png'></button></th>";

if ($export) {
 //clean links from excel export
  $thead = preg_replace('@<a[^>]*>([^<]+)</a>@si', '\\1 ', $thead); 
  $thead = preg_replace('@<img[^>]*>@si', ' ', $thead); 
}

echo $thead;

if ($expand) {
  echo "\n <th>Roll Number</th>";
  echo "\n <th>Roll Bits</th>";
  echo "\n <th>Ticket Bits</th>";
  echo "\n <th>Checksum Bits</th>";
}
else
echo "</tr>\n</thead>\n";
echo "\n<tbody>\n";
echo "\n<tr>";

//create pre-fill form box vars
$vouchernum=isset($_GET['vouchernum'])?($_GET['vouchernum']):"";
$voucherstartdate=isset($_GET['voucherstartdate'])?($_GET['voucherstartdate']):"";
$vouchermins=isset($_GET['vouchermins'])?($_GET['vouchermins']):"";
$voucheruser=isset($_GET['voucheruser'])?($_GET['voucheruser']):"";
$voucherassigner=isset($_GET['voucherassigner'])?($_GET['voucherassigner']):"";
$vouchernotes=isset($_GET['vouchernotes'])?$_GET['vouchernotes']:"";
$voucherroll=isset($_GET['voucherroll'])?$_GET['voucherroll']:"";
$voucherrollbits=isset($_GET['voucherrollbits'])?$_GET['voucherrollbits']:"";
$voucherticketbits=isset($_GET['voucherticketbits'])?$_GET['voucherticketbits']:"";
$voucherchksumbits=isset($_GET['voucherchksumbits'])?$_GET['voucherchksumbits']:"";
$page=isset($_GET['page'])?$_GET['page']:1;

// Display Search Boxes
if (!$export) {

  echo "<td title='Edit this record.'></td>
  		<td title='Randomly generated characters from captive portal program.'><input style='width:auto' type=text value='$vouchernum' name='vouchernum'></td>
		<td title='Date that the voucher was issued.'><input style='width:auto' type=text value='$voucherstartdate' name='voucherstartdate'></td>
  		<td title='How long the given voucher is good for.'><select style='width:15em' id='vouchermins' name='vouchermins'>
			<option value=''>Select</option>;
			<option value='1440'>1 Day</option>
			<option value='4320'>3 Days</option>
			<option value='10080'>1 Week</option>
			<option value='44640'>1 Month</option>
			<option value='131040'>3 Months</option>
			<option value='393120'>9 Months</option>
			<option value='525600'>1 Year</option>
			</select>
		</td>";
  echo "<td title='Who is using this voucher?'><input style='width:30em' type=text value='$voucheruser' name='voucheruser'></td>";
  echo "<td title='Who assigned this voucher to the user?'><input style='width:30em' type=text value='$voucherassigner' name='voucherassigner'></td>";
  echo "<td title='Notes'><input style='width:31em' type=text value='$vouchernotes' name='vouchernotes'></td>";

  if ($expand) {
    echo "<td></td>";
	echo "<td title='Voucher Roll Number provided by Captive Portal System.'><input style='width:auto' type=text value='$voucherroll' name='voucherroll'></td>";
	echo "<td title='Voucher Roll Bits provided by Captive Portal System.'><input style='width:auto' type=text value='$voucherrollbits' name='voucherrollbits'></td>";
	echo "<td title='Voucher Ticket Bits provided by Captive Portal System.'><input style='width:auto' type=text value='$voucherticketbits' name='voucherticketbits'></td>";
	echo "<td title='Voucher Checksum Bits provided by Captive Portal System.'><input style='width:auto' type=text value='$voucherchksumbits' name='voucherchksumbits'></td>";
}else{
    $url=http_build_query($_GET);
    echo "<td style='vertical-align:top;'>".
         "<a alt='More' title='Show More Columns' href='$fscriptname?$url&amp;expand=1'>".
         "<img src='images/more.png'></a></td>";
  }
  echo "</tr>\n\n";

}//if not export to excel: searchboxes

// Create WHERE clause
$where = '';
if (isset($vouchernum) && strlen($vouchernum)) $where.="AND vouchernum LIKE '%$vouchernum%' ";
if (isset($voucherstartdate) && strlen($voucherstartdate)) $where.="AND voucherstartdate LIKE '%$voucherstartdate%' ";
if (isset($vouchermins) && strlen($vouchermins)) $where.="AND (vouchermins = '$vouchermins') ";
if (isset($voucheruser) && strlen($voucheruser)) $where.="AND voucheruser LIKE '%$voucheruser%' ";
if (isset($voucherassigner) && strlen($voucherassigner)) $where.="AND voucherassigner LIKE '%$voucherassigner%' ";
if (isset($vouchernotes) && strlen($vouchernotes)) $where.="AND vouchernotes LIKE '%$vouchernotes%' ";
if (isset($voucherroll) && strlen($voucherroll)) $where.="AND voucherroll = '$voucherroll' ";
if (isset($voucherrollbits) && strlen($voucherrollbits)) $where.="AND voucherrollbits = '$voucherrollbits' ";
if (isset($voucherticketbits) && strlen($voucherticketbits)) $where.="AND voucherticketbits = '$voucherticketbits' ";
if (isset($voucherchksumbits) && strlen($voucherchksumbits)) $where.="AND voucherchksumbits = '$voucherchksumbits' ";

///////////////////////////////////////////////////////////							Pagination							///////////////////////////////////////////////////////////

//	How many records are in table
$sth=db_execute($dbh,"SELECT count(vouchers.id) as totalrows FROM vouchers WHERE id = '' OR id != '' $where");
$totalrows=$sth->fetchColumn();

//	Page Links
//	Get's the current page number
$get2=$_GET;
unset($get2['page']);
$url=http_build_query($get2);

//	Previous and Next Links
	$prev = $page - 1;
	$next = $page + 1;

//	Previous Page
	if ($page > 1){
	$prevlink .="<a href='$fscriptname?$url&amp;page=$prev'><img src='../images/previous-button.png' width='64' height='25' alt='previous' /></a> ";
	}else{
	$prevlink .="<img src='../images/previous-button.png' width='64' height='25' alt='previous' /> ";
	}

//	Numbers
	for ($plinks="",$pc=1;$pc<=ceil($totalrows/$perpage);$pc++){
		if ($pc==$page){
			$plinks.="<b><u><a href='$fscriptname?$url&amp;page=$pc'>[$pc]</a></u></b> ";
		}else{
			$plinks.="<a href='$fscriptname?$url&amp;page=$pc'>$pc</a> ";
		}
	}

//	Next Page
	if ($page < ceil($totalrows/$perpage)){
	$nextlink .="<a href='$fscriptname?$url&amp;page=$next'><img src='../images/next-button.png' width='64' height='25' alt='next' /></a> ";
	}else{
	$nextlink .=" <img src='../images/next-button.png' width='64' height='25' alt='next' />";
	}

//	Show All
	$alllink .="<a href='$fscriptname?$url&amp;page=all'><br /><img src='../images/view-all-button.gif' width='64' height='25' alt='show all' /></a> ";

///////////////////////////////////////////////////////////							end, Pagination							///////////////////////////////////////////////////////////

$t=time();
$sql="SELECT * FROM vouchers WHERE id = '' OR id != '' $where order by $orderby LIMIT $perpage OFFSET ".($perpage*($page-1));
$sth=db_execute($dbh,$sql);

// Display Results
$currow=0;
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
$currow++;

// Table Row
  if ($currow%2) $c="class='dark'";
  else $c="";

  echo "\n<tr $c>".
       "<td><a class='editiditm icon edit' title='Edit' href='$fscriptname?action=editvoucher&amp;id=".$r['id']."'><span>Edit</span></a></td>".
		"\n  <td>".$r['vouchernum']."</td>".
		"\n  <td>".$r['voucherstartdate']."</td>".
		"\n  <td>".(($r['vouchermins'] == "1440") ? "1 Day" : (($r['vouchermins'] == "4320") ? "3 Days" : (($r['vouchermins'] == "10080") ? "1 Week" : (($r['vouchermins'] == "44640") ? "1 Month" : (($r['vouchermins'] == "131040") ? "3 Months" : (($r['vouchermins'] == "393120") ? "9 Months" : (($r['vouchermins'] == "525600") ? "1 Year" : "")))))))."</td>".
		"\n  <td>".$r['voucheruser']."</td>".
		"\n  <td>".$r['voucherassigner']."</td>".
		"\n  <td>".$r['vouchernotes']."</td>";

if ($expand){ //display more columns
	echo	"<td><center><input type='image' src='images/delete.png' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>".
				"<input type=hidden name='action' value='$action'>".
				"<input type=hidden name='id' value='$id'></td>";
	echo	"\n  <td><center>".$r['voucherroll']."</center></td>";
	echo	"\n  <td><center>".$r['voucherrollbits']."</center></td>";
	echo	"\n  <td><center>".$r['voucherticketbits']."</center></td>";
	echo	"\n  <td><center>".$r['voucherchksumbits']."</center></td>";
}else{
	echo "<td><center><input type='image' src='images/delete.png' onclick='javascript:delconfirm2(\"{$r['id']}\",\"$scriptname?action=$action&amp;delid={$r['id']}\");'>".
    "<input type=hidden name='action' value='$action'>".
    "<input type=hidden name='id' value='$id'>";
	}
  echo "</td></tr>";
}

$sth->closeCursor();

if ($export) {
  echo "</tbody>\n</table>\n";
  exit;
}
else {
    $cs=12;

?>
  <tr><td colspan='<?php echo $cs?>' class=tdc><button type=submit><img src='images/search.png'>Search</button></td></tr>
  </tbody>
  </table>
  <input type='hidden' name='action' value='<?php echo $_GET['action']?>'>
  </form>

<?php  ///////////////////////////////////////////////////////////							Pagination Links							///////////////////////////////////////////////////////////?>

<div class='gray'>
  <br /><b><?php echo $totalrows?> results<br>
	<?php if ($page >= 1 && $page != "all"){
		echo $prevlink;
	}
	if ($page != "all"){
	// Function to generate pagination array - that is a list of links for pages navigation
    function paginate ($base_url, $query_str, $total_pages, $page, $perpage)
    {
        // Array to store page link list
        $page_array = array ();
        // Show dots flag - where to show dots?
        $dotshow = true;
        // walk through the list of pages
        for ( $i = 1; $i <= $total_pages; $i ++ )
        {
           // If first or last page or the page number falls 
           // within the pagination limit
           // generate the links for these pages
           if ($i == 1 || $i == $total_pages || 
                 ($i >= $page - $perpage && $i <= $page + $perpage) )
           {
              // reset the show dots flag
              $dotshow = true;
              // If it's the current page, leave out the link
              // otherwise set a URL field also
              if ($i != $page)
                  $page_array[$i]['url'] = $base_url . $query_str .
                                             "=" . $i;
              $page_array[$i]['text'] = strval ($i);
           }
           // If ellipses dots are to be displayed
           // (page navigation skipped)
           else if ($dotshow == true)
           {
               // set it to false, so that more than one 
               // set of ellipses is not displayed
               $dotshow = false;
               $page_array[$i]['text'] = "...";
           }
        }
        // return the navigation array
        return $page_array;
    }
    // To use the pagination function in a 
    // PHP script to display the list of links
    // paginate total number of pages ($pc) - current page is $page and show
    // 3 links around the current page
    $pages = paginate ("?$url&amp;", "page", ($pc - 1), $page, 3); ?>

    <?php 
    // list display
    foreach ($pages as $page) {
        // If page has a link
        if (isset ($page['url'])) { ?>
            <a href="<?php echo $page['url']?>">
    		<?php echo $page['text'] ?>
    	</a>
    <?php }
        // no link - just display the text
         else 
            echo $page['text'];
    }
	}?>
	<?php if ($page >= 1 && $page != "all"){
		echo $nextlink."<br />";
	}else
	?>
	<?php if ($page != "all"){
		echo $alllink."<br />";
	}else
	?>
	<a href='<?php echo "$fscriptname?action=$action&amp;export=1"?>'><img src='images/xcel2.jpg' height=25 border=0>Export to Excel
    
<?php  ///////////////////////////////////////////////////////////							end, Pagination	Links						///////////////////////////////////////////////////////////?>

<?php 
}

if ($export) {
  echo "\n</body>\n</html>\n";
  exit;
}

?>