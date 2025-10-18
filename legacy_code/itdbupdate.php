<script>
  $(document).ready(function() {

    $('#updtbtn').click(function(){
      $.ajax({
	type: "POST",
	cache: false,
	//url: "itdbupdate.php",
	//data: $('#profilesfrm').serialize(),
	data: {update: '1'},
	success: function(msg){
	  $('#messagetxt').html(msg);
	}
      });

    });

  });
</script>

<?php
if (!isset($_POST['update'])) {
?>
  <div id=messagetxt style='margin-left:auto;margin-right:auto;width:800px;'>
    <div style='background-color:#eee;border:1px solid #aaa;padding:5px;'>
    <?php
    if ($fordbversion < dbversion() ) {
	echo "It seems that you have replaced the ITDB installation with an older version, but  not the the database  which is newer version than the software. This will probably lead to data corruption. Please update ITDB files to the newest version<br>\n";
    }
    else {
    ?>
    You have updated your ITDB installation (files). To complete the upgrade, database to the same version.<br>
    Please take a backup of your database if you haven't done so yet right now, by downloading the file: 
      <a style='font-size:12px;' title='<?php te("Download DataBase file. Contains all data except uploaded files/documents");?>' href='getdb.php'>
      <img src='images/database_save.png'> Download Database (SQLite)</a><br>
      When finished, click <button id='updtbtn' type=submit>update</button> to update the database to the latest version.
      <br>
    </div>

  <div style='background-color:#fee;margin-top:10px;border:1px solid #aaa;padding:5px;' >
  <?php
  echo "Database version=".dbversion()."<br>";
  echo "Installation version=$fordbversion";
  echo "</div>";
  echo "</div>";

  }
  ?>
<?php
}
else {

  $updtdir="updates/db/";
  $startversion=dbversion();

  echo "Starting update process\n<ul>\n";
  for ($v=$startversion+1 ; $v<=$fordbversion ; $v++) {
    echo "<li>Updating to version $v:</li>";
    $updtfile=$updtdir.($v-1)."_${v}.sql";
    $sql = file_get_contents($updtfile);
    $b=strlen($sql);
    echo "<li>Reading update file: $updtfile ($b bytes)</li>\n";
    if (!$b) exit;
     //$dbh->beginTransaction(); //not here , some statements auto-commit
    echo "<li>Applying  updates</li>\n";
     db_exec($dbh,$sql);
  }
  echo "</ul>\n";

  if (dbversion()==$fordbversion) {
    $s="Success!";
    $c="#dfd";
  }
  else {
    $s="Version Mismatch";
    $c="#fdd";
  }

  echo "<div style='background-color:$c;margin-top:10px;border:1px solid #aaa;padding:5px;' >";
  echo "$s<br>Database version=".dbversion()."<br>";
  echo "Installation version=$fordbversion";
  echo "</div>";
  echo "<a href=''>Proceed to ITDB</a>"; 
}
?>
