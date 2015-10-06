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
	$aColumns = array('id','voucherroll','voucherrollbits','voucherticketbits','voucherchksumbits','vouchermins','vouchernum','voucherstartdate','voucheruser','voucherassigner','vouchernotes');
	
	include( '../init.php');

	/* Indexed column (used for fast and accurate table cardinality) */
	$sIndexColumn = "id";
	
	/* DB table to use */
	$sTable = "vouchers";
	
	
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
		  SELECT count(vouchers.id) as count ,
		  vouchers.id AS id,
                  FROM vouchers
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
	

$sql="SELECT *
	  FROM
  	  vouchers
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
	

//file_put_contents("/tmp/itlog.txt",json_encode($output)."\n\n");
	echo json_encode( $output );
	
	$sth->closeCursor();
?>
