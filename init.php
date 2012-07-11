<?php 
//ITDB:IT-items database
//sivann at gmail.com 2008-2009
//init.php: init db + some variables

//$scriptdir=dirname($_SERVER['SCRIPT_FILENAME']);
//$scriptdir=getcwd();  //some may have problem with this

$scriptdir=dirname( __FILE__ );

date_default_timezone_set("GMT");
$servername=$_SERVER['SERVER_NAME'];
$scriptname=$_SERVER['SCRIPT_NAME'];

error_reporting (E_ALL ^ E_NOTICE); 

//for scripts including init.php from inside php/ directory:
if (basename($scriptdir)=="php") { 
  $scriptdir=preg_replace("/\/php$/","",$scriptdir);
}

//wscriptdir: www relative address of base itdb directory (/itdb)
//used for cookie setting
$wscriptdir=dirname($_SERVER['SCRIPT_NAME']);
if (basename($wscriptdir)=="php") { 
  $wscriptdir=preg_replace('#/php$#','',$wscriptdir);
}
if ($wscriptdir=="") $wscriptdir="/"; //itdb installed under /

if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
  $remaddr=$_SERVER['HTTP_X_FORWARDED_FOR'];
else 
  $remaddr=$_SERVER['REMOTE_ADDR'];


require_once('conf.php');

if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == "on"))
  $prot="https" ;
else
  $prot="http";

$fscriptname="$prot://$servername$scriptname";
$fuploaddirwww="$prot://$servername".dirname($scriptname)."/$uploaddirwww";

// find out our username
$procusername="";
if (function_exists('posix_getgid')) {
  $procuserinfo = posix_getpwuid(posix_getgid()); 
  $procusername=$procuserinfo['name'];
}

if (!strlen($procusername))
  $procusername=exec('whoami');

/**********************************************/
//to work without register_globals
foreach($_REQUEST as $k => $v) { ${$k} = $v; }


if (!file_exists($dbfile)) {
  echo "$dbfile not found<br>";
  echo "<b><big>if this is a fresh install, copy pure.db to $dbfile</b></big><br>";
  echo "in unix: <br><pre>cd $scriptdir/data; cp pure.db itdb.db</pre>";
  exit;
}

if (!$demomode) {
  if (!is_writable($dbfile)) {
    echo "$dbfile is not writeable by apache<br>";
    echo "<b><big>make $dbfile writeable by the user running the web server</big></b><br>";
    echo "in unix:<br><pre> cd $scriptdir/data; chown $procusername itdb.db; chmod u+w itdb.db</pre>";
    exit;
  }

  if (!is_writable("$scriptdir/data")) {
    echo "$scriptdir is not writeable by apache<br>";
    echo "<b><big>make $scriptdir/data writeable by the user running the web server</big></b><br>";
    echo "in unix:<br><pre> chown $procusername $scriptdir/data; chmod u+w $scriptdir/data/</pre>";
    exit;
  }

  if (!is_writable($uploaddir)) {
    echo "$scriptdir is not writeable by apache<br>";
    echo "<b><big>make $uploaddir writeable by the user running the web server</big></b><br>";
    echo "in unix: <br><pre>chown $procusername $uploaddir; chmod u+w $uploaddir</pre>";
    exit;
  }

}


//open db
try {
  $dbh = new PDO("sqlite:$dbfile");
} 
catch (PDOException $e) {
  print "Open database Error!: " . $e->getMessage() . "<br>";
  die();
}
$error = $dbh->errorInfo();
if($error[0] && isset($error[2]))  echo "Error 00: ".$error[2]."<br>";

//$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

//some configuration
$ret = $dbh->exec("PRAGMA case_sensitive_like = 0;");
$ret = $dbh->exec("PRAGMA encoding = \"UTF-8\";");


$uploadErrors = array(
    UPLOAD_ERR_OK => 'There is no error, the file uploaded with success.',
    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
);


$sql="SELECT * FROM settings";
$sth=db_execute($dbh,$sql,1);
$settings=$sth->fetchAll(PDO::FETCH_ASSOC);

$settings=$settings[0];


