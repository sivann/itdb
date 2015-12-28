<?php
	/* sivann
	 * How to debug:
	 * 1. Firebug, Net, XHR (prefered)
	 * 2. OR try this url: itdb/php/datatables_listitems_ajax.php?sEcho=1&iColumns=20&sColumns=&iDisplayStart=0&iDisplayLength=18&mDataProp_0=0&mDataProp_1=1&mDataProp_2=2&mDataProp_3=3&mDataProp_4=4&mDataProp_5=5&mDataProp_6=6&mDataProp_7=7&mDataProp_8=8&mDataProp_9=9&mDataProp_10=10&mDataProp_11=11&mDataProp_12=12&mDataProp_13=13&mDataProp_14=14&mDataProp_15=15&mDataProp_16=16&mDataProp_17=17&mDataProp_18=18&mDataProp_19=19&sSearch=&bRegex=false&sSearch_0=&bRegex_0=false&bSearchable_0=true&sSearch_1=&bRegex_1=false&bSearchable_1=true&sSearch_2=&bRegex_2=false&bSearchable_2=true&sSearch_3=&bRegex_3=false&bSearchable_3=true&sSearch_4=&bRegex_4=false&bSearchable_4=true&sSearch_5=&bRegex_5=false&bSearchable_5=true&sSearch_6=&bRegex_6=false&bSearchable_6=true&sSearch_7=&bRegex_7=false&bSearchable_7=true&sSearch_8=&bRegex_8=false&bSearchable_8=true&sSearch_9=&bRegex_9=false&bSearchable_9=true&sSearch_10=&bRegex_10=false&bSearchable_10=true&sSearch_11=&bRegex_11=false&bSearchable_11=true&sSearch_12=&bRegex_12=false&bSearchable_12=true&sSearch_13=&bRegex_13=false&bSearchable_13=true&sSearch_14=&bRegex_14=false&bSearchable_14=true&sSearch_15=&bRegex_15=false&bSearchable_15=true&sSearch_16=&bRegex_16=false&bSearchable_16=true&sSearch_17=&bRegex_17=false&bSearchable_17=true&sSearch_18=&bRegex_18=false&bSearchable_18=true&sSearch_19=&bRegex_19=false&bSearchable_19=true&iSortingCols=1&iSortCol_0=0&sSortDir_0=desc&bSortable_0=true&bSortable_1=true&bSortable_2=true&bSortable_3=true&bSortable_4=true&bSortable_5=true&bSortable_6=true&bSortable_7=true&bSortable_8=true&bSortable_9=true&bSortable_10=true&bSortable_11=true&bSortable_12=true&bSortable_13=true&bSortable_14=true&bSortable_15=true&bSortable_16=true&bSortable_17=true&bSortable_18=true&bSortable_19=true&_=1310570699892
         *
	 */
	
	/* Array of database columns which should be read and sent back to DataTables. Use a space where
	 * you want to insert a non-database field (for example a counter or static image)
	 */
	$aColumns = array('itemid','itemlabel','typedesc','title','itemmodel','dnsname','serial','purchasedate',
	'remdays','username','statusdesc','locationname','areaname','rackinfo','purchprice','macs','ipv4','ipv6',
	'remadmip','taginfo','softinfo');
	
	include( '../init.php');

	/* Indexed column (used for fast and accurate table cardinality) */
	$sIndexColumn = "id";
	
	/* DB table to use */
	$sTable = "items";
	
	
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * If you just want to use the basic configuration for DataTables with PHP server-side, there is
	 * no need to edit below this line
	 */
	
	/* 
	 * DB connection
	 */
	
	$gaSql['link'] = $dbh;
	
	/* 
	 * Paging
	 */
	$sLimit = "";
	if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
	{
		$sLimit = "LIMIT ". $_GET['iDisplayLength'] ." OFFSET ".
			 $_GET['iDisplayStart'] ;
	}
	
	
	/*
	 * Ordering
	 */
	if ( isset( $_GET['iSortCol_0'] ) )
	{
		$sOrder = "ORDER BY  ";
		for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
		{
			if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
			{
				$sOrder .= $aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."
				 	". $_GET['sSortDir_'.$i]  .", ";
			}
		}
		
		$sOrder = substr_replace( $sOrder, "", -2 );
		if ( $sOrder == "ORDER BY" )
		{
			$sOrder = "";
		}
	}
	
	
	/* 
	 * Filtering
	 * NOTE This assumes that the field that is being searched on is a string typed field (ie. one
	 * on which LIKE can be used). Boolean fields etc will need a modification here.
	 */
	$sWhere = "";
	if ( $_GET['sSearch'] != "" )
	{
		$sWhere = "WHERE (";
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			if ( $_GET['bSearchable_'.$i] == "true" )
			{
				$sWhere .= $aColumns[$i]." LIKE '%".$_GET['sSearch'] ."%' OR ";
			}
		}
		$sWhere = substr_replace( $sWhere, "", -3 );
		$sWhere .= ")";

		//fix ambiguous colnames here for filtering
	}
	
	/* Individual column filtering */
	for ( $i=0 ; $i<count($aColumns) ; $i++ )
	{
		if ( $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
		{
			if ( $sWhere == "" )
			{
				$sWhere = "WHERE ";
			}
			else
			{
				$sWhere .= " AND ";
			}
			$sWhere .= $aColumns[$i]." LIKE '%".$_GET['sSearch_'.$i]."%' ";
		}
	}
	
	
	/* Total items */
	$sQueryCnt = "SELECT count($sIndexColumn) as count FROM $sTable ";
	$sth=db_execute($dbh,$sQueryCnt);
	$rResultTotal=$sth->fetch(PDO::FETCH_ASSOC);
	$iTotal=$rResultTotal['count'];
	$sth->closeCursor();

	$t=time();

	
	if ( $sWhere != "" )
	{

	      /* items after filtering */
	      //$sQueryCnt = "SELECT count($sIndexColumn) as count FROM $sTable $sWhere";
	      $sQueryCnt = "
		  SELECT count(items.id) as count ,
		  items.id AS itemid,
		  items.model AS itemmodel,
		  items.label AS itemlabel,
                  locations.name as locationname,
                  coalesce(sn,'') || ' ' || coalesce(sn2,'') || ' ' || coalesce(sn3,'') AS serial,
				  '' as remdays, purchasedate, warrantymonths, 
                  coalesce(racks.label,'') || ' ' || coalesce(racks.usize,'') || ' ' || coalesce(racks.model,'') AS rackinfo,
                  (SELECT group_concat( tags.name ,',') from tags,tag2item WHERE tag2item.itemid=items.id AND tags.id=tag2item.tagid) AS taginfo,
                  (SELECT group_concat( software.stitle ,'|') from software,item2soft WHERE item2soft.itemid=items.id AND software.id=item2soft.softid) AS softinfo
                  FROM
                  items
		  JOIN itemtypes ON items.itemtypeid=itemtypes.id 
		  JOIN agents ON items.manufacturerid=agents.id
		  JOIN users ON items.userid=users.id
		  JOIN statustypes ON items.status=statustypes.id
		  LEFT OUTER JOIN locations ON items.locationid=locations.id
		  LEFT OUTER JOIN locareas ON items.locareaid=locareas.id
		  LEFT OUTER JOIN racks ON items.rackid=racks.id
		  $sWhere";
	      $sth=db_execute($dbh,$sQueryCnt);
	      $rResultTotal=$sth->fetch(PDO::FETCH_ASSOC);
	      $iFilteredTotal=$rResultTotal['count'];
	      $sth->closeCursor();
	}
	else
	{
		$iFilteredTotal = $iTotal;
	}
	
	/* The actual query */
	//if ( $sWhere == "" ) $sWhere = " WHERE 1=1 ";
	

  //(purchasedate+warrantymonths*30*24*60*60-$t)/(60*60*24) AS remdays,
	$sQuery = "
		  SELECT 
		  items.id AS itemid,
		  itemtypes.typedesc as typedesc, 
                  agents.title,
                  items.model as itemmodel,
                  dnsname,
                  items.label as itemlabel,
                  purchasedate,
                  users.username,
                  statustypes.statusdesc,
                  locations.name as locationname,
                  locareas.areaname,
                  coalesce(sn,'') || ' ' || coalesce(sn2,'') || ' ' || coalesce(sn3,'') AS serial,
				  '' as remdays, warrantymonths, 
                  coalesce(racks.label,'') || ' ' || coalesce(racks.usize,'') || ' ' || coalesce(racks.model,'') AS rackinfo,
                  (SELECT group_concat( tags.name ,', ') FROM tags,tag2item WHERE tag2item.itemid=items.id AND tags.id=tag2item.tagid) AS taginfo,
                  (SELECT group_concat( software.stitle ,',') FROM software,item2soft WHERE item2soft.itemid=items.id and software.id=item2soft.softid) AS softinfo,
                  purchprice,
                  macs, ipv4, ipv6, remadmip
                  FROM
                  items
		  JOIN itemtypes ON items.itemtypeid=itemtypes.id 
		  JOIN agents ON items.manufacturerid=agents.id
		  LEFT OUTER JOIN statustypes ON items.status=statustypes.id
		  JOIN users ON items.userid=users.id
		  LEFT OUTER JOIN locations ON items.locationid=locations.id
		  LEFT OUTER JOIN locareas ON items.locareaid=locareas.id
		  LEFT OUTER JOIN racks ON items.rackid=racks.id
		  $sWhere
		  $sOrder
		  $sLimit
		  ";


//file_put_contents("/tmp/itlog.txt",$sQuery."\n\n");

	           
	$sth = db_execute($dbh,$sQuery);

	
	/*
	 * Output
	 */
	$output = array(
		"sEcho" => intval($_GET['sEcho']),
		"iTotalRecords" => $iTotal,
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);
	

	while ( $aRow = $sth->fetch(PDO::FETCH_ASSOC)) {
		$row = array();
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			if ( $aColumns[$i] == "itemid" ) {
				$statusid=getstatusidofitem($aRow['itemid'],$dbh);
				$x=attrofstatus($statusid,$dbh); $attr=$x[0]; $statustxt=$x[1];

				$r="<div style='width:60px'><span $attr>&nbsp;</span>".
				   "<span><a class='editid' title='Edit' href='?action=edititem&amp;id=".$aRow['itemid']."'>".
				   $aRow['itemid']."</a></span></div>";
				$row[] = $r;
			}
			elseif ( $aColumns[$i] == "remdays" ) {
				//$remdays=$aRow['remdays'];
				$remdays_r=calcremdays($aRow['purchasedate'],$aRow['warrantymonths']);
				$rdstr=$remdays_r['string'];
				$rd=$remdays_r['days'];
				$row[] = "<small><div title='$rd'>". $rdstr. "</div></small>"; // title attribute used for sorting
			}

			elseif ( $aColumns[$i] == "purchasedate" ) {
				if (strlen($aRow[$aColumns[$i]]))
				  $row[] = "<span title='{$aRow[$aColumns[$i]]}'>".date($dateparam,(int)$aRow[$aColumns[$i]])."</span>";
				else 
				  $row[] = "<span title='0'></span>";
			}

			elseif ( $aColumns[$i] == "softinfo" )
			{
				/* Special output formatting for 'version' column */
				//$row[] = "<small>". $aRow[$aColumns[$i]] ."</small>";
				$w=$aRow[$aColumns[$i]];
				$arr = preg_split("/[\s,]+/", $w,5);
				foreach ($arr as &$v)
				  if (strlen($v)) $v=substr($v, 0, 5).". ";

				$w2=implode("",$arr);
				if (strlen($w2)) $w2.="...";
				

				$row[] = "<small><div title='$w'>". $w2. "</div></small>";
			}
			else if ( $aColumns[$i] != ' ' )
			{
				/* General output */
				$row[] = $aRow[ $aColumns[$i] ];
			}
		}
		$output['aaData'][] = $row;
	}
	
//file_put_contents("/tmp/itlog.txt",json_encode($output)."\n\n");
	echo json_encode( $output );
	
	$sth->closeCursor();
?>
