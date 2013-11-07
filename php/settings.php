<SCRIPT LANGUAGE="JavaScript"> 

$(document).ready(function() {



<?php
if (isset($_POST['dateformat']) ) { //if we came from a post (save), refresh to show new language
	echo "window.location=window.location;";
}
?>

});
</SCRIPT>
<?php 

if (!isset($initok)) {echo "do not run this script directly";exit;}
if(!isset($userdata) || $userdata[0]['usertype'] == 1) { echo "You must have Admin (Full Access) to access this page";exit;}

/* Spiros Ioannou 2009-2010 , sivann _at_ gmail.com */

if (isset($_POST['dateformat']) ) { //if we came from a post (save), update the rack 
  $sql="UPDATE settings set companytitle='".trim($_POST['companytitle']).
  "', dateformat='".$_POST['dateformat'].
  "', currency='".$_POST['currency'].
  "', useldap='".$_POST['useldap'].
  "', ldap_server='".trim($_POST['ldap_server']).
  "', ldap_dn='".trim($_POST['ldap_dn']).
  "', ldap_getusers='".trim($_POST['ldap_getusers']).
  "', ldap_getusers_filter='".trim($_POST['ldap_getusers_filter']).
  "',".
       " lang='".$_POST['lang']."', ".
       //" switchmapenable='".$_POST['switchmapenable']."', switchmapdir='".$_POST['switchmapdir']."',".
       //" timeformat='".$_POST['timeformat']."', ".
       " timezone='".$_POST['timezone']."' ";
  db_exec($dbh,$sql);

}//save pressed

/////////////////////////////
//// display data 

$sql="SELECT * FROM settings";
$sth=$dbh->query($sql);
$settings=$sth->fetchAll(PDO::FETCH_ASSOC);
$settings=$settings[0];

echo "\n<form id='mainform' method=post  action='$scriptname?action=$action' enctype='multipart/form-data'  name='settingsfrm'>\n";

echo "\n<h1>".t("Settings")."</h1>\n";
?>

    <table class="tbl2" >
    <tr><td colspan=2><h3><?php te("Settings"); ?></h3></td></tr>
    <tr><td class="tdt"><?php te("Company Title");?>:</td> 
        <td><input  class='input2 ' size=20 type=text name='companytitle' value="<?php echo $settings['companytitle']?>"></td></tr>
    <tr><td class="tdt"><?php te("Date Format")?></td><td>
    <select  name='dateformat'>
      <?php if ($settings['dateformat']=="dmy") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> value='dmy'>Day/Month/Year</option>
      <?php if ($settings['dateformat']=="mdy") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> value='mdy'>Month/Day/Year</option>
    </select>
    </td>
    </tr>
    <!--tr><td class="tdt"><?php te("Time Format")?></td><td>
    <select  name='timeformat'>
      <?php if ($settings['timeformat']=="hh:mm:ss") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> value='hh:mm:ss'>hh:mm:ss</option>
    </select>
    </td>
    </tr-->

    <tr><td class="tdt"><?php te("Currency")?></td><td>

    <select  name='currency'>
      <?php if ($settings['currency']=="&euro;") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> title='Euro' value='<?php echo htmlentities("&euro;");?>'>&euro;</option>

      <?php if ($settings['currency']=="$") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> title='Dollar' value='<?php echo htmlentities("$");?>'>$</option>

      <?php if ($settings['currency']=="&pound;") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> title='Pound' value='<?php echo htmlentities("&pound;");?>'>&pound;</option>

      <?php if ($settings['currency']=="&yen;") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> title='Yen' value='<?php echo htmlentities("&yen;");?>'>&yen;</option>

      <?php if ($settings['currency']=="&#8361;") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> title='Won' value='<?php echo htmlentities("&#8361;");?>'>&#8361;</option>

      <?php if ($settings['currency']=="&#8360;") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> title='Rupee' value='<?php echo htmlentities("&#8360;");?>'>&#8360;</option>

      <?php if ($settings['currency']=="&#8377;") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> title='Indian Rupee' value='<?php echo htmlentities("&#8377;");?>'>&#8377;</option>

      <?php if ($settings['currency']=="&#20803;") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> title='Yuan' value='<?php echo htmlentities("&#20803;");?>'>&#20803;</option>

      <?php if ($settings['currency']=="&#65020;") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> title='Rial' value='<?php echo htmlentities("&#65020;");?>'>&#65020;</option>

      <?php if ($settings['currency']=="Ft") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> title='Forint' value='<?php echo htmlentities("&#65020;");?>'>Ft</option>

    </select></td></tr>
    <tr><td class="tdt"><?php te("Interface Language")?></td><td>
    <select  name='lang'>
      <?php if ($settings['lang']=="en") $s="SELECTED"; else $s="" ?>
      <option <?php echo $s?> value='en'>en</option>
      <?php
      $tfiles=scandir("translations/");
      foreach ($tfiles as $f) {
		  $f=strtolower($f);
		  if (strstr($f,"txt") && (!strstr($f,"new")) && (!strstr($f,"missing"))) {
			  $bf=basename($f,".txt");
			  if ($settings['lang']=="$bf") $s="SELECTED"; else $s="" ;
			  echo "<option $s value='$bf'>$bf</option>\n";
		  }
      }
      ?>
    </select>
    </td>

    </tr>
    <tr><td class="tdt" title='Timezone based on 3 alpha abbreviation. (e.g. MST, EST, UTC, etc)'><?php te("Timezone (Abbreviation)");?>:</td><td>
    <select name='timezone'>
