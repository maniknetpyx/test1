<?php

require_once(dirname(dirname(dirname(__FILE__)))."/config/config.inc.php");
require_once(SITE_HTML_ROOT."classes/functions.mysql.php");
require_once(SITE_HTML_ROOT."classes/functions.misc.php");
require_once(SITE_HTML_ROOT."classes/functions.common.php");
require_once(SITE_HTML_ROOT."classes/functions.admin-functions.php");
	
	$mysql = new sql;
	
	

	$aColumns = array( 'user_id','uname', 'email','fname','lname','user_admin_status' );
	$sIndexColumn = "user_id";
	$sTable = "dtravel_user_master AS a INNER JOIN dtravel_user_restinfo AS b ON a.user_id=b.rest_user_id";
	
	
	
	function fatal_error ( $sErrorMessage = '' )
	{
		header( $_SERVER['SERVER_PROTOCOL'] .' 500 Internal Server Error' );
		die( $sErrorMessage );
	}
	
	$sLimit = "";
	if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
	{
		$sLimit = "LIMIT ".intval( $_GET['iDisplayStart'] ).", ".
			intval( $_GET['iDisplayLength'] );
	}

	$sOrder = "";
	if ( isset( $_GET['iSortCol_0'] ) )
	{
		$sOrder = "ORDER BY  ";
		for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
		{
			if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
			{
				$sOrder .= "`".$aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."` ".
					($_GET['sSortDir_'.$i]==='asc' ? 'asc' : 'desc') .", ";
			}
		}
		
		$sOrder = substr_replace( $sOrder, "", -2 );
		if ( $sOrder == "ORDER BY" )
		{
			$sOrder = "";
		}
	}

	$sWhere = "";
	if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
	{
		$sWhere = "WHERE (";
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			$sWhere .= "`".$aColumns[$i]."` LIKE '%".mysql_real_escape_string( $_GET['sSearch'] )."%' OR ";
		}
		$sWhere = substr_replace( $sWhere, "", -3 );
		$sWhere .= ')';
	}
	
	/* Individual column filtering */
	for ( $i=0 ; $i<count($aColumns) ; $i++ )
	{
		if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
		{
			if ( $sWhere == "" )
			{
				$sWhere = "WHERE ";
			}
			else
			{
				$sWhere .= " AND ";
			}
			$sWhere .= "`".$aColumns[$i]."` LIKE '%".mysql_real_escape_string($_GET['sSearch_'.$i])."%' ";
		}
	}
	
	
	
	    $sQuery = "
		SELECT SQL_CALC_FOUND_ROWS `".str_replace(" , ", " ", implode("`, `", $aColumns))."`
		FROM   $sTable
		$sWhere
		$sOrder
		$sLimit
		";
		
	$rResult = mysql_query( $sQuery ) or fatal_error( 'MySQL Error: ' . mysql_errno() );  // $gaSql['link'];
	
	
	$sQuery = "SELECT FOUND_ROWS()";    /* Data set length after filtering */
	$rResultFilterTotal = mysql_query( $sQuery ) or fatal_error( 'MySQL Error: ' . mysql_errno() );  //, $gaSql['link']
	$aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);
	$iFilteredTotal = $aResultFilterTotal[0];
	
	
	$sQuery = "SELECT COUNT(`".$sIndexColumn."`) FROM   $sTable "; /* Total data set length */
	$rResultTotal = mysql_query( $sQuery ) or fatal_error( 'MySQL Error: ' . mysql_errno() );  // , $gaSql['link']
	$aResultTotal = mysql_fetch_array($rResultTotal);
	$iTotal = $aResultTotal[0];
	
	
	/*
	 * Output
	 */
	$output = array(
		"sEcho" => intval($_GET['sEcho']),
		"iTotalRecords" => $iTotal,
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);
	
	array_push($aColumns, "status_toggle", "img_toggle");
	while ( $aRow = mysql_fetch_array( $rResult ) )
	{
	
	    switch($aRow['user_admin_status']):
		case 1:
				$status_toggle = 'deactivate';
				$img_toggle = 'active';
				$echo_stat_toggle = 'Active';
		break;
		
		case 0:
				$status_toggle = 'activate';
				$img_toggle = 'deactive';
				$echo_stat_toggle = 'Deactive';
		break;
	    endswitch;
		
		$aRow['status_toggle'] = $status_toggle;
		$aRow['img_toggle'] = $img_toggle ;

		
		$row = array();
		
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{  					
				$row[] = $aRow[ $aColumns[$i] ];
		}
		$output['aaData'][] = $row;
	}
	
	echo json_encode( $output );
?>