<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./search.php
	// Created:    30-Jul-02, 17:40
	// Modified:   02-Dec-04, 13:46

	// This is the main script that handles the search query and displays the query results.
	// Supports three different output styles: 1) List view, with fully configurable columns -> displayColumns() function
	// 2) Details view, shows all fields -> displayRows() function; 3) Export view, citation style -> exportRows() function

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password
	include 'header.inc'; // include header
	include 'footer.inc'; // include footer
	include 'include.inc'; // include common functions
	include "ini.inc.php"; // include common variables

	// --------------------------------------------------------------------

	// Connect to a session
	session_start();

	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!

	// --------------------------------------------------------------------

	// EXTRACT FORM VARIABLES

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// Extract the form used for searching:
	$formType = $_REQUEST['formType'];
	
	// Extract the type of display requested by the user (either 'Display', 'Export' or ''):
	// ('' will produce the default columnar output style)
	if (isset($_REQUEST['submit']))
		$displayType = $_REQUEST['submit'];
	else
		$displayType = "";

	// Extract other variables from the request:
	if (isset($_REQUEST['sqlQuery']))
		$sqlQuery = $_REQUEST['sqlQuery'];
	else
		$sqlQuery = "";

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
		$rowOffset = $_REQUEST['rowOffset'];
	else
		$rowOffset = 0;

	// In order to generalize routines we have to query further variables here:
	if (isset($_REQUEST['exportFormatSelector']))
		$exportFormat = $_REQUEST['exportFormatSelector']; // get the export format chosen by the user (only occurs in 'extract.php' form and in query result lists)
	else
		$exportFormat = "";
	if (ereg("%20", $exportFormat)) // if '$exportFormat' still contains URL encoded data... ('%20' is the URL encoded form of a space, see note below!)
		$exportFormat = rawurldecode($exportFormat); // ...URL decode 'exportFormat' statement (it was URL encoded before incorporation into a hidden tag of the 'sqlSearch' form to avoid any HTML syntax errors)
													// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
													//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

	if (isset($_REQUEST['exportOrder']))
		$exportOrder = $_REQUEST['exportOrder']; // get information how the data should be sorted (only occurs in 'extract.php'/'sql_search' forms and in query result lists). If this param is set to 'Year', records will be listed in blocks sorted by year.
	else
		$exportOrder = "";

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

	// get the referring URL (if any):
	if (isset($HTTP_REFERER))
		$referer = $HTTP_REFERER;
	else // as an example, 'HTTP_REFERER' won't be set if a user clicked on a URL of type '.../show.php?record=12345' within an email announcement
		$referer = ""; // if there's no HTTP referer available we provide the empty string here
	
	// check if the user did mark any checkboxes (and set up variables accordingly, they will be used within the 'displayRows()' and 'exportRows()' functions)
	if (ereg(".+search.php", $referer) AND empty($recordSerialsArray)) // no checkboxes were marked
		$nothingChecked = true;
	else // some checkboxes were marked -OR- the query resulted from another script like 'show.php' (which has no checkboxes to mark!)
		$nothingChecked = false;

	// --------------------------------------------------------------------
	
	// VERIFY SQL QUERY:

	// For a normal user we only allow the use of SELECT queries (the admin is allowed to do everything that is allowed by his GRANT privileges):
	// NOTE: This does only provide for minimal security!
	//		 To avoid further security risks you should grant the mysql user (who's specified in 'db.inc') only those
	//		 permissions that are required to access the literature database. This can be done by use of a GRANT statement:
	//		 GRANT SELECT,INSERT,UPDATE,DELETE ON MYSQL_DATABASE_NAME_GOES_HERE.* TO MYSQL_USER_NAME_GOES_HERE@localhost IDENTIFIED BY 'MYSQL_PASSWORD_GOES_HERE';

	// if the sql query isn't build from scratch but is accepted from user input (which is the case for the forms 'sqlSearch' and 'refineSearch'):
	if (ereg("(sql|refine)Search", $formType)) // the user used 'sql_search.php' -OR- the "Search within Results" form above the query results list (that was produced by 'search.php')
	{
		if (((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail))) AND (!ereg("^SELECT", $sqlQuery))) // if (the user isn't logged in -OR- any normal user is logged in) -AND- he/she did use anything other than a SELECT query
		{
			session_register("HeaderString"); // save an appropriate error message
			$HeaderString = "<b><span class=\"warning\">You're only permitted to execute SELECT queries!</span></b>";
			if (ereg(".+sql_search.php", $referer)) // if the sql query was entered in the form provided by 'sql_search.php'
				header("Location: $referer"); // relocate back to the calling page
			else // if the user didn't come from 'sql_search.php' (e.g., if he attempted to hack parameters of a GET query directly)
				header("Location: index.php"); // relocate back to the main page		
			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE

	// (1) OPEN the database connection:
	//      (variables are set by include file 'db.inc'!)
	if (!($connection = @ mysql_connect($hostName, $username, $password)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to connect to the host:", $oldQuery);

	// (2) SELECT the database:
	//      (variables are set by include file 'db.inc'!)
	if (!(mysql_select_db($databaseName, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to connect to the database:", $oldQuery);

	// --------------------------------------------------------------------

	if (isset($HTTP_POST_VARS["loginEmail"]))
		$loginEmail = $HTTP_POST_VARS["loginEmail"]; // extract the email address of the currently logged in user

	if (session_is_registered("loginEmail")) // if a user is logged in...
		$userID = getUserID($loginEmail, $connection); // ...get the user's 'user_id' using his/her 'loginEmail' (function 'getUserID()' is defined in 'include.inc')
	else
		$userID = 0; // set variable to zero (a user with '$userID = 0' definitely doesn't exist) in order to prevent 'Undefined variable...' messages

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY from user input provided by any of the search forms:

	// --- Form 'sql_search.php': ------------------
	if ("$formType" == "sqlSearch") // the user used the 'sql_search.php' form for searching...
		{
			// handle the display & querying of user specific fields:
			if (!session_is_registered("loginEmail")) // if NO user is logged in...
			{
				if (ereg(", (marked|copy|selected|user_keys|user_notes|user_file)", $sqlQuery)) // ... and any user specific fields are part of the SELECT statement...
				{
					$sqlQuery = ereg_replace(", (marked|copy|selected|user_keys|user_notes|user_file)","",$sqlQuery); // ...delete any user specific fields from 'SELECT' clause
					session_register("HeaderString"); // save an appropriate error message
					$HeaderString = "<b><span class=\"warning\">Display of user specific fields was ommitted!</span></b>";
				}

				if ((ereg(".+sql_search.php", $referer)) AND (ereg("WHERE.+(marked|copy|selected|user_keys|user_notes|user_file)",$sqlQuery))) // if a user who's NOT logged in tries to query user specific fields (by use of 'sql_search.php')...
				// Note that the script 'show.php' may query the user specific field 'selected' (e.g., by URLs of the form: 'show.php?author=...&userID=...&only=selected')
				// but since (in that case) the '$referer' variable is either empty or does not end with 'sql_search.php' this if clause will not apply (which is ok since we want to allow 'show.php' to query the 'selected' field)
				{
					$sqlQuery = ereg_replace("FROM refs LEFT JOIN.+WHERE","FROM refs WHERE",$sqlQuery); // ...delete 'LEFT JOIN...' part from 'FROM' clause
					$sqlQuery = preg_replace("/WHERE (marked|copy|selected|user_keys|user_notes|user_file).+?(?= AND| ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/","WHERE",$sqlQuery); // ...delete any user specific fields from 'WHERE' clause
					$sqlQuery = preg_replace("/( AND)? (marked|copy|selected|user_keys|user_notes|user_file).+?(?= AND| ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/","",$sqlQuery); // ...delete any user specific fields from 'WHERE' clause
					$sqlQuery = ereg_replace("WHERE AND","WHERE",$sqlQuery); // ...delete any superfluous 'AND' that wasn't removed properly by the two regex patterns above
					$sqlQuery = preg_replace("/WHERE(?= ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/","WHERE serial RLIKE \".+\"",$sqlQuery); // ...supply generic 'WHERE' clause if it did ONLY contain user specific fields
					session_register("HeaderString"); // save an appropriate error message
					$HeaderString = "<b><span class=\"warning\">Querying of user specific fields was ommitted!</span></b>"; // save an appropriate error message
				}

			}
			else // if a user is logged in...
			{
				if (ereg("LEFT JOIN user_data",$sqlQuery)) // if the 'LEFT JOIN...' statement is part of the 'FROM' clause...
				{
					// ...and any user specific fields other(!) than just the 'selected' field are part of the 'SELECT' or 'WHERE' clause...
					// Note that we exclude the 'selected' field here (although it is user specific). By that we allow the 'selected' field to be queried by every user that's logged in.
					// This is done to support the 'show.php' script which may query the user specific field 'selected' (e.g., by URLs of the form: 'show.php?author=...&userID=...&only=selected')
					if (ereg(", (marked|copy|user_keys|user_notes|user_file)", $sqlQuery) OR ereg("WHERE.+(marked|copy|user_keys|user_notes|user_file)", $sqlQuery))
					{
						$sqlQuery = ereg_replace("user_id *= *[0-9]+","user_id = $userID",$sqlQuery); // ...replace any other user ID with the ID of the currently logged in user
						$sqlQuery = ereg_replace("location RLIKE [^ ]+","location RLIKE \"$loginEmail\"",$sqlQuery); // ...replace any other user email address with the login email address of the currently logged in user
					}
				}

				// if we're going to display record details for a logged in user, we have to ensure the display of user specific fields (which may have been deleted from a query due to a previous logout action);
				// in 'Display Details' view, the 'call_number' and 'serial' fields are the last generic fields before any user specific fields:
				if (($displayType == "Display") AND (ereg(", call_number, serial FROM refs", $sqlQuery))) // if the user specific fields are missing from the SELECT statement...
					$sqlQuery = str_replace(', call_number, serial FROM refs',', call_number, serial, marked, copy, selected, user_keys, user_notes, user_file FROM refs',$sqlQuery); // ...add all user specific fields to the 'SELECT' clause

				if (($displayType == "Display") AND (!ereg("LEFT JOIN user_data",$sqlQuery))) // if the 'LEFT JOIN...' statement isn't already part of the 'FROM' clause...
					$sqlQuery = ereg_replace(" FROM refs"," FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = $userID",$sqlQuery); // ...add the 'LEFT JOIN...' part to the 'FROM' clause
			}

			if (ereg("^SELECT", $sqlQuery)) // restrict adding of columns to SELECT queries (so that 'DELETE FROM refs ...' statements won't get modified as well):
			{
				$sqlQuery = str_replace(' FROM refs',', orig_record FROM refs',$sqlQuery); // add 'orig_record' column (which is required in order to present visual feedback on duplicate records)
				$sqlQuery = str_replace(' FROM refs',', serial FROM refs',$sqlQuery); // add 'serial' column (which is required in order to obtain unique checkbox names)
		
				if ("$showLinks" == "1")
					$sqlQuery = str_replace(' FROM refs',', file, url, doi FROM refs',$sqlQuery); // add 'file', 'url' & 'doi' columns
			}
		
			$query = str_replace('\"','"',$sqlQuery); // replace any \" with "
			$query = ereg_replace('(\\\\)+','\\\\',$query);
		}

	// --- Form 'simple_search.php': ---------------
	elseif ("$formType" == "simpleSearch") // the user used the 'simple_search.php' form for searching...
		{
			$query = extractFormElementsSimple($showLinks);
		}

	// --- Form 'library_search.php': --------------
	elseif ("$formType" == "librarySearch") // the user used the 'library_search.php' form for searching...
		{
			$query = extractFormElementsLibrary($showLinks);
		}

	// --- Form 'advanced_search.php': -------------
	elseif ("$formType" == "advancedSearch") // the user used the 'advanced_search.php' form for searching...
		{
			$query = extractFormElementsAdvanced($showLinks, $userID);
		}

	// --- Form within 'search.php': ---------------
	elseif ("$formType" == "refineSearch") // the user used the "Search within Results" form above the query results list (that was produced by 'search.php')
		{
			$query = extractFormElementsRefine($displayType, $sqlQuery, $showLinks, $userID);
		}

	// --- Form within 'search.php': ---------------
	elseif ("$formType" == "queryResults") // the user clicked either the 'Display' or 'Export' button within a query results list (that was produced by 'search.php')
		{
			$query = extractFormElementsQueryResults($displayType, $showLinks, $exportOrder, $orderBy, $userID);
		}

	// --- Form 'extract.php': ---------------------
	elseif ("$formType" == "extractSearch") // the user used the 'extract.php' form for searching...
		{
			$query = extractFormElementsExtract($exportOrder);
		}

	// --- My Refs Search Form within 'index.php': -------------------
	elseif ("$formType" == "myRefsSearch") // the user used the 'Show My Refs' search form on the main page ('index.php') for searching...
		{
			$query = extractFormElementsMyRefs($showLinks, $loginEmail, $userID);
		}

	// --- Quick Search Form within 'index.php': ---------------------
	elseif ("$formType" == "quickSearch") // the user used the 'Quick Search' form on the main page ('index.php') for searching...
		{
			$query = extractFormElementsQuick($showLinks);
		}

	// --------------------------------------------------------------------

	// (3) RUN QUERY, (4) DISPLAY HEADER & RESULTS

	// (3) RUN the query on the database through the connection:
	if (!($result = @ mysql_query ($query, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", $oldQuery);

	// (4a) DISPLAY header:
	// First, build the appropriate SQL query in order to embed it into the 'your query' URL:
	if ("$showLinks" == "1")
		$query = str_replace(', file, url, doi FROM refs',' FROM refs',$query); // strip 'file', 'url' & 'doi' columns from SQL query

	$query = str_replace(', serial FROM refs',' FROM refs',$query); // strip 'serial' column from SQL query

	$query = str_replace(', orig_record FROM refs',' FROM refs',$query); // strip 'orig_record' column from SQL query

	if (ereg("(simple|quick)Search", $formType)) // if $formType is "simpleSearch" or "quickSearch" and there is more than one WHERE clause (indicated by '...AND...'):
		$query = str_replace('WHERE serial RLIKE ".+" AND','WHERE',$query); // strip first WHERE clause (which was added only due to an internal workaround)

	$queryURL = rawurlencode($query); // URL encode SQL query

	if (!ereg("^SELECT", $query)) // for queries other than SELECT queries (e.g. UPDATE, DELETE or INSERT queries that were executed by the admin via use of 'sql_search.php')
		$affectedRows = ($result ? mysql_affected_rows ($connection) : 0); // get the number of rows that were modified (or return 0 if an error occurred)

	// Second, find out how many rows are available:
	$rowsFound = @ mysql_num_rows($result);
	if ($rowsFound > 0) // If there were rows found ...
		{
			// ... setup variables in order to facilitate "previous" & "next" browsing:
			// a) Set $rowOffset to zero if not previously defined, or if a wrong number (<=0) was given
			if (empty($rowOffset) || ($rowOffset <= 0))
				$rowOffset = 0;

			// Adjust the $showRows value if not previously defined, or if a wrong number (<=0) was given
			if (empty($showRows) || ($showRows <= 0))
				$showRows = 5;
			
			// b) The "Previous" page begins at the current offset LESS the number of rows per page
			$previousOffset = $rowOffset - $showRows;
			
			// c) The "Next" page begins at the current offset PLUS the number of rows per page
			$nextOffset = $rowOffset + $showRows;
			
			// d) Seek to the current offset
			mysql_data_seek($result, $rowOffset);
		}
	else // set variables to zero in order to prevent 'Undefined variable...' messages when nothing was found ('$rowsFound = 0'):
		{
			$previousOffset = 0;
			$nextOffset = 0;
		}

	// Third, calculate the maximum result number on each page ('$showMaxRow' is required as parameter to the 'displayRows()' function)
	if (($rowOffset + $showRows) < $rowsFound)
		$showMaxRow = ($rowOffset + $showRows); // maximum result number on each page
	else
		$showMaxRow = $rowsFound; // for the last results page, correct the maximum result number if necessary

	// Fourth, check if there's some query URL available pointing to a previous search results page
	if ($oldQuery == "")
		{
			// If there's no query URL available, we build the *full* query URL for the page currently displayed. The variable '$oldQuery' will get included into every 'browse'/'field title'/'display details'/'edit record'/'add record' link. Plus it will get written into a hidden form tag so that it's available on 'display details' (batch display)
			// The variable '$oldQuery' gets routed thru the 'display details' and 'record.php' forms to facilitate a link to the current results page on the subsequent receipt page that follows any add/edit/delete action!
			$oldQuery = "sqlQuery=" . $query . "&amp;showQuery=" . $showQuery . "&amp;showLinks=" . $showLinks . "&amp;formType=sqlSearch&amp;showRows=" . $showRows . "&amp;rowOffset=" . $rowOffset . "&amp;submit=" . $displayType . "&amp;exportFormatSelector=" . rawurlencode($exportFormat) . "&amp;exportOrder=" . $exportOrder;
		}
	else // there's already a query URL available
		// Note: If there's an existing 'oldQuery', a new 'oldQuery' will be generated only, if the output is routed thru the 'displayColumns()' function!
		//       This will only happen if $displayType == '' (i.e., not 'Display' or 'Export').
		{
			if (ereg('sqlQuery%3D', $oldQuery)) // if '$oldQuery' still contains URL encoded data... ('%3D' is the URL encoded form of '=', see note below!)
				$oldQuery = rawurldecode($oldQuery); // ...URL decode old query URL (it was URL encoded before incorporation into a hidden tag of the 'queryResults' form to avoid any HTML syntax errors)
												// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
												//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!
			$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "
			$oldQuery = ereg_replace('(\\\\)+','\\\\',$oldQuery);
		}

	// Finally, build the appropriate header string (which is required as parameter to the 'showPageHeader()' function):
	if (!session_is_registered("HeaderString")) // if there's no stored message available
	{
		if (!empty($headerMsg)) // if there's a custom header message available, e.g. one that describes who's literature is being displayed...
		{
			$HeaderString = $headerMsg; // ...we use that string as header message ('$headerMsg' could contain something like: "Literature of Matthias Steffens:")
		}
		else // provide the default message:
		{
			if (ereg("^SELECT", $query)) // for SELECT queries:
			{
				if ($rowsFound == 1)
					$HeaderStringPart = " record ";
				else
					$HeaderStringPart = " records ";

				if ("$showQuery" == "1")
					$HeaderString = $HeaderStringPart . "found matching <a href=\"sql_search.php?customQuery=1&amp;sqlQuery=$queryURL&amp;showQuery=$showQuery&amp;showLinks=$showLinks&amp;showRows=$showRows&amp;submit=$displayType&amp;exportFormatSelector=" . rawurlencode($exportFormat) . "&amp;exportOrder=$exportOrder&amp;oldQuery=" . rawurlencode($oldQuery) . "\">your query</a>:\n<br>\n<br>\n<code>$query</code>";
				else // $showQuery == "0" or wasn't specified
					$HeaderString = $HeaderStringPart . "found matching <a href=\"sql_search.php?customQuery=1&amp;sqlQuery=$queryURL&amp;showQuery=$showQuery&amp;showLinks=$showLinks&amp;showRows=$showRows&amp;submit=$displayType&amp;exportFormatSelector=" . rawurlencode($exportFormat) . "&amp;exportOrder=$exportOrder&amp;oldQuery=" . rawurlencode($oldQuery) . "\">your query</a>:";
			
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

				if ("$showQuery" == "1")
					$HeaderString = $affectedRows . $HeaderStringPart . "affected by <a href=\"sql_search.php?customQuery=1&amp;sqlQuery=$queryURL&amp;showQuery=$showQuery&amp;showLinks=$showLinks&amp;showRows=$showRows&amp;submit=$displayType&amp;exportFormatSelector=" . rawurlencode($exportFormat) . "&amp;exportOrder=$exportOrder&amp;oldQuery=" . rawurlencode($oldQuery) . "\">your query</a>:\n<br>\n<br>\n<code>$query</code>";
				else // $showQuery == "0" or wasn't specified
					$HeaderString = $affectedRows . $HeaderStringPart . "affected by <a href=\"sql_search.php?customQuery=1&amp;sqlQuery=$queryURL&amp;showQuery=$showQuery&amp;showLinks=$showLinks&amp;showRows=$showRows&amp;submit=$displayType&amp;exportFormatSelector=" . rawurlencode($exportFormat) . "&amp;exportOrder=$exportOrder&amp;oldQuery=" . rawurlencode($oldQuery) . "\">your query</a>:";
			}
		}
	}
	else
		session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

	// Now, show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// Then, call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- Query Results", "noindex,nofollow", "Results from the " . htmlentities($officialDatabaseName), "", true, "");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, $oldQuery);

	// (4b) DISPLAY results:
	if ($displayType == "Display") // display details for each of the selected records
		displayRows($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $nothingChecked, $exportFormat, $exportOrder, $orderBy, $showMaxRow, $headerMsg, $userID);

	elseif ($displayType == "Export") // export each of the selected records
		exportRows($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $nothingChecked, $exportFormat, $exportOrder, $orderBy, $headerMsg, $userID);

	else // show all records in columnar style
		displayColumns($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $exportFormat, $exportOrder, $headerMsg, $userID, $displayType);

	// --------------------------------------------------------------------

	// (5) CLOSE CONNECTION

	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to disconnect from the database:", $oldQuery);

	// --------------------------------------------------------------------

	// SHOW THE RESULTS IN AN HTML <TABLE> (columnar layout)
	function displayColumns($result, $rowsFound, $query, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $exportFormat, $exportOrder, $headerMsg, $userID, $displayType)
	{
		global $oldQuery; // This is required since the 'add record' link gets constructed outside this function, otherwise it would still contain the older query URL!)
		global $filesBaseURL; // defined in 'ini.inc.php'
		global $markupSearchReplacePatterns; // defined in 'ini.inc.php'

		if (ereg(".+LIMIT *[0-9]+",$query)) // query does contain the 'LIMIT' parameter
			$orderBy = ereg_replace(".+ORDER BY (.+) LIMIT.+","\\1",$query); // extract 'ORDER BY'... parameter (without including any 'LIMIT' parameter)
		else // query does not contain the 'LIMIT' parameter
			$orderBy = ereg_replace(".+ORDER BY (.+)","\\1",$query); // extract 'ORDER BY'... parameter
			

	// If the query has results ...
	if ($rowsFound > 0) 
	{
		// BEGIN RESULTS HEADER --------------------
		// 1) First, initialize some variables that we'll need later on
		if ("$showLinks" == "1")
			$CounterMax = "3"; // When displaying a 'Links' column truncate the last three columns (i.e., hide the 'file', 'url' and 'doi' columns)
		else
			$CounterMax = "0"; // Otherwise don't hide any columns

		// count the number of fields
		$fieldsFound = mysql_num_fields($result);
		// hide those last columns that were added by the script and not by the user
		$fieldsToDisplay = $fieldsFound-(2+$CounterMax); // (2+$CounterMax) -> $CounterMax is increased by 2 in order to hide the 'orig_record' & 'serial' columns (which were added to make checkboxes & dup warning work)

		// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
		if ("$showLinks" == "1")
			$NoColumns = (1+$fieldsToDisplay+1); // add checkbox & Links column
		else
			$NoColumns = (1+$fieldsToDisplay); // add checkbox column

		// Although there might be an (older) query URL available, we build a new query URL for the page currently displayed. The variable '$oldQuery' will get included into every 'browse'/'field title'/'display details'/'edit record'/'add record' link. Plus it will get written into a hidden form tag so that it's available on 'display details' (batch display)
		// The variable '$oldQuery' gets routed thru the 'display details' and 'record.php' forms to facilitate a link to the current results page on the subsequent receipt page that follows any add/edit/delete action!
		$oldQuery = "sqlQuery=" . $query . "&amp;showQuery=" . $showQuery . "&amp;showLinks=" . $showLinks . "&amp;formType=sqlSearch&amp;showRows=" . $showRows . "&amp;rowOffset=" . $rowOffset . "&amp;submit=" . $displayType . "&amp;exportFormatSelector=" . rawurlencode($exportFormat) . "&amp;exportOrder=" . $exportOrder;


		// 2) Build a FORM & TABLE containing options to refine the search results as well as the diplayed columns
		//    First, specify which colums should be available in the popup menu (column items must be separated by a comma or comma+space!):
		$refineSearchSelectorElements1 = "author, title, year, keywords, abstract, publication, abbrev_journal, volume, issue, pages, editor, publisher, area, type, location, call_number, notes"; // these columns will be always visible (no matter whether the user is logged in or not)
		$refineSearchSelectorElements2 = "marked, copy, selected, user_keys, user_notes, user_file"; // these columns will be only visible to logged in users (in this case: the user specific fields from table 'user_data')
		$refineSearchSelectorElementSelected = "author"; // this column will be selected by default
		// Call the 'buildRefineSearchElements()' function (defined in 'include.inc') which does the actual work:
		$RefineSearch = buildRefineSearchElements("search.php", $queryURL, $showQuery, $showLinks, $showRows, $NoColumns, $refineSearchSelectorElements1, $refineSearchSelectorElements2, $refineSearchSelectorElementSelected);
		echo $RefineSearch;


		// 3) Start another FORM
		echo "\n<form action=\"search.php\" method=\"POST\" name=\"queryResults\">"
				. "\n<input type=\"hidden\" name=\"formType\" value=\"queryResults\">"
				. "\n<input type=\"hidden\" name=\"submit\" value=\"Display\">" // provide a default value for the 'submit' form tag (then, hitting <enter> within the 'ShowRows' text entry field will act as if the user clicked the 'Display' button)
				. "\n<input type=\"hidden\" name=\"orderBy\" value=\"" . rawurlencode($orderBy) . "\">" // embed the current ORDER BY parameter so that it can be re-applied when displaying details
				. "\n<input type=\"hidden\" name=\"showQuery\" value=\"$showQuery\">" // embed the current value of '$showQuery' so that it's available on 'display details' (batch display) & 'export'
				. "\n<input type=\"hidden\" name=\"showLinks\" value=\"$showLinks\">" // embed the current value of '$showLinks' so that it's available on 'display details' (batch display) & 'export'
				. "\n<input type=\"hidden\" name=\"oldQuery\" value=\"" . rawurlencode($oldQuery) . "\">"; // embed the current value of '$oldQuery' so that it's available on 'display details' (batch display)

		//    and start a TABLE, with column headers
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the database results for your query\">";
		

		// 4) Build a TABLE ROW with links for "previous" & "next" browsing, as well as links to intermediate pages
		//    call the 'buildBrowseLinks()' function (defined in 'include.inc'):
		$BrowseLinks = buildBrowseLinks("search.php", $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, "25", "sqlSearch", "", $exportFormat, $exportOrder, $orderBy, $headerMsg);
		echo $BrowseLinks;

		//    and insert a spacer TABLE ROW:
		echo "\n<tr align=\"center\">"
//			. "\n\t<td colspan=\"$NoColumns\">&nbsp;</td>" // uncomment this line and comment the next one if you prefer white space (instead of a divider line)
			. "\n\t<td colspan=\"$NoColumns\"><hr align=\"center\" width=\"100%\"></td>"
			. "\n</tr>";


		// 5) For the column headers, start another TABLE ROW ...
		echo "\n<tr>";

		// ... print a marker ('x') column (which will hold the checkboxes within the results part)
		echo "\n\t<th align=\"left\" valign=\"top\">&nbsp;</th>";

		// for each of the attributes in the result set...
		for ($i=0; $i<$fieldsToDisplay; $i++)
		{
			// ... and print out each of the attribute names
			// in that row as a separate TH (Table Header)...
			$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
			$HTMLafterLink = "</th>"; // close the table header tag
			// call the 'buildFieldNameLinks()' function (defined in 'include.inc'), which will return a properly formatted table header tag holding the current field's name
			// as well as the URL encoded query with the appropriate ORDER clause:
			$tableHeaderLink = buildFieldNameLinks("search.php", $query, $oldQuery, "", $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "", "", "");
			echo $tableHeaderLink; // print the attribute name as link
		 }

		if ("$showLinks" == "1")
			{
				$newORDER = ("ORDER BY url DESC, doi DESC"); // Build the appropriate ORDER BY clause to facilitate sorting by Links column

				$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
				$HTMLafterLink = "</th>"; // close the table header tag
				// call the 'buildFieldNameLinks()' function (defined in 'include.inc'), which will return a properly formatted table header tag holding the current field's name
				// as well as the URL encoded query with the appropriate ORDER clause:
				$tableHeaderLink = buildFieldNameLinks("search.php", $query, $oldQuery, $newORDER, $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "", "Links", "url");
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
			echo "\n\t<td align=\"center\" valign=\"top\" width=\"10\">\n\t\t<input type=\"checkbox\" name=\"marked[]\" value=\"" . $row["serial"] . "\">";

			if (!empty($row["orig_record"]))
			{
				echo "\n\t\t<br>";
				if ($row["orig_record"] < 0)
					echo "<img src=\"img/ok.gif\" alt=\"(original)\" title=\"original record\" width=\"14\" height=\"16\" hspace=\"0\" border=\"0\">";
				else // $row["orig_record"] > 0
					echo "<img src=\"img/caution.gif\" alt=\"(duplicate)\" title=\"duplicate record\" width=\"5\" height=\"16\" hspace=\"0\" border=\"0\">";
			}

			echo "\n\t</td>";

			// ... and print out each of the attributes
			// in that row as a separate TD (Table Data)
			// (Note: 'htmlentities($row[$i])' for HTML encoding higher ASCII will only work correctly if character encoding of data is ISO-8859-1!)
			for ($i=0; $i<$fieldsToDisplay; $i++)
			{
				$row[$i] = htmlentities($row[$i]); // HTML encode higher ASCII characters

				// the following two lines will fetch the current attribute name:
				$info = mysql_fetch_field ($result, $i); // get the meta-data for the attribute
				$orig_fieldname = $info->name; // get the attribute name

				// if '$markupSearchReplacePatterns' (defined in 'ini.inc.php') contains search & replace actions...
				if (ereg("^(title|keywords|abstract)$", $orig_fieldname)) // ...apply them to the 'title', 'keywords' and 'abstract' fields
					foreach ($markupSearchReplacePatterns as $searchString => $replaceString)
						if (preg_match("/" . $searchString . "/", $row[$i]))
							$row[$i] = preg_replace("/" . $searchString . "/", $replaceString, $row[$i]);

				echo "\n\t<td valign=\"top\">" . $row[$i] . "</td>";
			}

			// embed appropriate links (if available):
			if ("$showLinks" == "1")
			{
				echo "\n\t<td valign=\"top\">";

				if (session_is_registered("loginEmail")) // if a user is logged in, show user specific fields:
					echo "\n\t\t<a href=\"search.php?sqlQuery=SELECT%20author%2C%20title%2C%20type%2C%20year%2C%20publication%2C%20abbrev_journal%2C%20volume%2C%20issue%2C%20pages%2C%20corporate_author%2C%20thesis%2C%20address%2C%20keywords%2C%20abstract%2C%20publisher%2C%20place%2C%20editor%2C%20language%2C%20summary_language%2C%20orig_title%2C%20series_editor%2C%20series_title%2C%20abbrev_series_title%2C%20series_volume%2C%20series_issue%2C%20edition%2C%20issn%2C%20isbn%2C%20medium%2C%20area%2C%20expedition%2C%20conference%2C%20notes%2C%20approved%2C%20location%2C%20call_number%2C%20serial%2C%20marked%2C%20copy%2C%20selected%2C%20user_keys%2C%20user_notes%2C%20user_file%20"
						. "FROM%20refs%20LEFT%20JOIN%20user_data%20ON%20serial%20%3D%20record_id%20AND%20user_id%20%3D%20" . $userID . "%20";
				else // if NO user logged in, don't display any user specific fields:
					echo "\n\t\t<a href=\"search.php?sqlQuery=SELECT%20author%2C%20title%2C%20type%2C%20year%2C%20publication%2C%20abbrev_journal%2C%20volume%2C%20issue%2C%20pages%2C%20corporate_author%2C%20thesis%2C%20address%2C%20keywords%2C%20abstract%2C%20publisher%2C%20place%2C%20editor%2C%20language%2C%20summary_language%2C%20orig_title%2C%20series_editor%2C%20series_title%2C%20abbrev_series_title%2C%20series_volume%2C%20series_issue%2C%20edition%2C%20issn%2C%20isbn%2C%20medium%2C%20area%2C%20expedition%2C%20conference%2C%20notes%2C%20approved%2C%20location%2C%20call_number%2C%20serial%20"
						. "FROM%20refs%20";

				echo "WHERE%20serial%20RLIKE%20%22%5E%28" . $row["serial"]
					. "%29%24%22%20ORDER%20BY%20" . rawurlencode($orderBy)
					. "&amp;showQuery=" . $showQuery
					. "&amp;showLinks=" . $showLinks
					. "&amp;formType=sqlSearch"
					. "&amp;submit=Display"
					. "&amp;oldQuery=" . rawurlencode($oldQuery)
					. "\"><img src=\"img/details.gif\" alt=\"details\" title=\"show details\" width=\"9\" height=\"17\" hspace=\"0\" border=\"0\"></a>&nbsp;&nbsp;";

				echo "\n\t\t<a href=\"record.php?serialNo=" . $row["serial"] . "&amp;recordAction=edit"
					. "&amp;oldQuery=" . rawurlencode($oldQuery)
					. "\"><img src=\"img/edit.gif\" alt=\"edit\" title=\"edit record\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";

				if ((!empty($row["file"])) OR (!empty($row["doi"])) OR (!empty($row["url"])))
					echo "\n\t\t<br>";

				if (session_is_registered("loginEmail")) // if a user is logged in, show a link to any corresponding file (if available):
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
		// END RESULTS DATA COLUMNS ----------------

		// BEGIN RESULTS FOOTER --------------------
		// Insert a spacer TABLE ROW:
		echo "\n<tr align=\"center\">"
//			. "\n\t<td colspan=\"$NoColumns\">&nbsp;</td>" // uncomment this line and comment the next one if you prefer white space (instead of a divider line)
			. "\n\t<td colspan=\"$NoColumns\"><hr align=\"center\" width=\"100%\"></td>"
			. "\n</tr>";

		// Again, insert the (already constructed) BROWSE LINKS
		// (i.e., a TABLE row with links for "previous" & "next" browsing, as well as links to intermediate pages)
		echo $BrowseLinks;

		// Build two rows with links for managing the checkboxes, as well as buttons for displaying/exporting selected records
		// Call the 'buildResultsFooter()' function (which does the actual work):
		$ResultsFooter = buildResultsFooter($NoColumns, $showRows, $exportFormat);
		echo $ResultsFooter;
		// END RESULTS FOOTER ----------------------

		// Then, finish the table
		echo "\n</table>";

		// Finally, finish the form
		echo "\n</form>";
	}
	else
	{
		// Report that nothing was found:
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the database results for your query\">"
				. "\n<tr>"
				. "\n\t<td valign=\"top\">Sorry, but your query didn't produce any results!&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>"
				. "\n</tr>"
				. "\n</table>";
	}// end if $rowsFound body
	}

	// --------------------------------------------------------------------

	// SHOW THE RESULTS IN AN HTML <TABLE> (horizontal layout)
	function displayRows($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $nothingChecked, $exportFormat, $exportOrder, $orderBy, $showMaxRow, $headerMsg, $userID)
	{
		global $filesBaseURL; // defined in 'ini.inc.php'
		global $markupSearchReplacePatterns; // defined in 'ini.inc.php'
		global $databaseBaseURL; // defined in 'ini.inc.php'

		// Start a form
		echo "\n<form action=\"search.php\" method=\"POST\" name=\"queryResults\">"
				. "\n<input type=\"hidden\" name=\"formType\" value=\"queryResults\">"
				. "\n<input type=\"hidden\" name=\"submit\" value=\"Display\">" // provide a default value for the 'submit' form tag (then, hitting <enter> within the 'ShowRows' text entry field will act as if the user clicked the 'Display' button)
				. "\n<input type=\"hidden\" name=\"orderBy\" value=\"" . rawurlencode($orderBy) . "\">" // embed the current ORDER BY parameter so that it can be re-applied when displaying details
				. "\n<input type=\"hidden\" name=\"showQuery\" value=\"$showQuery\">" // embed the current value of '$showQuery' so that it's available on 'display details' (batch display) & 'export'
				. "\n<input type=\"hidden\" name=\"showLinks\" value=\"$showLinks\">" // embed the current value of '$showLinks' so that it's available on 'display details' (batch display) & 'export'
				. "\n<input type=\"hidden\" name=\"oldQuery\" value=\"" . rawurlencode($oldQuery) . "\">"; // embed the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!

		// Start a table, with column headers
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" width=\"95%\" summary=\"This table holds the database results for your query\">";
		
	// If the query has results ...
	if ($rowsFound > 0) 
	{
		// BEGIN RESULTS HEADER --------------------
		// 1) First, initialize some variables that we'll need later on
		if ("$showLinks" == "1")
			$CounterMax = "3"; // When displaying a 'Links' column truncate the last three columns (i.e., hide the 'file', 'url' and 'doi' columns)
		else
			$CounterMax = "0"; // Otherwise don't hide any columns

		// count the number of fields
		$fieldsFound = mysql_num_fields($result);
		// hide those last columns that were added by the script and not by the user
		$fieldsToDisplay = $fieldsFound-(2+$CounterMax); // (2+$CounterMax) -> $CounterMax is increased by 2 in order to hide the 'orig_record' & 'serial' columns (which were added to make checkboxes & dup warning work)

		// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
		if ("$showLinks" == "1") // in 'display details' layout, we simply set it to a fixed no of columns:
			$NoColumns = 8; // 8 columns: checkbox, 3 x (field name + field contents), links
		else
			$NoColumns = 7; // 7 columns: checkbox, field name, field contents

		// 2) Build a TABLE row with links for "previous" & "next" browsing, as well as links to intermediate pages
		//    call the 'buildBrowseLinks()' function (defined in 'include.inc'):
		$BrowseLinks = buildBrowseLinks("search.php", $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, "25", "sqlSearch", "Display", $exportFormat, $exportOrder, $orderBy, $headerMsg);
		echo $BrowseLinks;

		// 3) For the column headers, start another TABLE row ...
		echo "\n<tr>";

		// ... print a marker ('x') column (which will hold the checkboxes within the results part)
		echo "\n\t<th align=\"left\" valign=\"top\">&nbsp;</th>";

		// ... print a record header
		if (($showMaxRow-$rowOffset) == "1") // '$showMaxRow-$rowOffset' gives the number of displayed records for a particular page) // '($rowsFound == "1" || $showRows == "1")' wouldn't trap the case of a single record on the last of multiple results pages!
				$recordHeader = "Record"; // use singular form if there's only one record to display
		else
				$recordHeader = "Records"; // use plural form if there are multiple records to display
		echo "\n\t<th align=\"left\" valign=\"top\" colspan=\"6\">$recordHeader</th>";

		if ("$showLinks" == "1")
			{
				$newORDER = ("ORDER BY url DESC, doi DESC"); // Build the appropriate ORDER BY clause to facilitate sorting by Links column

				$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
				$HTMLafterLink = "</th>"; // close the table header tag
				// call the 'buildFieldNameLinks()' function (defined in 'include.inc'), which will return a properly formatted table header tag holding the current field's name
				// as well as the URL encoded query with the appropriate ORDER clause:
				$tableHeaderLink = buildFieldNameLinks("search.php", $query, $oldQuery, $newORDER, $result, "", $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "Display", "Links", "url");
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
					if (ereg("^(author|title|year|volume|corporate_author|address|keywords|abstract|publisher|language|series_editor|series_volume|issn|area|notes|location|call_number|marked|user_keys|user_notes|user_file|created_date|modified_date)$", $orig_fieldname))
						{
							$recordData .= "\n<tr>"; // ...start a new TABLE row

							if ($i == "0") // ... print a column with a checkbox if it's the first row of attribute data:
								$recordData .= "\n\t<td align=\"left\" valign=\"top\" width=\"10\"><input type=\"checkbox\" name=\"marked[]\" value=\"" . $row["serial"] . "\"></td>";
							else // ... otherwise simply print an empty TD tag:
								$recordData .= "\n\t<td valign=\"top\" width=\"10\">&nbsp;</td>";
						}

					// ... and print out each of the ATTRIBUTE NAMES:
					// in that row as a bold link...
					if (ereg("^(author|title|type|year|publication|abbrev_journal|volume|issue|pages|call_number|serial)$", $orig_fieldname)) // print a colored background (grey)
						{
							$HTMLbeforeLink = "\n\t<td valign=\"top\" width=\"75\" bgcolor=\"#DEDEDE\"><b>"; // start the (bold) TD tag
							$HTMLafterLink = "</b></td>"; // close the (bold) TD tag
						}
					elseif (ereg("^(marked|copy|selected|user_keys|user_notes|user_file)$", $orig_fieldname)) // print a colored background (light orange) for all the user specific fields
						{
							$HTMLbeforeLink = "\n\t<td valign=\"top\" width=\"75\" bgcolor=\"#FFFFCC\"><b>"; // start the (bold) TD tag
							$HTMLafterLink = "</b></td>"; // close the (bold) TD tag
						}
					else // no colored background
						{
							$HTMLbeforeLink = "\n\t<td valign=\"top\" width=\"75\"><b>"; // start the (bold) TD tag
							$HTMLafterLink = "</b></td>"; // close the (bold) TD tag
						}
					// call the 'buildFieldNameLinks()' function (defined in 'include.inc'), which will return a properly formatted table data tag holding the current field's name
					// as well as the URL encoded query with the appropriate ORDER clause:
					$recordData .= buildFieldNameLinks("search.php", $query, $oldQuery, "", $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "Display", "", "");

					// print the ATTRIBUTE DATA:
					// first, calculate the correct colspan value for all the fields specified:
					if (ereg("^(author|address|keywords|abstract|location|user_keys|user_notes|user_file)$", $orig_fieldname))
						$ColspanFields = 5; // supply an appropriate colspan value
					elseif (ereg("^(title|corporate_author|notes|call_number)$", $orig_fieldname))
						$ColspanFields = 3; // supply an appropriate colspan value

					// then, start the TD tag, for all the fields specified:
					if (ereg("^(author|title|corporate_author|address|keywords|abstract|notes|location|call_number|user_keys|user_notes|user_file)$", $orig_fieldname)) // WITH colspan attribute:
						if (ereg("^(author|title|call_number)$", $orig_fieldname)) // print a colored background (grey)
							$recordData .= "\n\t<td valign=\"top\" colspan=\"$ColspanFields\" bgcolor=\"#DEDEDE\">"; // ...with colspan attribute & appropriate value
						elseif (ereg("^(user_keys|user_notes|user_file)$", $orig_fieldname)) // print a colored background (light orange) for all the user specific fields
							$recordData .= "\n\t<td valign=\"top\" colspan=\"$ColspanFields\" bgcolor=\"#FFFFCC\">"; // ...with colspan attribute & appropriate value
						else // no colored background
							$recordData .= "\n\t<td valign=\"top\" colspan=\"$ColspanFields\">"; // ...with colspan attribute & appropriate value

					else // for all other fields WITHOUT colspan attribute:
						if (ereg("^(type|year|publication|abbrev_journal|volume|issue|pages|serial)$", $orig_fieldname)) // print a colored background (grey)
							$recordData .= "\n\t<td valign=\"top\" bgcolor=\"#DEDEDE\">"; // ...without colspan attribute
						elseif (ereg("^(marked|copy|selected)$", $orig_fieldname)) // print a colored background (light orange) for all the user specific fields
							$recordData .= "\n\t<td valign=\"top\" bgcolor=\"#FFFFCC\">"; // ...without colspan attribute
						else // no colored background
							$recordData .= "\n\t<td valign=\"top\">"; // ...without colspan attribute
		
					if (ereg("^(author|title|year)$", $orig_fieldname)) // print author, title & year fields in bold
						$recordData .= "<b>";

					// Note: 'htmlentities($row[$i])' for HTML encoding higher ASCII will only work correctly if character encoding of data is ISO-8859-1!
					$row[$i] = htmlentities($row[$i]); // HTML encode higher ASCII characters

					// if '$markupSearchReplacePatterns' (defined in 'ini.inc.php') contains search & replace actions...
					if (ereg("^(title|keywords|abstract)$", $orig_fieldname)) // ...apply them to the 'title', 'keywords' and 'abstract' fields
						foreach ($markupSearchReplacePatterns as $searchString => $replaceString)
							if (preg_match("/" . $searchString . "/", $row[$i]))
								$row[$i] = preg_replace("/" . $searchString . "/", $replaceString, $row[$i]);

					$recordData .= $row[$i]; // print the attribute data

					if (ereg("^(author|title|year)$", $orig_fieldname))
						$recordData .= "</b>";							

					$recordData .= "</td>"; // finish the TD tag

					// for all the fields specified (-> all fields to the right):
					if (ereg("^(author|type|abbrev_journal|pages|thesis|address|keywords|abstract|editor|orig_title|abbrev_series_title|edition|medium|conference|approved|location|serial|selected|user_keys|user_notes|user_file|created_by|modified_by)$", $orig_fieldname))
						{
							if ("$showLinks" == "1")
								{
									// ...embed appropriate links (if available):
									if ($i == "0") // ... print a column with links if it's the first row of attribute data:
									{
										$recordData .= "\n\t<td valign=\"top\" width=\"50\">";
										$recordData .= "\n\t\t<a href=\"record.php?serialNo=" . $row["serial"] . "&amp;recordAction=edit"
													. "&amp;oldQuery=" . rawurlencode($oldQuery)
													. "\"><img src=\"img/edit.gif\" alt=\"edit\" title=\"edit record\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";

										if ((!empty($row["file"])) OR ((!empty($row["doi"])) OR (!empty($row["url"]))))
											$recordData .= "&nbsp;&nbsp;";

										if (session_is_registered("loginEmail")) // if a user is logged in, show a link to any corresponding file (if available):
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

										if (session_is_registered("loginEmail") AND !empty($row["file"]) AND (!empty($row["doi"]) OR !empty($row["url"])))
											$recordData .= "<br>";

										if (!empty($row["url"])) // 'htmlentities()' is used to convert any '&' into '&amp;'
											$recordData .= "\n\t\t<a href=\"" . htmlentities($row["url"]) . "\"><img src=\"img/www.gif\" alt=\"url\" title=\"goto web page\" width=\"17\" height=\"20\" hspace=\"0\" border=\"0\"></a>";
							
										if (!empty($row["url"]) AND (!empty($row["doi"])))
											$recordData .= "&nbsp;";

										if (!empty($row["doi"]))
											$recordData .= "\n\t\t<a href=\"http://dx.doi.org/" . $row["doi"] . "\"><img src=\"img/doi.gif\" alt=\"doi\" title=\"goto web page (via DOI)\" width=\"17\" height=\"20\" hspace=\"0\" border=\"0\"></a>";

										$recordData .= "\n\t</td>";
									}
									else // ... otherwise simply print an empty TD tag:
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
		// END RESULTS DATA COLUMNS ----------------

		// BEGIN RESULTS FOOTER --------------------
		// Again, insert the (already constructed) BROWSE LINKS
		// (i.e., a TABLE row with links for "previous" & "next" browsing, as well as links to intermediate pages)
		echo $BrowseLinks;

		// Build two rows with links for managing the checkboxes, as well as buttons for displaying/exporting selected records
		// Call the 'buildResultsFooter()' function (which does the actual work):
		$ResultsFooter = buildResultsFooter($NoColumns, $showRows, $exportFormat);
		echo $ResultsFooter;
		// END RESULTS FOOTER ----------------------
	}
	else
	{
		if ($nothingChecked == true)
			// Inform the user that no records were selected:
			echo "\n<tr>\n\t<td valign=\"top\">No records selected! Please select one or more records by clicking the appropriate checkboxes.&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>\n";
		else // $nothingChecked == false (i.e., the user did check some checkboxes) -OR- the query resulted from another script like 'show.php' (which has no checkboxes to mark!)
			// Report that nothing was found:
			echo "\n<tr>\n\t<td valign=\"top\">Sorry, but your query didn't produce any results!&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>\n";
	}// end if $rowsFound body

	// Then, finish the table
	echo "\n</table>";

	// Finally, finish the form
	echo "\n</form>";
	}

	// --------------------------------------------------------------------

	// SHOW THE RESULTS IN AN HTML <TABLE> (export layout)
	function exportRows($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $nothingChecked, $exportFormat, $exportOrder, $orderBy, $headerMsg, $userID)
	{
		global $markupSearchReplacePatterns; // defined in 'ini.inc.php'

		// Start a table
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the database results for your query\">";
		
	// If the query has results ...
	if ($rowsFound > 0) 
	{
		// BEGIN RESULTS HEADER --------------------
		// 1) First, initialize some variables that we'll need later on
		// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
		if ("$showLinks" == "1" && "$exportOrder" == "year") // in export layout, we simply set it to a fixed value (either '1' or '2', depending on the values of '$showLinks' and '$exportOrder')
			$NoColumns = 2; // first column: literature citation, second column: 'display details' link
		else
			$NoColumns = 1;

		// 2) Build a TABLE row with links for "previous" & "next" browsing, as well as links to intermediate pages
		//    call the 'buildBrowseLinks()' function (defined in 'include.inc'):
		$BrowseLinks = buildBrowseLinks("search.php", $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, "25", "sqlSearch", "Export", $exportFormat, $exportOrder, $orderBy, $headerMsg);
		echo $BrowseLinks;
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

			// if '$markupSearchReplacePatterns' (defined in 'ini.inc.php') contains search & replace actions, apply them to the 'title' field:
			foreach ($markupSearchReplacePatterns as $searchString => $replaceString)
				if (preg_match("/" . $searchString . "/", $row['title']))
					$row['title'] = preg_replace("/" . $searchString . "/", $replaceString, $row['title']);

			$record = ""; // make sure that our buffer variable is empty

			// Order attributes according to the chosen output style & record type:

			// --- BEGIN POLAR BIOLOGY|MARINE BIOLOGY|MEPS ------
			if (ereg("Polar Biol|Mar Biol|MEPS", $exportFormat)) // format output according to the citation style used by journals 'Polar Biology', 'Marine Biology', 'Marine Ecology Progress Series'
				{
					if ($row['type'] == "Journal Article")		// JOURNAL ARTICLE ------------------------------
						{
							if (!empty($row['author']))			// author
								{
									// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
									//   1. pattern describing old delimiter that separates different authors
									//   2. new delimiter that separates different authors
									//   3. pattern describing old delimiter that separates author name & initials (within one author)
									//   4. new delimiter that separates author name & initials (within one author)
									//   5. new delimiter that separates multiple initials (within one author)
									//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
									//   7. contents of the author field
									$author = reArrangeAuthorContents(" *; *",
																		", ",
																		" *, *",
																		" ",
																		"",
																		false,
																		$row['author']);
									$record .= $author . " ";
								}

							if (!empty($row['year']))				// year
								$record .= "(" . $row['year'] . ") ";

							if (!empty($row['title']))			// title
								{
									$record .= $row['title'];
									if (!ereg("[?!.]$", $row['title']))
										$record .= ".";
									$record .= " ";
								}

							if (!empty($row['publication']))		// publication
								$record .= $row['publication'] . " ";

							if (!empty($row['volume']))			// volume
								$record .= $row['volume'];

							if (!empty($row['issue']))			// issue
								$record .= "(" . $row['issue'] . ")";

							if ($row['online_publication'] == "yes") // this record refers to an online article
							{
								// instead of any pages info (which normally doesn't exist for online publications) we append
								// an optional string (given in 'online_citation') plus the DOI:

								if (!empty($row['online_citation']))			// online_citation
								{
									if (!empty($row['volume'])||!empty($row['issue']))		// only add ":" if either volume or issue isn't empty
										$record .= ":";

									$record .= " " . $row['online_citation'];
								}

								if (!empty($row['doi']))			// doi
									$record .= " doi:" . $row['doi'];
							}
							else // $row['online_publication'] == "no" -> this record refers to a printed article, so we append any pages info instead:
							{
								if (!empty($row['pages']))			// pages
									{
										if (!empty($row['volume'])||!empty($row['issue']))		// only add ":" if either volume or issue isn't empty
											$record .= ":";
										if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
											$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
										else
											$pagesDisplay = $row['pages'];
										$record .= $pagesDisplay;
									}
							}
						}
					elseif ($row['type'] == "Book Chapter")		// BOOK CHAPTER ---------------------------------
						{
							if (!empty($row['author']))			// author
								{
									// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
									//   1. pattern describing old delimiter that separates different authors
									//   2. new delimiter that separates different authors
									//   3. pattern describing old delimiter that separates author name & initials (within one author)
									//   4. new delimiter that separates author name & initials (within one author)
									//   5. new delimiter that separates multiple initials (within one author)
									//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
									//   7. contents of the author field
									$author = reArrangeAuthorContents(" *; *",
																		", ",
																		" *, *",
																		" ",
																		"",
																		false,
																		$row['author']);
									$record .= $author . " ";
								}

							if (!empty($row['year']))				// year
								$record .= "(" . $row['year'] . ") ";

							if (!empty($row['title']))			// title
								{
									$record .= $row['title'];
									if (!ereg("[?!.]$", $row['title']))
										$record .= ".";
									$record .= " ";
								}

							if (!empty($row['editor']))			// editor
								{
									// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
									//   1. pattern describing old delimiter that separates different authors
									//   2. new delimiter that separates different authors
									//   3. pattern describing old delimiter that separates author name & initials (within one author)
									//   4. new delimiter that separates author name & initials (within one author)
									//   5. new delimiter that separates multiple initials (within one author)
									//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
									//   7. contents of the author field
									$editor = reArrangeAuthorContents(" *; *",
																		", ",
																		" *, *",
																		" ",
																		"",
																		false,
																		$row['editor']);
									$record .= "In: " . $editor;
									if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
										$record .= " (eds)";
									else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
										$record .= " (ed)";
								}

							$publication = ereg_replace("[ \r\n]*\(Eds?:[^\)\r\n]*\)", "", $row['publication']);
							if (!empty($publication))			// publication
								$record .= " " . $publication . ". ";
							else
								if (!empty($row['editor']))
									$record .= ". ";

							if (!empty($row['series_title'])) // if there's a series title, series information will replace the publisher & place information
								{
									$record .= $row['series_title'];	// series title

									if (!empty($row['series_volume'])||!empty($row['series_issue']))
										$record .= " ";

									if (!empty($row['series_volume']))	// series volume
										$record .= $row['series_volume'];
		
									if (!empty($row['series_issue']))	// series issue
										$record .= "(" . $row['series_issue'] . ")";

									if (!empty($row['pages']))
										$record .= ", ";

								}
							else // if there's NO series title available, we'll insert the publisher & place instead:
								{
									if (!empty($row['publisher']))		// publisher
										{
											$record .= $row['publisher'];
											if (!empty($row['place']))
												$record .= ", ";
											else
												if (!ereg(",$", $row['publisher']))
													$record .= ",";
												$record .= " ";
										}
		
									if (!empty($row['place']))			// place
										{
											$record .= $row['place'];
											if (!empty($row['pages']))
												{
													if (!ereg(",$", $row['place']))
														$record .= ",";
													$record .= " ";
												}
										}
								}

							if (!empty($row['pages']))			// pages
								{
									if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
										$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
									else
										$pagesDisplay = $row['pages'];
									$record .= "pp " . $pagesDisplay;
								}
						}
					elseif (ereg("Book Whole|Map|Manuscript|Journal", $row['type']))	// BOOK WHOLE -- MAP -- MANUSCRIPT --
						{
							if (!empty($row['author']))			// author
								{
									$author = ereg_replace("[ \r\n]*\(eds?\)", "", $row['author']);

									// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
									//   1. pattern describing old delimiter that separates different authors
									//   2. new delimiter that separates different authors
									//   3. pattern describing old delimiter that separates author name & initials (within one author)
									//   4. new delimiter that separates author name & initials (within one author)
									//   5. new delimiter that separates multiple initials (within one author)
									//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
									//   7. contents of the author field
									$author = reArrangeAuthorContents(" *; *",
																		", ",
																		" *, *",
																		" ",
																		"",
																		false,
																		$author);
									$record .= $author . " ";
								}

							if (!empty($row['year']))				// year
								$record .= "(" . $row['year'] . ") ";

							if (!empty($row['title']))			// title
								{
									$record .= $row['title'];
									if (!ereg("[?!.]$", $row['title']))
										$record .= ".";
									$record .= " ";
								}

							if (!empty($row['thesis']))			// thesis
								$record .= $row['thesis'] . ". ";

							if (!empty($row['publisher']))		// publisher
								{
									$record .= $row['publisher'];
									if (!empty($row['place']))
										$record .= ", ";
									else
										if (!ereg(",$", $row['publisher']))
											$record .= ",";
										$record .= " ";
								}

							if (!empty($row['place']))			// place
								{
									$record .= $row['place'];
									if (!empty($row['series_title']) || !empty($row['pages']))
										{
											if (!ereg(",$", $row['place']))
												$record .= ",";
											$record .= " ";
										}
								}

							if (!empty($row['series_title']))	// series title
								{
									$record .= $row['series_title'];

									// series volume & series issue will get appended only if there's also a series title(!):
									if (!empty($row['series_volume'])||!empty($row['series_issue']))
										$record .= " ";

									if (!empty($row['series_volume']))	// series volume
										$record .= $row['series_volume'];
		
									if (!empty($row['series_issue']))	// series issue
										$record .= "(" . $row['series_issue'] . ")";

									if (!empty($row['pages']))
										{
											if (!ereg(",$", $row['series_volume']))
												$record .= ",";
											$record .= " ";
										}
								}

							if (!empty($row['pages']))			// pages
								{
									if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
										// Note that we'll check for page ranges here although for whole books the 'pages' field should NOT contain a page range but the total number of pages! (like: "623 pp")
										$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
									else
										$pagesDisplay = $row['pages'];
									$record .= $pagesDisplay;
								}
						}

					// do some further cleanup:
					$record = ereg_replace("[.,][ \r\n]*$", "", $record); // remove '.' or ',' at end of line
					if ($exportFormat == "MEPS") // if '$exportFormat' = 'MEPS' ...
						$record = ereg_replace("pp ([0-9]+)", "p \\1", $record); // ... replace 'pp' with 'p' in front of (book chapter) page numbers
				}
			// --- END POLAR BIOLOGY|MARINE BIOLOGY|MEPS --------

			// --- BEGIN DEEP SEA RES ---------------------------
			elseif ($exportFormat == "Deep Sea Res") // format output according to the citation style used by the journal 'Deep Sea Research'
				{
					if ($row['type'] == "Journal Article")		// JOURNAL ARTICLE ------------------------------
						{
							if (!empty($row['author']))			// author
								{
									// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
									//   1. pattern describing old delimiter that separates different authors
									//   2. new delimiter that separates different authors
									//   3. pattern describing old delimiter that separates author name & initials (within one author)
									//   4. new delimiter that separates author name & initials (within one author)
									//   5. new delimiter that separates multiple initials (within one author)
									//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
									//   7. contents of the author field
									$author = reArrangeAuthorContents(" *; *",
																		", ",
																		" *, *",
																		", ",
																		".",
																		false,
																		$row['author']);
									$record .= $author . ", ";
								}

							if (!empty($row['year']))				// year
								$record .= "" . $row['year'] . ". ";

							if (!empty($row['title']))			// title
								{
									$record .= $row['title'];
									if (!ereg("[?!.]$", $row['title']))
										$record .= ".";
									$record .= " ";
								}

							if (!empty($row['publication']))		// publication
								$record .= $row['publication'] . " ";

							if (!empty($row['volume']))			// volume
								$record .= $row['volume'];

							if (!empty($row['issue']))			// issue
								$record .= " (" . $row['issue'] . ")";

							if ($row['online_publication'] == "yes") // this record refers to an online article
							{
								// instead of any pages info (which normally doesn't exist for online publications) we append
								// an optional string (given in 'online_citation') plus the DOI:

								if (!empty($row['online_citation']))			// online_citation
								{
									if (!empty($row['publication'])||!empty($row['volume'])||!empty($row['issue']))		// only add "," if either publication, volume or issue isn't empty
										$record .= ",";

									$record .= " " . $row['online_citation'];
								}

								if (!empty($row['doi']))			// doi
								{
									if (!empty($row['publication'])||!empty($row['volume'])||!empty($row['issue']))		// only add "," if either publication, volume or issue isn't empty
										$record .= ",";

									$record .= " doi:" . $row['doi'];
								}
							}
							else // $row['online_publication'] == "no" -> this record refers to a printed article, so we append any pages info instead:
							{
								if (!empty($row['pages']))			// pages
									{
										if (!empty($row['volume'])||!empty($row['issue']))		// only add "," if either volume or issue isn't empty
											$record .= ", ";
										if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
											$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
										else
											$pagesDisplay = $row['pages'];
										$record .= $pagesDisplay;
									}
							}
							
							if (!ereg("\. *$", $record))
								$record .= ".";
						}
					elseif ($row['type'] == "Book Chapter")		// BOOK CHAPTER ---------------------------------
						{
							if (!empty($row['author']))			// author
								{
									// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
									//   1. pattern describing old delimiter that separates different authors
									//   2. new delimiter that separates different authors
									//   3. pattern describing old delimiter that separates author name & initials (within one author)
									//   4. new delimiter that separates author name & initials (within one author)
									//   5. new delimiter that separates multiple initials (within one author)
									//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
									//   7. contents of the author field
									$author = reArrangeAuthorContents(" *; *",
																		", ",
																		" *, *",
																		", ",
																		".",
																		false,
																		$row['author']);
									$record .= $author . ", ";
								}

							if (!empty($row['year']))				// year
								$record .= "" . $row['year'] . ". ";

							if (!empty($row['title']))			// title
								{
									$record .= $row['title'];
									if (!ereg("[?!.]$", $row['title']))
										$record .= ".";
									$record .= " ";
								}

							if (!empty($row['editor']))			// editor
								{
									// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
									//   1. pattern describing old delimiter that separates different authors
									//   2. new delimiter that separates different authors
									//   3. pattern describing old delimiter that separates author name & initials (within one author)
									//   4. new delimiter that separates author name & initials (within one author)
									//   5. new delimiter that separates multiple initials (within one author)
									//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
									//   7. contents of the author field
									$editor = reArrangeAuthorContents(" *; *",
																		", ",
																		" *, *",
																		", ",
																		".",
																		false,
																		$row['editor']);
									$record .= "In: " . $editor;
									if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
										$record .= " (Eds.)";
									else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
										$record .= " (Ed.)";
								}

							$publication = ereg_replace("[ \r\n]*\(Eds?:[^\)\r\n]*\)", "", $row['publication']);
							if (!empty($publication))			// publication
								$record .= ", " . $publication . ". ";
							else
								if (!empty($row['editor']))
									$record .= ". ";

							if (!empty($row['series_title'])) // if there's a series title, series information will replace the publisher & place information
								{
									$record .= $row['series_title'];	// series title

									if (!empty($row['series_volume'])||!empty($row['series_issue']))
										$record .= " ";

									if (!empty($row['series_volume']))	// series volume
										$record .= $row['series_volume'];
		
									if (!empty($row['series_issue']))	// series issue
										$record .= "(" . $row['series_issue'] . ")";

									if (!empty($row['pages']))
										$record .= ", ";

								}
							else // if there's NO series title available, we'll insert the publisher & place instead:
								{
									if (!empty($row['publisher']))		// publisher
										{
											$record .= $row['publisher'];
											if (!empty($row['place']))
												$record .= ", ";
											else
												if (!ereg(",$", $row['publisher']))
													$record .= ",";
												$record .= " ";
										}
		
									if (!empty($row['place']))			// place
										{
											$record .= $row['place'];
											if (!empty($row['pages']))
												{
													if (!ereg(",$", $row['place']))
														$record .= ",";
													$record .= " ";
												}
										}
								}

							if (!empty($row['pages']))			// pages
								{
									if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
										$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
									else
										$pagesDisplay = $row['pages'];
									$record .= "pp. " . $pagesDisplay;
								}
							
							if (!ereg("\. *$", $record))
								$record .= ".";
						}
					elseif (ereg("Book Whole|Map|Manuscript|Journal", $row['type']))	// BOOK WHOLE -- MAP -- MANUSCRIPT --
						{
							if (!empty($row['author']))			// author
								{
									$author = ereg_replace("[ \r\n]*\(eds?\)", "", $row['author']);

									// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
									//   1. pattern describing old delimiter that separates different authors
									//   2. new delimiter that separates different authors
									//   3. pattern describing old delimiter that separates author name & initials (within one author)
									//   4. new delimiter that separates author name & initials (within one author)
									//   5. new delimiter that separates multiple initials (within one author)
									//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
									//   7. contents of the author field
									$author = reArrangeAuthorContents(" *; *",
																		", ",
																		" *, *",
																		", ",
																		".",
																		false,
																		$author);
									$record .= $author . ", ";
								}

							if (!empty($row['year']))				// year
								$record .= "" . $row['year'] . ". ";

							if (!empty($row['title']))			// title
								{
									$record .= $row['title'];
									if (!ereg("[?!.]$", $row['title']))
										$record .= ".";
									$record .= " ";
								}

							if (!empty($row['thesis']))			// thesis
								$record .= $row['thesis'] . ". ";

							if (!empty($row['publisher']))		// publisher
								{
									$record .= $row['publisher'];
									if (!empty($row['place']))
										$record .= ", ";
									else
										if (!ereg("[?!.]$", $row['publisher']))
											$record .= ". ";
										else
											$record .= " ";
								}

							if (!empty($row['place']))			// place
								{
									$record .= $row['place'];
									if (!empty($row['series_title']) || !empty($row['pages']))
										{
											if (!ereg(",$", $row['place']))
												$record .= ",";
											$record .= " ";
										}
								}

							if (!empty($row['series_title']))	// series title
								{
									$record .= $row['series_title'];

									if (!empty($row['series_volume']))	// series volume (will get appended only if there's also a series title!)
									{
										$record .= " ";
										$record .= $row['series_volume'];
									}

									if (!empty($row['pages']))
										{
											if (!ereg(",$", $row['series_volume']))
												$record .= ",";
											$record .= " ";
										}
								}

							if (!empty($row['pages']))			// pages
								{
									if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
										// Note that we'll check for page ranges here although for whole books the 'pages' field should NOT contain a page range but the total number of pages! (like: "623 pp")
										$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
									else
										$pagesDisplay = $row['pages'];
									$record .= $pagesDisplay;
								}
							
							if (!ereg("\. *$", $record))
								$record .= ".";
						}

					// do some further cleanup:
					$record = ereg_replace("[ \r\n]*$", "", $record); // remove whitespace at end of line
					$record = ereg_replace("([0-9]+) *pp\.$", "\\1pp.", $record); // remove space between (book whole) page numbers & "pp"
				}
			// --- END DEEP SEA RES -----------------------------

			// --- BEGIN TEXT CITATION --------------------------
			elseif ($exportFormat == "Text Citation") // output records suitable for citation within a text, like: "Ambrose 1991 {3735}", "Ambrose & Renaud 1995 {3243}" or "Ambrose et al. 2001 {4774}"
				{
				// currently the following parameters are not available via the GUI but are provided as fixed values here:
				$authorConnector = " & "; // string that connects first and second author (if author_count = 2)
				$etalPrintItalic = true; // specifies if "et al" should be either printed in italic (true) or as regular text (false)
				$etalWithDot = true; // specifies whether "et al" is followed by a dot (true) or not (false)
				$yearWithBrackets = false; // specifies whether the year is enclosed by a brackets (true) or not (false)
				$recordIDStartDelimiter = "{"; // specifies the string that prefixes the record id
				$recordIDEndDelimiter = "}"; // specifies the string that suffixes the record id
				
				// Call the 'extractAuthorsLastName()' function (defined in 'include.inc') to extract the last name of a particular author (specified by position). Required Parameters:
				//   1. pattern describing delimiter that separates different authors
				//   2. pattern describing delimiter that separates author name & initials (within one author)
				//   3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name)
				//   4. contents of the author field
				$record = extractAuthorsLastName(" *; *",
													" *, *",
													1,
													$row['author']);

				if ($row['author_count'] == "1") // one author, like: "Ambrose 1991 {3735}"
					if ($yearWithBrackets)
						$record .= " (" . $row['year'] . ") " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;
					else
						$record .= " " . $row['year'] . " " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;


				elseif ($row['author_count'] == "2") // two authors, like "Ambrose & Renaud 1995 {3243}"
					{
						$record .= $authorConnector;
	
						// Call the 'extractAuthorsLastName()' function (defined in 'include.inc') extract the last name of a particular author (specified by position). Required Parameters:
						//   1. pattern describing delimiter that separates different authors
						//   2. pattern describing delimiter that separates author name & initials (within one author)
						//   3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name)
						//   4. contents of the author field
						$record .= extractAuthorsLastName(" *; *",
															" *, *",
															2,
															$row['author']);
	
						if ($yearWithBrackets)
							$record .= " (" . $row['year'] . ") " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;
						else
							$record .= " " . $row['year'] . " " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;
					}

				elseif ($row['author_count'] == "3") // three or more authors, like "Ambrose et al. 2001 {4774}"
					{
						$record .= " ";

						if ($etalPrintItalic)
							$record .= "<i>";
	
						$record .= "et al";
	
						if ($etalWithDot)
							$record .= ".";
	
						if ($etalPrintItalic)
							$record .= "</i>";
	
						if ($yearWithBrackets)
							$record .= " (" . $row['year'] . ") " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;
						else
							$record .= " " . $row['year'] . " " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;
					}
				}
			// --- END TEXT CITATION ----------------------------

			// Print out the current record:
			if (!empty($record)) // unless the record buffer is empty...
			{
				if ($exportOrder == "year") // list records in blocks sorted by year:
					if (!in_array ($row['year'], $yearsArray)) // if this record's year hasn't occurred already
					{
						$yearsArray[] = $row['year']; // add it to the array of years
						echo "\n<tr>";
						echo "\n\t<td valign=\"top\" colspan=\"$NoColumns\"><h4>" . $row['year'] . "</h4></td>"; // print out a heading with the current year
						echo "\n</tr>";

					}

				echo "\n<tr>";
				echo "\n\t<td valign=\"top\">" . $record . "</td>"; // print out the record

				if ("$showLinks" == "1") // display a 'display details' link:
				{
					echo "\n\t<td valign=\"top\">";

					// print out the corresponding 'display details' link:
					if (session_is_registered("loginEmail")) // if a user is logged in, show user specific fields:
						echo "\n\t\t<a href=\"search.php?sqlQuery=SELECT%20author%2C%20title%2C%20type%2C%20year%2C%20publication%2C%20abbrev_journal%2C%20volume%2C%20issue%2C%20pages%2C%20corporate_author%2C%20thesis%2C%20address%2C%20keywords%2C%20abstract%2C%20publisher%2C%20place%2C%20editor%2C%20language%2C%20summary_language%2C%20orig_title%2C%20series_editor%2C%20series_title%2C%20abbrev_series_title%2C%20series_volume%2C%20series_issue%2C%20edition%2C%20issn%2C%20isbn%2C%20medium%2C%20area%2C%20expedition%2C%20conference%2C%20notes%2C%20approved%2C%20location%2C%20call_number%2C%20serial%2C%20marked%2C%20copy%2C%20selected%2C%20user_keys%2C%20user_notes%2C%20user_file%20"
							. "FROM%20refs%20LEFT%20JOIN%20user_data%20ON%20serial%20%3D%20record_id%20AND%20user_id%20%3D%20" . $userID . "%20";
					else // if NO user logged in, don't display any user specific fields:
						echo "\n\t\t<a href=\"search.php?sqlQuery=SELECT%20author%2C%20title%2C%20type%2C%20year%2C%20publication%2C%20abbrev_journal%2C%20volume%2C%20issue%2C%20pages%2C%20corporate_author%2C%20thesis%2C%20address%2C%20keywords%2C%20abstract%2C%20publisher%2C%20place%2C%20editor%2C%20language%2C%20summary_language%2C%20orig_title%2C%20series_editor%2C%20series_title%2C%20abbrev_series_title%2C%20series_volume%2C%20series_issue%2C%20edition%2C%20issn%2C%20isbn%2C%20medium%2C%20area%2C%20expedition%2C%20conference%2C%20notes%2C%20approved%2C%20location%2C%20call_number%2C%20serial%20"
							. "FROM%20refs%20";
	
					echo "WHERE%20serial%20RLIKE%20%22%5E%28" . $row['serial']
						. "%29%24%22%20ORDER%20BY%20" . rawurlencode($orderBy)
						. "&amp;showQuery=" . $showQuery
						. "&amp;showLinks=" . $showLinks
						. "&amp;formType=sqlSearch"
						. "&amp;submit=Display"
						. "&amp;oldQuery=" . rawurlencode($oldQuery)
						. "\"><img src=\"img/details.gif\" alt=\"details\" title=\"show details\" width=\"9\" height=\"17\" hspace=\"0\" border=\"0\"></a>";

					echo "\n\t</td>";
				}

				echo "\n</tr>";
			}
		}
		// END RESULTS DATA COLUMNS ----------------

		// BEGIN RESULTS FOOTER --------------------
		// Again, insert the (already constructed) BROWSE LINKS
		// (i.e., a TABLE row with links for "previous" & "next" browsing, as well as links to intermediate pages)
		echo $BrowseLinks;
		// END RESULTS FOOTER ----------------------
	}
	else
	{
		if ($nothingChecked == true)
			// Inform the user that no records were selected:
			echo "\n<tr>\n\t<td valign=\"top\">No records selected! Please select one or more records by clicking the appropriate checkboxes.&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>\n";
		else // $nothingChecked == false (i.e., the user did check some checkboxes) -OR- the query resulted from another script like 'show.php' (which has no checkboxes to mark!)
			// Report that nothing was found:
			echo "\n<tr>\n\t<td valign=\"top\">Sorry, but your query didn't produce any results!&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>\n";
	}// end if $rowsFound body

	// Then, finish the table
	echo "\n</table>";
	}

	// --------------------------------------------------------------------

	// RE-ARRANGE AUTHOR FIELD CONTENTS
	// (this function separates contents of the author field into their functional parts, i.e.:
	// 		{
	//			{author_name}, {author_initial(s)}
	//		}
	// 		{
	//			{author_name}, {author_initial(s)}
	//		}
	// 		{
	//			...
	//		}
	//  then, these functional pieces will be joined again according to the separators specified)
	//  Note: this function assumes that:
	//			1. within one author object, there's only *one* delimiter separating author name & initials!
	//			2. author objects are stored in the db as "<author_name><author_initials_delimiter><author_initials>", i.e., initials follow *after* the author's name!
	function reArrangeAuthorContents($oldBetweenAuthorsDelim, $newBetweenAuthorsDelim, $oldAuthorsInitialsDelim, $newAuthorsInitialsDelim, $betweenInitialsDelim, $initialsBeforeAuthor, $authorContents)
	{
		// Note: I haven't figured out how to *successfully* enable locale support, so that e.g. '[[:upper:]]' would also match 'ÿ' etc.
		//       Therefore, as a workaround, high ascii chars are specified literally below
		//       (in order to have this work, the character encoding of 'search.php' must be set to 'Western (Iso Latin 1)' aka 'ISO-8859-1'!)
		//       high ascii chars upper case = "ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆"
		//       high ascii chars lower case = "‰Â·‡‚„ÁÈËÍÎÒˆ¯ÛÚÙı¸˙˘˚ÌÏÓÔÊˇﬂ"
		// setlocale(LC_COLLATE, 'la_LN.ISO-8859-1'); // use the ISO 8859-1 Latin-1 character set  for pattern matching

		$authorsArray = split($oldBetweenAuthorsDelim, $authorContents); // get a list of all authors for this record
		
		$newAuthorsArray = array(); // initialize array variable
		foreach ($authorsArray as $singleAuthor)
			{
				$singleAuthorArray = split($oldAuthorsInitialsDelim, $singleAuthor); // for each author, extract author name & initials to separate list items


				// within initials, reduce all full first names (-> defined by a starting uppercase character, followed by one ore more lowercase characters)
				// to initials, i.e., only retain their first character
				// (as of the 2. assumption outlined in this functions header, the second element must be the author's initials)
				$singleAuthorArray[1] = preg_replace("/([[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆])[[:lower:]‰Â·‡‚„ÁÈËÍÎÒˆ¯ÛÚÙı¸˙˘˚ÌÏÓÔÊˇﬂ]+/", "\\1", $singleAuthorArray[1]);

				// within initials, remove any dots:
				$singleAuthorArray[1] = preg_replace("/([[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆])\.+/", "\\1", $singleAuthorArray[1]);

				// within initials, remove any spaces *between* initials:
				$singleAuthorArray[1] = preg_replace("/(?<=[-[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆]) +(?=[-[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆])/", "", $singleAuthorArray[1]);

				// within initials, add a space after a hyphen, but only if ...
				if (ereg(" $", $betweenInitialsDelim)) // ... the delimiter that separates initials ends with a space
					$singleAuthorArray[1] = preg_replace("/-(?=[[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆])/", "- ", $singleAuthorArray[1]);

				// then, separate initials with the specified delimiter:
				$singleAuthorArray[1] = preg_replace("/([[:upper:]ƒ≈¡¿¬√«…» À—÷ÿ”“‘’‹⁄Ÿ€ÕÃŒœ∆])/", "\\1$betweenInitialsDelim", $singleAuthorArray[1]);

	
				if ($initialsBeforeAuthor) // put array elements in reverse order:
					$singleAuthorArray = array_reverse($singleAuthorArray); // (Note: this only works, if the array has only *two* elements, i.e., one containing the author's name and one holding the initials!)
					
	
				$newAuthorsArray[] = implode($newAuthorsInitialsDelim, $singleAuthorArray); // re-join author name & initials, using the specified delimiter, and copy the string to the end of an array
			}

		$newAuthorContents = implode($newBetweenAuthorsDelim, $newAuthorsArray); // re-join authors, using the specified delimiter

		// do some final clean up:
		$newAuthorContents = preg_replace("/  +/", " ", $newAuthorContents); // remove double spaces (which occur e.g., when both, $betweenInitialsDelim & $newAuthorsInitialsDelim, end with a space)
		$newAuthorContents = preg_replace("/ +([,.;:?!])/", "\\1", $newAuthorContents); // remove spaces before [,.;:?!]
		
		// (Note: 'htmlentities()' for HTML encoding higher ASCII will only work correctly if character encoding of data is ISO-8859-1!)
		$newAuthorContents = htmlentities($newAuthorContents); // HTML encode higher ASCII characters within the newly arranged author contents

		return $newAuthorContents;
	}

	// --------------------------------------------------------------------

	//	BUILD RESULTS FOOTER
	// (i.e., build two rows with links for managing the checkboxes, as well as buttons for displaying/exporting selected records)
	function buildResultsFooter($NoColumns, $showRows, $exportFormat)
	{
		// Append a row with SELECT LINKS for managing the checkboxes
		$ResultsFooterRow = "\n<tr>";
		$ResultsFooterRow .= "\n\t<td align=\"center\" valign=\"top\" colspan=\"$NoColumns\">"
							. "\n\t\t<a href=\"JavaScript:checkall(true,'marked[]')\">Select All</a>&nbsp;&nbsp;&nbsp;"
							. "\n\t\t<a href=\"JavaScript:checkall(false,'marked[]')\">Deselect All</a>"
							. "\n\t</td>";
		$ResultsFooterRow .= "\n</tr>";

		// Append a row with BUTTONS for displaying/exporting selected records
		$ResultsFooterRow .= "\n<tr>";
		$ResultsFooterRow .= "\n\t<td align=\"center\" valign=\"top\" colspan=\"$NoColumns\">"
							. "\n\t\tSelected Records:&nbsp;&nbsp;&nbsp;&nbsp;"
							. "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Display\">&nbsp;&nbsp;&nbsp;Full Entries&nbsp;&nbsp;&nbsp;"
							. "\n\t\t<input type=\"text\" name=\"showRows\" value=\"$showRows\" size=\"4\">&nbsp;&nbsp;"
							. "\n\t\trecords per page&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;"
							. "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Export\">&nbsp;&nbsp;&nbsp;"
							. "\n\t\tin Format:&nbsp;&nbsp;"
							. "\n\t\t<select name=\"exportFormatSelector\">"
							. "\n\t\t\t<option>Polar Biol</option>"
							. "\n\t\t\t<option>Mar Biol</option>"
							. "\n\t\t\t<option>MEPS</option>"
							. "\n\t\t\t<option>Deep Sea Res</option>"
							. "\n\t\t\t<option>Text Citation</option>"
							. "\n\t\t</select>&nbsp;&nbsp;&nbsp;"
							. "\n\t\tsort by:&nbsp;&nbsp;"
							. "\n\t\t<select name=\"exportOrder\">"
							. "\n\t\t\t<option>author</option>"
							. "\n\t\t\t<option>year</option>"
							. "\n\t\t</select>"
							. "\n\t</td>";
		$ResultsFooterRow .= "\n</tr>";
		
		// Apply some search & replace in order to assign the 'selected' param to the option previously chosen by the user:
		// Note: currently, this only works when the correct 'exportFormat' name gets incorporated into an URL *manually*
		//       it doesn't work with previous & next browsing since these links actually don't submit the form (i.e., the current state of form variables won't get send)
		if (!empty($exportFormat))
			$ResultsFooterRow = ereg_replace("<option>$exportFormat", "<option selected>$exportFormat", $ResultsFooterRow);

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
		$showAuthor = $_POST['showAuthor'];
		if ("$showAuthor" == "1")
			$query .= ", author"; // add 'author' column

		// ... if the user has checked the checkbox next to 'Title', we'll add that column to the SELECT query:
		$showTitle = $_POST['showTitle'];
		if ("$showTitle" == "1")
			$query .= ", title"; // add 'title' column

		// ... if the user has checked the checkbox next to 'Year', we'll add that column to the SELECT query:
		$showYear = $_POST['showYear'];
		if ("$showYear" == "1")
			$query .= ", year"; // add 'year' column

		// ... if the user has checked the checkbox next to 'Publication', we'll add that column to the SELECT query:
		$showPublication = $_POST['showPublication'];
		if ("$showPublication" == "1")
			$query .= ", publication"; // add 'publication' column

		// ... if the user has checked the checkbox next to 'Volume', we'll add that column to the SELECT query:
		$showVolume = $_POST['showVolume'];
		if ("$showVolume" == "1")
			$query .= ", volume"; // add 'volume' column

		// ... if the user has checked the checkbox next to 'Pages', we'll add that column to the SELECT query:
		$showPages = $_POST['showPages'];
		if ("$showPages" == "1")
			$query .= ", pages"; // add 'pages' column

		// ... we still have to trap the case that the (silly!) user hasn't checked any of the column checkboxes above:
		if ($query == "SELECT")
			$query .= " author"; // force add 'author' column if the user hasn't checked any of the column checkboxes

		$query .= ", orig_record"; // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
								//  (which is required in order to present visual feedback on duplicate records)

		$query .= ", serial"; // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
							//  (which is required in order to obtain unique checkbox names)

		if ("$showLinks" == "1")
			$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

		// Finally, fix the wrong syntax where its says "SELECT, author, title, ..." instead of "SELECT author, title, ..."
		$query = str_replace("SELECT, ","SELECT ",$query);

		// Note: since we won't query any user specific fields (like 'marked', 'copy', 'selected', 'user_keys', 'user_notes' or 'user_file') we skip the 'LEFT JOIN...' part of the 'FROM' clause:
		$query .= " FROM refs WHERE serial RLIKE \".+\""; // add FROM & (initial) WHERE clause
		
		// ---------------------------------------

		// ... if the user has specified an author, add the value of '$authorName' as an AND clause:
		$authorName = $_POST['authorName'];
		if ("$authorName" != "")
			{
				$authorSelector = $_POST['authorSelector'];
				if ("$authorSelector" == "contains")
					$query .= " AND author RLIKE \"$authorName\"";
				elseif ("$authorSelector" == "does not contain")
					$query .= " AND author NOT RLIKE \"$authorName\"";
				elseif ("$authorSelector" == "is equal to")
					$query .= " AND author = \"$authorName\"";
				elseif ("$authorSelector" == "is not equal to")
					$query .= " AND author != \"$authorName\"";
				elseif ("$authorSelector" == "starts with")
					$query .= " AND author RLIKE \"^$authorName\"";
				elseif ("$authorSelector" == "ends with")
					$query .= " AND author RLIKE \"$authorName$\"";
			}

		// ... if the user has specified a title, add the value of '$titleName' as an AND clause:
		$titleName = $_POST['titleName'];
		if ("$titleName" != "")
			{
				$titleSelector = $_POST['titleSelector'];
				if ("$titleSelector" == "contains")
					$query .= " AND title RLIKE \"$titleName\"";
				elseif ("$titleSelector" == "does not contain")
					$query .= " AND title NOT RLIKE \"$titleName\"";
				elseif ("$titleSelector" == "is equal to")
					$query .= " AND title = \"$titleName\"";
				elseif ("$titleSelector" == "is not equal to")
					$query .= " AND title != \"$titleName\"";
				elseif ("$titleSelector" == "starts with")
					$query .= " AND title RLIKE \"^$titleName\"";
				elseif ("$titleSelector" == "ends with")
					$query .= " AND title RLIKE \"$titleName$\"";
			}
	
		// ... if the user has specified a year, add the value of '$yearNo' as an AND clause:
		$yearNo = $_POST['yearNo'];
		if ("$yearNo" != "")
			{
				$yearSelector = $_POST['yearSelector'];
				if ("$yearSelector" == "contains")
					$query .= " AND year RLIKE \"$yearNo\"";
				elseif ("$yearSelector" == "does not contain")
					$query .= " AND year NOT RLIKE \"$yearNo\"";
				elseif ("$yearSelector" == "is equal to")
					$query .= " AND year = \"$yearNo\"";
				elseif ("$yearSelector" == "is not equal to")
					$query .= " AND year != \"$yearNo\"";
				elseif ("$yearSelector" == "starts with")
					$query .= " AND year RLIKE \"^$yearNo\"";
				elseif ("$yearSelector" == "ends with")
					$query .= " AND year RLIKE \"$yearNo$\"";
				elseif ("$yearSelector" == "is greater than")
					$query .= " AND year > \"$yearNo\"";
				elseif ("$yearSelector" == "is less than")
					$query .= " AND year < \"$yearNo\"";
			}
	
		// ... if the user has specified a publication, add the value of '$publicationName' as an AND clause:
		$publicationRadio = $_POST['publicationRadio'];
		if ("$publicationRadio" == "1")
		{
			$publicationName = $_POST['publicationName'];
			if ("$publicationName" != "All" && "$publicationName" != "")
				{
					$publicationSelector = $_POST['publicationSelector'];
					if ("$publicationSelector" == "contains")
						$query .= " AND publication RLIKE \"$publicationName\"";
					elseif ("$publicationSelector" == "does not contain")
						$query .= " AND publication NOT RLIKE \"$publicationName\"";
					elseif ("$publicationSelector" == "is equal to")
						$query .= " AND publication = \"$publicationName\"";
					elseif ("$publicationSelector" == "is not equal to")
						$query .= " AND publication != \"$publicationName\"";
					elseif ("$publicationSelector" == "starts with")
						$query .= " AND publication RLIKE \"^$publicationName\"";
					elseif ("$publicationSelector" == "ends with")
						$query .= " AND publication RLIKE \"$publicationName$\"";
				}
		}
		elseif ("$publicationRadio" == "0")
		{
			$publicationName2 = $_POST['publicationName2'];
			if ("$publicationName2" != "")
				{
					$publicationSelector2 = $_POST['publicationSelector2'];
					if ("$publicationSelector2" == "contains")
						$query .= " AND publication RLIKE \"$publicationName2\"";
					elseif ("$publicationSelector2" == "does not contain")
						$query .= " AND publication NOT RLIKE \"$publicationName2\"";
					elseif ("$publicationSelector2" == "is equal to")
						$query .= " AND publication = \"$publicationName2\"";
					elseif ("$publicationSelector2" == "is not equal to")
						$query .= " AND publication != \"$publicationName2\"";
					elseif ("$publicationSelector2" == "starts with")
						$query .= " AND publication RLIKE \"^$publicationName2\"";
					elseif ("$publicationSelector2" == "ends with")
						$query .= " AND publication RLIKE \"$publicationName2$\"";
				}
		}
	
		// ... if the user has specified a volume, add the value of '$volumeNo' as an AND clause:
		$volumeNo = $_POST['volumeNo'];
		if ("$volumeNo" != "")
			{
				$volumeSelector = $_POST['volumeSelector'];
				if ("$volumeSelector" == "contains")
					$query .= " AND volume RLIKE \"$volumeNo\"";
				elseif ("$volumeSelector" == "does not contain")
					$query .= " AND volume NOT RLIKE \"$volumeNo\"";
				elseif ("$volumeSelector" == "is equal to")
					$query .= " AND volume = \"$volumeNo\"";
				elseif ("$volumeSelector" == "is not equal to")
					$query .= " AND volume != \"$volumeNo\"";
				elseif ("$volumeSelector" == "starts with")
					$query .= " AND volume RLIKE \"^$volumeNo\"";
				elseif ("$volumeSelector" == "ends with")
					$query .= " AND volume RLIKE \"$volumeNo$\"";
				elseif ("$volumeSelector" == "is greater than")
					$query .= " AND volume > \"$volumeNo\"";
				elseif ("$volumeSelector" == "is less than")
					$query .= " AND volume < \"$volumeNo\"";
			}
	
		// ... if the user has specified some pages, add the value of '$pagesNo' as an AND clause:
		$pagesNo = $_POST['pagesNo'];
		if ("$pagesNo" != "")
			{
				$pagesSelector = $_POST['pagesSelector'];
				if ("$pagesSelector" == "contains")
					$query .= " AND pages RLIKE \"$pagesNo\"";
				elseif ("$pagesSelector" == "does not contain")
					$query .= " AND pages NOT RLIKE \"$pagesNo\"";
				elseif ("$pagesSelector" == "is equal to")
					$query .= " AND pages = \"$pagesNo\"";
				elseif ("$pagesSelector" == "is not equal to")
					$query .= " AND pages != \"$pagesNo\"";
				elseif ("$pagesSelector" == "starts with")
					$query .= " AND pages RLIKE \"^$pagesNo\"";
				elseif ("$pagesSelector" == "ends with")
					$query .= " AND pages RLIKE \"$pagesNo$\"";
			}


		// Construct the ORDER BY clause:
		// A) extract first level sort option:
		$sortSelector1 = $_POST['sortSelector1'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector1 = str_replace("pages", "first_page", $sortSelector1);

		$sortRadio1 = $_POST['sortRadio1'];
		if ("$sortRadio1" == "0") // sort ascending
			$query .= " ORDER BY $sortSelector1";
		else // sort descending
			$query .= " ORDER BY $sortSelector1 DESC";

		// B) extract second level sort option:
		$sortSelector2 = $_POST['sortSelector2'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector2 = str_replace("pages", "first_page", $sortSelector2);

		$sortRadio2 = $_POST['sortRadio2'];
		if ("$sortRadio2" == "0") // sort ascending
			$query .= ", $sortSelector2";
		else // sort descending
			$query .= ", $sortSelector2 DESC";

		// C) extract third level sort option:
		$sortSelector3 = $_POST['sortSelector3'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector3 = str_replace("pages", "first_page", $sortSelector3);

		$sortRadio3 = $_POST['sortRadio3'];
		if ("$sortRadio3" == "0") // sort ascending
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
		$showAuthor = $_POST['showAuthor'];
		if ("$showAuthor" == "1")
			$query .= ", author"; // add 'author' column

		// ... if the user has checked the checkbox next to 'Title', we'll add that column to the SELECT query:
		$showTitle = $_POST['showTitle'];
		if ("$showTitle" == "1")
			$query .= ", title"; // add 'title' column

		// ... if the user has checked the checkbox next to 'Year', we'll add that column to the SELECT query:
		$showYear = $_POST['showYear'];
		if ("$showYear" == "1")
			$query .= ", year"; // add 'year' column

		// ... if the user has checked the checkbox next to 'Editor', we'll add that column to the SELECT query:
		$showEditor = $_POST['showEditor'];
		if ("$showEditor" == "1")
			$query .= ", editor"; // add 'editor' column

		// ... if the user has checked the checkbox next to 'Series', we'll add that column to the SELECT query:
		$showSeriesTitle = $_POST['showSeriesTitle'];
		if ("$showSeriesTitle" == "1")
			$query .= ", series_title"; // add 'series_title' column

		// ... if the user has checked the checkbox next to 'Volume', we'll add that column to the SELECT query:
		$showVolume = $_POST['showVolume'];
		if ("$showVolume" == "1")
			$query .= ", series_volume"; // add 'series_volume' column

		// ... if the user has checked the checkbox next to 'Pages', we'll add that column to the SELECT query:
		$showPages = $_POST['showPages'];
		if ("$showPages" == "1")
			$query .= ", pages"; // add 'pages' column

		// ... if the user has checked the checkbox next to 'Publisher', we'll add that column to the SELECT query:
		$showPublisher = $_POST['showPublisher'];
		if ("$showPublisher" == "1")
			$query .= ", publisher"; // add 'publisher' column

		// ... if the user has checked the checkbox next to 'Place', we'll add that column to the SELECT query:
		$showPlace = $_POST['showPlace'];
		if ("$showPlace" == "1")
			$query .= ", place"; // add 'place' column

		// ... if the user has checked the checkbox next to 'Signature', we'll add that column to the SELECT query:
		$showCallNumber = $_POST['showCallNumber'];
		if ("$showCallNumber" == "1")
			$query .= ", call_number"; // add 'call_number' column

		// ... if the user has checked the checkbox next to 'Keywords', we'll add that column to the SELECT query:
		$showKeywords = $_POST['showKeywords'];
		if ("$showKeywords" == "1")
			$query .= ", keywords"; // add 'keywords' column

		// ... if the user has checked the checkbox next to 'Notes', we'll add that column to the SELECT query:
		$showNotes = $_POST['showNotes'];
		if ("$showNotes" == "1")
			$query .= ", notes"; // add 'notes' column

		// ... we still have to trap the case that the (silly!) user hasn't checked any of the column checkboxes above:
		if ($query == "SELECT")
			$query .= " author"; // force add 'author' column if the user hasn't checked any of the column checkboxes

		$query .= ", orig_record"; // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
								//  (which is required in order to present visual feedback on duplicate records)

		$query .= ", serial"; // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
							//  (which is required in order to obtain unique checkbox names)

		if ("$showLinks" == "1")
			$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

		// Finally, fix the wrong syntax where its says "SELECT, author, title, ..." instead of "SELECT author, title, ..."
		$query = str_replace("SELECT, ","SELECT ",$query);

		// Note: since we won't query any user specific fields (like 'marked', 'copy', 'selected', 'user_keys', 'user_notes' or 'user_file') we skip the 'LEFT JOIN...' part of the 'FROM' clause:
		$query .= " FROM refs WHERE serial RLIKE \".+\" AND location RLIKE \"IP÷ Library\""; // add FROM & (initial) WHERE clause
		
		// ---------------------------------------

		// ... if the user has specified an author, add the value of '$authorName' as an AND clause:
		$authorName = $_POST['authorName'];
		if ("$authorName" != "")
			{
				$authorSelector = $_POST['authorSelector'];
				if ("$authorSelector" == "contains")
					$query .= " AND author RLIKE \"$authorName\"";
				elseif ("$authorSelector" == "does not contain")
					$query .= " AND author NOT RLIKE \"$authorName\"";
				elseif ("$authorSelector" == "is equal to")
					$query .= " AND author = \"$authorName\"";
				elseif ("$authorSelector" == "is not equal to")
					$query .= " AND author != \"$authorName\"";
				elseif ("$authorSelector" == "starts with")
					$query .= " AND author RLIKE \"^$authorName\"";
				elseif ("$authorSelector" == "ends with")
					$query .= " AND author RLIKE \"$authorName$\"";
			}

		// ... if the user has specified a title, add the value of '$titleName' as an AND clause:
		$titleName = $_POST['titleName'];
		if ("$titleName" != "")
			{
				$titleSelector = $_POST['titleSelector'];
				if ("$titleSelector" == "contains")
					$query .= " AND title RLIKE \"$titleName\"";
				elseif ("$titleSelector" == "does not contain")
					$query .= " AND title NOT RLIKE \"$titleName\"";
				elseif ("$titleSelector" == "is equal to")
					$query .= " AND title = \"$titleName\"";
				elseif ("$titleSelector" == "is not equal to")
					$query .= " AND title != \"$titleName\"";
				elseif ("$titleSelector" == "starts with")
					$query .= " AND title RLIKE \"^$titleName\"";
				elseif ("$titleSelector" == "ends with")
					$query .= " AND title RLIKE \"$titleName$\"";
			}
	
		// ... if the user has specified a year, add the value of '$yearNo' as an AND clause:
		$yearNo = $_POST['yearNo'];
		if ("$yearNo" != "")
			{
				$yearSelector = $_POST['yearSelector'];
				if ("$yearSelector" == "contains")
					$query .= " AND year RLIKE \"$yearNo\"";
				elseif ("$yearSelector" == "does not contain")
					$query .= " AND year NOT RLIKE \"$yearNo\"";
				elseif ("$yearSelector" == "is equal to")
					$query .= " AND year = \"$yearNo\"";
				elseif ("$yearSelector" == "is not equal to")
					$query .= " AND year != \"$yearNo\"";
				elseif ("$yearSelector" == "starts with")
					$query .= " AND year RLIKE \"^$yearNo\"";
				elseif ("$yearSelector" == "ends with")
					$query .= " AND year RLIKE \"$yearNo$\"";
				elseif ("$yearSelector" == "is greater than")
					$query .= " AND year > \"$yearNo\"";
				elseif ("$yearSelector" == "is less than")
					$query .= " AND year < \"$yearNo\"";
			}
	
		// ... if the user has specified an editor, add the value of '$editorName' as an AND clause:
		$editorName = $_POST['editorName'];
		if ("$editorName" != "")
			{
				$editorSelector = $_POST['editorSelector'];
				if ("$editorSelector" == "contains")
					$query .= " AND editor RLIKE \"$editorName\"";
				elseif ("$editorSelector" == "does not contain")
					$query .= " AND editor NOT RLIKE \"$editorName\"";
				elseif ("$editorSelector" == "is equal to")
					$query .= " AND editor = \"$editorName\"";
				elseif ("$editorSelector" == "is not equal to")
					$query .= " AND editor != \"$editorName\"";
				elseif ("$editorSelector" == "starts with")
					$query .= " AND editor RLIKE \"^$editorName\"";
				elseif ("$editorSelector" == "ends with")
					$query .= " AND editor RLIKE \"$editorName$\"";
			}

		// ... if the user has specified a series title, add the value of '$seriesTitleName' as an AND clause:
		$seriesTitleRadio = $_POST['seriesTitleRadio'];
		if ("$seriesTitleRadio" == "1")
		{
			$seriesTitleName = $_POST['seriesTitleName'];
			if ("$seriesTitleName" != "All" && "$seriesTitleName" != "")
				{
					$seriesTitleSelector = $_POST['seriesTitleSelector'];
					if ("$seriesTitleSelector" == "contains")
						$query .= " AND series_title RLIKE \"$seriesTitleName\"";
					elseif ("$seriesTitleSelector" == "does not contain")
						$query .= " AND series_title NOT RLIKE \"$seriesTitleName\"";
					elseif ("$seriesTitleSelector" == "is equal to")
						$query .= " AND series_title = \"$seriesTitleName\"";
					elseif ("$seriesTitleSelector" == "is not equal to")
						$query .= " AND series_title != \"$seriesTitleName\"";
					elseif ("$seriesTitleSelector" == "starts with")
						$query .= " AND series_title RLIKE \"^$seriesTitleName\"";
					elseif ("$seriesTitleSelector" == "ends with")
						$query .= " AND series_title RLIKE \"$seriesTitleName$\"";
				}
		}
		elseif ("$seriesTitleRadio" == "0")
		{
			$seriesTitleName2 = $_POST['seriesTitleName2'];
			if ("$seriesTitleName2" != "")
				{
					$seriesTitleSelector2 = $_POST['seriesTitleSelector2'];
					if ("$seriesTitleSelector2" == "contains")
						$query .= " AND series_title RLIKE \"$seriesTitleName2\"";
					elseif ("$seriesTitleSelector2" == "does not contain")
						$query .= " AND series_title NOT RLIKE \"$seriesTitleName2\"";
					elseif ("$seriesTitleSelector2" == "is equal to")
						$query .= " AND series_title = \"$seriesTitleName2\"";
					elseif ("$seriesTitleSelector2" == "is not equal to")
						$query .= " AND series_title != \"$seriesTitleName2\"";
					elseif ("$seriesTitleSelector2" == "starts with")
						$query .= " AND series_title RLIKE \"^$seriesTitleName2\"";
					elseif ("$seriesTitleSelector2" == "ends with")
						$query .= " AND series_title RLIKE \"$seriesTitleName2$\"";
				}
		}
	
		// ... if the user has specified a series volume, add the value of '$volumeNo' as an AND clause:
		$volumeNo = $_POST['volumeNo'];
		if ("$volumeNo" != "")
			{
				$volumeSelector = $_POST['volumeSelector'];
				if ("$volumeSelector" == "contains")
					$query .= " AND series_volume RLIKE \"$volumeNo\"";
				elseif ("$volumeSelector" == "does not contain")
					$query .= " AND series_volume NOT RLIKE \"$volumeNo\"";
				elseif ("$volumeSelector" == "is equal to")
					$query .= " AND series_volume = \"$volumeNo\"";
				elseif ("$volumeSelector" == "is not equal to")
					$query .= " AND series_volume != \"$volumeNo\"";
				elseif ("$volumeSelector" == "starts with")
					$query .= " AND series_volume RLIKE \"^$volumeNo\"";
				elseif ("$volumeSelector" == "ends with")
					$query .= " AND series_volume RLIKE \"$volumeNo$\"";
				elseif ("$volumeSelector" == "is greater than")
					$query .= " AND series_volume > \"$volumeNo\"";
				elseif ("$volumeSelector" == "is less than")
					$query .= " AND series_volume < \"$volumeNo\"";
			}
	
		// ... if the user has specified some pages, add the value of '$pagesNo' as an AND clause:
		$pagesNo = $_POST['pagesNo'];
		if ("$pagesNo" != "")
			{
				$pagesSelector = $_POST['pagesSelector'];
				if ("$pagesSelector" == "contains")
					$query .= " AND pages RLIKE \"$pagesNo\"";
				elseif ("$pagesSelector" == "does not contain")
					$query .= " AND pages NOT RLIKE \"$pagesNo\"";
				elseif ("$pagesSelector" == "is equal to")
					$query .= " AND pages = \"$pagesNo\"";
				elseif ("$pagesSelector" == "is not equal to")
					$query .= " AND pages != \"$pagesNo\"";
				elseif ("$pagesSelector" == "starts with")
					$query .= " AND pages RLIKE \"^$pagesNo\"";
				elseif ("$pagesSelector" == "ends with")
					$query .= " AND pages RLIKE \"$pagesNo$\"";
			}
	
		// ... if the user has specified a publisher, add the value of '$publisherName' as an AND clause:
		$publisherName = $_POST['publisherName'];
		if ("$publisherName" != "")
			{
				$publisherSelector = $_POST['publisherSelector'];
				if ("$publisherSelector" == "contains")
					$query .= " AND publisher RLIKE \"$publisherName\"";
				elseif ("$publisherSelector" == "does not contain")
					$query .= " AND publisher NOT RLIKE \"$publisherName\"";
				elseif ("$publisherSelector" == "is equal to")
					$query .= " AND publisher = \"$publisherName\"";
				elseif ("$publisherSelector" == "is not equal to")
					$query .= " AND publisher != \"$publisherName\"";
				elseif ("$publisherSelector" == "starts with")
					$query .= " AND publisher RLIKE \"^$publisherName\"";
				elseif ("$publisherSelector" == "ends with")
					$query .= " AND publisher RLIKE \"$publisherName$\"";
			}

		// ... if the user has specified a place, add the value of '$placeName' as an AND clause:
		$placeName = $_POST['placeName'];
		if ("$placeName" != "")
			{
				$placeSelector = $_POST['placeSelector'];
				if ("$placeSelector" == "contains")
					$query .= " AND place RLIKE \"$placeName\"";
				elseif ("$placeSelector" == "does not contain")
					$query .= " AND place NOT RLIKE \"$placeName\"";
				elseif ("$placeSelector" == "is equal to")
					$query .= " AND place = \"$placeName\"";
				elseif ("$placeSelector" == "is not equal to")
					$query .= " AND place != \"$placeName\"";
				elseif ("$placeSelector" == "starts with")
					$query .= " AND place RLIKE \"^$placeName\"";
				elseif ("$placeSelector" == "ends with")
					$query .= " AND place RLIKE \"$placeName$\"";
			}

		// ... if the user has specified a call number, add the value of '$callNumberName' as an AND clause:
		$callNumberName = $_POST['callNumberName'];
		if ("$callNumberName" != "")
			{
				$callNumberSelector = $_POST['callNumberSelector'];
				if ("$callNumberSelector" == "contains")
					$query .= " AND call_number RLIKE \"$callNumberName\"";
				elseif ("$callNumberSelector" == "does not contain")
					$query .= " AND call_number NOT RLIKE \"$callNumberName\"";
				elseif ("$callNumberSelector" == "is equal to")
					$query .= " AND call_number = \"$callNumberName\"";
				elseif ("$callNumberSelector" == "is not equal to")
					$query .= " AND call_number != \"$callNumberName\"";
				elseif ("$callNumberSelector" == "starts with")
					$query .= " AND call_number RLIKE \"^$callNumberName\"";
				elseif ("$callNumberSelector" == "ends with")
					$query .= " AND call_number RLIKE \"$callNumberName$\"";
			}

		// ... if the user has specified some keywords, add the value of '$keywordsName' as an AND clause:
		$keywordsName = $_POST['keywordsName'];
		if ("$keywordsName" != "")
			{
				$keywordsSelector = $_POST['keywordsSelector'];
				if ("$keywordsSelector" == "contains")
					$query .= " AND keywords RLIKE \"$keywordsName\"";
				elseif ("$keywordsSelector" == "does not contain")
					$query .= " AND keywords NOT RLIKE \"$keywordsName\"";
				elseif ("$keywordsSelector" == "is equal to")
					$query .= " AND keywords = \"$keywordsName\"";
				elseif ("$keywordsSelector" == "is not equal to")
					$query .= " AND keywords != \"$keywordsName\"";
				elseif ("$keywordsSelector" == "starts with")
					$query .= " AND keywords RLIKE \"^$keywordsName\"";
				elseif ("$keywordsSelector" == "ends with")
					$query .= " AND keywords RLIKE \"$keywordsName$\"";
			}

		// ... if the user has specified some notes, add the value of '$notesName' as an AND clause:
		$notesName = $_POST['notesName'];
		if ("$notesName" != "")
			{
				$notesSelector = $_POST['notesSelector'];
				if ("$notesSelector" == "contains")
					$query .= " AND notes RLIKE \"$notesName\"";
				elseif ("$notesSelector" == "does not contain")
					$query .= " AND notes NOT RLIKE \"$notesName\"";
				elseif ("$notesSelector" == "is equal to")
					$query .= " AND notes = \"$notesName\"";
				elseif ("$notesSelector" == "is not equal to")
					$query .= " AND notes != \"$notesName\"";
				elseif ("$notesSelector" == "starts with")
					$query .= " AND notes RLIKE \"^$notesName\"";
				elseif ("$notesSelector" == "ends with")
					$query .= " AND notes RLIKE \"$notesName$\"";
			}


		// Construct the ORDER BY clause:
		// A) extract first level sort option:
		$sortSelector1 = $_POST['sortSelector1'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector1 = str_replace("pages", "first_page", $sortSelector1);

		$sortRadio1 = $_POST['sortRadio1'];
		if ("$sortRadio1" == "0") // sort ascending
			$query .= " ORDER BY $sortSelector1";
		else // sort descending
			$query .= " ORDER BY $sortSelector1 DESC";

		// B) extract second level sort option:
		$sortSelector2 = $_POST['sortSelector2'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector2 = str_replace("pages", "first_page", $sortSelector2);

		$sortRadio2 = $_POST['sortRadio2'];
		if ("$sortRadio2" == "0") // sort ascending
			$query .= ", $sortSelector2";
		else // sort descending
			$query .= ", $sortSelector2 DESC";

		// C) extract third level sort option:
		$sortSelector3 = $_POST['sortSelector3'];
		// when field name = 'pages' then sort by 'first_page' instead:
		$sortSelector3 = str_replace("pages", "first_page", $sortSelector3);

		$sortRadio3 = $_POST['sortRadio3'];
		if ("$sortRadio3" == "0") // sort ascending
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
		$showAuthor = $_POST['showAuthor'];
		if ("$showAuthor" == "1")
			$query .= ", author"; // add 'author' column

		// ... if the user has checked the checkbox next to 'Address', we'll add that column to the SELECT query:
		$showAddress = $_POST['showAddress'];
		if ("$showAddress" == "1")
			$query .= ", address"; // add 'address' column

		// ... if the user has checked the checkbox next to 'Corporate Author', we'll add that column to the SELECT query:
		$showCorporateAuthor = $_POST['showCorporateAuthor'];
		if ("$showCorporateAuthor" == "1")
			$query .= ", corporate_author"; // add 'corporate_author' column

		// ... if the user has checked the checkbox next to 'Thesis', we'll add that column to the SELECT query:
		$showThesis = $_POST['showThesis'];
		if ("$showThesis" == "1")
			$query .= ", thesis"; // add 'thesis' column

		// ... if the user has checked the checkbox next to 'Title', we'll add that column to the SELECT query:
		$showTitle = $_POST['showTitle'];
		if ("$showTitle" == "1")
			$query .= ", title"; // add 'title' column

		// ... if the user has checked the checkbox next to 'Original Title', we'll add that column to the SELECT query:
		$showOrigTitle = $_POST['showOrigTitle'];
		if ("$showOrigTitle" == "1")
			$query .= ", orig_title"; // add 'orig_title' column

		// ... if the user has checked the checkbox next to 'Year', we'll add that column to the SELECT query:
		$showYear = $_POST['showYear'];
		if ("$showYear" == "1")
			$query .= ", year"; // add 'year' column

		// ... if the user has checked the checkbox next to 'Publication', we'll add that column to the SELECT query:
		$showPublication = $_POST['showPublication'];
		if ("$showPublication" == "1")
			$query .= ", publication"; // add 'publication' column

		// ... if the user has checked the checkbox next to 'Abbreviated Journal', we'll add that column to the SELECT query:
		$showAbbrevJournal = $_POST['showAbbrevJournal'];
		if ("$showAbbrevJournal" == "1")
			$query .= ", abbrev_journal"; // add 'abbrev_journal' column

		// ... if the user has checked the checkbox next to 'Editor', we'll add that column to the SELECT query:
		$showEditor = $_POST['showEditor'];
		if ("$showEditor" == "1")
			$query .= ", editor"; // add 'editor' column

		// ... if the user has checked the checkbox next to 'Volume', we'll add that column to the SELECT query:
		$showVolume = $_POST['showVolume'];
		if ("$showVolume" == "1")
			$query .= ", volume"; // add 'volume' column

		// ... if the user has checked the checkbox next to 'Issue', we'll add that column to the SELECT query:
		$showIssue = $_POST['showIssue'];
		if ("$showIssue" == "1")
			$query .= ", issue"; // add 'issue' column

		// ... if the user has checked the checkbox next to 'Pages', we'll add that column to the SELECT query:
		$showPages = $_POST['showPages'];
		if ("$showPages" == "1")
			$query .= ", pages"; // add 'pages' column

		// ... if the user has checked the checkbox next to 'Series', we'll add that column to the SELECT query:
		$showSeriesTitle = $_POST['showSeriesTitle'];
		if ("$showSeriesTitle" == "1")
			$query .= ", series_title"; // add 'series_title' column

		// ... if the user has checked the checkbox next to 'Abbreviated Series Title', we'll add that column to the SELECT query:
		$showAbbrevSeriesTitle = $_POST['showAbbrevSeriesTitle'];
		if ("$showAbbrevSeriesTitle" == "1")
			$query .= ", abbrev_series_Title"; // add 'abbrev_series_Title' column

		// ... if the user has checked the checkbox next to 'Series Editor', we'll add that column to the SELECT query:
		$showSeriesEditor = $_POST['showSeriesEditor'];
		if ("$showSeriesEditor" == "1")
			$query .= ", series_editor"; // add 'series_editor' column

		// ... if the user has checked the checkbox next to 'Series Volume', we'll add that column to the SELECT query:
		$showVolume = $_POST['showSeriesVolume'];
		if ("$showSeriesVolume" == "1")
			$query .= ", series_volume"; // add 'series_volume' column

		// ... if the user has checked the checkbox next to 'Series Issue', we'll add that column to the SELECT query:
		$showSeriesIssue = $_POST['showSeriesIssue'];
		if ("$showSeriesIssue" == "1")
			$query .= ", series_issue"; // add 'series_issue' column

		// ... if the user has checked the checkbox next to 'Publisher', we'll add that column to the SELECT query:
		$showPublisher = $_POST['showPublisher'];
		if ("$showPublisher" == "1")
			$query .= ", publisher"; // add 'publisher' column

		// ... if the user has checked the checkbox next to 'Place of Publication', we'll add that column to the SELECT query:
		$showPlace = $_POST['showPlace'];
		if ("$showPlace" == "1")
			$query .= ", place"; // add 'place' column

		// ... if the user has checked the checkbox next to 'Edition', we'll add that column to the SELECT query:
		$showEdition = $_POST['showEdition'];
		if ("$showEdition" == "1")
			$query .= ", edition"; // add 'edition' column

		// ... if the user has checked the checkbox next to 'Medium', we'll add that column to the SELECT query:
		$showMedium = $_POST['showMedium'];
		if ("$showMedium" == "1")
			$query .= ", medium"; // add 'medium' column

		// ... if the user has checked the checkbox next to 'ISSN', we'll add that column to the SELECT query:
		$showISSN = $_POST['showISSN'];
		if ("$showISSN" == "1")
			$query .= ", issn"; // add 'issn' column

		// ... if the user has checked the checkbox next to 'ISBN', we'll add that column to the SELECT query:
		$showISBN = $_POST['showISBN'];
		if ("$showISBN" == "1")
			$query .= ", isbn"; // add 'isbn' column

		// ... if the user has checked the checkbox next to 'Language', we'll add that column to the SELECT query:
		$showLanguage = $_POST['showLanguage'];
		if ("$showLanguage" == "1")
			$query .= ", language"; // add 'language' column

		// ... if the user has checked the checkbox next to 'Summary Language', we'll add that column to the SELECT query:
		$showSummaryLanguage = $_POST['showSummaryLanguage'];
		if ("$showSummaryLanguage" == "1")
			$query .= ", summary_language"; // add 'summary_language' column

		// ... if the user has checked the checkbox next to 'Keywords', we'll add that column to the SELECT query:
		$showKeywords = $_POST['showKeywords'];
		if ("$showKeywords" == "1")
			$query .= ", keywords"; // add 'keywords' column

		// ... if the user has checked the checkbox next to 'Abstract', we'll add that column to the SELECT query:
		$showAbstract = $_POST['showAbstract'];
		if ("$showAbstract" == "1")
			$query .= ", abstract"; // add 'abstract' column

		// ... if the user has checked the checkbox next to 'Area', we'll add that column to the SELECT query:
		$showArea = $_POST['showArea'];
		if ("$showArea" == "1")
			$query .= ", area"; // add 'area' column

		// ... if the user has checked the checkbox next to 'Expedition', we'll add that column to the SELECT query:
		$showExpedition = $_POST['showExpedition'];
		if ("$showExpedition" == "1")
			$query .= ", expedition"; // add 'expedition' column

		// ... if the user has checked the checkbox next to 'Conference', we'll add that column to the SELECT query:
		$showConference = $_POST['showConference'];
		if ("$showConference" == "1")
			$query .= ", conference"; // add 'conference' column

		// ... if the user has checked the checkbox next to 'DOI', we'll add that column to the SELECT query:
		$showDOI = $_POST['showDOI'];
		if ("$showDOI" == "1")
			$query .= ", doi"; // add 'doi' column

		// ... if the user has checked the checkbox next to 'URL', we'll add that column to the SELECT query:
		$showURL = $_POST['showURL'];
		if ("$showURL" == "1")
			$query .= ", url"; // add 'url' column

		// ... if the user has checked the checkbox next to 'Location', we'll add that column to the SELECT query:
		$showLocation = $_POST['showLocation'];
		if ("$showLocation" == "1")
			$query .= ", location"; // add 'location' column

		// ... if the user has checked the checkbox next to 'Call Number', we'll add that column to the SELECT query:
		$showCallNumber = $_POST['showCallNumber'];
		if ("$showCallNumber" == "1")
			$query .= ", call_number"; // add 'call_number' column

		// ... if the user has checked the checkbox next to 'File Name', we'll add that column to the SELECT query:
		$showFile = $_POST['showFile'];
		if ("$showFile" == "1")
			$query .= ", file"; // add 'file' column

		// ... if the user has checked the checkbox next to 'Copy', we'll add that column to the SELECT query:
		$showCopy = $_POST['showCopy'];
		if ("$showCopy" == "1")
			$query .= ", copy"; // add 'copy' column

		// ... if the user has checked the checkbox next to 'Notes', we'll add that column to the SELECT query:
		$showNotes = $_POST['showNotes'];
		if ("$showNotes" == "1")
			$query .= ", notes"; // add 'notes' column

		// ... if the user has checked the checkbox next to 'User Keys', we'll add that column to the SELECT query:
		$showUserKeys = $_POST['showUserKeys'];
		if ("$showUserKeys" == "1")
			$query .= ", user_keys"; // add 'user_keys' column

		// ... if the user has checked the checkbox next to 'User Notes', we'll add that column to the SELECT query:
		$showUserNotes = $_POST['showUserNotes'];
		if ("$showUserNotes" == "1")
			$query .= ", user_notes"; // add 'user_notes' column

		// ... if the user has checked the checkbox next to 'User File', we'll add that column to the SELECT query:
		$showUserFile = $_POST['showUserFile'];
		if ("$showUserFile" == "1")
			$query .= ", user_file"; // add 'user_file' column

		// ... if the user has checked the checkbox next to 'Serial', we'll add that column to the SELECT query:
		$showSerial = $_POST['showSerial'];
		if ("$showSerial" == "1")
			$query .= ", serial"; // add 'serial' column

		// ... if the user has checked the checkbox next to 'Type', we'll add that column to the SELECT query:
		$showType = $_POST['showType'];
		if ("$showType" == "1")
			$query .= ", type"; // add 'type' column

		// ... if the user has checked the checkbox next to 'Marked', we'll add that column to the SELECT query:
		$showMarked = $_POST['showMarked'];
		if ("$showMarked" == "1")
			$query .= ", marked"; // add 'marked' column

		// ... if the user has checked the checkbox next to 'Selected', we'll add that column to the SELECT query:
		$showSelected = $_POST['showSelected'];
		if ("$showSelected" == "1")
			$query .= ", selected"; // add 'selected' column

		// ... if the user has checked the checkbox next to 'Approved', we'll add that column to the SELECT query:
		$showApproved = $_POST['showApproved'];
		if ("$showApproved" == "1")
			$query .= ", approved"; // add 'approved' column

		// ... if the user has checked the checkbox next to 'Date Created', we'll add that column to the SELECT query:
		$showCreatedDate = $_POST['showCreatedDate'];
		if ("$showCreatedDate" == "1")
			$query .= ", created_date"; // add 'created_date' column

		// ... if the user has checked the checkbox next to 'Time Created', we'll add that column to the SELECT query:
		$showCreatedTime = $_POST['showCreatedTime'];
		if ("$showCreatedTime" == "1")
			$query .= ", created_time"; // add 'created_time' column

		// ... if the user has checked the checkbox next to 'Created By', we'll add that column to the SELECT query:
		$showCreatedBy = $_POST['showCreatedBy'];
		if ("$showCreatedBy" == "1")
			$query .= ", created_by"; // add 'created_by' column

		// ... if the user has checked the checkbox next to 'Date Modified', we'll add that column to the SELECT query:
		$showModifiedDate = $_POST['showModifiedDate'];
		if ("$showModifiedDate" == "1")
			$query .= ", modified_date"; // add 'modified_date' column

		// ... if the user has checked the checkbox next to 'Time Modified', we'll add that column to the SELECT query:
		$showModifiedTime = $_POST['showModifiedTime'];
		if ("$showModifiedTime" == "1")
			$query .= ", modified_time"; // add 'modified_time' column

		// ... if the user has checked the checkbox next to 'Modified By', we'll add that column to the SELECT query:
		$showModifiedBy = $_POST['showModifiedBy'];
		if ("$showModifiedBy" == "1")
			$query .= ", modified_by"; // add 'modified_by' column

		// ... we still have to trap the case that the (silly!) user hasn't checked any of the column checkboxes above:
		if ($query == "SELECT")
			$query .= " author"; // force add 'author' column if the user hasn't checked any of the column checkboxes

		$query .= ", orig_record"; // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
								//  (which is required in order to present visual feedback on duplicate records)

		$query .= ", serial"; // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
							//  (which is required in order to obtain unique checkbox names)

		if ("$showLinks" == "1")
			$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

		// Finally, fix the wrong syntax where its says "SELECT, author, title, ..." instead of "SELECT author, title, ..."
		$query = str_replace("SELECT, ","SELECT ",$query);

		if (session_is_registered("loginEmail")) // if a user is logged in...
			$query .= " FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = " . $userID . " WHERE serial RLIKE \".+\""; // add FROM & (initial) WHERE clause
		else // NO user logged in
			$query .= " FROM refs WHERE serial RLIKE \".+\""; // add FROM & (initial) WHERE clause
		
		// ---------------------------------------

		// ... if the user has specified an author, add the value of '$authorName' as an AND clause:
		$authorName = $_POST['authorName'];
		if ("$authorName" != "")
			{
				$authorSelector = $_POST['authorSelector'];
				if ("$authorSelector" == "contains")
					$query .= " AND author RLIKE \"$authorName\"";
				elseif ("$authorSelector" == "does not contain")
					$query .= " AND author NOT RLIKE \"$authorName\"";
				elseif ("$authorSelector" == "is equal to")
					$query .= " AND author = \"$authorName\"";
				elseif ("$authorSelector" == "is not equal to")
					$query .= " AND author != \"$authorName\"";
				elseif ("$authorSelector" == "starts with")
					$query .= " AND author RLIKE \"^$authorName\"";
				elseif ("$authorSelector" == "ends with")
					$query .= " AND author RLIKE \"$authorName$\"";
			}

		// ... if the user has specified an address, add the value of '$addressName' as an AND clause:
		$addressName = $_POST['addressName'];
		if ("$addressName" != "")
			{
				$addressSelector = $_POST['addressSelector'];
				if ("$addressSelector" == "contains")
					$query .= " AND address RLIKE \"$addressName\"";
				elseif ("$addressSelector" == "does not contain")
					$query .= " AND address NOT RLIKE \"$addressName\"";
				elseif ("$addressSelector" == "is equal to")
					$query .= " AND address = \"$addressName\"";
				elseif ("$addressSelector" == "is not equal to")
					$query .= " AND address != \"$addressName\"";
				elseif ("$addressSelector" == "starts with")
					$query .= " AND address RLIKE \"^$addressName\"";
				elseif ("$addressSelector" == "ends with")
					$query .= " AND address RLIKE \"$addressName$\"";
			}

		// ... if the user has specified a corporate author, add the value of '$corporateAuthorName' as an AND clause:
		$corporateAuthorName = $_POST['corporateAuthorName'];
		if ("$corporateAuthorName" != "")
			{
				$corporateAuthorSelector = $_POST['corporateAuthorSelector'];
				if ("$corporateAuthorSelector" == "contains")
					$query .= " AND corporate_author RLIKE \"$corporateAuthorName\"";
				elseif ("$corporateAuthorSelector" == "does not contain")
					$query .= " AND corporate_author NOT RLIKE \"$corporateAuthorName\"";
				elseif ("$corporateAuthorSelector" == "is equal to")
					$query .= " AND corporate_author = \"$corporateAuthorName\"";
				elseif ("$corporateAuthorSelector" == "is not equal to")
					$query .= " AND corporate_author != \"$corporateAuthorName\"";
				elseif ("$corporateAuthorSelector" == "starts with")
					$query .= " AND corporate_author RLIKE \"^$corporateAuthorName\"";
				elseif ("$corporateAuthorSelector" == "ends with")
					$query .= " AND corporate_author RLIKE \"$corporateAuthorName$\"";
			}

		// ... if the user has specified a thesis, add the value of '$thesisName' as an AND clause:
		$thesisRadio = $_POST['thesisRadio'];
		if ("$thesisRadio" == "1")
		{
			$thesisName = $_POST['thesisName'];
			if ("$thesisName" != "All" && "$thesisName" != "")
				{
					$thesisSelector = $_POST['thesisSelector'];
					if ("$thesisSelector" == "contains")
						$query .= " AND thesis RLIKE \"$thesisName\"";
					elseif ("$thesisSelector" == "does not contain")
						$query .= " AND thesis NOT RLIKE \"$thesisName\"";
					elseif ("$thesisSelector" == "is equal to")
						$query .= " AND thesis = \"$thesisName\"";
					elseif ("$thesisSelector" == "is not equal to")
						$query .= " AND thesis != \"$thesisName\"";
					elseif ("$thesisSelector" == "starts with")
						$query .= " AND thesis RLIKE \"^$thesisName\"";
					elseif ("$thesisSelector" == "ends with")
						$query .= " AND thesis RLIKE \"$thesisName$\"";
				}
		}
		elseif ("$thesisRadio" == "0")
		{
			$thesisName2 = $_POST['thesisName2'];
			if ("$thesisName2" != "")
				{
					$thesisSelector2 = $_POST['thesisSelector2'];
					if ("$thesisSelector2" == "contains")
						$query .= " AND thesis RLIKE \"$thesisName2\"";
					elseif ("$thesisSelector2" == "does not contain")
						$query .= " AND thesis NOT RLIKE \"$thesisName2\"";
					elseif ("$thesisSelector2" == "is equal to")
						$query .= " AND thesis = \"$thesisName2\"";
					elseif ("$thesisSelector2" == "is not equal to")
						$query .= " AND thesis != \"$thesisName2\"";
					elseif ("$thesisSelector2" == "starts with")
						$query .= " AND thesis RLIKE \"^$thesisName2\"";
					elseif ("$thesisSelector2" == "ends with")
						$query .= " AND thesis RLIKE \"$thesisName2$\"";
				}
		}

		// ... if the user has specified a title, add the value of '$titleName' as an AND clause:
		$titleName = $_POST['titleName'];
		if ("$titleName" != "")
			{
				$titleSelector = $_POST['titleSelector'];
				if ("$titleSelector" == "contains")
					$query .= " AND title RLIKE \"$titleName\"";
				elseif ("$titleSelector" == "does not contain")
					$query .= " AND title NOT RLIKE \"$titleName\"";
				elseif ("$titleSelector" == "is equal to")
					$query .= " AND title = \"$titleName\"";
				elseif ("$titleSelector" == "is not equal to")
					$query .= " AND title != \"$titleName\"";
				elseif ("$titleSelector" == "starts with")
					$query .= " AND title RLIKE \"^$titleName\"";
				elseif ("$titleSelector" == "ends with")
					$query .= " AND title RLIKE \"$titleName$\"";
			}

		// ... if the user has specified an original title, add the value of '$origTitleName' as an AND clause:
		$origTitleName = $_POST['origTitleName'];
		if ("$origTitleName" != "")
			{
				$origTitleSelector = $_POST['origTitleSelector'];
				if ("$origTitleSelector" == "contains")
					$query .= " AND orig_title RLIKE \"$origTitleName\"";
				elseif ("$origTitleSelector" == "does not contain")
					$query .= " AND orig_title NOT RLIKE \"$origTitleName\"";
				elseif ("$origTitleSelector" == "is equal to")
					$query .= " AND orig_title = \"$origTitleName\"";
				elseif ("$origTitleSelector" == "is not equal to")
					$query .= " AND orig_title != \"$origTitleName\"";
				elseif ("$origTitleSelector" == "starts with")
					$query .= " AND orig_title RLIKE \"^$origTitleName\"";
				elseif ("$origTitleSelector" == "ends with")
					$query .= " AND orig_title RLIKE \"$origTitleName$\"";
			}

		// ... if the user has specified a year, add the value of '$yearNo' as an AND clause:
		$yearNo = $_POST['yearNo'];
		if ("$yearNo" != "")
			{
				$yearSelector = $_POST['yearSelector'];
				if ("$yearSelector" == "contains")
					$query .= " AND year RLIKE \"$yearNo\"";
				elseif ("$yearSelector" == "does not contain")
					$query .= " AND year NOT RLIKE \"$yearNo\"";
				elseif ("$yearSelector" == "is equal to")
					$query .= " AND year = \"$yearNo\"";
				elseif ("$yearSelector" == "is not equal to")
					$query .= " AND year != \"$yearNo\"";
				elseif ("$yearSelector" == "starts with")
					$query .= " AND year RLIKE \"^$yearNo\"";
				elseif ("$yearSelector" == "ends with")
					$query .= " AND year RLIKE \"$yearNo$\"";
				elseif ("$yearSelector" == "is greater than")
					$query .= " AND year > \"$yearNo\"";
				elseif ("$yearSelector" == "is less than")
					$query .= " AND year < \"$yearNo\"";
			}

		// ... if the user has specified a publication, add the value of '$publicationName' as an AND clause:
		$publicationRadio = $_POST['publicationRadio'];
		if ("$publicationRadio" == "1")
		{
			$publicationName = $_POST['publicationName'];
			if ("$publicationName" != "All" && "$publicationName" != "")
				{
					$publicationSelector = $_POST['publicationSelector'];
					if ("$publicationSelector" == "contains")
						$query .= " AND publication RLIKE \"$publicationName\"";
					elseif ("$publicationSelector" == "does not contain")
						$query .= " AND publication NOT RLIKE \"$publicationName\"";
					elseif ("$publicationSelector" == "is equal to")
						$query .= " AND publication = \"$publicationName\"";
					elseif ("$publicationSelector" == "is not equal to")
						$query .= " AND publication != \"$publicationName\"";
					elseif ("$publicationSelector" == "starts with")
						$query .= " AND publication RLIKE \"^$publicationName\"";
					elseif ("$publicationSelector" == "ends with")
						$query .= " AND publication RLIKE \"$publicationName$\"";
				}
		}
		elseif ("$publicationRadio" == "0")
		{
			$publicationName2 = $_POST['publicationName2'];
			if ("$publicationName2" != "")
				{
					$publicationSelector2 = $_POST['publicationSelector2'];
					if ("$publicationSelector2" == "contains")
						$query .= " AND publication RLIKE \"$publicationName2\"";
					elseif ("$publicationSelector2" == "does not contain")
						$query .= " AND publication NOT RLIKE \"$publicationName2\"";
					elseif ("$publicationSelector2" == "is equal to")
						$query .= " AND publication = \"$publicationName2\"";
					elseif ("$publicationSelector2" == "is not equal to")
						$query .= " AND publication != \"$publicationName2\"";
					elseif ("$publicationSelector2" == "starts with")
						$query .= " AND publication RLIKE \"^$publicationName2\"";
					elseif ("$publicationSelector2" == "ends with")
						$query .= " AND publication RLIKE \"$publicationName2$\"";
				}
		}

		// ... if the user has specified an abbreviated journal, add the value of '$abbrevJournalName' as an AND clause:
		$abbrevJournalRadio = $_POST['abbrevJournalRadio'];
		if ("$abbrevJournalRadio" == "1")
		{
			$abbrevJournalName = $_POST['abbrevJournalName'];
			if ("$abbrevJournalName" != "All" && "$abbrevJournalName" != "")
				{
					$abbrevJournalSelector = $_POST['abbrevJournalSelector'];
					if ("$abbrevJournalSelector" == "contains")
						$query .= " AND abbrev_journal RLIKE \"$abbrevJournalName\"";
					elseif ("$abbrevJournalSelector" == "does not contain")
						$query .= " AND abbrev_journal NOT RLIKE \"$abbrevJournalName\"";
					elseif ("$abbrevJournalSelector" == "is equal to")
						$query .= " AND abbrev_journal = \"$abbrevJournalName\"";
					elseif ("$abbrevJournalSelector" == "is not equal to")
						$query .= " AND abbrev_journal != \"$abbrevJournalName\"";
					elseif ("$abbrevJournalSelector" == "starts with")
						$query .= " AND abbrev_journal RLIKE \"^$abbrevJournalName\"";
					elseif ("$abbrevJournalSelector" == "ends with")
						$query .= " AND abbrev_journal RLIKE \"$abbrevJournalName$\"";
				}
		}
		elseif ("$abbrevJournalRadio" == "0")
		{
			$abbrevJournalName2 = $_POST['abbrevJournalName2'];
			if ("$abbrevJournalName2" != "")
				{
					$abbrevJournalSelector2 = $_POST['abbrevJournalSelector2'];
					if ("$abbrevJournalSelector2" == "contains")
						$query .= " AND abbrev_journal RLIKE \"$abbrevJournalName2\"";
					elseif ("$abbrevJournalSelector2" == "does not contain")
						$query .= " AND abbrev_journal NOT RLIKE \"$abbrevJournalName2\"";
					elseif ("$abbrevJournalSelector2" == "is equal to")
						$query .= " AND abbrev_journal = \"$abbrevJournalName2\"";
					elseif ("$abbrevJournalSelector2" == "is not equal to")
						$query .= " AND abbrev_journal != \"$abbrevJournalName2\"";
					elseif ("$abbrevJournalSelector2" == "starts with")
						$query .= " AND abbrev_journal RLIKE \"^$abbrevJournalName2\"";
					elseif ("$abbrevJournalSelector2" == "ends with")
						$query .= " AND abbrev_journal RLIKE \"$abbrevJournalName2$\"";
				}
		}

		// ... if the user has specified an editor, add the value of '$editorName' as an AND clause:
		$editorName = $_POST['editorName'];
		if ("$editorName" != "")
			{
				$editorSelector = $_POST['editorSelector'];
				if ("$editorSelector" == "contains")
					$query .= " AND editor RLIKE \"$editorName\"";
				elseif ("$editorSelector" == "does not contain")
					$query .= " AND editor NOT RLIKE \"$editorName\"";
				elseif ("$editorSelector" == "is equal to")
					$query .= " AND editor = \"$editorName\"";
				elseif ("$editorSelector" == "is not equal to")
					$query .= " AND editor != \"$editorName\"";
				elseif ("$editorSelector" == "starts with")
					$query .= " AND editor RLIKE \"^$editorName\"";
				elseif ("$editorSelector" == "ends with")
					$query .= " AND editor RLIKE \"$editorName$\"";
			}

		// ... if the user has specified a volume, add the value of '$volumeNo' as an AND clause:
		$volumeNo = $_POST['volumeNo'];
		if ("$volumeNo" != "")
			{
				$volumeSelector = $_POST['volumeSelector'];
				if ("$volumeSelector" == "contains")
					$query .= " AND volume RLIKE \"$volumeNo\"";
				elseif ("$volumeSelector" == "does not contain")
					$query .= " AND volume NOT RLIKE \"$volumeNo\"";
				elseif ("$volumeSelector" == "is equal to")
					$query .= " AND volume = \"$volumeNo\"";
				elseif ("$volumeSelector" == "is not equal to")
					$query .= " AND volume != \"$volumeNo\"";
				elseif ("$volumeSelector" == "starts with")
					$query .= " AND volume RLIKE \"^$volumeNo\"";
				elseif ("$volumeSelector" == "ends with")
					$query .= " AND volume RLIKE \"$volumeNo$\"";
				elseif ("$volumeSelector" == "is greater than")
					$query .= " AND volume > \"$volumeNo\"";
				elseif ("$volumeSelector" == "is less than")
					$query .= " AND volume < \"$volumeNo\"";
			}

		// ... if the user has specified an issue, add the value of '$issueNo' as an AND clause:
		$issueNo = $_POST['issueNo'];
		if ("$issueNo" != "")
			{
				$issueSelector = $_POST['issueSelector'];
				if ("$issueSelector" == "contains")
					$query .= " AND issue RLIKE \"$issueNo\"";
				elseif ("$issueSelector" == "does not contain")
					$query .= " AND issue NOT RLIKE \"$issueNo\"";
				elseif ("$issueSelector" == "is equal to")
					$query .= " AND issue = \"$issueNo\"";
				elseif ("$issueSelector" == "is not equal to")
					$query .= " AND issue != \"$issueNo\"";
				elseif ("$issueSelector" == "starts with")
					$query .= " AND issue RLIKE \"^$issueNo\"";
				elseif ("$issueSelector" == "ends with")
					$query .= " AND issue RLIKE \"$issueNo$\"";
				elseif ("$issueSelector" == "is greater than")
					$query .= " AND issue > \"$issueNo\"";
				elseif ("$issueSelector" == "is less than")
					$query .= " AND issue < \"$issueNo\"";
			}

		// ... if the user has specified some pages, add the value of '$pagesNo' as an AND clause:
		$pagesNo = $_POST['pagesNo'];
		if ("$pagesNo" != "")
			{
				$pagesSelector = $_POST['pagesSelector'];
				if ("$pagesSelector" == "contains")
					$query .= " AND pages RLIKE \"$pagesNo\"";
				elseif ("$pagesSelector" == "does not contain")
					$query .= " AND pages NOT RLIKE \"$pagesNo\"";
				elseif ("$pagesSelector" == "is equal to")
					$query .= " AND pages = \"$pagesNo\"";
				elseif ("$pagesSelector" == "is not equal to")
					$query .= " AND pages != \"$pagesNo\"";
				elseif ("$pagesSelector" == "starts with")
					$query .= " AND pages RLIKE \"^$pagesNo\"";
				elseif ("$pagesSelector" == "ends with")
					$query .= " AND pages RLIKE \"$pagesNo$\"";
			}


		// ... if the user has specified a series title, add the value of '$seriesTitleName' as an AND clause:
		$seriesTitleRadio = $_POST['seriesTitleRadio'];
		if ("$seriesTitleRadio" == "1")
		{
			$seriesTitleName = $_POST['seriesTitleName'];
			if ("$seriesTitleName" != "All" && "$seriesTitleName" != "")
				{
					$seriesTitleSelector = $_POST['seriesTitleSelector'];
					if ("$seriesTitleSelector" == "contains")
						$query .= " AND series_title RLIKE \"$seriesTitleName\"";
					elseif ("$seriesTitleSelector" == "does not contain")
						$query .= " AND series_title NOT RLIKE \"$seriesTitleName\"";
					elseif ("$seriesTitleSelector" == "is equal to")
						$query .= " AND series_title = \"$seriesTitleName\"";
					elseif ("$seriesTitleSelector" == "is not equal to")
						$query .= " AND series_title != \"$seriesTitleName\"";
					elseif ("$seriesTitleSelector" == "starts with")
						$query .= " AND series_title RLIKE \"^$seriesTitleName\"";
					elseif ("$seriesTitleSelector" == "ends with")
						$query .= " AND series_title RLIKE \"$seriesTitleName$\"";
				}
		}
		elseif ("$seriesTitleRadio" == "0")
		{
			$seriesTitleName2 = $_POST['seriesTitleName2'];
			if ("$seriesTitleName2" != "")
				{
					$seriesTitleSelector2 = $_POST['seriesTitleSelector2'];
					if ("$seriesTitleSelector2" == "contains")
						$query .= " AND series_title RLIKE \"$seriesTitleName2\"";
					elseif ("$seriesTitleSelector2" == "does not contain")
						$query .= " AND series_title NOT RLIKE \"$seriesTitleName2\"";
					elseif ("$seriesTitleSelector2" == "is equal to")
						$query .= " AND series_title = \"$seriesTitleName2\"";
					elseif ("$seriesTitleSelector2" == "is not equal to")
						$query .= " AND series_title != \"$seriesTitleName2\"";
					elseif ("$seriesTitleSelector2" == "starts with")
						$query .= " AND series_title RLIKE \"^$seriesTitleName2\"";
					elseif ("$seriesTitleSelector2" == "ends with")
						$query .= " AND series_title RLIKE \"$seriesTitleName2$\"";
				}
		}

		// ... if the user has specified an abbreviated series title, add the value of '$abbrevSeriesTitleName' as an AND clause:
		$abbrevSeriesTitleRadio = $_POST['abbrevSeriesTitleRadio'];
		if ("$abbrevSeriesTitleRadio" == "1")
		{
			$abbrevSeriesTitleName = $_POST['abbrevSeriesTitleName'];
			if ("$abbrevSeriesTitleName" != "All" && "$abbrevSeriesTitleName" != "")
				{
					$abbrevSeriesTitleSelector = $_POST['abbrevSeriesTitleSelector'];
					if ("$abbrevSeriesTitleSelector" == "contains")
						$query .= " AND abbrev_series_title RLIKE \"$abbrevSeriesTitleName\"";
					elseif ("$abbrevSeriesTitleSelector" == "does not contain")
						$query .= " AND abbrev_series_title NOT RLIKE \"$abbrevSeriesTitleName\"";
					elseif ("$abbrevSeriesTitleSelector" == "is equal to")
						$query .= " AND abbrev_series_title = \"$abbrevSeriesTitleName\"";
					elseif ("$abbrevSeriesTitleSelector" == "is not equal to")
						$query .= " AND abbrev_series_title != \"$abbrevSeriesTitleName\"";
					elseif ("$abbrevSeriesTitleSelector" == "starts with")
						$query .= " AND abbrev_series_title RLIKE \"^$abbrevSeriesTitleName\"";
					elseif ("$abbrevSeriesTitleSelector" == "ends with")
						$query .= " AND abbrev_series_title RLIKE \"$abbrevSeriesTitleName$\"";
				}
		}
		elseif ("$abbrevSeriesTitleRadio" == "0")
		{
			$abbrevSeriesTitleName2 = $_POST['abbrevSeriesTitleName2'];
			if ("$abbrevSeriesTitleName2" != "")
				{
					$abbrevSeriesTitleSelector2 = $_POST['abbrevSeriesTitleSelector2'];
					if ("$abbrevSeriesTitleSelector2" == "contains")
						$query .= " AND abbrev_series_title RLIKE \"$abbrevSeriesTitleName2\"";
					elseif ("$abbrevSeriesTitleSelector2" == "does not contain")
						$query .= " AND abbrev_series_title NOT RLIKE \"$abbrevSeriesTitleName2\"";
					elseif ("$abbrevSeriesTitleSelector2" == "is equal to")
						$query .= " AND abbrev_series_title = \"$abbrevSeriesTitleName2\"";
					elseif ("$abbrevSeriesTitleSelector2" == "is not equal to")
						$query .= " AND abbrev_series_title != \"$abbrevSeriesTitleName2\"";
					elseif ("$abbrevSeriesTitleSelector2" == "starts with")
						$query .= " AND abbrev_series_title RLIKE \"^$abbrevSeriesTitleName2\"";
					elseif ("$abbrevSeriesTitleSelector2" == "ends with")
						$query .= " AND abbrev_series_title RLIKE \"$abbrevSeriesTitleName2$\"";
				}
		}

		// ... if the user has specified a series editor, add the value of '$seriesEditorName' as an AND clause:
		$seriesEditorName = $_POST['seriesEditorName'];
		if ("$seriesEditorName" != "")
			{
				$seriesEditorSelector = $_POST['seriesEditorSelector'];
				if ("$seriesEditorSelector" == "contains")
					$query .= " AND series_editor RLIKE \"$seriesEditorName\"";
				elseif ("$seriesEditorSelector" == "does not contain")
					$query .= " AND series_editor NOT RLIKE \"$seriesEditorName\"";
				elseif ("$seriesEditorSelector" == "is equal to")
					$query .= " AND series_editor = \"$seriesEditorName\"";
				elseif ("$seriesEditorSelector" == "is not equal to")
					$query .= " AND series_editor != \"$seriesEditorName\"";
				elseif ("$seriesEditorSelector" == "starts with")
					$query .= " AND series_editor RLIKE \"^$seriesEditorName\"";
				elseif ("$seriesEditorSelector" == "ends with")
					$query .= " AND series_editor RLIKE \"$seriesEditorName$\"";
			}


		// ... if the user has specified a series volume, add the value of '$seriesVolumeNo' as an AND clause:
		$seriesVolumeNo = $_POST['seriesVolumeNo'];
		if ("$seriesVolumeNo" != "")
			{
				$seriesVolumeSelector = $_POST['seriesVolumeSelector'];
				if ("$seriesVolumeSelector" == "contains")
					$query .= " AND series_volume RLIKE \"$seriesVolumeNo\"";
				elseif ("$seriesVolumeSelector" == "does not contain")
					$query .= " AND series_volume NOT RLIKE \"$seriesVolumeNo\"";
				elseif ("$seriesVolumeSelector" == "is equal to")
					$query .= " AND series_volume = \"$seriesVolumeNo\"";
				elseif ("$seriesVolumeSelector" == "is not equal to")
					$query .= " AND series_volume != \"$seriesVolumeNo\"";
				elseif ("$seriesVolumeSelector" == "starts with")
					$query .= " AND series_volume RLIKE \"^$seriesVolumeNo\"";
				elseif ("$seriesVolumeSelector" == "ends with")
					$query .= " AND series_volume RLIKE \"$seriesVolumeNo$\"";
				elseif ("$seriesVolumeSelector" == "is greater than")
					$query .= " AND series_volume > \"$seriesVolumeNo\"";
				elseif ("$seriesVolumeSelector" == "is less than")
					$query .= " AND series_volume < \"$seriesVolumeNo\"";
			}

		// ... if the user has specified a series issue, add the value of '$seriesIssueNo' as an AND clause:
		$seriesIssueNo = $_POST['seriesIssueNo'];
		if ("$seriesIssueNo" != "")
			{
				$seriesIssueSelector = $_POST['seriesIssueSelector'];
				if ("$seriesIssueSelector" == "contains")
					$query .= " AND series_issue RLIKE \"$seriesIssueNo\"";
				elseif ("$seriesIssueSelector" == "does not contain")
					$query .= " AND series_issue NOT RLIKE \"$seriesIssueNo\"";
				elseif ("$seriesIssueSelector" == "is equal to")
					$query .= " AND series_issue = \"$seriesIssueNo\"";
				elseif ("$seriesIssueSelector" == "is not equal to")
					$query .= " AND series_issue != \"$seriesIssueNo\"";
				elseif ("$seriesIssueSelector" == "starts with")
					$query .= " AND series_issue RLIKE \"^$seriesIssueNo\"";
				elseif ("$seriesIssueSelector" == "ends with")
					$query .= " AND series_issue RLIKE \"$seriesIssueNo$\"";
				elseif ("$seriesIssueSelector" == "is greater than")
					$query .= " AND series_issue > \"$seriesIssueNo\"";
				elseif ("$seriesIssueSelector" == "is less than")
					$query .= " AND series_issue < \"$seriesIssueNo\"";
			}

		// ... if the user has specified a publisher, add the value of '$publisherName' as an AND clause:
		$publisherRadio = $_POST['publisherRadio'];
		if ("$publisherRadio" == "1")
		{
			$publisherName = $_POST['publisherName'];
			if ("$publisherName" != "All" && "$publisherName" != "")
				{
					$publisherSelector = $_POST['publisherSelector'];
					if ("$publisherSelector" == "contains")
						$query .= " AND publisher RLIKE \"$publisherName\"";
					elseif ("$publisherSelector" == "does not contain")
						$query .= " AND publisher NOT RLIKE \"$publisherName\"";
					elseif ("$publisherSelector" == "is equal to")
						$query .= " AND publisher = \"$publisherName\"";
					elseif ("$publisherSelector" == "is not equal to")
						$query .= " AND publisher != \"$publisherName\"";
					elseif ("$publisherSelector" == "starts with")
						$query .= " AND publisher RLIKE \"^$publisherName\"";
					elseif ("$publisherSelector" == "ends with")
						$query .= " AND publisher RLIKE \"$publisherName$\"";
				}
		}
		elseif ("$publisherRadio" == "0")
		{
			$publisherName2 = $_POST['publisherName2'];
			if ("$publisherName2" != "")
				{
					$publisherSelector2 = $_POST['publisherSelector2'];
					if ("$publisherSelector2" == "contains")
						$query .= " AND publisher RLIKE \"$publisherName2\"";
					elseif ("$publisherSelector2" == "does not contain")
						$query .= " AND publisher NOT RLIKE \"$publisherName2\"";
					elseif ("$publisherSelector2" == "is equal to")
						$query .= " AND publisher = \"$publisherName2\"";
					elseif ("$publisherSelector2" == "is not equal to")
						$query .= " AND publisher != \"$publisherName2\"";
					elseif ("$publisherSelector2" == "starts with")
						$query .= " AND publisher RLIKE \"^$publisherName2\"";
					elseif ("$publisherSelector2" == "ends with")
						$query .= " AND publisher RLIKE \"$publisherName2$\"";
				}
		}

		// ... if the user has specified a place, add the value of '$placeName' as an AND clause:
		$placeRadio = $_POST['placeRadio'];
		if ("$placeRadio" == "1")
		{
			$placeName = $_POST['placeName'];
			if ("$placeName" != "All" && "$placeName" != "")
				{
					$placeSelector = $_POST['placeSelector'];
					if ("$placeSelector" == "contains")
						$query .= " AND place RLIKE \"$placeName\"";
					elseif ("$placeSelector" == "does not contain")
						$query .= " AND place NOT RLIKE \"$placeName\"";
					elseif ("$placeSelector" == "is equal to")
						$query .= " AND place = \"$placeName\"";
					elseif ("$placeSelector" == "is not equal to")
						$query .= " AND place != \"$placeName\"";
					elseif ("$placeSelector" == "starts with")
						$query .= " AND place RLIKE \"^$placeName\"";
					elseif ("$placeSelector" == "ends with")
						$query .= " AND place RLIKE \"$placeName$\"";
				}
		}
		elseif ("$placeRadio" == "0")
		{
			$placeName2 = $_POST['placeName2'];
			if ("$placeName2" != "")
				{
					$placeSelector2 = $_POST['placeSelector2'];
					if ("$placeSelector2" == "contains")
						$query .= " AND place RLIKE \"$placeName2\"";
					elseif ("$placeSelector2" == "does not contain")
						$query .= " AND place NOT RLIKE \"$placeName2\"";
					elseif ("$placeSelector2" == "is equal to")
						$query .= " AND place = \"$placeName2\"";
					elseif ("$placeSelector2" == "is not equal to")
						$query .= " AND place != \"$placeName2\"";
					elseif ("$placeSelector2" == "starts with")
						$query .= " AND place RLIKE \"^$placeName2\"";
					elseif ("$placeSelector2" == "ends with")
						$query .= " AND place RLIKE \"$placeName2$\"";
				}
		}

		// ... if the user has specified an edition, add the value of '$editionNo' as an AND clause:
		$editionNo = $_POST['editionNo'];
		if ("$editionNo" != "")
			{
				$editionSelector = $_POST['editionSelector'];
				if ("$editionSelector" == "contains")
					$query .= " AND edition RLIKE \"$editionNo\"";
				elseif ("$editionSelector" == "does not contain")
					$query .= " AND edition NOT RLIKE \"$editionNo\"";
				elseif ("$editionSelector" == "is equal to")
					$query .= " AND edition = \"$editionNo\"";
				elseif ("$editionSelector" == "is not equal to")
					$query .= " AND edition != \"$editionNo\"";
				elseif ("$editionSelector" == "starts with")
					$query .= " AND edition RLIKE \"^$editionNo\"";
				elseif ("$editionSelector" == "ends with")
					$query .= " AND edition RLIKE \"$editionNo$\"";
				elseif ("$editionSelector" == "is greater than")
					$query .= " AND edition > \"$editionNo\"";
				elseif ("$editionSelector" == "is less than")
					$query .= " AND edition < \"$editionNo\"";
			}

		// ... if the user has specified a medium, add the value of '$mediumName' as an AND clause:
		$mediumName = $_POST['mediumName'];
		if ("$mediumName" != "")
			{
				$mediumSelector = $_POST['mediumSelector'];
				if ("$mediumSelector" == "contains")
					$query .= " AND medium RLIKE \"$mediumName\"";
				elseif ("$mediumSelector" == "does not contain")
					$query .= " AND medium NOT RLIKE \"$mediumName\"";
				elseif ("$mediumSelector" == "is equal to")
					$query .= " AND medium = \"$mediumName\"";
				elseif ("$mediumSelector" == "is not equal to")
					$query .= " AND medium != \"$mediumName\"";
				elseif ("$mediumSelector" == "starts with")
					$query .= " AND medium RLIKE \"^$mediumName\"";
				elseif ("$mediumSelector" == "ends with")
					$query .= " AND medium RLIKE \"$mediumName$\"";
			}

		// ... if the user has specified an ISSN, add the value of '$issnName' as an AND clause:
		$issnName = $_POST['issnName'];
		if ("$issnName" != "")
			{
				$issnSelector = $_POST['issnSelector'];
				if ("$issnSelector" == "contains")
					$query .= " AND issn RLIKE \"$issnName\"";
				elseif ("$issnSelector" == "does not contain")
					$query .= " AND issn NOT RLIKE \"$issnName\"";
				elseif ("$issnSelector" == "is equal to")
					$query .= " AND issn = \"$issnName\"";
				elseif ("$issnSelector" == "is not equal to")
					$query .= " AND issn != \"$issnName\"";
				elseif ("$issnSelector" == "starts with")
					$query .= " AND issn RLIKE \"^$issnName\"";
				elseif ("$issnSelector" == "ends with")
					$query .= " AND issn RLIKE \"$issnName$\"";
			}

		// ... if the user has specified an ISBN, add the value of '$isbnName' as an AND clause:
		$isbnName = $_POST['isbnName'];
		if ("$isbnName" != "")
			{
				$isbnSelector = $_POST['isbnSelector'];
				if ("$isbnSelector" == "contains")
					$query .= " AND isbn RLIKE \"$isbnName\"";
				elseif ("$isbnSelector" == "does not contain")
					$query .= " AND isbn NOT RLIKE \"$isbnName\"";
				elseif ("$isbnSelector" == "is equal to")
					$query .= " AND isbn = \"$isbnName\"";
				elseif ("$isbnSelector" == "is not equal to")
					$query .= " AND isbn != \"$isbnName\"";
				elseif ("$isbnSelector" == "starts with")
					$query .= " AND isbn RLIKE \"^$isbnName\"";
				elseif ("$isbnSelector" == "ends with")
					$query .= " AND isbn RLIKE \"$isbnName$\"";
			}


		// ... if the user has specified a language, add the value of '$languageName' as an AND clause:
		$languageRadio = $_POST['languageRadio'];
		if ("$languageRadio" == "1")
		{
			$languageName = $_POST['languageName'];
			if ("$languageName" != "All" && "$languageName" != "")
				{
					$languageSelector = $_POST['languageSelector'];
					if ("$languageSelector" == "contains")
						$query .= " AND language RLIKE \"$languageName\"";
					elseif ("$languageSelector" == "does not contain")
						$query .= " AND language NOT RLIKE \"$languageName\"";
					elseif ("$languageSelector" == "is equal to")
						$query .= " AND language = \"$languageName\"";
					elseif ("$languageSelector" == "is not equal to")
						$query .= " AND language != \"$languageName\"";
					elseif ("$languageSelector" == "starts with")
						$query .= " AND language RLIKE \"^$languageName\"";
					elseif ("$languageSelector" == "ends with")
						$query .= " AND language RLIKE \"$languageName$\"";
				}
		}
		elseif ("$languageRadio" == "0")
		{
			$languageName2 = $_POST['languageName2'];
			if ("$languageName2" != "")
				{
					$languageSelector2 = $_POST['languageSelector2'];
					if ("$languageSelector2" == "contains")
						$query .= " AND language RLIKE \"$languageName2\"";
					elseif ("$languageSelector2" == "does not contain")
						$query .= " AND language NOT RLIKE \"$languageName2\"";
					elseif ("$languageSelector2" == "is equal to")
						$query .= " AND language = \"$languageName2\"";
					elseif ("$languageSelector2" == "is not equal to")
						$query .= " AND language != \"$languageName2\"";
					elseif ("$languageSelector2" == "starts with")
						$query .= " AND language RLIKE \"^$languageName2\"";
					elseif ("$languageSelector2" == "ends with")
						$query .= " AND language RLIKE \"$languageName2$\"";
				}
		}

		// ... if the user has specified a summary language, add the value of '$summaryLanguageName' as an AND clause:
		$summaryLanguageRadio = $_POST['summaryLanguageRadio'];
		if ("$summaryLanguageRadio" == "1")
		{
			$summaryLanguageName = $_POST['summaryLanguageName'];
			if ("$summaryLanguageName" != "All" && "$summaryLanguageName" != "")
				{
					$summaryLanguageSelector = $_POST['summaryLanguageSelector'];
					if ("$summaryLanguageSelector" == "contains")
						$query .= " AND summary_language RLIKE \"$summaryLanguageName\"";
					elseif ("$summaryLanguageSelector" == "does not contain")
						$query .= " AND summary_language NOT RLIKE \"$summaryLanguageName\"";
					elseif ("$summaryLanguageSelector" == "is equal to")
						$query .= " AND summary_language = \"$summaryLanguageName\"";
					elseif ("$summaryLanguageSelector" == "is not equal to")
						$query .= " AND summary_language != \"$summaryLanguageName\"";
					elseif ("$summaryLanguageSelector" == "starts with")
						$query .= " AND summary_language RLIKE \"^$summaryLanguageName\"";
					elseif ("$summaryLanguageSelector" == "ends with")
						$query .= " AND summary_language RLIKE \"$summaryLanguageName$\"";
				}
		}
		elseif ("$summaryLanguageRadio" == "0")
		{
			$summaryLanguageName2 = $_POST['summaryLanguageName2'];
			if ("$summaryLanguageName2" != "")
				{
					$summaryLanguageSelector2 = $_POST['summaryLanguageSelector2'];
					if ("$summaryLanguageSelector2" == "contains")
						$query .= " AND summary_language RLIKE \"$summaryLanguageName2\"";
					elseif ("$summaryLanguageSelector2" == "does not contain")
						$query .= " AND summary_language NOT RLIKE \"$summaryLanguageName2\"";
					elseif ("$summaryLanguageSelector2" == "is equal to")
						$query .= " AND summary_language = \"$summaryLanguageName2\"";
					elseif ("$summaryLanguageSelector2" == "is not equal to")
						$query .= " AND summary_language != \"$summaryLanguageName2\"";
					elseif ("$summaryLanguageSelector2" == "starts with")
						$query .= " AND summary_language RLIKE \"^$summaryLanguageName2\"";
					elseif ("$summaryLanguageSelector2" == "ends with")
						$query .= " AND summary_language RLIKE \"$summaryLanguageName2$\"";
				}
		}

		// ... if the user has specified some keywords, add the value of '$keywordsName' as an AND clause:
		$keywordsName = $_POST['keywordsName'];
		if ("$keywordsName" != "")
			{
				$keywordsSelector = $_POST['keywordsSelector'];
				if ("$keywordsSelector" == "contains")
					$query .= " AND keywords RLIKE \"$keywordsName\"";
				elseif ("$keywordsSelector" == "does not contain")
					$query .= " AND keywords NOT RLIKE \"$keywordsName\"";
				elseif ("$keywordsSelector" == "is equal to")
					$query .= " AND keywords = \"$keywordsName\"";
				elseif ("$keywordsSelector" == "is not equal to")
					$query .= " AND keywords != \"$keywordsName\"";
				elseif ("$keywordsSelector" == "starts with")
					$query .= " AND keywords RLIKE \"^$keywordsName\"";
				elseif ("$keywordsSelector" == "ends with")
					$query .= " AND keywords RLIKE \"$keywordsName$\"";
			}

		// ... if the user has specified an abstract, add the value of '$abstractName' as an AND clause:
		$abstractName = $_POST['abstractName'];
		if ("$abstractName" != "")
			{
				$abstractSelector = $_POST['abstractSelector'];
				if ("$abstractSelector" == "contains")
					$query .= " AND abstract RLIKE \"$abstractName\"";
				elseif ("$abstractSelector" == "does not contain")
					$query .= " AND abstract NOT RLIKE \"$abstractName\"";
				elseif ("$abstractSelector" == "is equal to")
					$query .= " AND abstract = \"$abstractName\"";
				elseif ("$abstractSelector" == "is not equal to")
					$query .= " AND abstract != \"$abstractName\"";
				elseif ("$abstractSelector" == "starts with")
					$query .= " AND abstract RLIKE \"^$abstractName\"";
				elseif ("$abstractSelector" == "ends with")
					$query .= " AND abstract RLIKE \"$abstractName$\"";
			}


		// ... if the user has specified an area, add the value of '$areaName' as an AND clause:
		$areaRadio = $_POST['areaRadio'];
		if ("$areaRadio" == "1")
		{
			$areaName = $_POST['areaName'];
			if ("$areaName" != "All" && "$areaName" != "")
				{
					$areaSelector = $_POST['areaSelector'];
					if ("$areaSelector" == "contains")
						$query .= " AND area RLIKE \"$areaName\"";
					elseif ("$areaSelector" == "does not contain")
						$query .= " AND area NOT RLIKE \"$areaName\"";
					elseif ("$areaSelector" == "is equal to")
						$query .= " AND area = \"$areaName\"";
					elseif ("$areaSelector" == "is not equal to")
						$query .= " AND area != \"$areaName\"";
					elseif ("$areaSelector" == "starts with")
						$query .= " AND area RLIKE \"^$areaName\"";
					elseif ("$areaSelector" == "ends with")
						$query .= " AND area RLIKE \"$areaName$\"";
				}
		}
		elseif ("$areaRadio" == "0")
		{
			$areaName2 = $_POST['areaName2'];
			if ("$areaName2" != "")
				{
					$areaSelector2 = $_POST['areaSelector2'];
					if ("$areaSelector2" == "contains")
						$query .= " AND area RLIKE \"$areaName2\"";
					elseif ("$areaSelector2" == "does not contain")
						$query .= " AND area NOT RLIKE \"$areaName2\"";
					elseif ("$areaSelector2" == "is equal to")
						$query .= " AND area = \"$areaName2\"";
					elseif ("$areaSelector2" == "is not equal to")
						$query .= " AND area != \"$areaName2\"";
					elseif ("$areaSelector2" == "starts with")
						$query .= " AND area RLIKE \"^$areaName2\"";
					elseif ("$areaSelector2" == "ends with")
						$query .= " AND area RLIKE \"$areaName2$\"";
				}
		}

		// ... if the user has specified an expedition, add the value of '$expeditionName' as an AND clause:
		$expeditionName = $_POST['expeditionName'];
		if ("$expeditionName" != "")
			{
				$expeditionSelector = $_POST['expeditionSelector'];
				if ("$expeditionSelector" == "contains")
					$query .= " AND expedition RLIKE \"$expeditionName\"";
				elseif ("$expeditionSelector" == "does not contain")
					$query .= " AND expedition NOT RLIKE \"$expeditionName\"";
				elseif ("$expeditionSelector" == "is equal to")
					$query .= " AND expedition = \"$expeditionName\"";
				elseif ("$expeditionSelector" == "is not equal to")
					$query .= " AND expedition != \"$expeditionName\"";
				elseif ("$expeditionSelector" == "starts with")
					$query .= " AND expedition RLIKE \"^$expeditionName\"";
				elseif ("$expeditionSelector" == "ends with")
					$query .= " AND expedition RLIKE \"$expeditionName$\"";
			}

		// ... if the user has specified a conference, add the value of '$conferenceName' as an AND clause:
		$conferenceName = $_POST['conferenceName'];
		if ("$conferenceName" != "")
			{
				$conferenceSelector = $_POST['conferenceSelector'];
				if ("$conferenceSelector" == "contains")
					$query .= " AND conference RLIKE \"$conferenceName\"";
				elseif ("$conferenceSelector" == "does not contain")
					$query .= " AND conference NOT RLIKE \"$conferenceName\"";
				elseif ("$conferenceSelector" == "is equal to")
					$query .= " AND conference = \"$conferenceName\"";
				elseif ("$conferenceSelector" == "is not equal to")
					$query .= " AND conference != \"$conferenceName\"";
				elseif ("$conferenceSelector" == "starts with")
					$query .= " AND conference RLIKE \"^$conferenceName\"";
				elseif ("$conferenceSelector" == "ends with")
					$query .= " AND conference RLIKE \"$conferenceName$\"";
			}

		// ... if the user has specified a DOI, add the value of '$doiName' as an AND clause:
		$doiName = $_POST['doiName'];
		if ("$doiName" != "")
			{
				$doiSelector = $_POST['doiSelector'];
				if ("$doiSelector" == "contains")
					$query .= " AND doi RLIKE \"$doiName\"";
				elseif ("$doiSelector" == "does not contain")
					$query .= " AND doi NOT RLIKE \"$doiName\"";
				elseif ("$doiSelector" == "is equal to")
					$query .= " AND doi = \"$doiName\"";
				elseif ("$doiSelector" == "is not equal to")
					$query .= " AND doi != \"$doiName\"";
				elseif ("$doiSelector" == "starts with")
					$query .= " AND doi RLIKE \"^$doiName\"";
				elseif ("$doiSelector" == "ends with")
					$query .= " AND doi RLIKE \"$doiName$\"";
			}

		// ... if the user has specified an URL, add the value of '$urlName' as an AND clause:
		$urlName = $_POST['urlName'];
		if ("$urlName" != "")
			{
				$urlSelector = $_POST['urlSelector'];
				if ("$urlSelector" == "contains")
					$query .= " AND url RLIKE \"$urlName\"";
				elseif ("$urlSelector" == "does not contain")
					$query .= " AND url NOT RLIKE \"$urlName\"";
				elseif ("$urlSelector" == "is equal to")
					$query .= " AND url = \"$urlName\"";
				elseif ("$urlSelector" == "is not equal to")
					$query .= " AND url != \"$urlName\"";
				elseif ("$urlSelector" == "starts with")
					$query .= " AND url RLIKE \"^$urlName\"";
				elseif ("$urlSelector" == "ends with")
					$query .= " AND url RLIKE \"$urlName$\"";
			}


		// ... if the user has specified a location, add the value of '$locationName' as an AND clause:
		$locationRadio = $_POST['locationRadio'];
		if ("$locationRadio" == "1")
		{
			$locationName = $_POST['locationName'];
			if ("$locationName" != "All" && "$locationName" != "")
				{
					$locationSelector = $_POST['locationSelector'];
					if ("$locationSelector" == "contains")
						$query .= " AND location RLIKE \"$locationName\"";
					elseif ("$locationSelector" == "does not contain")
						$query .= " AND location NOT RLIKE \"$locationName\"";
					elseif ("$locationSelector" == "is equal to")
						$query .= " AND location = \"$locationName\"";
					elseif ("$locationSelector" == "is not equal to")
						$query .= " AND location != \"$locationName\"";
					elseif ("$locationSelector" == "starts with")
						$query .= " AND location RLIKE \"^$locationName\"";
					elseif ("$locationSelector" == "ends with")
						$query .= " AND location RLIKE \"$locationName$\"";
				}
		}
		elseif ("$locationRadio" == "0")
		{
			$locationName2 = $_POST['locationName2'];
			if ("$locationName2" != "")
				{
					$locationSelector2 = $_POST['locationSelector2'];
					if ("$locationSelector2" == "contains")
						$query .= " AND location RLIKE \"$locationName2\"";
					elseif ("$locationSelector2" == "does not contain")
						$query .= " AND location NOT RLIKE \"$locationName2\"";
					elseif ("$locationSelector2" == "is equal to")
						$query .= " AND location = \"$locationName2\"";
					elseif ("$locationSelector2" == "is not equal to")
						$query .= " AND location != \"$locationName2\"";
					elseif ("$locationSelector2" == "starts with")
						$query .= " AND location RLIKE \"^$locationName2\"";
					elseif ("$locationSelector2" == "ends with")
						$query .= " AND location RLIKE \"$locationName2$\"";
				}
		}

		// ... if the user has specified a call number, add the value of '$callNumberName' as an AND clause:
		$callNumberName = $_POST['callNumberName'];
		if ("$callNumberName" != "")
			{
				$callNumberSelector = $_POST['callNumberSelector'];
				if ("$callNumberSelector" == "contains")
					$query .= " AND call_number RLIKE \"$callNumberName\"";
				elseif ("$callNumberSelector" == "does not contain")
					$query .= " AND call_number NOT RLIKE \"$callNumberName\"";
				elseif ("$callNumberSelector" == "is equal to")
					$query .= " AND call_number = \"$callNumberName\"";
				elseif ("$callNumberSelector" == "is not equal to")
					$query .= " AND call_number != \"$callNumberName\"";
				elseif ("$callNumberSelector" == "starts with")
					$query .= " AND call_number RLIKE \"^$callNumberName\"";
				elseif ("$callNumberSelector" == "ends with")
					$query .= " AND call_number RLIKE \"$callNumberName$\"";
			}

		// ... if the user has specified a file, add the value of '$fileName' as an AND clause:
		$fileName = $_POST['fileName'];
		if ("$fileName" != "")
			{
				$fileSelector = $_POST['fileSelector'];
				if ("$fileSelector" == "contains")
					$query .= " AND file RLIKE \"$fileName\"";
				elseif ("$fileSelector" == "does not contain")
					$query .= " AND file NOT RLIKE \"$fileName\"";
				elseif ("$fileSelector" == "is equal to")
					$query .= " AND file = \"$fileName\"";
				elseif ("$fileSelector" == "is not equal to")
					$query .= " AND file != \"$fileName\"";
				elseif ("$fileSelector" == "starts with")
					$query .= " AND file RLIKE \"^$fileName\"";
				elseif ("$fileSelector" == "ends with")
					$query .= " AND file RLIKE \"$fileName$\"";
			}


		// ... if the user has specified a copy status, add the value of '$copyName' as an AND clause:
		$copyName = $_POST['copyName'];
		if ("$copyName" != "All" && "$copyName" != "")
			{
				$copySelector = $_POST['copySelector'];
				if ("$copySelector" == "is equal to")
					$query .= " AND copy = \"$copyName\"";
				elseif ("$copySelector" == "is not equal to")
					$query .= " AND copy != \"$copyName\"";
			}

		// ... if the user has specified some notes, add the value of '$notesName' as an AND clause:
		$notesName = $_POST['notesName'];
		if ("$notesName" != "")
			{
				$notesSelector = $_POST['notesSelector'];
				if ("$notesSelector" == "contains")
					$query .= " AND notes RLIKE \"$notesName\"";
				elseif ("$notesSelector" == "does not contain")
					$query .= " AND notes NOT RLIKE \"$notesName\"";
				elseif ("$notesSelector" == "is equal to")
					$query .= " AND notes = \"$notesName\"";
				elseif ("$notesSelector" == "is not equal to")
					$query .= " AND notes != \"$notesName\"";
				elseif ("$notesSelector" == "starts with")
					$query .= " AND notes RLIKE \"^$notesName\"";
				elseif ("$notesSelector" == "ends with")
					$query .= " AND notes RLIKE \"$notesName$\"";
			}


		// ... if the user has specified some user keys, add the value of '$userKeysName' as an AND clause:
		$userKeysRadio = $_POST['userKeysRadio'];
		if ("$userKeysRadio" == "1")
		{
			$userKeysName = $_POST['userKeysName'];
			if ("$userKeysName" != "All" && "$userKeysName" != "")
				{
					$userKeysSelector = $_POST['userKeysSelector'];
					if ("$userKeysSelector" == "contains")
						$query .= " AND user_keys RLIKE \"$userKeysName\"";
					elseif ("$userKeysSelector" == "does not contain")
						$query .= " AND user_keys NOT RLIKE \"$userKeysName\"";
					elseif ("$userKeysSelector" == "is equal to")
						$query .= " AND user_keys = \"$userKeysName\"";
					elseif ("$userKeysSelector" == "is not equal to")
						$query .= " AND user_keys != \"$userKeysName\"";
					elseif ("$userKeysSelector" == "starts with")
						$query .= " AND user_keys RLIKE \"^$userKeysName\"";
					elseif ("$userKeysSelector" == "ends with")
						$query .= " AND user_keys RLIKE \"$userKeysName$\"";
				}
		}
		elseif ("$userKeysRadio" == "0")
		{
			$userKeysName2 = $_POST['userKeysName2'];
			if ("$userKeysName2" != "")
				{
					$userKeysSelector2 = $_POST['userKeysSelector2'];
					if ("$userKeysSelector2" == "contains")
						$query .= " AND user_keys RLIKE \"$userKeysName2\"";
					elseif ("$userKeysSelector2" == "does not contain")
						$query .= " AND user_keys NOT RLIKE \"$userKeysName2\"";
					elseif ("$userKeysSelector2" == "is equal to")
						$query .= " AND user_keys = \"$userKeysName2\"";
					elseif ("$userKeysSelector2" == "is not equal to")
						$query .= " AND user_keys != \"$userKeysName2\"";
					elseif ("$userKeysSelector2" == "starts with")
						$query .= " AND user_keys RLIKE \"^$userKeysName2\"";
					elseif ("$userKeysSelector2" == "ends with")
						$query .= " AND user_keys RLIKE \"$userKeysName2$\"";
				}
		}

		// ... if the user has specified some user notes, add the value of '$userNotesName' as an AND clause:
		$userNotesName = $_POST['userNotesName'];
		if ("$userNotesName" != "")
			{
				$userNotesSelector = $_POST['userNotesSelector'];
				if ("$userNotesSelector" == "contains")
					$query .= " AND user_notes RLIKE \"$userNotesName\"";
				elseif ("$userNotesSelector" == "does not contain")
					$query .= " AND user_notes NOT RLIKE \"$userNotesName\"";
				elseif ("$userNotesSelector" == "is equal to")
					$query .= " AND user_notes = \"$userNotesName\"";
				elseif ("$userNotesSelector" == "is not equal to")
					$query .= " AND user_notes != \"$userNotesName\"";
				elseif ("$userNotesSelector" == "starts with")
					$query .= " AND user_notes RLIKE \"^$userNotesName\"";
				elseif ("$userNotesSelector" == "ends with")
					$query .= " AND user_notes RLIKE \"$userNotesName$\"";
			}

		// ... if the user has specified a user file, add the value of '$userFileName' as an AND clause:
		$userFileName = $_POST['userFileName'];
		if ("$userFileName" != "")
			{
				$userFileSelector = $_POST['userFileSelector'];
				if ("$userFileSelector" == "contains")
					$query .= " AND user_file RLIKE \"$userFileName\"";
				elseif ("$userFileSelector" == "does not contain")
					$query .= " AND user_file NOT RLIKE \"$userFileName\"";
				elseif ("$userFileSelector" == "is equal to")
					$query .= " AND user_file = \"$userFileName\"";
				elseif ("$userFileSelector" == "is not equal to")
					$query .= " AND user_file != \"$userFileName\"";
				elseif ("$userFileSelector" == "starts with")
					$query .= " AND user_file RLIKE \"^$userFileName\"";
				elseif ("$userFileSelector" == "ends with")
					$query .= " AND user_file RLIKE \"$userFileName$\"";
			}

		// ... if the user has specified a serial, add the value of '$serialNo' as an AND clause:
		$serialNo = $_POST['serialNo'];
		if ("$serialNo" != "")
			{
				$serialSelector = $_POST['serialSelector'];
				if ("$serialSelector" == "contains")
					$query .= " AND serial RLIKE \"$serialNo\"";
				elseif ("$serialSelector" == "does not contain")
					$query .= " AND serial NOT RLIKE \"$serialNo\"";
				elseif ("$serialSelector" == "is equal to")
					$query .= " AND serial = \"$serialNo\"";
				elseif ("$serialSelector" == "is not equal to")
					$query .= " AND serial != \"$serialNo\"";
				elseif ("$serialSelector" == "starts with")
					$query .= " AND serial RLIKE \"^$serialNo\"";
				elseif ("$serialSelector" == "ends with")
					$query .= " AND serial RLIKE \"$serialNo$\"";
				elseif ("$serialSelector" == "is greater than")
					$query .= " AND serial > \"$serialNo\"";
				elseif ("$serialSelector" == "is less than")
					$query .= " AND serial < \"$serialNo\"";
				elseif ("$serialSelector" == "is within list")
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
		if ("$typeRadio" == "1")
		{
			$typeName = $_POST['typeName'];
			if ("$typeName" != "All" && "$typeName" != "")
				{
					$typeSelector = $_POST['typeSelector'];
					if ("$typeSelector" == "contains")
						$query .= " AND type RLIKE \"$typeName\"";
					elseif ("$typeSelector" == "does not contain")
						$query .= " AND type NOT RLIKE \"$typeName\"";
					elseif ("$typeSelector" == "is equal to")
						$query .= " AND type = \"$typeName\"";
					elseif ("$typeSelector" == "is not equal to")
						$query .= " AND type != \"$typeName\"";
					elseif ("$typeSelector" == "starts with")
						$query .= " AND type RLIKE \"^$typeName\"";
					elseif ("$typeSelector" == "ends with")
						$query .= " AND type RLIKE \"$typeName$\"";
				}
		}
		elseif ("$typeRadio" == "0")
		{
			$typeName2 = $_POST['typeName2'];
			if ("$typeName2" != "")
				{
					$typeSelector2 = $_POST['typeSelector2'];
					if ("$typeSelector2" == "contains")
						$query .= " AND type RLIKE \"$typeName2\"";
					elseif ("$typeSelector2" == "does not contain")
						$query .= " AND type NOT RLIKE \"$typeName2\"";
					elseif ("$typeSelector2" == "is equal to")
						$query .= " AND type = \"$typeName2\"";
					elseif ("$typeSelector2" == "is not equal to")
						$query .= " AND type != \"$typeName2\"";
					elseif ("$typeSelector2" == "starts with")
						$query .= " AND type RLIKE \"^$typeName2\"";
					elseif ("$typeSelector2" == "ends with")
						$query .= " AND type RLIKE \"$typeName2$\"";
				}
		}

		// ... if the user has selected a radio button for 'Marked', add the corresponding value for 'marked' as an AND clause:
		$markedRadio = $_POST['markedRadio'];
		if ("$markedRadio" == "1")
			$query .= " AND marked = \"yes\"";		
		elseif ("$markedRadio" == "0")
			$query .= " AND marked = \"no\"";

		// ... if the user has selected a radio button for 'Selected', add the corresponding value for 'selected' as an AND clause:
		$selectedRadio = $_POST['selectedRadio'];
		if ("$selectedRadio" == "1")
			$query .= " AND selected = \"yes\"";		
		elseif ("$selectedRadio" == "0")
			$query .= " AND selected = \"no\"";

		// ... if the user has selected a radio button for 'Approved', add the corresponding value for 'approved' as an AND clause:
		$approvedRadio = $_POST['approvedRadio'];
		if ("$approvedRadio" == "1")
			$query .= " AND approved = \"yes\"";		
		elseif ("$approvedRadio" == "0")
			$query .= " AND approved = \"no\"";

		// ... if the user has specified a created date, add the value of '$createdDateNo' as an AND clause:
		$createdDateNo = $_POST['createdDateNo'];
		if ("$createdDateNo" != "")
			{
				$createdDateSelector = $_POST['createdDateSelector'];
				if ("$createdDateSelector" == "contains")
					$query .= " AND created_date RLIKE \"$createdDateNo\"";
				elseif ("$createdDateSelector" == "does not contain")
					$query .= " AND created_date NOT RLIKE \"$createdDateNo\"";
				elseif ("$createdDateSelector" == "is equal to")
					$query .= " AND created_date = \"$createdDateNo\"";
				elseif ("$createdDateSelector" == "is not equal to")
					$query .= " AND created_date != \"$createdDateNo\"";
				elseif ("$createdDateSelector" == "starts with")
					$query .= " AND created_date RLIKE \"^$createdDateNo\"";
				elseif ("$createdDateSelector" == "ends with")
					$query .= " AND created_date RLIKE \"$createdDateNo$\"";
				elseif ("$createdDateSelector" == "is greater than")
					$query .= " AND created_date > \"$createdDateNo\"";
				elseif ("$createdDateSelector" == "is less than")
					$query .= " AND created_date < \"$createdDateNo\"";
			}

		// ... if the user has specified a created time, add the value of '$createdTimeNo' as an AND clause:
		$createdTimeNo = $_POST['createdTimeNo'];
		if ("$createdTimeNo" != "")
			{
				$createdTimeSelector = $_POST['createdTimeSelector'];
				if ("$createdTimeSelector" == "contains")
					$query .= " AND created_time RLIKE \"$createdTimeNo\"";
				elseif ("$createdTimeSelector" == "does not contain")
					$query .= " AND created_time NOT RLIKE \"$createdTimeNo\"";
				elseif ("$createdTimeSelector" == "is equal to")
					$query .= " AND created_time = \"$createdTimeNo\"";
				elseif ("$createdTimeSelector" == "is not equal to")
					$query .= " AND created_time != \"$createdTimeNo\"";
				elseif ("$createdTimeSelector" == "starts with")
					$query .= " AND created_time RLIKE \"^$createdTimeNo\"";
				elseif ("$createdTimeSelector" == "ends with")
					$query .= " AND created_time RLIKE \"$createdTimeNo$\"";
				elseif ("$createdTimeSelector" == "is greater than")
					$query .= " AND created_time > \"$createdTimeNo\"";
				elseif ("$createdTimeSelector" == "is less than")
					$query .= " AND created_time < \"$createdTimeNo\"";
			}

		// ... if the user has specified a created by, add the value of '$createdByName' as an AND clause:
		$createdByRadio = $_POST['createdByRadio'];
		if ("$createdByRadio" == "1")
		{
			$createdByName = $_POST['createdByName'];
			if ("$createdByName" != "All" && "$createdByName" != "")
				{
					$createdBySelector = $_POST['createdBySelector'];
					if ("$createdBySelector" == "contains")
						$query .= " AND created_by RLIKE \"$createdByName\"";
					elseif ("$createdBySelector" == "does not contain")
						$query .= " AND created_by NOT RLIKE \"$createdByName\"";
					elseif ("$createdBySelector" == "is equal to")
						$query .= " AND created_by = \"$createdByName\"";
					elseif ("$createdBySelector" == "is not equal to")
						$query .= " AND created_by != \"$createdByName\"";
					elseif ("$createdBySelector" == "starts with")
						$query .= " AND created_by RLIKE \"^$createdByName\"";
					elseif ("$createdBySelector" == "ends with")
						$query .= " AND created_by RLIKE \"$createdByName$\"";
				}
		}
		elseif ("$createdByRadio" == "0")
		{
			$createdByName2 = $_POST['createdByName2'];
			if ("$createdByName2" != "")
				{
					$createdBySelector2 = $_POST['createdBySelector2'];
					if ("$createdBySelector2" == "contains")
						$query .= " AND created_by RLIKE \"$createdByName2\"";
					elseif ("$createdBySelector2" == "does not contain")
						$query .= " AND created_by NOT RLIKE \"$createdByName2\"";
					elseif ("$createdBySelector2" == "is equal to")
						$query .= " AND created_by = \"$createdByName2\"";
					elseif ("$createdBySelector2" == "is not equal to")
						$query .= " AND created_by != \"$createdByName2\"";
					elseif ("$createdBySelector2" == "starts with")
						$query .= " AND created_by RLIKE \"^$createdByName2\"";
					elseif ("$createdBySelector2" == "ends with")
						$query .= " AND created_by RLIKE \"$createdByName2$\"";
				}
		}

		// ... if the user has specified a modified date, add the value of '$modifiedDateNo' as an AND clause:
		$modifiedDateNo = $_POST['modifiedDateNo'];
		if ("$modifiedDateNo" != "")
			{
				$modifiedDateSelector = $_POST['modifiedDateSelector'];
				if ("$modifiedDateSelector" == "contains")
					$query .= " AND modified_date RLIKE \"$modifiedDateNo\"";
				elseif ("$modifiedDateSelector" == "does not contain")
					$query .= " AND modified_date NOT RLIKE \"$modifiedDateNo\"";
				elseif ("$modifiedDateSelector" == "is equal to")
					$query .= " AND modified_date = \"$modifiedDateNo\"";
				elseif ("$modifiedDateSelector" == "is not equal to")
					$query .= " AND modified_date != \"$modifiedDateNo\"";
				elseif ("$modifiedDateSelector" == "starts with")
					$query .= " AND modified_date RLIKE \"^$modifiedDateNo\"";
				elseif ("$modifiedDateSelector" == "ends with")
					$query .= " AND modified_date RLIKE \"$modifiedDateNo$\"";
				elseif ("$modifiedDateSelector" == "is greater than")
					$query .= " AND modified_date > \"$modifiedDateNo\"";
				elseif ("$modifiedDateSelector" == "is less than")
					$query .= " AND modified_date < \"$modifiedDateNo\"";
			}

		// ... if the user has specified a modified time, add the value of '$modifiedTimeNo' as an AND clause:
		$modifiedTimeNo = $_POST['modifiedTimeNo'];
		if ("$modifiedTimeNo" != "")
			{
				$modifiedTimeSelector = $_POST['modifiedTimeSelector'];
				if ("$modifiedTimeSelector" == "contains")
					$query .= " AND modified_time RLIKE \"$modifiedTimeNo\"";
				elseif ("$modifiedTimeSelector" == "does not contain")
					$query .= " AND modified_time NOT RLIKE \"$modifiedTimeNo\"";
				elseif ("$modifiedTimeSelector" == "is equal to")
					$query .= " AND modified_time = \"$modifiedTimeNo\"";
				elseif ("$modifiedTimeSelector" == "is not equal to")
					$query .= " AND modified_time != \"$modifiedTimeNo\"";
				elseif ("$modifiedTimeSelector" == "starts with")
					$query .= " AND modified_time RLIKE \"^$modifiedTimeNo\"";
				elseif ("$modifiedTimeSelector" == "ends with")
					$query .= " AND modified_time RLIKE \"$modifiedTimeNo$\"";
				elseif ("$modifiedTimeSelector" == "is greater than")
					$query .= " AND modified_time > \"$modifiedTimeNo\"";
				elseif ("$modifiedTimeSelector" == "is less than")
					$query .= " AND modified_time < \"$modifiedTimeNo\"";
			}

		// ... if the user has specified a modified by, add the value of '$modifiedByName' as an AND clause:
		$modifiedByRadio = $_POST['modifiedByRadio'];
		if ("$modifiedByRadio" == "1")
		{
			$modifiedByName = $_POST['modifiedByName'];
			if ("$modifiedByName" != "All" && "$modifiedByName" != "")
				{
					$modifiedBySelector = $_POST['modifiedBySelector'];
					if ("$modifiedBySelector" == "contains")
						$query .= " AND modified_by RLIKE \"$modifiedByName\"";
					elseif ("$modifiedBySelector" == "does not contain")
						$query .= " AND modified_by NOT RLIKE \"$modifiedByName\"";
					elseif ("$modifiedBySelector" == "is equal to")
						$query .= " AND modified_by = \"$modifiedByName\"";
					elseif ("$modifiedBySelector" == "is not equal to")
						$query .= " AND modified_by != \"$modifiedByName\"";
					elseif ("$modifiedBySelector" == "starts with")
						$query .= " AND modified_by RLIKE \"^$modifiedByName\"";
					elseif ("$modifiedBySelector" == "ends with")
						$query .= " AND modified_by RLIKE \"$modifiedByName$\"";
				}
		}
		elseif ("$modifiedByRadio" == "0")
		{
			$modifiedByName2 = $_POST['modifiedByName2'];
			if ("$modifiedByName2" != "")
				{
					$modifiedBySelector2 = $_POST['modifiedBySelector2'];
					if ("$modifiedBySelector2" == "contains")
						$query .= " AND modified_by RLIKE \"$modifiedByName2\"";
					elseif ("$modifiedBySelector2" == "does not contain")
						$query .= " AND modified_by NOT RLIKE \"$modifiedByName2\"";
					elseif ("$modifiedBySelector2" == "is equal to")
						$query .= " AND modified_by = \"$modifiedByName2\"";
					elseif ("$modifiedBySelector2" == "is not equal to")
						$query .= " AND modified_by != \"$modifiedByName2\"";
					elseif ("$modifiedBySelector2" == "starts with")
						$query .= " AND modified_by RLIKE \"^$modifiedByName2\"";
					elseif ("$modifiedBySelector2" == "ends with")
						$query .= " AND modified_by RLIKE \"$modifiedByName2$\"";
				}
		}


		// Construct the ORDER BY clause:
		$query .= " ORDER BY ";

		// A) extract first level sort option:
		$sortSelector1 = $_POST['sortSelector1'];
		if ("$sortSelector1" != "")
			{
				// when field name = 'pages' then sort by 'first_page' instead:
				$sortSelector1 = str_replace("pages", "first_page", $sortSelector1);
		
				$sortRadio1 = $_POST['sortRadio1'];
				if ("$sortRadio1" == "0") // sort ascending
					$query .= "$sortSelector1";
				else // sort descending
					$query .= "$sortSelector1 DESC";
			}

		// B) extract second level sort option:
		$sortSelector2 = $_POST['sortSelector2'];
		if ("$sortSelector2" != "")
			{
				// when field name = 'pages' then sort by 'first_page' instead:
				$sortSelector2 = str_replace("pages", "first_page", $sortSelector2);
		
				$sortRadio2 = $_POST['sortRadio2'];
				if ("$sortRadio2" == "0") // sort ascending
					$query .= ", $sortSelector2";
				else // sort descending
					$query .= ", $sortSelector2 DESC";
			}

		// C) extract third level sort option:
		$sortSelector3 = $_POST['sortSelector3'];
		if ("$sortSelector3" != "")
			{
				// when field name = 'pages' then sort by 'first_page' instead:
				$sortSelector3 = str_replace("pages", "first_page", $sortSelector3);
		
				$sortRadio3 = $_POST['sortRadio3'];
				if ("$sortRadio3" == "0") // sort ascending
					$query .= ", $sortSelector3";
				else // sort descending
					$query .= ", $sortSelector3 DESC";
			}

		// Since the sort popup menus use empty fields as delimiters between groups of fields
		// we'll have to trap the case that the user hasn't chosen any field names for sorting:
		if (ereg("ORDER BY $", $query) == true)
			$query .= "author, year DESC, publication"; // use the default ORDER BY clause

		// Finally, fix the wrong syntax where its says "ORDER BY, author, title, ..." instead of "ORDER BY author, title, ...":
		$query = str_replace("ORDER BY , ","ORDER BY ",$query);


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Search within Results" form above the query results list (which, in turn, was returned by 'search.php'):
	function extractFormElementsRefine($displayType, $sqlQuery, $showLinks, $userID)
	{
		$refineSearchSelector = $_POST['refineSearchSelector']; // extract field name chosen by the user
		$refineSearchName = $_POST['refineSearchName']; // extract search text entered by the user

		if (isset($_POST['showRefineSearchFieldRadio']))
			$showRefineSearchFieldRadio = $_POST['showRefineSearchFieldRadio']; // extract user option whether searched field should be displayed
		else
			$showRefineSearchFieldRadio = ""; // set variable to "" in order to prevent 'Undefined variable...' messages

		$refineSearchActionRadio = $_POST['refineSearchActionRadio']; // extract user option whether matched records should be included or excluded

		$query = rawurldecode($sqlQuery); // URL decode SQL query (it was URL encoded before incorporation into a hidden tag of the 'refineSearch' form to avoid any HTML syntax errors)
											// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
											//       But, opposed to that, URL encoded data that are included within a form by means of a hidden form tag will *NOT* get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

		if (ereg("^(marked|copy|selected|user_keys|user_notes|user_file)$", $refineSearchSelector) == true) // if the chosen field is one of the user specific fields from table 'user_data': 'marked', 'copy', 'selected', 'user_keys', 'user_notes' or 'user_file'
			if (ereg("LEFT JOIN user_data", $query) != true) // if the 'LEFT JOIN...' statement isn't already part of the 'FROM' clause...
				$query = str_replace(" FROM refs"," FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = $userID",$query); // ...add the 'LEFT JOIN...' part to the 'FROM' clause

		if ("$showRefineSearchFieldRadio" == "1") // if the user checked the radio button next to 'Show column'...
			{
				if (!preg_match("/SELECT.*\W$refineSearchSelector\W.*FROM refs/", $query)) // ...and the field is *not* already displayed...
					$query = str_replace(" FROM refs",", $refineSearchSelector FROM refs",$query); // ...then SHOW the field that was used for refining the search results
			}
		elseif ("$showRefineSearchFieldRadio" == "0") // if the user checked the radio button next to 'Hide column'...
			{
				if (preg_match("/SELECT.*\W$refineSearchSelector\W.*FROM refs/", $query)) // ...and the field *is* currently displayed...
					// for all columns except the first:
					$query = preg_replace("/(SELECT.+?), $refineSearchSelector( .*FROM refs)/","\\1\\2",$query); // ...then HIDE the field that was used for refining the search results
					// for all columns except the last:
					$query = preg_replace("/(SELECT.*? )$refineSearchSelector, (.+FROM refs)/","\\1\\2",$query); // ...then HIDE the field that was used for refining the search results
			}
		// else if $showRefineSearchFieldRadio == '' (which is the form's default) we don't change the display of any columns

		$query = str_replace(' FROM refs',', orig_record FROM refs',$query); // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
																				// (which is required in order to present visual feedback on duplicate records)

		$query = str_replace(' FROM refs',', serial FROM refs',$query); // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
																		// (which is required in order to obtain unique checkbox names)

		if ("$showLinks" == "1")
			$query = str_replace(' FROM refs',', file, url, doi FROM refs',$query); // add 'file', 'url' & 'doi' columns

		if ("$refineSearchName" != "") // if the user typed a search string into the text entry field...
		{
			// Depending on the chosen output action, construct an appropriate SQL query:
			if ($refineSearchActionRadio == "1") // if the user checked the radio button next to "Restrict to matched records"
				{
					// for the fields 'marked=no', 'copy=false' and 'selected=no', force NULL values to be matched:
					if (($refineSearchSelector == "marked" AND $refineSearchName == "no") OR ($refineSearchSelector == "copy" AND $refineSearchName == "false") OR ($refineSearchSelector == "selected" AND $refineSearchName == "no"))
						$query = str_replace("WHERE","WHERE ($refineSearchSelector RLIKE \"$refineSearchName\" OR $refineSearchSelector IS NULL) AND",$query); // ...add search field name & value to the sql query
					else // add default 'WHERE' clause:
						$query = str_replace("WHERE","WHERE $refineSearchSelector RLIKE \"$refineSearchName\" AND",$query); // ...add search field name & value to the sql query
				}
			else // $refineSearchActionRadio == "0" // if the user checked the radio button next to "Exclude matched records"
				{
					// for the fields 'marked=yes', 'copy!=false' and 'selected=yes', force NULL values to be excluded:
					if (($refineSearchSelector == "marked" AND $refineSearchName == "yes") OR ($refineSearchSelector == "copy" AND $refineSearchName != "false") OR ($refineSearchSelector == "selected" AND $refineSearchName == "yes"))
						$query = str_replace("WHERE","WHERE ($refineSearchSelector NOT RLIKE \"$refineSearchName\" OR $refineSearchSelector IS NULL) AND",$query); // ...add search field name & value to the sql query
					else // add default 'WHERE' clause:
						$query = str_replace("WHERE","WHERE $refineSearchSelector NOT RLIKE \"$refineSearchName\" AND",$query); // ...add search field name & value to the sql query
				}
			$query = str_replace(' AND serial RLIKE ".+"','',$query); // remove any 'AND serial RLIKE ".+"' which isn't required anymore
		}

		// else, if the user did NOT type a search string into the text entry field, we simply keep the old WHERE clause...


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from records selected by the user within the query results list (which, in turn, was returned by 'search.php'):
	function extractFormElementsQueryResults($displayType, $showLinks, $exportOrder, $orderBy, $userID)
	{
		// Extract checkbox variable values from the request:
		if (isset($_POST['marked']))
			$recordSerialsArray = $_POST['marked']; // extract the values of all checked checkboxes (i.e., the serials of all selected records)
		else
			$recordSerialsArray = "";
			
		// join array elements:
		if (!empty($recordSerialsArray)) // the user did check some checkboxes
			$recordSerialsString = implode("|", $recordSerialsArray); // separate record serials by "|" in order to facilitate regex querying...
		else // the user didn't check any checkboxes
			$recordSerialsString = "0"; // we use '0' which definitely doesn't exist as serial, resulting in a "nothing found" feedback


		// Depending on the chosen output format, construct an appropriate SQL query:
		if ($displayType == "Export")
			{
			// Note: since we won't query any user specific fields (like 'marked', 'copy', 'selected', 'user_keys', 'user_notes' or 'user_file') we skip the 'LEFT JOIN...' part of the 'FROM' clause:
			if ($exportOrder == "year") // sort records first by year (descending), then in the usual way:
				$query = "SELECT type, author, year, title, publication, abbrev_journal, volume, issue, pages, thesis, editor, publisher, place, abbrev_series_title, series_title, series_editor, series_volume, series_issue, language, author_count, online_publication, online_citation, doi, serial FROM refs WHERE serial RLIKE \"^(" . $recordSerialsString . ")$\" ORDER BY year DESC, first_author, author_count, author, title";
			else // if any other or no '$exportOrder' parameter is specified, we supply the default ORDER BY pattern (which is suitable for citation in a journal etc.):
				$query = "SELECT type, author, year, title, publication, abbrev_journal, volume, issue, pages, thesis, editor, publisher, place, abbrev_series_title, series_title, series_editor, series_volume, series_issue, language, author_count, online_publication, online_citation, doi, serial FROM refs WHERE serial RLIKE \"^(" . $recordSerialsString . ")$\" ORDER BY first_author, author_count, author, year, title";
			}
		else // $displayType == "Display" (hitting <enter> within the 'ShowRows' text entry field will act as if the user clicked the 'Display' button)
			{
				// for the selected records, select *all* available fields:
				// (note: we also add the 'serial' column at the end in order to provide standardized input [compare processing of form 'sql_search.php'])
				if (session_is_registered("loginEmail")) // if a user is logged in...
					$query = "SELECT author, title, type, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved, location, call_number, serial, marked, copy, selected, user_keys, user_notes, user_file, orig_record, serial";
				else // NO user logged in
					$query = "SELECT author, title, type, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved, location, call_number, serial, orig_record, serial";

				if ("$showLinks" == "1")
					$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

				if (session_is_registered("loginEmail")) // if a user is logged in...
					$query .= " FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = " . $userID . " WHERE serial RLIKE \"^(" . $recordSerialsString . ")$\" ORDER BY $orderBy";
				else // NO user logged in
					$query .= " FROM refs WHERE serial RLIKE \"^(" . $recordSerialsString . ")$\" ORDER BY $orderBy";
			}


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the 'extract.php' form:
	function extractFormElementsExtract($exportOrder)
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
		// Note: since we won't query any user specific fields (like 'marked', 'copy', 'selected', 'user_keys', 'user_notes' or 'user_file') we skip the 'LEFT JOIN...' part of the 'FROM' clause:
		if ($exportOrder == "year") // sort records first by year (descending), then in the usual way:
			$query = "SELECT type, author, year, title, publication, abbrev_journal, volume, issue, pages, thesis, editor, publisher, place, abbrev_series_title, series_title, series_editor, series_volume, series_issue, language, author_count, online_publication, online_citation, doi, serial FROM refs WHERE serial RLIKE \"^(" . $recordSerialsString . ")$\" ORDER BY year DESC, first_author, author_count, author, title";
		else // if any other or no '$exportOrder' parameter is specified, we supply the default ORDER BY pattern (which is suitable for citation in a journal etc.):
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

		if ("$showLinks" == "1")
			$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

		// Note: since we won't query any user specific fields (like 'marked', 'copy', 'selected', 'user_keys', 'user_notes' or 'user_file') we skip the 'LEFT JOIN...' part of the 'FROM' clause:
		$query .= " FROM refs WHERE serial RLIKE \".+\""; // add FROM & (initial) WHERE clause
		
		if ("$quickSearchName" != "") // if the user typed a search string into the text entry field...
			$query .= " AND $quickSearchSelector RLIKE \"$quickSearchName\""; // ...add search field name & value to the sql query

		$query .= " ORDER BY author, year DESC, publication"; // add the default ORDER BY clause


		return $query;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Show My Refs" form on the
	// main page ('index.php') which searches the user specific fields from table 'user_data':
	function extractFormElementsMyRefs($showLinks, $loginEmail, $userID)
	{
		$query = "SELECT author, title, year, publication, volume, pages";

		$myRefsRadio = $_POST['myRefsRadio']; // will be "1" if the user wants to display ALL of his records, otherwise it will be "0"

		// extract form popup 'marked/not marked':
		if (isset($_REQUEST['findMarked']))
			$findMarked = $_POST['findMarked']; // will be "1" if the user wants to search the 'marked' field
		else
			$findMarked = "";

		if (isset($_REQUEST['markedSelector']))
			$markedSelector = $_POST['markedSelector']; // extract 'marked' field value chosen by the user
		else
			$markedSelector = "";

		// extract form popup 'selected/not selected':
		if (isset($_REQUEST['findSelected']))
			$findSelected = $_POST['findSelected']; // will be "1" if the user wants to search the 'selected' field
		else
			$findSelected = "";

		if (isset($_REQUEST['selectedSelector']))
			$selectedSelector = $_POST['selectedSelector']; // extract 'selected' field value chosen by the user
		else
			$selectedSelector = "";

		// extract form popup 'copy = true/fetch/ordered/false':
		if (isset($_REQUEST['findCopy']))
			$findCopy = $_POST['findCopy']; // will be "1" if the user wants to search the 'copy' field
		else
			$findCopy = "";

		if (isset($_REQUEST['copySelector']))
			$copySelector = $_POST['copySelector']; // extract 'copy' field value chosen by the user
		else
			$copySelector = "";

		// extract form text entry field 'key':
		if (isset($_REQUEST['findUserKeys']))
			$findUserKeys = $_POST['findUserKeys']; // will be "1" if the user wants to search the 'user_keys' field
		else
			$findUserKeys = "";

		if (isset($_REQUEST['userKeysName']))
			$userKeysName = $_POST['userKeysName']; // extract user keys entered by the user
		else
			$userKeysName = "";

		// extract form text entry field 'note':
		if (isset($_REQUEST['findUserNotes']))
			$findUserNotes = $_POST['findUserNotes']; // will be "1" if the user wants to search the 'user_notes' field
		else
			$findUserNotes = "";

		if (isset($_REQUEST['userNotesName']))
			$userNotesName = $_POST['userNotesName']; // extract user notes entered by the user
		else
			$userNotesName = "";

		// extract form text entry field 'file':
		if (isset($_REQUEST['findUserFile']))
			$findUserFile = $_POST['findUserFile']; // will be "1" if the user wants to search the 'user_file' field
		else
			$findUserFile = "";

		if (isset($_REQUEST['userFileName']))
			$userFileName = $_POST['userFileName']; // extract file specification entered by the user
		else
			$userFileName = "";

		if ("$myRefsRadio" == "0") // if the user only wants to display a subset of his records:
			{
				if ("$findMarked" == "1") // if the user wants to search the 'marked' field...
					$query .= ", marked"; // ...add 'marked' field to SELECT query
		
				if ("$findSelected" == "1") // if the user wants to search the 'selected' field...
					$query .= ", selected"; // ...add 'selected' field to SELECT query
		
				if ("$findCopy" == "1") // if the user wants to search the 'copy' field...
					$query .= ", copy"; // ...add 'copy' field to SELECT query
		
				if ("$findUserKeys" == "1") // if the user wants to search the 'user_keys' field...
					$query .= ", user_keys"; // ...add 'user_keys' to SELECT query
		
				if ("$findUserNotes" == "1") // if the user wants to search the 'user_notes' field...
					$query .= ", user_notes"; // ...add 'user_notes' to SELECT query

				if ("$findUserFile" == "1") // if the user wants to search the 'user_file' field...
					$query .= ", user_file"; // ...add 'user_file' to SELECT query
			}

		$query .= ", orig_record"; // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
								//  (which is required in order to present visual feedback on duplicate records)

		$query .= ", serial"; // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
							//  (which is required in order to obtain unique checkbox names)

		if ("$showLinks" == "1")
			$query .= ", file, url, doi"; // add 'file', 'url' & 'doi' columns

		$query .= " FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = " . $userID . " WHERE location RLIKE \"$loginEmail\""; // add FROM & (initial) WHERE clause
		

		if ("$myRefsRadio" == "0") // if the user only wants to display a subset of his records:
			{
				if ("$findMarked" == "1") // if the user wants to search the 'marked' field...
					{
						if ("$markedSelector" == "marked")
							$query .= " AND marked = \"yes\""; // ...add 'marked' field name & value to the sql query
						else // $markedSelector == "not marked" (i.e., 'marked' is either 'no' -or- NULL)
							$query .= " AND (marked = \"no\" OR marked IS NULL)"; // ...add 'marked' field name & value to the sql query
					}

				if ("$findSelected" == "1") // if the user wants to search the 'selected' field...
					{
						if ("$selectedSelector" == "selected")
							$query .= " AND selected = \"yes\""; // ...add 'selected' field name & value to the sql query
						else // $selectedSelector == "not selected" (i.e., 'selected' is either 'no' -or- NULL)
							$query .= " AND (selected = \"no\" OR selected IS NULL)"; // ...add 'selected' field name & value to the sql query
					}

				if ("$findCopy" == "1") // if the user wants to search the 'copy' field...
					{
						if ("$copySelector" == "true")
							$query .= " AND copy = \"true\""; // ...add 'copy' field name & value to the sql query
						elseif ("$copySelector" == "ordered")
							$query .= " AND copy = \"ordered\""; // ...add 'copy' field name & value to the sql query
						elseif ("$copySelector" == "fetch")
							$query .= " AND copy = \"fetch\""; // ...add 'copy' field name & value to the sql query
						else // 'copy' is either 'false' -or- NULL
							$query .= " AND (copy = \"false\" OR copy IS NULL)"; // ...add 'copy' field name & value to the sql query
					}

				if ("$findUserKeys" == "1") // if the user wants to search the 'user_keys' field...
					if ("$userKeysName" != "") // if the user typed a search string into the text entry field...
						$query .= " AND user_keys RLIKE \"$userKeysName\""; // ...add 'user_keys' field name & value to the sql query
		
				if ("$findUserNotes" == "1") // if the user wants to search the 'user_notes' field...
					if ("$userNotesName" != "") // if the user typed a search string into the text entry field...
						$query .= " AND user_notes RLIKE \"$userNotesName\""; // ...add 'user_notes' field name & value to the sql query

				if ("$findUserFile" == "1") // if the user wants to search the 'user_file' field...
					if ("$userFileName" != "") // if the user typed a search string into the text entry field...
						$query .= " AND user_file RLIKE \"$userFileName\""; // ...add 'user_file' field name & value to the sql query
			}


		$query .= " ORDER BY author, year DESC, publication"; // add the default ORDER BY clause


		return $query;
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter($oldQuery);

	// --------------------------------------------------------------------
?>
</body>
</html>	 