<?php
      $tz_array=file("php/timezones.txt");
      foreach ($tz_array as $tz) {
	$tz=trim($tz);
	if ($tz==$settings['timezone']) $s="SELECTED"; else $s="";
	echo "<option $s>$tz</option>\n";
      }
?>
</select>

</td></tr>

<!--
    <tr><td colspan=2><h3><?php te("Integration"); ?></h3></td></tr>

    <tr>
    <?php
      //SwitchMap Enabled (switchmapenable)
      $y="";$n="";
      if ($settings['switchmapenable']=="1") {$y="checked";$n="";}
      if ($settings['switchmapenable']=="0") {$n="checked";$y="";}
    ?>
      <td class='tdt' title='Select yes if switchmap is installed on this server.'><?php te("SwitchMap Integration");?>:</td>
      <td>
        <div >
          <input  validate='required:true' <?php echo $y?> class='radio' type=radio name='switchmapenable' value='1'><?php te("Yes");?>
          <input  class='radio' type=radio <?php echo $n?> name='switchmapenable' value='0'><?php te("No");?>
        </div>
      </td>
    </tr>
    <tr><td class="tdt" title='Provide the full path to the switches directory within the SwitchMap directory.'><?php te("Path To Switchmap");?>:</td><td><input  class='input2 ' size=20 type=text name='switchmapdir' value="<?php echo $settings['switchmapdir']?>"></td></tr>

-->
    <tr><td class="tdt"><?php te("Use LDAP");?>:</td> 
        <td><select  name='useldap'>
        <?php
        if ($settings['useldap']==1) $s1='SELECTED';
        else $s1='';
        ?>
        <option value=0><?=t('No')?></option>
        <option <?=$s1?> value=1><?=t('Yes')?></option>
        </select>
        (for authentication only, except user admin which is local)</td></tr>

    <tr><td class="tdt"><?php te("LDAP Server");?>:</td> 
        <td><input  class='input2 ' size=20 type=text name='ldap_server' value="<?php echo $settings['ldap_server']?>"> e.g.: ldap.mydomain.com</td></tr>
    <tr><td class="tdt"><?php te("LDAP DN");?>:</td> 
        <td><input  class='input2 ' size=20 type=text name='ldap_dn' value="<?php echo $settings['ldap_dn']?>"> For user authentication.e.g.: ou=People,dc=mydomain,dc=com</td></tr>
    <tr><td class="tdt"><?php te("LDAP Search for users");?>:</td> 
        <td><input  class='input2 ' size=20 type=text name='ldap_getusers' value="<?php echo $settings['ldap_getusers']?>"> e.g.: ou=People,dc=mydomain,dc=com</td></tr>
    <tr><td class="tdt"><?php te("LDAP User filter");?>:</td> 
        <td><input  class='input2 ' size=20 type=text name='ldap_getusers_filter' value="<?php echo $settings['ldap_getusers_filter']?>"> e.g.: (&amp; (uid=*) (IsActive=TRUE))</td></tr>


<tr>
<td colspan=2>
<br>
<button type="submit"><img src="images/save.png" alt="Save"> <?php te("Save");?></button>
</td>
</tr>
</table>
<input type=hidden name='action' value='<?php echo $action ?>'>
</form>

</body>
</html>
