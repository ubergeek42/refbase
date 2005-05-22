<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author.
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/srwxml.inc.php
	// Author:     Matthias Steffens <mailto:refbase@extracts.de> and
	//             Richard Karnesky <mailto:karnesky@northwestern.edu>
	//
	// Created:    17-May-05, 16:38
	// Modified:   22-May-05, 19:10

	// This include file contains functions that'll export records to SRW XML.
	// Requires ActiveLink PHP XML Package, which is available under the GPL from:
	// <http://www.active-link.com/software/>. See 'sru.php' for more info.


	// Import the ActiveLink Packages
	require_once("classes/include.php");
	import("org.active-link.xml.XML");
	import("org.active-link.xml.XMLDocument");

	// --------------------------------------------------------------------

	// Return MODS XML records wrapped into SRW XML ('searchRetrieveResponse'):
	function srwCollection($result, $rowOffset, $showRows, $exportStylesheet, $displayType)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		// Individual records are objects and collections of records are strings

		$srwCollectionDoc = new XMLDocument();
		$srwCollectionDoc->setEncoding($contentTypeCharset);

		$srwCollection = srwGenerateBaseTags("searchRetrieveResponse");

		$showRowsOriginal = $showRows; // save original value of '$showRows' (which may get modified by the 'seekInMySQLResultsToOffset()' function below)

		// Find out how many rows are available and (if there were rows found) seek to the current offset:
		// function 'seekInMySQLResultsToOffset()' is defined in 'include.inc.php'
		list($result, $rowOffset, $showRows, $rowsFound, $previousOffset, $nextOffset, $showMaxRow) = seekInMySQLResultsToOffset($result, $rowOffset, $showRows, $displayType);

		$srwRowsFoundBranch = new XMLBranch("srw:numberOfRecords");
		$srwRowsFoundBranch->setTagContent($rowsFound);
		$srwCollection->addXMLBranch($srwRowsFoundBranch);

		// <srw:resultSetId> not supported yet
		// <srw:resultSetIdleTime> not supported yet

		$srwRecordsBranch = new XMLBranch("srw:records");
	
		if ($showRowsOriginal != 0) // we ommit the records list in the response if the SRU query did contain 'maximumRecords=0'
		{
			$exportArray = array(); // Array for individually exported records
	
			// Generate the export for each record and push them onto an array:
			for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
			{
				// Convert special characters in each of the fields:
				foreach ($row as $rowFieldName => $rowFieldValue)
					// We only convert those special chars to entities which are supported by XML:
					// (function 'encodeHTMLspecialchars()' is defined in 'include.inc.php')
					$row[$rowFieldName] = encodeHTMLspecialchars($row[$rowFieldName]);
	
				// Note: except from the above conversion of angle brackets (i.e., '<'
				//       and '>'), ampersands ('&') and quotes, data will be exported as
				//       fetched from the MySQL database, i.e., there's NO conversion of:
				//        - higher ASCII chars
				//        - "human readable markup" that's used within plain text fields
				//          of the database to define rich text characters like italics,
				//          etc. (see '$markupSearchReplacePatterns' in 'ini.inc.php')
	
				$record = modsRecord($row); // Export the current record as MODS XML
	
				if (!empty($record)) // unless the record buffer is empty...
					array_push($exportArray, $record); // ...add it to an array of exports
			}
	
			$i = $rowOffset; // initialize counter
	
			// for each of the MODS records in the result set...
			foreach ($exportArray as $mods)
			{
				++$i; // increment $i by one, then return $i
	
				$srwRecordBranch = new XMLBranch("srw:record");
	
				srwGeneratePackingSchema($srwRecordBranch, "xml", "mods");
	
				$srwRecordDataBranch = new XMLBranch("srw:recordData");
	
				// NOTE: converting the MODS object into a string to perform search & replace actions
				//       may be very clumsy but I don't know any better... ?:-/
				$modsString = $mods->getXMLString();
				$modsString = preg_replace('/<mods/i','<mods xmlns="http://www.loc.gov/mods/v3" version="3.0"',$modsString);
				// alternatively to the above line we could add a 'mods:' identifier to all MODS XML tags:
//				$modsString = preg_replace("#<(/)?#","<\\1mods:",$modsString);
				$mods->removeAllBranches();
				$mods->parseFromString($modsString);
				
				$srwRecordDataBranch->addXMLasBranch($mods);
				$srwRecordBranch->addXMLBranch($srwRecordDataBranch);
	
				$srwRecordPositionBranch = new XMLBranch("srw:recordPosition");
				$srwRecordPositionBranch->setTagContent($i);
				$srwRecordBranch->addXMLBranch($srwRecordPositionBranch);
	
				$srwRecordsBranch->addXMLBranch($srwRecordBranch);
			}
		}
	
		$srwCollection->addXMLBranch($srwRecordsBranch);

		$srwCollectionDoc->setXML($srwCollection);
		$srwCollectionString = $srwCollectionDoc->getXMLString();

		// Add the XML Stylesheet definition:
		// Note that this is just a hack (that should get fixed) since I don't know how to do it properly using the ActiveLink PHP XML Package ?:-/
		if (!empty($exportStylesheet))
			$srwCollectionString = preg_replace("/(?=\<srw:searchRetrieveResponse)/i","<?xml-stylesheet type=\"text/xsl\" href=\"" . $exportStylesheet . "\"?>\n",$srwCollectionString);

		return $srwCollectionString;
	}

	// --------------------------------------------------------------------

	// return an SRW 'explainResponse' if the SRW/U client issued either of the following:
	// - http://.../refs/sru.php?operation=explain
	// - http://.../refs/sru.php?
	// - http://.../refs/sru.php
	function srwExplainResponse($exportStylesheet)
	{
		global $contentTypeCharset; // these variables are specified in 'ini.inc.php'
		global $databaseBaseURL;
		global $officialDatabaseName;
		global $hostInstitutionName;
		global $feedbackEmail;
		global $defaultNumberOfRecords;
		global $defaultLanguage;

		global $loc; // defined in 'locales/core.php'

		$srwCollectionDoc = new XMLDocument();
		$srwCollectionDoc->setEncoding($contentTypeCharset);

		$srwCollection = srwGenerateBaseTags("explainResponse");
		
		$srwRecordBranch = new XMLBranch("srw:record");

		srwGeneratePackingSchema($srwRecordBranch, "xml", "zeerex");

		$srwRecordDataBranch = new XMLBranch("srw:recordData");

		$srwExplainBranch = new XMLBranch("zr:explain");


		// extract the protocol from the base URL:
		if (preg_match("#^([^:]+)://.*#",$databaseBaseURL))
			$databaseProtocol = preg_replace("#^([^:]+)://.*#","\\1",$databaseBaseURL);
		else
			$databaseProtocol = "";

		// extract the host from the base URL:
		if (preg_match("#^[^:]+://(?:www\.)?[^/]+.*#",$databaseBaseURL))
			$databaseHost = preg_replace("#^[^:]+://(?:www\.)?([^/]+).*#","\\1",$databaseBaseURL);
		else
			$databaseHost = $databaseBaseURL;

		// extract the path on server from the base URL:
		if (preg_match("#^[^:]+://(?:www\.)?[^/]+/.+#",$databaseBaseURL))
			$databasePathOnServer = preg_replace("#^[^:]+://(?:www\.)?[^/]+/(.+)#","\\1",$databaseBaseURL);
		else
			$databasePathOnServer = "";

		// get the total number of records in the database:
		$recordCount = getNumberOfRecords(); // function 'getNumberOfRecords()' is defined in 'include.inc.php'

		// get date/time information when the database was last modified:
		$lastModified = getLastModifiedDateTime(); // function 'getLastModifiedDateTime()' is defined in 'include.inc.php'


		// --- begin server info ------------------------------------
		$srwServerInfoBranch = new XMLBranch("zr:serverInfo");
		$srwServerInfoBranch->setTagAttribute("protocol", "SRU");
		$srwServerInfoBranch->setTagAttribute("version", "1.1");
		if (!empty($databaseProtocol))
			$srwServerInfoBranch->setTagAttribute("transport", $databaseProtocol);

		$srwServerInfoBranch->setTagContent($databaseHost, "zr:serverInfo/host");
		$srwServerInfoBranch->setTagContent("80", "zr:serverInfo/port"); // NOTE: this should really be a variable in 'ini.inc.php' or such

		$srwServerInfoDatabaseBranch = new XMLBranch("database");
		$srwServerInfoDatabaseBranch->setTagAttribute("numRecs", $recordCount);
		$srwServerInfoDatabaseBranch->setTagAttribute("lastUpdate", $lastModified);
		$srwServerInfoDatabaseBranch->setTagContent($databasePathOnServer . "sru.php");
		$srwServerInfoBranch->addXMLBranch($srwServerInfoDatabaseBranch);

		// IMPORTANT: if you want to allow remote users who are NOT logged in (userID=0) to query the refbase database
		//            via 'sru.php' then either the 'Export' or the 'Batch export' user permission needs to be
		//            enabled at 'user_options.php?userID=0'. This will allow export of XML records via 'sru.php'
		//            but won't allow a user who isn't logged in to export records via the web interface. However, you
		//            should be aware that a direct GET query like 'show.php?author=miller&submit=Export&exportFormatSelector=MODS%20XML'
		//            will be also allowed then!

		// As an alternative, you can provide explicit login information within the 'serverInfo/authentication' tag
		// below. But, obviously, the provided login information should be only given for an account that has the
		// 'Export' permission bit enabled but has otherwise limited access rights!

		// If the 'authentication' element is present, but empty, then it implies that authentication is required
		// to connect to the server, however there is no publically available login. If it contains a string, then
		// this is the token to give in order to authenticate. Otherwise it may contain three elements:
		// 1. user: The username to supply.
		// 2. group: The group to supply.
		// 3. password: The password to supply.
//		$srwServerInfoAuthenticationBranch = new XMLBranch("authentication");
//		$srwServerInfoAuthenticationBranch->setTagContent("LOGINEMAIL", "authentication/user");
//		$srwServerInfoAuthenticationBranch->setTagContent("PASSWORD", "authentication/password");
//		$srwServerInfoBranch->addXMLBranch($srwServerInfoAuthenticationBranch);

		$srwExplainBranch->addXMLBranch($srwServerInfoBranch);
		// --- end server info --------------------------------------


		// --- begin database info ----------------------------------
		$srwDatabaseInfoBranch = new XMLBranch("zr:databaseInfo");

		$srwDatabaseTitleBranch = new XMLBranch("title");
		$srwDatabaseTitleBranch->setTagAttribute("lang", $defaultLanguage);
		$srwDatabaseTitleBranch->setTagAttribute("primary", "true");
		$srwDatabaseTitleBranch->setTagContent(encodeHTMLspecialchars($officialDatabaseName));
		$srwDatabaseInfoBranch->addXMLBranch($srwDatabaseTitleBranch);

		$srwDatabaseDescriptionBranch = new XMLBranch("description");
		$srwDatabaseDescriptionBranch->setTagAttribute("lang", $defaultLanguage);
		$srwDatabaseDescriptionBranch->setTagAttribute("primary", "true");
		$srwDatabaseDescriptionBranch->setTagContent(encodeHTMLspecialchars($loc["ThisDatabaseAttempts"]));
		$srwDatabaseInfoBranch->addXMLBranch($srwDatabaseDescriptionBranch);

		$srwDatabaseInfoBranch->setTagContent(encodeHTMLspecialchars($hostInstitutionName), "zr:databaseInfo/author");

		$srwDatabaseInfoBranch->setTagContent(encodeHTMLspecialchars($hostInstitutionName) . " (" . $feedbackEmail . ")", "zr:databaseInfo/contact");

		$srwDatabaseImplementationBranch = new XMLBranch("implementation");
//		$srwDatabaseImplementationBranch->setTagAttribute("version", "0.8.0");
		$srwDatabaseImplementationBranch->setTagAttribute("identifier", "refbase");
		$srwDatabaseImplementationBranch->setTagContent("Web Reference Database (http://refbase.sourceforge.net)", "implementation/title");
		$srwDatabaseInfoBranch->addXMLBranch($srwDatabaseImplementationBranch);

		$srwDatabaseLinksBranch = new XMLBranch("links");

		$srwDatabaseLinkBranch = new XMLBranch("link");
		$srwDatabaseLinkBranch->setTagAttribute("type", "www");
		$srwDatabaseLinkBranch->setTagContent($databaseBaseURL);
		$srwDatabaseLinksBranch->addXMLBranch($srwDatabaseLinkBranch);

		$srwDatabaseLinkBranch = new XMLBranch("link");
		$srwDatabaseLinkBranch->setTagAttribute("type", "sru");
		$srwDatabaseLinkBranch->setTagContent($databaseBaseURL . "sru.php");
		$srwDatabaseLinksBranch->addXMLBranch($srwDatabaseLinkBranch);

		$srwDatabaseLinkBranch = new XMLBranch("link");
		$srwDatabaseLinkBranch->setTagAttribute("type", "rss");
		$srwDatabaseLinkBranch->setTagContent($databaseBaseURL . "rss.php?where=serial%20RLIKE%20%22.%2B%22&amp;showRows=10");
		$srwDatabaseLinksBranch->addXMLBranch($srwDatabaseLinkBranch);

		$srwDatabaseLinkBranch = new XMLBranch("link");
		$srwDatabaseLinkBranch->setTagAttribute("type", "icon");
		$srwDatabaseLinkBranch->setTagContent($databaseBaseURL . "img/logo.gif");
		$srwDatabaseLinksBranch->addXMLBranch($srwDatabaseLinkBranch);

		$srwDatabaseInfoBranch->addXMLBranch($srwDatabaseLinksBranch);

		$srwExplainBranch->addXMLBranch($srwDatabaseInfoBranch);
		// --- end database info ------------------------------------


		// --- begin index info -------------------------------------
		$srwIndexInfoBranch = new XMLBranch("zr:indexInfo");

		// although all global refbase fields are searchable we'll only provide
		// the main/important fields in the explain response:
		$indexNameArray = array("author" => "Author",
								"title" => "Publication title",
								"year" => "Year of publication",
								"publication" => "Publication or journal name",
								"abbrev_journal" => "Abbreviated journal name",
								"volume" => "Publication volume",
								"issue" => "Publication issue",
								"pages" => "Range or total number of pages",
								"editor" => "Editor",
								"keywords" => "Keywords",
								"abstract" => "Abstract",
								"notes" => "Notes",
								"issn" => "International standard serial number",
								"isbn" => "International standard book number",
								"doi" => "Digital object identifier",
								"url" => "Uniform resource locator",
								"serial" => "Record Serial Number");

		foreach ($indexNameArray as $indexName => $indexTitle)
		{
			$srwIndexBranch = new XMLBranch("index");
			$srwIndexBranch->setTagAttribute("search", "true");
			$srwIndexBranch->setTagAttribute("scan", "false");
			$srwIndexBranch->setTagAttribute("sort", "false");

			$srwIndexTitleBranch = new XMLBranch("title");
			$srwIndexTitleBranch->setTagAttribute("lang", "en");
			$srwIndexTitleBranch->setTagContent($indexTitle);
			$srwIndexBranch->addXMLBranch($srwIndexTitleBranch);
	
			$srwIndexBranch->setTagContent($indexName, "index/map/name");

			$srwIndexInfoBranch->addXMLBranch($srwIndexBranch);
		}

		$srwExplainBranch->addXMLBranch($srwIndexInfoBranch);
		// --- end index info ---------------------------------------


		// --- begin schema info -------------------------------------
		$srwSchemaInfoBranch = new XMLBranch("zr:schemaInfo");

		$srwSchemaBranch = new XMLBranch("schema");
		$srwSchemaBranch->setTagAttribute("identifier", "http://www.loc.gov/mods/v3");
		$srwSchemaBranch->setTagAttribute("location", "http://www.loc.gov/standards/mods/v3/mods-3-0.xsd");
		$srwSchemaBranch->setTagAttribute("sort", "false");
		$srwSchemaBranch->setTagAttribute("retrieve", "true");
		$srwSchemaBranch->setTagAttribute("name", "mods");

		$srwSchemaTitleBranch = new XMLBranch("title");
		$srwSchemaTitleBranch->setTagAttribute("lang", "en");
		$srwSchemaTitleBranch->setTagContent("Metadata Object Description Schema (MODS)");
		$srwSchemaBranch->addXMLBranch($srwSchemaTitleBranch);

		$srwSchemaInfoBranch->addXMLBranch($srwSchemaBranch);

		$srwExplainBranch->addXMLBranch($srwSchemaInfoBranch);
		// --- end schema info ---------------------------------------


		// --- begin config info -------------------------------------
		$srwConfigInfoBranch = new XMLBranch("zr:configInfo");

		$srwConfigDefaultBranch = new XMLBranch("default");
		$srwConfigDefaultBranch->setTagAttribute("type", "numberOfRecords");
		$srwConfigDefaultBranch->setTagContent($defaultNumberOfRecords);
		$srwConfigInfoBranch->addXMLBranch($srwConfigDefaultBranch);

		$srwConfigDefaultBranch = new XMLBranch("default");
		$srwConfigDefaultBranch->setTagAttribute("type", "stylesheet");
		$srwConfigDefaultBranch->setTagContent($databaseBaseURL . "srwmods2html.xsl");
		$srwConfigInfoBranch->addXMLBranch($srwConfigDefaultBranch);

		$srwConfigDefaultBranch = new XMLBranch("default");
		$srwConfigDefaultBranch->setTagAttribute("type", "index");
		$srwConfigDefaultBranch->setTagContent("serial");
		$srwConfigInfoBranch->addXMLBranch($srwConfigDefaultBranch);

		$srwConfigDefaultBranch = new XMLBranch("default");
		$srwConfigDefaultBranch->setTagAttribute("type", "relation");
		$srwConfigDefaultBranch->setTagContent("any");
		$srwConfigInfoBranch->addXMLBranch($srwConfigDefaultBranch);

		$srwConfigSettingBranch = new XMLBranch("setting");
		$srwConfigSettingBranch->setTagAttribute("type", "sortSchema");
		$srwConfigSettingBranch->setTagContent("serial");
		$srwConfigInfoBranch->addXMLBranch($srwConfigSettingBranch);

		$srwConfigSettingBranch = new XMLBranch("setting");
		$srwConfigSettingBranch->setTagAttribute("type", "retrieveSchema");
		$srwConfigSettingBranch->setTagContent("mods");
		$srwConfigInfoBranch->addXMLBranch($srwConfigSettingBranch);

		$srwConfigSettingBranch = new XMLBranch("setting");
		$srwConfigSettingBranch->setTagAttribute("type", "recordPacking");
		$srwConfigSettingBranch->setTagContent("xml");
		$srwConfigInfoBranch->addXMLBranch($srwConfigSettingBranch);

		$srwConfigSupportsBranch = new XMLBranch("supports");
		$srwConfigSupportsBranch->setTagAttribute("type", "emptyTerm");
		$srwConfigInfoBranch->addXMLBranch($srwConfigSupportsBranch);

		$srwExplainBranch->addXMLBranch($srwConfigInfoBranch);
		// --- end config info ---------------------------------------


		$srwRecordDataBranch->addXMLBranch($srwExplainBranch);

		$srwRecordBranch->addXMLBranch($srwRecordDataBranch);

		$srwCollection->addXMLBranch($srwRecordBranch);

		$srwCollectionDoc->setXML($srwCollection);
		$srwCollectionString = $srwCollectionDoc->getXMLString();

		return $srwCollectionString;
	}

	// --------------------------------------------------------------------

	// Return SRW diagnostics (i.e. SRW error information) wrapped into SRW XML ('searchRetrieveResponse'):
	function srwDiagnostics($diagCode, $diagDetails, $exportStylesheet)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		$diagMessages = array(1 => "General system error", // Details: Debugging information (traceback)
								2 => "System temporarily unavailable",
								3 => "Authentication error",
								4 => "Unsupported operation",
								5 => "Unsupported version", // Details: Highest version supported
								6 => "Unsupported parameter value", // Details: Name of parameter
								7 => "Mandatory parameter not supplied", // Details: Name of missing parameter
								8 => "Unsupported Parameter", // Details: Name of the unsupported parameter

								10 => "Query syntax error",
								39 => "Proximity not supported",

								50 => "Result sets not supported",

								61 => "First record position out of range",
								64 => "Record temporarily unavailable",
								65 => "Record does not exist",
								66 => "Unknown schema for retrieval", // Details: Schema URI or short name
								67 => "Record not available in this schema", // Details: Schema URI or short name
								68 => "Not authorised to send record",
								69 => "Not authorised to send record in this schema",
								70 => "Record too large to send", // Details: Maximum record size
								71 => "Unsupported record packing",
								72 => "XPath retrieval unsupported",

								80 => "Sort not supported",

								110 => "Stylesheets not supported");

		if (isset($diagMessages[$diagCode]))
			$diagMessage = $diagMessages[$diagCode];
		else
			$diagMessage = "Unknown error";

		$srwCollectionDoc = new XMLDocument();
		$srwCollectionDoc->setEncoding($contentTypeCharset);

		$srwCollection = srwGenerateBaseTags("searchRetrieveResponse");

		$diagnosticsBranch = new XMLBranch("srw:diagnostics");
		$diagnosticsBranch->setTagAttribute("xmlns", "info:srw/schema/1/diagnostic-v1.1");

		$diagnosticsBranch->setTagContent("info:srw/diagnostic/1/" . $diagCode, "srw:diagnostics/diagnostic/uri");
		$diagnosticsBranch->setTagContent($diagMessage, "srw:diagnostics/diagnostic/message");
		if (!empty($diagDetails))
			$diagnosticsBranch->setTagContent(encodeHTMLspecialchars($diagDetails), "srw:diagnostics/diagnostic/details");

		$srwCollection->addXMLBranch($diagnosticsBranch);

		$srwCollectionDoc->setXML($srwCollection);
		$srwCollectionString = $srwCollectionDoc->getXMLString();

		return $srwCollectionString;
	}

	// --------------------------------------------------------------------

	// Generate the basic SRW XML tree required for a 'searchRetrieveResponse' or 'explainResponse':
	function srwGenerateBaseTags($srwOperation)
	{
		$srwCollection = new XML("srw:" . $srwOperation);
		$srwCollection->setTagAttribute("xmlns:srw", "http://www.loc.gov/zing/srw/");

		if ($srwOperation == "searchRetrieveResponse")
		{
			$srwCollection->setTagAttribute("xmlns:diag", "http://www.loc.gov/zing/srw/diagnostic/");
			$srwCollection->setTagAttribute("xmlns:xcql", "http://www.loc.gov/zing/cql/xcql/");
			$srwCollection->setTagAttribute("xmlns:mods", "http://www.loc.gov/mods/v3");
		}
		elseif ($srwOperation == "explainResponse")
		{
			$srwCollection->setTagAttribute("xmlns:zr", "http://explain.z3950.org/dtd/2.0/");
		}

		$srwVersionBranch = new XMLBranch("srw:version");
		$srwVersionBranch->setTagContent("1.1");
		$srwCollection->addXMLBranch($srwVersionBranch);

		return $srwCollection;
	}

	// --------------------------------------------------------------------

	// Generate the basic SRW XML elements 'recordPacking' and 'recordSchema':
	function srwGeneratePackingSchema(&$thisObject, $srwPacking, $srwSchema)
	{
		// available schemas taken from <http://www.loc.gov/z3950/agency/zing/srw/record-schemas.html>
		$srwSchemas = array("dc" => "info:srw/schema/1/dc-v1.1",
							"diag" => "info:srw/schema/1/diagnostic-v1.1",
							"zeerex" => "http://explain.z3950.org/dtd/2.0/",
							"mods" => "info:srw/schema/1/mods-v3.0",
							"onix" => "info:srw/schema/1/onix-v2.0",
							"marcxml" => "info:srw/schema/1/marcxml-v1.1",
							"ead" => "info:srw/schema/1/ead-2002",
							"zthes" => "http://zthes.z3950.org/xml/0.5/",
							"ccg" => "http://srw.cheshire3.org/schemas/ccg/1.0/",
							"rec" => "info:srw/schema/2/rec-1.0",
							"server-choice" => "info:srw/schema/1/server-choice",
							"xpath" => "info:srw/schema/1/xpath-1.0");

		$srwRecordPackingBranch = new XMLBranch("srw:recordPacking");
		$srwRecordPackingBranch->setTagContent($srwPacking);
		$thisObject->addXMLBranch($srwRecordPackingBranch);

		$srwRecordSchemaBranch = new XMLBranch("srw:recordSchema");
		$srwRecordSchemaBranch->setTagContent($srwSchemas[$srwSchema]);
		$thisObject->addXMLBranch($srwRecordSchemaBranch);
	}

	// --------------------------------------------------------------------
?>
