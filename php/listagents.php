<SCRIPT LANGUAGE="JavaScript"> 
$(function () {
 //$('input#agentlistfilter').quicksearch('table#agentlisttbl tbody tr');

  $('table#agentlisttbl').dataTable({
                "sPaginationType": "full_numbers",
                "bJQueryUI": true,
                "iDisplayLength": 50,
                "aLengthMenu": [[10,25, 50, 100, -1], [10,25, 50, 100, "All"]],
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
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

$sql="SELECT * from agents order by title,type";
$sth=db_execute($dbh,$sql);
?>

<h1><?php te("Vendors (Agents)");?> <a title='<?php te("Add new Agent");?>' href='<?php echo $scriptname?>?action=editagent&amp;id=new'><img border=0 src='images/add.png' ></a>
</h1>

<table class='display' width='100%' border=0 id='agentlisttbl'>

    <thead>
        <tr>
			<th style='width:70px'><?php te("Edit ID");?></th>
			<th style='width:15em'><?php te("Type");?></th>
			<th style='width:35em'><?php te("Vendor");?></th>
			<th style='width:50em'><?php te("Sales Contact");?></th>
			<th style='width:50em'><?php te("Support Contact");?></th>
        </tr>
    </thead>
<tbody>
<?php 

$i=0;
/// print actions list
while ($r=$sth->fetch(PDO::FETCH_ASSOC)) {
  $i1++;
  $type="";

  if ($r['type']&1) $type.="<span style='color:#b00;'>Buyer<br></span>";
  if ($r['type']&2) $type.="<span style='color:#a0b;'>S/W Manufacturer<br></span>";
  if ($r['type']&8) $type.="<span style='color:#600;'>H/W Manufacturer<br></span>";
  if ($r['type']&4) $type.="<span style='color:#0a0;'>Vendor<br></span>";
  if ($r['type']&16) $type.="<span style='color:#44e;'>Contractor<br></span>";

  echo "\n <tr id='trid{$r['id']}'>
  			<td><a class='editiditm icon edit' href='$scriptname?action=editagent&amp;id=".$r['id']."'><span>{$r['id']}</span></a></td>
			<td style='padding-left:2px;padding-right:2px;'>$type</td>
			<td style='padding-left:2px;padding-right:2px;'>
					{$r['title']}<br>
					{$r['contactadd']}";
					if($r['contactadd2']!==""){
						echo "<br>".$r['contactadd2']."<br>";
						echo $r['contactcity'] ." ". $r['contactstate'] ." ". $r['contactzip'];
					}else{
						echo "<br>".$r['contactcity'] ." ". $r['contactstate'] ." ". $r['contactzip'];
					}
	  echo "</td>

 			<td style='padding-left:2px;padding-right:2px;'>
				<table>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>Name:</td><td style='padding-top:0px;padding-bottom:0px'>{$r['salescontactname']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>Office Phone:</td><td style='padding-top:0px;padding-bottom:0px'>{$r['salescontactphone']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px;width:10em'>Mobile Phone:</td><td style='padding-top:0px;padding-bottom:0px'>{$r['salescontactcellphone']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>Fax:</td><td style='padding-top:0px;padding-bottom:0px'>{$r['salescontactfax']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>Email:</td><td style='padding-top:0px;padding-bottom:0px'><a href='mailto:".$r['salescontactemail']." '>{$r['salescontactemail']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>URL:</td><td style=';padding-top:0px;padding-bottom:0px'><a href='".$r['salescontacturl']." '>{$r['salescontacturl']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>Notes:</td><td style='width:50em;padding-top:0px;padding-bottom:0px'>{$r['salescontactnotes']}</td></tr>
				</table>
			</td>
 			<td style='padding-left:2px;padding-right:2px;'>
				<table>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>Name:</td><td style='padding-top:0px;padding-bottom:0px'>{$r['supportcontactname']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>Office Phone:</td><td style='padding-top:0px;padding-bottom:0px'>{$r['supportcontactphone']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px;width:10em'>Mobile Phone:</td><td style='padding-top:0px;padding-bottom:0px'>{$r['supportcontactcellphone']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>Fax:</td><td style='padding-top:0px;padding-bottom:0px'>{$r['supportcontactfax']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>Email:</td><td style='padding-top:0px;padding-bottom:0px'><a href='mailto:".$r['supportcontactemail']." '>{$r['supportcontactemail']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>URL:</td><td style=';padding-top:0px;padding-bottom:0px'><a href='".$r['supportcontacturl']." '>{$r['supportcontacturl']}</td></tr>
					<tr><td style='text-align:right;padding-top:0px;padding-bottom:0px'>Notes:</td><td style='width:50em;padding-top:0px;padding-bottom:0px'>{$r['supportcontactnotes']}</td></tr>
				</table>
			</td>";
}

?>

</tbody>
</table>

</form>
</body>
</html>
