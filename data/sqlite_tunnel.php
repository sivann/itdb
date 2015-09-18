<?php
//------------------------------------------------------------------------------
//	
// PHP Connection Script for SQLite
// (C) 2002-2014 SQL Maestro Group. All rights reserved.
//
//------------------------------------------------------------------------------

error_reporting(E_ALL);
ini_set('display_errors', '1');


header("Expires: Tue, 01 Jan 1980 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: private, no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$majorVersion = "1";
$minorVersion = "0";

$action = @$_GET["action"];
$host = @$_GET["host"];
$uid = @$_GET["user"];
$pwd = @$_GET["password"];
$port = @$_GET["port"];
$dbname = @$_GET["db"];

if (!get_magic_quotes_gpc())
    $dbname = addslashes($dbname);

$encoding = @$_GET["encoding"];
$quotechr = @$_GET["quotechr"];

if (!get_magic_quotes_gpc())
    $quotechr = addslashes($quotechr);

$sql = @$_GET["sql"];
if ($sql == "")
    $sql = @$_POST["sql"];

if (get_magic_quotes_gpc())
    $sql = stripslashes($sql);

$dblistError = "";
$dblist	= "<input class=\"inputText\" style=\"width:150\" onkeyup=\"CheckNotEmpty()\" type=\"text\" name=\"db\">";
$tablelist = "";
$testConnectionResult = "";
$tableListError = "";

function cdata($body, $masking = false)
{
    if ($masking)
        return "<![CDATA[" . str_replace("]]>", "<\$SQLMAESTRO_DOUBLE_BRACKETS\$>", $body) . "]]>";
    else
        return "<![CDATA[" . $body . "]]>";
}


$sqlListDatabases = "";
$sqlListTables = "SELECT name AS name, sql AS definition FROM sqlite_master WHERE (type = 'table') AND (name <> 'sqlite_sequence') ORDER BY name";

$sqliteerror = "";

function db_close($connectionHandle)
{
    sqlite_close($connectionHandle);
}

function db_connect($host, $port, $username, $password)
{
    return false;
}

function db_connect_database($host, $port, $username, $password, $database)
{
    global $sqliteerror;
    $connection = @sqlite_open($database, 0666, $sqliteerror);
    if ($connection)
    {
        $attachedDbIndex = 1;
        while(true)
        {
            if (isset($_POST['attachdb_name_' . $attachedDbIndex]) && isset($_POST['attachdb_alias_' . $attachedDbIndex]))
            {
                $attachStatement = sprintf('ATTACH DATABASE \'%s\' AS %s',  $_POST['attachdb_name_' . $attachedDbIndex], $_POST['attachdb_alias_' . $attachedDbIndex]);
                if (!@sqlite_query($connection, $attachStatement))
                {
                    global $sqliteerror;
                    $sqliteerror = 'Attach failed. "' . $attachStatement . '": ' . sqlite_error_string(sqlite_last_error($connection)) ;
                    return null;
                }
            }
            else
                break;
            $attachedDbIndex++;
        }
    }
    return $connection;
}

function fetchResult($connectionHandle, $sql)
{
    return @sqlite_unbuffered_query($connectionHandle, $sql);
}

function fetchRow($resultHandle)
{
    $result = @sqlite_current($resultHandle, SQLITE_NUM);
    return $result;
}

function getResultNumRows($resultHandle)
{
    if (@sqlite_current($resultHandle, SQLITE_NUM))
        return 1; 
    else
        return 0;
}

function hasRows($resultHandle)
{
    return @sqlite_has_more($resultHandle);
}

function getNeedNextRow()
{
    return true;
}

function getResultNumColumns($resultHandle)
{
    return @sqlite_num_fields($resultHandle);
}

function getError($connectionHandle)
{
    global $sqliteerror;
    if (! empty($sqliteerror))
        return $sqliteerror; 
    else
    {
        if ($connectionHandle)
            return sqlite_error_string(sqlite_last_error($connectionHandle)); 
        else
            return "Could not connect to database";
    }
}

function setServerEncoding($connectionHandle, $encoding)
{}

function getNeedHost()
{
    return false;
}

function getNeedIdentification()
{
    return false;
}

function getNeedPort()
{
    return false;
}

function db_next($queryResult)
{
    @sqlite_next($queryResult);
}

function db_row_affected($connectionHandle, $queryResult)
{
    return @sqlite_changes($chandle);
}

function db_last_insert_id($connectionHandle)
{
    return @sqlite_last_insert_rowid($mysqlHandle);
}

function getSQLiteVersion($connectionHandle)
{
    return sqlite_libversion();
}

function getServerVersion($connectionHandle)
{
    return getSQLiteVersion($connectionHandle);
}

function getColumnMetadata($resultHandle, $columnOffset)
{
    $result ["Name"] = cdata(sqlite_field_name($resultHandle, $columnOffset));
    $result ["Length"] = "8000";
    $result ["Type"] = "string";
    return $result;
}

function getRowColumn($resultHandle, $row, $columnOffset)
{
    $result ["isNull"] = is_null($row [$columnOffset]) ? "True" : "False";
    $result ["Value"] = cdata($row [$columnOffset], true);
    return $result;
}

function getCanGetDatabases()
{
    return false;
}


function getDefaultPort()
{
	global $defaultPort;
		
	return $defaultPort; 
}

function WriteCustomTag($tag, $value)
{
    if (!isset($value))
        echo "<" . $tag . "/>";
    else
        echo "<" . $tag . ">" . $value . "</" . $tag . ">";
}

function BeginCustomTag($tag)
{
    echo "<" . $tag . ">";
}

function EndCustomTag($tag)
{
    echo "</" . $tag . ">";
}

function WriteAdditionalParams($connection)
{
    if (function_exists('db_get_additional_params'))
    {
        $params = db_get_additional_params($connection);
        foreach($params as $paramName => $paramValue)
            WriteCustomTag($paramName, $paramValue);
    }
}

function WriteScriptVersion()
{
    global $majorVersion;
    global $minorVersion;

    BeginCustomTag("ScriptVersion");
    WriteCustomTag("Major", $majorVersion);
    WriteCustomTag("Minor", $minorVersion);
    EndCustomTag("ScriptVersion");
}

function WhiteXmlHeader($encoding)
{
    header("Content-type: text/xml");
    echo  "<?xml version=\"1.0\"";
    echo  " encoding=\"" . $encoding . "\"";
    echo " ?>";
}

function WriteError($message)
{
    BeginCustomTag("ErrorMessage");
    echo $message;
    EndCustomTag("ErrorMessage");
}

function BeginColumn()
{ 
    BeginCustomTag("Column");
}
function EndColumn()
{ 
    EndCustomTag("Column");
}

function BeginMaestroResult($encoding = "")
{
    echo "<MaestroResult";
    if (!empty($encoding))
        echo " encoding=\"" . $encoding . "\"";
    echo ">";
}

function EndMaestroResult()
{ 
    EndCustomTag("MaestroResult");
}

function BeginResult()
{ 
    BeginCustomTag("Result");
}
function EndResult()
{ 
    EndCustomTag("Result");
}

function WriteServerVerion($version)
{
    BeginCustomTag("ServerVersion");
    echo $version;
    EndCustomTag("ServerVersion");
}

function WriteRowsAffected($rowsAffected)
{
    BeginCustomTag("RowsAffected");
    echo $rowsAffected;
    EndCustomTag("RowsAffected");
}

function WriteLastInsertId($lastInsertId)
{
    BeginCustomTag("LastInsertedId");
    echo $lastInsertId;
    EndCustomTag("LastInsertedId");
}

function BeginColumns()
{ 
    BeginCustomTag("Columns");
}
function EndColumns()
{ 
    EndCustomTag("Columns");
}
function BeginRows()
{ 
    BeginCustomTag("Rows");
}
function EndRows()
{ 
    EndCustomTag("Rows");
}
function BeginRow()
{ 
    BeginCustomTag("Row");
}
function EndRow()
{ 
    EndCustomTag("Row");
}

function showColumnMetadata($resultHandle, $columnOffset)
{
    BeginColumn();
    $columnMetadata = getColumnMetadata($resultHandle, $columnOffset);
    foreach($columnMetadata as $key => $value)
        WriteCustomTag($key, $value);
    EndColumn();
}

function ShowResultMetadata($connectionHandle, $queryResult)
{
    $numFields = 0;
    $numFields = getResultNumColumns($queryResult);

    if ($numFields > 0)
    {
        BeginColumns();
        for ($i = 0; $i < $numFields; $i++)
            showColumnMetadata($queryResult, $i);
        EndColumns();
    }
}

function ShowRow($queryResult, $row)
{
    BeginRow();
    for ($i = 0; $i < getResultNumColumns($queryResult); $i++)
    {
        $rowColumn = getRowColumn($queryResult, $row, $i);
        echo "<Column ";
        foreach($rowColumn as $key => $value)
        {
            if ($key == "Value")
                $rowValue = $value;
            else
                echo $key . "=\"" . $value . "\" ";
        }
        echo ">";
        echo $rowValue;
        echo "</Column>";
    }
    EndRow();
}

function ShowResult($connectionHandle, $queryResult)
{
    BeginRows();
    if (hasRows($queryResult) > 0)
        while ($row = fetchRow($queryResult))
        {
            ShowRow($queryResult, $row);
            if (getNeedNextRow())
                db_next($queryResult);
        }
    EndRows();
}

function ExecuteSql($host, $port, $uid, $pwd, $dbname, $encoding, $sql)
{
    $connectionHandle = db_connect_database($host, $port, $uid, $pwd, $dbname);

    WhiteXmlHeader("unknown");

    if (!$connectionHandle)
    {
        BeginMaestroResult();
        WriteScriptVersion();
        WriteError(getError($connectionHandle));
        EndMaestroResult();
    }
    else
    {
        BeginMaestroResult($encoding);
        WriteAdditionalParams($connectionHandle);
        WriteScriptVersion();
        WriteServerVerion( getServerVersion($connectionHandle) );

        setServerEncoding($connectionHandle, $encoding);

        $queryResult = fetchResult($connectionHandle, $sql);

        if ($queryResult)
        {
            BeginResult();

            ShowResultMetadata($connectionHandle, $queryResult);
            ShowResult($connectionHandle, $queryResult);

            WriteRowsAffected(db_row_affected($connectionHandle, $queryResult));
            WriteLastInsertId(db_last_insert_id($connectionHandle));

            EndResult();
        }
        else
        {
            WriteError(getError($connectionHandle));
        }
        EndMaestroResult();
    }
}

function getDatabasesList($connectionHandle, &$databasesNumber, &$errorMessage)
{
    global $sqlListDatabases;

    $databasesNumber = 0;
    $pDB = fetchResult($connectionHandle, $sqlListDatabases);
    $databasesNumber = getResultNumRows($pDB);
    if (!$pDB)
        $errorMessage = getError($connectionHandle);
    else
        return $pDB;
}

function listDatabases($connectionHandle)
{
    $databasesNumber = 0;
    $errorMessage = "";
    $databases = getDatabasesList($connectionHandle, $databasesNumber, $errorMessage);
    if ($databases && ($databasesNumber > 0))
    {
        $result = "";
        for($i = 0; $i < $databasesNumber; $i++)
        {
            $database = fetchRow($databases);
            $result .= "<option value=\"" . htmlspecialchars($database[0]) . "\">". htmlspecialchars($database[0]) . "</option>";
            if (getNeedNextRow())
                db_next($databases);
        }
        $result = "<select class=\"inputText\" style=\"width:150\"  name=\"db\">" . $result . "</select>";
    }
    else // no SHOW DATABASES privilege

    {
        $result = "<input class=\"inputText\" onkeyup=\"CheckNotEmpty()\" style=\"width:150\" type=\"text\" name=\"db\">";
    }
    return $result;
}

function getTableList($connectionHandle, &$errorMessage)
{
    global $sqlListTables;

    $databasesNumber = 0;
    $pDB = fetchResult($connectionHandle, $sqlListTables);
    if (!$pDB)
        $errorMessage = getError($connectionHandle);
    else
        return $pDB;
}

function listTables($connectionHandle, &$errorMessage)
{
    $tables = getTableList($connectionHandle, $errorMessage);

    $result = "";
    if ($tables)
    {
        while ($table = fetchRow($tables))
        {
            $result .= "<tr><td>" . $table[0] . "<td></tr>";
            if (getNeedNextRow())
                db_next($tables);
        }
        $result = "<table>" . $result . "</table>";
    }
    else
    {
        return false;
    }
    return $result;
}

function ShowDatabases($host, $port, $uid, $pwd, &$dblist, &$dblistError)
{
    $connectionHandle = db_connect($host, $port, $uid, $pwd);
    if (!$connectionHandle)
    {
        $dblistError = getError($connectionHandle);
        $dblist = listDatabases($connectionHandle);
    }
    else
    {
        $dblist = listDatabases($connectionHandle);
        db_close($connectionHandle);
    }
}

function ShowTables($host, $port, $uid, $pwd, $dbname, &$tablelist)
{
    global $tableListError;

    $connectionHandle = db_connect_database($host, $port, $uid, $pwd, $dbname);
    if (!$connectionHandle)
    {
        $tableListError = "<p class=\"unsuccessful_info\">Could not retrieve table list from <b>" . $dbname . "</b>: " . getError($connectionHandle) . "</p>";
    }
    else
    {
        $errorMessage = "";
        $tablelist = listTables($connectionHandle, $errorMessage);
        if (!$tablelist)
            $tableListError = "<p class=\"unsuccessful_info\">Could not retrieve table list from <b>" . $dbname . "</b>: " . $errorMessage . "</p>";
        db_close($connectionHandle);
    }
}


function GuiTestConnection($host, $port, $uid, $pwd, $dbname, &$dblist, &$dblistError, &$testConnectionResult)
{
    $testConnectionResult = "";
    if (getCanGetDatabases())
    {
        $connectionHandle = db_connect($host, $port, $uid, $pwd);
        //$dblist = listDatabases($connectionHandle);
        //if ($connectionHandle)
        //	db_close($connectionHandle);
    }

    if (!empty($dbname))
    {
        $connectionHandle = db_connect_database($host, $port, $uid, $pwd, $dbname);
        if ($connectionHandle)
        {
            $testConnectionResult = "<p class=\"successful_info\">Connection to <b>" . $dbname . "</b> successful</p>" .
                "<p>Server version: " . getServerVersion($connectionHandle) . "</p>";
            db_close($connectionHandle);
        }
        else
        {
            $testConnectionResult = "<p class=\"unsuccessful_info\">Connection to <b>" . $dbname . "</b> failed: " . getError($connectionHandle) . "</p>";
        }
    }
}

function TestConnect($host, $port, $uid, $pwd, $encoding, $database = "")
{
    if (getCanGetDatabases())
        $connectionHandle = db_connect($host, $port, $uid, $pwd);
    else
        $connectionHandle = db_connect_database($host, $port, $uid, $pwd, 	$database);

    WhiteXmlHeader($encoding);
    BeginMaestroResult();
    WriteAdditionalParams($connectionHandle);
    WriteScriptVersion();
    if ($connectionHandle)
        WriteServerVerion( getServerVersion($connectionHandle) );
    else
        WriteError(getError($connectionHandle));
    EndMaestroResult();
}

$hostMessage = "";
$uidMessage = "";
$dbnameMessage = "";

switch($action)
{
    case "execsql":
        ExecuteSql($host, $port, $uid, $pwd, $dbname, $encoding, $sql);
        exit();
        break;
    case "testconnect":
        if (getCanGetDatabases())
            TestConnect($host, $port, $uid, $pwd, $encoding);
        else
            TestConnect($host, $port, $uid, $pwd, $encoding, $dbname);
        exit();
        break;
    case "showdatabases":
        if (getCanGetDatabases())
        {
            if (empty($host) || empty($uid))
            {
                if (empty($host))
                    $hostMessage = " style=\"background-color:#FFAAAA;\"";
                if (empty($uid))
                    $uidMessage = " style=\"background-color:#FFAAAA;\"";
            }
            else
                ShowDatabases($host, $port, $uid, $pwd, $dblist, $dblistError);
        }
        break;
    case "showtables":
        if ((getNeedHost() && empty($host)) || (getNeedIdentification() && empty($uid)) || empty($dbname))
        {
            if (empty($host))
                $hostMessage = " style=\"background-color:#FFAAAA;\"";
            if (empty($uid))
                $uidMessage = " style=\"background-color:#FFAAAA;\"";
            if (empty($dbname))
                $dbnameMessage = "background-color:#FFAAAA;";
        }
        else
        {
            //ShowDatabases($host, $port, $uid, $pwd, $dblist, $dblistError);
            ShowTables($host, $port, $uid, $pwd, $dbname, $tablelist);
        }
        break;
    case "guitestconnection":
        if ( (getNeedHost() && empty($host)) || (getNeedIdentification() && empty($uid)) || empty($dbname))
        {
            if (empty($host))
                $hostMessage = " style=\"background-color:#FFAAAA;\"";
            if (empty($uid))
                $uidMessage = " style=\"background-color:#FFAAAA;\"";
            if (empty($dbname))
                $dbnameMessage = "background-color:#FFAAAA;";
        }
        else
            GuiTestConnection($host, $port, $uid, $pwd, $dbname, $dblist, $dblistError, $testConnectionResult);
        break;
}
?>

<html>
    <head>
        <title>SQL Maestro Connection Script</title>
        <style type="text/css">
            BODY
            {
                padding:0;
                margin:0;
                background-color: white;
                color:#4C4C4C;
            }
            P { margin:0 0 8px 0; padding:0; }
            BODY, TD, DIV, P { font: normal 11px verdana, tahoma, arial; }
            A:link, A:visited, A:hover, A:active
            {
                color:#3A739A;
                text-decoration: none;
            }
            A.gray:link, A.gray:visited, A.gray:hover, A.gray:active { color:#4C4C4C; }
            A:link IMG, A:visited IMG { border-color:#5F95CB; }
            A.active:link, A.active:visited, A.active:hover, A.active:active {
                color: #4C4C4C;
                font-weight:bold;
            }
            H1
            {
                font:bold 18px Arial;
                margin: 0 0 10px 0;
                color:#538AC5;
            }
            DIV.required { padding:20px 0 10px 0; font-weight:bold; font-size:9px; }
            SPAN.marker { font-weight:bold; font-size:9px; color:#FFA13D; }
            SPAN.redmarker { font-weight:bold; font-size:9px; color:#FF0000; }
            FORM { padding:0; margin:0; }
            INPUT { padding:0; margin:0; }
            INPUT.inputText
            {
                width:100%;
                border: solid 1px #9E9E9E;
                color:#4C4C4C;
            }
            INPUT.submit {
                width:150px;
                height:20px;
                margin:0;
                padding:1px 10px 2px 10px;
                font:bold 11px tahoma;
                color: #4C4C4C;
                background:#DBE4ED;
                border:solid 1px #6BA8D1;
            }
            .successful_info
            {
                color: green;
            }
            .unsuccessful_info
            {
                color: red;
            }
        </style>

        <script type="text/javascript">
            function CheckNotEmpty(value)
            {
                var controls = [
<?php
echo "\"host\", ";
echo "\"user\", ";
echo "\"db\"";
?>];
                        var buttons = [
                        <?php
echo "\"btntest\", ";
echo "\"showtables\"";
?>];
                            for (i = 0; i < controls.length; i++)
                            {
                                if (document.getElementById(controls[i]).getAttribute("value") == "")
                                {
                                    for (j = 0; j < buttons.length; j++)
                                        document.getElementById(buttons[j]).setAttribute("disabled", "disabled");
                                    return;
                                }
                            }
                            for (j = 0; j < buttons.length; j++)
                                document.getElementById(buttons[j]).setAttribute("disabled", "");
                        }
        </script>

    </head>
    <body onLoad="CheckNotEmpty()">
        <div align="center">

            <br />
            <br />
            <div style="width:740px;" align="left"><h1>Connection Script</h1></div>

            <div style="width:740px;" align="center" >
                <form>
                    <input type="hidden" name="action" value="">
                    <div class="required" align="left">Fields marked by <span class="marker">*</span> are required.</div>
                    <table cellspacing="3" cellpadding="7" border="0" width="100%" style="border:solid 1px #6BA8D1;">
                                <?php if (getNeedHost())
                                { ?>
                        <tr>
                            <td nowrap align="right" width="250"> Host/Server name (or IP) <span class="marker">*</span>:</td>
                            <td>
                                <input type="text" name="host" class="inputText" onKeyUp="CheckNotEmpty()" <?php echo $hostMessage ?> value="<?php echo $host ?>">

                            </td>
                        </tr>
                                    <?php } ?>
                                <?php if (getNeedIdentification())
                                { ?>
                        <tr>
                            <td nowrap align="right" >User <span class="marker">*</span>:</td>
                            <td><input type="text" name="user" class="inputText" onKeyUp="CheckNotEmpty()" <?php echo $uidMessage ?> value="<?php echo @$uid ?>">

                            </td>
                        </tr>
                        <tr>
                            <td nowrap align="right" >Password:</td>
                            <td><input type="password" name="password" class="inputText" value="<?php echo @$pwd ?>">
                            </td>
                        </tr>
                                    <?php } ?>
                                <?php if (getNeedPort())
                                { ?>
                        <tr>
                            <td nowrap align="right" >Port (if not <?php echo getDefaultPort() ?>):</td>
                            <td><input type="text" name="port" class="inputText" value="<?php echo @$port ?>">
                            </td>
                        </tr>
    <?php } ?>
                        <tr>
                            <td nowrap align="right" >Database <span class="marker">*</span>:</td>
                            <td><?php
function CreatePlainDbListControl($color = "", $checkFunction = "", $value = "")
{
    /*return "<input type=\"text\" name=\"db\" class=\"inputText\" " .
				 "style=\"width:150\;" . (empty($color) ? "" : "background-color:" . $color . ";") . "\" " .
				 empty($checkFunction) ? "" : "onkeyup=\"" . $checkFunction . "\" " .
				 "value=\"" . $value . "\">";*/
}
if (empty($dblistError))
{
    if (($action == "guitestconnection") && !empty($dbname))
    {
        echo "<input type=\"text\" name=\"db\" class=\"inputText\" onkeyup=\"CheckNotEmpty()\" style=\"width:150;\" value=\"" . $dbname . "\">";
    }
    else if (($action == "showtables"))
                            {
        echo "<input type=\"text\" name=\"db\" class=\"inputText\" onkeyup=\"CheckNotEmpty()\" style=\"width:150;" . $dbnameMessage . "\"  value=\"" . $dbname . "\">";
    }
    else if ($action == "showdatabases")
    {
                                echo $dblist;
                            }
    else
    {
        echo "<input class=\"inputText\" style=\"width:150;". $dbnameMessage . "\" onkeyup=\"CheckNotEmpty()\" type=\"text\" name=\"db\">";
    }
                        }
else
    echo $dblist;
                        ?>
                                &nbsp;
<?php
if (getCanGetDatabases())
{
                                    ?>
                                <input type="button" name="btndb" class="submit" value="Get Database List" onClick="this.form.action.value='showdatabases';this.form.submit();">
    <?php
                        }
?>
                            </td>
                        </tr>

<?php if (!empty($dblistError))
{ ?>
                        <tr>
                            <td></td>
                            <td><font color="red"><?php echo $dblistError ?></font></td>
                        </tr>
    <?php } ?>

                        <tr>
                            <td></td>
                            <td>
                                <input type="button" name="btntest" class="submit" value="Test Connection" onClick="this.form.action.value='guitestconnection';this.form.submit();">
                                &nbsp;
                                <input type="button" name="showtables" class="submit" value="ShowTables" onClick="this.form.action.value='showtables';this.form.submit();">
                            </td>
                        </tr>

<?php if (!empty($testConnectionResult))
{ ?>
                        <tr>
                            <td></td>
                            <td><?php echo $testConnectionResult ?></td>
                        </tr>
    <?php } ?>
<?php if (!empty($tableListError))
{ ?>
                        <tr>
                            <td></td>
                            <td><?php echo $tableListError  ?></td>
                        </tr>
    <?php } ?>


<?php if (!empty($tablelist))
{ ?>
                        <tr>
                            <td></td>
                            <td>
                                <b>Table List</b><br />
    <?php echo $tablelist ?>
                            </td>
                        </tr>
    <?php } ?>

                    </table>
                </form>
            </div>
            <div style="width:700px;" align="right">
                &copy; 2002-2014 <a href="http://www.sqlmaestro.com/">SQL Maestro Group</a>
            </div>
        </div>
    </body>
</html>
