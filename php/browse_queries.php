<?php 
include('../init.php');

// 2010 sivann

if (isset($_REQUEST['id'])) $id=$_REQUEST['id']; else $id="";
$id=$_REQUEST['id'];

if ("$id"=="showusers") {
      showlist1("users","username");
}
elseif ($id=="itemtypes") {
      showlist1("itemtypes","typedesc");
}

elseif ($id=="showagents") {
      //showlist1("agents","title");
?>
	<li id='agents:items' class='jstree-closed'><?php te("H/W Manufacturers");?></li>
	<li id='agents:software' class='jstree-closed'><?php te("S/W Manufacturers");?></li>
	<li id='agents:vendors' class='jstree-closed'><?php te("Vendors");?></li>
	<li id='agents:buyers' class='jstree-closed'><?php te("Buyers");?></li>
<?php
}
elseif (strstr($id,"users:")) {
      $x=explode(":",$id);
      $user_id=$x[1];
      $sql="select items.id,agents.title || ' ' || items.model  || ' [' || itemtypes.typedesc || ', ID:' || items.id || ']' ".
           " AS nodetext from items,agents,itemtypes ".
           " WHERE  items.itemtypeid=itemtypes.id AND userid=$user_id AND agents.id=items.manufacturerid ORDER BY agents.title";
      showlist2($sql,"items","jstree-leaf","$wscriptdir/index.php?action=edititem&id=");
}

elseif (strstr($id,"itemtypes:")) {
      $x=explode(":",$id);
      $type_id=$x[1];
      $sql="select items.id,agents.title || ' ' || items.model || ' [' || itemtypes.typedesc || ', ID:' || items.id || ']' ".
           " as nodetext FROM items,agents,itemtypes ".
           " WHERE  items.itemtypeid=itemtypes.id AND agents.id=items.manufacturerid ".
           " AND items.itemtypeid=$type_id ORDER BY agents.title";
      showlist2($sql,"items","jstree-leaf","$wscriptdir/index.php?action=edititem&id=");
}

elseif (strstr($id,"agents:items")) {
      $sql="SELECT id,agents.title as nodetext  FROM agents WHERE type&8 order by nodetext";
      showlist2($sql,"agenthw","jstree-closed","$wscriptdir/index.php?action=editagent&id=");
}
elseif (strstr($id,"agenthw:")) {
      $x=explode(":",$id);
      $agent_id=$x[1];
      $sql="select items.id,items.model || ' [' || itemtypes.typedesc || ', ID:' || items.id ||']' as nodetext FROM items,agents,itemtypes ".
           " WHERE agents.id=items.manufacturerid AND agents.id=$agent_id AND items.itemtypeid=itemtypes.id ORDER BY nodetext";
      showlist2($sql,"items","jstree-leaf","$wscriptdir/index.php?action=edititem&id=");
}

elseif (strstr($id,"agents:software")) {
      $sql="SELECT id,agents.title as nodetext  FROM agents WHERE type&2 order by nodetext";
      showlist2($sql,"agentsw","jstree-closed","$wscriptdir/index.php?action=editagent&id=");
}
elseif (strstr($id,"agentsw:")) {
      $x=explode(":",$id);
      $agent_id=$x[1];
      $sql="SELECT software.id,software.stitle || ' ' || software.sversion AS nodetext FROM software WHERE manufacturerid='$agent_id'";
      showlist2($sql,"software","jstree-leaf","$wscriptdir/index.php?action=editsoftware&id=");
}


elseif (strstr($id,"agents:vendors")) {
      $sql="SELECT id,agents.title as nodetext  FROM agents WHERE type&4 order by nodetext";
      showlist2($sql,"agentvendor","jstree-closed","$wscriptdir/index.php?action=editagent&id=");
}

elseif (strstr($id,"agents:contractors")) {
      $sql="SELECT id,agents.title as nodetext  FROM agents WHERE type&16 order by nodetext";
      showlist2($sql,"agentcontractor","jstree-closed","$wscriptdir/index.php?action=editagent&id=");
}


elseif (strstr($id,"agentvendor:")) {
      $x=explode(":",$id);
      $agent_id=$x[1];
      $sql="SELECT invoices.id, invoices.number || ' ' || date(invoices.date,'unixepoch') as nodetext ".
           "FROM invoices WHERE vendorid='$agent_id' ORDER BY invoices.date";
      showlist2($sql,"invoice","jstree-leaf","$wscriptdir/index.php?action=editinvoice&id=");
}
elseif (strstr($id,"agentcontractor:")) {
      $x=explode(":",$id);
      $agent_id=$x[1];
      $sql="SELECT contracts.id, contracts.number || ' ' || date(contracts.startdate,'unixepoch') as nodetext ".
           "FROM contracts WHERE contractorid='$agent_id' ORDER BY contracts.startdate";
      showlist2($sql,"contract","jstree-leaf","$wscriptdir/index.php?action=editcontract&id=");
}

elseif (strstr($id,"agents:buyers")) {
      $sql="SELECT id,agents.title as nodetext  FROM agents WHERE type&1 order by nodetext";
      showlist2($sql,"agentbuyer","jstree-closed","$wscriptdir/index.php?action=editagent&id=");
}
elseif (strstr($id,"agentbuyer:")) {
      $x=explode(":",$id);
      $agent_id=$x[1];
      $sql="SELECT invoices.id, agents.title || ' ' || invoices.number || ' ' || date(invoices.date,'unixepoch') as nodetext ".
           "FROM invoices,agents WHERE invoices.buyerid='$agent_id' AND agents.id=invoices.vendorid ORDER BY invoices.date";
      showlist2($sql,"invoice","jstree-leaf","$wscriptdir/index.php?action=editinvoice&id=");
}
elseif ($id == "0") {
 echo "<li id='itemtypes' class='jstree-closed'>".t("Item Types")."</li>\n";
      echo "<li id='showusers' class='jstree-closed'>".t("Users")."</li>\n";
      echo "<li id='showagents' class='jstree-closed'>".t("Agents")."</li>\n";
}
else {
  echo " <li id='showusers' class='jstree-closed'><a href='#'>unknown id ($id)</a></li>\n";
}

function showlist2($sql,$basetable,$class="jstree-closed",$url="")
{
  global $dbh;


  $sth=db_execute($dbh,$sql);

  while ($r=$sth->fetch(PDO::FETCH_ASSOC))  {
    $id=$r["id"];
    $nodetext=$r["nodetext"];
    if (strlen($url)) {
      $url1=$url."$id";
      echo "<li id='$basetable:$id' class='$class'><a href='$url1'>$nodetext</a></li>\n";
    }
    else {
      echo "<li id='$basetable:$id' class='$class'>$nodetext</li>\n";
    }
  }
}



function showlist1($table,$colname,$class="jstree-closed",$where = "",$url="")
{
  global $dbh;

  $sql="SELECT ".$table.".id, $colname FROM $table ";
  if (strlen($where)) $sql.=" WHERE $where";
  $sql.=" ORDER BY $colname";

  $sth=db_execute($dbh,$sql);

  while ($r=$sth->fetch(PDO::FETCH_ASSOC))  {
    $v=$r[$colname];
    $id=$r["id"];

    if (strlen($url)) {
      $url1=$url."$id";
      echo "<li id='$table:$id' class='$class'><a href='$url1'>$v</a></li>\n";
    }
    else 
      echo "<li id='$table:$id' class='$class'>$v</li>\n";
  }
}

?>
