<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./search.php
	// Created:    30-Jul-02, 17:40
	// Modified:   02-Oct-04, 18:04

	// This is the main script that handles the search query and displays the query results.
	// Supports three different output styles: 1) List view, with fully configurable columns -> displayColumns() function
	// 2) Details view, shows all fields -> displayDetails() function; 3) Citation view -> generateCitations() function

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
	// Incorporate some include files:
	include 'db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'header.inc.php'; // include header
	include 'results_header.inc.php'; // include results header
	include 'footer.inc.php'; // include footer
	include 'include.inc.php'; // include common functions
	include "ini.inc.php"; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// EXTRACT FORM VARIABLES

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// Extract the form used for searching:
	$formType = $_REQUEST['formType'];
	
	// Extract the type of display requested by the user. Normally, this will be one of the following:
	//  - '' => if the 'submit' parameter is empty, this will produce the default columnar output style ('displayColumns()' function)
	//  - 'Display' => display details for each of the selected records ('displayDetails()' function)
	//  - 'Cite' => build a proper citation for each of the selected records ('generateCitations()' function)
	// Note that the 'submit' parameter can be also one of the following:
	//   - 'RSS' => these value gets included within the 'track' link (in the page header) and will cause 'search.php' to return results as RSS feed
	//   - 'Search', 'Show' or 'Hide' => these values change/refine the search results or their appearance on screen (how many entries & which columns get displayed)
	//   - 'Add', 'Remove', 'Remember' or 'Forget' => these values will trigger actions that act on the selected records
	if (isset($_REQUEST['submit']))
		$displayType = $_REQUEST['submit'];
	else
		$displayType = "";


	// we need to check if the user is allowed to view records with the specified display type:
	if ($displayType == "Display")
	{
		if (isset($_SESSION['user_permissions']) AND !ereg("allow_details_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain 'allow_details_view'...
		{
			// save an appropriate error message:
			$HeaderString = "<b><span class=\"warning\">You have no permission to display any record details!</span></b>";
	
			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
	
			header("Location: index.php"); // redirect to main page ('index.php')
	
			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}
	elseif ($displayType == "Cite")
	{
		if (isset($_SESSION['user_permissions']) AND !ereg("allow_cite", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain 'allow_cite'...
		{
			// save an appropriate error message:
			$HeaderString = "<b><span class=\"warning\">You have no permission to use the cite feature!</span></b>";
	
			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
	
			if (ereg(".+extract.php", $_SERVER['HTTP_REFERER'])) // if the query was submitted by 'extract.php'
				header("Location: " . $_SERVER['HTTP_REFERER']); // redirect to calling page
			else
				header("Location: index.php"); // redirect to main page ('index.php')
	
			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}
	elseif (empty($displayType) AND ereg(".+search.php", $_SERVER['HTTP_REFERER']))
	{
		// by restricting this if clause to scripts that end with 'search.php', we exclude 'show.php' to allow for SQL queries like : 'show.php?date=...&when=...&range=...' and 'show.php?year=...'
		// (and if the referer variable is empty this if clause won't apply either)

		if (isset($_SESSION['user_permissions']) AND !ereg("allow_sql_search", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable does NOT contain 'allow_sql_search'...
		{
			// save an appropriate error message:
			$HeaderString = "<b><span class=\"warning\">You have no permission to perform custom SQL searches!</span></b>";
	
			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
	
			if (ereg(".+sql_search.php", $_SERVER['HTTP_REFERER'])) // if the sql query was entered in the form provided by 'sql_search.php'
				header("Location: " . $_SERVER['HTTP_REFERER']); // redirect to calling page
			else
				header("Location: index.php"); // redirect to main page ('index.php')
	
			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}


	// For a given display type, extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// Extract other variables from the request:
	if (isset($_REQUEST['sqlQuery']))
		$sqlQuery = $_REQUEST['sqlQuery'];
	else
		$sqlQuery = "";
	if (ereg("%20", $sqlQuery)) // if '$sqlQuery' still contains URL encoded data... ('%20' is the URL encoded form of a space, see note below!)
		$sqlQuery = rawurldecode($sqlQuery); // URL decode SQL query (it was URL encoded before incorporation into hidden tags of the 'groupSearch', 'refineSearch', 'displayOptions' and 'queryResults' forms to avoid any HTML syntax errors)
											// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
											//       But, opposed to that, URL encoded data that are included within a form by means of a hidden form tag will *NOT* get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

	if (isset($_REQUEST['showQuery']))
		$showQuery = $_REQUEST['showQuery'];
	else
		$showQuery = "";

	if (isset($_REQUEST['showLinks']))
		$showLinks = $_REQUEST['showLinks'];
	else
		$showLinks = "";

	if (isset($_REQUEST['showRows']))
		$showRows = $_REQUEST['showRows'];
	else
		$showRows = 0;

	if (isset($_REQUEST['rowOffset']))
	{
		// Note: Besides passing the current value of '$rowOffset' within GET queries, this parameter was also included as a hidden tag into the 'queryResults' form.
		//       This was done, so that the correct offset could be re-applied after the user pressed either of the 'Add', 'Remove', 'Remember' or 'Forget' buttons.
		//       However, '$rowOffset' MUST NOT be set if the user clicked the 'Display' or 'Cite' button within the 'queryResults' form!
		//       Therefore, we'll trap this case here:
		if (($formType != "queryResults") OR ($formType == "queryResults" AND !ereg("^(Display|Cite)$", $displayType)))
			$rowOffset = $_REQUEST['rowOffset'];
	}
	else
		$rowOffset = 0;

	// In order to generalize routines we have to query further variables here:
	if (isset($_REQUEST['citeStyleSelector']))
		$citeStyle = $_REQUEST['citeStyleSelector']; // get the cite style chosen by the user (only occurs in 'extract.php' form and in query result lists)
	else
		$citeStyle = "";
	if (ereg("%20", $citeStyle)) // if '$citeStyle' still contains URL encoded data... ('%20' is the URL encoded form of a space, see note below!)
		$citeStyle = rawurldecode($citeStyle); // ...URL decode 'citeStyle' statement (it was URL encoded before incorporation into a hidden tag of the 'sqlSearch' form to avoid any HTML syntax errors)
													// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
													//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

	if (isset($_REQUEST['citeOrder']))
		$citeOrder = $_REQUEST['citeOrder']; // get information how the data should be sorted (only occurs in 'extract.php'/'sql_search' forms and in query result lists). If this param is set to 'Year', records will be listed in blocks sorted by year.
	else
		$citeOrder = "";

	if (isset($_REQUEST['orderBy']))
		$orderBy = $_REQUEST['orderBy']; // extract the current ORDER BY parameter so that it can be re-applied when displaying details (only occurs in query result lists)
	else
		$orderBy = "";
	if (ereg("%20", $orderBy)) // if '$orderBy' still contains URL encoded data... ('%20' is the URL encoded form of a space, see note below!)
		$orderBy = rawurldecode($orderBy); // ...URL decode 'orderBy' statement (it was URL encoded before incorporation into a hidden tag of the 'queryResults' form to avoid any HTML syntax errors)
										// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
										//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

	if ($orderBy == '') // if there's no ORDER BY parameter...
		$orderBy = "author, year DESC, publication"; // ...use the default ORDER BY clause

	if (isset($_REQUEST['headerMsg']))
		$headerMsg = $_REQUEST['headerMsg']; // get any custom header message
						// Note: this feature is provided in 'search.php' so that it's possible to include an information string within a link. This info string could
						//       e.g. describe who's publications are being displayed (e.g.: "Publications of Matthias Steffens:"). I.e., a link pointing to a persons own
						//       publications can include the appropriate owner information (it will show up as header message)
	else
		$headerMsg = "";

	if (isset($_REQUEST['oldQuery']))
		$oldQuery = $_REQUEST['oldQuery']; // get the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	else
		$oldQuery = "";

	// Note: support for keeping the selection state of records across different pages/logins isn't fully implemented yet!
	// Actually, I did remove the 'Remember' and 'Forget' buttons again from the interface but the code is still in place (yet not completed...)
	if (isset($_REQUEST['selectedRecords']))
		$selectedRecordsArray = $_REQUEST['selectedRecords']; // get the serials of all previously selected records (which have been saved by use of the 'Remember' button)
	else
		$selectedRecordsArray = "";

	// get the referring URL (if any):
	if (isset($_SERVER['HTTP_REFERER']))
		$referer = $_SERVER['HTTP_REFERER'];
	else // as an example, 'HTTP_REFERER' won't be set if a user clicked on a URL of type '.../show.php?record=12345' within an email announcement
		$referer = ""; // if there's no HTTP referer available we provide the empty string here

	// Extract checkbox variable values from the request:
	if (isset($_REQUEST['marked']))
		$recordSerialsArray = $_REQUEST['marked']; // extract the values of all checked checkboxes (i.e., the serials of all selected records)
	else
		$recordSerialsArray = array();

	// check if the user did mark any checkboxes (and set up variables accordingly, they will be used within the 'displayDetails()', 'generateCitations()' and 'modifyUserGroups()' functions)
	if (ereg(".+search.php", $referer) AND empty($recordSerialsArray)) // no checkboxes were marked
		$nothingChecked = true;
	else // some checkboxes were marked -OR- the query resulted from another script like 'show.php' or 'rss.php' (which has no checkboxes to mark!)
		$nothingChecked = false;

	

	// --------------------------------------------------------------------
	
	// VERIFY SQL QUERY:

	// For a normal user we only allow the use of SELECT queries (the admin is allowed to do everything that is allowed by his GRANT privileges):
	// NOTE: This does only provide for minimal security!
	//		 To avoid further security risks you should grant the mysql user (who's specified in 'db.inc.php') only those
	//		 permissions that are required to access the literature database. This can be done by use of a GRANT statement:
	//		 GRANT SELECT,INSERT,UPDATE,DELETE ON MYSQL_DATABASE_NAME_GOES_HERE.* TO MYSQL_USER_NAME_GOES_HERE@localhost IDENTIFIED BY 'MYSQL_PASSWORD_GOES_HERE';

	// if the sql query isn't build from scratch but is accepted from user input (which is the case for the forms 'sqlSearch' and 'refineSearch'):
	if (eregi("(sql|refine)Search", $formType)) // the user used 'sql_search.php' -OR- the "Search within Results" form above the query results list (that was produced by 'search.php')
	{
		if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail))) // if the user isn't logged in -OR- any normal user is logged in...
		{
			$notPermitted = false;

			// ...and the user did use anything other than a SELECT query:
			if (!eregi("^SELECT", $sqlQuery))
			{
				$notPermitted = true;
				// save an appropriate error message:
				$HeaderString = "<b><span class=\"warning\">You're only permitted to execute SELECT queries!</span></b>";
			}
			// ...or the user tries to query anything other than the 'refs' or 'user_data' table:
			elseif (!preg_match("/FROM refs( LEFT JOIN user_data ON serial ?= ?record_id AND user_id ?= ?\d*)?(?= WHERE| ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i", $sqlQuery))
			{
				$notPermitted = true;
				// save an appropriate error message:
				$HeaderString = "<b><span class=\"warning\">You have no permission to perform this query!</span></b>";
			}

			if ($notPermitted == true)
			{
				// Write back session variable:
				saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
	
				if (eregi(".+sql_search.php", $referer)) // if the sql query was entered in the form provided by 'sql_search.php'
					header("Location: $referer"); // relocate back to the calling page
				else // if the user didn't come from 'sql_search.php' (e.g., if he attempted to hack parameters of a GET query directly)
					header("Location: index.php"); // relocate back to the main page
				exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
			}
		}
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase($oldQuery); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	if (isset($_POST["loginEmail"]))
		$loginEmail = $_POST["loginEmail"]; // extract the email address of the currently logged in user

	if (isset($_SESSION['loginEmail'])) // if a user is logged in...
		$userID = getUserID($loginEmail); // ...get the user's 'user_id' using his/her 'loginEmail' (function 'getUserID()' is defined in 'include.inc.php')
	else
		$userID = 0; // set variable to zero (a user with '$userID = 0' definitely doesn't exist) in order to prevent 'Undefined variable...' messages

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY from user input provided by any of the search forms:

	// --- Form 'sql_search.php': ------------------
	if ($formType == "sqlSearch") // the user either used the 'sql_search.php' form for searching -OR- used scripts like 'show.php' or 'rss.php' (which also use 'formType=sqlSearch')...
		{
			// verify the SQL query specified by the user and modify it if security concerns are encountered:
			// (this function does add/remove user-specific query code as required and will fix problems with escape sequences within the SQL query)
			$query = verifySQLQuery($sqlQuery, $referer, $displayType, $showLinks); // function 'verifySQLQuery()' is defined in 'include.inc.php' (since it's also used by 'rss.php')
		}

	// --- Form 'simple_search.php': ---------------
	elseif ($formType == "simpleSearch") // the user used the 'simple_search.php' form for searching...
		{
			$query = extractFormElementsSimple($showLinks);
		}

	// --- Form 'library_search.php': --------------
	elseif ($formType == "librarySearch") // the user used the 'library_search.php' form for searching...
		{
			$query = extractFormElementsLibrary($showLinks);
		}

	// --- Form 'advanced_search.php': -------------
	elseif ($formType == "advancedSearch") // the user used the 'advanced_search.php' form for searching...
		{
			$query = extractFormElementsAdvanced($showLinks, $userID);
		}

	// --- Form within 'search.php': ---------------
	elseif ($formType == "refineSearch" OR $formType == "displayOptions") // the user used the "Search within Results" (or "Display Options") form above the query results list (that was produced by 'search.php')
		{
			$query = extractFormElementsRefineDisplay($displayType, $sqlQuery, $showLinks, $userID);
		}

	// --- Form within 'search.php': ---------------
	elseif ($formType == "queryResults") // the user clicked one of the buttons under the query results list (that was produced by 'search.php')
		{
			$query = extractFormElementsQueryResults($displayType, $showLinks, $citeOrder, $orderBy, $userID, $sqlQuery, $referer, $recordSerialsArray);
		}

	// --- Form 'extract.php': ---------------------
	elseif ($formType == "extractSearch") // the user used the 'extract.php' form for searching...
		{
			$query = extractFormElementsExtract($citeOrder);
		}

	// --- My Refs Search Form within 'index.php': -------------------
	elseif ($formType == "myRefsSearch") // the user used the 'Show My Refs' search form on the main page ('index.php') for searching...
		{
			$query = extractFormElementsMyRefs($showLinks, $loginEmail, $userID);
		}

	// --- Quick Search Form within 'index.php': ---------------------
	elseif ($formType == "quickSearch") // the user used the 'Quick Search' form on the main page ('index.php') for searching...
		{
			$query = extractFormElementsQuick($showLinks);
		}

	// --- My Groups Search Form within 'index.php': ---------------------
	elseif ($formType == "groupSearch") // the user used the 'Show My Group' form on the main page ('index.php') or above the query results list (that was produced by 'search.php')
		{
			$query = extractFormElementsGroup($sqlQuery, $showLinks, $userID);
		}

	// --------------------------------------------------------------------

	// this is to support the '$fileVisibilityException' feature from 'ini.inc.php':
	if (!preg_match("/SELECT.+$fileVisibilityException[0].+FROM/i", $query))
	{
		$query = ereg_replace("(, orig_record)?(, serial)?(, file, url, doi)? FROM refs", ", $fileVisibilityException[0]\\1\\2\\3 FROM refs",$query); // add column that's given in '$fileVisibilityException'
		$addCounterMax = 1; // this will ensure that the added column won't get displayed within the 'displayColumns()' function
	}
	else
		$addCounterMax = 0;


	// (3) RUN QUERY, (4) DISPLAY HEADER & RESULTS

	// (3) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query, $oldQuery); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	// (4a) DISPLAY header:
	// First, build the appropriate SQL query in order to embed it into the 'your query' URL:
	if ($showLinks == "1")
		$query = str_replace(', file, url, doi FROM refs',' FROM refs',$query); // strip 'file', 'url' & 'doi' columns from SQL query

	$query = str_replace(', serial FROM refs',' FROM refs',$query); // strip 'serial' column from SQL query

	$query = str_replace(', orig_record FROM refs',' FROM refs',$query); // strip 'orig_record' column from SQL query

	$query = str_replace(", $fileVisibilityException[0] FROM refs",' FROM refs',$query); // strip column that's given in '$fileVisibilityException' (defined in 'ini.inc.php')

	if (ereg("(simple|advanced|library|quick)Search", $formType)) // if $formType is "simpleSearch", "advancedSearch", "librarySearch" or "quickSearch" and there is more than one WHERE clause (indicated by '...AND...'):
		$query = str_replace('WHERE serial RLIKE ".+" AND','WHERE',$query); // strip first WHERE clause (which was added only due to an internal workaround)

	$queryURL = rawurlencode($query); // URL encode SQL query

	if (!eregi("^SELECT", $query)) // for queries other than SELECT queries (e.g. UPDATE, DELETE or INSERT queries that were executed by the admin via use of 'sql_search.php')
		$affectedRows = ($result ? mysql_affected_rows ($connection) : 0); // get the number of rows that were modified (or return 0 if an error occurred)

	// Second, find out how many rows are available:
	$rowsFound = @ mysql_num_rows($result);
	if ($rowsFound > 0) // If there were rows found ...
		{
			// ... setup variables in order to facilitate "previous" & "next" browsing:
			// a) Set '$rowOffset' to zero if not previously defined, or if a wrong number (<=0) was given
			if (empty($rowOffset) || ($rowOffset <= 0) || ($showRows >= $rowsFound)) // the third condition is only necessary if '$rowOffset' gets embedded within the 'displayOptions' form (see function 'buildDisplayOptionsElements()' in 'include.inc.php')
				$rowOffset = 0;

			// Adjust the '$showRows' value if not previously defined, or if a wrong number (<=0 or float) was given
			if (empty($showRows) || ($showRows <= 0) || !ereg("^[0-9]+$", $showRows))
				$showRows = 5;

			// NOTE: The current value of '$rowOffset' is embedded as hidden tag within the 'displayOptions' form. By this, the current row offset can be re-applied
			//       after the user pressed the 'Show'/'Hide' button within the 'displayOptions' form. But then, to avoid that browse links don't behave as expected,
			//       we need to adjust the actual value of '$rowOffset' to an exact multiple of '$showRows':
			$offsetRatio = ($rowOffset / $showRows);
			if (!is_integer($offsetRatio)) // check whether the value of the '$offsetRatio' variable is not an integer
			{ // if '$offsetRatio' is a float:
				$offsetCorrectionFactor = floor($offsetRatio); // get it's next lower integer
				if ($offsetCorrectionFactor != 0)
					$rowOffset = ($offsetCorrectionFactor * $showRows); // correct the current row offset to the closest multiple of '$showRows' *below* the current row offset
				else
					$rowOffset = 0;
			}
			
			// b) The "Previous" page begins at the current offset LESS the number of rows per page
			$previousOffset = $rowOffset - $showRows;
			
			// c) The "Next" page begins at the current offset PLUS the number of rows per page
			$nextOffset = $rowOffset + $showRows;
			
			// d) Seek to the current offset
			mysql_data_seek($result, $rowOffset);
		}
	else // set variables to zero in order to prevent 'Undefined variable...' messages when nothing was found ('$rowsFound = 0'):
		{
			$rowOffset = 0;
			$previousOffset = 0;
			$nextOffset = 0;
		}

	// Third, calculate the maximum result number on each page ('$showMaxRow' is required as parameter to the 'displayDetails()' function)
	if (($rowOffset + $showRows) < $rowsFound)
		$showMaxRow = ($rowOffset + $showRows); // maximum result number on each page
	else
		$showMaxRow = $rowsFound; // for the last results page, correct the maximum result number if necessary

	// Fourth, check if there's some query URL available pointing to a previous search results page
	if ($oldQuery == "")
		{
			// If there's no query URL available, we build the *full* query URL for the page currently displayed. The variable '$oldQuery' will get included into every 'browse'/'field title'/'display details'/'edit record'/'add record' link. Plus it will get written into a hidden form tag so that it's available on 'display details' (batch display)
			// The variable '$oldQuery' gets routed thru the 'display details' and 'record.php' forms to facilitate a link to the current results page on the subsequent receipt page that follows any add/edit/delete action!
			$oldQuery = "sqlQuery=" . $query . "&amp;showQuery=" . $showQuery . "&amp;showLinks=" . $showLinks . "&amp;formType=sqlSearch&amp;showRows=" . $showRows . "&amp;rowOffset=" . $rowOffset . "&amp;submit=" . $displayType . "&amp;citeStyleSelector=" . rawurlencode($citeStyle) . "&amp;citeOrder=" . $citeOrder;
		}
	else // there's already a query URL available
		// Note: If there's an existing 'oldQuery', a new 'oldQuery' will be generated only, if the output is routed thru the 'displayColumns()' function!
		//       This will only happen if $displayType == '' (i.e., not 'Display', 'Cite' or 'RSS').
		{
			if (ereg('sqlQuery%3D', $oldQuery)) // if '$oldQuery' still contains URL encoded data... ('%3D' is the URL encoded form of '=', see note below!)
				$oldQuery = rawurldecode($oldQuery); // ...URL decode old query URL (it was URL encoded before incorporation into a hidden tag of the 'queryResults' form to avoid any HTML syntax errors)
												// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
												//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!
			$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "
			$oldQuery = ereg_replace('(\\\\)+','\\\\',$oldQuery);
		}

	// Finally, build the appropriate header string (which is required as parameter to the 'showPageHeader()' function):
	if (!isset($_SESSION['HeaderString'])) // if there's no stored message available
	{
		if (!empty($headerMsg)) // if there's a custom header message available, e.g. one that describes who's literature is being displayed...
		{
			$HeaderString = $headerMsg; // ...we use that string as header message ('$headerMsg' could contain something like: "Literature of Matthias Steffens:")
		}
		else // provide the default message:
		{
			if (eregi("^SELECT", $query)) // for SELECT queries:
			{
				if ($rowsFound == 1)
					$HeaderStringPart = " record ";
				else
					$HeaderStringPart = " records ";

				$HeaderStringPart .= "found matching ";

				if (isset($_SESSION['user_permissions']) AND ereg("allow_sql_search", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_sql_search'...
					// ...generate a link to 'sql_search.php' with a custom SQL query that matches the current result set & display options:
					$HeaderString = $HeaderStringPart . "<a href=\"sql_search.php?customQuery=1&amp;sqlQuery=$queryURL&amp;showQuery=$showQuery&amp;showLinks=$showLinks&amp;showRows=$showRows&amp;submit=$displayType&amp;citeStyleSelector=" . rawurlencode($citeStyle) . "&amp;citeOrder=$citeOrder&amp;oldQuery=" . rawurlencode($oldQuery) . "\" title=\"modify your current query\">your query</a>";
				else // use of 'sql_search.php' isn't allowed for this user
					$HeaderString = $HeaderStringPart . "your query"; // so we ommit the link

				if (isset($_SESSION['user_permissions']) AND ((isset($_SESSION['loginEmail']) AND ereg("(allow_user_queries|allow_rss_feeds)", $_SESSION['user_permissions'])) OR (!isset($_SESSION['loginEmail']) AND ereg("allow_rss_feeds", $_SESSION['user_permissions'])))) // if the 'user_permissions' session variable contains 'allow_rss_feeds' -OR- if logged in, aditionally: 'allow_user_queries':
					$HeaderString .= " (";

				if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_queries", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_queries'...
				{
					// ...we'll show a link to save the current query:
					$HeaderString .= "<a href=\"query_manager.php?customQuery=1&amp;sqlQuery=$queryURL&amp;showQuery=$showQuery&amp;showLinks=$showLinks&amp;showRows=$showRows&amp;displayType=$displayType&amp;citeStyleSelector=" . rawurlencode($citeStyle) . "&amp;citeOrder=$citeOrder&amp;viewType=$viewType&amp;oldQuery=" . rawurlencode($oldQuery) . "\" title=\"save your current query\">save</a>";

					if (isset($_SESSION['user_permissions']) AND ereg("allow_rss_feeds", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_rss_feeds', we'll insert a pipe between the 'save' and 'track' links...
						$HeaderString .= " | ";
				}

				if (isset($_SESSION['user_permissions']) AND ereg("allow_rss_feeds", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_rss_feeds', we'll show a link that will generate a dynamic RSS feed for the current query...
				{
					// ...extract the 'WHERE' clause from the SQL query to include it within the RSS link:
					$queryWhereClause = preg_replace("/^.+?WHERE (.+?)(?= ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$).*?$/","\\1",$query);
				
					// ...and display a link that will generate a dynamic RSS feed for the current query:
					$HeaderString .= "<a href=\"rss.php?where=" . rawurlencode($queryWhereClause) . "&amp;showRows=$showRows\" title=\"track newly added records matching your current query by subscribing to this RSS feed\">track</a>";
				}

				if (isset($_SESSION['user_permissions']) AND ((isset($_SESSION['loginEmail']) AND ereg("(allow_user_queries|allow_rss_feeds)", $_SESSION['user_permissions'])) OR (!isset($_SESSION['loginEmail']) AND ereg("allow_rss_feeds", $_SESSION['user_permissions'])))) // if the 'user_permissions' session variable contains 'allow_rss_feeds' -OR- if logged in, aditionally: 'allow_user_queries':
					$HeaderString .= ")";

				if ($showQuery == "1")
					$HeaderString .= ":\n<br>\n<br>\n<code>$query</code>";
				else // $showQuery == "0" or wasn't specified
					$HeaderString .= ":";
			
				if ($rowsFound > 0)
					$HeaderString = ($rowOffset + 1) . "&#8211;" . $showMaxRow . " of " . $rowsFound . $HeaderString;
				elseif ($rowsFound == 0)
					$HeaderString = $rowsFound . $HeaderString;
				else
					$HeaderString = $HeaderString; // well, this is actually bad coding but I do it for clearity reasons...
			}
			else // for queries other than SELECT queries (e.g. UPDATE, DELETE or INSERT queries that were executed by the admin via use of 'sql_search.php') display the number of rows that were modified:
			{
				if ($affectedRows == 1)
					$HeaderStringPart = " record was ";
				else
					$HeaderStringPart = " records were ";

				if ($showQuery == "1")
					$HeaderString = $affectedRows . $HeaderStringPart . "affected by <a href=\"sql_search.php?customQuery=1&amp;sqlQuery=$queryURL&amp;showQuery=$showQuery&amp;showLinks=$showLinks&amp;showRows=$showRows&amp;submit=$displayType&amp;citeStyleSelector=" . rawurlencode($citeStyle) . "&amp;citeOrder=$citeOrder&amp;oldQuery=" . rawurlencode($oldQuery) . "\">your query</a>:\n<br>\n<br>\n<code>$query</code>";
				else // $showQuery == "0" or wasn't specified
					$HeaderString = $affectedRows . $HeaderStringPart . "affected by <a href=\"sql_search.php?customQuery=1&amp;sqlQuery=$queryURL&amp;showQuery=$showQuery&amp;showLinks=$showLinks&amp;showRows=$showRows&amp;submit=$displayType&amp;citeStyleSelector=" . rawurlencode($citeStyle) . "&amp;citeOrder=$citeOrder&amp;oldQuery=" . rawurlencode($oldQuery) . "\">your query</a>:";
			}
		}
	}
	else
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}


	// Now, show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// Then, call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- Query Results", "index,follow", "Results from the " . htmlentities($officialDatabaseName), "", true, "", $viewType);
	if ($viewType != "Print") // Note: we ommit the visible header in print view! ('viewType=Print')
		showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, $oldQuery);


	// (4b) DISPLAY results:
	if ($displayType == "Display") // display details for each of the selected records
		displayDetails($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $nothingChecked, $citeStyle, $citeOrder, $orderBy, $showMaxRow, $headerMsg, $userID, $viewType, $selectedRecordsArray);

	elseif ($displayType == "Cite") // build a proper citation for each of the selected records
		generateCitations($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $nothingChecked, $citeStyle, $citeOrder, $orderBy, $headerMsg, $userID, $viewType, $selectedRecordsArray);

	else // show all records in columnar style
		displayColumns($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $nothingChecked, $citeStyle, $citeOrder, $headerMsg, $userID, $displayType, $viewType, $selectedRecordsArray, $addCounterMax);

	// --------------------------------------------------------------------

	// (5) CLOSE CONNECTION
	disconnectFromMySQLDatabase($oldQuery); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// SHOW THE RESULTS IN AN HTML <TABLE> (columnar layout)
	function displayColumns($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $nothingChecked, $citeStyle, $citeOrder, $headerMsg, $userID, $displayType, $viewType, $selectedRecordsArray, $addCounterMax)
	{
		global $oldQuery; // This is required since the 'add record' link gets constructed outside this function, otherwise it would still contain the older query URL!)
		global $filesBaseURL; // defined in 'ini.inc.php'
		global $markupSearchReplacePatterns; // defined in 'ini.inc.php'
		global $fileVisibility; // defined in 'ini.inc.php'
		global $fileVisibilityException; // defined in 'ini.inc.php'

		if (eregi(".+LIMIT *[0-9]+",$query)) // query does contain the 'LIMIT' parameter
			$orderBy = ereg_replace(".+ORDER BY (.+) LIMIT.+","\\1",$query); // extract 'ORDER BY'... parameter (without including any 'LIMIT' parameter)
		else // query does not contain the 'LIMIT' parameter
			$orderBy = ereg_replace(".+ORDER BY (.+)","\\1",$query); // extract 'ORDER BY'... parameter
			
	if (!ereg("^(Add|Remove)$", $displayType) OR (ereg("^(Add|Remove)$", $displayType) AND ($nothingChecked == false)))
	{
		// If the query has results ...
		if ($rowsFound > 0)
		{
			// BEGIN RESULTS HEADER --------------------
			// 1) First, initialize some variables that we'll need later on
			if ($showLinks == "1")
				$CounterMax = 3; // When displaying a 'Links' column truncate the last three columns (i.e., hide the 'file', 'url' and 'doi' columns)
			else
				$CounterMax = 0; // Otherwise don't hide any columns

			// count the number of fields
			$fieldsFound = mysql_num_fields($result);
			// hide those last columns that were added by the script and not by the user
			$fieldsToDisplay = $fieldsFound-(2+$CounterMax+$addCounterMax); // (2+$CounterMax) -> $CounterMax is increased by 2 in order to hide the 'orig_record' & 'serial' columns (which were added to make checkboxes & dup warning work)
																			// $addCounterMax is set to 1 when the field given in '$fileVisibilityException[0]' (defined in 'ini.inc.php') was added to the query, otherwise '$addCounterMax = 0'
	
			// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
			if ($showLinks == "1")
				$NoColumns = (1+$fieldsToDisplay+1); // add checkbox & Links column
			else
				$NoColumns = (1+$fieldsToDisplay); // add checkbox column
	
			// Although there might be an (older) query URL available, we build a new query URL for the page currently displayed. The variable '$oldQuery' will get included into every 'browse'/'field title'/'display details'/'edit record'/'add record' link. Plus it will get written into a hidden form tag so that it's available on 'display details' (batch display)
			// The variable '$oldQuery' gets routed thru the 'display details' and 'record.php' forms to facilitate a link to the current results page on the subsequent receipt page that follows any add/edit/delete action!
			$oldQuery = "sqlQuery=" . $query . "&amp;showQuery=" . $showQuery . "&amp;showLinks=" . $showLinks . "&amp;formType=sqlSearch&amp;showRows=" . $showRows . "&amp;rowOffset=" . $rowOffset . "&amp;submit=" . $displayType . "&amp;citeStyleSelector=" . rawurlencode($citeStyle) . "&amp;citeOrder=" . $citeOrder;
	
	
			// Note: we ommit the 'Search Within Results' form in print view! ('viewType=Print')
			if ($viewType != "Print")
			{
				// 2) Build a TABLE with forms containing options to show the user's groups, refine the search results or change the displayed columns:
	
				//    2a) Build a FORM with a popup containing the user's groups:
				$formElementsGroup = buildGroupSearchElements("search.php", $queryURL, $query, $showQuery, $showLinks, $showRows); // function 'buildGroupSearchElements()' is defined in 'include.inc.php'
	
				//    2b) Build a FORM containing options to refine the search results:
				//        First, specify which colums should be available in the popup menu (column items must be separated by a comma or comma+space!):
				$refineSearchSelectorElements1 = "author, title, year, keywords, abstract, type, publication, abbrev_journal, volume, issue, pages, thesis, publisher, place, editor, series_title, area, notes, location, call_number"; // these columns will be always visible (no matter whether the user is logged in or not)
				$refineSearchSelectorElements2 = "marked, copy, selected, user_keys, user_notes, user_file, user_groups, bibtex_id"; // these columns will be only visible to logged in users (in this case: the user specific fields from table 'user_data')
				$refineSearchSelectorElementSelected = "author"; // this column will be selected by default
				//        Call the 'buildRefineSearchElements()' function (defined in 'include.inc.php') which does the actual work:
				$formElementsRefine = buildRefineSearchElements("search.php", $queryURL, $showQuery, $showLinks, $showRows, $refineSearchSelectorElements1, $refineSearchSelectorElements2, $refineSearchSelectorElementSelected);
	
				//    2c) Build a FORM containing display options (show/hide columns or change the number of records displayed per page):
				//        Again, specify which colums should be available in the popup menu (column items must be separated by a comma or comma+space!):
				$displayOptionsSelectorElements1 = "author, title, year, keywords, abstract, type, publication, abbrev_journal, volume, issue, pages, thesis, publisher, place, editor, series_title, area, notes, location, call_number"; // these columns will be always visible (no matter whether the user is logged in or not)
				$displayOptionsSelectorElements2 = "marked, copy, selected, user_keys, user_notes, user_file, user_groups, bibtex_id"; // these columns will be only visible to logged in users (in this case: the user specific fields from table 'user_data')
				$displayOptionsSelectorElementSelected = "author"; // this column will be selected by default
				//        Call the 'buildDisplayOptionsElements()' function (defined in 'include.inc.php') which does the actual work:
				$formElementsDisplayOptions = buildDisplayOptionsElements("search.php", $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $displayOptionsSelectorElements1, $displayOptionsSelectorElements2, $displayOptionsSelectorElementSelected, $fieldsToDisplay);
	
				echo displayResultsHeader($formElementsGroup, $formElementsRefine, $formElementsDisplayOptions); // function 'displayResultsHeader()' is defined in 'results_header.inc.php'
			}
	
	
			//    and insert a divider line (which separates the 'Search Within Results' form from the browse links & results data below):
			if ($viewType != "Print") // Note: we ommit the divider line in print view! ('viewType=Print')
				echo "\n<hr align=\"center\" width=\"93%\">";
	
			// 3) Build a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages
			//    call the 'buildBrowseLinks()' function (defined in 'include.inc.php'):
			$BrowseLinks = buildBrowseLinks("search.php", $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, "25", "sqlSearch", "", $citeStyle, $citeOrder, $orderBy, $headerMsg, $viewType);
			echo $BrowseLinks;
	
	
			// 4) Start a FORM
			echo "\n<form action=\"search.php\" method=\"POST\" name=\"queryResults\">"
					. "\n<input type=\"hidden\" name=\"formType\" value=\"queryResults\">"
					. "\n<input type=\"hidden\" name=\"submit\" value=\"Display\">" // provide a default value for the 'submit' form tag (then, hitting <enter> within the 'ShowRows' text entry field will act as if the user clicked the 'Display' button)
					. "\n<input type=\"hidden\" name=\"orderBy\" value=\"" . rawurlencode($orderBy) . "\">" // embed the current ORDER BY parameter so that it can be re-applied when displaying details
					. "\n<input type=\"hidden\" name=\"showQuery\" value=\"$showQuery\">" // embed the current value of '$showQuery' so that it's available on 'display details' (batch display) & 'cite'
					. "\n<input type=\"hidden\" name=\"showLinks\" value=\"$showLinks\">" // embed the current value of '$showLinks' so that it's available on 'display details' (batch display) & 'cite'
					. "\n<input type=\"hidden\" name=\"rowOffset\" value=\"$rowOffset\">" // embed the current value of '$rowOffset' so that it can be re-applied after the user pressed either of the 'Add', 'Remove', 'Remember' or 'Forget' buttons within the 'queryResults' form
					// Note: the inclusion of '$rowOffset' here is only meant to support reloading of the same results page again after a user clicked the 'Add', 'Remove', 'Remember' or 'Forget' buttons
					//       However, '$rowOffset' MUST NOT be set if the user clicked the 'Display' or 'Cite' button! Therefore we'll trap for this case at the top of the script.
					. "\n<input type=\"hidden\" name=\"sqlQuery\" value=\"$queryURL\">" // embed the current sqlQuery so that it can be re-applied after the user pressed either of the 'Add', 'Remove', 'Remember' or 'Forget' buttons within the 'queryResults' form
					. "\n<input type=\"hidden\" name=\"oldQuery\" value=\"" . rawurlencode($oldQuery) . "\">"; // embed the current value of '$oldQuery' so that it's available on 'display details' (batch display)
	
	
			// 5) And start a TABLE, with column headers
			echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the database results for your query\">";
			
			//    for the column headers, start a TABLE ROW ...
			echo "\n<tr>";
	
			// ... print a marker ('x') column (which will hold the checkboxes within the results part)
			if ($viewType != "Print") // Note: we ommit the marker column in print view! ('viewType=Print')
				echo "\n\t<th align=\"left\" valign=\"top\">&nbsp;</th>";
	
			// for each of the attributes in the result set...
			for ($i=0; $i<$fieldsToDisplay; $i++)
			{
				// ... and print out each of the attribute names
				// in that row as a separate TH (Table Header)...
				$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
				$HTMLafterLink = "</th>"; // close the table header tag
				// call the 'buildFieldNameLinks()' function (defined in 'include.inc.php'), which will return a properly formatted table header tag holding the current field's name
				// as well as the URL encoded query with the appropriate ORDER clause:
				$tableHeaderLink = buildFieldNameLinks("search.php", $query, $oldQuery, "", $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "", "", "", $viewType);
				echo $tableHeaderLink; // print the attribute name as link
			 }
	
			if ($showLinks == "1")
				{
					$newORDER = ("ORDER BY url DESC, doi DESC"); // Build the appropriate ORDER BY clause to facilitate sorting by Links column
	
					$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
					$HTMLafterLink = "</th>"; // close the table header tag
					// call the 'buildFieldNameLinks()' function (defined in 'include.inc.php'), which will return a properly formatted table header tag holding the current field's name
					// as well as the URL encoded query with the appropriate ORDER clause:
					$tableHeaderLink = buildFieldNameLinks("search.php", $query, $oldQuery, $newORDER, $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "", "Links", "url", $viewType);
					echo $tableHeaderLink; // print the attribute name as link
				}
	
			// Finish the row
			echo "\n</tr>";
			// END RESULTS HEADER ----------------------
			
			// BEGIN RESULTS DATA COLUMNS --------------
			// Fetch one page of results (or less if on the last page)
			// (i.e., upto the limit specified in $showRows) fetch a row into the $row array and ...
			for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
			{
				// ... start a TABLE ROW ...
				echo "\n<tr>";
	
				// ... print a column with a checkbox
				if ($viewType != "Print") // Note: we ommit the marker column in print view! ('viewType=Print')
				{
					echo "\n\t<td align=\"center\" valign=\"top\" width=\"10\">\n\t\t<input type=\"checkbox\" name=\"marked[]\" value=\"" . $row["serial"] . "\" title=\"select this record\">";
		
					if (!empty($row["orig_record"]))
					{
						echo "\n\t\t<br>";
						if ($row["orig_record"] < 0)
							echo "<img src=\"img/ok.gif\" alt=\"(original)\" title=\"original record\" width=\"14\" height=\"16\" hspace=\"0\" border=\"0\">";
						else // $row["orig_record"] > 0
							echo "<img src=\"img/caution.gif\" alt=\"(duplicate)\" title=\"duplicate record\" width=\"5\" height=\"16\" hspace=\"0\" border=\"0\">";
					}
		
					echo "\n\t</td>";
				}
	
				// ... and print out each of the attributes
				// in that row as a separate TD (Table Data)
				// (Note: 'htmlentities($row[$i])' for HTML encoding higher ASCII will only work correctly if character encoding of data is ISO-8859-1!)
				for ($i=0; $i<$fieldsToDisplay; $i++)
				{
					$row[$i] = htmlentities($row[$i]); // HTML encode higher ASCII characters
	
					// the following two lines will fetch the current attribute name:
					$info = mysql_fetch_field ($result, $i); // get the meta-data for the attribute
					$orig_fieldname = $info->name; // get the attribute name

					// Perform search & replace actions on the text of the 'title', 'keywords' and 'abstract' fields:
					// (the array '$markupSearchReplacePatterns' in 'ini.inc.php' defines which search & replace actions will be employed)
					if (ereg("^(title|keywords|abstract)$", $orig_fieldname)) // apply the defined search & replace actions to the 'title', 'keywords' and 'abstract' fields:
						$row[$i] = searchReplaceText($markupSearchReplacePatterns, $row[$i]); // function 'searchReplaceText()' is defined in 'include.inc.php'
	
					echo "\n\t<td valign=\"top\">" . $row[$i] . "</td>";
				}
	
				// embed appropriate links (if available):
				if ($showLinks == "1")
				{
					echo "\n\t<td valign=\"top\">";
	
					if (isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_details_view'...
					{
						// ... display a link that opens the 'details view' for this record:
						if (isset($_SESSION['loginEmail'])) // if a user is logged in, show user specific fields:
							echo "\n\t\t<a href=\"search.php?sqlQuery=SELECT%20author%2C%20title%2C%20type%2C%20year%2C%20publication%2C%20abbrev_journal%2C%20volume%2C%20issue%2C%20pages%2C%20corporate_author%2C%20thesis%2C%20address%2C%20keywords%2C%20abstract%2C%20publisher%2C%20place%2C%20editor%2C%20language%2C%20summary_language%2C%20orig_title%2C%20series_editor%2C%20series_title%2C%20abbrev_series_title%2C%20series_volume%2C%20series_issue%2C%20edition%2C%20issn%2C%20isbn%2C%20medium%2C%20area%2C%20expedition%2C%20conference%2C%20notes%2C%20approved%2C%20location%2C%20call_number%2C%20serial%2C%20marked%2C%20copy%2C%20selected%2C%20user_keys%2C%20user_notes%2C%20user_file%2C%20user_groups%2C%20bibtex_id%2C%20related%20"
								. "FROM%20refs%20LEFT%20JOIN%20user_data%20ON%20serial%20%3D%20record_id%20AND%20user_id%20%3D%20" . $userID . "%20";
						else // if NO user logged in, don't display any user specific fields:
							echo "\n\t\t<a href=\"search.php?sqlQuery=SELECT%20author%2C%20title%2C%20type%2C%20year%2C%20publication%2C%20abbrev_journal%2C%20volume%2C%20issue%2C%20pages%2C%20corporate_author%2C%20thesis%2C%20address%2C%20keywords%2C%20abstract%2C%20publisher%2C%20place%2C%20editor%2C%20language%2C%20summary_language%2C%20orig_title%2C%20series_editor%2C%20series_title%2C%20abbrev_series_title%2C%20series_volume%2C%20series_issue%2C%20edition%2C%20issn%2C%20isbn%2C%20medium%2C%20area%2C%20expedition%2C%20conference%2C%20notes%2C%20approved%2C%20location%2C%20call_number%2C%20serial%20"
								. "FROM%20refs%20";

						echo "WHERE%20serial%20RLIKE%20%22%5E%28" . $row["serial"]
							. "%29%24%22%20ORDER%20BY%20" . rawurlencode($orderBy)
							. "&amp;showQuery=" . $showQuery
							. "&amp;showLinks=" . $showLinks
							. "&amp;formType=sqlSearch"
							. "&amp;viewType=" . $viewType
							. "&amp;submit=Display"
							. "&amp;oldQuery=" . rawurlencode($oldQuery)
							. "\"><img src=\"img/details.gif\" alt=\"details\" title=\"show details\" width=\"9\" height=\"17\" hspace=\"0\" border=\"0\"></a>&nbsp;&nbsp;";
					}
	
					if (isset($_SESSION['user_permissions']) AND ereg("allow_edit", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_edit'...
						// ... display a link that opens the edit form for this record:
						echo "\n\t\t<a href=\"record.php?serialNo=" . $row["serial"] . "&amp;recordAction=edit"
							. "&amp;oldQuery=" . rawurlencode($oldQuery)
							. "\"><img src=\"img/edit.gif\" alt=\"edit\" title=\"edit record\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";
	
					if ((!empty($row["file"])) OR (!empty($row["doi"])) OR (!empty($row["url"])))
						echo "\n\t\t<br>";
	
					// show a link to any corresponding file if one of the following conditions is met:
					// - the variable '$fileVisibility' (defined in 'ini.inc.php') is set to 'everyone'
					// - the variable '$fileVisibility' is set to 'login' AND the user is logged in
					// - the variable '$fileVisibility' is set to 'user-specific' AND the 'user_permissions' session variable contains 'allow_download'
					// - the array variable '$fileVisibilityException' (defined in 'ini.inc.php') contains a pattern (in array element 1) that matches the contents of the field given (in array element 0)
					if ($fileVisibility == "everyone" OR ($fileVisibility == "login" AND isset($_SESSION['loginEmail'])) OR ($fileVisibility == "user-specific" AND (isset($_SESSION['user_permissions']) AND ereg("allow_download", $_SESSION['user_permissions']))) OR (preg_match($fileVisibilityException[1], $row[$fileVisibilityException[0]])))
					{
						if (!empty($row["file"]))// if the 'file' field is NOT empty
						{
							if (ereg("^(http|ftp)://", $row["file"])) // if the 'file' field contains a full URL (starting with "http://" or "ftp://")
								$URLprefix = ""; // we don't alter the URL given in the 'file' field
							else // if the 'file' field contains only a partial path (like 'polarbiol/10240001.pdf') or just a file name (like '10240001.pdf')
								$URLprefix = $filesBaseURL; // use the base URL of the standard files directory as prefix ('$filesBaseURL' is defined in 'ini.inc.php')
	
							if (ereg("\.pdf$", $row["file"])) // if the 'file' field contains a link to a PDF file
								echo "\n\t\t<a href=\"" . $URLprefix . $row["file"] . "\"><img src=\"img/file_PDF.gif\" alt=\"pdf\" title=\"download PDF file\" width=\"17\" height=\"17\" hspace=\"0\" border=\"0\"></a>"; // display a PDF file icon as download link
							else
								echo "\n\t\t<a href=\"" . $URLprefix . $row["file"] . "\"><img src=\"img/file.gif\" alt=\"file\" title=\"download file\" width=\"11\" height=\"15\" hspace=\"0\" border=\"0\"></a>"; // display a generic file icon as download link						
						}
					}
	
					// if a DOI number exists for this record, we'll prefer it as link, otherwise we use the URL (if available):
					// (note, that in column view, we'll use the same icon, no matter if the DOI or the URL is used for the link)
					if (!empty($row["doi"]))
						echo "\n\t\t<a href=\"http://dx.doi.org/" . $row["doi"] . "\"><img src=\"img/link.gif\" alt=\"doi\" title=\"goto web page (via DOI)\" width=\"11\" height=\"8\" hspace=\"0\" border=\"0\"></a>";
					elseif (!empty($row["url"])) // 'htmlentities()' is used to convert any '&' into '&amp;'
						echo "\n\t\t<a href=\"" . htmlentities($row["url"]) . "\"><img src=\"img/link.gif\" alt=\"url\" title=\"goto web page\" width=\"11\" height=\"8\" hspace=\"0\" border=\"0\"></a>";
	
	// 				// as an alternative, print out DOI and URL links individually:
	//
	//				if ((!empty($row["file"])) AND ((!empty($row["doi"])) OR (!empty($row["url"]))))
	//					echo "&nbsp;&nbsp;"; // add some whitespace
	//
	//				if (!empty($row["doi"]))
	//					echo "\n\t\t<a href=\"http://dx.doi.org/" . $row["doi"] . "\"><img src=\"img/doi.gif\" alt=\"doi\" title=\"goto web page (via DOI)\" width=\"20\" height=\"10\" hspace=\"0\" border=\"0\"></a>";
	//
	//				if (!empty($row["doi"]) AND (!empty($row["url"])))
	//					echo "&nbsp;&nbsp;"; // add some whitespace
	//
	//				if (!empty($row["url"]))
	//					echo "\n\t\t<a href=\"" . $row["url"] . "\"><img src=\"img/link.gif\" alt=\"url\" title=\"goto web page\" width=\"11\" height=\"8\" hspace=\"0\" border=\"0\"></a>";
	
					echo "\n\t</td>";
				}
				// Finish the row
				echo "\n</tr>";
			}
			// Finish the table
			echo "\n</table>";
			// END RESULTS DATA COLUMNS ----------------
	
			// BEGIN RESULTS FOOTER --------------------
			// Note: we ommit the results footer in print view! ('viewType=Print')
			if ($viewType != "Print")
			{
				// Again, insert the (already constructed) BROWSE LINKS
				// (i.e., a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages)
				echo $BrowseLinks;
	
				if (isset($_SESSION['user_permissions']) AND ((isset($_SESSION['loginEmail']) AND ereg("(allow_details_view|allow_cite|allow_user_groups|allow_export|allow_batch_export)", $_SESSION['user_permissions'])) OR (!isset($_SESSION['loginEmail']) AND ereg("(allow_details_view|allow_cite)", $_SESSION['user_permissions'])))) // if the 'user_permissions' session variable does contain any of the following: 'allow_details_view', 'allow_cite' -AND- if logged in, aditionally: 'allow_user_groups', 'allow_export', 'allow_batch_export'...
					// ...Insert a divider line (which separates the results data from the forms in the footer):
					echo "\n<hr align=\"center\" width=\"93%\">";
	
				// Build a TABLE containing rows with buttons for displaying/citing selected records
				// Call the 'buildResultsFooter()' function (which does the actual work):
				$ResultsFooter = buildResultsFooter($NoColumns, $showRows, $citeStyle, $selectedRecordsArray);
				echo $ResultsFooter;
			}
			// END RESULTS FOOTER ----------------------
	
			// Finally, finish the form
			echo "\n</form>";
		}
		else
		{
			// Report that nothing was found:
			$nothingFoundFeedback = nothingFound(false); // This is a clumsy workaround: by pretending that there were some records marked by the user ($nothingChecked = false) we force the 'nothingFound()' function to output "Sorry, but your query didn't produce any results!" instead of "No records selected..."
			echo $nothingFoundFeedback;
		}// end if $rowsFound body
	}
	else // if the user clicked either the 'Add' or the 'Remove' button on a search results page but did not mark some checkboxes in front of the records, we display a "No records selected..." warning:
	{
		// Report that nothing was selected:
		$nothingFoundFeedback = nothingFound($nothingChecked);
		echo $nothingFoundFeedback;
	}
	}

	// --------------------------------------------------------------------

	// SHOW THE RESULTS IN AN HTML <TABLE> (horizontal layout)
	function displayDetails($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $nothingChecked, $citeStyle, $citeOrder, $orderBy, $showMaxRow, $headerMsg, $userID, $viewType, $selectedRecordsArray)
	{
		global $filesBaseURL; // defined in 'ini.inc.php'
		global $markupSearchReplacePatterns; // defined in 'ini.inc.php'
		global $databaseBaseURL; // defined in 'ini.inc.php'
		global $fileVisibility;  // defined in 'ini.inc.php'
		global $fileVisibilityException; // defined in 'ini.inc.php'

	// If the query has results ...
	if ($rowsFound > 0) 
	{
		// BEGIN RESULTS HEADER --------------------
		// 1) First, initialize some variables that we'll need later on
		if ($showLinks == "1")
			$CounterMax = 3; // When displaying a 'Links' column truncate the last three columns (i.e., hide the 'file', 'url' and 'doi' columns)
		else
			$CounterMax = 0; // Otherwise don't hide any columns

		if (isset($_SESSION['loginEmail'])) // if a user is logged in...
			$CounterMax = ($CounterMax + 1); // ...we'll also need to hide the 'related' column (which isn't displayed in 'details view' but is only used to generate a link to related records)

		// count the number of fields
		$fieldsFound = mysql_num_fields($result);
		// hide those last columns that were added by the script and not by the user
		$fieldsToDisplay = $fieldsFound-(2+$CounterMax); // (2+$CounterMax) -> $CounterMax is increased by 2 in order to hide the 'orig_record' & 'serial' columns (which were added to make checkboxes & dup warning work)
		// In summary, when displaying a 'Links' column and with a user being logged in, we hide the following fields: 'related, orig_record, serial, file, url, doi' (i.e., truncate the last six columns)

		// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
		if ($showLinks == "1") // in 'display details' layout, we simply set it to a fixed no of columns:
			$NoColumns = 8; // 8 columns: checkbox, 3 x (field name + field contents), links
		else
			$NoColumns = 7; // 7 columns: checkbox, field name, field contents


		// 2) Note: we ommit the 'Search Within Results' form when displaying details! (compare with 'displayColumns()' function)


		// 3) Build a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages
		//    call the 'buildBrowseLinks()' function (defined in 'include.inc.php'):
		$BrowseLinks = buildBrowseLinks("search.php", $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, "25", "sqlSearch", "Display", $citeStyle, $citeOrder, $orderBy, $headerMsg, $viewType);
		echo $BrowseLinks;


		// 4) Start a FORM
		echo "\n<form action=\"search.php\" method=\"POST\" name=\"queryResults\">"
				. "\n<input type=\"hidden\" name=\"formType\" value=\"queryResults\">"
				. "\n<input type=\"hidden\" name=\"submit\" value=\"Display\">" // provide a default value for the 'submit' form tag (then, hitting <enter> within the 'ShowRows' text entry field will act as if the user clicked the 'Display' button)
				. "\n<input type=\"hidden\" name=\"orderBy\" value=\"" . rawurlencode($orderBy) . "\">" // embed the current ORDER BY parameter so that it can be re-applied when displaying details
				. "\n<input type=\"hidden\" name=\"showQuery\" value=\"$showQuery\">" // embed the current value of '$showQuery' so that it's available on 'display details' (batch display) & 'cite'
				. "\n<input type=\"hidden\" name=\"showLinks\" value=\"$showLinks\">" // embed the current value of '$showLinks' so that it's available on 'display details' (batch display) & 'cite'
				. "\n<input type=\"hidden\" name=\"oldQuery\" value=\"" . rawurlencode($oldQuery) . "\">"; // embed the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!


		// 5) And start a TABLE, with column headers
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" width=\"95%\" summary=\"This table holds the database results for your query\">";
		
		//    for the column headers, start a TABLE ROW ...
		echo "\n<tr>";

		// ... print a marker ('x') column (which will hold the checkboxes within the results part)
		if ($viewType != "Print") // Note: we ommit the marker column in print view! ('viewType=Print')
			echo "\n\t<th align=\"left\" valign=\"top\">&nbsp;</th>";

		// ... print a record header
		if (($showMaxRow-$rowOffset) == "1") // '$showMaxRow-$rowOffset' gives the number of displayed records for a particular page) // '($rowsFound == "1" || $showRows == "1")' wouldn't trap the case of a single record on the last of multiple results pages!
				$recordHeader = "Record"; // use singular form if there's only one record to display
		else
				$recordHeader = "Records"; // use plural form if there are multiple records to display
		echo "\n\t<th align=\"left\" valign=\"top\" colspan=\"6\">$recordHeader</th>";

		if ($showLinks == "1")
			{
				$newORDER = ("ORDER BY url DESC, doi DESC"); // Build the appropriate ORDER BY clause to facilitate sorting by Links column

				$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
				$HTMLafterLink = "</th>"; // close the table header tag
				// call the 'buildFieldNameLinks()' function (defined in 'include.inc.php'), which will return a properly formatted table header tag holding the current field's name
				// as well as the URL encoded query with the appropriate ORDER clause:
				$tableHeaderLink = buildFieldNameLinks("search.php", $query, $oldQuery, $newORDER, $result, "", $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "Display", "Links", "url", $viewType);
				echo $tableHeaderLink; // print the attribute name as link
			}

		// Finish the row
		echo "\n</tr>";
		// END RESULTS HEADER ----------------------
		
		// BEGIN RESULTS DATA COLUMNS --------------
		// Fetch one page of results (or less if on the last page)
		// (i.e., upto the limit specified in $showRows) fetch a row into the $row array and ...
		for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
		{
			// ... print out each of the attributes
			// in that row as a separate TR (Table Row)
			$recordData = ""; // make sure that buffer variable is empty

			for ($i=0; $i<$fieldsToDisplay; $i++)
				{
					// the following two lines will fetch the current attribute name:
					$info = mysql_fetch_field ($result, $i); // get the meta-data for the attribute
					$orig_fieldname = $info->name; // get the attribute name

					// for all the fields specified (-> all fields to the left):
					if (ereg("^(author|title|year|volume|corporate_author|address|keywords|abstract|publisher|language|series_editor|series_volume|issn|area|notes|location|call_number|marked|user_keys|user_notes|user_groups|created_date|modified_date)$", $orig_fieldname))
						{
							$recordData .= "\n<tr>"; // ...start a new TABLE row

							if ($viewType != "Print") // Note: we ommit the marker column in print view! ('viewType=Print')
							{
								if ($i == 0) // ... print a column with a checkbox if it's the first row of attribute data:
									$recordData .= "\n\t<td align=\"left\" valign=\"top\" width=\"10\"><input type=\"checkbox\" name=\"marked[]\" value=\"" . $row["serial"] . "\" title=\"select this record\"></td>";
								else // ... otherwise simply print an empty TD tag:
									$recordData .= "\n\t<td valign=\"top\" width=\"10\">&nbsp;</td>";
							}
						}

					// ... and print out each of the ATTRIBUTE NAMES:
					// in that row as a bold link...
					if (ereg("^(author|title|type|year|publication|abbrev_journal|volume|issue|pages|call_number|serial)$", $orig_fieldname)) // print a colored background (grey, by default)
						{
							$HTMLbeforeLink = "\n\t<td valign=\"top\" width=\"75\" class=\"mainfieldsbg\"><b>"; // start the (bold) TD tag
							$HTMLafterLink = "</b></td>"; // close the (bold) TD tag
						}
					elseif (ereg("^(marked|copy|selected|user_keys|user_notes|user_file|user_groups|bibtex_id)$", $orig_fieldname)) // print a colored background (light orange, by default) for all the user specific fields
						{
							$HTMLbeforeLink = "\n\t<td valign=\"top\" width=\"75\" class=\"userfieldsbg\"><b>"; // start the (bold) TD tag
							$HTMLafterLink = "</b></td>"; // close the (bold) TD tag
						}
					else // no colored background (by default)
						{
							$HTMLbeforeLink = "\n\t<td valign=\"top\" width=\"75\" class=\"otherfieldsbg\"><b>"; // start the (bold) TD tag
							$HTMLafterLink = "</b></td>"; // close the (bold) TD tag
						}
					// call the 'buildFieldNameLinks()' function (defined in 'include.inc.php'), which will return a properly formatted table data tag holding the current field's name
					// as well as the URL encoded query with the appropriate ORDER clause:
					$recordData .= buildFieldNameLinks("search.php", $query, $oldQuery, "", $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "Display", "", "", $viewType);

					// print the ATTRIBUTE DATA:
					// first, calculate the correct colspan value for all the fields specified:
					if (ereg("^(author|address|keywords|abstract|location|user_keys)$", $orig_fieldname))
						$ColspanFields = 5; // supply an appropriate colspan value
					elseif (ereg("^(title|corporate_author|notes|call_number|user_notes|user_groups)$", $orig_fieldname))
						$ColspanFields = 3; // supply an appropriate colspan value

					// then, start the TD tag, for all the fields specified:
					if (ereg("^(author|title|corporate_author|address|keywords|abstract|notes|location|call_number|user_keys|user_notes|user_groups)$", $orig_fieldname)) // WITH colspan attribute:
						if (ereg("^(author|title|call_number)$", $orig_fieldname)) // print a colored background (grey, by default)
							$recordData .= "\n\t<td valign=\"top\" colspan=\"$ColspanFields\" class=\"mainfieldsbg\">"; // ...with colspan attribute & appropriate value
						elseif (ereg("^(user_keys|user_notes|user_file|user_groups)$", $orig_fieldname)) // print a colored background (light orange, by default) for all the user specific fields
							$recordData .= "\n\t<td valign=\"top\" colspan=\"$ColspanFields\" class=\"userfieldsbg\">"; // ...with colspan attribute & appropriate value
						else // no colored background (by default)
							$recordData .= "\n\t<td valign=\"top\" colspan=\"$ColspanFields\" class=\"otherfieldsbg\">"; // ...with colspan attribute & appropriate value

					else // for all other fields WITHOUT colspan attribute:
						if (ereg("^(type|year|publication|abbrev_journal|volume|issue|pages|serial)$", $orig_fieldname)) // print a colored background (grey, by default)
							$recordData .= "\n\t<td valign=\"top\" class=\"mainfieldsbg\">"; // ...without colspan attribute
						elseif (ereg("^(marked|copy|selected|user_file|bibtex_id)$", $orig_fieldname)) // print a colored background (light orange, by default) for all the user specific fields
							$recordData .= "\n\t<td valign=\"top\" class=\"userfieldsbg\">"; // ...without colspan attribute
						else // no colored background (by default)
							$recordData .= "\n\t<td valign=\"top\" class=\"otherfieldsbg\">"; // ...without colspan attribute
		
					if (ereg("^(author|title|year)$", $orig_fieldname)) // print author, title & year fields in bold
						$recordData .= "<b>";

					// Note: 'htmlentities($row[$i])' for HTML encoding higher ASCII will only work correctly if character encoding of data is ISO-8859-1!
					$row[$i] = htmlentities($row[$i]); // HTML encode higher ASCII characters

					if (ereg("^abstract$", $orig_fieldname)) // for the abstract field, transform newline ('\n') characters into <br> tags
						$row[$i] = ereg_replace("\n", "<br>", $row[$i]);

					// Perform search & replace actions on the text of the 'title', 'keywords' and 'abstract' fields:
					// (the array '$markupSearchReplacePatterns' in 'ini.inc.php' defines which search & replace actions will be employed)
					if (ereg("^(title|keywords|abstract)$", $orig_fieldname)) // apply the defined search & replace actions to the 'title', 'keywords' and 'abstract' fields:
						$row[$i] = searchReplaceText($markupSearchReplacePatterns, $row[$i]); // function 'searchReplaceText()' is defined in 'include.inc.php'

					$recordData .= $row[$i]; // print the attribute data

					if (ereg("^(author|title|year)$", $orig_fieldname))
						$recordData .= "</b>";							

					$recordData .= "</td>"; // finish the TD tag

					// for all the fields specified (-> all fields to the right):
					if (ereg("^(author|type|abbrev_journal|pages|thesis|address|keywords|abstract|editor|orig_title|abbrev_series_title|edition|medium|conference|approved|location|serial|selected|user_keys|user_file|bibtex_id|created_by|modified_by)$", $orig_fieldname))
						{
							if ($showLinks == "1")
								{
									// ...embed appropriate links (if available):
									if ($i == 0) // ... print a column with links if it's the first row of attribute data:
									{
										// count the number of available link elements:
										$linkElementCounterLoggedOut = 0;

										if (!empty($row["url"]))
											$linkElementCounterLoggedOut = ($linkElementCounterLoggedOut + 1);

										if (!empty($row["doi"]))
											$linkElementCounterLoggedOut = ($linkElementCounterLoggedOut + 1);

										$linkElementCounterLoggedIn = $linkElementCounterLoggedOut;

										if (isset($_SESSION['loginEmail']) AND !empty($row["file"]))
											$linkElementCounterLoggedIn = ($linkElementCounterLoggedIn + 1);

										if (isset($_SESSION['loginEmail']) AND !empty($row["related"]))
											$linkElementCounterLoggedIn = ($linkElementCounterLoggedIn + 1);


										$recordData .= "\n\t<td valign=\"top\" width=\"50\" rowspan=\"2\">"; // note that this table cell spans the next row!

										if (isset($_SESSION['user_permissions']) AND ereg("allow_edit", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_edit'...
											// ... display a link that opens the edit form for this record:
											$recordData .= "\n\t\t<a href=\"record.php?serialNo=" . $row["serial"] . "&amp;recordAction=edit"
														. "&amp;oldQuery=" . rawurlencode($oldQuery)
														. "\"><img src=\"img/edit.gif\" alt=\"edit\" title=\"edit record\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";

										if (($linkElementCounterLoggedOut > 0) OR (isset($_SESSION['loginEmail']) AND $linkElementCounterLoggedIn > 0))
											$recordData .= "&nbsp;&nbsp;";

										// show a link to any corresponding file if one of the following conditions is met:
										// - the variable '$fileVisibility' (defined in 'ini.inc.php') is set to 'everyone'
										// - the variable '$fileVisibility' is set to 'login' AND the user is logged in
										// - the variable '$fileVisibility' is set to 'user-specific' AND the 'user_permissions' session variable contains 'allow_download'
										// - the array variable '$fileVisibilityException' (defined in 'ini.inc.php') contains a pattern (in array element 1) that matches the contents of the field given (in array element 0)
										if ($fileVisibility == "everyone" OR ($fileVisibility == "login" AND isset($_SESSION['loginEmail'])) OR ($fileVisibility == "user-specific" AND (isset($_SESSION['user_permissions']) AND ereg("allow_download", $_SESSION['user_permissions']))) OR (preg_match($fileVisibilityException[1], $row[$fileVisibilityException[0]])))
										{
											if (!empty($row["file"]))// if the 'file' field is NOT empty
											{
												if (ereg("^(http|ftp)://", $row["file"])) // if the 'file' field contains a full URL (starting with "http://" or "ftp://")
													$URLprefix = ""; // we don't alter the URL given in the 'file' field
												else // if the 'file' field contains only a partial path (like 'polarbiol/10240001.pdf') or just a file name (like '10240001.pdf')
													$URLprefix = $filesBaseURL; // use the base URL of the standard files directory as prefix ('$filesBaseURL' is defined in 'ini.inc.php')
						
												if (ereg("\.pdf$", $row["file"])) // if the 'file' field contains a link to a PDF file
													$recordData .= "\n\t\t<a href=\"" . $URLprefix . $row["file"] . "\"><img src=\"img/file_PDF.gif\" alt=\"pdf\" title=\"download PDF file\" width=\"17\" height=\"17\" hspace=\"0\" border=\"0\"></a>"; // display a PDF file icon as download link
												else
													$recordData .= "\n\t\t<a href=\"" . $URLprefix . $row["file"] . "\"><img src=\"img/file.gif\" alt=\"file\" title=\"download file\" width=\"11\" height=\"15\" hspace=\"0\" border=\"0\"></a>"; // display a generic file icon as download link						
											}
										}

										if ((($linkElementCounterLoggedOut > 1) OR (isset($_SESSION['loginEmail']) AND $linkElementCounterLoggedIn > 1)) AND !empty($row["file"]))
											$recordData .= "<br>";

										if (!empty($row["url"])) // 'htmlentities()' is used to convert any '&' into '&amp;'
											$recordData .= "\n\t\t<a href=\"" . htmlentities($row["url"]) . "\"><img src=\"img/www.gif\" alt=\"url\" title=\"goto web page\" width=\"17\" height=\"20\" hspace=\"0\" border=\"0\"></a>";
							
										if ((($linkElementCounterLoggedOut > 2) OR (isset($_SESSION['loginEmail']) AND $linkElementCounterLoggedIn > 2)) AND !empty($row["url"]))
											$recordData .= "&nbsp;";

										if (!empty($row["doi"]))
											$recordData .= "\n\t\t<a href=\"http://dx.doi.org/" . $row["doi"] . "\"><img src=\"img/doi.gif\" alt=\"doi\" title=\"goto web page (via DOI)\" width=\"17\" height=\"20\" hspace=\"0\" border=\"0\"></a>";

										if (($linkElementCounterLoggedOut > 3) OR (isset($_SESSION['loginEmail']) AND $linkElementCounterLoggedIn > 3))
											$recordData .= "<br>";

										if (isset($_SESSION['loginEmail'])) // if a user is logged in, show a link to any related records (if available):
										{
											if (!empty($row["related"]))
											{
												$relatedRecordsLink = buildRelatedRecordsLink($row["related"], $userID);

												$recordData .= "\n\t\t<a href=\"" . $relatedRecordsLink . "\"><img src=\"img/related.gif\" alt=\"related\" title=\"display related records\" width=\"19\" height=\"16\" hspace=\"0\" border=\"0\"></a>";
											}
										}

										$recordData .= "\n\t</td>";
									}
									// ... for the second row (which consists of the second and third field), we don't print any table column tag at all since the links (printed in the first row) span this second row!
									elseif ($i > 3) // ... for the third row up to the last row, simply print an empty TD tag:
										$recordData .= "\n\t<td valign=\"top\" width=\"50\">&nbsp;</td>";
								}

							$recordData .= "\n</tr>"; // ...and finish the row
						}
				}

			// Print out an URL that links directly to this record:
//			$recordData .= "\n<tr>" // start a new TR (Table Row)
//						. "\n\t<td colspan=\"$NoColumns\" align=\"center\" class=\"footer\">Link to this record:&nbsp;&nbsp;" . $databaseBaseURL . "show.php?record=" . $row["serial"] . "</td>"
//						. "\n</tr>";

			if ((($rowCounter+1) < $showRows) && (($rowCounter+1) < $rowsFound)) // append a divider line if it's not the last (or only) record on the page
				if (!(($showMaxRow == $rowsFound) && (($rowCounter+1) == ($showMaxRow-$rowOffset)))) // if we're NOT on the *last* page processing the *last* record... ('$showMaxRow-$rowOffset' gives the number of displayed records for a particular page)
					$recordData .= "\n<tr>"
						. "\n\t<td colspan=\"$NoColumns\">&nbsp;</td>"
						. "\n</tr>"
						. "\n<tr>"
						. "\n\t<td colspan=\"$NoColumns\"><hr align=\"left\" width=\"100%\"></td>"
						. "\n</tr>"
						. "\n<tr>"
						. "\n\t<td colspan=\"$NoColumns\">&nbsp;</td>"
						. "\n</tr>";
				
			echo $recordData;
		}
		// Finish the table
		echo "\n</table>";
		// END RESULTS DATA COLUMNS ----------------

		// BEGIN RESULTS FOOTER --------------------
		// Note: we ommit the results footer in print view! ('viewType=Print')
		if ($viewType != "Print")
		{
			// Again, insert the (already constructed) BROWSE LINKS
			// (i.e., a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages)
			echo $BrowseLinks;
	
			// Build a TABLE containing rows with buttons for displaying/citing selected records
			// Call the 'buildResultsFooter()' function (which does the actual work):
			$ResultsFooter = buildResultsFooter($NoColumns, $showRows, $citeStyle, $selectedRecordsArray);
			echo $ResultsFooter;
		}
		// END RESULTS FOOTER ----------------------

		// Finally, finish the form
		echo "\n</form>";
	}
	else
	{
		$nothingFoundFeedback = nothingFound($nothingChecked);
		echo $nothingFoundFeedback;
	}// end if $rowsFound body
	}

	// --------------------------------------------------------------------

	// SHOW THE RESULTS IN AN HTML <TABLE> (citation layout)
	function generateCitations($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $nothingChecked, $citeStyle, $citeOrder, $orderBy, $headerMsg, $userID, $viewType, $selectedRecordsArray)
	{
		global $markupSearchReplacePatterns; // defined in 'ini.inc.php'

	// If the query has results ...
	if ($rowsFound > 0) 
	{
		// BEGIN RESULTS HEADER --------------------
		// 1) First, initialize some variables that we'll need later on
		// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
		if ($showLinks == "1" && $citeOrder == "year") // in citation layout, we simply set it to a fixed value (either '1' or '2', depending on the values of '$showLinks' and '$citeOrder')
			$NoColumns = 2; // first column: literature citation, second column: 'display details' link
		else
			$NoColumns = 1;


		// 2) Note: we ommit the 'Search Within Results' form in citation layout! (compare with 'displayColumns()' function)


		// 3) Build a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages
		//    call the 'buildBrowseLinks()' function (defined in 'include.inc.php'):
		$BrowseLinks = buildBrowseLinks("search.php", $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, "25", "sqlSearch", "Cite", $citeStyle, $citeOrder, $orderBy, $headerMsg, $viewType);
		echo $BrowseLinks;

		// 4) Start a TABLE
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the database results for your query\">";
		// END RESULTS HEADER ----------------------
		
		// BEGIN RESULTS DATA COLUMNS --------------
		$yearsArray = array(""); // initialize array variable

		// Fetch one page of results (or less if on the last page)
		// (i.e., upto the limit specified in $showRows) fetch a row into the $row array and ...
		for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
		{
			foreach ($row as $rowFieldName => $rowFieldValue)
				if (!ereg($rowFieldName, "^(author|editor)$")) // we HTML encode higher ASCII chars for all but the author & editor fields. The author & editor fields are excluded here
					// since these fields must be passed *without* HTML entities to the 'reArrangeAuthorContents()' function (which will then handle the HTML encoding by itself)
					// (Note: 'htmlentities($row[$i])' for HTML encoding higher ASCII will only work correctly if character encoding of data is ISO-8859-1!)
					$row[$rowFieldName] = htmlentities($row[$rowFieldName]); // HTML encode higher ASCII characters within each of the fields

			// Perform search & replace actions on the text of the 'title' field:
			// (the array '$markupSearchReplacePatterns' in 'ini.inc.php' defines which search & replace actions will be employed)
			$row['title'] = searchReplaceText($markupSearchReplacePatterns, $row['title']); // function 'searchReplaceText()' is defined in 'include.inc.php'


			$citeStyleFile = getStyleFile($citeStyle); // fetch the name of the citation style file that's associated with the style given in '$citeStyle'

			// include the found citation style file *once*:
			include_once "styles/" . $citeStyleFile; // instead of 'include_once' we could also use: 'if ($rowCounter == 0) { include "styles/" . $citeStyleFile; }'

			// Order attributes according to the chosen output style & record type:
			$record = citeRecord($row, $citeStyle); // function 'citeRecord()' is defined in the citation style file given in '$citeStyleFile' (which, in turn, must reside in the 'styles' directory of the refbase root directory)


			// Print out the current record:
			if (!empty($record)) // unless the record buffer is empty...
			{
				if ($citeOrder == "year") // list records in blocks sorted by year:
					if (!in_array ($row['year'], $yearsArray)) // if this record's year hasn't occurred already
					{
						$yearsArray[] = $row['year']; // add it to the array of years
						echo "\n<tr>";
						echo "\n\t<td valign=\"top\" colspan=\"$NoColumns\"><h4>" . $row['year'] . "</h4></td>"; // print out a heading with the current year
						echo "\n</tr>";

					}

				echo "\n<tr>";
				echo "\n\t<td valign=\"top\">" . $record . "</td>"; // print out the record

				if ($showLinks == "1") // display a 'display details' link:
				{
					echo "\n\t<td valign=\"top\">";

					if (isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_details_view'...
					{
						// ... display a link that opens the 'details view' for this record:
						if (isset($_SESSION['loginEmail'])) // if a user is logged in, show user specific fields:
							echo "\n\t\t<a href=\"search.php?sqlQuery=SELECT%20author%2C%20title%2C%20type%2C%20year%2C%20publication%2C%20abbrev_journal%2C%20volume%2C%20issue%2C%20pages%2C%20corporate_author%2C%20thesis%2C%20address%2C%20keywords%2C%20abstract%2C%20publisher%2C%20place%2C%20editor%2C%20language%2C%20summary_language%2C%20orig_title%2C%20series_editor%2C%20series_title%2C%20abbrev_series_title%2C%20series_volume%2C%20series_issue%2C%20edition%2C%20issn%2C%20isbn%2C%20medium%2C%20area%2C%20expedition%2C%20conference%2C%20notes%2C%20approved%2C%20location%2C%20call_number%2C%20serial%2C%20marked%2C%20copy%2C%20selected%2C%20user_keys%2C%20user_notes%2C%20user_file%2C%20user_groups%2C%20bibtex_id%2C%20related%20"
								. "FROM%20refs%20LEFT%20JOIN%20user_data%20ON%20serial%20%3D%20record_id%20AND%20user_id%20%3D%20" . $userID . "%20";
						else // if NO user logged in, don't display any user specific fields:
							echo "\n\t\t<a href=\"search.php?sqlQuery=SELECT%20author%2C%20title%2C%20type%2C%20year%2C%20publication%2C%20abbrev_journal%2C%20volume%2C%20issue%2C%20pages%2C%20corporate_author%2C%20thesis%2C%20address%2C%20keywords%2C%20abstract%2C%20publisher%2C%20place%2C%20editor%2C%20language%2C%20summary_language%2C%20orig_title%2C%20series_editor%2C%20series_title%2C%20abbrev_series_title%2C%20series_volume%2C%20series_issue%2C%20edition%2C%20issn%2C%20isbn%2C%20medium%2C%20area%2C%20expedition%2C%20conference%2C%20notes%2C%20approved%2C%20location%2C%20call_number%2C%20serial%20"
								. "FROM%20refs%20";
		
						echo "WHERE%20serial%20RLIKE%20%22%5E%28" . $row['serial']
							. "%29%24%22%20ORDER%20BY%20" . rawurlencode($orderBy)
							. "&amp;showQuery=" . $showQuery
							. "&amp;showLinks=" . $showLinks
							. "&amp;formType=sqlSearch"
							. "&amp;viewType=" . $viewType
							. "&amp;submit=Display"
							. "&amp;oldQuery=" . rawurlencode($oldQuery)
							. "\"><img src=\"img/details.gif\" alt=\"details\" title=\"show details\" width=\"9\" height=\"17\" hspace=\"0\" border=\"0\"></a>";
					}

					echo "\n\t</td>";
				}

				echo "\n</tr>";
			}
		}
		// Finish the table
		echo "\n</table>";
		// END RESULTS DATA COLUMNS ----------------

		// BEGIN RESULTS FOOTER --------------------
		// Note: we ommit the results footer in print view! ('viewType=Print')
		if ($viewType != "Print")
		{
			// Again, insert the (already constructed) BROWSE LINKS
			// (i.e., a TABLE with links for "previous" & "next" browsing, as well as links to intermediate pages)
			echo $BrowseLinks;
		}
		// END RESULTS FOOTER ----------------------
	}
	else
	{
		$nothingFoundFeedback = nothingFound($nothingChecked);
		echo $nothingFoundFeedback;
	}// end if $rowsFound body
	}

	// --------------------------------------------------------------------

	// MODIFY USER GROUPS
	// add (remove) selected records to (from) the specified user group
	function modifyUserGroups($displayType, $recordSerialsArray, $recordSerialsString, $userID, $userGroup, $userGroupActionRadio)
	{
		global $oldQuery;
		global $connection;

		// Check whether the contents of the '$userGroup' variable shall be interpreted as regular expression:
		// Note: We assume the variable contents to be a (perl-style!) regular expression if the following conditions are true:
		//       - the user checked the radio button next to the group text entry field ('userGroupName')
		//       - the entered string starts with 'REGEXP:'
		if (($userGroupActionRadio == "0") AND (ereg("^REGEXP:", $userGroup))) // don't escape possible meta characters
		{
			$userGroup = preg_replace("/REGEXP:(.+)/", "(\\1)", $userGroup); // remove 'REGEXP:' tage & enclose the following pattern in brackets
			// The enclosing brackets ensure that a pipe '|' which is used in the grep pattern doesn't cause any harm.
			// E.g., without enclosing brackets, the pattern 'mygroup|.+' would be (among others) resolved to ' *; *mygroup|.+ *' (see below).
			// This, in turn, would cause the pattern to match beyond the group delimiter (semicolon), causing severe damage to the user's
			// other group names!

			// to assure that the regular pattern specifed by the user doesn't match beyond our group delimiter ';' (semicolon),
			// we'll need to convert any greedy regex quantifiers to non-greedy ones:
			$userGroup = preg_replace("/(?<![?+*]|[\d,]})([?+*]|\{\d+(, *\d*)?\})(?!\?)/", "\\1?", $userGroup);
		}

		// otherwise we escape any possible meta characters:
		else // if the user checked the radio button next to the group popup menu ($userGroupActionRadio == "1") -OR-
			// the radio button next to the group text entry field was selected BUT the string does NOT start with an opening bracket and end with a closing bracket...
			$userGroup = preg_quote($userGroup, "/"); // escape meta characters (including '/' that is used as delimiter for the PCRE replace functions below and which gets passed as second argument)


		// for the current user, get all entries within the 'user_data' table that refer to the selected records (listed in '$recordSerialsString'):
		$query = "SELECT record_id, user_groups FROM user_data WHERE record_id RLIKE \"^(" . $recordSerialsString . ")$\" AND user_id = " . $userID;

		$result = queryMySQLDatabase($query, $oldQuery); // RUN the query on the database through the connection (function 'queryMySQLDatabase()' is defined in 'include.inc.php')

		$foundSerialsArray = array(""); // initialize array variable (which will hold the serial numbers of all found records)

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
			{
				$recordID = $row["record_id"]; // get the serial number of the current record
				$foundSerialsArray[] = $recordID; // add this record's serial to the array of found serial numbers

				$recordUserGroups = $row["user_groups"]; // extract the user groups that the current record belongs to

				// ADD the specified user group to the 'user_groups' field:
				if ($displayType == "Add" AND !ereg("(^|.*;) *$userGroup *(;.*|$)", $recordUserGroups)) // if the specified group isn't listed already within the 'user_groups' field:
				{
					if (empty($recordUserGroups)) // and if the 'user_groups' field is completely empty
						$recordUserGroups = ereg_replace("^.*$", "$userGroup", $recordUserGroups); // add the specified user group to the 'user_groups' field
					else // if the 'user_groups' field does already contain some user content:
						$recordUserGroups = ereg_replace("^(.+)$", "\\1; $userGroup", $recordUserGroups); // append the specified user group to the 'user_groups' field
				}

				// REMOVE the specified user group from the 'user_groups' field:
				elseif ($displayType == "Remove") // remove the specified group from the 'user_groups' field:
				{
					$recordUserGroups = preg_replace("/^ *$userGroup *(?=;|$)/", "", $recordUserGroups); // the specified group is listed at the very beginning of the 'user_groups' field
					$recordUserGroups = preg_replace("/ *; *$userGroup *(?=;|$)/", "", $recordUserGroups); // the specified group occurs after some other group name within the 'user_groups' field
					$recordUserGroups = ereg_replace("^ *; *", "", $recordUserGroups); // remove any remaining group delimiters at the beginning of the 'user_groups' field
				}

				// for the current record & user ID, update the matching entry within the 'user_data' table:
				$queryUserData = "UPDATE user_data SET user_groups = \"" . $recordUserGroups . "\" WHERE record_id = " . $recordID . " AND user_id = " . $userID;
		
				$resultUserData = queryMySQLDatabase($queryUserData, $oldQuery); // RUN the query on the database through the connection (function 'queryMySQLDatabase()' is defined in 'include.inc.php')
			}
		}

		// for all selected records that have no entries in the 'user_data' table (for this user), we'll need to add a new entry containing the specified group:
		$leftoverSerialsArray = array_diff($recordSerialsArray, $foundSerialsArray); // get all unique array elements of '$recordSerialsArray' which are not in '$foundSerialsArray'

		foreach ($leftoverSerialsArray as $leftoverRecordID) // for each record that we haven't processed yet (since it doesn't have an entry in the 'user_data' table for this user)
		{
			// for the current record & user ID, add a new entry (containing the specified group) to the 'user_data' table:
			$queryUserData = "INSERT INTO user_data SET "
							. "user_groups = \"$userGroup\", "
							. "record_id = \"$leftoverRecordID\", "
							. "user_id = \"$userID\", "
							. "data_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value

			$resultUserData = queryMySQLDatabase($queryUserData, $oldQuery); // RUN the query on the database through the connection (function 'queryMySQLDatabase()' is defined in 'include.inc.php')
		}

		getUserGroups($userID); // update the 'userGroups' session variable (function 'getUserGroups()' is defined in 'include.inc.php')
	}

	// --------------------------------------------------------------------

	//	BUILD RESULTS FOOTER
	// (i.e., build a TABLE containing rows with buttons for displaying/citing selected records)
	function buildResultsFooter($NoColumns, $showRows, $citeStyle, $selectedRecordsArray)
	{
		if (isset($_SESSION['user_permissions']) AND ((isset($_SESSION['loginEmail']) AND ereg("(allow_details_view|allow_cite|allow_user_groups|allow_export|allow_batch_export)", $_SESSION['user_permissions'])) OR (!isset($_SESSION['loginEmail']) AND ereg("(allow_details_view|allow_cite)", $_SESSION['user_permissions'])))) // only build a table if the 'user_permissions' session variable does contain any of the following: 'allow_details_view', 'allow_cite' -AND- if logged in, aditionally: 'allow_user_groups', 'allow_export', 'allow_batch_export'...
		{
	
			// Note: the feature which remembers selected records across multiple results pages hasn't been implemented yet!!
			//		$selectedRecordsCount = count($selectedRecordsArray); // count the number of records that have been selected previously
	
			// Start a TABLE
			$ResultsFooterRow = "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"90%\" summary=\"This table holds the results footer which offers forms to display/cite selected records\">";
	
			$ResultsFooterRow .= "\n<tr>"
	
								. "\n\t<td align=\"left\" valign=\"top\">Selected Records";
	
			// Note: the feature which remembers selected records across multiple results pages hasn't been implemented yet!!
			//		if (isset($_SESSION['loginEmail'])) // if a user is logged in, show the number of records that have been selected already:
			//			if ($selectedRecordsCount > 0)
			//				$ResultsFooterRow .= " ($selectedRecordsCount)";
			//			else
			//				$ResultsFooterRow .= "";
	
			$ResultsFooterRow .= ":</td>";
	
			$ResultsFooterRow .= "\n\t<td align=\"left\" valign=\"top\" colspan=\"" . ($NoColumns - 1) . "\">";

			if (isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions'])) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_details_view', show form elements to display record details for all selected records:
			{
				// Display details functionality:
				$ResultsFooterRow .= "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Display\" title=\"display details for all selected records\">&nbsp;&nbsp;&nbsp;full entries&nbsp;&nbsp;&nbsp;"
									. "\n\t\t<input type=\"text\" name=\"showRows\" value=\"$showRows\" size=\"4\" title=\"specify how many records shall be displayed per page (this option also applies to the 'Cite' functionality!)\">&nbsp;&nbsp;records per page";
			}
			else
				$ResultsFooterRow .= "\n\t\t&nbsp;";			

			$ResultsFooterRow .= "\n\t</td>"
								. "\n</tr>";
	
			if (isset($_SESSION['user_permissions']) AND ereg("allow_cite", $_SESSION['user_permissions'])) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_cite', show form elements to build a reference list for the selected records:
			{
				// Cite functionality:
				$ResultsFooterRow .= "\n<tr>"
		
									. "\n\t<td align=\"left\" valign=\"top\">";
		
				// Note: the feature which remembers selected records across multiple results pages hasn't been implemented yet!!
				//		if (isset($_SESSION['loginEmail'])) // if a user is logged in, provide additional features...
				//			$ResultsFooterRow .= "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Remember\" title=\"remember all records that you've selected on this page (until logout)\">&nbsp;"
				//								. "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Forget\" title=\"forget all selected records (including those that you've selected previously)\">";
				//		else
				$ResultsFooterRow .= "\n\t\t&nbsp;";
		
				$ResultsFooterRow .= "\n\t</td>"
		
									. "\n\t<td align=\"left\" valign=\"top\" colspan=\"" . ($NoColumns - 1) . "\">";
		
				if (!isset($_SESSION['user_styles']))
					$citeStyleDisabled = " disabled"; // disable the style popup (and other form elements) if the session variable holding the user's styles isn't available
				else
					$citeStyleDisabled = "";
		
				$ResultsFooterRow .= "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Cite\" title=\"build a list of references for all selected records\"$citeStyleDisabled>&nbsp;&nbsp;&nbsp;"
									. "\n\t\tusing style:&nbsp;&nbsp;"
									. "\n\t\t<select name=\"citeStyleSelector\" title=\"choose the output style for your reference list\"$citeStyleDisabled>";
		
				if (isset($_SESSION['user_styles']))
				{
					$optionTags = buildSelectMenuOptions($_SESSION['user_styles'], " *; *", "\t\t\t"); // build properly formatted <option> tag elements from the items listed in the 'user_styles' session variable
					$ResultsFooterRow .= $optionTags;
				}
				else
					$ResultsFooterRow .= "<option>(no styles available)</option>";
		
				$ResultsFooterRow .= "\n\t\t</select>&nbsp;&nbsp;&nbsp;"
									. "\n\t\tsort by:&nbsp;&nbsp;"
									. "\n\t\t<select name=\"citeOrder\" title=\"choose the primary sort order for your reference list\"$citeStyleDisabled>"
									. "\n\t\t\t<option>author</option>"
									. "\n\t\t\t<option>year</option>"
									. "\n\t\t</select>"
									. "\n\t</td>"
		
									. "\n</tr>";
			}
	
			// if a user is logged in, provide additional features...
			if (isset($_SESSION['loginEmail']))
			{
				if (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions'])) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_groups', show form elements to add/remove the selected records to/from a user's group:
				{
					// User groups functionality:
					if (!isset($_SESSION['userGroups']))
					{
						$groupSearchDisabled = " disabled"; // disable the (part of the) 'Add to/Remove from group' form elements if the session variable holding the user's groups isnt't available
						$groupSearchPopupMenuChecked = "";
						$groupSearchTextInputChecked = " checked";
						$groupSearchSelectorTitle = "(to setup a new group with all selected records, enter a group name to the right, then click the 'Add' button)";
						$groupSearchTextInputTitle = "to setup a new group with the selected records, specify the name of the group here, then click the 'Add' button";
					}
					else
					{
						$groupSearchDisabled = "";
						$groupSearchPopupMenuChecked = " checked";
						$groupSearchTextInputChecked = "";
						$groupSearchSelectorTitle = "choose the group to which the selected records shall belong (or from which they shall be removed)";
						$groupSearchTextInputTitle = "to setup a new group with the selected records, click the radio button to the left &amp; specify the name of the group here, then click the 'Add' button";
					}
		
					$ResultsFooterRow .= "\n<tr>"
			
										. "\n\t<td align=\"left\" valign=\"top\">&nbsp;</td>"
			
										. "\n\t<td align=\"left\" valign=\"top\" colspan=\"" . ($NoColumns - 1) . "\">"
										. "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Add\" title=\"add all selected records to the specified group\">&nbsp;"
										. "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Remove\" title=\"remove all selected records from the specified group\"$groupSearchDisabled>&nbsp;&nbsp;&nbsp;group:&nbsp;&nbsp;"
										. "\n\t\t<input type=\"radio\" name=\"userGroupActionRadio\" value=\"1\" title=\"click here if you want to add (remove) the selected records to (from) an existing group; then, choose the group name from the popup menu to the right\"$groupSearchDisabled$groupSearchPopupMenuChecked>"
										. "\n\t\t<select name=\"userGroupSelector\" title=\"$groupSearchSelectorTitle\"$groupSearchDisabled>";
		
					if (isset($_SESSION['userGroups']))
					{
						$optionTags = buildSelectMenuOptions($_SESSION['userGroups'], " *; *", "\t\t\t"); // build properly formatted <option> tag elements from the items listed in the 'userGroups' session variable
						$ResultsFooterRow .= $optionTags;
					}
					else
					{
						$ResultsFooterRow .= "\n\t\t\t<option>(no groups available)</option>";
					}
		
					$ResultsFooterRow .= "\n\t\t</select>&nbsp;&nbsp;&nbsp;"
										. "\n\t\t<input type=\"radio\" name=\"userGroupActionRadio\" value=\"0\" title=\"click here if you want to setup a new group or specify a custom string describing your group(s); then, enter the group name in the text box to the right\"$groupSearchTextInputChecked>"
										. "\n\t\t<input type=\"text\" name=\"userGroupName\" value=\"\" size=\"8\" title=\"$groupSearchTextInputTitle\">"
										. "\n\t</td>"
			
										. "\n</tr>";
				}
		
				if (isset($_SESSION['user_permissions']) AND ereg("(allow_export|allow_batch_export)", $_SESSION['user_permissions'])) // if a user is logged in AND the 'user_permissions' session variable contains either 'allow_export' or 'allow_batch_export', show form elements to export the selected records:
				{
					// Export functionality:
					$ResultsFooterRow .= "\n<tr>"
			
										. "\n\t<td align=\"left\" valign=\"top\">&nbsp;</td>"
			
										. "\n\t<td align=\"left\" valign=\"top\" colspan=\"" . ($NoColumns - 1) . "\">";
		
					if (!isset($_SESSION['user_formats']))
						$exportFormatDisabled = " disabled"; // disable the format popup if the session variable holding the user's formats isn't available
					else
						$exportFormatDisabled = "";
			
					$ResultsFooterRow .= "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Export\" title=\"export selected records\"$exportFormatDisabled>&nbsp;&nbsp;&nbsp;"
										. "\n\t\tusing format:&nbsp;&nbsp;"
										. "\n\t\t<select name=\"exportFormatSelector\" title=\"choose the export format for your references\"$exportFormatDisabled>";
		
					if (isset($_SESSION['user_formats']))
					{
						$optionTags = buildSelectMenuOptions($_SESSION['user_formats'], " *; *", "\t\t\t"); // build properly formatted <option> tag elements from the items listed in the 'user_formats' session variable
						$ResultsFooterRow .= $optionTags;
					}
					else
						$ResultsFooterRow .= "<option>(no formats available)</option>";
		
					$ResultsFooterRow .= "\n\t\t</select>"
										. "\n\t</td>"
		
										. "\n</tr>";
				}
			}
			
			// Apply some search & replace in order to assign the 'selected' param to the option previously chosen by the user:
			// Note: currently, this only works when the correct 'citeStyle' name gets incorporated into an URL *manually*
			//       it doesn't work with previous & next browsing since these links actually don't submit the form (i.e., the current state of form variables won't get send)
			if (!empty($citeStyle))
				$ResultsFooterRow = ereg_replace("<option>$citeStyle", "<option selected>$citeStyle", $ResultsFooterRow);
	
			// Finish the table:
			$ResultsFooterRow .= "\n</table>";
		}
		else
			$ResultsFooterRow = ""; // return an empty string if the 'user_permissions' session variable does NOT contain any of the following: 'allow_details_view', 'allow_cite', 'allow_user_groups', 'allow_export', 'allow_batch_export'

		return $ResultsFooterRow;
	}

	// --------------------------------------------------------------------

	// EXTRACT FORM VARIABLES SENT THROUGH POST
	// (!! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php>)

	// Build the database query from user input provided by the 'simple_search.php' form:
	function extractFormElementsSimple($showLinks)
	{
		$query = "SELECT"; // (Note: we care about the wrong "SELECT, author" etc. syntax later on...)

		// ... if the user has checked the checkbox next to 'Author', we'll add that column to the SELECT query:
		if (isset($_POST['showAuthor']))
		{
			$showAuthor = $_POST['showAuthor'];
			if ($showAuthor == "1")
				$query .= ", author"; // add 'author' column
		}

		// ... if the user has checked the checkbox next to 'Title', we'll add that column to the SELECT query:
		if (isset($_POST['showTitle']))
		{
			$showTitle = $_POST['showTitle'];
			if ($showTitle == "1")
				$query .= ", title"; // add 'title' column
		}

		// ... if the user has checked the checkbox next to 'Year', we'll add that column to the SELECT query:
		if (isset($_POST['showYear']))
		{
			$showYear = $_POST['showYear'];
			if ($showYear == "1")
				$query .= ", year"; // add 'year' column
		}

		// ... if the user has checked the checkbox next to 'Publication', we'll add that column to the SELECT query:
		if (isset($_POST['showPublication']))
		{
			$showPublication = $_POST['showPublication'];
			if ($showPublication == "1")
				$query .= ", publication"; // add 'publication' column
		}

		// ... if the user has checked the checkbox next to 'Volume', we'll add that column to the SELECT query:
		if (isset($_POST['showVolume']))
		{
			$showVolume = $_POST['showVolume'];
			if ($showVolume == "1")
				$query .= ", volume"; // add 'volume' column
		}

		// ... if the user has checked the checkbox next to 'Pages', we'll add that column to the SELECT query:
		if (isset($_POST['showPages']))
		{
			$showPages = $_POST['showPages'];
			if ($showPages == "1")
				$query .= ", pages"; // add 'pages' column
		}

		// ... we still have to trap the case that the (silly!) user hasn't checked any of the column checkboxes above:
		if ($query == "SELECT")
			$query .= " author"; // force add 'author' column if the user hasn't checked any of the column checkboxes

		$query .= ", orig_record"; // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
								//  (which is required in order to present visual feedback on duplicate records)

		$query .= ", serial"; // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
							//  (which is required in order to obtain unique checkbox names)

		if ($showLinks == "1")
			$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

		// Finally, fix the wrong syntax where its says "SELECT, author, title, ..." instead of "SELECT author, title, ..."
		$query = str_replace("SELECT, ","SELECT ",$query);

		// Note: since we won't query any user specific fields (like 'marked', 'copy', 'selected', 'user_keys', 'user_notes', 'user_file', 'user_groups', 'bibtex_id' or 'related') we skip the 'LEFT JOIN...' part of the 'FROM' clause:
		$query .= " FROM refs WHERE serial RLIKE \".+\""; // add FROM & (initial) WHERE clause
		
		// ---------------------------------------

		// ... if the user has specified an author, add the value of '$authorName' as an AND clause:
		$authorName = $_POST['authorName'];
		if ($authorName != "")
			{
				$authorSelector = $_POST['authorSelector'];
				if ($authorSelector == "contains")
					$query .= " AND author RLIKE \"$authorName\"";
				elseif ($authorSelector == "does not contain")
					$query .= " AND author NOT RLIKE \"$authorName\"";
				elseif ($authorSelector == "is equal to")
					$query .= " AND author = \"$authorName\"";
				elseif ($authorSelector == "is not equal to")
					$query .= " AND author != \"$authorName\"";
				elseif ($authorSelector == "starts with")
					$query .= " AND author RLIKE \"^$authorName\"";
				elseif ($authorSelector == "ends with")
					$query .= " AND author RLIKE \"$authorName$\"";
			}

		// ... if the user has specified a title, add the value of '$titleName' as an AND clause:
		$titleName = $_POST['titleName'];
		if ($titleName != "")
			{
				$titleSelector = $_POST['titleSelector'];
				if ($titleSelector == "contains")
					$query .= " AND title RLIKE \"$titleName\"";
				elseif ($titleSelector == "does not contain")
					$query .= " AND title NOT RLIKE \"$titleName\"";
				elseif ($titleSelector == "is equal to")
					$query .= " AND title = \"$titleName\"";
				elseif ($titleSelector == "is not equal to")
					$query .= " AND title != \"$titleName\"";
				elseif ($titleSelector == "starts with")
					$query .= " AND title RLIKE \"^$titleName\"";
				elseif ($titleSelector == "ends with")
					$query .= " AND title RLIKE \"$titleName$\"";
			}
	
		// ... if the user has specified a year, add the value of '$yearNo' as an AND clause:
		$yearNo = $_POST['yearNo'];
		if ($yearNo != "")
			{
				$yearSelector = $_POST['yearSelector'];
				if ($yearSelector == "contains")
					$query .= " AND year RLIKE \"$yearNo\"";
				elseif ($yearSelector == "does not contain")
					$query .= " AND year NOT RLIKE \"$yearNo\"";
				elseif ($yearSelector == "is equal to")
					$query .= " AND year = \"$yearNo\"";
				elseif ($yearSelector == "is not equal to")
					$query .= " AND year != \"$yearNo\"";
				elseif ($yearSelector == "starts with")
					$query .= " AND year RLIKE \"^$yearNo\"";
				elseif ($yearSelector == "ends with")
					$query .= " AND year RLIKE \"$yearNo$\"";
				elseif ($yearSelector == "is greater than")
					$query .= " AND year > \"$yearNo\"";
				elseif ($yearSelector == "is less than")
					$query .= " AND year < \"$yearNo\"";
			}
	
		// ... if the user has specified a publication, add the value of '$publicationName' as an AND clause:
		$publicationRadio = $_POST['publicationRadio'];
		if ($publicationRadio == "1")
		{
			$publicationName = $_POST['publicationName'];
			if ($publicationName != "All" && $publicationName != "")
				{
					$publicationSelector = $_POST['publicationSelector'];
					if ($publicationSelector == "contains")
						$query .= " AND publication RLIKE \"$publicationName\"";
					elseif ($publicationSelector == "does not contain")
						$query .= " AND publication NOT RLIKE \"$publicationName\"";
					elseif ($publicationSelector == "is equal to")
						$query .= " AND publication = \"$publicationName\"";
					elseif ($publicationSelector == "is not equal to")
						$query .= " AND publication != \"$publicationName\"";
					elseif ($publicationSelector == "starts with")
						$query .= " AND publication RLIKE \"^$publicationName\"";
					elseif ($publicationSelector == "ends with")
						$query .= " AND publication RLIKE \"$publicationName$\"";
				}
		}
		elseif ($publicationRadio == "0")
		{
			$publicationName2 = $_POST['publicationName2'];
			if ($publicationName2 != "")
				{
					$publicationSelector2 = $_POST['publicationSelector2'];
					if ($publicationSelector2 == "contains")
						$query .= " AND publication RLIKE \"$publicationName2\"";
					elseif ($publicationSelector2 == "does not contain")
						$query .= " AND publication NOT RLIKE \"$publicationName2\"";
					elseif ($publicationSelector2 == "is equal to")
						$query .= " AND publication = \"$publicationName2\"";
					elseif ($publicationSelector2 == "is not equal to")
						$query .= " AND publication != \"$publicationName2\"";
					elseif ($publicationSelector2 == "starts with")
						$query .= " AND publication RLIKE \"^$publicationName2\"";
					elseif ($publicationSelector2 == "ends with")
						$query .= " AND publication RLIKE \"$publicationName2$\"";
				}
		}
	
		// ... if the user has specified a volume, add the value of '$volumeNo' as an AND clause:
		$volumeNo = $_POST['volumeNo'];
		if ($volumeNo != "")
			{
				$volumeSelector = $_POST['volumeSelector'];
				if ($volumeSelector == "contains")
					$query .= " AND volume RLIKE \"$volumeNo\"";
				elseif ($volumeSelector == "does not contain")
					$query .= " AND volume NOT RLIKE \"$volumeNo\"";
				elseif ($volumeSelector == "is equal to")
					$query .= " AND volume = \"$volumeNo\"";
				elseif ($volumeSelector == "is not equal to")
					$query .= " AND volume != \"$volumeNo\"";
				elseif ($volumeSelector == "starts with")
					$query .= " AND volume RLIKE \"^$volumeNo\"";
				elseif ($volumeSelector == "ends with")
					$query .= " AND volume RLIKE \"$volumeNo$\"";
				elseif ($volumeSelector == "is greater than")
					$query .= " AND volume > \"$volumeNo\"";
				elseif ($volumeSelector == "is less than")
					$query .= " AND volume < \"$volumeNo\"";
			}
	
		// ... if the user has specified some pages, add the value of '$pagesNo' as an AND clause:
		$pagesNo = $_POST['pagesNo'];
		if ($pagesNo != "")
			{
				$pagesSelector = $_POST['pagesSelector'];
				if ($pagesSelector == "contains")
					$query .= " AND pages RLIKE \"$pagesNo\"";
				elseif ($pagesSelector == "does not contain")
					$query .= " AND pages NOT RLIKE \"$pagesNo\"";
				elseif ($pagesSelector == "is equal to")
					$query .= " AND pages = \"$pagesNo\"";
				elseif ($pagesSelector == "is not equal to")
					$query .= " AND pages != \"$pagesNo\"";
				elseif ($pagesSelector == "starts with")
					$query .= " AND pages RLIKE \"^$pagesNo\"";
				elseif ($pagesSelector == "ends with")
					$query .= " AND pages RLIKE \"$pagesNo$\"";
			}


		// Construct the ORDER BY clause:
		// A) extract first level sort option:
		$sortSelector1 = $_POST['sortSelector1'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector1 = str_replace("pages", "first_page", $sortSelector1);

		$sortRadio1 = $_POST['sortRadio1'];
		if ($sortRadio1 == "0") // sort ascending
			$query .= " ORDER BY $sortSelector1";
		else // sort descending
			$query .= " ORDER BY $sortSelector1 DESC";

		// B) extract second level sort option:
		$sortSelector2 = $_POST['sortSelector2'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector2 = str_replace("pages", "first_page", $sortSelector2);

		$sortRadio2 = $_POST['sortRadio2'];
		if ($sortRadio2 == "0") // sort ascending
			$query .= ", $sortSelector2";
		else // sort descending
			$query .= ", $sortSelector2 DESC";

		// C) extract third level sort option:
		$sortSelector3 = $_POST['sortSelector3'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector3 = str_replace("pages", "first_page", $sortSelector3);

		$sortRadio3 = $_POST['sortRadio3'];
		if ($sortRadio3 == "0") // sort ascending
			$query .= ", $sortSelector3";
		else // sort descending
			$query .= ", $sortSelector3 DESC";


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the 'library_search.php' form:
	function extractFormElementsLibrary($showLinks)
	{
		$query = "SELECT"; // (Note: we care about the wrong "SELECT, author" etc. syntax later on...)

		// ... if the user has checked the checkbox next to 'Author', we'll add that column to the SELECT query:
		if (isset($_POST['showAuthor']))
		{
			$showAuthor = $_POST['showAuthor'];
			if ($showAuthor == "1")
				$query .= ", author"; // add 'author' column
		}

		// ... if the user has checked the checkbox next to 'Title', we'll add that column to the SELECT query:
		if (isset($_POST['showTitle']))
		{
			$showTitle = $_POST['showTitle'];
			if ($showTitle == "1")
				$query .= ", title"; // add 'title' column
		}

		// ... if the user has checked the checkbox next to 'Year', we'll add that column to the SELECT query:
		if (isset($_POST['showYear']))
		{
			$showYear = $_POST['showYear'];
			if ($showYear == "1")
				$query .= ", year"; // add 'year' column
		}

		// ... if the user has checked the checkbox next to 'Editor', we'll add that column to the SELECT query:
		if (isset($_POST['showEditor']))
		{
			$showEditor = $_POST['showEditor'];
			if ($showEditor == "1")
				$query .= ", editor"; // add 'editor' column
		}

		// ... if the user has checked the checkbox next to 'Series', we'll add that column to the SELECT query:
		if (isset($_POST['showSeriesTitle']))
		{
			$showSeriesTitle = $_POST['showSeriesTitle'];
			if ($showSeriesTitle == "1")
				$query .= ", series_title"; // add 'series_title' column
		}

		// ... if the user has checked the checkbox next to 'Volume', we'll add that column to the SELECT query:
		if (isset($_POST['showVolume']))
		{
			$showVolume = $_POST['showVolume'];
			if ($showVolume == "1")
				$query .= ", series_volume"; // add 'series_volume' column
		}

		// ... if the user has checked the checkbox next to 'Pages', we'll add that column to the SELECT query:
		if (isset($_POST['showPages']))
		{
			$showPages = $_POST['showPages'];
			if ($showPages == "1")
				$query .= ", pages"; // add 'pages' column
		}

		// ... if the user has checked the checkbox next to 'Publisher', we'll add that column to the SELECT query:
		if (isset($_POST['showPublisher']))
		{
			$showPublisher = $_POST['showPublisher'];
			if ($showPublisher == "1")
				$query .= ", publisher"; // add 'publisher' column
		}

		// ... if the user has checked the checkbox next to 'Place', we'll add that column to the SELECT query:
		if (isset($_POST['showPlace']))
		{
			$showPlace = $_POST['showPlace'];
			if ($showPlace == "1")
				$query .= ", place"; // add 'place' column
		}

		// ... if the user has checked the checkbox next to 'Signature', we'll add that column to the SELECT query:
		if (isset($_POST['showCallNumber']))
		{
			$showCallNumber = $_POST['showCallNumber'];
			if ($showCallNumber == "1")
				$query .= ", call_number"; // add 'call_number' column
		}

		// ... if the user has checked the checkbox next to 'Keywords', we'll add that column to the SELECT query:
		if (isset($_POST['showKeywords']))
		{
			$showKeywords = $_POST['showKeywords'];
			if ($showKeywords == "1")
				$query .= ", keywords"; // add 'keywords' column
		}

		// ... if the user has checked the checkbox next to 'Notes', we'll add that column to the SELECT query:
		if (isset($_POST['showNotes']))
		{
			$showNotes = $_POST['showNotes'];
			if ($showNotes == "1")
				$query .= ", notes"; // add 'notes' column
		}

		// ... we still have to trap the case that the (silly!) user hasn't checked any of the column checkboxes above:
		if ($query == "SELECT")
			$query .= " author"; // force add 'author' column if the user hasn't checked any of the column checkboxes

		$query .= ", orig_record"; // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
								//  (which is required in order to present visual feedback on duplicate records)

		$query .= ", serial"; // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
							//  (which is required in order to obtain unique checkbox names)

		if ($showLinks == "1")
			$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

		// Finally, fix the wrong syntax where its says "SELECT, author, title, ..." instead of "SELECT author, title, ..."
		$query = str_replace("SELECT, ","SELECT ",$query);

		// Note: since we won't query any user specific fields (like 'marked', 'copy', 'selected', 'user_keys', 'user_notes', 'user_file', 'user_groups', 'bibtex_id' or 'related') we skip the 'LEFT JOIN...' part of the 'FROM' clause:
		$query .= " FROM refs WHERE serial RLIKE \".+\" AND location RLIKE \"IP� Library\""; // add FROM & (initial) WHERE clause
		
		// ---------------------------------------

		// ... if the user has specified an author, add the value of '$authorName' as an AND clause:
		$authorName = $_POST['authorName'];
		if ($authorName != "")
			{
				$authorSelector = $_POST['authorSelector'];
				if ($authorSelector == "contains")
					$query .= " AND author RLIKE \"$authorName\"";
				elseif ($authorSelector == "does not contain")
					$query .= " AND author NOT RLIKE \"$authorName\"";
				elseif ($authorSelector == "is equal to")
					$query .= " AND author = \"$authorName\"";
				elseif ($authorSelector == "is not equal to")
					$query .= " AND author != \"$authorName\"";
				elseif ($authorSelector == "starts with")
					$query .= " AND author RLIKE \"^$authorName\"";
				elseif ($authorSelector == "ends with")
					$query .= " AND author RLIKE \"$authorName$\"";
			}

		// ... if the user has specified a title, add the value of '$titleName' as an AND clause:
		$titleName = $_POST['titleName'];
		if ($titleName != "")
			{
				$titleSelector = $_POST['titleSelector'];
				if ($titleSelector == "contains")
					$query .= " AND title RLIKE \"$titleName\"";
				elseif ($titleSelector == "does not contain")
					$query .= " AND title NOT RLIKE \"$titleName\"";
				elseif ($titleSelector == "is equal to")
					$query .= " AND title = \"$titleName\"";
				elseif ($titleSelector == "is not equal to")
					$query .= " AND title != \"$titleName\"";
				elseif ($titleSelector == "starts with")
					$query .= " AND title RLIKE \"^$titleName\"";
				elseif ($titleSelector == "ends with")
					$query .= " AND title RLIKE \"$titleName$\"";
			}
	
		// ... if the user has specified a year, add the value of '$yearNo' as an AND clause:
		$yearNo = $_POST['yearNo'];
		if ($yearNo != "")
			{
				$yearSelector = $_POST['yearSelector'];
				if ($yearSelector == "contains")
					$query .= " AND year RLIKE \"$yearNo\"";
				elseif ($yearSelector == "does not contain")
					$query .= " AND year NOT RLIKE \"$yearNo\"";
				elseif ($yearSelector == "is equal to")
					$query .= " AND year = \"$yearNo\"";
				elseif ($yearSelector == "is not equal to")
					$query .= " AND year != \"$yearNo\"";
				elseif ($yearSelector == "starts with")
					$query .= " AND year RLIKE \"^$yearNo\"";
				elseif ($yearSelector == "ends with")
					$query .= " AND year RLIKE \"$yearNo$\"";
				elseif ($yearSelector == "is greater than")
					$query .= " AND year > \"$yearNo\"";
				elseif ($yearSelector == "is less than")
					$query .= " AND year < \"$yearNo\"";
			}
	
		// ... if the user has specified an editor, add the value of '$editorName' as an AND clause:
		$editorName = $_POST['editorName'];
		if ($editorName != "")
			{
				$editorSelector = $_POST['editorSelector'];
				if ($editorSelector == "contains")
					$query .= " AND editor RLIKE \"$editorName\"";
				elseif ($editorSelector == "does not contain")
					$query .= " AND editor NOT RLIKE \"$editorName\"";
				elseif ($editorSelector == "is equal to")
					$query .= " AND editor = \"$editorName\"";
				elseif ($editorSelector == "is not equal to")
					$query .= " AND editor != \"$editorName\"";
				elseif ($editorSelector == "starts with")
					$query .= " AND editor RLIKE \"^$editorName\"";
				elseif ($editorSelector == "ends with")
					$query .= " AND editor RLIKE \"$editorName$\"";
			}

		// ... if the user has specified a series title, add the value of '$seriesTitleName' as an AND clause:
		$seriesTitleRadio = $_POST['seriesTitleRadio'];
		if ($seriesTitleRadio == "1")
		{
			$seriesTitleName = $_POST['seriesTitleName'];
			if ($seriesTitleName != "All" && $seriesTitleName != "")
				{
					$seriesTitleSelector = $_POST['seriesTitleSelector'];
					if ($seriesTitleSelector == "contains")
						$query .= " AND series_title RLIKE \"$seriesTitleName\"";
					elseif ($seriesTitleSelector == "does not contain")
						$query .= " AND series_title NOT RLIKE \"$seriesTitleName\"";
					elseif ($seriesTitleSelector == "is equal to")
						$query .= " AND series_title = \"$seriesTitleName\"";
					elseif ($seriesTitleSelector == "is not equal to")
						$query .= " AND series_title != \"$seriesTitleName\"";
					elseif ($seriesTitleSelector == "starts with")
						$query .= " AND series_title RLIKE \"^$seriesTitleName\"";
					elseif ($seriesTitleSelector == "ends with")
						$query .= " AND series_title RLIKE \"$seriesTitleName$\"";
				}
		}
		elseif ($seriesTitleRadio == "0")
		{
			$seriesTitleName2 = $_POST['seriesTitleName2'];
			if ($seriesTitleName2 != "")
				{
					$seriesTitleSelector2 = $_POST['seriesTitleSelector2'];
					if ($seriesTitleSelector2 == "contains")
						$query .= " AND series_title RLIKE \"$seriesTitleName2\"";
					elseif ($seriesTitleSelector2 == "does not contain")
						$query .= " AND series_title NOT RLIKE \"$seriesTitleName2\"";
					elseif ($seriesTitleSelector2 == "is equal to")
						$query .= " AND series_title = \"$seriesTitleName2\"";
					elseif ($seriesTitleSelector2 == "is not equal to")
						$query .= " AND series_title != \"$seriesTitleName2\"";
					elseif ($seriesTitleSelector2 == "starts with")
						$query .= " AND series_title RLIKE \"^$seriesTitleName2\"";
					elseif ($seriesTitleSelector2 == "ends with")
						$query .= " AND series_title RLIKE \"$seriesTitleName2$\"";
				}
		}
	
		// ... if the user has specified a series volume, add the value of '$volumeNo' as an AND clause:
		$volumeNo = $_POST['volumeNo'];
		if ($volumeNo != "")
			{
				$volumeSelector = $_POST['volumeSelector'];
				if ($volumeSelector == "contains")
					$query .= " AND series_volume RLIKE \"$volumeNo\"";
				elseif ($volumeSelector == "does not contain")
					$query .= " AND series_volume NOT RLIKE \"$volumeNo\"";
				elseif ($volumeSelector == "is equal to")
					$query .= " AND series_volume = \"$volumeNo\"";
				elseif ($volumeSelector == "is not equal to")
					$query .= " AND series_volume != \"$volumeNo\"";
				elseif ($volumeSelector == "starts with")
					$query .= " AND series_volume RLIKE \"^$volumeNo\"";
				elseif ($volumeSelector == "ends with")
					$query .= " AND series_volume RLIKE \"$volumeNo$\"";
				elseif ($volumeSelector == "is greater than")
					$query .= " AND series_volume > \"$volumeNo\"";
				elseif ($volumeSelector == "is less than")
					$query .= " AND series_volume < \"$volumeNo\"";
			}
	
		// ... if the user has specified some pages, add the value of '$pagesNo' as an AND clause:
		$pagesNo = $_POST['pagesNo'];
		if ($pagesNo != "")
			{
				$pagesSelector = $_POST['pagesSelector'];
				if ($pagesSelector == "contains")
					$query .= " AND pages RLIKE \"$pagesNo\"";
				elseif ($pagesSelector == "does not contain")
					$query .= " AND pages NOT RLIKE \"$pagesNo\"";
				elseif ($pagesSelector == "is equal to")
					$query .= " AND pages = \"$pagesNo\"";
				elseif ($pagesSelector == "is not equal to")
					$query .= " AND pages != \"$pagesNo\"";
				elseif ($pagesSelector == "starts with")
					$query .= " AND pages RLIKE \"^$pagesNo\"";
				elseif ($pagesSelector == "ends with")
					$query .= " AND pages RLIKE \"$pagesNo$\"";
			}
	
		// ... if the user has specified a publisher, add the value of '$publisherName' as an AND clause:
		$publisherName = $_POST['publisherName'];
		if ($publisherName != "")
			{
				$publisherSelector = $_POST['publisherSelector'];
				if ($publisherSelector == "contains")
					$query .= " AND publisher RLIKE \"$publisherName\"";
				elseif ($publisherSelector == "does not contain")
					$query .= " AND publisher NOT RLIKE \"$publisherName\"";
				elseif ($publisherSelector == "is equal to")
					$query .= " AND publisher = \"$publisherName\"";
				elseif ($publisherSelector == "is not equal to")
					$query .= " AND publisher != \"$publisherName\"";
				elseif ($publisherSelector == "starts with")
					$query .= " AND publisher RLIKE \"^$publisherName\"";
				elseif ($publisherSelector == "ends with")
					$query .= " AND publisher RLIKE \"$publisherName$\"";
			}

		// ... if the user has specified a place, add the value of '$placeName' as an AND clause:
		$placeName = $_POST['placeName'];
		if ($placeName != "")
			{
				$placeSelector = $_POST['placeSelector'];
				if ($placeSelector == "contains")
					$query .= " AND place RLIKE \"$placeName\"";
				elseif ($placeSelector == "does not contain")
					$query .= " AND place NOT RLIKE \"$placeName\"";
				elseif ($placeSelector == "is equal to")
					$query .= " AND place = \"$placeName\"";
				elseif ($placeSelector == "is not equal to")
					$query .= " AND place != \"$placeName\"";
				elseif ($placeSelector == "starts with")
					$query .= " AND place RLIKE \"^$placeName\"";
				elseif ($placeSelector == "ends with")
					$query .= " AND place RLIKE \"$placeName$\"";
			}

		// ... if the user has specified a call number, add the value of '$callNumberName' as an AND clause:
		$callNumberName = $_POST['callNumberName'];
		if ($callNumberName != "")
			{
				$callNumberSelector = $_POST['callNumberSelector'];
				if ($callNumberSelector == "contains")
					$query .= " AND call_number RLIKE \"$callNumberName\"";
				elseif ($callNumberSelector == "does not contain")
					$query .= " AND call_number NOT RLIKE \"$callNumberName\"";
				elseif ($callNumberSelector == "is equal to")
					$query .= " AND call_number = \"$callNumberName\"";
				elseif ($callNumberSelector == "is not equal to")
					$query .= " AND call_number != \"$callNumberName\"";
				elseif ($callNumberSelector == "starts with")
					$query .= " AND call_number RLIKE \"^$callNumberName\"";
				elseif ($callNumberSelector == "ends with")
					$query .= " AND call_number RLIKE \"$callNumberName$\"";
			}

		// ... if the user has specified some keywords, add the value of '$keywordsName' as an AND clause:
		$keywordsName = $_POST['keywordsName'];
		if ($keywordsName != "")
			{
				$keywordsSelector = $_POST['keywordsSelector'];
				if ($keywordsSelector == "contains")
					$query .= " AND keywords RLIKE \"$keywordsName\"";
				elseif ($keywordsSelector == "does not contain")
					$query .= " AND keywords NOT RLIKE \"$keywordsName\"";
				elseif ($keywordsSelector == "is equal to")
					$query .= " AND keywords = \"$keywordsName\"";
				elseif ($keywordsSelector == "is not equal to")
					$query .= " AND keywords != \"$keywordsName\"";
				elseif ($keywordsSelector == "starts with")
					$query .= " AND keywords RLIKE \"^$keywordsName\"";
				elseif ($keywordsSelector == "ends with")
					$query .= " AND keywords RLIKE \"$keywordsName$\"";
			}

		// ... if the user has specified some notes, add the value of '$notesName' as an AND clause:
		$notesName = $_POST['notesName'];
		if ($notesName != "")
			{
				$notesSelector = $_POST['notesSelector'];
				if ($notesSelector == "contains")
					$query .= " AND notes RLIKE \"$notesName\"";
				elseif ($notesSelector == "does not contain")
					$query .= " AND notes NOT RLIKE \"$notesName\"";
				elseif ($notesSelector == "is equal to")
					$query .= " AND notes = \"$notesName\"";
				elseif ($notesSelector == "is not equal to")
					$query .= " AND notes != \"$notesName\"";
				elseif ($notesSelector == "starts with")
					$query .= " AND notes RLIKE \"^$notesName\"";
				elseif ($notesSelector == "ends with")
					$query .= " AND notes RLIKE \"$notesName$\"";
			}


		// Construct the ORDER BY clause:
		// A) extract first level sort option:
		$sortSelector1 = $_POST['sortSelector1'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector1 = str_replace("pages", "first_page", $sortSelector1);

		$sortRadio1 = $_POST['sortRadio1'];
		if ($sortRadio1 == "0") // sort ascending
			$query .= " ORDER BY $sortSelector1";
		else // sort descending
			$query .= " ORDER BY $sortSelector1 DESC";

		// B) extract second level sort option:
		$sortSelector2 = $_POST['sortSelector2'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector2 = str_replace("pages", "first_page", $sortSelector2);

		$sortRadio2 = $_POST['sortRadio2'];
		if ($sortRadio2 == "0") // sort ascending
			$query .= ", $sortSelector2";
		else // sort descending
			$query .= ", $sortSelector2 DESC";

		// C) extract third level sort option:
		$sortSelector3 = $_POST['sortSelector3'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector3 = str_replace("pages", "first_page", $sortSelector3);

		$sortRadio3 = $_POST['sortRadio3'];
		if ($sortRadio3 == "0") // sort ascending
			$query .= ", $sortSelector3";
		else // sort descending
			$query .= ", $sortSelector3 DESC";


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the 'advanced_search.php' form:
	function extractFormElementsAdvanced($showLinks, $userID)
	{
		$query = "SELECT"; // (Note: we care about the wrong "SELECT, author" etc. syntax later on...)

		// ... if the user has checked the checkbox next to 'Author', we'll add that column to the SELECT query:
		if (isset($_POST['showAuthor']))
		{
			$showAuthor = $_POST['showAuthor'];
			if ($showAuthor == "1")
				$query .= ", author"; // add 'author' column
		}

		// ... if the user has checked the checkbox next to 'Address', we'll add that column to the SELECT query:
		if (isset($_POST['showAddress']))
		{
			$showAddress = $_POST['showAddress'];
			if ($showAddress == "1")
				$query .= ", address"; // add 'address' column
		}

		// ... if the user has checked the checkbox next to 'Corporate Author', we'll add that column to the SELECT query:
		if (isset($_POST['showCorporateAuthor']))
		{
			$showCorporateAuthor = $_POST['showCorporateAuthor'];
			if ($showCorporateAuthor == "1")
				$query .= ", corporate_author"; // add 'corporate_author' column
		}

		// ... if the user has checked the checkbox next to 'Thesis', we'll add that column to the SELECT query:
		if (isset($_POST['showThesis']))
		{
			$showThesis = $_POST['showThesis'];
			if ($showThesis == "1")
				$query .= ", thesis"; // add 'thesis' column
		}

		// ... if the user has checked the checkbox next to 'Title', we'll add that column to the SELECT query:
		if (isset($_POST['showTitle']))
		{
			$showTitle = $_POST['showTitle'];
			if ($showTitle == "1")
				$query .= ", title"; // add 'title' column
		}

		// ... if the user has checked the checkbox next to 'Original Title', we'll add that column to the SELECT query:
		if (isset($_POST['showOrigTitle']))
		{
			$showOrigTitle = $_POST['showOrigTitle'];
			if ($showOrigTitle == "1")
				$query .= ", orig_title"; // add 'orig_title' column
		}

		// ... if the user has checked the checkbox next to 'Year', we'll add that column to the SELECT query:
		if (isset($_POST['showYear']))
		{
			$showYear = $_POST['showYear'];
			if ($showYear == "1")
				$query .= ", year"; // add 'year' column
		}

		// ... if the user has checked the checkbox next to 'Publication', we'll add that column to the SELECT query:
		if (isset($_POST['showPublication']))
		{
			$showPublication = $_POST['showPublication'];
			if ($showPublication == "1")
				$query .= ", publication"; // add 'publication' column
		}

		// ... if the user has checked the checkbox next to 'Abbreviated Journal', we'll add that column to the SELECT query:
		if (isset($_POST['showAbbrevJournal']))
		{
			$showAbbrevJournal = $_POST['showAbbrevJournal'];
			if ($showAbbrevJournal == "1")
				$query .= ", abbrev_journal"; // add 'abbrev_journal' column
		}

		// ... if the user has checked the checkbox next to 'Editor', we'll add that column to the SELECT query:
		if (isset($_POST['showEditor']))
		{
			$showEditor = $_POST['showEditor'];
			if ($showEditor == "1")
				$query .= ", editor"; // add 'editor' column
		}

		// ... if the user has checked the checkbox next to 'Volume', we'll add that column to the SELECT query:
		if (isset($_POST['showVolume']))
		{
			$showVolume = $_POST['showVolume'];
			if ($showVolume == "1")
				$query .= ", volume"; // add 'volume' column
		}

		// ... if the user has checked the checkbox next to 'Issue', we'll add that column to the SELECT query:
		if (isset($_POST['showIssue']))
		{
			$showIssue = $_POST['showIssue'];
			if ($showIssue == "1")
				$query .= ", issue"; // add 'issue' column
		}

		// ... if the user has checked the checkbox next to 'Pages', we'll add that column to the SELECT query:
		if (isset($_POST['showPages']))
		{
			$showPages = $_POST['showPages'];
			if ($showPages == "1")
				$query .= ", pages"; // add 'pages' column
		}

		// ... if the user has checked the checkbox next to 'Series', we'll add that column to the SELECT query:
		if (isset($_POST['showSeriesTitle']))
		{
			$showSeriesTitle = $_POST['showSeriesTitle'];
			if ($showSeriesTitle == "1")
				$query .= ", series_title"; // add 'series_title' column
		}

		// ... if the user has checked the checkbox next to 'Abbreviated Series Title', we'll add that column to the SELECT query:
		if (isset($_POST['showAbbrevSeriesTitle']))
		{
			$showAbbrevSeriesTitle = $_POST['showAbbrevSeriesTitle'];
			if ($showAbbrevSeriesTitle == "1")
				$query .= ", abbrev_series_title"; // add 'abbrev_series_title' column
		}

		// ... if the user has checked the checkbox next to 'Series Editor', we'll add that column to the SELECT query:
		if (isset($_POST['showSeriesEditor']))
		{
			$showSeriesEditor = $_POST['showSeriesEditor'];
			if ($showSeriesEditor == "1")
				$query .= ", series_editor"; // add 'series_editor' column
		}

		// ... if the user has checked the checkbox next to 'Series Volume', we'll add that column to the SELECT query:
		if (isset($_POST['showSeriesVolume']))
		{
			$showSeriesVolume = $_POST['showSeriesVolume'];
			if ($showSeriesVolume == "1")
				$query .= ", series_volume"; // add 'series_volume' column
		}

		// ... if the user has checked the checkbox next to 'Series Issue', we'll add that column to the SELECT query:
		if (isset($_POST['showSeriesIssue']))
		{
			$showSeriesIssue = $_POST['showSeriesIssue'];
			if ($showSeriesIssue == "1")
				$query .= ", series_issue"; // add 'series_issue' column
		}

		// ... if the user has checked the checkbox next to 'Publisher', we'll add that column to the SELECT query:
		if (isset($_POST['showPublisher']))
		{
			$showPublisher = $_POST['showPublisher'];
			if ($showPublisher == "1")
				$query .= ", publisher"; // add 'publisher' column
		}

		// ... if the user has checked the checkbox next to 'Place of Publication', we'll add that column to the SELECT query:
		if (isset($_POST['showPlace']))
		{
			$showPlace = $_POST['showPlace'];
			if ($showPlace == "1")
				$query .= ", place"; // add 'place' column
		}

		// ... if the user has checked the checkbox next to 'Edition', we'll add that column to the SELECT query:
		if (isset($_POST['showEdition']))
		{
			$showEdition = $_POST['showEdition'];
			if ($showEdition == "1")
				$query .= ", edition"; // add 'edition' column
		}

		// ... if the user has checked the checkbox next to 'Medium', we'll add that column to the SELECT query:
		if (isset($_POST['showMedium']))
		{
			$showMedium = $_POST['showMedium'];
			if ($showMedium == "1")
				$query .= ", medium"; // add 'medium' column
		}

		// ... if the user has checked the checkbox next to 'ISSN', we'll add that column to the SELECT query:
		if (isset($_POST['showISSN']))
		{
			$showISSN = $_POST['showISSN'];
			if ($showISSN == "1")
				$query .= ", issn"; // add 'issn' column
		}

		// ... if the user has checked the checkbox next to 'ISBN', we'll add that column to the SELECT query:
		if (isset($_POST['showISBN']))
		{
			$showISBN = $_POST['showISBN'];
			if ($showISBN == "1")
				$query .= ", isbn"; // add 'isbn' column
		}

		// ... if the user has checked the checkbox next to 'Language', we'll add that column to the SELECT query:
		if (isset($_POST['showLanguage']))
		{
			$showLanguage = $_POST['showLanguage'];
			if ($showLanguage == "1")
				$query .= ", language"; // add 'language' column
		}

		// ... if the user has checked the checkbox next to 'Summary Language', we'll add that column to the SELECT query:
		if (isset($_POST['showSummaryLanguage']))
		{
			$showSummaryLanguage = $_POST['showSummaryLanguage'];
			if ($showSummaryLanguage == "1")
				$query .= ", summary_language"; // add 'summary_language' column
		}

		// ... if the user has checked the checkbox next to 'Keywords', we'll add that column to the SELECT query:
		if (isset($_POST['showKeywords']))
		{
			$showKeywords = $_POST['showKeywords'];
			if ($showKeywords == "1")
				$query .= ", keywords"; // add 'keywords' column
		}

		// ... if the user has checked the checkbox next to 'Abstract', we'll add that column to the SELECT query:
		if (isset($_POST['showAbstract']))
		{
			$showAbstract = $_POST['showAbstract'];
			if ($showAbstract == "1")
				$query .= ", abstract"; // add 'abstract' column
		}

		// ... if the user has checked the checkbox next to 'Area', we'll add that column to the SELECT query:
		if (isset($_POST['showArea']))
		{
			$showArea = $_POST['showArea'];
			if ($showArea == "1")
				$query .= ", area"; // add 'area' column
		}

		// ... if the user has checked the checkbox next to 'Expedition', we'll add that column to the SELECT query:
		if (isset($_POST['showExpedition']))
		{
			$showExpedition = $_POST['showExpedition'];
			if ($showExpedition == "1")
				$query .= ", expedition"; // add 'expedition' column
		}

		// ... if the user has checked the checkbox next to 'Conference', we'll add that column to the SELECT query:
		if (isset($_POST['showConference']))
		{
			$showConference = $_POST['showConference'];
			if ($showConference == "1")
				$query .= ", conference"; // add 'conference' column
		}

		// ... if the user has checked the checkbox next to 'DOI', we'll add that column to the SELECT query:
		if (isset($_POST['showDOI']))
		{
			$showDOI = $_POST['showDOI'];
			if ($showDOI == "1")
				$query .= ", doi"; // add 'doi' column
		}

		// ... if the user has checked the checkbox next to 'URL', we'll add that column to the SELECT query:
		if (isset($_POST['showURL']))
		{
			$showURL = $_POST['showURL'];
			if ($showURL == "1")
				$query .= ", url"; // add 'url' column
		}

		// ... if the user has checked the checkbox next to 'Location', we'll add that column to the SELECT query:
		if (isset($_POST['showLocation']))
		{
			$showLocation = $_POST['showLocation'];
			if ($showLocation == "1")
				$query .= ", location"; // add 'location' column
		}

		// ... if the user has checked the checkbox next to 'Call Number', we'll add that column to the SELECT query:
		if (isset($_POST['showCallNumber']))
		{
			$showCallNumber = $_POST['showCallNumber'];
			if ($showCallNumber == "1")
				$query .= ", call_number"; // add 'call_number' column
		}

		// ... if the user has checked the checkbox next to 'File Name', we'll add that column to the SELECT query:
		if (isset($_POST['showFile']))
		{
			$showFile = $_POST['showFile'];
			if ($showFile == "1")
				$query .= ", file"; // add 'file' column
		}

		// ... if the user has checked the checkbox next to 'Copy', we'll add that column to the SELECT query:
		if (isset($_POST['showCopy']))
		{
			$showCopy = $_POST['showCopy'];
			if ($showCopy == "1")
				$query .= ", copy"; // add 'copy' column
		}

		// ... if the user has checked the checkbox next to 'Notes', we'll add that column to the SELECT query:
		if (isset($_POST['showNotes']))
		{
			$showNotes = $_POST['showNotes'];
			if ($showNotes == "1")
				$query .= ", notes"; // add 'notes' column
		}

		// ... if the user has checked the checkbox next to 'User Keys', we'll add that column to the SELECT query:
		if (isset($_POST['showUserKeys']))
		{
			$showUserKeys = $_POST['showUserKeys'];
			if ($showUserKeys == "1")
				$query .= ", user_keys"; // add 'user_keys' column
		}

		// ... if the user has checked the checkbox next to 'User Notes', we'll add that column to the SELECT query:
		if (isset($_POST['showUserNotes']))
		{
			$showUserNotes = $_POST['showUserNotes'];
			if ($showUserNotes == "1")
				$query .= ", user_notes"; // add 'user_notes' column
		}

		// ... if the user has checked the checkbox next to 'User File', we'll add that column to the SELECT query:
		if (isset($_POST['showUserFile']))
		{
			$showUserFile = $_POST['showUserFile'];
			if ($showUserFile == "1")
				$query .= ", user_file"; // add 'user_file' column
		}

		// ... if the user has checked the checkbox next to 'Serial', we'll add that column to the SELECT query:
		if (isset($_POST['showSerial']))
		{
			$showSerial = $_POST['showSerial'];
			if ($showSerial == "1")
				$query .= ", serial"; // add 'serial' column
		}

		// ... if the user has checked the checkbox next to 'Type', we'll add that column to the SELECT query:
		if (isset($_POST['showType']))
		{
			$showType = $_POST['showType'];
			if ($showType == "1")
				$query .= ", type"; // add 'type' column
		}

		// ... if the user has checked the checkbox next to 'Marked', we'll add that column to the SELECT query:
		if (isset($_POST['showMarked']))
		{
			$showMarked = $_POST['showMarked'];
			if ($showMarked == "1")
				$query .= ", marked"; // add 'marked' column
		}

		// ... if the user has checked the checkbox next to 'Selected', we'll add that column to the SELECT query:
		if (isset($_POST['showSelected']))
		{
			$showSelected = $_POST['showSelected'];
			if ($showSelected == "1")
				$query .= ", selected"; // add 'selected' column
		}

		// ... if the user has checked the checkbox next to 'Approved', we'll add that column to the SELECT query:
		if (isset($_POST['showApproved']))
		{
			$showApproved = $_POST['showApproved'];
			if ($showApproved == "1")
				$query .= ", approved"; // add 'approved' column
		}

		// ... if the user has checked the checkbox next to 'Date Created', we'll add that column to the SELECT query:
		if (isset($_POST['showCreatedDate']))
		{
			$showCreatedDate = $_POST['showCreatedDate'];
			if ($showCreatedDate == "1")
				$query .= ", created_date"; // add 'created_date' column
		}

		// ... if the user has checked the checkbox next to 'Time Created', we'll add that column to the SELECT query:
		if (isset($_POST['showCreatedTime']))
		{
			$showCreatedTime = $_POST['showCreatedTime'];
			if ($showCreatedTime == "1")
				$query .= ", created_time"; // add 'created_time' column
		}

		// ... if the user has checked the checkbox next to 'Created By', we'll add that column to the SELECT query:
		if (isset($_POST['showCreatedBy']))
		{
			$showCreatedBy = $_POST['showCreatedBy'];
			if ($showCreatedBy == "1")
				$query .= ", created_by"; // add 'created_by' column
		}

		// ... if the user has checked the checkbox next to 'Date Modified', we'll add that column to the SELECT query:
		if (isset($_POST['showModifiedDate']))
		{
			$showModifiedDate = $_POST['showModifiedDate'];
			if ($showModifiedDate == "1")
				$query .= ", modified_date"; // add 'modified_date' column
		}

		// ... if the user has checked the checkbox next to 'Time Modified', we'll add that column to the SELECT query:
		if (isset($_POST['showModifiedTime']))
		{
			$showModifiedTime = $_POST['showModifiedTime'];
			if ($showModifiedTime == "1")
				$query .= ", modified_time"; // add 'modified_time' column
		}

		// ... if the user has checked the checkbox next to 'Modified By', we'll add that column to the SELECT query:
		if (isset($_POST['showModifiedBy']))
		{
			$showModifiedBy = $_POST['showModifiedBy'];
			if ($showModifiedBy == "1")
				$query .= ", modified_by"; // add 'modified_by' column
		}

		// ... we still have to trap the case that the (silly!) user hasn't checked any of the column checkboxes above:
		if ($query == "SELECT")
			$query .= " author"; // force add 'author' column if the user hasn't checked any of the column checkboxes

		$query .= ", orig_record"; // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
								//  (which is required in order to present visual feedback on duplicate records)

		$query .= ", serial"; // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
							//  (which is required in order to obtain unique checkbox names)

		if ($showLinks == "1")
			$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

		// Finally, fix the wrong syntax where its says "SELECT, author, title, ..." instead of "SELECT author, title, ..."
		$query = str_replace("SELECT, ","SELECT ",$query);

		if (isset($_SESSION['loginEmail'])) // if a user is logged in...
			$query .= " FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = " . $userID . " WHERE serial RLIKE \".+\""; // add FROM & (initial) WHERE clause
		else // NO user logged in
			$query .= " FROM refs WHERE serial RLIKE \".+\""; // add FROM & (initial) WHERE clause
		
		// ---------------------------------------

		// ... if the user has specified an author, add the value of '$authorName' as an AND clause:
		$authorName = $_POST['authorName'];
		if ($authorName != "")
			{
				$authorSelector = $_POST['authorSelector'];
				if ($authorSelector == "contains")
					$query .= " AND author RLIKE \"$authorName\"";
				elseif ($authorSelector == "does not contain")
					$query .= " AND author NOT RLIKE \"$authorName\"";
				elseif ($authorSelector == "is equal to")
					$query .= " AND author = \"$authorName\"";
				elseif ($authorSelector == "is not equal to")
					$query .= " AND author != \"$authorName\"";
				elseif ($authorSelector == "starts with")
					$query .= " AND author RLIKE \"^$authorName\"";
				elseif ($authorSelector == "ends with")
					$query .= " AND author RLIKE \"$authorName$\"";
			}

		// ... if the user has specified an address, add the value of '$addressName' as an AND clause:
		$addressName = $_POST['addressName'];
		if ($addressName != "")
			{
				$addressSelector = $_POST['addressSelector'];
				if ($addressSelector == "contains")
					$query .= " AND address RLIKE \"$addressName\"";
				elseif ($addressSelector == "does not contain")
					$query .= " AND address NOT RLIKE \"$addressName\"";
				elseif ($addressSelector == "is equal to")
					$query .= " AND address = \"$addressName\"";
				elseif ($addressSelector == "is not equal to")
					$query .= " AND address != \"$addressName\"";
				elseif ($addressSelector == "starts with")
					$query .= " AND address RLIKE \"^$addressName\"";
				elseif ($addressSelector == "ends with")
					$query .= " AND address RLIKE \"$addressName$\"";
			}

		// ... if the user has specified a corporate author, add the value of '$corporateAuthorName' as an AND clause:
		$corporateAuthorName = $_POST['corporateAuthorName'];
		if ($corporateAuthorName != "")
			{
				$corporateAuthorSelector = $_POST['corporateAuthorSelector'];
				if ($corporateAuthorSelector == "contains")
					$query .= " AND corporate_author RLIKE \"$corporateAuthorName\"";
				elseif ($corporateAuthorSelector == "does not contain")
					$query .= " AND corporate_author NOT RLIKE \"$corporateAuthorName\"";
				elseif ($corporateAuthorSelector == "is equal to")
					$query .= " AND corporate_author = \"$corporateAuthorName\"";
				elseif ($corporateAuthorSelector == "is not equal to")
					$query .= " AND corporate_author != \"$corporateAuthorName\"";
				elseif ($corporateAuthorSelector == "starts with")
					$query .= " AND corporate_author RLIKE \"^$corporateAuthorName\"";
				elseif ($corporateAuthorSelector == "ends with")
					$query .= " AND corporate_author RLIKE \"$corporateAuthorName$\"";
			}

		// ... if the user has specified a thesis, add the value of '$thesisName' as an AND clause:
		$thesisRadio = $_POST['thesisRadio'];
		if ($thesisRadio == "1")
		{
			$thesisName = $_POST['thesisName'];
			if ($thesisName != "All" && $thesisName != "")
				{
					$thesisSelector = $_POST['thesisSelector'];
					if ($thesisSelector == "contains")
						$query .= " AND thesis RLIKE \"$thesisName\"";
					elseif ($thesisSelector == "does not contain")
						$query .= " AND thesis NOT RLIKE \"$thesisName\"";
					elseif ($thesisSelector == "is equal to")
						$query .= " AND thesis = \"$thesisName\"";
					elseif ($thesisSelector == "is not equal to")
						$query .= " AND thesis != \"$thesisName\"";
					elseif ($thesisSelector == "starts with")
						$query .= " AND thesis RLIKE \"^$thesisName\"";
					elseif ($thesisSelector == "ends with")
						$query .= " AND thesis RLIKE \"$thesisName$\"";
				}
		}
		elseif ($thesisRadio == "0")
		{
			$thesisName2 = $_POST['thesisName2'];
			if ($thesisName2 != "")
				{
					$thesisSelector2 = $_POST['thesisSelector2'];
					if ($thesisSelector2 == "contains")
						$query .= " AND thesis RLIKE \"$thesisName2\"";
					elseif ($thesisSelector2 == "does not contain")
						$query .= " AND thesis NOT RLIKE \"$thesisName2\"";
					elseif ($thesisSelector2 == "is equal to")
						$query .= " AND thesis = \"$thesisName2\"";
					elseif ($thesisSelector2 == "is not equal to")
						$query .= " AND thesis != \"$thesisName2\"";
					elseif ($thesisSelector2 == "starts with")
						$query .= " AND thesis RLIKE \"^$thesisName2\"";
					elseif ($thesisSelector2 == "ends with")
						$query .= " AND thesis RLIKE \"$thesisName2$\"";
				}
		}

		// ... if the user has specified a title, add the value of '$titleName' as an AND clause:
		$titleName = $_POST['titleName'];
		if ($titleName != "")
			{
				$titleSelector = $_POST['titleSelector'];
				if ($titleSelector == "contains")
					$query .= " AND title RLIKE \"$titleName\"";
				elseif ($titleSelector == "does not contain")
					$query .= " AND title NOT RLIKE \"$titleName\"";
				elseif ($titleSelector == "is equal to")
					$query .= " AND title = \"$titleName\"";
				elseif ($titleSelector == "is not equal to")
					$query .= " AND title != \"$titleName\"";
				elseif ($titleSelector == "starts with")
					$query .= " AND title RLIKE \"^$titleName\"";
				elseif ($titleSelector == "ends with")
					$query .= " AND title RLIKE \"$titleName$\"";
			}

		// ... if the user has specified an original title, add the value of '$origTitleName' as an AND clause:
		$origTitleName = $_POST['origTitleName'];
		if ($origTitleName != "")
			{
				$origTitleSelector = $_POST['origTitleSelector'];
				if ($origTitleSelector == "contains")
					$query .= " AND orig_title RLIKE \"$origTitleName\"";
				elseif ($origTitleSelector == "does not contain")
					$query .= " AND orig_title NOT RLIKE \"$origTitleName\"";
				elseif ($origTitleSelector == "is equal to")
					$query .= " AND orig_title = \"$origTitleName\"";
				elseif ($origTitleSelector == "is not equal to")
					$query .= " AND orig_title != \"$origTitleName\"";
				elseif ($origTitleSelector == "starts with")
					$query .= " AND orig_title RLIKE \"^$origTitleName\"";
				elseif ($origTitleSelector == "ends with")
					$query .= " AND orig_title RLIKE \"$origTitleName$\"";
			}

		// ... if the user has specified a year, add the value of '$yearNo' as an AND clause:
		$yearNo = $_POST['yearNo'];
		if ($yearNo != "")
			{
				$yearSelector = $_POST['yearSelector'];
				if ($yearSelector == "contains")
					$query .= " AND year RLIKE \"$yearNo\"";
				elseif ($yearSelector == "does not contain")
					$query .= " AND year NOT RLIKE \"$yearNo\"";
				elseif ($yearSelector == "is equal to")
					$query .= " AND year = \"$yearNo\"";
				elseif ($yearSelector == "is not equal to")
					$query .= " AND year != \"$yearNo\"";
				elseif ($yearSelector == "starts with")
					$query .= " AND year RLIKE \"^$yearNo\"";
				elseif ($yearSelector == "ends with")
					$query .= " AND year RLIKE \"$yearNo$\"";
				elseif ($yearSelector == "is greater than")
					$query .= " AND year > \"$yearNo\"";
				elseif ($yearSelector == "is less than")
					$query .= " AND year < \"$yearNo\"";
			}

		// ... if the user has specified a publication, add the value of '$publicationName' as an AND clause:
		$publicationRadio = $_POST['publicationRadio'];
		if ($publicationRadio == "1")
		{
			$publicationName = $_POST['publicationName'];
			if ($publicationName != "All" && $publicationName != "")
				{
					$publicationSelector = $_POST['publicationSelector'];
					if ($publicationSelector == "contains")
						$query .= " AND publication RLIKE \"$publicationName\"";
					elseif ($publicationSelector == "does not contain")
						$query .= " AND publication NOT RLIKE \"$publicationName\"";
					elseif ($publicationSelector == "is equal to")
						$query .= " AND publication = \"$publicationName\"";
					elseif ($publicationSelector == "is not equal to")
						$query .= " AND publication != \"$publicationName\"";
					elseif ($publicationSelector == "starts with")
						$query .= " AND publication RLIKE \"^$publicationName\"";
					elseif ($publicationSelector == "ends with")
						$query .= " AND publication RLIKE \"$publicationName$\"";
				}
		}
		elseif ($publicationRadio == "0")
		{
			$publicationName2 = $_POST['publicationName2'];
			if ($publicationName2 != "")
				{
					$publicationSelector2 = $_POST['publicationSelector2'];
					if ($publicationSelector2 == "contains")
						$query .= " AND publication RLIKE \"$publicationName2\"";
					elseif ($publicationSelector2 == "does not contain")
						$query .= " AND publication NOT RLIKE \"$publicationName2\"";
					elseif ($publicationSelector2 == "is equal to")
						$query .= " AND publication = \"$publicationName2\"";
					elseif ($publicationSelector2 == "is not equal to")
						$query .= " AND publication != \"$publicationName2\"";
					elseif ($publicationSelector2 == "starts with")
						$query .= " AND publication RLIKE \"^$publicationName2\"";
					elseif ($publicationSelector2 == "ends with")
						$query .= " AND publication RLIKE \"$publicationName2$\"";
				}
		}

		// ... if the user has specified an abbreviated journal, add the value of '$abbrevJournalName' as an AND clause:
		$abbrevJournalRadio = $_POST['abbrevJournalRadio'];
		if ($abbrevJournalRadio == "1")
		{
			$abbrevJournalName = $_POST['abbrevJournalName'];
			if ($abbrevJournalName != "All" && $abbrevJournalName != "")
				{
					$abbrevJournalSelector = $_POST['abbrevJournalSelector'];
					if ($abbrevJournalSelector == "contains")
						$query .= " AND abbrev_journal RLIKE \"$abbrevJournalName\"";
					elseif ($abbrevJournalSelector == "does not contain")
						$query .= " AND abbrev_journal NOT RLIKE \"$abbrevJournalName\"";
					elseif ($abbrevJournalSelector == "is equal to")
						$query .= " AND abbrev_journal = \"$abbrevJournalName\"";
					elseif ($abbrevJournalSelector == "is not equal to")
						$query .= " AND abbrev_journal != \"$abbrevJournalName\"";
					elseif ($abbrevJournalSelector == "starts with")
						$query .= " AND abbrev_journal RLIKE \"^$abbrevJournalName\"";
					elseif ($abbrevJournalSelector == "ends with")
						$query .= " AND abbrev_journal RLIKE \"$abbrevJournalName$\"";
				}
		}
		elseif ($abbrevJournalRadio == "0")
		{
			$abbrevJournalName2 = $_POST['abbrevJournalName2'];
			if ($abbrevJournalName2 != "")
				{
					$abbrevJournalSelector2 = $_POST['abbrevJournalSelector2'];
					if ($abbrevJournalSelector2 == "contains")
						$query .= " AND abbrev_journal RLIKE \"$abbrevJournalName2\"";
					elseif ($abbrevJournalSelector2 == "does not contain")
						$query .= " AND abbrev_journal NOT RLIKE \"$abbrevJournalName2\"";
					elseif ($abbrevJournalSelector2 == "is equal to")
						$query .= " AND abbrev_journal = \"$abbrevJournalName2\"";
					elseif ($abbrevJournalSelector2 == "is not equal to")
						$query .= " AND abbrev_journal != \"$abbrevJournalName2\"";
					elseif ($abbrevJournalSelector2 == "starts with")
						$query .= " AND abbrev_journal RLIKE \"^$abbrevJournalName2\"";
					elseif ($abbrevJournalSelector2 == "ends with")
						$query .= " AND abbrev_journal RLIKE \"$abbrevJournalName2$\"";
				}
		}

		// ... if the user has specified an editor, add the value of '$editorName' as an AND clause:
		$editorName = $_POST['editorName'];
		if ($editorName != "")
			{
				$editorSelector = $_POST['editorSelector'];
				if ($editorSelector == "contains")
					$query .= " AND editor RLIKE \"$editorName\"";
				elseif ($editorSelector == "does not contain")
					$query .= " AND editor NOT RLIKE \"$editorName\"";
				elseif ($editorSelector == "is equal to")
					$query .= " AND editor = \"$editorName\"";
				elseif ($editorSelector == "is not equal to")
					$query .= " AND editor != \"$editorName\"";
				elseif ($editorSelector == "starts with")
					$query .= " AND editor RLIKE \"^$editorName\"";
				elseif ($editorSelector == "ends with")
					$query .= " AND editor RLIKE \"$editorName$\"";
			}

		// ... if the user has specified a volume, add the value of '$volumeNo' as an AND clause:
		$volumeNo = $_POST['volumeNo'];
		if ($volumeNo != "")
			{
				$volumeSelector = $_POST['volumeSelector'];
				if ($volumeSelector == "contains")
					$query .= " AND volume RLIKE \"$volumeNo\"";
				elseif ($volumeSelector == "does not contain")
					$query .= " AND volume NOT RLIKE \"$volumeNo\"";
				elseif ($volumeSelector == "is equal to")
					$query .= " AND volume = \"$volumeNo\"";
				elseif ($volumeSelector == "is not equal to")
					$query .= " AND volume != \"$volumeNo\"";
				elseif ($volumeSelector == "starts with")
					$query .= " AND volume RLIKE \"^$volumeNo\"";
				elseif ($volumeSelector == "ends with")
					$query .= " AND volume RLIKE \"$volumeNo$\"";
				elseif ($volumeSelector == "is greater than")
					$query .= " AND volume > \"$volumeNo\"";
				elseif ($volumeSelector == "is less than")
					$query .= " AND volume < \"$volumeNo\"";
			}

		// ... if the user has specified an issue, add the value of '$issueNo' as an AND clause:
		$issueNo = $_POST['issueNo'];
		if ($issueNo != "")
			{
				$issueSelector = $_POST['issueSelector'];
				if ($issueSelector == "contains")
					$query .= " AND issue RLIKE \"$issueNo\"";
				elseif ($issueSelector == "does not contain")
					$query .= " AND issue NOT RLIKE \"$issueNo\"";
				elseif ($issueSelector == "is equal to")
					$query .= " AND issue = \"$issueNo\"";
				elseif ($issueSelector == "is not equal to")
					$query .= " AND issue != \"$issueNo\"";
				elseif ($issueSelector == "starts with")
					$query .= " AND issue RLIKE \"^$issueNo\"";
				elseif ($issueSelector == "ends with")
					$query .= " AND issue RLIKE \"$issueNo$\"";
				elseif ($issueSelector == "is greater than")
					$query .= " AND issue > \"$issueNo\"";
				elseif ($issueSelector == "is less than")
					$query .= " AND issue < \"$issueNo\"";
			}

		// ... if the user has specified some pages, add the value of '$pagesNo' as an AND clause:
		$pagesNo = $_POST['pagesNo'];
		if ($pagesNo != "")
			{
				$pagesSelector = $_POST['pagesSelector'];
				if ($pagesSelector == "contains")
					$query .= " AND pages RLIKE \"$pagesNo\"";
				elseif ($pagesSelector == "does not contain")
					$query .= " AND pages NOT RLIKE \"$pagesNo\"";
				elseif ($pagesSelector == "is equal to")
					$query .= " AND pages = \"$pagesNo\"";
				elseif ($pagesSelector == "is not equal to")
					$query .= " AND pages != \"$pagesNo\"";
				elseif ($pagesSelector == "starts with")
					$query .= " AND pages RLIKE \"^$pagesNo\"";
				elseif ($pagesSelector == "ends with")
					$query .= " AND pages RLIKE \"$pagesNo$\"";
			}


		// ... if the user has specified a series title, add the value of '$seriesTitleName' as an AND clause:
		$seriesTitleRadio = $_POST['seriesTitleRadio'];
		if ($seriesTitleRadio == "1")
		{
			$seriesTitleName = $_POST['seriesTitleName'];
			if ($seriesTitleName != "All" && $seriesTitleName != "")
				{
					$seriesTitleSelector = $_POST['seriesTitleSelector'];
					if ($seriesTitleSelector == "contains")
						$query .= " AND series_title RLIKE \"$seriesTitleName\"";
					elseif ($seriesTitleSelector == "does not contain")
						$query .= " AND series_title NOT RLIKE \"$seriesTitleName\"";
					elseif ($seriesTitleSelector == "is equal to")
						$query .= " AND series_title = \"$seriesTitleName\"";
					elseif ($seriesTitleSelector == "is not equal to")
						$query .= " AND series_title != \"$seriesTitleName\"";
					elseif ($seriesTitleSelector == "starts with")
						$query .= " AND series_title RLIKE \"^$seriesTitleName\"";
					elseif ($seriesTitleSelector == "ends with")
						$query .= " AND series_title RLIKE \"$seriesTitleName$\"";
				}
		}
		elseif ($seriesTitleRadio == "0")
		{
			$seriesTitleName2 = $_POST['seriesTitleName2'];
			if ($seriesTitleName2 != "")
				{
					$seriesTitleSelector2 = $_POST['seriesTitleSelector2'];
					if ($seriesTitleSelector2 == "contains")
						$query .= " AND series_title RLIKE \"$seriesTitleName2\"";
					elseif ($seriesTitleSelector2 == "does not contain")
						$query .= " AND series_title NOT RLIKE \"$seriesTitleName2\"";
					elseif ($seriesTitleSelector2 == "is equal to")
						$query .= " AND series_title = \"$seriesTitleName2\"";
					elseif ($seriesTitleSelector2 == "is not equal to")
						$query .= " AND series_title != \"$seriesTitleName2\"";
					elseif ($seriesTitleSelector2 == "starts with")
						$query .= " AND series_title RLIKE \"^$seriesTitleName2\"";
					elseif ($seriesTitleSelector2 == "ends with")
						$query .= " AND series_title RLIKE \"$seriesTitleName2$\"";
				}
		}

		// ... if the user has specified an abbreviated series title, add the value of '$abbrevSeriesTitleName' as an AND clause:
		$abbrevSeriesTitleRadio = $_POST['abbrevSeriesTitleRadio'];
		if ($abbrevSeriesTitleRadio == "1")
		{
			$abbrevSeriesTitleName = $_POST['abbrevSeriesTitleName'];
			if ($abbrevSeriesTitleName != "All" && $abbrevSeriesTitleName != "")
				{
					$abbrevSeriesTitleSelector = $_POST['abbrevSeriesTitleSelector'];
					if ($abbrevSeriesTitleSelector == "contains")
						$query .= " AND abbrev_series_title RLIKE \"$abbrevSeriesTitleName\"";
					elseif ($abbrevSeriesTitleSelector == "does not contain")
						$query .= " AND abbrev_series_title NOT RLIKE \"$abbrevSeriesTitleName\"";
					elseif ($abbrevSeriesTitleSelector == "is equal to")
						$query .= " AND abbrev_series_title = \"$abbrevSeriesTitleName\"";
					elseif ($abbrevSeriesTitleSelector == "is not equal to")
						$query .= " AND abbrev_series_title != \"$abbrevSeriesTitleName\"";
					elseif ($abbrevSeriesTitleSelector == "starts with")
						$query .= " AND abbrev_series_title RLIKE \"^$abbrevSeriesTitleName\"";
					elseif ($abbrevSeriesTitleSelector == "ends with")
						$query .= " AND abbrev_series_title RLIKE \"$abbrevSeriesTitleName$\"";
				}
		}
		elseif ($abbrevSeriesTitleRadio == "0")
		{
			$abbrevSeriesTitleName2 = $_POST['abbrevSeriesTitleName2'];
			if ($abbrevSeriesTitleName2 != "")
				{
					$abbrevSeriesTitleSelector2 = $_POST['abbrevSeriesTitleSelector2'];
					if ($abbrevSeriesTitleSelector2 == "contains")
						$query .= " AND abbrev_series_title RLIKE \"$abbrevSeriesTitleName2\"";
					elseif ($abbrevSeriesTitleSelector2 == "does not contain")
						$query .= " AND abbrev_series_title NOT RLIKE \"$abbrevSeriesTitleName2\"";
					elseif ($abbrevSeriesTitleSelector2 == "is equal to")
						$query .= " AND abbrev_series_title = \"$abbrevSeriesTitleName2\"";
					elseif ($abbrevSeriesTitleSelector2 == "is not equal to")
						$query .= " AND abbrev_series_title != \"$abbrevSeriesTitleName2\"";
					elseif ($abbrevSeriesTitleSelector2 == "starts with")
						$query .= " AND abbrev_series_title RLIKE \"^$abbrevSeriesTitleName2\"";
					elseif ($abbrevSeriesTitleSelector2 == "ends with")
						$query .= " AND abbrev_series_title RLIKE \"$abbrevSeriesTitleName2$\"";
				}
		}

		// ... if the user has specified a series editor, add the value of '$seriesEditorName' as an AND clause:
		$seriesEditorName = $_POST['seriesEditorName'];
		if ($seriesEditorName != "")
			{
				$seriesEditorSelector = $_POST['seriesEditorSelector'];
				if ($seriesEditorSelector == "contains")
					$query .= " AND series_editor RLIKE \"$seriesEditorName\"";
				elseif ($seriesEditorSelector == "does not contain")
					$query .= " AND series_editor NOT RLIKE \"$seriesEditorName\"";
				elseif ($seriesEditorSelector == "is equal to")
					$query .= " AND series_editor = \"$seriesEditorName\"";
				elseif ($seriesEditorSelector == "is not equal to")
					$query .= " AND series_editor != \"$seriesEditorName\"";
				elseif ($seriesEditorSelector == "starts with")
					$query .= " AND series_editor RLIKE \"^$seriesEditorName\"";
				elseif ($seriesEditorSelector == "ends with")
					$query .= " AND series_editor RLIKE \"$seriesEditorName$\"";
			}


		// ... if the user has specified a series volume, add the value of '$seriesVolumeNo' as an AND clause:
		$seriesVolumeNo = $_POST['seriesVolumeNo'];
		if ($seriesVolumeNo != "")
			{
				$seriesVolumeSelector = $_POST['seriesVolumeSelector'];
				if ($seriesVolumeSelector == "contains")
					$query .= " AND series_volume RLIKE \"$seriesVolumeNo\"";
				elseif ($seriesVolumeSelector == "does not contain")
					$query .= " AND series_volume NOT RLIKE \"$seriesVolumeNo\"";
				elseif ($seriesVolumeSelector == "is equal to")
					$query .= " AND series_volume = \"$seriesVolumeNo\"";
				elseif ($seriesVolumeSelector == "is not equal to")
					$query .= " AND series_volume != \"$seriesVolumeNo\"";
				elseif ($seriesVolumeSelector == "starts with")
					$query .= " AND series_volume RLIKE \"^$seriesVolumeNo\"";
				elseif ($seriesVolumeSelector == "ends with")
					$query .= " AND series_volume RLIKE \"$seriesVolumeNo$\"";
				elseif ($seriesVolumeSelector == "is greater than")
					$query .= " AND series_volume > \"$seriesVolumeNo\"";
				elseif ($seriesVolumeSelector == "is less than")
					$query .= " AND series_volume < \"$seriesVolumeNo\"";
			}

		// ... if the user has specified a series issue, add the value of '$seriesIssueNo' as an AND clause:
		$seriesIssueNo = $_POST['seriesIssueNo'];
		if ($seriesIssueNo != "")
			{
				$seriesIssueSelector = $_POST['seriesIssueSelector'];
				if ($seriesIssueSelector == "contains")
					$query .= " AND series_issue RLIKE \"$seriesIssueNo\"";
				elseif ($seriesIssueSelector == "does not contain")
					$query .= " AND series_issue NOT RLIKE \"$seriesIssueNo\"";
				elseif ($seriesIssueSelector == "is equal to")
					$query .= " AND series_issue = \"$seriesIssueNo\"";
				elseif ($seriesIssueSelector == "is not equal to")
					$query .= " AND series_issue != \"$seriesIssueNo\"";
				elseif ($seriesIssueSelector == "starts with")
					$query .= " AND series_issue RLIKE \"^$seriesIssueNo\"";
				elseif ($seriesIssueSelector == "ends with")
					$query .= " AND series_issue RLIKE \"$seriesIssueNo$\"";
				elseif ($seriesIssueSelector == "is greater than")
					$query .= " AND series_issue > \"$seriesIssueNo\"";
				elseif ($seriesIssueSelector == "is less than")
					$query .= " AND series_issue < \"$seriesIssueNo\"";
			}

		// ... if the user has specified a publisher, add the value of '$publisherName' as an AND clause:
		$publisherRadio = $_POST['publisherRadio'];
		if ($publisherRadio == "1")
		{
			$publisherName = $_POST['publisherName'];
			if ($publisherName != "All" && $publisherName != "")
				{
					$publisherSelector = $_POST['publisherSelector'];
					if ($publisherSelector == "contains")
						$query .= " AND publisher RLIKE \"$publisherName\"";
					elseif ($publisherSelector == "does not contain")
						$query .= " AND publisher NOT RLIKE \"$publisherName\"";
					elseif ($publisherSelector == "is equal to")
						$query .= " AND publisher = \"$publisherName\"";
					elseif ($publisherSelector == "is not equal to")
						$query .= " AND publisher != \"$publisherName\"";
					elseif ($publisherSelector == "starts with")
						$query .= " AND publisher RLIKE \"^$publisherName\"";
					elseif ($publisherSelector == "ends with")
						$query .= " AND publisher RLIKE \"$publisherName$\"";
				}
		}
		elseif ($publisherRadio == "0")
		{
			$publisherName2 = $_POST['publisherName2'];
			if ($publisherName2 != "")
				{
					$publisherSelector2 = $_POST['publisherSelector2'];
					if ($publisherSelector2 == "contains")
						$query .= " AND publisher RLIKE \"$publisherName2\"";
					elseif ($publisherSelector2 == "does not contain")
						$query .= " AND publisher NOT RLIKE \"$publisherName2\"";
					elseif ($publisherSelector2 == "is equal to")
						$query .= " AND publisher = \"$publisherName2\"";
					elseif ($publisherSelector2 == "is not equal to")
						$query .= " AND publisher != \"$publisherName2\"";
					elseif ($publisherSelector2 == "starts with")
						$query .= " AND publisher RLIKE \"^$publisherName2\"";
					elseif ($publisherSelector2 == "ends with")
						$query .= " AND publisher RLIKE \"$publisherName2$\"";
				}
		}

		// ... if the user has specified a place, add the value of '$placeName' as an AND clause:
		$placeRadio = $_POST['placeRadio'];
		if ($placeRadio == "1")
		{
			$placeName = $_POST['placeName'];
			if ($placeName != "All" && $placeName != "")
				{
					$placeSelector = $_POST['placeSelector'];
					if ($placeSelector == "contains")
						$query .= " AND place RLIKE \"$placeName\"";
					elseif ($placeSelector == "does not contain")
						$query .= " AND place NOT RLIKE \"$placeName\"";
					elseif ($placeSelector == "is equal to")
						$query .= " AND place = \"$placeName\"";
					elseif ($placeSelector == "is not equal to")
						$query .= " AND place != \"$placeName\"";
					elseif ($placeSelector == "starts with")
						$query .= " AND place RLIKE \"^$placeName\"";
					elseif ($placeSelector == "ends with")
						$query .= " AND place RLIKE \"$placeName$\"";
				}
		}
		elseif ($placeRadio == "0")
		{
			$placeName2 = $_POST['placeName2'];
			if ($placeName2 != "")
				{
					$placeSelector2 = $_POST['placeSelector2'];
					if ($placeSelector2 == "contains")
						$query .= " AND place RLIKE \"$placeName2\"";
					elseif ($placeSelector2 == "does not contain")
						$query .= " AND place NOT RLIKE \"$placeName2\"";
					elseif ($placeSelector2 == "is equal to")
						$query .= " AND place = \"$placeName2\"";
					elseif ($placeSelector2 == "is not equal to")
						$query .= " AND place != \"$placeName2\"";
					elseif ($placeSelector2 == "starts with")
						$query .= " AND place RLIKE \"^$placeName2\"";
					elseif ($placeSelector2 == "ends with")
						$query .= " AND place RLIKE \"$placeName2$\"";
				}
		}

		// ... if the user has specified an edition, add the value of '$editionNo' as an AND clause:
		$editionNo = $_POST['editionNo'];
		if ($editionNo != "")
			{
				$editionSelector = $_POST['editionSelector'];
				if ($editionSelector == "contains")
					$query .= " AND edition RLIKE \"$editionNo\"";
				elseif ($editionSelector == "does not contain")
					$query .= " AND edition NOT RLIKE \"$editionNo\"";
				elseif ($editionSelector == "is equal to")
					$query .= " AND edition = \"$editionNo\"";
				elseif ($editionSelector == "is not equal to")
					$query .= " AND edition != \"$editionNo\"";
				elseif ($editionSelector == "starts with")
					$query .= " AND edition RLIKE \"^$editionNo\"";
				elseif ($editionSelector == "ends with")
					$query .= " AND edition RLIKE \"$editionNo$\"";
				elseif ($editionSelector == "is greater than")
					$query .= " AND edition > \"$editionNo\"";
				elseif ($editionSelector == "is less than")
					$query .= " AND edition < \"$editionNo\"";
			}

		// ... if the user has specified a medium, add the value of '$mediumName' as an AND clause:
		$mediumName = $_POST['mediumName'];
		if ($mediumName != "")
			{
				$mediumSelector = $_POST['mediumSelector'];
				if ($mediumSelector == "contains")
					$query .= " AND medium RLIKE \"$mediumName\"";
				elseif ($mediumSelector == "does not contain")
					$query .= " AND medium NOT RLIKE \"$mediumName\"";
				elseif ($mediumSelector == "is equal to")
					$query .= " AND medium = \"$mediumName\"";
				elseif ($mediumSelector == "is not equal to")
					$query .= " AND medium != \"$mediumName\"";
				elseif ($mediumSelector == "starts with")
					$query .= " AND medium RLIKE \"^$mediumName\"";
				elseif ($mediumSelector == "ends with")
					$query .= " AND medium RLIKE \"$mediumName$\"";
			}

		// ... if the user has specified an ISSN, add the value of '$issnName' as an AND clause:
		$issnName = $_POST['issnName'];
		if ($issnName != "")
			{
				$issnSelector = $_POST['issnSelector'];
				if ($issnSelector == "contains")
					$query .= " AND issn RLIKE \"$issnName\"";
				elseif ($issnSelector == "does not contain")
					$query .= " AND issn NOT RLIKE \"$issnName\"";
				elseif ($issnSelector == "is equal to")
					$query .= " AND issn = \"$issnName\"";
				elseif ($issnSelector == "is not equal to")
					$query .= " AND issn != \"$issnName\"";
				elseif ($issnSelector == "starts with")
					$query .= " AND issn RLIKE \"^$issnName\"";
				elseif ($issnSelector == "ends with")
					$query .= " AND issn RLIKE \"$issnName$\"";
			}

		// ... if the user has specified an ISBN, add the value of '$isbnName' as an AND clause:
		$isbnName = $_POST['isbnName'];
		if ($isbnName != "")
			{
				$isbnSelector = $_POST['isbnSelector'];
				if ($isbnSelector == "contains")
					$query .= " AND isbn RLIKE \"$isbnName\"";
				elseif ($isbnSelector == "does not contain")
					$query .= " AND isbn NOT RLIKE \"$isbnName\"";
				elseif ($isbnSelector == "is equal to")
					$query .= " AND isbn = \"$isbnName\"";
				elseif ($isbnSelector == "is not equal to")
					$query .= " AND isbn != \"$isbnName\"";
				elseif ($isbnSelector == "starts with")
					$query .= " AND isbn RLIKE \"^$isbnName\"";
				elseif ($isbnSelector == "ends with")
					$query .= " AND isbn RLIKE \"$isbnName$\"";
			}


		// ... if the user has specified a language, add the value of '$languageName' as an AND clause:
		$languageRadio = $_POST['languageRadio'];
		if ($languageRadio == "1")
		{
			$languageName = $_POST['languageName'];
			if ($languageName != "All" && $languageName != "")
				{
					$languageSelector = $_POST['languageSelector'];
					if ($languageSelector == "contains")
						$query .= " AND language RLIKE \"$languageName\"";
					elseif ($languageSelector == "does not contain")
						$query .= " AND language NOT RLIKE \"$languageName\"";
					elseif ($languageSelector == "is equal to")
						$query .= " AND language = \"$languageName\"";
					elseif ($languageSelector == "is not equal to")
						$query .= " AND language != \"$languageName\"";
					elseif ($languageSelector == "starts with")
						$query .= " AND language RLIKE \"^$languageName\"";
					elseif ($languageSelector == "ends with")
						$query .= " AND language RLIKE \"$languageName$\"";
				}
		}
		elseif ($languageRadio == "0")
		{
			$languageName2 = $_POST['languageName2'];
			if ($languageName2 != "")
				{
					$languageSelector2 = $_POST['languageSelector2'];
					if ($languageSelector2 == "contains")
						$query .= " AND language RLIKE \"$languageName2\"";
					elseif ($languageSelector2 == "does not contain")
						$query .= " AND language NOT RLIKE \"$languageName2\"";
					elseif ($languageSelector2 == "is equal to")
						$query .= " AND language = \"$languageName2\"";
					elseif ($languageSelector2 == "is not equal to")
						$query .= " AND language != \"$languageName2\"";
					elseif ($languageSelector2 == "starts with")
						$query .= " AND language RLIKE \"^$languageName2\"";
					elseif ($languageSelector2 == "ends with")
						$query .= " AND language RLIKE \"$languageName2$\"";
				}
		}

		// ... if the user has specified a summary language, add the value of '$summaryLanguageName' as an AND clause:
		$summaryLanguageRadio = $_POST['summaryLanguageRadio'];
		if ($summaryLanguageRadio == "1")
		{
			$summaryLanguageName = $_POST['summaryLanguageName'];
			if ($summaryLanguageName != "All" && $summaryLanguageName != "")
				{
					$summaryLanguageSelector = $_POST['summaryLanguageSelector'];
					if ($summaryLanguageSelector == "contains")
						$query .= " AND summary_language RLIKE \"$summaryLanguageName\"";
					elseif ($summaryLanguageSelector == "does not contain")
						$query .= " AND summary_language NOT RLIKE \"$summaryLanguageName\"";
					elseif ($summaryLanguageSelector == "is equal to")
						$query .= " AND summary_language = \"$summaryLanguageName\"";
					elseif ($summaryLanguageSelector == "is not equal to")
						$query .= " AND summary_language != \"$summaryLanguageName\"";
					elseif ($summaryLanguageSelector == "starts with")
						$query .= " AND summary_language RLIKE \"^$summaryLanguageName\"";
					elseif ($summaryLanguageSelector == "ends with")
						$query .= " AND summary_language RLIKE \"$summaryLanguageName$\"";
				}
		}
		elseif ($summaryLanguageRadio == "0")
		{
			$summaryLanguageName2 = $_POST['summaryLanguageName2'];
			if ($summaryLanguageName2 != "")
				{
					$summaryLanguageSelector2 = $_POST['summaryLanguageSelector2'];
					if ($summaryLanguageSelector2 == "contains")
						$query .= " AND summary_language RLIKE \"$summaryLanguageName2\"";
					elseif ($summaryLanguageSelector2 == "does not contain")
						$query .= " AND summary_language NOT RLIKE \"$summaryLanguageName2\"";
					elseif ($summaryLanguageSelector2 == "is equal to")
						$query .= " AND summary_language = \"$summaryLanguageName2\"";
					elseif ($summaryLanguageSelector2 == "is not equal to")
						$query .= " AND summary_language != \"$summaryLanguageName2\"";
					elseif ($summaryLanguageSelector2 == "starts with")
						$query .= " AND summary_language RLIKE \"^$summaryLanguageName2\"";
					elseif ($summaryLanguageSelector2 == "ends with")
						$query .= " AND summary_language RLIKE \"$summaryLanguageName2$\"";
				}
		}

		// ... if the user has specified some keywords, add the value of '$keywordsName' as an AND clause:
		$keywordsName = $_POST['keywordsName'];
		if ($keywordsName != "")
			{
				$keywordsSelector = $_POST['keywordsSelector'];
				if ($keywordsSelector == "contains")
					$query .= " AND keywords RLIKE \"$keywordsName\"";
				elseif ($keywordsSelector == "does not contain")
					$query .= " AND keywords NOT RLIKE \"$keywordsName\"";
				elseif ($keywordsSelector == "is equal to")
					$query .= " AND keywords = \"$keywordsName\"";
				elseif ($keywordsSelector == "is not equal to")
					$query .= " AND keywords != \"$keywordsName\"";
				elseif ($keywordsSelector == "starts with")
					$query .= " AND keywords RLIKE \"^$keywordsName\"";
				elseif ($keywordsSelector == "ends with")
					$query .= " AND keywords RLIKE \"$keywordsName$\"";
			}

		// ... if the user has specified an abstract, add the value of '$abstractName' as an AND clause:
		$abstractName = $_POST['abstractName'];
		if ($abstractName != "")
			{
				$abstractSelector = $_POST['abstractSelector'];
				if ($abstractSelector == "contains")
					$query .= " AND abstract RLIKE \"$abstractName\"";
				elseif ($abstractSelector == "does not contain")
					$query .= " AND abstract NOT RLIKE \"$abstractName\"";
				elseif ($abstractSelector == "is equal to")
					$query .= " AND abstract = \"$abstractName\"";
				elseif ($abstractSelector == "is not equal to")
					$query .= " AND abstract != \"$abstractName\"";
				elseif ($abstractSelector == "starts with")
					$query .= " AND abstract RLIKE \"^$abstractName\"";
				elseif ($abstractSelector == "ends with")
					$query .= " AND abstract RLIKE \"$abstractName$\"";
			}


		// ... if the user has specified an area, add the value of '$areaName' as an AND clause:
		$areaRadio = $_POST['areaRadio'];
		if ($areaRadio == "1")
		{
			$areaName = $_POST['areaName'];
			if ($areaName != "All" && $areaName != "")
				{
					$areaSelector = $_POST['areaSelector'];
					if ($areaSelector == "contains")
						$query .= " AND area RLIKE \"$areaName\"";
					elseif ($areaSelector == "does not contain")
						$query .= " AND area NOT RLIKE \"$areaName\"";
					elseif ($areaSelector == "is equal to")
						$query .= " AND area = \"$areaName\"";
					elseif ($areaSelector == "is not equal to")
						$query .= " AND area != \"$areaName\"";
					elseif ($areaSelector == "starts with")
						$query .= " AND area RLIKE \"^$areaName\"";
					elseif ($areaSelector == "ends with")
						$query .= " AND area RLIKE \"$areaName$\"";
				}
		}
		elseif ($areaRadio == "0")
		{
			$areaName2 = $_POST['areaName2'];
			if ($areaName2 != "")
				{
					$areaSelector2 = $_POST['areaSelector2'];
					if ($areaSelector2 == "contains")
						$query .= " AND area RLIKE \"$areaName2\"";
					elseif ($areaSelector2 == "does not contain")
						$query .= " AND area NOT RLIKE \"$areaName2\"";
					elseif ($areaSelector2 == "is equal to")
						$query .= " AND area = \"$areaName2\"";
					elseif ($areaSelector2 == "is not equal to")
						$query .= " AND area != \"$areaName2\"";
					elseif ($areaSelector2 == "starts with")
						$query .= " AND area RLIKE \"^$areaName2\"";
					elseif ($areaSelector2 == "ends with")
						$query .= " AND area RLIKE \"$areaName2$\"";
				}
		}

		// ... if the user has specified an expedition, add the value of '$expeditionName' as an AND clause:
		$expeditionName = $_POST['expeditionName'];
		if ($expeditionName != "")
			{
				$expeditionSelector = $_POST['expeditionSelector'];
				if ($expeditionSelector == "contains")
					$query .= " AND expedition RLIKE \"$expeditionName\"";
				elseif ($expeditionSelector == "does not contain")
					$query .= " AND expedition NOT RLIKE \"$expeditionName\"";
				elseif ($expeditionSelector == "is equal to")
					$query .= " AND expedition = \"$expeditionName\"";
				elseif ($expeditionSelector == "is not equal to")
					$query .= " AND expedition != \"$expeditionName\"";
				elseif ($expeditionSelector == "starts with")
					$query .= " AND expedition RLIKE \"^$expeditionName\"";
				elseif ($expeditionSelector == "ends with")
					$query .= " AND expedition RLIKE \"$expeditionName$\"";
			}

		// ... if the user has specified a conference, add the value of '$conferenceName' as an AND clause:
		$conferenceName = $_POST['conferenceName'];
		if ($conferenceName != "")
			{
				$conferenceSelector = $_POST['conferenceSelector'];
				if ($conferenceSelector == "contains")
					$query .= " AND conference RLIKE \"$conferenceName\"";
				elseif ($conferenceSelector == "does not contain")
					$query .= " AND conference NOT RLIKE \"$conferenceName\"";
				elseif ($conferenceSelector == "is equal to")
					$query .= " AND conference = \"$conferenceName\"";
				elseif ($conferenceSelector == "is not equal to")
					$query .= " AND conference != \"$conferenceName\"";
				elseif ($conferenceSelector == "starts with")
					$query .= " AND conference RLIKE \"^$conferenceName\"";
				elseif ($conferenceSelector == "ends with")
					$query .= " AND conference RLIKE \"$conferenceName$\"";
			}

		// ... if the user has specified a DOI, add the value of '$doiName' as an AND clause:
		$doiName = $_POST['doiName'];
		if ($doiName != "")
			{
				$doiSelector = $_POST['doiSelector'];
				if ($doiSelector == "contains")
					$query .= " AND doi RLIKE \"$doiName\"";
				elseif ($doiSelector == "does not contain")
					$query .= " AND doi NOT RLIKE \"$doiName\"";
				elseif ($doiSelector == "is equal to")
					$query .= " AND doi = \"$doiName\"";
				elseif ($doiSelector == "is not equal to")
					$query .= " AND doi != \"$doiName\"";
				elseif ($doiSelector == "starts with")
					$query .= " AND doi RLIKE \"^$doiName\"";
				elseif ($doiSelector == "ends with")
					$query .= " AND doi RLIKE \"$doiName$\"";
			}

		// ... if the user has specified an URL, add the value of '$urlName' as an AND clause:
		$urlName = $_POST['urlName'];
		if ($urlName != "")
			{
				$urlSelector = $_POST['urlSelector'];
				if ($urlSelector == "contains")
					$query .= " AND url RLIKE \"$urlName\"";
				elseif ($urlSelector == "does not contain")
					$query .= " AND url NOT RLIKE \"$urlName\"";
				elseif ($urlSelector == "is equal to")
					$query .= " AND url = \"$urlName\"";
				elseif ($urlSelector == "is not equal to")
					$query .= " AND url != \"$urlName\"";
				elseif ($urlSelector == "starts with")
					$query .= " AND url RLIKE \"^$urlName\"";
				elseif ($urlSelector == "ends with")
					$query .= " AND url RLIKE \"$urlName$\"";
			}


		// ... if the user has specified a location, add the value of '$locationName' as an AND clause:
		$locationRadio = $_POST['locationRadio'];
		if ($locationRadio == "1")
		{
			$locationName = $_POST['locationName'];
			if ($locationName != "All" && $locationName != "")
				{
					$locationSelector = $_POST['locationSelector'];
					if ($locationSelector == "contains")
						$query .= " AND location RLIKE \"$locationName\"";
					elseif ($locationSelector == "does not contain")
						$query .= " AND location NOT RLIKE \"$locationName\"";
					elseif ($locationSelector == "is equal to")
						$query .= " AND location = \"$locationName\"";
					elseif ($locationSelector == "is not equal to")
						$query .= " AND location != \"$locationName\"";
					elseif ($locationSelector == "starts with")
						$query .= " AND location RLIKE \"^$locationName\"";
					elseif ($locationSelector == "ends with")
						$query .= " AND location RLIKE \"$locationName$\"";
				}
		}
		elseif ($locationRadio == "0")
		{
			$locationName2 = $_POST['locationName2'];
			if ($locationName2 != "")
				{
					$locationSelector2 = $_POST['locationSelector2'];
					if ($locationSelector2 == "contains")
						$query .= " AND location RLIKE \"$locationName2\"";
					elseif ($locationSelector2 == "does not contain")
						$query .= " AND location NOT RLIKE \"$locationName2\"";
					elseif ($locationSelector2 == "is equal to")
						$query .= " AND location = \"$locationName2\"";
					elseif ($locationSelector2 == "is not equal to")
						$query .= " AND location != \"$locationName2\"";
					elseif ($locationSelector2 == "starts with")
						$query .= " AND location RLIKE \"^$locationName2\"";
					elseif ($locationSelector2 == "ends with")
						$query .= " AND location RLIKE \"$locationName2$\"";
				}
		}

		// ... if the user has specified a call number, add the value of '$callNumberName' as an AND clause:
		$callNumberName = $_POST['callNumberName'];
		if ($callNumberName != "")
			{
				$callNumberSelector = $_POST['callNumberSelector'];
				if ($callNumberSelector == "contains")
					$query .= " AND call_number RLIKE \"$callNumberName\"";
				elseif ($callNumberSelector == "does not contain")
					$query .= " AND call_number NOT RLIKE \"$callNumberName\"";
				elseif ($callNumberSelector == "is equal to")
					$query .= " AND call_number = \"$callNumberName\"";
				elseif ($callNumberSelector == "is not equal to")
					$query .= " AND call_number != \"$callNumberName\"";
				elseif ($callNumberSelector == "starts with")
					$query .= " AND call_number RLIKE \"^$callNumberName\"";
				elseif ($callNumberSelector == "ends with")
					$query .= " AND call_number RLIKE \"$callNumberName$\"";
			}

		// ... if the user has specified a file, add the value of '$fileName' as an AND clause:
		$fileName = $_POST['fileName'];
		if ($fileName != "")
			{
				$fileSelector = $_POST['fileSelector'];
				if ($fileSelector == "contains")
					$query .= " AND file RLIKE \"$fileName\"";
				elseif ($fileSelector == "does not contain")
					$query .= " AND file NOT RLIKE \"$fileName\"";
				elseif ($fileSelector == "is equal to")
					$query .= " AND file = \"$fileName\"";
				elseif ($fileSelector == "is not equal to")
					$query .= " AND file != \"$fileName\"";
				elseif ($fileSelector == "starts with")
					$query .= " AND file RLIKE \"^$fileName\"";
				elseif ($fileSelector == "ends with")
					$query .= " AND file RLIKE \"$fileName$\"";
			}


		// ... if the user has specified a copy status, add the value of '$copyName' as an AND clause:
		$copyName = $_POST['copyName'];
		if ($copyName != "All" && $copyName != "")
			{
				$copySelector = $_POST['copySelector'];
				if ($copySelector == "is equal to")
					$query .= " AND copy = \"$copyName\"";
				elseif ($copySelector == "is not equal to")
					$query .= " AND copy != \"$copyName\"";
			}

		// ... if the user has specified some notes, add the value of '$notesName' as an AND clause:
		$notesName = $_POST['notesName'];
		if ($notesName != "")
			{
				$notesSelector = $_POST['notesSelector'];
				if ($notesSelector == "contains")
					$query .= " AND notes RLIKE \"$notesName\"";
				elseif ($notesSelector == "does not contain")
					$query .= " AND notes NOT RLIKE \"$notesName\"";
				elseif ($notesSelector == "is equal to")
					$query .= " AND notes = \"$notesName\"";
				elseif ($notesSelector == "is not equal to")
					$query .= " AND notes != \"$notesName\"";
				elseif ($notesSelector == "starts with")
					$query .= " AND notes RLIKE \"^$notesName\"";
				elseif ($notesSelector == "ends with")
					$query .= " AND notes RLIKE \"$notesName$\"";
			}


		// ... if the user has specified some user keys, add the value of '$userKeysName' as an AND clause:
		$userKeysRadio = $_POST['userKeysRadio'];
		if ($userKeysRadio == "1")
		{
			$userKeysName = $_POST['userKeysName'];
			if ($userKeysName != "All" && $userKeysName != "")
				{
					$userKeysSelector = $_POST['userKeysSelector'];
					if ($userKeysSelector == "contains")
						$query .= " AND user_keys RLIKE \"$userKeysName\"";
					elseif ($userKeysSelector == "does not contain")
						$query .= " AND user_keys NOT RLIKE \"$userKeysName\"";
					elseif ($userKeysSelector == "is equal to")
						$query .= " AND user_keys = \"$userKeysName\"";
					elseif ($userKeysSelector == "is not equal to")
						$query .= " AND user_keys != \"$userKeysName\"";
					elseif ($userKeysSelector == "starts with")
						$query .= " AND user_keys RLIKE \"^$userKeysName\"";
					elseif ($userKeysSelector == "ends with")
						$query .= " AND user_keys RLIKE \"$userKeysName$\"";
				}
		}
		elseif ($userKeysRadio == "0")
		{
			$userKeysName2 = $_POST['userKeysName2'];
			if ($userKeysName2 != "")
				{
					$userKeysSelector2 = $_POST['userKeysSelector2'];
					if ($userKeysSelector2 == "contains")
						$query .= " AND user_keys RLIKE \"$userKeysName2\"";
					elseif ($userKeysSelector2 == "does not contain")
						$query .= " AND user_keys NOT RLIKE \"$userKeysName2\"";
					elseif ($userKeysSelector2 == "is equal to")
						$query .= " AND user_keys = \"$userKeysName2\"";
					elseif ($userKeysSelector2 == "is not equal to")
						$query .= " AND user_keys != \"$userKeysName2\"";
					elseif ($userKeysSelector2 == "starts with")
						$query .= " AND user_keys RLIKE \"^$userKeysName2\"";
					elseif ($userKeysSelector2 == "ends with")
						$query .= " AND user_keys RLIKE \"$userKeysName2$\"";
				}
		}

		// ... if the user has specified some user notes, add the value of '$userNotesName' as an AND clause:
		$userNotesName = $_POST['userNotesName'];
		if ($userNotesName != "")
			{
				$userNotesSelector = $_POST['userNotesSelector'];
				if ($userNotesSelector == "contains")
					$query .= " AND user_notes RLIKE \"$userNotesName\"";
				elseif ($userNotesSelector == "does not contain")
					$query .= " AND user_notes NOT RLIKE \"$userNotesName\"";
				elseif ($userNotesSelector == "is equal to")
					$query .= " AND user_notes = \"$userNotesName\"";
				elseif ($userNotesSelector == "is not equal to")
					$query .= " AND user_notes != \"$userNotesName\"";
				elseif ($userNotesSelector == "starts with")
					$query .= " AND user_notes RLIKE \"^$userNotesName\"";
				elseif ($userNotesSelector == "ends with")
					$query .= " AND user_notes RLIKE \"$userNotesName$\"";
			}

		// ... if the user has specified a user file, add the value of '$userFileName' as an AND clause:
		$userFileName = $_POST['userFileName'];
		if ($userFileName != "")
			{
				$userFileSelector = $_POST['userFileSelector'];
				if ($userFileSelector == "contains")
					$query .= " AND user_file RLIKE \"$userFileName\"";
				elseif ($userFileSelector == "does not contain")
					$query .= " AND user_file NOT RLIKE \"$userFileName\"";
				elseif ($userFileSelector == "is equal to")
					$query .= " AND user_file = \"$userFileName\"";
				elseif ($userFileSelector == "is not equal to")
					$query .= " AND user_file != \"$userFileName\"";
				elseif ($userFileSelector == "starts with")
					$query .= " AND user_file RLIKE \"^$userFileName\"";
				elseif ($userFileSelector == "ends with")
					$query .= " AND user_file RLIKE \"$userFileName$\"";
			}

		// ... if the user has specified a serial, add the value of '$serialNo' as an AND clause:
		$serialNo = $_POST['serialNo'];
		if ($serialNo != "")
			{
				$serialSelector = $_POST['serialSelector'];
				if ($serialSelector == "contains")
					$query .= " AND serial RLIKE \"$serialNo\"";
				elseif ($serialSelector == "does not contain")
					$query .= " AND serial NOT RLIKE \"$serialNo\"";
				elseif ($serialSelector == "is equal to")
					$query .= " AND serial = \"$serialNo\"";
				elseif ($serialSelector == "is not equal to")
					$query .= " AND serial != \"$serialNo\"";
				elseif ($serialSelector == "starts with")
					$query .= " AND serial RLIKE \"^$serialNo\"";
				elseif ($serialSelector == "ends with")
					$query .= " AND serial RLIKE \"$serialNo$\"";
				elseif ($serialSelector == "is greater than")
					$query .= " AND serial > \"$serialNo\"";
				elseif ($serialSelector == "is less than")
					$query .= " AND serial < \"$serialNo\"";
				elseif ($serialSelector == "is within list")
					{
						// replace any non-digit chars with "|":
						$serialNo = preg_replace("/\D+/", "|", $serialNo);
						// strip "|" from beginning/end of string (if any):
						$serialNo = preg_replace("/^\|?(.+?)\|?$/", "\\1", $serialNo);
						$query .= " AND serial RLIKE \"^($serialNo)$\"";
					}
			}

		// ... if the user has specified a type, add the value of '$typeName' as an AND clause:
		$typeRadio = $_POST['typeRadio'];
		if ($typeRadio == "1")
		{
			$typeName = $_POST['typeName'];
			if ($typeName != "All" && $typeName != "")
				{
					$typeSelector = $_POST['typeSelector'];
					if ($typeSelector == "contains")
						$query .= " AND type RLIKE \"$typeName\"";
					elseif ($typeSelector == "does not contain")
						$query .= " AND type NOT RLIKE \"$typeName\"";
					elseif ($typeSelector == "is equal to")
						$query .= " AND type = \"$typeName\"";
					elseif ($typeSelector == "is not equal to")
						$query .= " AND type != \"$typeName\"";
					elseif ($typeSelector == "starts with")
						$query .= " AND type RLIKE \"^$typeName\"";
					elseif ($typeSelector == "ends with")
						$query .= " AND type RLIKE \"$typeName$\"";
				}
		}
		elseif ($typeRadio == "0")
		{
			$typeName2 = $_POST['typeName2'];
			if ($typeName2 != "")
				{
					$typeSelector2 = $_POST['typeSelector2'];
					if ($typeSelector2 == "contains")
						$query .= " AND type RLIKE \"$typeName2\"";
					elseif ($typeSelector2 == "does not contain")
						$query .= " AND type NOT RLIKE \"$typeName2\"";
					elseif ($typeSelector2 == "is equal to")
						$query .= " AND type = \"$typeName2\"";
					elseif ($typeSelector2 == "is not equal to")
						$query .= " AND type != \"$typeName2\"";
					elseif ($typeSelector2 == "starts with")
						$query .= " AND type RLIKE \"^$typeName2\"";
					elseif ($typeSelector2 == "ends with")
						$query .= " AND type RLIKE \"$typeName2$\"";
				}
		}

		// ... if the user has selected a radio button for 'Marked', add the corresponding value for 'marked' as an AND clause:
		if (isset($_POST['markedRadio']))
		{
			$markedRadio = $_POST['markedRadio'];
			if ($markedRadio == "1")
				$query .= " AND marked = \"yes\"";		
			elseif ($markedRadio == "0")
				$query .= " AND marked = \"no\"";
		}

		// ... if the user has selected a radio button for 'Selected', add the corresponding value for 'selected' as an AND clause:
		if (isset($_POST['selectedRadio']))
		{
			$selectedRadio = $_POST['selectedRadio'];
			if ($selectedRadio == "1")
				$query .= " AND selected = \"yes\"";		
			elseif ($selectedRadio == "0")
				$query .= " AND selected = \"no\"";
		}

		// ... if the user has selected a radio button for 'Approved', add the corresponding value for 'approved' as an AND clause:
		if (isset($_POST['approvedRadio']))
		{
			$approvedRadio = $_POST['approvedRadio'];
			if ($approvedRadio == "1")
				$query .= " AND approved = \"yes\"";		
			elseif ($approvedRadio == "0")
				$query .= " AND approved = \"no\"";
		}

		// ... if the user has specified a created date, add the value of '$createdDateNo' as an AND clause:
		$createdDateNo = $_POST['createdDateNo'];
		if ($createdDateNo != "")
			{
				$createdDateSelector = $_POST['createdDateSelector'];
				if ($createdDateSelector == "contains")
					$query .= " AND created_date RLIKE \"$createdDateNo\"";
				elseif ($createdDateSelector == "does not contain")
					$query .= " AND created_date NOT RLIKE \"$createdDateNo\"";
				elseif ($createdDateSelector == "is equal to")
					$query .= " AND created_date = \"$createdDateNo\"";
				elseif ($createdDateSelector == "is not equal to")
					$query .= " AND created_date != \"$createdDateNo\"";
				elseif ($createdDateSelector == "starts with")
					$query .= " AND created_date RLIKE \"^$createdDateNo\"";
				elseif ($createdDateSelector == "ends with")
					$query .= " AND created_date RLIKE \"$createdDateNo$\"";
				elseif ($createdDateSelector == "is greater than")
					$query .= " AND created_date > \"$createdDateNo\"";
				elseif ($createdDateSelector == "is less than")
					$query .= " AND created_date < \"$createdDateNo\"";
			}

		// ... if the user has specified a created time, add the value of '$createdTimeNo' as an AND clause:
		$createdTimeNo = $_POST['createdTimeNo'];
		if ($createdTimeNo != "")
			{
				$createdTimeSelector = $_POST['createdTimeSelector'];
				if ($createdTimeSelector == "contains")
					$query .= " AND created_time RLIKE \"$createdTimeNo\"";
				elseif ($createdTimeSelector == "does not contain")
					$query .= " AND created_time NOT RLIKE \"$createdTimeNo\"";
				elseif ($createdTimeSelector == "is equal to")
					$query .= " AND created_time = \"$createdTimeNo\"";
				elseif ($createdTimeSelector == "is not equal to")
					$query .= " AND created_time != \"$createdTimeNo\"";
				elseif ($createdTimeSelector == "starts with")
					$query .= " AND created_time RLIKE \"^$createdTimeNo\"";
				elseif ($createdTimeSelector == "ends with")
					$query .= " AND created_time RLIKE \"$createdTimeNo$\"";
				elseif ($createdTimeSelector == "is greater than")
					$query .= " AND created_time > \"$createdTimeNo\"";
				elseif ($createdTimeSelector == "is less than")
					$query .= " AND created_time < \"$createdTimeNo\"";
			}

		// ... if the user has specified a created by, add the value of '$createdByName' as an AND clause:
		$createdByRadio = $_POST['createdByRadio'];
		if ($createdByRadio == "1")
		{
			$createdByName = $_POST['createdByName'];
			if ($createdByName != "All" && $createdByName != "")
				{
					$createdBySelector = $_POST['createdBySelector'];
					if ($createdBySelector == "contains")
						$query .= " AND created_by RLIKE \"$createdByName\"";
					elseif ($createdBySelector == "does not contain")
						$query .= " AND created_by NOT RLIKE \"$createdByName\"";
					elseif ($createdBySelector == "is equal to")
						$query .= " AND created_by = \"$createdByName\"";
					elseif ($createdBySelector == "is not equal to")
						$query .= " AND created_by != \"$createdByName\"";
					elseif ($createdBySelector == "starts with")
						$query .= " AND created_by RLIKE \"^$createdByName\"";
					elseif ($createdBySelector == "ends with")
						$query .= " AND created_by RLIKE \"$createdByName$\"";
				}
		}
		elseif ($createdByRadio == "0")
		{
			$createdByName2 = $_POST['createdByName2'];
			if ($createdByName2 != "")
				{
					$createdBySelector2 = $_POST['createdBySelector2'];
					if ($createdBySelector2 == "contains")
						$query .= " AND created_by RLIKE \"$createdByName2\"";
					elseif ($createdBySelector2 == "does not contain")
						$query .= " AND created_by NOT RLIKE \"$createdByName2\"";
					elseif ($createdBySelector2 == "is equal to")
						$query .= " AND created_by = \"$createdByName2\"";
					elseif ($createdBySelector2 == "is not equal to")
						$query .= " AND created_by != \"$createdByName2\"";
					elseif ($createdBySelector2 == "starts with")
						$query .= " AND created_by RLIKE \"^$createdByName2\"";
					elseif ($createdBySelector2 == "ends with")
						$query .= " AND created_by RLIKE \"$createdByName2$\"";
				}
		}

		// ... if the user has specified a modified date, add the value of '$modifiedDateNo' as an AND clause:
		$modifiedDateNo = $_POST['modifiedDateNo'];
		if ($modifiedDateNo != "")
			{
				$modifiedDateSelector = $_POST['modifiedDateSelector'];
				if ($modifiedDateSelector == "contains")
					$query .= " AND modified_date RLIKE \"$modifiedDateNo\"";
				elseif ($modifiedDateSelector == "does not contain")
					$query .= " AND modified_date NOT RLIKE \"$modifiedDateNo\"";
				elseif ($modifiedDateSelector == "is equal to")
					$query .= " AND modified_date = \"$modifiedDateNo\"";
				elseif ($modifiedDateSelector == "is not equal to")
					$query .= " AND modified_date != \"$modifiedDateNo\"";
				elseif ($modifiedDateSelector == "starts with")
					$query .= " AND modified_date RLIKE \"^$modifiedDateNo\"";
				elseif ($modifiedDateSelector == "ends with")
					$query .= " AND modified_date RLIKE \"$modifiedDateNo$\"";
				elseif ($modifiedDateSelector == "is greater than")
					$query .= " AND modified_date > \"$modifiedDateNo\"";
				elseif ($modifiedDateSelector == "is less than")
					$query .= " AND modified_date < \"$modifiedDateNo\"";
			}

		// ... if the user has specified a modified time, add the value of '$modifiedTimeNo' as an AND clause:
		$modifiedTimeNo = $_POST['modifiedTimeNo'];
		if ($modifiedTimeNo != "")
			{
				$modifiedTimeSelector = $_POST['modifiedTimeSelector'];
				if ($modifiedTimeSelector == "contains")
					$query .= " AND modified_time RLIKE \"$modifiedTimeNo\"";
				elseif ($modifiedTimeSelector == "does not contain")
					$query .= " AND modified_time NOT RLIKE \"$modifiedTimeNo\"";
				elseif ($modifiedTimeSelector == "is equal to")
					$query .= " AND modified_time = \"$modifiedTimeNo\"";
				elseif ($modifiedTimeSelector == "is not equal to")
					$query .= " AND modified_time != \"$modifiedTimeNo\"";
				elseif ($modifiedTimeSelector == "starts with")
					$query .= " AND modified_time RLIKE \"^$modifiedTimeNo\"";
				elseif ($modifiedTimeSelector == "ends with")
					$query .= " AND modified_time RLIKE \"$modifiedTimeNo$\"";
				elseif ($modifiedTimeSelector == "is greater than")
					$query .= " AND modified_time > \"$modifiedTimeNo\"";
				elseif ($modifiedTimeSelector == "is less than")
					$query .= " AND modified_time < \"$modifiedTimeNo\"";
			}

		// ... if the user has specified a modified by, add the value of '$modifiedByName' as an AND clause:
		$modifiedByRadio = $_POST['modifiedByRadio'];
		if ($modifiedByRadio == "1")
		{
			$modifiedByName = $_POST['modifiedByName'];
			if ($modifiedByName != "All" && $modifiedByName != "")
				{
					$modifiedBySelector = $_POST['modifiedBySelector'];
					if ($modifiedBySelector == "contains")
						$query .= " AND modified_by RLIKE \"$modifiedByName\"";
					elseif ($modifiedBySelector == "does not contain")
						$query .= " AND modified_by NOT RLIKE \"$modifiedByName\"";
					elseif ($modifiedBySelector == "is equal to")
						$query .= " AND modified_by = \"$modifiedByName\"";
					elseif ($modifiedBySelector == "is not equal to")
						$query .= " AND modified_by != \"$modifiedByName\"";
					elseif ($modifiedBySelector == "starts with")
						$query .= " AND modified_by RLIKE \"^$modifiedByName\"";
					elseif ($modifiedBySelector == "ends with")
						$query .= " AND modified_by RLIKE \"$modifiedByName$\"";
				}
		}
		elseif ($modifiedByRadio == "0")
		{
			$modifiedByName2 = $_POST['modifiedByName2'];
			if ($modifiedByName2 != "")
				{
					$modifiedBySelector2 = $_POST['modifiedBySelector2'];
					if ($modifiedBySelector2 == "contains")
						$query .= " AND modified_by RLIKE \"$modifiedByName2\"";
					elseif ($modifiedBySelector2 == "does not contain")
						$query .= " AND modified_by NOT RLIKE \"$modifiedByName2\"";
					elseif ($modifiedBySelector2 == "is equal to")
						$query .= " AND modified_by = \"$modifiedByName2\"";
					elseif ($modifiedBySelector2 == "is not equal to")
						$query .= " AND modified_by != \"$modifiedByName2\"";
					elseif ($modifiedBySelector2 == "starts with")
						$query .= " AND modified_by RLIKE \"^$modifiedByName2\"";
					elseif ($modifiedBySelector2 == "ends with")
						$query .= " AND modified_by RLIKE \"$modifiedByName2$\"";
				}
		}


		// Construct the ORDER BY clause:
		$query .= " ORDER BY ";

		// A) extract first level sort option:
		$sortSelector1 = $_POST['sortSelector1'];
		if ($sortSelector1 != "")
			{
				// when field name = 'pages' then sort by 'first_page' instead:
				$sortSelector1 = str_replace("pages", "first_page", $sortSelector1);
		
				$sortRadio1 = $_POST['sortRadio1'];
				if ($sortRadio1 == "0") // sort ascending
					$query .= "$sortSelector1";
				else // sort descending
					$query .= "$sortSelector1 DESC";
			}

		// B) extract second level sort option:
		$sortSelector2 = $_POST['sortSelector2'];
		if ($sortSelector2 != "")
			{
				// when field name = 'pages' then sort by 'first_page' instead:
				$sortSelector2 = str_replace("pages", "first_page", $sortSelector2);
		
				$sortRadio2 = $_POST['sortRadio2'];
				if ($sortRadio2 == "0") // sort ascending
					$query .= ", $sortSelector2";
				else // sort descending
					$query .= ", $sortSelector2 DESC";
			}

		// C) extract third level sort option:
		$sortSelector3 = $_POST['sortSelector3'];
		if ($sortSelector3 != "")
			{
				// when field name = 'pages' then sort by 'first_page' instead:
				$sortSelector3 = str_replace("pages", "first_page", $sortSelector3);
		
				$sortRadio3 = $_POST['sortRadio3'];
				if ($sortRadio3 == "0") // sort ascending
					$query .= ", $sortSelector3";
				else // sort descending
					$query .= ", $sortSelector3 DESC";
			}

		// Since the sort popup menus use empty fields as delimiters between groups of fields
		// we'll have to trap the case that the user hasn't chosen any field names for sorting:
		if (ereg("ORDER BY $", $query))
			$query .= "author, year DESC, publication"; // use the default ORDER BY clause

		// Finally, fix the wrong syntax where its says "ORDER BY, author, title, ..." instead of "ORDER BY author, title, ...":
		$query = str_replace("ORDER BY , ","ORDER BY ",$query);


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Search within Results" or "Display Options" forms above the query results list (which, in turn, was returned by 'search.php'):
	function extractFormElementsRefineDisplay($displayType, $sqlQuery, $showLinks, $userID)
	{
		$query = $sqlQuery;

		if ($displayType == "Search") // the user clicked the 'Search' button of the "Search within Results" form
		{
			$fieldSelector = $_POST['refineSearchSelector']; // extract field name chosen by the user
			$refineSearchName = $_POST['refineSearchName']; // extract search text entered by the user

			if (isset($_POST['refineSearchExclude'])) // extract user option whether matched records should be included or excluded
				$refineSearchActionCheckbox = $_POST['refineSearchExclude']; // the user marked the checkbox next to "Exclude matches"
			else
				$refineSearchActionCheckbox = "0"; // the user did NOT mark the checkbox next to "Exclude matches"

			if ($refineSearchName != "") // if the user typed a search string into the text entry field...
			{
				// Depending on the chosen output action, construct an appropriate SQL query:
				if ($refineSearchActionCheckbox == "0") // if the user did NOT mark the checkbox next to "Exclude matches"
					{
						// for the fields 'marked=no', 'copy=false' and 'selected=no', force NULL values to be matched:
						if (($fieldSelector == "marked" AND $refineSearchName == "no") OR ($fieldSelector == "copy" AND $refineSearchName == "false") OR ($fieldSelector == "selected" AND $refineSearchName == "no"))
							$query = str_replace("WHERE","WHERE ($fieldSelector RLIKE \"$refineSearchName\" OR $fieldSelector IS NULL) AND",$query); // ...add search field name & value to the sql query
						else // add default 'WHERE' clause:
							$query = str_replace("WHERE","WHERE $fieldSelector RLIKE \"$refineSearchName\" AND",$query); // ...add search field name & value to the sql query
					}
				else // $refineSearchActionCheckbox == "1" // if the user marked the checkbox next to "Exclude matches"
					{
						// for the fields 'marked=yes', 'copy!=false' and 'selected=yes', force NULL values to be excluded:
						if (($fieldSelector == "marked" AND $refineSearchName == "yes") OR ($fieldSelector == "copy" AND $refineSearchName != "false") OR ($fieldSelector == "selected" AND $refineSearchName == "yes"))
							$query = str_replace("WHERE","WHERE ($fieldSelector NOT RLIKE \"$refineSearchName\" OR $fieldSelector IS NULL) AND",$query); // ...add search field name & value to the sql query
						else // add default 'WHERE' clause:
							$query = str_replace("WHERE","WHERE $fieldSelector NOT RLIKE \"$refineSearchName\" AND",$query); // ...add search field name & value to the sql query
					}
				$query = str_replace(' AND serial RLIKE ".+"','',$query); // remove any 'AND serial RLIKE ".+"' which isn't required anymore
			}
			// else, if the user did NOT type a search string into the text entry field, we simply keep the old WHERE clause...
		}


		elseif ($displayType == "Show" OR $displayType == "Hide") // the user clicked either the 'Show' or the 'Hide' button of the "Display Options" form
		// (hitting <enter> within the 'ShowRows' text entry field of the "Display Options" form will act as if the user clicked the 'Show' button)
		{
			$fieldSelector = $_POST['displayOptionsSelector']; // extract field name chosen by the user

			if ($displayType == "Show") // if the user clicked the 'Show' button...
				{
					if (!preg_match("/SELECT.*\W$fieldSelector\W.*FROM refs/", $query)) // ...and the field is *not* already displayed...
						$query = str_replace(" FROM refs",", $fieldSelector FROM refs",$query); // ...then SHOW the field that was used for refining the search results
				}
			elseif ($displayType == "Hide") // if the user clicked the 'Hide' button...
				{
					if (preg_match("/SELECT.*\W$fieldSelector\W.*FROM refs/", $query)) // ...and the field *is* currently displayed...
					{
						// for all columns except the first:
						$query = preg_replace("/(SELECT.+?), $fieldSelector( .*FROM refs)/","\\1\\2",$query); // ...then HIDE the field that was used for refining the search results
						// for all columns except the last:
						$query = preg_replace("/(SELECT.*? )$fieldSelector, (.+FROM refs)/","\\1\\2",$query); // ...then HIDE the field that was used for refining the search results
					}
				}
		}


		// the following changes to the SQL query are performed for both forms ("Search within Results" and "Display Options"):

		// if the chosen field is one of the user specific fields from table 'user_data': 'marked', 'copy', 'selected', 'user_keys', 'user_notes', 'user_file', 'user_groups', 'bibtex_id' or 'related'
		if (ereg("^(marked|copy|selected|user_keys|user_notes|user_file|user_groups|bibtex_id|related)$", $fieldSelector))
			if (ereg("LEFT JOIN user_data", $query) != true) // ...and if the 'LEFT JOIN...' statement isn't already part of the 'FROM' clause...
				$query = str_replace(" FROM refs"," FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = $userID",$query); // ...add the 'LEFT JOIN...' part to the 'FROM' clause

		$query = str_replace(' FROM refs',', orig_record FROM refs',$query); // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
																				// (which is required in order to present visual feedback on duplicate records)

		$query = str_replace(' FROM refs',', serial FROM refs',$query); // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
																		// (which is required in order to obtain unique checkbox names)

		if ($showLinks == "1")
			$query = str_replace(' FROM refs',', file, url, doi FROM refs',$query); // add 'file', 'url' & 'doi' columns


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from records selected by the user within the query results list (which, in turn, was returned by 'search.php'):
	function extractFormElementsQueryResults($displayType, $showLinks, $citeOrder, $orderBy, $userID, $sqlQuery, $referer, $recordSerialsArray)
	{
		if (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions']))) // if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_groups', show form elements to add/remove the selected records to/from a user's group:
		{
			$userGroupActionRadio = $_POST['userGroupActionRadio']; // extract user option whether we're supposed to process an existing group name or any custom/new group name that was specified by the user

			// Extract the chosen user group from the request:
			// first, we need to check whether the user did choose an existing group name from the popup menu
			// -OR- if he/she did enter a custom group name in the text entry field:
			if ($userGroupActionRadio == "1") // if the user checked the radio button next to the group popup menu ('userGroupSelector') [this is the default]
			{
				if (isset($_POST['userGroupSelector']))
					$userGroup = $_POST['userGroupSelector']; // extract the value of the 'userGroupSelector' popup menu
				else
					$userGroup = "";
			}
			else // $userGroupActionRadio == "0" // if the user checked the radio button next to the group text entry field ('userGroupName')
			{
				if (isset($_POST['userGroupName']))
					$userGroup = $_POST['userGroupName']; // extract the value of the 'userGroupName' text entry field
				else
					$userGroup = "";
			}
		}

			
		// join array elements:
		if (!empty($recordSerialsArray)) // the user did check some checkboxes
			$recordSerialsString = implode("|", $recordSerialsArray); // separate record serials by "|" in order to facilitate regex querying...
		else // the user didn't check any checkboxes
			$recordSerialsString = "0"; // we use '0' which definitely doesn't exist as serial, resulting in a "nothing found" feedback


		// Depending on the chosen output format, construct an appropriate SQL query:
		if ($displayType == "Cite")
			{
			// Note: since we won't query any user specific fields (like 'marked', 'copy', 'selected', 'user_keys', 'user_notes', 'user_file', 'user_groups', 'bibtex_id' or 'related') we skip the 'LEFT JOIN...' part of the 'FROM' clause:
			if ($citeOrder == "year") // sort records first by year (descending), then in the usual way:
				$query = "SELECT type, author, year, title, publication, abbrev_journal, volume, issue, pages, thesis, editor, publisher, place, abbrev_series_title, series_title, series_editor, series_volume, series_issue, language, author_count, online_publication, online_citation, doi, serial FROM refs WHERE serial RLIKE \"^(" . $recordSerialsString . ")$\" ORDER BY year DESC, first_author, author_count, author, title";
			else // if any other or no '$citeOrder' parameter is specified, we supply the default ORDER BY pattern (which is suitable for citation in a journal etc.):
				$query = "SELECT type, author, year, title, publication, abbrev_journal, volume, issue, pages, thesis, editor, publisher, place, abbrev_series_title, series_title, series_editor, series_volume, series_issue, language, author_count, online_publication, online_citation, doi, serial FROM refs WHERE serial RLIKE \"^(" . $recordSerialsString . ")$\" ORDER BY first_author, author_count, author, year, title";
			}
		elseif ($displayType == "Display") // (hitting <enter> within the 'ShowRows' text entry field will act as if the user clicked the 'Display' button)
			{
				// for the selected records, select *all* available fields:
				// (note: we also add the 'serial' column at the end in order to provide standardized input [compare processing of form 'sql_search.php'])
				if (isset($_SESSION['loginEmail'])) // if a user is logged in...
					$query = "SELECT author, title, type, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved, location, call_number, serial, marked, copy, selected, user_keys, user_notes, user_file, user_groups, bibtex_id, related, orig_record, serial";
				else // NO user logged in
					$query = "SELECT author, title, type, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved, location, call_number, serial, orig_record, serial";

				if ($showLinks == "1")
					$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

				if (isset($_SESSION['loginEmail'])) // if a user is logged in...
					$query .= " FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = " . $userID . " WHERE serial RLIKE \"^(" . $recordSerialsString . ")$\" ORDER BY $orderBy";
				else // NO user logged in
					$query .= " FROM refs WHERE serial RLIKE \"^(" . $recordSerialsString . ")$\" ORDER BY $orderBy";
			}
		elseif (isset($_SESSION['loginEmail']) AND ereg("^(Remember|Add|Remove)$", $displayType)) // if a user (who's logged in) clicked the 'Remember', 'Add' or 'Remove' button...
			{
				if ($displayType == "Remember") // the user clicked the 'Remember' button
					if (!empty($recordSerialsArray)) // the user did check some checkboxes
						// save the the serials of all selected records to a session variable:
						saveSessionVariable("selectedRecords", $recordSerialsArray); // function 'saveSessionVariable()' is defined in 'include.inc.php'

				if (ereg("^(Add|Remove)$", $displayType) AND !empty($userGroup)) // the user clicked either the 'Add' or the 'Remove' button
					modifyUserGroups($displayType, $recordSerialsArray, $recordSerialsString, $userID, $userGroup, $userGroupActionRadio); // add (remove) selected records to (from) the specified user group  (function 'modifyUserGroups()' is defined above!)


				// re-apply the current sqlQuery:
				$query = str_replace(' FROM refs',', orig_record FROM refs',$sqlQuery); // add 'orig_record' column (which is required in order to present visual feedback on duplicate records)
				$query = str_replace(' FROM refs',', serial FROM refs',$query); // add 'serial' column (which is required in order to obtain unique checkbox names)
		
				if ($showLinks == "1")
					$query = str_replace(' FROM refs',', file, url, doi FROM refs',$query); // add 'file', 'url' & 'doi' columns
			}

		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the 'extract.php' form:
	function extractFormElementsExtract($citeOrder)
	{
		// Extract form elements (that are unique to the 'extract.php' form):
		$sourceText = $_POST['sourceText']; // get the source text that contains the record serial numbers
		$startDelim = $_POST['startDelim']; // get the start delimiter that precedes record serial numbers
		$endDelim = $_POST['endDelim']; // get the end delimiter that follows record serial numbers
		
		$startDelim = preg_quote($startDelim); // escape any potential meta-characters
		$endDelim = preg_quote($endDelim); // escape any potential meta-characters

		// Extract record serial numbers from source text:
		$recordSerialsString = preg_replace("/(?<=^).*?(?=$startDelim\d+$endDelim|$)/s", "", $sourceText); // remove any text preceding the first serial number
		$recordSerialsString = preg_replace("/$startDelim(\d+)$endDelim.*?(?=$startDelim\d+$endDelim|$)/s", "\\1|", $recordSerialsString); // replace any text between serial numbers (or between a serial number and the end of the text) with "|"; additionally, remove the delimiters enclosing the serial numbers
		$recordSerialsString = preg_replace("/\D+$/s", "", $recordSerialsString); // remove any trailing non-digit chars (like \n or "|") at end of line

		// Construct the SQL query:
		// Note: since we won't query any user specific fields (like 'marked', 'copy', 'selected', 'user_keys', 'user_notes', 'user_file', 'user_groups', 'bibtex_id' or 'related') we skip the 'LEFT JOIN...' part of the 'FROM' clause:
		if ($citeOrder == "year") // sort records first by year (descending), then in the usual way:
			$query = "SELECT type, author, year, title, publication, abbrev_journal, volume, issue, pages, thesis, editor, publisher, place, abbrev_series_title, series_title, series_editor, series_volume, series_issue, language, author_count, online_publication, online_citation, doi, serial FROM refs WHERE serial RLIKE \"^(" . $recordSerialsString . ")$\" ORDER BY year DESC, first_author, author_count, author, title";
		else // if any other or no '$citeOrder' parameter is specified, we supply the default ORDER BY pattern (which is suitable for citation in a journal etc.):
			$query = "SELECT type, author, year, title, publication, abbrev_journal, volume, issue, pages, thesis, editor, publisher, place, abbrev_series_title, series_title, series_editor, series_volume, series_issue, language, author_count, online_publication, online_citation, doi, serial FROM refs WHERE serial RLIKE \"^(" . $recordSerialsString . ")$\" ORDER BY first_author, author_count, author, year, title";


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Quick Search" form on the main page ('index.php'):
	function extractFormElementsQuick($showLinks)
	{
		$query = "SELECT author, title, year, publication";

		$quickSearchSelector = $_POST['quickSearchSelector']; // extract field name chosen by the user
		$quickSearchName = $_POST['quickSearchName']; // extract search text entered by the user

		// if the SELECT string doesn't already contain the chosen field name...
		// (which is only the case for 'keywords' & 'abstract')
		if (!ereg("$quickSearchSelector", $query))
			$query .= ", $quickSearchSelector"; // ...add chosen field to SELECT query
		else
			$query .= ", volume, pages"; // ...otherwise, add further default columns

		$query .= ", orig_record"; // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
								//  (which is required in order to present visual feedback on duplicate records)

		$query .= ", serial"; // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
							//  (which is required in order to obtain unique checkbox names)

		if ($showLinks == "1")
			$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

		// Note: since we won't query any user specific fields (like 'marked', 'copy', 'selected', 'user_keys', 'user_notes', 'user_file', 'user_groups', 'bibtex_id' or 'related') we skip the 'LEFT JOIN...' part of the 'FROM' clause:
		$query .= " FROM refs WHERE serial RLIKE \".+\""; // add FROM & (initial) WHERE clause
		
		if ($quickSearchName != "") // if the user typed a search string into the text entry field...
			$query .= " AND $quickSearchSelector RLIKE \"$quickSearchName\""; // ...add search field name & value to the sql query

		$query .= " ORDER BY author, year DESC, publication"; // add the default ORDER BY clause


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Show My Group" form on the main page ('index.php') or above the query results list (that was produced by 'search.php'):
	function extractFormElementsGroup($sqlQuery, $showLinks, $userID)
	{
		if (!empty($sqlQuery)) // if there's a previous SQL query available (as is the case if the group search originated from a search results page - and not from the main page 'index.php')
			$query = preg_replace("/(SELECT .+?) FROM refs.+/", "\\1", $sqlQuery); // use the custom set of colums chosen by the user
		else
			$query = "SELECT author, title, year, publication, volume, pages, user_groups"; // use the default SELECT statement

		$groupSearchSelector = $_POST['groupSearchSelector']; // extract the user group chosen by the user

		$query .= ", orig_record"; // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
								//  (which is required in order to present visual feedback on duplicate records)

		$query .= ", serial"; // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
							//  (which is required in order to obtain unique checkbox names)

		if ($showLinks == "1")
			$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

		$query .= " FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = " . $userID; // add FROM clause

		$query .= " WHERE user_groups RLIKE \"(^|.*;) *$groupSearchSelector *(;.*|$)\""; // add WHERE clause

		$query .= " ORDER BY author, year DESC, publication"; // add the default ORDER BY clause


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Show My Refs" form on the
	// main page ('index.php') which searches the user specific fields from table 'user_data':
	// Note: Although the "Show My Refs" form on 'index.php' is of method="POST" we do accept
	//       GET queries as well in order to allow for the 'My Refs' links provided by the
	//       'showLogin()' function (from 'include.inc.php').
	function extractFormElementsMyRefs($showLinks, $loginEmail, $userID)
	{
		$query = "SELECT author, title, year, publication, volume, pages";

		$myRefsRadio = $_REQUEST['myRefsRadio']; // will be "1" if the user wants to display ALL of his records, otherwise it will be "0"

		// extract form popup 'marked/not marked':
		if (isset($_REQUEST['findMarked']))
			$findMarked = $_REQUEST['findMarked']; // will be "1" if the user wants to search the 'marked' field
		else
			$findMarked = "";

		if (isset($_REQUEST['markedSelector']))
			$markedSelector = $_REQUEST['markedSelector']; // extract 'marked' field value chosen by the user
		else
			$markedSelector = "";

		// extract form popup 'selected/not selected':
		if (isset($_REQUEST['findSelected']))
			$findSelected = $_REQUEST['findSelected']; // will be "1" if the user wants to search the 'selected' field
		else
			$findSelected = "";

		if (isset($_REQUEST['selectedSelector']))
			$selectedSelector = $_REQUEST['selectedSelector']; // extract 'selected' field value chosen by the user
		else
			$selectedSelector = "";

		// extract form popup 'copy = true/fetch/ordered/false':
		if (isset($_REQUEST['findCopy']))
			$findCopy = $_REQUEST['findCopy']; // will be "1" if the user wants to search the 'copy' field
		else
			$findCopy = "";

		if (isset($_REQUEST['copySelector']))
			$copySelector = $_REQUEST['copySelector']; // extract 'copy' field value chosen by the user
		else
			$copySelector = "";

		// extract form text entry field 'key':
		if (isset($_REQUEST['findUserKeys']))
			$findUserKeys = $_REQUEST['findUserKeys']; // will be "1" if the user wants to search the 'user_keys' field
		else
			$findUserKeys = "";

		if (isset($_REQUEST['userKeysName']))
			$userKeysName = $_REQUEST['userKeysName']; // extract user keys entered by the user
		else
			$userKeysName = "";

		// extract form text entry field 'note':
		if (isset($_REQUEST['findUserNotes']))
			$findUserNotes = $_REQUEST['findUserNotes']; // will be "1" if the user wants to search the 'user_notes' field
		else
			$findUserNotes = "";

		if (isset($_REQUEST['userNotesName']))
			$userNotesName = $_REQUEST['userNotesName']; // extract user notes entered by the user
		else
			$userNotesName = "";

		// extract form text entry field 'file':
		if (isset($_REQUEST['findUserFile']))
			$findUserFile = $_REQUEST['findUserFile']; // will be "1" if the user wants to search the 'user_file' field
		else
			$findUserFile = "";

		if (isset($_REQUEST['userFileName']))
			$userFileName = $_REQUEST['userFileName']; // extract file specification entered by the user
		else
			$userFileName = "";

		if ($myRefsRadio == "0") // if the user only wants to display a subset of his records:
			{
				if ($findMarked == "1") // if the user wants to search the 'marked' field...
					$query .= ", marked"; // ...add 'marked' field to SELECT query
		
				if ($findSelected == "1") // if the user wants to search the 'selected' field...
					$query .= ", selected"; // ...add 'selected' field to SELECT query
		
				if ($findCopy == "1") // if the user wants to search the 'copy' field...
					$query .= ", copy"; // ...add 'copy' field to SELECT query
		
				if ($findUserKeys == "1") // if the user wants to search the 'user_keys' field...
					$query .= ", user_keys"; // ...add 'user_keys' to SELECT query
		
				if ($findUserNotes == "1") // if the user wants to search the 'user_notes' field...
					$query .= ", user_notes"; // ...add 'user_notes' to SELECT query

				if ($findUserFile == "1") // if the user wants to search the 'user_file' field...
					$query .= ", user_file"; // ...add 'user_file' to SELECT query
			}

		$query .= ", orig_record"; // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
								//  (which is required in order to present visual feedback on duplicate records)

		$query .= ", serial"; // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
							//  (which is required in order to obtain unique checkbox names)

		if ($showLinks == "1")
			$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

		$query .= " FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = " . $userID . " WHERE location RLIKE \"$loginEmail\""; // add FROM & (initial) WHERE clause
		

		if ($myRefsRadio == "0") // if the user only wants to display a subset of his records:
			{
				if ($findMarked == "1") // if the user wants to search the 'marked' field...
					{
						if ($markedSelector == "marked")
							$query .= " AND marked = \"yes\""; // ...add 'marked' field name & value to the sql query
						else // $markedSelector == "not marked" (i.e., 'marked' is either 'no' -or- NULL)
							$query .= " AND (marked = \"no\" OR marked IS NULL)"; // ...add 'marked' field name & value to the sql query
					}

				if ($findSelected == "1") // if the user wants to search the 'selected' field...
					{
						if ($selectedSelector == "selected")
							$query .= " AND selected = \"yes\""; // ...add 'selected' field name & value to the sql query
						else // $selectedSelector == "not selected" (i.e., 'selected' is either 'no' -or- NULL)
							$query .= " AND (selected = \"no\" OR selected IS NULL)"; // ...add 'selected' field name & value to the sql query
					}

				if ($findCopy == "1") // if the user wants to search the 'copy' field...
					{
						if ($copySelector == "true")
							$query .= " AND copy = \"true\""; // ...add 'copy' field name & value to the sql query
						elseif ($copySelector == "ordered")
							$query .= " AND copy = \"ordered\""; // ...add 'copy' field name & value to the sql query
						elseif ($copySelector == "fetch")
							$query .= " AND copy = \"fetch\""; // ...add 'copy' field name & value to the sql query
						else // 'copy' is either 'false' -or- NULL
							$query .= " AND (copy = \"false\" OR copy IS NULL)"; // ...add 'copy' field name & value to the sql query
					}

				if ($findUserKeys == "1") // if the user wants to search the 'user_keys' field...
					if ($userKeysName != "") // if the user typed a search string into the text entry field...
						$query .= " AND user_keys RLIKE \"$userKeysName\""; // ...add 'user_keys' field name & value to the sql query
		
				if ($findUserNotes == "1") // if the user wants to search the 'user_notes' field...
					if ($userNotesName != "") // if the user typed a search string into the text entry field...
						$query .= " AND user_notes RLIKE \"$userNotesName\""; // ...add 'user_notes' field name & value to the sql query

				if ($findUserFile == "1") // if the user wants to search the 'user_file' field...
					if ($userFileName != "") // if the user typed a search string into the text entry field...
						$query .= " AND user_file RLIKE \"$userFileName\""; // ...add 'user_file' field name & value to the sql query
			}


		$query .= " ORDER BY author, year DESC, publication"; // add the default ORDER BY clause


		return $query;
	}

	// --------------------------------------------------------------------

	// NOTHING FOUND
	// informs the user that no results were found for the current query/action
	function nothingFound($nothingChecked)
	{
		$nothingFoundFeedback = "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the database results for your query\">";

		if ($nothingChecked == true)
			// Inform the user that no records were selected:
			$nothingFoundFeedback .= "\n<tr>\n\t<td valign=\"top\">No records selected! Please select one or more records by clicking the appropriate checkboxes.&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>\n</tr>";
		else // $nothingChecked == false (i.e., the user did check some checkboxes) -OR- the query resulted from another script like 'show.php' (which has no checkboxes to mark!)
			// Report that nothing was found:
			$nothingFoundFeedback .= "\n<tr>\n\t<td valign=\"top\">Sorry, but your query didn't produce any results!&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>\n</tr>";

		$nothingFoundFeedback .= "\n</table>";
		

		return $nothingFoundFeedback;
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc.php')
	if ($viewType != "Print") // Note: we ommit the footer in print view! ('viewType=Print')
		displayfooter($oldQuery);

	// --------------------------------------------------------------------
?>

</body>
</html>	 