if ($settings['dateformat']=="dmy") {
  $datetitle="d/m/y or yyyy";
  $datecalparam="dd/mm/yy";
  $dateparam="d/m/Y";
  $maskdateparam="d9/m9/y999";
}
else {
  $datetitle="m/d/y or yyyy";
  $datecalparam="mm/dd/yy";
  $dateparam="m/d/Y";
  $maskdateparam="m9/d9/y999";
}

date_default_timezone_set($settings['timezone']);

read_trans($settings['lang']);

function sec2ymd($secs)
{
  if (strlen($secs))
    return date("Ymd",$secs);
  else 
    return "";
}

//convert Y/M/D dates to unix timestamp
function ymd2sec($d)
{
  global $settings;

  if (!strlen($d))
    $purchasedate2="NULL";
  elseif ($settings['dateformat']=="dmy"){
    $x=explode("/",$d);
    if ((count($x)==1) && strlen(trim($d))==4) { //only year
      $d2=  mktime(0, 0, 0, 1, 1, $d);
    }
    else {
      $d2=  mktime(0, 0, 0, $x[1], $x[0], $x[2]);
    }
//echo "$d -> $d2<br>";
    return $d2;
  }
  elseif ($settings['dateformat']=="mdy"){
    $x=explode("/",$d);
    if ((count($x)==1) && strlen(trim($d))==4) { //only year
      $d2=  mktime(0, 0, 0, 1, 1, $d);
    }
    else {
      $d2=  mktime(0, 0, 0, $x[0], $x[1], $x[2]);
    }
    return $d2;
  }
  return "";
}

//remove invalid filename characters
function validfn($s) {
  $f =preg_split('//u', 'ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΠΡΣΣΤΥΦΧΨΩΪΫΌΎΏΆΈΰαβγδεζηθικλμνξοπρςστυφχψωίϊΐϋόύώάέΰ');
  $t =preg_split('//u', 'ABGDEZHUIKLMNJOPRSSTYFXCVIUOUVAEUabgdezhuiklmnjoprsstyfxcviiiuouvaeu');
  $s=str_replace($f,$t,$s);
  $reserved = preg_quote('\/:*?"<>|', '/');
  $s=preg_replace("/([-\\x00-\\x20\\x7f-\\xff{$reserved}])/e", "", $s); 
  $s=strtolower($s);
  return $s;
}


//encode string for sql/html
function strenc($s)
{
  $s=htmlspecialchars($s,ENT_QUOTES,"UTF-8");
  return $s;
}

