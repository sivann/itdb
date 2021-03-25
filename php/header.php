<!doctype html>

<!-- (c) dev 2021 -->
<!-- (c) Spiros Ioannou 2008-2009 -->
<!-- sivann at gmail . com  -->
<!-- poer.mrn at gmail . com  -->
<html itemscope="" lang="en-ID">
<head>
<title>ITDB - [<?php  echo $title." $id";?>] - IT Items Database</title>
<meta content="origin" name="referrer">
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" href="images/favicon.png">
<!-- Themes & Style -->
<link rel="stylesheet" href="css/sweetTitles.css" type="text/css">
<link rel="stylesheet" href="css/jquery-ui.css" type="text/css" >
<link rel="stylesheet" href="css/itdb.css" type="text/css">
<!-- Datatables -->
<link rel="stylesheet" href="css/jquery.dataTables.css" type="text/css">
<link rel="stylesheet" href="css/buttons.dataTables.min.css" type="text/css">

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
<!-- JQuery -->
<script src="js/jquery-1.12.1.js"></script>
<script src="js/jquery-ui.min.js"></script>
<!-- Datatables -->
<script src="js/jquery.dataTables.min.js"></script>
<script src="js/jszip.min.js"></script>
<script src="js/pdfmake.min.js"></script>
<script src="js/vfs_fonts.js"></script>
<script src="js/dataTables.buttons.min.js"></script>
<script src="js/buttons.html5.min.js"></script>
<script src="js/buttons.print.min.js"></script>
<!-- Themes & Style -->
<script src="js/itdb.js"></script>
<script src="js/Sortable.min.js"></script>
<script src="js/sweetTitles.js"></script>
<script src="js/jquery.maskedinput.min.js"></script>
<script src="js/jquery.quicksearch.js"></script>
<script src="js/jquery.form.js"></script>
<script src="js/jquery.popupWindow.js"></script>


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

