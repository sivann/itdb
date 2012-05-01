<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<!-- (c) Spiros Ioannou 2008-2009 -->
<!-- sivann at gmail . com  -->
<html>
<head>
<title>ITDB - [<?php  echo $title." $id";?>] - IT Items Database</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=9" > 


<link rel="stylesheet" href="css/sweetTitles.css">
<link type="text/css" href="css/jquery-themes/blue2/jquery-ui-1.8.12.custom.css" rel="stylesheet" >
<link rel="stylesheet" href="css/itdb.css" type="text/css">
<link rel="stylesheet" href="css/theme.css" type="text/css">

<link rel="stylesheet" href="css/datatable.css" type="text/css">
<link rel="stylesheet" href="css/TableTools_JUI.css" type="text/css">
<link rel="stylesheet" href="css/ColVis.css" type="text/css">

<link rel="icon" type="image/png" href="images/favicon.png">


<?php
  if (isset($_GET['nomenu']) && ($_GET['nomenu'])) {
    echo "<link rel='stylesheet' href='css/itdbnomenu.css' type='text/css'>\n";

  }
?>


<script>

function BodyLoad() {
    return;
    //document.itdbloginfrm.authusername.focus();
}

function delconfirm(what,delurl) {

  var answer = confirm("Are you sure you want to delete id " + what + " ?")
  if (answer) window.location = delurl;
}

function cloneconfirm(what,cloneurl) {

  var answer = confirm("Are you sure you want to clone item id " + what + " ?")
  if (answer) window.location = cloneurl;
}



function delconfirm2(what,delurl,msg)
{
  var mesg;
  if (arguments.length<3) 
    mesg="Warning! All associations and orphaned files will be removed too. Write YES if you want to delete this : " + what;
  else 
    mesg=msg+" Write YES if you want to delete id "+what;

  $i=window.prompt(mesg,'NO');
  if ($i=='YES') 
    window.location = delurl;
  else
    alert('ABORTED...');
}

function showid(n){
  document.getElementById(n).scrollIntoView(true);
}
</script>

<script type="text/javascript" src="js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.12.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="js/jquery.TableTools.min.js"></script>
<!-- <script type="text/javascript" src="js/jquery.FixedColumns.min.js"></script> -->
<!-- <script type="text/javascript" src="js/jquery.ColVis.min.js"></script> -->
<script type="text/javascript" src="js/itdb.js "></script>
<script type="text/javascript" SRC="js/sorttable.js"></script>
<script type="text/javascript" src="js/sweetTitles.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput-1.3.js"></script>
<script type="text/javascript" src="js/jquery.quicksearch.js "></script>
<script type="text/javascript" src="js/jquery.form.js "></script>
<script type="text/javascript" src="js/jquery.popupWindow.js "></script>


<?php 
  echo $head;
?>




<script>

$(document).ready(function() {

    $( ".dateinp" ).datepicker({
	    showOn: "button",
	    buttonImage: "images/calendar.png",
	    buttonImageOnly: true,
	    changeMonth: true,
	    changeYear: true,
	    dateFormat: '<?php echo $datecalparam?>',
	    onClose: function() {$(this).valid();}, //for the validator plugin to validate gui-selected date

    });

    $.mask.definitions['d']='[0123]';
    $.mask.definitions['m']='[01]';
    $.mask.definitions['y']='[12]';
    $(".dateinp").mask('<?php echo $maskdateparam?>',{placeholder:"_"});
  });

</script>


</head>

