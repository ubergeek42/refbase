<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./sru.php
	// Created:    17-May-05, 16:22
	// Modified:   22-May-05, 19:28

	// This script serves as a (faceless) routing page which takes a SRU query
	// and converts the query into a native refbase query
	// which is then passed to 'search.php'.

	// Supports 'explain' and 'searchRetrieve' operations (but not 'scan') and outputs
	// records as MODS XML wrapped into SRW XML. Allows to query all global refbase fields
	// (the given index name must match a refbase field name). If no index name is given
	// the 'serial' field will be searched by default.

	// Examples for recognized SRU/CQL queries:
	//
	// - ask the server to explain its SRW/U server & capabilities:
	//     sru.php
	//     sru.php?
	//     sru.php?operation=explain&version=1.1
	//
	// - return record with serial number 1:
	//     sru.php?version=1.1&query=1
	//     sru.php?version=1.1&query=1&operation=searchRetrieve&recordPacking=xml&recordSchema=mods
	//
	// - find all records where the title field contains either 'ecology' or 'diversity':
	//     sru.php?version=1.1&query=title%20any%20ecology%20diversity
	//
	// - find all records where the author field contains both 'dieckmann' and 'thomas':
	//     sru.php?version=1.1&query=author%20all%20dieckmann%20thomas
	//
	// - find all records where the publication field equals exactly 'Marine Ecology Progress Series':
	//     sru.php?version=1.1&query=publication%20exact%20Marine%20Ecology%20Progress%20Series
	//
	// - find all records where the year field is greater than or equals '2005':
	//     sru.php?version=1.1&query=year>=2005
	//
	// - find records with serial numbers 1, 123, 499, 612, 21654 & 23013 but
	//   return only the three last records:
	//     sru.php?version=1.1&query=1%20123%20499%20612%2021654%2023013&startRecord=4&maximumRecords=3
	//
	// - return just the number of found records (but not the full record data):
	//     sru.php?version=1.1&query=1%20123%20499%20612%2021654%2023013&maximumRecords=0
	//
	// - supress the default stylesheet or specify your own:
	//     sru.php?version=1.1&query=1&stylesheet=
	//     sru.php?version=1.1&query=1&stylesheet=xml2html.xsl

	// Note that (if the 'version' & 'query' parameters are present in the
	// query) 'operation=searchRetrieve' is assumed if ommitted. Additionally,
	// only 'recordPacking=xml' and 'recordSchema=mods' are supported and
	// 'sru.php' will use these settings by default if not given in the query.
	// Data will be returned together with a default stylesheet if the
	// 'stylesheet' parameter wasn't given in the query. XPath, sort and
	// result sets are not supported and only SRW version 1.1 is recognized.

	// For more on SRW/SRU, see:
	//   <http://www.loc.gov/z3950/agency/zing/srw/>

	// TODO: - proper parsing of CQL query string (currently, 'sru.php' allows only for a very limited set of CQL queries)
	//       - allow querying of user-specific fields by recognizing the 'x-authenticationToken' parameter
	//       - offer support for the boolean CQL operators 'and/or/not'
	//       - honour the 'sortKeys' parameter and return records sorted accordingly


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables
	include 'includes/locales.inc.php'; // include the locales
	include 'includes/srwxml.inc.php'; // include functions that deal with SRW XML

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// Extract mandatory parameters passed to the script:

	if (isset($_REQUEST['query']))
		$sruQuery = $_REQUEST['query'];
	else
		$sruQuery = "";

	if (isset($_REQUEST['version']))
		$sruVersion = $_REQUEST['version'];
	else
		$sruVersion = "";

	// Extract optional parameters passed to the script:

	if (isset($_REQUEST['operation']))
		$sruOperation = $_REQUEST['operation'];
	else
		$sruOperation = "searchRetrieve"; // we assume a 'searchRetrieve' operation if not given

	if (isset($_REQUEST['recordSchema'])) // note that we'll currently always output as 'mods'
		$sruRecordSchema = $_REQUEST['recordSchema'];
	else
		$sruRecordSchema = "mods";

	if (isset($_REQUEST['recordPacking'])) // note that we'll currently always output as 'xml'
		$sruRecordPacking = $_REQUEST['recordPacking'];
	else
		$sruRecordPacking = "xml";

	if (isset($_REQUEST['maximumRecords']))
		$showRows = $_REQUEST['maximumRecords'];
	else
		$showRows = $defaultNumberOfRecords; // '$defaultNumberOfRecords' is defined in 'ini.inc.php'

	if (isset($_REQUEST['startRecord']))
		$rowOffset = ($_REQUEST['startRecord']) - 1; // first row number in a MySQL result set is 0 (not 1)
	else
		$rowOffset = "";

	if (isset($_REQUEST['stylesheet']))
		$exportStylesheet = $_REQUEST['stylesheet'];
	else
		$exportStylesheet = "srwmods2html.xsl"; // we provide a default stylesheet if no stylesheet was specified in the query

	// The following (optional) parameters are extracted but are not supported yet:

	if (isset($_REQUEST['sortKeys']))
		$sruSortKeys = $_REQUEST['sortKeys'];
	else
		$sruSortKeys = "";

	if (isset($_REQUEST['recordXPath']))
		$sruRecordXPath = $_REQUEST['recordXPath'];
	else
		$sruRecordXPath = "";

	if (isset($_REQUEST['resultSetTTL']))
		$sruResultSetTTL = $_REQUEST['resultSetTTL'];
	else
		$sruResultSetTTL = "";

	if (isset($_REQUEST['extraRequestData']))
		$sruExtraRequestData = $_REQUEST['extraRequestData'];
	else
		$sruExtraRequestData = "";

	// For the context of 'sru.php' we set some parameters explicitly:

	$displayType = "Export";
	$exportFormat = "SRW XML";
	$exportType = "xml";
	$showLinks = "1";

	// -------------------------------------------------------------------------------------------------------------------

	// Parse CQL query:
	// NOTE: we don't provide a full CQL parser here but will (for now) concentrate on a rather limited feature
	//       set that makes sense in conjunction with refbase. However, future versions should employ far better
	//       CQL parsing logic.

	$searchArray = array(); // intialize array that will hold information about context set, index name, relation and search value
	$searchSubArray1 = array();

	// check for presence of context set/index name and any of the main relations:
	if (preg_match('/^[^\" <>=]+( +(all|any|exact|within) +| *(<>|<=|>=|<|>|=) *)/', $sruQuery))
	{
		// extract the context set:
		// (note that context sets aren't supported yet!)
		if (preg_match('/^([^\" <>=.]+)\./', $sruQuery))
			$contextSet = preg_replace('/^([^\" <>=.]+)\..*/', '\\1', $sruQuery);
		else
			$contextSet = ""; // use the default context set

		// extract the index:
		$indexName = preg_replace('/^(?:[^\" <>=.]+\.)?([^\" <>=.]+).*/', '\\1', $sruQuery);

		// extract the main relation (relation modifiers aren't supported yet!):
		$mainRelation = preg_replace('/^[^\" <>=]+( +(all|any|exact|within) +| *(<>|<=|>=|<|>|=) *).*/', '\\1', $sruQuery);
		// remove any runs of leading or trailing whitespace:
		$mainRelation = trim($mainRelation);

		// extract the search term:
		$searchTerm = preg_replace('/^[^\" <>=]+(?: +(?:all|any|exact|within) +| *(?:<>|<=|>=|<|>|=) *)(.*)/', '\\1', $sruQuery);
		// remove any leading or trailing quotes from the search term:
		// (note that multiple query parts connected with boolean operators aren't supported yet!)
		$searchTerm = preg_replace('/^\\\"/', '', $searchTerm);
		$searchTerm = preg_replace('/\\\"$/', '', $searchTerm);
		// convert any anchor at the end of a search term (like 'foo^') to the correct MySQL variant ('foo$'):
		$searchTerm = preg_replace('/\^( |$)/', '$\\1', $searchTerm);

		$whereClausePart = $indexName;

		if ($mainRelation == "all")
		{
			if (ereg(" ", $searchTerm))
			{
				$searchTermArray = split(" +", $searchTerm);
	
				foreach ($searchTermArray as $searchTermItem)
					$whereClauseSubPartsArray[] = " RLIKE \"" . $searchTermItem . "\"";
	
				$whereClausePart .= implode(" AND " . $indexName . " ", $whereClauseSubPartsArray);
			}
			else
				$whereClausePart .= " RLIKE \"" . $searchTerm . "\"";
		}

		elseif ($mainRelation == "any")
		{
			$searchTerm = splitAndMerge(" +", "|", $searchTerm); // function 'splitAndMerge()' is defined in 'include.inc.php'
			$whereClausePart .= " RLIKE \"" . $searchTerm . "\"";
		}

		elseif ($mainRelation == "exact")
			$whereClausePart .= " = \"" . $searchTerm . "\"";

		elseif ($mainRelation == "=")
			$whereClausePart .= " RLIKE \"" . $searchTerm . "\"";

		elseif ($mainRelation == "<>")
			$whereClausePart .= " NOT RLIKE \"" . $searchTerm . "\"";

		elseif ($mainRelation == "<")
			$whereClausePart .= " < \"" . $searchTerm . "\"";

		elseif ($mainRelation == "<=")
			$whereClausePart .= " <= \"" . $searchTerm . "\"";

		elseif ($mainRelation == ">")
			$whereClausePart .= " > \"" . $searchTerm . "\"";

		elseif ($mainRelation == ">=")
			$whereClausePart .= " >= \"" . $searchTerm . "\"";

		$searchSubArray1[] = array("_boolean" => "",
									"_query" => $whereClausePart);
	}
	else // no context set/index name and relation was given -> search the 'serial' field by default:
	{
		// NOTE: the following code block does not conform to CQL syntax rules!

		// replace any non-digit chars with "|":
		// (in doing so we'll ignore any 'and/or/not' booleans that were
		//  present in the search term and assume an 'or' operator instead)
		$serialsString = preg_replace("/\D+/", "|", $sruQuery);
		// strip "|" from beginning/end of string (if any):
		$serialsString = preg_replace("/^\|?(.*?)\|?$/", "\\1", $serialsString);

		if (!empty($serialsString))
			$searchSubArray1[] = array("_boolean" => "",
										"_query" => "serial RLIKE \"^(" . $serialsString . ")$\"");
			// although our default relation (exposed by the explain response) is 'any' we treat
			// each number as if it was given as number with enclosing anchors (like '^9^')
	}

	if (!empty($searchSubArray1))
		$searchArray[] = array("_boolean" => "",
								"_query" => $searchSubArray1);
				
	// -------------------------------------------------------------------------------------------------------------------

	// Check for operation and that mandatory parameters have been passed:
	if ($sruOperation == "explain" OR (!isset($_REQUEST['query']) AND !isset($_REQUEST['version']) AND !isset($_REQUEST['operation']) AND !isset($_REQUEST['recordSchema']) AND !isset($_REQUEST['recordPacking']) AND !isset($_REQUEST['maximumRecords']) AND !isset($_REQUEST['startRecord']) AND !isset($_REQUEST['sortKeys']) AND !isset($_REQUEST['recordXPath']) AND !isset($_REQUEST['stylesheet'])))
		// if 'sru.php' was called with 'operation=explain' -OR- without any recognized parameters, we'll return an appropriate 'explainResponse':
		echo srwExplainResponse($exportStylesheet);

	// if 'sru.php' was called without any valid (or with incorrect) parameters, we'll return appropriate 'diagnostics':
	elseif (!eregi("^(explain|searchRetrieve)$",$sruOperation))
		echo srwDiagnostics(4, "Only 'explain' and 'searchRetrieve' operations are supported", $exportStylesheet);

	elseif (empty($sruQuery))
		echo srwDiagnostics(7, "query", $exportStylesheet); // required 'query' parameter is missing

	elseif (empty($sruVersion))
		echo srwDiagnostics(7, "version", $exportStylesheet); // required 'version' parameter is missing

	elseif ($sruVersion != "1.1")
		echo srwDiagnostics(5, "1.1", $exportStylesheet); // only SRW version 1.1 is supported

	elseif (!eregi("^mods$",$sruRecordSchema) AND !eregi("^info:srw/schema/1/mods-v3\.0$",$sruRecordSchema))
		echo srwDiagnostics(66, $sruRecordSchema, $exportStylesheet); // no other schema than MODS is supported

	elseif (!eregi("^xml$",$sruRecordPacking))
		echo srwDiagnostics(71, "Only 'recordPacking=xml' is supported", $exportStylesheet); // no other record packing than XML is supported

	elseif (!empty($sruRecordXPath))
		echo srwDiagnostics(72, "", $exportStylesheet); // XPath isn't supported yet

	elseif (!empty($sruSortKeys))
		echo srwDiagnostics(80, "", $exportStylesheet); // Sort isn't supported yet

	elseif (!empty($sruResultSetTTL))
		echo srwDiagnostics(50, "", $exportStylesheet); // Result sets aren't supported

	// -------------------------------------------------------------------------------------------------------------------

	else // the script was called at least with the required parameters 'query' and 'version'
	{

//		// NOTE: the generation of SQL queries (or parts of) should REALLY be modular and be moved to separate dedicated functions!

		// CONSTRUCT SQL QUERY:

		// Note: the 'verifySQLQuery()' function that gets called by 'search.php' to process query data with "$formType = sqlSearch" will add the user specific fields to the 'SELECT' clause
		// and the 'LEFT JOIN...' part to the 'FROM' clause of the SQL query if a user is logged in. It will also add 'orig_record', 'serial', 'file', 'url' & 'doi' columns
		// as required. Therefore it's sufficient to provide just the plain SQL query here:

		// Build SELECT clause:
		// select all fields required to export a record as SRW XML:
		$query = "SELECT author, title, type, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved, location, online_publication, online_citation, call_number, serial";
		//           (the above string MUST end with ", call_number, serial" in order to have the described query completion feature work correctly!


		// Build FROM clause:
		$query .= " FROM $tableRefs"; // add FROM clause (variable '$tableRefs' is defined in 'db.inc.php')


		if (!empty($searchArray))
		{
			// Build WHERE clause:
			$query .= " WHERE";

			appendToWhereClause($searchArray);
		}


		// Build ORDER BY clause:
		$query .= " ORDER BY serial";

		// --------------------------------------------------------------------

		// Build the correct query URL:
		// (we skip unnecessary parameters here since 'search.php' will use it's default values for them)
		$queryURL = "sqlQuery=" . rawurlencode($query) . "&formType=sqlSearch&submit=" . $displayType . "&showRows=" . $showRows . "&rowOffset=" . $rowOffset . "&showLinks=" . $showLinks . "&exportFormatSelector=" . rawurlencode($exportFormat) . "&exportType=" . $exportType . "&exportStylesheet=" . $exportStylesheet;

		// call 'search.php' with the correct query URL in order to display record details:
		header("Location: search.php?$queryURL");
	}

	// -------------------------------------------------------------------------------------------------------------------

	// this function walks a '$searchArray' and appends its items to the WHERE clause:
	// (the array hierarchy will be maintained, i.e. if the '_query' item is itself
	//  an array of query items these sub-items will get properly nested in parentheses)
	function appendToWhereClause($searchArray)
	{
		global $query;

		foreach ($searchArray as $searchArrayItem)
		{
			if (is_array($searchArrayItem["_query"]))
			{
				$query .= " " . $searchArrayItem["_boolean"] . " (";
				$query .= appendToWhereClause($searchArrayItem["_query"]);
				$query .= " )";
			}
			else
			{
				$query .= " " . $searchArrayItem["_boolean"] . " " . $searchArrayItem["_query"];
			}
		}
	}
?>
