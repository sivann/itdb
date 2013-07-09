<?php 
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

function opendb($dbfile) {
    global $dbh;
    //open db
    try {
      $dbh = new PDO("sqlite:$dbfile");
    } 
    catch (PDOException $e) {
      print "Open database Error!: " . $e->getMessage() . "<br>";
      die();
    }
    return $dbh;
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);


    //$ret = $dbh->exec("PRAGMA case_sensitive_like = 0;");

}

function ckdberr($resource) {
    global $errorstr;
    $error = $resource->errorInfo();
    if($error[0] && isset($error[2])) {
        $errorstr= $error[2];
        $errorbt = debug_backtrace();
        logerr($errorstr."   BACKTRACE: ".$errorbt);
        return 1;
    }
    return 0;
}


/*execute with prepared statements
Example:
        $sql="SELECT * from tablename where id=:id order by date";
        $stmt=db_execute($dbh,$sql,array('id'=>$items['id']));
        $res=$stmt->fetch(PDO::FETCH_ASSOC);
*/

function db_execute2($dbh,$sql,$params=NULL) {
    global $errorstr,$errorbt,$errorno;

    $sth = $dbh->prepare($sql);
    $error = $dbh->errorInfo();

    if(((int)$error[0]||(int)$error[1]) && isset($error[2])) {
        $errorstr= "DB Error: ($sql): <br>\n".
            $error[2]."<br>\nParameters:"."params\n";
            //implode(",",$params);
        $errorbt= debug_backtrace();
        $errorno=$error[1]+$error[0];
        logerr("$errorstr BACKTRACE:".$errorbt);
        return 0;
    }

    if (is_array($params))
        $sth->execute($params);
    else
        $sth->execute();

    $error = $sth->errorInfo();
    if(((int)$error[0]||(int)$error[1]) && isset($error[2])) {
        $errorstr= "DB Error: ($sql): <br>\n".$error[2]."<br>\nParameters:".implode(",",$params);
        $errorbt= debug_backtrace();
        $errorno=$error[1]+$error[0];
        logerr("$errorstr BACKTRACE:".$errorbt);
    }

    return $sth;
}



function connect_to_ldap_server($ldap_server,$username,$passwd,$ldap_dn) {
    global $gen_error,$gen_errorstr;

    $ds=ldap_connect($ldap_server);  // must be a valid LDAP server!
    //echo "connect result is " . $ds . "<br />\n";
    if($ds){
        $dn="uid=".$username.",".$ldap_dn;
        echo $dn;
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r=ldap_bind($ds,$dn, $passwd);
        if(!$r){
            $gen_errorstr="ldap_bind: ".ldap_error($ds);
            $gen_error=100;
            ldap_close($ds);
            return FALSE;
        }
        return $ds;
    }
    else {
        return FALSE;
    }
}


?>
