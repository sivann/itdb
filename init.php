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
$serverport=$_SERVER['SERVER_PORT'];

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
require_once('functions.php');
require_once('model.php');

if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == "on"))
  $prot="https" ;
else
  $prot="http";

$fscriptname="$prot://$servername:$serverport$scriptname";
$fuploaddirwww="$prot://$servername:$serverport".dirname($scriptname)."/$uploaddirwww";

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

  if (!is_writable("$scriptdir/tcpdf/cache")) {
    echo "$scriptdir is not writeable by apache<br>";
    echo "<b><big>make $scriptdir/tcpdf/cache writeable by the user running the web server</big></b><br>";
    echo "in unix:<br><pre> chown -R $procusername $scriptdir/tcpdf/cache; chmod u+w $scriptdir/tcpdf/cache/</pre>";
    exit;
  }


  if (!is_writable($uploaddir)) {
    echo "$scriptdir is not writeable by apache<br>";
    echo "<b><big>make $uploaddir writeable by the user running the web server</big></b><br>";
    echo "in unix: <br><pre>chown $procusername $uploaddir; chmod u+w $uploaddir</pre>";
    exit;
  }

  if (!is_writable("$scriptdir/translations")) {
    echo "$scriptdir/translations is not writeable by apache<br>";
    echo "<b><big>make $uploaddir writeable by the user running the web server</big></b><br>";
    echo "in unix: <br><pre>chown $procusername $scriptdir/translations; chmod u+w $scriptdir/translations</pre>";
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
//$ret = $dbh->exec("PRAGMA foreign_keys = ON");


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


///////////cookies///////////
$authstatus=0;
$authmsg="Not logged in";
if (!$demomode ) {
  if (isset($_POST['logout'])) {
     setcookie("itdbcookie1",'', time()+3600*1,$wscriptdir);
     header("Location: $scriptname"); //eat get parameters
  }
  elseif (isset($_POST['authusername'])){ //logging in
       $username=$_POST['authusername'];
       $password=$_POST['authpassword'];

        if ($settings['useldap'] && $username != 'admin') {
            $r=connect_to_ldap_server($settings['ldap_server'],$username,$password,$settings['ldap_dn']);
            echo "HERE. r=".var_dump($r)."\n";
            if ($r == false) {
                $authstatus=0;
                $authmsg="Wrong Password";
            }
            else {
                 $rnd=mt_rand(); //create a random
                 $u=getuserbyname($username);
                 if ($u==-1) { //user not found, it's an LDAP user, add him
                     db_execute2($dbh,
                         "INSERT into users (username,cookie1,usertype) values (:username,:cookie1,:usertype)",
                         array('username'=>$username,'cookie1'=>$rnd,'usertype'=>2));
                 }
                 db_exec($dbh,"UPDATE users set cookie1='$rnd' where username='$username'",1,1);
                 setcookie("itdbcookie1",$rnd, time()+3600*24*2,$wscriptdir); //random number set for two days
                 setcookie("itdbuser",$username, time()+3600*24*60,$wscriptdir); //username
                 $authstatus=1;
                 $authmsg="User Authenticated";
            }
        }

        if (!$authstatus) { //try local users
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
elseif ($demomode) { //demomode
  $authstatus=1;
  $authmsg="Demo mode, no save possible";
}

?>
