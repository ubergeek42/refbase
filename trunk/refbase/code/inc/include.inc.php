<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./include.inc.php
	// Created:    16-Apr-02, 10:54
	// Modified:   27-Feb-05, 20:52

	// This file contains important
	// functions that are shared
	// between all scripts.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// Untaint user data:
	function clean($input, $maxlength)
	{
		$input = substr($input, 0, $maxlength);
		$input = EscapeShellCmd($input);
		return ($input);
	}

	// --------------------------------------------------------------------

	// Start a session:
	function start_session($updateUserFormatsStylesTypesPermissions)
	{
		global $loginEmail;
		global $loginUserID;
		global $loginFirstName;
		global $loginLastName;
		global $abbrevInstitution;
//		global $referer;

		// Initialize the session:
		session_start();

		// Extract session variables (only necessary if register globals is OFF!):
		if (isset($_SESSION['loginEmail']))
		{
			$loginEmail = $_SESSION['loginEmail'];
			$loginUserID = $_SESSION['loginUserID'];
			$loginFirstName = $_SESSION['loginFirstName'];
			$loginLastName = $_SESSION['loginLastName'];
			$abbrevInstitution = $_SESSION['abbrevInstitution'];
		}
		elseif ($updateUserFormatsStylesTypesPermissions)
			// if the user isn't logged in we set the available export formats, citation styles, document types and permissions to
			// the defaults which are specified in the 'formats', 'styles', 'types' and 'user_permissions' tables for 'user_id = 0'.
			// (a 'user_id' of zero is used within these tables to indicate the default settings if the user isn't logged in)
		{
			// Get all export formats that were selected by the admin to be visible if a user isn't logged in
			// and (if some formats were found) save them as semicolon-delimited string to the session variable 'user_formats':
			getVisibleUserFormatsStylesTypes(0, "format", "export");

			// Get all citation styles that were selected by the admin to be visible if a user isn't logged in
			// and (if some styles were found) save them as semicolon-delimited string to the session variable 'user_styles':
			getVisibleUserFormatsStylesTypes(0, "style", "");

			// Get all document types that were selected by the admin to be visible if a user isn't logged in
			// and (if some types were found) save them as semicolon-delimited string to the session variable 'user_types':
			getVisibleUserFormatsStylesTypes(0, "type", "");

			// Get the user permissions for the current user
			// and save all allowed user actions as semicolon-delimited string to the session variable 'user_permissions':
			getPermissions(0, "user", true);
		}

//		if (isset($_SESSION['referer']))
//			$referer = $_SESSION['referer'];
	}

	// --------------------------------------------------------------------

	// Create a new session variable:
	function saveSessionVariable($sessionVariableName, $sessionVariableContents)
	{
		// since PHP 4.1.0 or greater, adding variables directly to the '$_SESSION' variable
		//  will register a session variable regardless whether register globals is ON or OFF!
		$_SESSION[$sessionVariableName] = $sessionVariableContents;
	}

	// --------------------------------------------------------------------

	// Remove a session variable:
	function deleteSessionVariable($sessionVariableName)
	{
		if (ini_get('register_globals') == 1) // register globals is ON for the current directory
			session_unregister($sessionVariableName); // clear the specified session variable
		else // register globals is OFF for the current directory
			unset($_SESSION[$sessionVariableName]); // clear the specified session variable
	}

	// --------------------------------------------------------------------

	// Connect to the MySQL database:
	function connectToMySQLDatabase($oldQuery)
	{
		global $hostName; // these variables are specified in 'db.inc.php' 
		global $username;
		global $password;
		global $databaseName;

		global $connection;

		// If a connection parameter is not available, then use our own connection to avoid any locking problems
		if (!isset($connection))
		{
			// (1) OPEN the database connection:
			//      (variables are set by include file 'db.inc.php'!)
			if (!($connection = @ mysql_connect($hostName, $username, $password)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to connect to the host:", $oldQuery);
		
			// (2) SELECT the database:
			//      (variables are set by include file 'db.inc.php'!)
			if (!(mysql_select_db($databaseName, $connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to connect to the database:", $oldQuery);
		}
	}

	// --------------------------------------------------------------------

	// Query the MySQL database:
	function queryMySQLDatabase($query, $oldQuery)
	{
		global $connection;

		// (3) RUN the query on the database through the connection:
		if (!($result = @ mysql_query ($query, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", $oldQuery);

		return $result;
	}

	// --------------------------------------------------------------------

	// Disconnect from the MySQL database:
	function disconnectFromMySQLDatabase($oldQuery)
	{
		global $connection;

		if (isset($connection))
			// (5) CLOSE the database connection:
			if (!(mysql_close($connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to disconnect from the database:", $oldQuery);
	}

	// --------------------------------------------------------------------

	// Show error (prepares error output and redirects it to 'error.php' which displays the error message):
	function showErrorMsg($headerMsg, $oldQuery)
	{
		$errorNo = mysql_errno();
		$errorMsg = mysql_error();
		header("Location: error.php?errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery));
		exit;
	}

	// --------------------------------------------------------------------

	// Show whether the user is logged in or not:
	function showLogin()
	{
		global $loginEmail;
		global $loginWelcomeMsg;
		global $loginFirstName;
		global $loginLastName;
		global $abbrevInstitution;
		global $loginUserID;
		global $loginStatus;
		global $loginLinks;
		global $adminLoginEmail; // ('$adminLoginEmail' is specified in 'ini.inc.php')


		// Read session variables:
		if (isset($_SESSION['loginUserID']))
			$loginUserID = $_SESSION['loginUserID'];


//		$referer = $_SERVER["REQUEST_URI"]; // 'REQUEST_URI' does only seem to work for GET requests (but not for POST requests!) ?:-/
		// so, as a workaround, we build an appropriate query string from scratch (which will also work for POST requests):

		// --- BEGIN WORKAROUND ---
		global $formType;
		global $displayType;
		global $queryURL;
		global $showQuery;
		global $showLinks;
		global $showRows;
		global $rowOffset;

		global $citeStyle;
		global $citeOrder;
		global $orderBy;

		global $recordAction;
		global $serialNo;
		global $headerMsg;
		global $oldQuery;
		
		global $errorNo;
		global $errorMsg;
		
		// Extract checkbox variable values from the request:
		if (isset($_REQUEST['marked']))
			$recordSerialsArray = $_REQUEST['marked']; // extract the values of all checked checkboxes (i.e., the serials of all selected records)	
		else
			$recordSerialsArray = "";
		$recordSerialsString = ""; // initialize variable
		// join array elements:
		if (!empty($recordSerialsArray)) // the user did check some checkboxes
			$recordSerialsString = implode("&marked[]=", $recordSerialsArray); // prefix each record serial (except the first one) with "&marked[]="
		$recordSerialsString = "&marked[]=" . $recordSerialsString; // prefix also the very first record serial with "&marked[]="
		
		// based on the refering script we adjust the parameters that get included in the link:
		if (ereg(".*(index|simple_search|advanced_search|sql_search|library_search|extract|users|user_details|user_receipt)\.php", $_SERVER["SCRIPT_NAME"]))
			$referer = $_SERVER["SCRIPT_NAME"]; // we don't need to provide any parameters if the user clicked login/logout on the main page or any of the search pages (we just need to re-locate
												// back to these pages after successful login/logout). Logout on 'users.php', 'user_details.php' or 'user_receipt.php' will redirect to 'index.php'.

		elseif (ereg(".*(record|receipt)\.php", $_SERVER["SCRIPT_NAME"]))
			$referer = $_SERVER["SCRIPT_NAME"] . "?" . "recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery);

		elseif (ereg(".*error\.php", $_SERVER["SCRIPT_NAME"]))
			$referer = $_SERVER["SCRIPT_NAME"] . "?" . "errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery);

		else
			$referer = $_SERVER["SCRIPT_NAME"] . "?" . "formType=" . "sqlSearch" . "&submit=" . $displayType . "&headerMsg=" . rawurlencode($headerMsg) . "&sqlQuery=" . $queryURL . "&showQuery=" . $showQuery . "&showLinks=" . $showLinks . "&showRows=" . $showRows . "&rowOffset=" . $rowOffset . $recordSerialsString . "&citeStyleSelector=" . rawurlencode($citeStyle) . "&citeOrder=" . $citeOrder . "&orderBy=" . rawurlencode($orderBy) . "&oldQuery=" . rawurlencode($oldQuery);
		// --- END WORKAROUND -----

		// Is the user logged in?
		if (isset($_SESSION['loginEmail']))
			{
				$loginWelcomeMsg = "Welcome<br><em>" . encodeHTML($loginFirstName) . " " . encodeHTML($loginLastName) . "</em>!";

				if ($loginEmail == $adminLoginEmail)
					$loginStatus = "You're logged in as<br><span class=\"warning\">Admin</span> (<em>" . $loginEmail . "</em>)";
				else
					$loginStatus = "You're logged in as<br><em>" . $loginEmail . "</em>";

				$loginLinks = "";
				if ($loginEmail == $adminLoginEmail) // if the admin is logged in, add the 'Add User' & 'Manage Users' links:
				{
					$loginLinks .= "<a href=\"user_details.php\" title=\"add a user to the database\">Add User</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
					$loginLinks .= "<a href=\"users.php\" title=\"manage user data\">Manage Users</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				}
				else // if a normal user is logged in, we add the 'My Refs' and 'Options' links instead:
				{
					$loginLinks .= "<a href=\"search.php?formType=myRefsSearch&amp;showQuery=0&amp;showLinks=1&amp;myRefsRadio=1\" title=\"display all of your records\">My Refs</a>&nbsp;&nbsp;|&nbsp;&nbsp;";

					if (isset($_SESSION['user_permissions']) AND ereg("allow_modify_options", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_modify_options'...
					// ... include a link to 'user_receipt.php':
						$loginLinks .= "<a href=\"user_receipt.php?userID=" . $loginUserID . "\" title=\"view and modify your account details and options\">Options</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				}
				$loginLinks .= "<a href=\"user_logout.php?referer=" . rawurlencode($referer) . "\" title=\"logout from the database\">Logout</a>";
			}
		else
			{
				$loginWelcomeMsg = "";

				if (ereg(".*(record|import[^.]*)\.php", $_SERVER["SCRIPT_NAME"]))
					$loginStatus = "<span class=\"warning\">You must be logged in<br>to submit this form!</span>";
				else
					$loginStatus = "";

				$loginLinks = "<a href=\"user_login.php?referer=" . rawurlencode($referer) . "\" title=\"login to the database\">Login</a>";
			}

		// Write back session variables:
		saveSessionVariable("loginUserID", $loginUserID);
		saveSessionVariable("loginStatus", $loginStatus);
		saveSessionVariable("loginLinks", $loginLinks);

		// Although the '$referer' variable gets included as GET parameter above, we'll also save the variable as session variable:
		// (this should help re-directing to the correct page if a user called 'user_login/logout.php' manually, i.e., without parameters)
		saveSessionVariable("referer", $referer);
	}

	// --------------------------------------------------------------------

	// Get the 'user_id' for the record entry in table 'auth' whose email matches that in 'loginEmail':
	function getUserID($loginEmail)
	{
		global $tableAuth; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// We find the user_id through the 'users' table, using the session variable holding their 'loginEmail'.
		$query = "SELECT user_id FROM $tableAuth WHERE email = '$loginEmail'";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection
		$row = mysql_fetch_array($result);

		return($row["user_id"]);
	}

	// --------------------------------------------------------------------

	// Generic function that provides email sending capability:
	function sendEmail($emailRecipient, $emailSubject, $emailBody)
	{
		global $adminLoginEmail; // these variables are specified in 'ini.inc.php'
		global $contentTypeCharset;

		// Setup some additional headers:
		$emailHeaders = "From: " . $adminLoginEmail . "\n"
						. "Return-Path: " . $adminLoginEmail . "\n"
						. "X-Sender: " . $adminLoginEmail . "\n"
						. "X-Mailer: PHP\n"
						. "X-Priority: 3\n"
						. "Content-Type: text/plain; charset=" . $contentTypeCharset;

		// Send the email:
		mail($emailRecipient, $emailSubject, $emailBody, $emailHeaders);
	}

	// --------------------------------------------------------------------

	// BUILD FIELD NAME LINKS
	// (i.e., build clickable column headers for each available column based on the field names of the relevant mysql table)
	function buildFieldNameLinks($href, $query, $oldQuery, $newORDER, $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, $formType, $submitType, $linkName, $orig_fieldname, $viewType)
	{
		if ("$orig_fieldname" == "") // if there's no fixed original fieldname specified (as is the case for all fields but the 'Links' column)
			{
				// Get the meta-data for the attribute
				$info = mysql_fetch_field ($result, $i);
				// Get the attribute name:
				$orig_fieldname = $info->name;
			}
		// Replace substrings with spaces:
		$fieldname = str_replace("_"," ",$orig_fieldname);
		// Form words (i.e., make the first char of a word uppercase):
		$fieldname = ucwords($fieldname);

		if ($linkName == "") // if there's no fixed link name specified (as is the case for all fields but the 'Links' column)...
			$linkName = $fieldname; // ...use the attribute's name as link name

		// Setup some variables (in order to enable sorting by clicking on column titles)
		// NOTE: Column sorting with any queries that include the 'LIMIT'... parameter
		//       will (technically) work. However, every new query will limit the selection to a *different* list of records!! ?:-/
		if ("$newORDER" == "") // if there's no fixed ORDER BY string specified (as is the case for all fields but the 'Links' column)
			{
				if ($info->numeric == "1") // Check if the field's data type is numeric (if so we'll append " DESC" to the ORDER clause)
					$newORDER = ("ORDER BY " . $orig_fieldname . " DESC"); // Build the appropriate ORDER BY clause (sort numeric fields in DESCENDING order)
				else
					$newORDER = ("ORDER BY " . $orig_fieldname); // Build the appropriate ORDER BY clause
			}

		if ("$orig_fieldname" == "pages") // when original field name = 'pages' then...
			{
				$newORDER = eregi_replace("ORDER BY pages", "ORDER BY first_page DESC", $newORDER); // ...sort by 'first_page' instead
				$orig_fieldname = "first_page"; // adjust '$orig_fieldname' variable accordingly
			}

		if ("$orig_fieldname" == "volume") // when original field name = 'volume' then...
			{
				$newORDER = eregi_replace("ORDER BY volume", "ORDER BY volume_numeric DESC", $newORDER); // ...sort by 'volume_numeric' instead
				$orig_fieldname = "volume_numeric"; // adjust '$orig_fieldname' variable accordingly
			}

		if ("$orig_fieldname" == "series_volume") // when original field name = 'series_volume' then...
			{
				$newORDER = eregi_replace("ORDER BY series_volume", "ORDER BY series_volume_numeric DESC", $newORDER); // ...sort by 'series_volume_numeric' instead
				$orig_fieldname = "series_volume_numeric"; // adjust '$orig_fieldname' variable accordingly
			}

		if ("$orig_fieldname" == "marked") // when original field name = 'marked' then...
			$newORDER = eregi_replace("ORDER BY marked", "ORDER BY marked DESC", $newORDER); // ...sort 'marked' column in DESCENDING order (so that 'yes' sorts before 'no')

		if ("$orig_fieldname" == "last_login") // when original field name = 'last_login' (defined in 'users' table) then...
			$newORDER = eregi_replace("ORDER BY last_login", "ORDER BY last_login DESC", $newORDER); // ...sort 'last_login' column in DESCENDING order (so that latest date+time sorts first)

		$orderBy = eregi_replace("ORDER BY ", "", $newORDER); // remove 'ORDER BY ' phrase in order to store just the 'ORDER BY' field spec within the 'orderBy' variable

		// call the 'newORDERclause()' function to replace the ORDER clause:
		$queryURLNewOrder = newORDERclause($newORDER, $query);

		// figure out if clicking on the current field name will sort in ascending or descending order:
		// (note that for 1st-level sort attributes, this value will be modified again below)
		if (eregi("ORDER BY [^ ]+ DESC", $newORDER)) // if 1st-level sort is in descending order...
			$linkTitleSortOrder = " (descending order)"; // ...sorting will be conducted in DESCending order
		else
			$linkTitleSortOrder = " (ascending order)"; // ...sorting will be conducted in ASCending order

		// toggle sort order for the 1st-level sort attribute:
		if (preg_match("/ORDER BY $orig_fieldname(?! DESC)/i", $query)) // if 1st-level sort is by this attribute (in ASCending order)...
		{
			$queryURLNewOrder = preg_replace("/(ORDER%20BY%20$orig_fieldname)(?!%20DESC)/i", "\\1%20DESC", $queryURLNewOrder); // ...change sort order to DESCending
			$linkTitleSortOrder = " (descending order)"; // adjust the link title attribute's sort info accordingly
		}
		elseif (preg_match("/ORDER BY $orig_fieldname DESC/i", $query)) // if 1st-level sort is by this attribute (in DESCending order)...
		{
			$queryURLNewOrder = preg_replace("/(ORDER%20BY%20$orig_fieldname)%20DESC/i", "\\1", $queryURLNewOrder); // ...change sort order to ASCending
			$linkTitleSortOrder = " (ascending order)"; // adjust the link title attribute's sort info accordingly
		}

		// build an informative string that get's displayed when a user mouses over a link:
		$linkTitle = "\"sort by field '" . $orig_fieldname . "'" . $linkTitleSortOrder . "\"";
		
		// start the table header tag & print the attribute name as link:
		$tableHeaderLink = "$HTMLbeforeLink<a href=\"$href?sqlQuery=$queryURLNewOrder&amp;showQuery=$showQuery&amp;showLinks=$showLinks&amp;formType=$formType&amp;showRows=$showRows&amp;rowOffset=$rowOffset&amp;submit=$submitType&amp;orderBy=" . rawurlencode($orderBy) . "&amp;oldQuery=" . rawurlencode($oldQuery) . "&amp;viewType=$viewType\" title=$linkTitle>$linkName</a>";

		// append sort indicator after the 1st-level sort attribute:
		if (preg_match("/ORDER BY $orig_fieldname(?! DESC)(?=,| LIMIT|$)/i", $query)) // if 1st-level sort is by this attribute (in ASCending order)...
			$tableHeaderLink .= "&nbsp;<img src=\"img/sort_asc.gif\" alt=\"(up)\" title=\"sorted by field '" . $orig_fieldname . "' (ascending order)\" width=\"8\" height=\"10\" hspace=\"0\" border=\"0\">"; // ...append an upward arrow image
		elseif (preg_match("/ORDER BY $orig_fieldname DESC/i", $query)) // if 1st-level sort is by this attribute (in DESCending order)...
			$tableHeaderLink .= "&nbsp;<img src=\"img/sort_desc.gif\" alt=\"(down)\" title=\"sorted by field '" . $orig_fieldname . "' (descending order)\" width=\"8\" height=\"10\" hspace=\"0\" border=\"0\">"; // ...append a downward arrow image

		$tableHeaderLink .=  $HTMLafterLink; // append any necessary HTML

		return $tableHeaderLink;
	}

	// --------------------------------------------------------------------

	//	REPLACE ORDER CLAUSE IN SQL QUERY
	function newORDERclause($newORDER, $query)
	{
		$queryNewOrder = eregi_replace('LIMIT','•LIMIT',$query); // put a unique delimiter in front of the 'LIMIT'... parameter (in order to keep any 'LIMIT' parameter)
		$queryNewOrder = eregi_replace('ORDER BY [^•]+',$newORDER,$queryNewOrder); // replace old 'ORDER BY'... parameter by new one
		$queryNewOrder = str_replace('•',' ',$queryNewOrder); // remove the unique delimiter again
		$queryURLNewOrder = rawurlencode($queryNewOrder); // URL encode query
		return $queryURLNewOrder;
	}

	// --------------------------------------------------------------------

	//	BUILD BROWSE LINKS
	// (i.e., build a TABLE row with links for "previous" & "next" browsing, as well as links to intermediate pages)
	function buildBrowseLinks($href, $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, $maxPageNo, $formType, $displayType, $citeStyle, $citeOrder, $orderBy, $headerMsg, $viewType)
	{
		// First, calculate the offset page number:
		$pageOffset = ($rowOffset / $showRows);
		// workaround for always rounding upward (since I don't know better! :-/):
		if (ereg("[0-9]+\.[0-9+]",$pageOffset)) // if the result number is not an integer..
			$pageOffset = (int) $pageOffset + 1; // we convert the number into an integer and add 1
		// set the offset page number to a multiple of $maxPageNo:
		$pageOffset = $maxPageNo * (int) ($pageOffset / $maxPageNo);

		// Plus, calculate the maximum number of pages needed:
		$lastPage = ($rowsFound / $showRows);
		// workaround for always rounding upward (since I don't know better! :-/):
		if (ereg("[0-9]+\.[0-9+]",$lastPage)) // if the result number is not an integer..
			$lastPage = (int) $lastPage + 1; // we convert the number into an integer and add 1

		// Start a <TABLE>:
		$BrowseLinks = "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds browse links that link to the results pages of your query\">";

		// Start a <TABLE> row:
		$BrowseLinks .= "\n<tr>";

		if ($viewType == "Print")
			$BrowseLinks .= "\n\t<td align=\"left\" valign=\"bottom\" width=\"145\"><a href=\"index.php\">Home</a></td>";
		elseif (($href != "help.php" AND $displayType != "Cite") OR ($href == "help.php" AND $displayType == "List"))
		{
			$BrowseLinks .= "\n\t<td align=\"left\" valign=\"bottom\" width=\"145\" class=\"small\">"
							. "\n\t\t<a href=\"JavaScript:checkall(true,'marked[]')\" title=\"select all records on this page\">Select All</a>&nbsp;&nbsp;&nbsp;"
							. "\n\t\t<a href=\"JavaScript:checkall(false,'marked[]')\" title=\"deselect all records on this page\">Deselect All</a>"
							. "\n\t</td>";
		}
		// we don't show the select/deselect links in citation layout (since there aren't any checkboxes anyhow);
		// similarly, we ommit these links on 'help.php' in 'Display' mode:
		else // citation layout
			$BrowseLinks .= "\n\t<td align=\"left\" valign=\"bottom\" width=\"145\">&nbsp;</td>";


		$BrowseLinks .= "\n\t<td align=\"center\" valign=\"bottom\">";

		// a) If there's a page range below the one currently shown,
		// create a "[xx-xx]" link (linking directly to the previous range of pages):
		if ($pageOffset > "0")
			{
				$previousRangeFirstPage = ($pageOffset - $maxPageNo + 1); // calculate the first page of the next page range

				$previousRangeLastPage = ($previousRangeFirstPage + $maxPageNo - 1); // calculate the last page of the next page range

				$BrowseLinks .= "\n\t\t<a href=\"" . $href
					. "?sqlQuery=" . rawurlencode($query)
					. "&amp;submit=$displayType"
					. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
					. "&amp;citeOrder=$citeOrder"
					. "&amp;orderBy=" . rawurlencode($orderBy)
					. "&amp;headerMsg=" . rawurlencode($headerMsg)
					. "&amp;showQuery=$showQuery"
					. "&amp;showLinks=$showLinks"
					. "&amp;formType=$formType"
					. "&amp;showRows=$showRows"
					. "&amp;rowOffset=" . (($pageOffset - $maxPageNo) * $showRows)
					. "&amp;oldQuery=" . rawurlencode($oldQuery)
					. "&amp;viewType=$viewType"
					. "\" title=\"display results page " . $previousRangeLastPage . " and links to pages " . $previousRangeFirstPage . "&#8211;" . $previousRangeLastPage . "\">[" . $previousRangeFirstPage . "&#8211;" . $previousRangeLastPage . "] </a>";
			}

		// b) Are there any previous pages?
		if ($rowOffset > 0)
			// Yes, so create a previous link
			$BrowseLinks .= "\n\t\t<a href=\"" . $href
				. "?sqlQuery=" . rawurlencode($query)
				. "&amp;submit=$displayType"
				. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
				. "&amp;citeOrder=$citeOrder"
				. "&amp;orderBy=" . rawurlencode($orderBy)
				. "&amp;headerMsg=" . rawurlencode($headerMsg)
				. "&amp;showQuery=$showQuery"
				. "&amp;showLinks=$showLinks"
				. "&amp;formType=$formType"
				. "&amp;showRows=$showRows"
				. "&amp;rowOffset=$previousOffset"
				. "&amp;oldQuery=" . rawurlencode($oldQuery)
				. "&amp;viewType=$viewType"
				. "\" title=\"display previous results page\">&lt;&lt;</a>";
		else
			// No, there is no previous page so don't print a link
			$BrowseLinks .= "\n\t\t&lt;&lt;";
	
		// c) Output the page numbers as links:
		// Count through the number of pages in the results:
		for($x=($pageOffset * $showRows), $page=($pageOffset + 1);
			$x<$rowsFound && $page <= ($pageOffset + $maxPageNo);
			$x+=$showRows, $page++)
			// Is this the current page?
				if ($x < $rowOffset || 
					$x > ($rowOffset + $showRows - 1))
					// No, so print out a link
					$BrowseLinks .= " \n\t\t<a href=\"" . $href
						. "?sqlQuery=" . rawurlencode($query)
						. "&amp;submit=$displayType"
						. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
						. "&amp;citeOrder=$citeOrder"
						. "&amp;orderBy=" . rawurlencode($orderBy)
						. "&amp;headerMsg=" . rawurlencode($headerMsg)
						. "&amp;showQuery=$showQuery"
						. "&amp;showLinks=$showLinks"
						. "&amp;formType=$formType"
						. "&amp;showRows=$showRows"
						. "&amp;rowOffset=$x"
						. "&amp;oldQuery=" . rawurlencode($oldQuery)
						. "&amp;viewType=$viewType"
						. "\" title=\"display results page $page\">$page</a>";
				else
					// Yes, so don't print a link
					$BrowseLinks .= " \n\t\t<b>$page</b>"; // current page is set in <b>BOLD</b>

		$BrowseLinks .= " ";
	
		// d) Are there any Next pages?
		if ($rowsFound > $nextOffset)
			// Yes, so create a next link
			$BrowseLinks .= "\n\t\t<a href=\"" . $href
				. "?sqlQuery=" . rawurlencode($query)
				. "&amp;submit=$displayType"
				. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
				. "&amp;citeOrder=$citeOrder"
				. "&amp;orderBy=" . rawurlencode($orderBy)
				. "&amp;headerMsg=" . rawurlencode($headerMsg)
				. "&amp;showQuery=$showQuery"
				. "&amp;showLinks=$showLinks"
				. "&amp;formType=$formType"
				. "&amp;showRows=$showRows"
				. "&amp;rowOffset=$nextOffset"
				. "&amp;oldQuery=" . rawurlencode($oldQuery)
				. "&amp;viewType=$viewType"
				. "\" title=\"display next results page\">&gt;&gt;</a>";
		else
			// No,	there is no next page so don't print a link
			$BrowseLinks .= "\n\t\t&gt;&gt;";

		// e) If there's a page range above the one currently shown,
		// create a "[xx-xx]" link (linking directly to the next range of pages):
		if ($pageOffset < ($lastPage - $maxPageNo))
			{
				$nextRangeFirstPage = ($pageOffset + $maxPageNo + 1); // calculate the first page of the next page range

				$nextRangeLastPage = ($nextRangeFirstPage + $maxPageNo - 1); // calculate the last page of the next page range
				if ($nextRangeLastPage > $lastPage)
					$nextRangeLastPage = $lastPage; // adjust if this is the last range of pages and if it doesn't go up to the max allowed no of pages

				$BrowseLinks .= "\n\t\t<a href=\"" . $href
					. "?sqlQuery=" . rawurlencode($query)
					. "&amp;submit=$displayType"
					. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
					. "&amp;citeOrder=$citeOrder"
					. "&amp;orderBy=" . rawurlencode($orderBy)
					. "&amp;headerMsg=" . rawurlencode($headerMsg)
					. "&amp;showQuery=$showQuery"
					. "&amp;showLinks=$showLinks"
					. "&amp;formType=$formType"
					. "&amp;showRows=$showRows"
					. "&amp;rowOffset=" . (($pageOffset + $maxPageNo) * $showRows)
					. "&amp;oldQuery=" . rawurlencode($oldQuery)
					. "&amp;viewType=$viewType"
					. "\" title=\"display results page " . $nextRangeFirstPage . " and links to pages " . $nextRangeFirstPage . "&#8211;" . $nextRangeLastPage . "\"> [" . $nextRangeFirstPage . "&#8211;" . $nextRangeLastPage . "]</a>";
			}

		$BrowseLinks .= "\n\t</td>";

		$BrowseLinks .= "\n\t<td align=\"right\" valign=\"bottom\" width=\"145\">";

		if ($viewType == "Print")
			// f) create a 'Web View' link that will show the currently displayed result set in web view:
			$BrowseLinks .= "\n\t\t<a href=\"" . $href
				. "?sqlQuery=" . rawurlencode($query)
				. "&amp;submit=$displayType"
				. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
				. "&amp;citeOrder=$citeOrder"
				. "&amp;orderBy=" . rawurlencode($orderBy)
				. "&amp;headerMsg=" . rawurlencode($headerMsg)
				. "&amp;showQuery=$showQuery"
				. "&amp;showLinks=1"
				. "&amp;formType=$formType"
				. "&amp;showRows=$showRows"
				. "&amp;rowOffset=$rowOffset"
				. "&amp;oldQuery=" . rawurlencode($oldQuery)
				. "&amp;viewType=Web"
				. "\"><img src=\"img/web.gif\" alt=\"web\" title=\"back to web view\" width=\"16\" height=\"16\" hspace=\"0\" border=\"0\"></a>";
		else
		{
			if (isset($_SESSION['user_permissions']) AND ereg("allow_print_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_print_view'...
				// f) create a 'Print View' link that will show the currently displayed result set in print view:
				$BrowseLinks .= "\n\t\t<a href=\"" . $href
					. "?sqlQuery=" . rawurlencode($query)
					. "&amp;submit=$displayType"
					. "&amp;citeStyleSelector=" . rawurlencode($citeStyle)
					. "&amp;citeOrder=$citeOrder"
					. "&amp;orderBy=" . rawurlencode($orderBy)
					. "&amp;headerMsg=" . rawurlencode($headerMsg)
					. "&amp;showQuery=$showQuery"
					. "&amp;showLinks=0"
					. "&amp;formType=$formType"
					. "&amp;showRows=$showRows"
					. "&amp;rowOffset=$rowOffset"
					. "&amp;oldQuery=" . rawurlencode($oldQuery)
					. "&amp;viewType=Print"
					. "\"><img src=\"img/print.gif\" alt=\"print\" title=\"display print view\" width=\"17\" height=\"18\" hspace=\"0\" border=\"0\"></a>";
		}

		$BrowseLinks .= "\n\t</td>"
						. "\n</tr>"
						. "\n</table>";

		return $BrowseLinks;
	}

	// --------------------------------------------------------------------

	// prepare the previous query stored in '$oldQuery' so that it can be used as active query again:
	function reactivateOldQuery($oldQuery)
	{
		// we'll have to URL encode the sqlQuery part within '$oldQuery' while maintaining the rest unencoded(!):
		$oldQuerySQLPart = preg_replace("/sqlQuery=(.+?)&amp;.+/", "\\1", $oldQuery); // extract the sqlQuery part within '$oldQuery'
		$oldQueryOtherPart = preg_replace("/sqlQuery=.+?(&amp;.+)/", "\\1", $oldQuery); // extract the remaining part after the sqlQuery
		$oldQuerySQLPart = rawurlencode($oldQuerySQLPart); // URL encode sqlQuery part within '$oldQuery'
		$oldQueryPartlyEncoded = "sqlQuery=" . $oldQuerySQLPart . $oldQueryOtherPart; // Finally, we merge everything again

		return $oldQueryPartlyEncoded;
	}

	// --------------------------------------------------------------------

	//	BUILD REFINE SEARCH ELEMENTS
	// (i.e., provide options to refine the search results)
	function buildRefineSearchElements($href, $queryURL, $showQuery, $showLinks, $showRows, $refineSearchSelectorElements1, $refineSearchSelectorElements2, $refineSearchSelectorElementSelected)
	{
		// adjust button spacing according to the calling script (which is either 'search.php' or 'users.php')
		if ($href == "users.php")
			$spaceBeforeButton = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; // I know this is ugly (it's just a quick workaround which should get fixed in the future...)
		else // if ($href == "search.php")
			$spaceBeforeButton = "&nbsp;&nbsp;";

		$refineSearchForm = <<<EOF
		<form action="$href" method="POST" name="refineSearch">
			<input type="hidden" name="formType" value="refineSearch">
			<input type="hidden" name="sqlQuery" value="$queryURL">
			<input type="hidden" name="showQuery" value="$showQuery">
			<input type="hidden" name="showLinks" value="$showLinks">
			<input type="hidden" name="showRows" value="$showRows">
			<table align="center" border="0" cellpadding="0" cellspacing="5" summary="This table holds a search form that enables you to refine the previous search result">
				<tr>
					<td valign="top">
						Search within Results:
					</td>
				</tr>
				<tr>
					<td valign="top">
						<select name="refineSearchSelector" title="choose the field you want to search">
EOF;

		$optionTags = buildSelectMenuOptions($refineSearchSelectorElements1, " *, *", "\t\t\t\t\t\t\t", false); // build correct option tags from the column items provided

		if (isset($_SESSION['loginEmail']) AND !empty($refineSearchSelectorElements2)) // if a user is logged in -AND- there were any additional elements specified...
			// ...add these additional elements to the popup menu:
			$optionTags .= buildSelectMenuOptions($refineSearchSelectorElements2, " *, *", "\t\t\t\t\t\t\t", false); // build correct option tags from the column items provided

		$optionTags = ereg_replace("<option>$refineSearchSelectorElementSelected", "<option selected>$refineSearchSelectorElementSelected", $optionTags); // add 'selected' attribute:

		$refineSearchForm .= $optionTags;

		$refineSearchForm .= <<<EOF

						</select>&nbsp;&nbsp;
						<input type="text" name="refineSearchName" size="11" title="enter your search string here">
					</td>
				</tr>
				<tr>
					<td valign="top">
						<input type="checkbox" name="refineSearchExclude" value="1" title="mark this checkbox to exclude all records from the current result set that match the above search criterion">&nbsp;Exclude matches$spaceBeforeButton
						<input type="submit" name="submit" value="Search" title="search within the current result set">
					</td>
				</tr>
			</table>
		</form>

EOF;

		return $refineSearchForm;
	}

	// --------------------------------------------------------------------

	//	BUILD USER GROUP FORM ELEMENTS
	// (i.e., provide options to show the user's personal reference groups -OR- the admin's user groups)
	// Note: this function serves two purposes (which must not be confused!):
	// 		 - if "$href = search.php", it will modify the values of the 'user_groups' field of the 'user_data' table (where a user can assign one or more groups to particular *references*)
	//       - if "$href = users.php", this function will modify the values of the 'user_groups' field of the 'users' table (where the admin can assign one or more groups to particular *users*)
	function buildGroupSearchElements($href, $queryURL, $query, $showQuery, $showLinks, $showRows)
	{
		if (preg_match("/.+user_groups RLIKE \"[()|^.;* ]+[^;]+?[()|$.;* ]+\"/i", $query)) // if the query does contain a 'WHERE' clause that searches for a particular user group
			$currentGroup = preg_replace("/.+user_groups RLIKE \"[()|^.;* ]+([^;]+?)[()|$.;* ]+\".*/i", "\\1", $query); // extract the particular group name
		else
			$currentGroup = "none";

		// show the 'Show My Groups' form:
		// - if the admin is logged in and calls 'users.php' (since only the admin will be allowed to call 'users.php', checking '$href' is sufficient here) -OR-
		// - if a user is logged in AND the 'user_permissions' session variable contains 'allow_user_groups'
		if (($href == "users.php") OR (isset($_SESSION['loginEmail']) AND (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions']))))
		{
			if (($href == "search.php" AND isset($_SESSION['userGroups'])) OR ($href == "users.php" AND isset($_SESSION['adminUserGroups']))) // if the appropriate session variable is set
			{
				$groupSearchDisabled = "";
				$groupSearchSelectorTitle = "choose the group that you want to display";
				$groupSearchButtonTitle = "show all records that belong to the specified group";
			}
			else
			{
				$groupSearchDisabled = " disabled"; // disable the 'Show My Groups' form if the session variable holding the user's groups isnt't available
				$groupSearchSelectorTitle = "(to setup a new group with all selected records, enter a group name at the bottom of this page, then click the 'Add' button)";
				$groupSearchButtonTitle = "(not available since you haven't specified any groups yet)";
			}

			// adjust the form title according to the calling script (which is either 'search.php' or 'users.php')
			if ($href == "search.php")
				$formTitleAddon = " My";
			elseif ($href == "users.php")
				$formTitleAddon = " User";
			else
				$formTitleAddon = ""; // currently, '$href' will be either 'search.php' or 'users.php', but anyhow

			$groupSearchForm = <<<EOF
		<form action="$href" method="POST" name="groupSearch">
			<input type="hidden" name="formType" value="groupSearch">
			<input type="hidden" name="sqlQuery" value="$queryURL">
			<input type="hidden" name="showQuery" value="$showQuery">
			<input type="hidden" name="showLinks" value="$showLinks">
			<input type="hidden" name="showRows" value="$showRows">
			<table align="left" border="0" cellpadding="0" cellspacing="5" summary="This table holds a search form that gives you access to your groups">
				<tr>
					<td valign="top">
						Show$formTitleAddon Group:
					</td>
				</tr>
				<tr>
					<td valign="top">
						<select name="groupSearchSelector" title="$groupSearchSelectorTitle"$groupSearchDisabled>
EOF;
	
			if (($href == "search.php" AND isset($_SESSION['userGroups'])) OR ($href == "users.php" AND isset($_SESSION['adminUserGroups']))) // if the appropriate session variable is set
			{
				 // build properly formatted <option> tag elements from the items listed in the appropriate session variable:
				if ($href == "search.php")
					$optionTags = buildSelectMenuOptions($_SESSION['userGroups'], " *; *", "\t\t\t\t\t\t\t", false);
				elseif ($href == "users.php")
					$optionTags = buildSelectMenuOptions($_SESSION['adminUserGroups'], " *; *", "\t\t\t\t\t\t\t", false);

				if (!empty($currentGroup)) // if the current SQL query contains a 'WHERE' clause that searches for a particular user group
					$optionTags = ereg_replace("<option>$currentGroup</option>", "<option selected>$currentGroup</option>", $optionTags); // we select that group by adding the 'selected' parameter to the apropriate <option> tag

				$groupSearchForm .= $optionTags;
			}
			else
				$groupSearchForm .= "<option>(no groups available)</option>";

			$groupSearchForm .= <<<EOF

						</select>
					</td>
				</tr>
				<tr>
					<td valign="top">
						<input type="submit" value="Show" title="$groupSearchButtonTitle"$groupSearchDisabled>
					</td>
				</tr>
			</table>
		</form>

EOF;
		}
		else
			$groupSearchForm = "\t\t&nbsp;\n";

		return $groupSearchForm;
	}

	// --------------------------------------------------------------------

	//	BUILD DISPLAY OPTIONS FORM ELEMENTS
	// (i.e., provide options to show/hide columns or change the number of records displayed per page)
	function buildDisplayOptionsElements($href, $queryURL, $showQuery, $showLinks, $rowOffset, $showRows, $displayOptionsSelectorElements1, $displayOptionsSelectorElements2, $displayOptionsSelectorElementSelected, $fieldsToDisplay)
	{
		$displayOptionsForm = <<<EOF
		<form action="$href" method="POST" name="displayOptions">
			<input type="hidden" name="formType" value="displayOptions">
			<input type="hidden" name="submit" value="Show">
			<input type="hidden" name="sqlQuery" value="$queryURL">
			<input type="hidden" name="showQuery" value="$showQuery">
			<input type="hidden" name="showLinks" value="$showLinks">
			<input type="hidden" name="rowOffset" value="$rowOffset">
			<input type="hidden" name="showRows" value="$showRows">
			<table align="right" border="0" cellpadding="0" cellspacing="5" summary="This table holds a form that enables you to modify the display of columns and records">
				<tr>
					<td valign="top">
						Display Options:
					</td>
				</tr>
				<tr>
					<td valign="top">
						<select name="displayOptionsSelector" title="choose the field you want to show or hide">
EOF;

	// NOTE: we embed the current value of '$rowOffset' as hidden tag within the 'displayOptions' form. By this, the current row offset can be re-applied after the user pressed the 'Show'/'Hide' button within the 'displayOptions' form.
	//       To avoid that browse links don't behave as expected, the actual value of '$rowOffset' will be adjusted in 'search.php' to an exact multiple of '$showRows'!
	
		$optionTags = buildSelectMenuOptions($displayOptionsSelectorElements1, " *, *", "\t\t\t\t\t\t\t", false); // build correct option tags from the column items provided

		if (isset($_SESSION['loginEmail']) AND !empty($displayOptionsSelectorElements2)) // if a user is logged in -AND- there were any additional elements specified...
			// ...add these additional elements to the popup menu:
			$optionTags .= buildSelectMenuOptions($displayOptionsSelectorElements2, " *, *", "\t\t\t\t\t\t\t", false); // build correct option tags from the column items provided

		$optionTags = ereg_replace("<option>$displayOptionsSelectorElementSelected", "<option selected>$displayOptionsSelectorElementSelected", $optionTags); // add 'selected' attribute:

		$displayOptionsForm .= $optionTags;

		if ($fieldsToDisplay < 2)
		{
			$hideButtonDisabled = " disabled"; // disable the 'Hide' button if there's currently only one field being displayed (except the links column)
			$hideButtonTitle = "(only available with two or more fields being displayed!)";
		}
		else
		{
			$hideButtonDisabled = "";
			$hideButtonTitle = "hide the specified field";
		}

		$displayOptionsForm .= <<<EOF

						</select>&nbsp;
						<input type="submit" name="submit" value="Show" title="show the specified field">&nbsp;
						<input type="submit" name="submit" value="Hide" title="$hideButtonTitle"$hideButtonDisabled>
					</td>
				</tr>
				<tr>
					<td valign="top">
						<input type="text" name="showRows" value="$showRows" size="4" title="specify how many records shall be displayed per page">&nbsp;&nbsp;records per page
					</td>
				</tr>
			</table>
		</form>

EOF;

		return $displayOptionsForm;
	}

	// --------------------------------------------------------------------

	// Build the database query from user input provided by the "Search within Results" or "Display Options" forms above the query results list (which, in turn, was returned by 'search.php' or 'users.php', respectively):
	function extractFormElementsRefineDisplay($queryTable, $displayType, $query, $showLinks, $userID)
	{
		global $tableRefs, $tableUserData, $tableUsers; // defined in 'db.inc.php'

		// extract form variables:
		if ($displayType == "Search") // the user clicked the 'Search' button of the "Search within Results" form
		{
			$fieldSelector = $_POST['refineSearchSelector']; // extract field name chosen by the user
			$refineSearchName = $_POST['refineSearchName']; // extract search text entered by the user

			if (isset($_POST['refineSearchExclude'])) // extract user option whether matched records should be included or excluded
				$refineSearchActionCheckbox = $_POST['refineSearchExclude']; // the user marked the checkbox next to "Exclude matches"
			else
				$refineSearchActionCheckbox = "0"; // the user did NOT mark the checkbox next to "Exclude matches"
		}

		elseif ($displayType == "Show" OR $displayType == "Hide") // the user clicked either the 'Show' or the 'Hide' button of the "Display Options" form
		// (hitting <enter> within the 'ShowRows' text entry field of the "Display Options" form will act as if the user clicked the 'Show' button)
		{
			$fieldSelector = $_POST['displayOptionsSelector']; // extract field name chosen by the user
		}


		if ($displayType == "Search")
		{
			if ($refineSearchName != "") // if the user typed a search string into the text entry field...
			{
				// Depending on the chosen output action, construct an appropriate SQL query:
				if ($refineSearchActionCheckbox == "0") // if the user did NOT mark the checkbox next to "Exclude matches"
					{
						// for the fields 'marked=no', 'copy=false' and 'selected=no', force NULL values to be matched:
						if (($fieldSelector == "marked" AND $refineSearchName == "no") OR ($fieldSelector == "copy" AND $refineSearchName == "false") OR ($fieldSelector == "selected" AND $refineSearchName == "no"))
							$query = eregi_replace("WHERE","WHERE ($fieldSelector RLIKE \"$refineSearchName\" OR $fieldSelector IS NULL) AND",$query); // ...add search field name & value to the sql query
						else // add default 'WHERE' clause:
							$query = eregi_replace("WHERE","WHERE $fieldSelector RLIKE \"$refineSearchName\" AND",$query); // ...add search field name & value to the sql query
					}
				else // $refineSearchActionCheckbox == "1" // if the user marked the checkbox next to "Exclude matches"
					{
						$query = eregi_replace("WHERE","WHERE ($fieldSelector NOT RLIKE \"$refineSearchName\" OR $fieldSelector IS NULL) AND",$query); // ...add search field name & value to the sql query
					}
				$query = eregi_replace(' AND serial RLIKE ".+"','',$query); // remove any 'AND serial RLIKE ".+"' which isn't required anymore
			}
			// else, if the user did NOT type a search string into the text entry field, we simply keep the old WHERE clause...
		}


		elseif ($displayType == "Show" OR $displayType == "Hide")
		{
			if ($displayType == "Show") // if the user clicked the 'Show' button...
				{
					if (!preg_match("/SELECT.*\W$fieldSelector\W.*FROM $queryTable/i", $query)) // ...and the field is *not* already displayed...
						$query = eregi_replace(" FROM $queryTable",", $fieldSelector FROM $queryTable",$query); // ...then SHOW the field that was used for refining the search results
				}
			elseif ($displayType == "Hide") // if the user clicked the 'Hide' button...
				{
					if (preg_match("/SELECT.*\W$fieldSelector\W.*FROM $queryTable/i", $query)) // ...and the field *is* currently displayed...
					{
						// for all columns except the first:
						$query = preg_replace("/(SELECT.+?), $fieldSelector( .*FROM $queryTable)/i","\\1\\2",$query); // ...then HIDE the field that was used for refining the search results
						// for all columns except the last:
						$query = preg_replace("/(SELECT.*? )$fieldSelector, (.+FROM $queryTable)/i","\\1\\2",$query); // ...then HIDE the field that was used for refining the search results
					}
				}
		}

		// the following changes to the SQL query are performed for both forms ("Search within Results" and "Display Options"):
		if ($queryTable == $tableRefs) // 'search.php':
		{
			// if the chosen field is one of the user specific fields from table 'user_data': 'marked', 'copy', 'selected', 'user_keys', 'user_notes', 'user_file', 'user_groups', 'cite_key' or 'related'
			if (ereg("^(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)$", $fieldSelector))
				if (!eregi("LEFT JOIN $tableUserData", $query)) // ...and if the 'LEFT JOIN...' statement isn't already part of the 'FROM' clause...
					$query = eregi_replace(" FROM $tableRefs"," FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = $userID",$query); // ...add the 'LEFT JOIN...' part to the 'FROM' clause

			$query = eregi_replace(" FROM $tableRefs",", orig_record FROM $tableRefs",$query); // add 'orig_record' column (although it won't be visible the 'orig_record' column gets included in every search query)
																					// (which is required in order to present visual feedback on duplicate records)
	
			$query = eregi_replace(" FROM $tableRefs",", serial FROM $tableRefs",$query); // add 'serial' column (although it won't be visible the 'serial' column gets included in every search query)
																			// (which is required in order to obtain unique checkbox names)

			if ($showLinks == "1")
				$query = eregi_replace(" FROM $tableRefs",", file, url, doi FROM $tableRefs",$query); // add 'file', 'url' & 'doi' columns
		}
		elseif ($queryTable == $tableUsers) // 'users.php':
		{
			$query = eregi_replace(" FROM $tableUsers",", user_id FROM $tableUsers",$query); // add 'user_id' column (although it won't be visible the 'user_id' column gets included in every search query)
																				// (which is required in order to obtain unique checkbox names as well as for use in the 'getUserID()' function)
		}


		return $query;
	}

	// --------------------------------------------------------------------

	// SPLIT AND MERGE AGAIN
	// (this function takes a string and splits it on $splitDelim into an array, then re-joins the pieces inserting $joinDelim as separator)
	function splitAndMerge($splitDelim, $joinDelim, $sourceString)
	{
		// split the string on the specified delimiter (which is interpreted as regular expression!):
		$piecesArray = split($splitDelim, $sourceString);

		// re-join the array with the specified separator:
		$newString = implode($joinDelim, $piecesArray);

		return $newString;
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
		
		$newAuthorContents = encodeHTML($newAuthorContents); // HTML encode higher ASCII characters within the newly arranged author contents

		return $newAuthorContents;
	}

	// --------------------------------------------------------------------

	// EXTRACT AUTHOR'S LAST NAME
	// this function takes the contents of the author field and will extract the last name of a particular author (specified by position)
	// (e.g., setting '$authorPosition' to "1" will return the 1st author's last name)
	//  Note: this function assumes that:
	//			1. within one author object, there's only *one* delimiter separating author name & initials!
	//			2. author objects are stored in the db as "<author_name><author_initials_delimiter><author_initials>", i.e., initials follow *after* the author's name!
	function extractAuthorsLastName($oldBetweenAuthorsDelim, $oldAuthorsInitialsDelim, $authorPosition, $authorContents)
	{
		$authorsArray = split($oldBetweenAuthorsDelim, $authorContents); // get a list of all authors for this record

		$authorPosition = ($authorPosition-1); // php array elements start with "0", so we decrease the authors position by 1
		$singleAuthor = $authorsArray[$authorPosition]; // for the author in question, extract the full author name (last name & initials)
		$singleAuthorArray = split($oldAuthorsInitialsDelim, $singleAuthor); // then, extract author name & initials to separate list items
		$singleAuthorsLastName = $singleAuthorArray[0]; // extract this author's last name into a new variable

		return $singleAuthorsLastName;
	}

	// --------------------------------------------------------------------

	// GET UPLOAD INFO
	// Given the name of a file upload field, return a four (or five) element associative
	// array containing information about the file. The element names are:

	//     name     - original name of file on client
	//     type     - MIME type of file (e.g.: 'image/gif')
	//     tmp_name - name of temporary file on server
	//     error    - holds an error number >0 if something went wrong, otherwise 0
	//                (the 'error' element was added with PHP 4.2.0. Error code explanation: <http://www.php.net/manual/en/features.file-upload.errors.php>)
	//     size     - size of file in bytes

	// depending what happend on upload, they will contain the following values (PHP 4.1 and above):
	//              no file upload  upload exceeds 'upload_max_filesize'  successful upload
	//              --------------  ------------------------------------  -----------------
	//     name           ""                       [name]                      [name]
	//     type           ""                         ""                        [type]
	//     tmp_name    "" OR "none"                  ""                      [tmp_name]
	//     error          4                          1                           0
	//     size           0                          0                         [size]
	
	// The function prefers the $_FILES array if it is available, falling back
	// to $HTTP_POST_FILES and $HTTP_POST_VARS as necessary.
	
	function getUploadInfo($name)
	{
		global $HTTP_POST_FILES, $HTTP_POST_VARS;
	
		$uploadFileInfo = array(); // initialize array variable

		// Look for information in PHP 4.1 $_FILES array first.
		// Note: The entry in $_FILES might be present even if no file was uploaded (see above).
		//       Check the 'tmp_name' and/or the 'error' member to make sure there is a file.
		if (isset($_FILES))
			if (isset($_FILES[$name]))
				$uploadFileInfo = ($_FILES[$name]);

		// Look for information in PHP 4 $HTTP_POST_FILES array next.
		// (Again, check the 'tmp_name' and/or the 'error' member to make sure there is a file.)
		elseif (isset($HTTP_POST_FILES))
			if (isset($HTTP_POST_FILES[$name]))
				$uploadFileInfo = ($HTTP_POST_FILES[$name]);

		// Look for PHP 3 style upload variables.
		// Check the _name member, because $HTTP_POST_VARS[$name] might not
		// actually be a file field.
		elseif (isset($HTTP_POST_VARS[$name])
			&& isset($HTTP_POST_VARS[$name . "_name"]))
		{
			// Map PHP 3 elements to PHP 4-style element names
			$uploadFileInfo["name"] = $HTTP_POST_VARS[$name . "_name"];
			$uploadFileInfo["tmp_name"] = $HTTP_POST_VARS[$name];
			$uploadFileInfo["size"] = $HTTP_POST_VARS[$name . "_size"];
			$uploadFileInfo["type"] = $HTTP_POST_VARS[$name . "_type"];
		}

		if ($uploadFileInfo["tmp_name"] == "none") // on some systems (PHP versions) the 'tmp_name' element might contain 'none' if there was no file being uploaded
			$uploadFileInfo["tmp_name"] = ""; // in order to standardize array output we replace 'none' with an empty string

		return $uploadFileInfo;
	}

	// --------------------------------------------------------------------

	// BUILD RELATED RECORDS LINK
	// (this function generates a proper SQL query string from the contents of the user specific 'related' field (table 'user_data') and returns a HTML link;
	//  clicking this link will show all records that match the serials or partial queries that were specified within the 'related' field)
	function buildRelatedRecordsLink($relatedFieldString, $userID)
	{
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		// initialize some arrays:
		$serialsArray = array(); // we'll use this array to hold all record serial numbers that we encounter
		$queriesArray = array(); // this array will hold all sub-queries that were extracted from the 'related' field

		// split the source string on any semi-colon ";" (optionally surrounded by whitespace) which works as our main delimiter:
		$relatedFieldArray = split(" *; *", $relatedFieldString);

		foreach ($relatedFieldArray as $relatedFieldArrayElement)
			{
				$relatedFieldArrayElement = trim($relatedFieldArrayElement); // remove any preceding or trailing whitespace

				if (!empty($relatedFieldArrayElement))
				{
					if (is_numeric($relatedFieldArrayElement)) // if the current array element is a number, we assume its a serial number
						$serialsArray[] = $relatedFieldArrayElement; // append the current array element to the end of the serials array
					else
					{
						// replace any colon ":" (optionally surrounded by whitespace) with " RLIKE " and enclose the search value with quotes:
						// (as an example, 'author:steffens, m' will be transformed to 'author RLIKE "steffens, m"')
						if (ereg(":",$relatedFieldArrayElement))
							$relatedFieldArrayElement = preg_replace("/ *: *(.+)/"," RLIKE \"\\1\"",$relatedFieldArrayElement);
						// else we assume '$relatedFieldArrayElement' to contain a valid 'WHERE' clause!
						
						$queriesArray[] = $relatedFieldArrayElement; // append the current array element to the end of the queries array
					}
				}
			}

		if (!empty($serialsArray)) // if the 'related' field did contain any record serials
		{
			$serialsString = implode("|", $serialsArray);
			$serialsString = "serial RLIKE \"^(" . $serialsString . ")$\"";
			$queriesArray[] = $serialsString; // append the serial query to the end of the queries array
		}

		// re-join the queries array with an "OR" separator:
		$queriesString = implode(" OR ", $queriesArray);

		// build the full SQL query:
		$relatedQuery = "SELECT author, title, year, publication, volume, pages";

		// if any of the user specific fields are present in the contents of the 'related' field, we'll add the 'LEFT JOIN...' part to the 'FROM' clause:
		if (ereg("marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related",$queriesString))
			$relatedQuery .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = $userID";
		else // we skip the 'LEFT JOIN...' part of the 'FROM' clause:
			$relatedQuery .= " FROM $tableRefs";

		$relatedQuery .= " WHERE " . $queriesString . " ORDER BY author, year DESC, publication"; // add 'WHERE' & 'ORDER BY' clause
		
		// build the correct query URL:
		$relatedRecordsLink = "search.php?sqlQuery=" . rawurlencode($relatedQuery) . "&amp;formType=sqlSearch&amp;showLinks=1"; // we skip unnecessary parameters ('search.php' will use it's default values for them)

		return $relatedRecordsLink;
	}

	// --------------------------------------------------------------------

	// MODIFY USER GROUPS
	// add (remove) selected records to (from) the specified user group
	// Note: this function serves two purposes (which must not be confused!):
	// 		 - if "$queryTable = user_data", it will modify the values of the 'user_groups' field of the 'user_data' table (where a user can assign one or more groups to particular *references*)
	// 		 - if "$queryTable = users", this function will modify the values of the 'user_groups' field of the 'users' table (where the admin can assign one or more groups to particular *users*)
	function modifyUserGroups($queryTable, $displayType, $recordSerialsArray, $recordSerialsString, $userID, $userGroup, $userGroupActionRadio)
	{
		global $oldQuery;
		global $tableUserData, $tableUsers; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

// 		// Check whether the contents of the '$userGroup' variable shall be interpreted as regular expression:
// 		// Note: We assume the variable contents to be a (perl-style!) regular expression if the following conditions are true:
// 		//       - the user checked the radio button next to the group text entry field ('userGroupName')
// 		//       - the entered string starts with 'REGEXP:'
// 		if (($userGroupActionRadio == "0") AND (ereg("^REGEXP:", $userGroup))) // don't escape possible meta characters
// 		{
// 			$userGroup = preg_replace("/REGEXP:(.+)/", "(\\1)", $userGroup); // remove 'REGEXP:' tage & enclose the following pattern in brackets
// 			// The enclosing brackets ensure that a pipe '|' which is used in the grep pattern doesn't cause any harm.
// 			// E.g., without enclosing brackets, the pattern 'mygroup|.+' would be (among others) resolved to ' *; *mygroup|.+ *' (see below).
// 			// This, in turn, would cause the pattern to match beyond the group delimiter (semicolon), causing severe damage to the user's
// 			// other group names!
// 
// 			// to assure that the regular pattern specifed by the user doesn't match beyond our group delimiter ';' (semicolon),
// 			// we'll need to convert any greedy regex quantifiers to non-greedy ones:
// 			$userGroup = preg_replace("/(?<![?+*]|[\d,]})([?+*]|\{\d+(, *\d*)?\})(?!\?)/", "\\1?", $userGroup);
// 		}

		// otherwise we escape any possible meta characters:
//		else // if the user checked the radio button next to the group popup menu ($userGroupActionRadio == "1") -OR-
			// the radio button next to the group text entry field was selected BUT the string does NOT start with an opening bracket and end with a closing bracket...
			$userGroup = preg_quote($userGroup, "/"); // escape meta characters (including '/' that is used as delimiter for the PCRE replace functions below and which gets passed as second argument)


		if ($queryTable == $tableUserData) // for the current user, get all entries within the 'user_data' table that refer to the selected records (listed in '$recordSerialsString'):
			$query = "SELECT record_id, user_groups FROM $tableUserData WHERE record_id RLIKE \"^(" . $recordSerialsString . ")$\" AND user_id = " . $userID;
		elseif ($queryTable == $tableUsers) // for the admin, get all entries within the 'users' table that refer to the selected records (listed in '$recordSerialsString'):
			$query = "SELECT user_id as record_id, user_groups FROM $tableUsers WHERE user_id RLIKE \"^(" . $recordSerialsString . ")$\"";
			// (note that by using 'user_id as record_id' we can use the term 'record_id' as identifier of the primary key for both tables)


		$result = queryMySQLDatabase($query, $oldQuery); // RUN the query on the database through the connection

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

				if ($queryTable == $tableUserData) // for the current record & user ID, update the matching entry within the 'user_data' table:
					$queryUserData = "UPDATE $tableUserData SET user_groups = \"" . $recordUserGroups . "\" WHERE record_id = " . $recordID . " AND user_id = " . $userID;
				elseif ($queryTable == $tableUsers) // for the current user ID, update the matching entry within the 'users' table:
					$queryUserData = "UPDATE $tableUsers SET user_groups = \"" . $recordUserGroups . "\" WHERE user_id = " . $recordID;


				$resultUserData = queryMySQLDatabase($queryUserData, $oldQuery); // RUN the query on the database through the connection
			}
		}

		if ($queryTable == $tableUserData)
		{
			// for all selected records that have no entries in the 'user_data' table (for this user), we'll need to add a new entry containing the specified group:
			$leftoverSerialsArray = array_diff($recordSerialsArray, $foundSerialsArray); // get all unique array elements of '$recordSerialsArray' which are not in '$foundSerialsArray'

			foreach ($leftoverSerialsArray as $leftoverRecordID) // for each record that we haven't processed yet (since it doesn't have an entry in the 'user_data' table for this user)
			{
				// for the current record & user ID, add a new entry (containing the specified group) to the 'user_data' table:
				$queryUserData = "INSERT INTO $tableUserData SET "
								. "user_groups = \"$userGroup\", "
								. "record_id = \"$leftoverRecordID\", "
								. "user_id = \"$userID\", "
								. "data_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value
	
				$resultUserData = queryMySQLDatabase($queryUserData, $oldQuery); // RUN the query on the database through the connection
			}
		}

		getUserGroups($queryTable, $userID); // update the appropriate session variable
	}

	// --------------------------------------------------------------------

	// Get all user groups specified by the current user (or admin)
	// and (if some groups were found) save them as semicolon-delimited string to a session variable:
	// Note: this function serves two purposes (which must not be confused!):
	// 		 - if "$queryTable = user_data", it will fetch unique values from the 'user_groups' field of the 'user_data' table (where a user can assign one or more groups to particular *references*)
	//       - if "$queryTable = users", this function will fetch unique values from the 'user_groups' field of the 'users' table (where the admin can assign one or more groups to particular *users*)
	function getUserGroups($queryTable, $userID)
	{
		global $tableUserData, $tableUsers; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// Note: 'user_groups RLIKE ".+"' will cause the database to only return user data entries where the 'user_groups' field
		//       is neither NULL (=> 'user_groups IS NOT NULL') nor the empty string (=> 'user_groups NOT RLIKE "^$"')
		if ($queryTable == $tableUserData)
			// Find all unique 'user_groups' entries in the 'user_data' table belonging to the current user:
			$query = "SELECT DISTINCT user_groups FROM $tableUserData WHERE user_id = '$userID' AND user_groups RLIKE \".+\"";
		elseif ($queryTable == $tableUsers)
			// Find all unique 'user_groups' entries in the 'users' table:
			$query = "SELECT DISTINCT user_groups FROM $tableUsers WHERE user_groups RLIKE \".+\"";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

		$userGroupsArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
			{
				// remove any meaningless delimiter(s) from the beginning or end of a field string:
				$rowUserGroupsString = trimTextPattern($row["user_groups"], "( *; *)+", true, true); // function 'trimTextPattern()' is defined in 'include.inc.php'

				// split the contents of the 'user_groups' field on the specified delimiter (which is interpreted as regular expression!):
				$rowUserGroupsArray = split(" *; *", $rowUserGroupsString);

				$userGroupsArray = array_merge($userGroupsArray, $rowUserGroupsArray); // append this row's group names to the array of found user groups
			}

			// remove duplicate group names from array:
			$userGroupsArray = array_unique($userGroupsArray);
			// sort in ascending order:
			sort($userGroupsArray);

			// join array of unique user groups with '; ' as separator:
			$userGroupsString = implode('; ', $userGroupsArray);

			// Write the resulting string of user groups into a session variable:
			if ($queryTable == $tableUserData)
				saveSessionVariable("userGroups", $userGroupsString);
			elseif ($queryTable == $tableUsers)
				saveSessionVariable("adminUserGroups", $userGroupsString);
		}
		else // no user groups found
		{ // delete any session variable (which is now outdated):
			if ($queryTable == $tableUserData)
				deleteSessionVariable("userGroups");
			elseif ($queryTable == $tableUsers)
				deleteSessionVariable("adminUserGroups");
		}
	}

	// --------------------------------------------------------------------

	// Get all user queries specified by the current user
	// and (if some queries were found) save them as semicolon-delimited string to the session variable 'userQueries':
	function getUserQueries($userID)
	{
		global $tableQueries; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// Find all unique query entries in the 'queries' table belonging to the current user:
		// (query names should be unique anyhow, so the DISTINCT parameter wouldn't be really necessary)
		$query = "SELECT DISTINCT query_name FROM $tableQueries WHERE user_id = '$userID' ORDER BY last_execution DESC";
		// Note: we sort (in descending order) by the 'last_execution' field to get the last used query entries first;
		//       by that, the last used query will be always at the top of the popup menu within the 'Recall My Query' form

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

		$userQueriesArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
				$userQueriesArray[] = $row["query_name"]; // append this row's query name to the array of found user queries

			// join array of unique user queries with '; ' as separator:
			$userQueriesString = implode('; ', $userQueriesArray);

			// Write the resulting string of user queries into a session variable:
			saveSessionVariable("userQueries", $userQueriesString);
		}
		else // no user queries found
			deleteSessionVariable("userQueries"); // delete any 'userQueries' session variable (which is now outdated)
	}

	// --------------------------------------------------------------------

	// Get all available formats/styles/types:
	function getAvailableFormatsStylesTypes($dataType, $formatType) // '$dataType' must be one of the following: 'format', 'style', 'type'; '$formatType' must be either '', 'export' or 'import'
	{
		global $tableDepends, $tableFormats, $tableStyles, $tableTypes; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		if ($dataType == "format")
			$query = "SELECT format_name, format_id FROM $tableFormats LEFT JOIN $tableDepends ON $tableFormats.depends_id = $tableDepends.depends_id WHERE format_type = '" . $formatType . "' AND format_enabled = 'true' AND depends_enabled = 'true' ORDER BY order_by, format_name";

		elseif ($dataType == "style")
			$query = "SELECT style_name, style_id FROM $tableStyles LEFT JOIN $tableDepends ON $tableStyles.depends_id = $tableDepends.depends_id WHERE style_enabled = 'true' AND depends_enabled = 'true' ORDER BY order_by, style_name";

		elseif ($dataType == "type")
			$query = "SELECT type_name, type_id FROM $tableTypes WHERE type_enabled = 'true' ORDER BY order_by, type_name";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection
	
		$availableFormatsStylesTypesArray = array(); // initialize array variable
	
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
			while ($row = @ mysql_fetch_array($result)) // for all rows found
				$availableFormatsStylesTypesArray[$row[$dataType . "_id"]] = $row[$dataType . "_name"]; // append this row's format/style/type name to the array of found user formats/styles/types

		return $availableFormatsStylesTypesArray;
	}

	// --------------------------------------------------------------------

	// Get all formats/styles/types that are available and were enabled by the admin for the current user:
	function getEnabledUserFormatsStylesTypes($userID, $dataType, $formatType, $returnIDsAsValues) // '$dataType' must be one of the following: 'format', 'style', 'type'; '$formatType' must be either '', 'export' or 'import'
	{
		global $tableDepends, $tableFormats, $tableStyles, $tableTypes, $tableUserFormats, $tableUserStyles, $tableUserTypes; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		if ($dataType == "format")
			$query = "SELECT $tableFormats.format_name, $tableFormats.format_id FROM $tableFormats LEFT JOIN $tableUserFormats on $tableFormats.format_id = $tableUserFormats.format_id LEFT JOIN $tableDepends ON $tableFormats.depends_id = $tableDepends.depends_id WHERE format_type = '" . $formatType . "' AND format_enabled = 'true' AND depends_enabled = 'true' AND user_id = '" . $userID . "' ORDER BY $tableFormats.order_by, $tableFormats.format_name";

		elseif ($dataType == "style")
			$query = "SELECT $tableStyles.style_name, $tableStyles.style_id FROM $tableStyles LEFT JOIN $tableUserStyles on $tableStyles.style_id = $tableUserStyles.style_id LEFT JOIN $tableDepends ON $tableStyles.depends_id = $tableDepends.depends_id WHERE style_enabled = 'true' AND depends_enabled = 'true' AND user_id = '" . $userID . "' ORDER BY $tableStyles.order_by, $tableStyles.style_name";

		elseif ($dataType == "type")
			$query = "SELECT $tableTypes.type_name, $tableTypes.type_id FROM $tableTypes LEFT JOIN $tableUserTypes USING (type_id) WHERE type_enabled = 'true' AND user_id = '" . $userID . "' ORDER BY $tableTypes.order_by, $tableTypes.type_name";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection
	
		$enabledFormatsStylesTypesArray = array(); // initialize array variable
	
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
			while ($row = @ mysql_fetch_array($result)) // for all rows found
			{
				if ($returnIDsAsValues) // return format/style/type IDs as element values:
					$enabledFormatsStylesTypesArray[] = $row[$dataType . "_id"]; // append this row's format/style/type ID to the array of found user formats/styles/types
				else // return format/style/type names as element values and use the corresponding IDs as element keys:
					$enabledFormatsStylesTypesArray[$row[$dataType . "_id"]] = $row[$dataType . "_name"]; // append this row's format/style/type name to the array of found user formats/styles/types
			}

		return $enabledFormatsStylesTypesArray;
	}

	// --------------------------------------------------------------------

	// Get all user formats/styles/types that are available and enabled for the current user (by admins choice) AND which this user has choosen to be visible:
	// and (if some formats/styles/types were found) save them each as semicolon-delimited string to the session variables 'user_formats', 'user_styles' or 'user_types', respectively:
	function getVisibleUserFormatsStylesTypes($userID, $dataType, $formatType) // '$dataType' must be one of the following: 'format', 'style', 'type'; '$formatType' must be either '', 'export' or 'import'
	{
		global $loginEmail;
		global $adminLoginEmail; // ('$adminLoginEmail' is specified in 'ini.inc.php')
		global $tableDepends, $tableFormats, $tableStyles, $tableTypes, $tableUserFormats, $tableUserStyles, $tableUserTypes; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		if ($dataType == "format")
		{
			// Find all enabled+visible formats in table 'user_formats' belonging to the current user:
			// Note: following conditions must be matched to have a format "enabled+visible" for a particular user:

			//       - 'formats' table: the 'format_enabled' field must contain 'true' for the given format
			//                          (the 'formats' table gives the admin control over which formats are available to the database users)

			//       - 'depends' table: the 'depends_enabled' field must contain 'true' for the 'depends_id' that matches the 'depends_id' of the given format in table 'formats'
			//                          (the 'depends' table specifies whether there are any external tools required for a particular format and if these tools are available)

			//       - 'user_formats' table: there must be an entry for the given user where the 'format_id' matches the 'format_id' of the given format in table 'formats' -AND-
			//                               the 'show_format' field must contain 'true' for the 'format_id' that matches the 'format_id' of the given format in table 'formats'
			//                               (the 'user_formats' table specifies all of the available formats for a particular user that have been selected by this user to be included in the format popups)
			$query = "SELECT format_name FROM $tableFormats LEFT JOIN $tableUserFormats on $tableFormats.format_id = $tableUserFormats.format_id LEFT JOIN $tableDepends ON $tableFormats.depends_id = $tableDepends.depends_id WHERE format_type = '" . $formatType . "' AND format_enabled = 'true' AND depends_enabled = 'true' AND user_id = '" . $userID . "' AND show_format = 'true' ORDER BY $tableFormats.order_by, $tableFormats.format_name";
		}
		elseif ($dataType == "style")
		{
			// Find all enabled+visible styles in table 'user_styles' belonging to the current user:
			// (same conditions apply as for formats)
			$query = "SELECT style_name FROM $tableStyles LEFT JOIN $tableUserStyles on $tableStyles.style_id = $tableUserStyles.style_id LEFT JOIN $tableDepends ON $tableStyles.depends_id = $tableDepends.depends_id WHERE style_enabled = 'true' AND depends_enabled = 'true' AND user_id = '" . $userID . "' AND show_style = 'true' ORDER BY $tableStyles.order_by, $tableStyles.style_name";
		}
		elseif ($dataType == "type")
		{
			// Find all enabled+visible types in table 'user_types' belonging to the current user:
			// (opposed to formats & styles, we're not checking for any dependencies here)
			$query = "SELECT type_name FROM $tableTypes LEFT JOIN $tableUserTypes USING (type_id) WHERE user_id = '" . $userID . "' AND show_type = 'true' ORDER BY $tableTypes.order_by, $tableTypes.type_name";
		}

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

		$userFormatsStylesTypesArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
				$userFormatsStylesTypesArray[] = $row[$dataType . "_name"]; // append this row's format/style/type name to the array of found user formats/styles/types

			// we'll only update the appropriate session variable if either a normal user is logged in -OR- the admin is logged in and views his own user options page
			if (($loginEmail != $adminLoginEmail) OR (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
			{
				// join array of unique user formats/styles/types with '; ' as separator:
				$userFormatsStylesTypesString = implode('; ', $userFormatsStylesTypesArray);
	
				// Write the resulting string of user formats/styles/types into a session variable:
				saveSessionVariable("user_" . $dataType . "s", $userFormatsStylesTypesString);
			}
		}
		else // no user formats/styles/types found
			// we'll only delete the appropriate session variable if either a normal user is logged in -OR- the admin is logged in and views his own user options page
			if (($loginEmail != $adminLoginEmail) OR (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
				deleteSessionVariable("user_" . $dataType . "s"); // delete any 'user_formats'/'user_styles'/'user_types' session variable (which is now outdated)		

		return $userFormatsStylesTypesArray;
	}

	// --------------------------------------------------------------------

	// Get all formats/styles/types that are available (or enabled for the current user) and return them as properly formatted <option> tag elements.
	// Note that this function will return two pretty different things, depending on who's logged in:
	//   - if the admin is logged in, it will return all *available* formats/styles/types as <option> tags
	//     (with those items being selected which were _enabled_ by the admin for the current user)
	//   - if a normal user is logged in, this function will return all formats/styles/types as <option> tags which were *enabled* by the admin for the current user
	//     (with those items being selected which were choosen to be _visible_ by the current user)
	function returnFormatsStylesTypesAsOptionTags($userID, $dataType, $formatType) // '$dataType' must be one of the following: 'format', 'style', 'type'; '$formatType' must be either '', 'export' or 'import'
	{
		global $loginEmail;
		global $adminLoginEmail; // ('$adminLoginEmail' is specified in 'ini.inc.php')

		if ($loginEmail == $adminLoginEmail) // if the admin is logged in
			$availableFormatsStylesTypesArray = getAvailableFormatsStylesTypes($dataType, $formatType); // get all available formats/styles/types

		$enabledFormatsStylesTypesArray = getEnabledUserFormatsStylesTypes($userID, $dataType, $formatType, false); // get all formats/styles/types that were enabled by the admin for the current user

		if ($loginEmail == $adminLoginEmail) // if the admin is logged in
		{
			$optionTags = buildSelectMenuOptions($availableFormatsStylesTypesArray, " *; *", "\t\t\t", true); // build properly formatted <option> tag elements from the items listed in '$availableFormatsStylesTypesArray'

			$selectedFormatsStylesTypesArray = $enabledFormatsStylesTypesArray; // get all formats/styles/types that were enabled by the admin for the current user
		}
		else // if ($loginEmail != $adminLoginEmail) // if a normal user is logged in
		{
			$optionTags = buildSelectMenuOptions($enabledFormatsStylesTypesArray, " *; *", "\t\t\t", true); // build properly formatted <option> tag elements from the items listed in '$enabledFormatsStylesTypesArray'

			$selectedFormatsStylesTypesArray = getVisibleUserFormatsStylesTypes($userID, $dataType, $formatType); // get all formats/styles/types that were choosen to be visible for the current user		
		}
	
		foreach($selectedFormatsStylesTypesArray as $itemKey => $itemValue) // escape possible meta characters within names of formats/styles/types that shall be selected (otherwise the grep pattern below would fail)
			$selectedFormatsStylesTypesArray[$itemKey] = preg_quote($itemValue);
	
		$selectedFormatsStylesTypes = implode("|", $selectedFormatsStylesTypesArray); // merge array of formats/styles/types that shall be selected

		$optionTags = ereg_replace("<option([^>]*)>($selectedFormatsStylesTypes)</option>", "<option\\1 selected>\\2</option>", $optionTags); // select all formats/styles/types that are listed within '$selectedFormatsStylesTypesArray'

		return $optionTags;
	}

	// --------------------------------------------------------------------

	// Fetch the name of the citation style file that's associated with the style given in '$citeStyle'
	// Note: Refbase identifies popup items by their name (and not by ID numbers) which means that the style names within the 'styles' table must be unique!
	// That said, this function assumes unique style names, i.e., there's no error checking for duplicates!
	function getStyleFile($citeStyle)
	{
		global $tableStyles; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// get the 'style_spec' for the record entry in table 'styles' whose 'style_name' matches that in '$citeStyle':
		$query = "SELECT style_spec FROM $tableStyles WHERE style_name = '$citeStyle'";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection
		$row = mysql_fetch_array($result);

		return($row["style_spec"]);
	}

	// --------------------------------------------------------------------

	// Fetch the path/name of the format file that's associated with the format given in '$formatName'
	function getFormatFile($formatName, $formatType) // '$formatType' must be either 'export' or 'import'
	{
		global $tableFormats; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// get the 'format_spec' for the record entry in table 'formats' whose 'format_name' matches that in '$formatName':
		$query = "SELECT format_spec FROM $tableFormats WHERE format_name = '" . $formatName . "' AND format_type = '" . $formatType . "'";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection
		$row = mysql_fetch_array($result);

		return($row["format_spec"]);
	}

	// --------------------------------------------------------------------

	// Fetch the path of the external utility that's required for a particular import/export format
	function getExternalUtilityPath($externalUtilityName)
	{
		global $tableDepends; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// get the path for the record entry in table 'depends' whose field 'depends_external' matches that in '$externalUtilityName':
		$query = "SELECT depends_path FROM $tableDepends WHERE depends_external = '" . $externalUtilityName . "'";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection
		$row = mysql_fetch_array($result);

		return($row["depends_path"]);
	}

	// --------------------------------------------------------------------

	// Get the user (or group) permissions for the current user
	// and (optionally) save all allowed user actions as semicolon-delimited string to the session variable 'user_permissions':
	function getPermissions($user_OR_groupID, $permissionType, $savePermissionsToSessionVariable) // '$permissionType' must be either 'user' or 'group'; '$savePermissionsToSessionVariable' must be either 'true' or 'false'
	{
		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// Fetch all permission settings from the 'user_permissions' (or 'group_permissions') table for the current user:
		$query = "SELECT allow_add, allow_edit, allow_delete, allow_download, allow_upload, allow_details_view, allow_print_view, allow_sql_search, allow_user_groups, allow_user_queries, allow_rss_feeds, allow_import, allow_export, allow_cite, allow_batch_import, allow_batch_export, allow_modify_options FROM " . $permissionType . "_permissions WHERE " . $permissionType . "_id = '$user_OR_groupID'";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

		if (mysql_num_rows($result) == 1) // interpret query result: Do we have exactly one row?
		{
			$userPermissionsArray = array(); // initialize array variables
			$userPermissionsFieldNameArray = array();

			$row = mysql_fetch_array($result); // fetch the one row into the array '$row'

			$fieldsFound = mysql_num_fields($result); // count the number of fields

			for ($i=0; $i<$fieldsFound; $i++)
			{
				$fieldInfo = mysql_fetch_field($result, $i); // get the meta-data for the attribute
				$fieldName = $fieldInfo->name; // get the current attribute name

				$userPermissionsArray[$fieldName] = $row[$i]; // ... append this field's permission value using the field's permission name as key

				if ($row[$i] == "yes") // if the current permission is set to 'yes'...
					$userPermissionsFieldNameArray[] = $fieldName; // ... append this field's permission name (as value) to the array of allowed user actions
			}

			// join array of allowed user actions with '; ' as separator:
			$allowedUserActionsString = implode('; ', $userPermissionsFieldNameArray);
	
			if ($savePermissionsToSessionVariable)
				// Write the resulting string of allowed user actions into a session variable:
				saveSessionVariable("user_permissions", $allowedUserActionsString);

			return $userPermissionsArray;
		}
		else
		{
			if ($savePermissionsToSessionVariable)
				// since no (or more than one) user/group was found with the given ID, we fall back to the default permissions which apply when no user is logged in, i.e.,
				// we assume 'user_id' or 'group_id' is zero! (the 'start_session()' function will take care of setting up permissions when no user is logged in)
				deleteSessionVariable("user_permissions"); // therefore, we delete any existing 'user_permissions' session variable (which is now outdated)

			return array();
		}
	}

	// --------------------------------------------------------------------

	// Returns language information:
	// if empty($userID): get all languages that were setup and enabled by the admin
	// if !empty($userID): get the preferred language for the user with the specified userID
	function getLanguages($userID)
	{
		global $tableLanguages, $tableUsers; // defined in 'db.inc.php'

		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		if (empty($userID))
			// Find all unique language entries in the 'languages' table that are enabled:
			// (language names should be unique anyhow, so the DISTINCT parameter wouldn't be really necessary)
			$query = "SELECT DISTINCT language_name FROM $tableLanguages WHERE language_enabled = 'true' ORDER BY order_by";
		else
			// Get the preferred language for the user with the user ID given in '$userID':
			$query = "SELECT language AS language_name FROM $tableUsers WHERE user_id = '" . $userID . "'";
		

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection

		$languagesArray = array(); // initialize array variable

		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows found ...
		{
			while ($row = @ mysql_fetch_array($result)) // for all rows found
				$languagesArray[] = $row["language_name"]; // append this row's language name to the array of found languages
		}

		return $languagesArray;
	}

	// --------------------------------------------------------------------

	// Update the specified user permissions for the selected user(s):
	function updateUserPermissions($recordSerialsString, $userPermissionsArray) // '$userPermissionsArray' must contain one or more key/value elements of the form array('allow_add' => 'yes', 'allow_delete' => 'no') where key is a particular 'allow_*' field name from table 'user_permissions' and value is either 'yes' or 'no'
	{
		connectToMySQLDatabase("");

		// CONSTRUCT SQL QUERY:
		// prepare the 'SET' part of the SQL query string:
		foreach($userPermissionsArray as $permissionKey => $permissionValue)
			$permissionQueryArray[] = $permissionKey . " = \"" . $permissionValue . "\"";

		$permissionQueryString = implode(", ", $permissionQueryArray);

		// Update all specified permission settings in the 'user_permissions' table for the selected user(s):
		$query = "UPDATE user_permissions SET " . $permissionQueryString . " WHERE user_id RLIKE \"^(" . $recordSerialsString . ")$\"";

		$result = queryMySQLDatabase($query, ""); // RUN the query on the database through the connection
	}

	// --------------------------------------------------------------------

	// Build properly formatted <option> tag elements from items listed within an array or string (and which -- in the case of strings -- are delimited by '$splitDelim').
	// The string given in '$prefix' will be used to prefix each of the <option> tags (e.g., use '\t\t' to indent each of the tags by 2 tabs)
	function buildSelectMenuOptions($sourceStringOrArray, $splitDelim, $prefix, $useArrayKeysAsValues)
	{
		if (is_string($sourceStringOrArray)) // split the string on the specified delimiter (which is interpreted as regular expression!):
			$itemArray = split($splitDelim, $sourceStringOrArray);
		else // source data are already provided as array:
			$itemArray = $sourceStringOrArray;

		$optionTags = ""; // initialize variable

		// copy each item as option tag element to the end of the '$optionTags' variable:
		if ($useArrayKeysAsValues)
		{
			foreach ($itemArray as $itemID => $item)
				$optionTags .= "\n$prefix<option value=\"$itemID\">$item</option>";
		}
		else
		{
			foreach ($itemArray as $item)
				$optionTags .= "\n$prefix<option>$item</option>";
		}

		return $optionTags;
	}

	// --------------------------------------------------------------------

	// Remove a text pattern from the beginning and/or end of a string:
	// This function is used to remove leading and/or trailing delimiters from a string.
	// Notes:  - '$removePattern' must be specified as regular expression!
	//         - set both variables '$trimLeft' & '$trimRight' to 'true' if you want your text pattern to get removed from BOTH sides of the source string;
	//           if you only want to trim the LEFT side of your source string: set '$trimLeft = true' & '$trimRight = false';
	//           if you only want to trim the RIGHT side of your source string: set '$trimLeft = false' & '$trimRight = true';
	// Example:  if '$removePattern' = ' *; *' and both, '$trimLeft' and '$trimRight', are set to 'true',
	//           the string '; red; green; yellow; ' would be transformed to 'red; green; yellow'.
	function trimTextPattern($sourceString, $removePattern, $trimLeft, $trimRight)
	{
		if ($trimLeft)
			$sourceString = ereg_replace("^" . $removePattern, "", $sourceString); // remove text pattern from beginning of source string

		if ($trimRight)
			$sourceString = ereg_replace($removePattern . "$", "", $sourceString); // remove text pattern from end of source string

		return $sourceString; // return the trimmed source string
	}

	// --------------------------------------------------------------------

	// Perform search & replace actions on the given text input:
	// (the array '$markupSearchReplacePatterns' in 'ini.inc.php' defines which search & replace actions will be employed)
	function searchReplaceText($searchReplaceActionsArray, $sourceString)
	{
		// apply the search & replace actions defined in '$searchReplaceActionsArray' to the text passed in '$sourceString':
		foreach ($searchReplaceActionsArray as $searchString => $replaceString)
			if (preg_match("/" . $searchString . "/", $sourceString))
				$sourceString = preg_replace("/" . $searchString . "/", $replaceString, $sourceString);


		return $sourceString;
	}

	// --------------------------------------------------------------------

	// Encode HTML entities:
	// (this custom function is provided so that it'll be easier to change the way how entities are HTML encoded later on)
	function encodeHTML($sourceString)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		$encodedString = htmlentities($sourceString, ENT_COMPAT, "$contentTypeCharset");
		// Notes from <http://www.php.net/manual/en/function.htmlentities.php>:
		//
		//     - The optional second parameter lets you define what will be done with 'single' and "double" quotes.
		//       It takes on one of three constants with the default being ENT_COMPAT:
		//       ENT_COMPAT:   Will convert double-quotes and leave single-quotes alone.
		//       ENT_QUOTES:   Will convert both double and single quotes.
		//       ENT_NOQUOTES: Will leave both double and single quotes unconverted.
		//
		//     - The optional third argument defines the character set used in conversion. Support for this argument
		//       was added in PHP 4.1.0. Presently, the ISO-8859-1 character set is used as the default.


		return $encodedString;
	}

	// --------------------------------------------------------------------

	// Verify the SQL query specified by the user and modify it if security concerns are encountered:
	// (this function does add/remove user-specific query code as required and will fix problems with escape sequences within the SQL query)
	function verifySQLQuery($sqlQuery, $referer, $displayType, $showLinks)
	{
		global $loginEmail;
		global $loginUserID;
		global $tableRefs, $tableUserData; // defined in 'db.inc.php'

		// handle the display & querying of user specific fields:
		if (!isset($_SESSION['loginEmail'])) // if NO user is logged in...
		{
			// ... and any user specific fields are part of the SELECT or ORDER BY statement...
			if (eregi("(SELECT|ORDER BY|,) (marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)",$sqlQuery))
			{
				// if the 'SELECT' clause contains any user specific fields:
				if (preg_match("/SELECT(.(?!FROM))+?(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)/i",$sqlQuery))
				{
					// save an appropriate error message:
					$HeaderString = "<b><span class=\"warning\">Display of user specific fields was ommitted!</span></b>";
					// note: we don't write out any error message if the user specific fields do only occur within the 'ORDER' clause (but not within the 'SELECT' clause)
	
					// Write back session variable:
					saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
				}
	
				$sqlQuery = eregi_replace("(SELECT|ORDER BY) (marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)( DESC)?", "\\1 ", $sqlQuery); // ...delete any user specific fields from beginning of 'SELECT' or 'ORDER BY' clause
				$sqlQuery = eregi_replace(", (marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)( DESC)?", "", $sqlQuery); // ...delete any remaining user specific fields from 'SELECT' or 'ORDER BY' clause
				$sqlQuery = eregi_replace("(SELECT|ORDER BY) *, *", "\\1 ", $sqlQuery); // ...remove any field delimiters that directly follow the 'SELECT' or 'ORDER BY' terms
	
				$sqlQuery = preg_replace("/SELECT *(?=FROM)/i", "SELECT author, title, year, publication, volume, pages ", $sqlQuery); // ...supply generic 'SELECT' clause if it did ONLY contain user specific fields
				$sqlQuery = preg_replace("/ORDER BY *(?=LIMIT|GROUP BY|HAVING|PROCEDURE|FOR UPDATE|LOCK IN|$)/i", "ORDER BY author, year DESC, publication", $sqlQuery); // ...supply generic 'ORDER BY' clause if it did ONLY contain user specific fields
			}
	
			if ((eregi(".+search.php",$referer)) AND (eregi("LEFT JOIN $tableUserData",$sqlQuery))) // if the calling script ends with 'search.php' (i.e., is NOT 'show.php', see note below!) AND the 'LEFT JOIN...' statement is part of the 'FROM' clause...
				$sqlQuery = eregi_replace("FROM $tableRefs LEFT JOIN.+WHERE","FROM $tableRefs WHERE",$sqlQuery); // ...delete 'LEFT JOIN...' part from 'FROM' clause
	
			if ((eregi(".+search.php",$referer) OR $displayType == "RSS") AND (eregi("WHERE.+(marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related)",$sqlQuery))) // if a user who's NOT logged in tries to query user specific fields (by use of 'sql_search.php')...
			// Note that the script 'show.php' may query the user specific field 'selected' (e.g., by URLs of the form: 'show.php?author=...&userID=...&only=selected')
			// but since (in that case) the '$referer' variable is either empty or does not end with 'search.php' this if clause will not apply (which is ok since we want to allow 'show.php' to query the 'selected' field)
			{
				// ...delete 'LEFT JOIN...' part from 'FROM' clause -> this is already accomplished by the code above!
				$sqlQuery = preg_replace("/WHERE (marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related).+?(?= AND| ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i","WHERE",$sqlQuery); // ...delete any user specific fields from 'WHERE' clause
				$sqlQuery = preg_replace("/( AND)? (marked|copy|selected|user_keys|user_notes|user_file|user_groups|cite_key|related).+?(?= AND| ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i","",$sqlQuery); // ...delete any user specific fields from 'WHERE' clause
				$sqlQuery = eregi_replace("WHERE AND","WHERE",$sqlQuery); // ...delete any superfluous 'AND' that wasn't removed properly by the two regex patterns above
				$sqlQuery = preg_replace("/WHERE(?= ORDER BY| LIMIT| GROUP BY| HAVING| PROCEDURE| FOR UPDATE| LOCK IN|$)/i","WHERE serial RLIKE \".+\"",$sqlQuery); // ...supply generic 'WHERE' clause if it did ONLY contain user specific fields
	
				// save an appropriate error message:
				$HeaderString = "<b><span class=\"warning\">Querying of user specific fields was ommitted!</span></b>"; // save an appropriate error message
	
				// Write back session variable:
				saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
			}
		}
	
		else // if a user is logged in...
		{
			if (eregi("LEFT JOIN $tableUserData",$sqlQuery)) // if the 'LEFT JOIN...' statement is part of the 'FROM' clause...
			{
				// ...and any user specific fields other(!) than just the 'selected' field are part of the 'SELECT' or 'WHERE' clause...
				// Note that we exclude the 'selected' field here (although it is user specific). By that we allow the 'selected' field to be queried by every user that's logged in.
				// This is done to support the 'show.php' script which may query the user specific field 'selected' (e.g., by URLs of the form: 'show.php?author=...&userID=...&only=selected')
				if (eregi(", (marked|copy|user_keys|user_notes|user_file|user_groups|cite_key|related)",$sqlQuery) OR eregi("WHERE.+(marked|copy|user_keys|user_notes|user_file|user_groups|cite_key|related)",$sqlQuery))
				{
					$sqlQuery = eregi_replace("user_id *= *[0-9]+","user_id = $loginUserID",$sqlQuery); // ...replace any other user ID with the ID of the currently logged in user
					$sqlQuery = eregi_replace("location RLIKE [^ ]+","location RLIKE \"$loginEmail\"",$sqlQuery); // ...replace any other user email address with the login email address of the currently logged in user
				}
			}
	
			// if we're going to display record details for a logged in user, we have to ensure the display of user specific fields (which may have been deleted from a query due to a previous logout action);
			// in 'Display Details' view, the 'call_number' and 'serial' fields are the last generic fields before any user specific fields:
			if (($displayType == "Display") AND (eregi(", call_number, serial FROM $tableRefs",$sqlQuery))) // if the user specific fields are missing from the SELECT statement...
				$sqlQuery = eregi_replace(", call_number, serial FROM $tableRefs",", call_number, serial, marked, copy, selected, user_keys, user_notes, user_file, user_groups, cite_key, related FROM $tableRefs",$sqlQuery); // ...add all user specific fields to the 'SELECT' clause
	
			if (($displayType == "Display" OR $displayType == "RSS") AND (!eregi("LEFT JOIN $tableUserData",$sqlQuery))) // if the 'LEFT JOIN...' statement isn't already part of the 'FROM' clause...
				$sqlQuery = eregi_replace(" FROM $tableRefs"," FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = $loginUserID",$sqlQuery); // ...add the 'LEFT JOIN...' part to the 'FROM' clause
		}
	
		if (eregi("^SELECT",$sqlQuery)) // restrict adding of columns to SELECT queries (so that 'DELETE FROM refs ...' statements won't get modified as well):
		{
			$sqlQuery = eregi_replace(" FROM $tableRefs",", orig_record FROM $tableRefs",$sqlQuery); // add 'orig_record' column (which is required in order to present visual feedback on duplicate records)
			$sqlQuery = eregi_replace(" FROM $tableRefs",", serial FROM $tableRefs",$sqlQuery); // add 'serial' column (which is required in order to obtain unique checkbox names)
	
			if ($showLinks == "1")
				$sqlQuery = eregi_replace(" FROM $tableRefs",", file, url, doi FROM $tableRefs",$sqlQuery); // add 'file', 'url' & 'doi' columns
		}
	
		// fix escape sequences within the SQL query:
		$query = str_replace('\"','"',$sqlQuery); // replace any \" with "
		$query = eregi_replace('(\\\\)+','\\\\',$query);


		return $query;
	}

	// --------------------------------------------------------------------

	// generate a RFC-2822 formatted date from MySQL date & time fields:
	function generateUNIXTimeStamp($mysqlDate, $mysqlTime)
	{
		$dateArray = split("-", $mysqlDate); // split MySQL-formatted date string (e.g. "2004-09-27") into its pieces (year, month, day)

		$timeArray = split(":", $mysqlTime); // split MySQL-formatted time string (e.g. "23:58:23") into its pieces (hours, minutes, seconds)

		$timeStamp = mktime($timeArray[0], $timeArray[1], $timeArray[2], $dateArray[1], $dateArray[2], $dateArray[0]);

		$rfc2822date = date('r', $timeStamp);


		return $rfc2822date;
	}

	// --------------------------------------------------------------------

	// generate an email address from MySQL 'created_by' fields that conforms
	// to the RFC-2822 specifications (<http://www.faqs.org/rfcs/rfc2822.html>):
	function generateRFC2822EmailAddress($createdBy)
	{
		// Note that the following patterns don't attempt to do fancy parsing of email addresses but simply assumes the string format
		// of the 'created_by' field (table 'refs'). If you change the string format, you must modify these patterns as well!
		$authorName = preg_replace("/(.+?)\([^)]+\)/", "\\1", $createdBy);
		$authorEmail = preg_replace("/.+?\(([^)]+)\)/", "\\1", $createdBy);

		$rfc2822address = encodeHTML($authorName . "<" . $authorEmail . ">");


		return $rfc2822address;
	}

	// --------------------------------------------------------------------

	// Takes a SQL query and tries to describe it in natural language:
	// (Note that, currently, this function doesn't attempt to cover all kinds of SQL queries [which would be a task by its own!]
	//  but rather sticks to what is needed in the context of refbase: I.e., right now, only the 'WHERE' clause will be translated)
	function explainSQLQuery($sourceSQLQuery)
	{
		// fix escape sequences within the SQL query:
		$translatedSQL = str_replace('\"','"',$sourceSQLQuery); // replace any \" with "
		$translatedSQL = ereg_replace('(\\\\)+','\\\\',$translatedSQL);

		// define an array of search & replace actions:
		// (Note that the order of array elements IS important since it defines when a search/replace action gets executed)
		$sqlSearchReplacePatterns = array(" != "                         =>  " is not equal to ",
										" = "                            =>  " is equal to ",
										" > "                            =>  " is greater than ",
										" < "                            =>  " is less than ",
										"NOT RLIKE \"\\^([^\"]+?)\\$\""  =>  "is not equal to \"\\1\"",
										"NOT RLIKE \"\\^"                =>  "does not start with \"",
										"NOT RLIKE \"([^\"]+?)\\$\""     =>  "does not end with \"\\1\"",
										"NOT RLIKE"                      =>  "does not contain",
										"RLIKE \"\\^([^\"]+?)\\$\""      =>  "is equal to \"\\1\"",
										"RLIKE \"\\^"                    =>  "starts with \"",
										"RLIKE \"([^\"]+?)\\$\""         =>  "ends with \"\\1\"",
										"RLIKE"                          =>  "contains",
										"AND"                            =>  "and");

		// Perform search & replace actions on the SQL query:
		$translatedSQL = searchReplaceText($sqlSearchReplacePatterns, $translatedSQL); // function 'searchReplaceText()' is defined in 'include.inc.php'


		return $translatedSQL;
	}

	// --------------------------------------------------------------------

	// Generate RSS XML data from a particular result set (upto the limit given in '$showRows'):
	function generateRSS($result, $showRows, $rssChannelDescription)
	{
		global $officialDatabaseName; // these variables are defined in 'ini.inc.php'
		global $databaseBaseURL;
		global $feedbackEmail;
		global $defaultCiteStyle;
		global $markupSearchReplacePatterns;
		global $contentTypeCharset;

		$currentDateTimeStamp = date('r'); // get the current date & time (in UNIX time stamp format => "date('D, j M Y H:i:s O')")

		// write RSS header:
		$rssData = "<?xml version=\"1.0\" encoding=\"" . $contentTypeCharset . "\"?>"
					. "\n<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">";

		// write channel info:
		$rssData .= "\n\t<channel>"
					. "\n\t\t<title>" . encodeHTML($officialDatabaseName) . "</title>"
					. "\n\t\t<link>" . $databaseBaseURL . "</link>"
					. "\n\t\t<description>" . encodeHTML($rssChannelDescription) . "</description>"
					. "\n\t\t<language>en</language>"
					. "\n\t\t<pubDate>" . $currentDateTimeStamp . "</pubDate>"
					. "\n\t\t<lastBuildDate>" . $currentDateTimeStamp . "</lastBuildDate>"
					. "\n\t\t<webMaster>" . $feedbackEmail . "</webMaster>";

		// write image data:
		$rssData .=  "\n\n\t\t<image>"
					. "\n\t\t\t<url>" . $databaseBaseURL . "img/logo.gif</url>"
					. "\n\t\t\t<title>" . encodeHTML($officialDatabaseName) . "</title>"
					. "\n\t\t\t<link>" . $databaseBaseURL . "</link>"
					. "\n\t\t</image>";

		// fetch results: upto the limit specified in '$showRows', fetch a row into the '$row' array and write out a RSS item:
		for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
		{
			// Perform search & replace actions on the text of the 'title' field:
			// (the array '$markupSearchReplacePatterns' in 'ini.inc.php' defines which search & replace actions will be employed)
//			$row['title'] = searchReplaceText($markupSearchReplacePatterns, encodeHTML($row['title']));
			// Note: search/replace seems to work but the resulting HTML code doesn't get displayed properly in my news reader... ?:-/

			$citeStyleFile = getStyleFile($defaultCiteStyle); // fetch the name of the citation style file that's associated with the style given in '$defaultCiteStyle' (which, in turn, is defined in 'ini.inc.php')

			// include the found citation style file *once*:
			include_once "cite/" . $citeStyleFile; // instead of 'include_once' we could also use: 'if ($rowCounter == 0) { include "cite/" . $citeStyleFile; }'

			// Generate a proper citation for this record, ordering attributes according to the chosen output style & record type:
			$record = citeRecord($row, $defaultCiteStyle); // function 'citeRecord()' is defined in the citation style file given in '$citeStyleFile' (which, in turn, must reside in the 'styles' directory of the refbase root directory)

			// append a RSS item for the current record:
			$rssData .= "\n\n\t\t<item>"

						. "\n\t\t\t<title>" . encodeHTML($row['title']) . "</title>"

						. "\n\t\t\t<link>" . $databaseBaseURL . "show.php?record=" . $row['serial'] . "</link>"

						. "\n\t\t\t<description>" . encodeHTML($record)

						. "\n\t\t\t&lt;br&gt;&lt;br&gt;Edited by " . encodeHTML($row['modified_by']) . " on " . generateUNIXTimeStamp($row['modified_date'], $row['modified_time']) . ".</description>"

						. "\n\t\t\t<guid isPermaLink=\"true\">" . $databaseBaseURL . "show.php?record=" . $row['serial'] . "</guid>"

						. "\n\t\t\t<pubDate>" . generateUNIXTimeStamp($row['created_date'], $row['created_time']) . "</pubDate>"

						. "\n\t\t\t<author>" . generateRFC2822EmailAddress($row['created_by']) . "</author>"

						. "\n\t\t</item>";
		}

		// finish RSS data:
		$rssData .=  "\n\n\t</channel>"
					. "\n</rss>\n";


		return $rssData;
	}

	// --------------------------------------------------------------------
?>