if (get_magic_quotes_gpc()) {
    function stripslashes_deep($value)
    {
        $value = is_array($value) ?
                    array_map('stripslashes_deep', $value) :
                    //sqlite_escape_string(stripslashes($value));
                    strenc(stripslashes($value)); /* take care of special chars + quotes */
                    //stripslashes($value);

        return $value;
    }

    $_POST = array_map('stripslashes_deep', $_POST);
    $_GET = array_map('stripslashes_deep', $_GET); /* careful, this may interfere with  serialize()/unserialize() */
    $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
    $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

//////////////////// Database functions /////
// check permissions, log errors and transactions
// 
//encode string for sql/html

//for insert, update, delete
function db_exec($dbh,$sql,$skipauth=0,$skiphist=0,&$wantlastid=0)
{
global $authstatus,$userdata, $remaddr, $dblogsize,$errorstr,$errorbt;

  if (!$skipauth && !$authstatus) {echo "<big><b>Not logged in</b></big><br>";return 0;}
  if (stristr($sql,"insert ")) $skiphist=1; //for lastid function to work.

  //find user access
  $usr=$userdata[0]['username'];
  $sqlt="SELECT usertype FROM users where username='$usr'";
  $sth=$dbh->prepare($sqlt);
  $sth->execute();
  $ut=$sth->fetch(PDO::FETCH_ASSOC);
  $usertype=($ut['usertype']);
  $sth->closeCursor();

  if (!$skipauth && $usertype && (stristr($sql,"DELETE") || stristr($sql,"UPDATE") || stristr($sql,"INSERT")) 
      && !stristr($sql," tt ")) { /*tt:temporary table used for complex queries*/
    echo "<big><b>Access Denied, user '$usr' is read-only</b></big><br>";
    return 0;
  }

  $r=$dbh->exec($sql);
  $error = $dbh->errorInfo();
  if($error[0] && isset($error[2])) {
    $errorstr= "<br><b>db_exec:DB Error: ($sql): ".$error[2]."<br></b>";
    $errorbt = debug_backtrace();
    echo "</table></table></div>\n<pre>".$errorstr;
    print_r ($errorbt);
    return 0;
  }
  $wantlastid=$dbh->lastInsertId();

  if (!$skiphist) {
    $hist="";
    $t=time();
    $escsql=str_replace("'","''",$sql);
    $histsql="INSERT into history (date,sql,ip,authuser) VALUES ($t,'$escsql','$remaddr','".$_COOKIE["itdbuser"]."')";
    //update history table
    $rh=$dbh->exec($histsql);
    $lasthistid=$dbh->lastInsertId();

    $error = $dbh->errorInfo();
    if($error[0] && isset($error[2])) {
      $errorstr= "<br><b>HIST DB Error: ($histsql): ".$error[2]."<br></b>";
      $errorbt = debug_backtrace();
      echo $errorstr;
      print_r ($errorbt);
      return 0;
    }
    else { /* remove old history entries */
	$lastkeep=(int)($lasthistid)-$dblogsize;
	$sql="DELETE from history where id<$lastkeep";
	$sth=$dbh->exec($sql);
    }

  }
  return $r;
} //db_exec

//for select
function db_execute($dbh,$sql,$skipauth=0)
{
  global $authstatus,$errorstr,$errorbt;
  if (!$skipauth && !$authstatus) {echo "<big><b>Not logged in</b></big><br>";return 0;}
  $sth = $dbh->prepare($sql);
  $error = $dbh->errorInfo();
  if($error[0] && isset($error[2])) {
    $errorstr= "\n<br><b>db_execute:DB Error: ($sql): ".$error[2]."<br></b>\n";
    $errorbt= debug_backtrace();

    echo "</table></table></div>\n<pre>".$errorstr;
    print_r ($errorbt);
    echo "</pre>";

    return 0;
  }
  $sth->execute();
  return $sth;
}


///////////cookies///////////

$authstatus=0;
$authmsg="Not logged in";
if (!$demomode) {
  if (isset($_POST['logout'])) {
     setcookie("itdbcookie1",'', time()+3600*1,$wscriptdir);
     header("Location: $scriptname"); //eat get parameters
  }
  elseif (isset($_POST['authusername'])){ //logging in
   $username=$_POST['authusername'];
   $password=$_POST['authpassword'];
   $sth=db_execute($dbh,"SELECT * from users where username='$username' limit 1",1);
   $userdata=$sth->fetchAll(PDO::FETCH_ASSOC);
   $nr=count($userdata);
   if ((!$nr) || $userdata[0]['username']!=$username) {
      $authstatus=0;
      $authmsg="Invalid username";
   }
   elseif (($userdata[0]['pass']==$password) && strlen($password)) { //correct password
     $rnd=mt_rand(); //create a random
     //store random in db
     db_exec($dbh,"UPDATE users set cookie1='$rnd' where username='$username'",1,1);
     //store random in browser
     setcookie("itdbcookie1",$rnd, time()+3600*24*2,$wscriptdir); //random number set for two days
     setcookie("itdbuser",$username, time()+3600*24*60,$wscriptdir); //username
     $authstatus=1;
     $authmsg="User Authenticated";
   }
   else { //wrong password
     $authstatus=0;
     $authmsg="Wrong Password";
   }

  } //logging in 
  elseif (isset($_COOKIE["itdbuser"]) && ! isset($_COOKIE["itdbcookie1"])) {
    $authstatus=0;
    $authmsg="Session Expired";
  } 
  elseif (isset($_COOKIE["itdbuser"])) { //& isset itdbookie1, check if valid
    $sql="SELECT * from users where username='".$_COOKIE["itdbuser"]."' limit 1";
    $sth=db_execute($dbh,$sql,1);
    $userdata=$sth->fetchAll(PDO::FETCH_ASSOC);
    //$dbg= "db cookie:".$userdata[0]['cookie1'] . "<br>browser cookie:".$_COOKIE["itdbcookie1"];

    if ($userdata[0]['cookie1']==$_COOKIE["itdbcookie1"]) {
      $authstatus=1;
      $authmsg="Welcome back ".$_COOKIE["itdbuser"];
      setcookie("itdbcookie1",$userdata[0]['cookie1'], time()+3600*24*2,$wscriptdir); //renew for two days
    }
    else {
      $authstatus=0;
      $authmsg="Logged on from another browser? Please re-login.";
    }
  }
  else
    $authmsg="Please Login!";
}
else { //demomode
  $authstatus=1;
  $authmsg="Demo mode, no save possible";
}

function getstatusidofitem($itemid,$dbh)
{
  $sql="SELECT status FROM items WHERE items.id='$itemid'";
  $sth=db_execute($dbh,$sql);
  $statusid=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $statusid=$statusid['status'];
  return $statusid;

}

/* return css and title based on item status */
function attrofstatus($statusid,$dbh)
{
  $sql="SELECT * from statustypes WHERE id='$statusid'";
  $sth=db_execute($dbh,$sql);
  $statusdesc=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $statusdesc=$statusdesc['statusdesc'];
  $attr=" class='statusx status".$statusid."' title='Status: $statusdesc' ";
  $statustxt=$statusdesc;

  return array($attr,$statustxt);

}

/********************************* DB QUERIES IN FUNCTION FORM ****************************************/

function ftype2str($typeid,$dbh) {

  $sql="SELECT typedesc from filetypes WHERE ".
        " id='$typeid'";
  $sth=db_execute($dbh,$sql);
  $typestr=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $typestr=$typestr['typedesc'];

  return ucfirst($typestr);

}
//returns files array from invoice id
function invid2files($invid,$dbh) {
  $sql="SELECT files.* from files,invoice2file WHERE ".
        " invoice2file.invoiceid='$invid' AND ".
        " invoice2file.fileid=files.id";
  $sth=db_execute($dbh,$sql);
  $fn=$sth->fetchAll(PDO::FETCH_ASSOC);
  return $fn;
}

//returns files array from software id
function softid2files($softid,$dbh) {
  $sql="SELECT files.* from files,software2file WHERE ".
        " software2file.softwareid='$softid' AND ".
        " software2file.fileid=files.id";
  $sth=db_execute($dbh,$sql);
  $fn=$sth->fetchAll(PDO::FETCH_ASSOC);
  return $fn;
}

function softid2invoicefiles($softid,$dbh) {
  $sql="SELECT files.* from files,invoice2file,soft2inv WHERE ".
      " soft2inv.softid='$softid' AND ".
      " invoice2file.invoiceid=soft2inv.invid AND ".
      " invoice2file.fileid=files.id";
  $sthi=db_execute($dbh,$sql);
  $f=$sthi->fetchAll(PDO::FETCH_ASSOC);
  return $f;
}


function softid2contractfiles($softid,$dbh) {
    $sql="SELECT files.* from files,contract2file,contract2soft WHERE ".
        " contract2soft.softid='$softid' AND ".
        " contract2file.contractid=contract2soft.contractid AND ".
        " contract2file.fileid=files.id";
    $sthi=db_execute($dbh,$sql);
    $f=$sthi->fetchAll(PDO::FETCH_ASSOC);
    return $f;
}




//returns files array from item id
function itemid2files($itemid,$dbh) {
  $sql="SELECT files.* from files,item2file WHERE ".
        " item2file.itemid='$itemid' AND ".
        " item2file.fileid=files.id";
  $sth=db_execute($dbh,$sql);
  $fn=$sth->fetchAll(PDO::FETCH_ASSOC);
  return $fn;
}

function itemid2invoicefiles($itemid,$dbh) {
    $sql="SELECT files.* from files,invoice2file,item2inv WHERE ".
        " item2inv.itemid='$itemid' AND ".
        " invoice2file.invoiceid=item2inv.invid AND ".
        " invoice2file.fileid=files.id";
    $sthi=db_execute($dbh,$sql);
    $f=$sthi->fetchAll(PDO::FETCH_ASSOC);
    return $f;
}

function itemid2contractfiles($itemid,$dbh) {
    $sql="SELECT files.* from files,contract2file,contract2item WHERE ".
        " contract2item.itemid='$itemid' AND ".
        " contract2file.contractid=contract2item.contractid AND ".
        " contract2file.fileid=files.id";
    $sthi=db_execute($dbh,$sql);
    $f=$sthi->fetchAll(PDO::FETCH_ASSOC);
    return $f;
}



//returns files array from contract id
function contractid2files($contractid,$dbh) {
  $sql="SELECT files.* from files,contract2file WHERE ".
        " contract2file.contractid='$contractid' AND ".
        " contract2file.fileid=files.id";
  $sth=db_execute($dbh,$sql);
  $fn=$sth->fetchAll(PDO::FETCH_ASSOC);
  return $fn;
}



//returns number of connected items/racks with a locationid
function countloclinks($locid,$dbh) {
  $sql="select count(id) count from items where locationid=$locid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $count+=$r['count'];

  $sql="select count(id) count from racks where locationid=$locid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $count+=$r['count'];

  return $count;
}

//returns number of connected items/racks with a location areaid
function countlocarealinks($locareaid,$dbh) {
  $sql="select count(id) count from items where locareaid=$locareaid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $count+=$r['count'];

  $sql="select count(id) count from racks where locareaid=$locareaid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $count+=$r['count'];

  return $count;
}

function countfileidlinks($fileid,$dbh) {
  $count=0;

  $sql="select count(softwareid) count from software2file where fileid=$fileid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $count+=$r['count'];

  $sql="select count(*) count from invoice2file where fileid=$fileid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $count+=$r['count'];

  $sql="select count(*) count from item2file where fileid=$fileid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $count+=$r['count'];

  $sql="select count(*) count from contract2file where fileid=$fileid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $count+=$r['count'];

  return $count;
}


function delfile($fileid,$dbh) {
global $uploaddir;
  //delete inter-item links
  $sql="select fname from files where id=$fileid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $fname=$r['fname'];


  $sql="DELETE from files where id=$fileid";
  $sth=db_exec($dbh,$sql);

  $sql="DELETE from invoice2file where fileid=$fileid";
  $sth=db_exec($dbh,$sql);

  $sql="DELETE from software2file where fileid=$fileid";
  $sth=db_exec($dbh,$sql);

  $sql="DELETE from item2file where fileid=$fileid";
  $sth=db_exec($dbh,$sql);

  $sql="DELETE from contract2file where fileid=$fileid";
  $sth=db_exec($dbh,$sql);

  if (strlen($fname))
    unlink($uploaddir.$fname);
}

function countitemtags($tagid) {
  global $dbh;

  $sql="SELECT count(itemid) count from tag2item where tagid=$tagid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  return $r['count'];
}

function countsoftwaretags($tagid) {
  global $dbh;

  $sql="SELECT count(softwareid) count from tag2software where tagid=$tagid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  return $r['count'];
}



function tagid2name($id) {
  global $dbh;
  $sql="SELECT name from tags where id = '$id'";
  $sth=$dbh->query($sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $name=$r['name'];
  return $name;
}


function tagname2id($name) {
  global $dbh;

  $id="";
  $sql="SELECT id from tags where name = '$name'";
  $sth=$dbh->query($sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $id=$r['id'];

  return $id;

}

function showtags($type="item",$id,$lnk=1) {
  // id: item id
  global $dbh;

  $sql="SELECT name FROM tags,tag2$type WHERE tags.id=tag2$type.tagid AND tag2$type.{$type}id='$id' ORDER BY name";
  $sth = $dbh->query($sql);
  $tags=array();
  while ($r=$sth->fetch(PDO::FETCH_ASSOC)) array_push($tags,$r['name']);
  $sth->closeCursor();


  $ret= "\n<!-- showtags -->\n";
  $ret.= "\n  <ul class='tags'>\n";
  if (count($tags)) {
    for ($i=0;$i<count($tags)-1 ; $i++) {

      if ($lnk)
	$ret.= "    <li><a href=''>{$tags[$i]}</a></li>\n";
      else
	$ret.= "    <li>{$tags[$i]}</li>\n";
    }
    if ($lnk)
      $ret.= "    <li class='last'><a href=''>{$tags[$i]}</a></li>\n";
    else
      $ret.= "    <li class='last'>{$tags[$i]}</li>\n";
  }
  $ret.= "  </ul>\n  ";
  return $ret;
}


function countitemsinrack($rackid) {
  global $dbh;

  $sql="SELECT count(id) count from items where rackid=$rackid";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  return $r['count'];
}

function delrack($rackid,$dbh) {
  $sql="UPDATE items set rackid='' where rackid='$rackid'";
  $sth=db_exec($dbh,$sql);

  $sql="DELETE from racks where id='$rackid'";
  $sth=db_exec($dbh,$sql);

}

function deluser($userid,$dbh) {
  if ($userid==1) {
    echo "Cannot remove user with ID=1";
    return;
  }
  $sql="UPDATE items set userid=1 where userid='$userid'";
  $sth=db_exec($dbh,$sql);

  $sql="DELETE from users where id='$userid'";
  $sth=db_exec($dbh,$sql);

}

function countitemsofuser($userid) {
  global $dbh;

  $sql="SELECT count(id) count from items where userid='$userid'";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  return $r['count'];
}

function showfiles($f,$class="fileslist",$wantdel=1,$divtitle='') {
// f: array with data from "files" table (from fetchall)
global $dateparam,$scriptname,$action,$id,$uploaddirwww,$dbh;

  $flnk="";
  for ($lnk="",$c=0;$c<count($f);$c++) {
   $fname=$f[$c]['fname'];
   $ftitle=$f[$c]['title'];
   $fid=$f[$c]['id'];
   $ftype=$f[$c]['type'];
   $fdate=empty($f[$c]['date'])?"":date($dateparam,$f[$c]['date']);
   $ftypestr=ftype2str($ftype,$dbh);
   if (strlen($ftitle)) $t="<br>Title:$ftitle"; else $t="";

   $flnk.="<div title='$divtitle' class='$class' >";
   if ($wantdel) 
     $flnk.="<a title='Remove association. If file is orphaned (nothing links to it), it gets deleted.' ".
	   "href='javascript:delconfirm2(\"[$fid] $fname\",\"$scriptname?action=$action&amp;id=$id&amp;delfid=$fid\");'>".
	 "<img src='images/delete.png'></a> ";
   $flnk.= "<a target=_blank title='Edit File $fid' href='$scriptname?action=editfile&amp;id=$fid'><img  src='images/edit.png'></a>".
	 " <a target=_blank title='Download $fname' href='".$uploaddirwww.$fname."'><img src='images/down.png'></a>".
	 "<br>Type:<b>$ftypestr</b>".
	 "<br>Date:<b>$fdate</b>".
	 "<br>Title:$ftitle\n".
	 "</div>\n ";
  }

  return $flnk;

}

function showremdays($remdays) {
  if (abs($remdays)>360) $remw=sprintf("%.1f",($remdays/360))."yr";
  else if (abs($remdays)>70) $remw=sprintf("%.1f",($remdays/30))."mon";
  else if (strlen($remdays)) $remw="$remdays"."d";
  else $remw="";


  if ($remdays<0) $remw="<span style='color:#F90000'>$remw</span>";
  if ($remdays>0) $remw="<span style='color:green'>$remw</span>";
  if (abs($remdays)>360*20) $remw="";
  
  return $remw;
}

function t($s) {
  global $trans_tbl,$settings,$trans_showmissing,$scriptdir;
  $lang=$settings['lang'];

  if ($lang=="en") return $s;
 
  if (isset($trans_tbl[$lang][$s]) && strlen(trim($trans_tbl[$lang][$s]))) {
    return $trans_tbl[$lang][$s];
  }
  else {
    if ($trans_showmissing) {
      $fn="$scriptdir/translations/{$lang}.missing.txt";
      file_put_contents($fn,$s."\n",FILE_APPEND);
    }
    return $s;
  }

}

function te($s) {
  echo t($s);
}

function read_trans($lang) {
  global $trans_tbl,$scriptdir;
  $row = 0;
  if ($lang=="en") return;
  $fn="$scriptdir/translations/$lang.txt";
  if (is_readable($fn) && (($handle = fopen($fn, "r")) !== FALSE)) {
      while (($data = fgetcsv($handle, 1000, "#")) !== FALSE) {
	  $num = count($data);
	  $row++;
	  if ($num<2)  continue;
	  if ($num>2)  echo "<p style='display:inline;background-color:white;color:red'> Error in $fn, row $row: ($num fields found, 2 expected) <br /></p>\n";
	  $trans_tbl[$lang][$data[0]]=$data[1];
      }
      fclose($handle);
  }
  else {
    echo "<p style='display:inline;background-color:white;color:red'> Error openning $fn <br /></p>\n";
  }
}

/* return h/w manufacturers agent */
function getagenthwmanufacturers() {
  global $dbh;

  $sql="select * from agents where type&4";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetchAll(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $agents=$r;

  return $r;
}


function gethwmanufacturerbyname ($name) {
  global $dbh;

	$name=trim($name);
	$sql="select * from agents where type&4 AND title like '%$name%' ";
	$sth=db_execute($dbh,$sql);
	$r=$sth->fetchAll(PDO::FETCH_ASSOC);
	$sth->closeCursor();

	if (!count($r)) return 0;
	else return $r;
}


function getuseridbyname ($name) {
  global $dbh;

	$name=trim(strtolower($name));
	if (!strlen($name))
		return 0;
	$sql="select id from users where LOWER(username) ='$name' ";
	$sth=db_execute($dbh,$sql);
	$r=$sth->fetch(PDO::FETCH_ASSOC);
	$sth->closeCursor();

	if (!count($r)) return 0;
	else return $r['id'];
}

function getagentidbyname ($name) {
  global $dbh;

	$name=trim(strtolower($name));
	if (!strlen($name))
		return 0;
	$sql="select id from agents where LOWER(title) ='$name' ";
	$sth=db_execute($dbh,$sql);
	$r=$sth->fetch(PDO::FETCH_ASSOC);
	$sth->closeCursor();

	if (!count($r)) return 0;
	else return $r['id'];
}

function getitemtypeidbyname ($name) {
  global $dbh;

	$name=trim(strtolower($name));
	if (!strlen($name))
		return 0;
	$sql="select id from itemtypes where LOWER(typedesc) ='$name' ";
	$sth=db_execute($dbh,$sql);
	$r=$sth->fetch(PDO::FETCH_ASSOC);
	$sth->closeCursor();

	if (!count($r)) return 0;
	else return $r['id'];
}


function getstatustypeidbyname ($name) {
  global $dbh;

	$name=trim(strtolower($name));
	if (!strlen($name))
		return 0;
	$sql="select id from statustypes where LOWER(statusdesc) ='$name' ";
	$sth=db_execute($dbh,$sql);
	$r=$sth->fetch(PDO::FETCH_ASSOC);
	$sth->closeCursor();

	if (!count($r)) return 0;
	else return $r['id'];
}

function getlocidsbynames ($locname,$areaname) {
  global $dbh;

	$locname=trim(strtolower($locname));
	$areaname=trim(strtolower($areaname));
	if (!strlen($locname) || (!strlen($areaname)))
		return 0;
	$sql="select locations.id as locid, locareas.id as locareaid from locations,locareas ".
	" WHERE locareas.locationid=locations.id AND ".
	" LOWER(locareas.areaname) ='$areaname' AND ".
	" LOWER(locations.name) ='$locname' ";

	$sth=db_execute($dbh,$sql);
	$r=$sth->fetch(PDO::FETCH_ASSOC);
	$sth->closeCursor();

	if (!count($r)) return 0;
	else return $r;
}




function getuserbyname ($name) {
  global $dbh;

	$name=trim(strtolower($name));
	$sql="select * from users where lower(username) = '$name' ";
	$sth=db_execute($dbh,$sql);
	$r=$sth->fetchAll(PDO::FETCH_ASSOC);
	$sth->closeCursor();

	if (!count($r)) return 0;
	else return $r;
}



/* return URL of an agent which has a specific $tag description. E.g. "Service Tag" */
function getagenturlbytag($agenturl,$tagstr) {
  global $dbh;

  $sql="SELECT urls from agents where id='$agenturl'";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $urls=$r['urls'];

  $allurls=explode("|",$urls);
  for ($i=0;$i<count($allurls);$i++) {
    $row=explode("#",$allurls[$i]);
    $description=$row[0];
    $url=urldecode($row[1]);
    if (stristr($description,$tagstr))
    return $url;
  }
  return "";
}


function dbversion() {
  global $dbh;

  $sql="SELECT * from settings";
  $sth=db_execute($dbh,$sql);
  $r=$sth->fetch(PDO::FETCH_ASSOC);
  $sth->closeCursor();
  $ver=$r['dbversion'];
  return $ver;
}

?>
