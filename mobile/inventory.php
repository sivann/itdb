<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head> 
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
	<link rel='stylesheet' href='inventory.css' type='text/css' media='screen' />
	<meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
	<title>ITDB Mobile View</title>
</head>
<body>
	<?php
	// ID aus URL auslesen und in Kombi mit Apache Vhost.d Rewrite Rule die Seite hier laden
	// English: Get ID from Apache and display this page after apache vhost.d rewrite rule was applied
	$id = $_GET["id"]; 
	
	// Working Apache vhost.d rewrite configuration example:
	// 
	// Filename: vhosts.d/itdb.conf
	//
	// Content:
	// <VirtualHost 1.2.3.4:80>
    //    ServerAdmin admin@your-domain.com
    //    ServerName inventory.your-domain.com
    //    RewriteEngine on
    //    RewriteRule ^/([0-9]+)/?$ http://inventory.your-domain.com/itdb/mobile/inventory.php?id=$1 [NC,L] # Redirect Inventory-Requests
	// </VirtualHost>
	//
	// Example URL: http://inventory.your-domain.com/75
	// The example URL would open this page: http://inventory.your-domain.com/itdb/mobile/inventory.php?id=75
	// and the data for the item with the ID 75 is shown. 

	// SQLite Datenbank readonly öffnen
	// English: Open database, but readonly for sure
	$db = new SQLite3('../data/itdb.db',SQLITE3_OPEN_READONLY);
	
	// Überprüfen ob Datenbank existiert und Infos auslesen + in Tabelle packen
	// English: Check database connection, read info from database and write it to table
	if($db){
        	//$sql = ('SELECT id,sn,sn2,sn3,dnsname,macs,label FROM items WHERE id ='. $id .';');
		$t=time();
        	$sql = ('SELECT id,sn,sn2,sn3,dnsname,macs,label,(purchasedate+warrantymonths*30*24*60*60-' . $t .')/(60*60*24) AS remdays FROM items WHERE id ='. $id .';');
        	$ret = $db->query($sql);
        	while($row = $ret->fetchArray(SQLITE3_ASSOC) ){
		// Garantie umrechnen auf Jahre, Monate, Tage
		// English: Warranty: Get years, months and days
		$remdays=$row['remdays'];
		//Garantie auf Jahre umrechnen
		//English: Get warranty years
		if ($remdays > 360)
		{
			$remdays=sprintf("%.1f",($remdays/360))." years";
		}
		//Garantie auf Monate umrechnen
		//English: Get warranty months
		elseif ($remdays > 70)
		{
			$remdays=sprintf("%.1f",($remdays/30))." months";
		}
		// Garantie auf Tage umrechnen
		//English: Get warranty days
		elseif (strlen($remdays))
		{	
			$remdays="$remdays"." days";
		}	
		// Nichts anzeigen wenn keine Garantie hinterlegt
		//English: If no warranty info is found, show N/A
		else
		{
			$remdays="N/A";
		}
		?>

		<?php // Tabelle bauen mit CSS und Daten ausgeben
		// English: Build table using CSS and fill with data ?>		
		<div class="datagrid"><table>
		<!-- Replace the img src with your own logo if you like -->
		<thead><tr class="alt"><th><img src="../images/itdb.png" alt="" width=32px height=32px align=top />
		</th><th>ITDB Mobile View</th></tr></thead></table></div>
		<br>
		<div class="datagrid"><table>
		<thead><tr><th>Property</th><th>Value</th></tr></thead>
		<tbody>
		<tr><td>Hostname</td><td><?php echo $row['dnsname']?></td></tr>
		<tr><td>Label</td><td><?php echo $row['label']?></td></tr>
		<tr><td>MAC</td><td><?php echo $row['macs']?></td></tr>
		<tr><td>S/N</td><td><?php echo $row['sn']?></td></tr>
		<tr><td>Inventory 1</td><td><?php echo $row['sn2']?></td></tr>
		<tr><td>Inventory 2</td><td><?php echo $row['sn3']?></td></tr>
		<tr><td>Warranty</td><td><?php echo $remdays?></td></tr>
		</tbody>
		<tfoot>
		<tr><td colspan="2"><div id="paging"><ul>
		<li><a href="../index.php?action=edititem&id=<?php echo "$id"; ?>"><span>View full item details in ITDB </span></a></li>
		</ul></div></tr>
		</tfoot>
		</table></div>
		<?php
        	}
	} else {
        	// Fehler anzeigen, wenn mit Datenbank etwas nicht stimmt
        	// English: Show error when database connection failed
        	echo "Error! Check database or connection to database!";
        	echo $db->lastErrorMsg();
	}

	// Datenbankverbindung schließen
	// English: Close Database connection
	$db->close();
	?>

</body>
</html>
