<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./include.inc
	// Created:    16-Apr-02, 10:54
	// Modified:   10-Jan-04, 12:32

	// This file contains important
	// functions that are shared
	// between all scripts.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password
	include "ini.inc.php"; // include common variables

	// --------------------------------------------------------------------

	// Untaint user data:
	function clean($input, $maxlength)
	{
		$input = substr($input, 0, $maxlength);
		$input = EscapeShellCmd($input);
		return ($input);
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
		global $loginStatus;
		global $loginLinks;
		global $loginUserID;
		global $adminLoginEmail; // ('$adminLoginEmail' is specified in 'ini.inc.php')

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

		global $exportFormat;
		global $exportOrder;
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
			$referer = $_SERVER["SCRIPT_NAME"] . "?" . "formType=" . "sqlSearch" . "&submit=" . $displayType . "&headerMsg=" . rawurlencode($headerMsg) . "&sqlQuery=" . $queryURL . "&showQuery=" . $showQuery . "&showLinks=" . $showLinks . "&showRows=" . $showRows . "&rowOffset=" . $rowOffset . $recordSerialsString . "&exportFormatSelector=" . rawurlencode($exportFormat) . "&exportOrder=" . $exportOrder . "&orderBy=" . rawurlencode($orderBy) . "&oldQuery=" . rawurlencode($oldQuery);
		// --- END WORKAROUND -----

		if (!session_is_registered("loginUserID"))
			session_register("loginUserID");

		if (!session_is_registered("loginStatus"))
			session_register("loginStatus");

		if (!session_is_registered("loginLinks"))
			session_register("loginLinks");

		// Is the user logged in?
		if (session_is_registered("loginEmail"))
			{
				$loginWelcomeMsg = "Welcome<br><em>" . htmlentities($loginFirstName) . " " . htmlentities($loginLastName) . "</em>!";

				if ($loginEmail == $adminLoginEmail)
					$loginStatus = "You're logged in as<br><span class=\"warning\">Admin</span> (<em>" . $loginEmail . "</em>)";
				else
					$loginStatus = "You're logged in as<br><em>" . $loginEmail . "</em>";

				$loginLinks = "";
				if ($loginEmail == $adminLoginEmail) // if the admin is logged in, add the 'Add User' & 'Manage Users' links:
				{
					$loginLinks .= "<a href=\"user_details.php\">Add User</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
					$loginLinks .= "<a href=\"users.php\">Manage Users</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				}
				else // if a normal user is logged in, we just add the 'Change Details' link instead:
					$loginLinks .= "<a href=\"user_details.php?userID=" . $loginUserID . "\">Change Details</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				$loginLinks .= "<a href=\"user_logout.php?referer=" . rawurlencode($referer) . "\">Logout</a>";
			}
		else
			{
				$loginWelcomeMsg = "";

				if (ereg(".*(record|import[^.]*)\.php", $_SERVER["SCRIPT_NAME"]))
					$loginStatus = "<span class=\"warning\">You must be logged in<br>to submit this form!</span>";
				else
					$loginStatus = "";

				$loginLinks = "<a href=\"user_login.php?referer=" . rawurlencode($referer) . "\">Login</a>";
			}
	}

	// --------------------------------------------------------------------

	// Get the 'user_id' for the record entry in table 'auth' whose email matches that in 'loginEmail':
	function getUserID($loginEmail, $connection)
	{
		global $hostName;
		global $username;
		global $password;
		global $databaseName;

		$open = false;

		// If a connection parameter is not passed, then use our own connection to avoid any locking problems
		if (!isset($connection))
		{
			// (1) OPEN the database connection:
			//      (variables are set by include file 'db.inc'!)
			if (!($connection = @ mysql_connect($hostName, $username, $password)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to connect to the host:", "");
		
			// (2) SELECT the database:
			//      (variables are set by include file 'db.inc'!)
			if (!(mysql_select_db($databaseName, $connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to connect to the database:", "");

			$open = true;
		}

		// CONSTRUCT SQL QUERY:
		// We find the user_id through the 'users' table, using the session variable holding their 'loginEmail'.
		$query = "SELECT user_id FROM auth WHERE email = '$loginEmail'";

		// (3) RUN the query on the database through the connection:
		if ($result = @ mysql_query ($query, $connection))
			$row = mysql_fetch_array($result);
		else
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

		if ($open == true)
		{
			// (5) CLOSE the database connection:
			if (!(mysql_close($connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to disconnect from the database:", "");
		}

		return($row["user_id"]);
	}

	// --------------------------------------------------------------------

	// Generic function that provides email sending capability:
	function sendEmail($emailRecipient, $emailSubject, $emailBody)
	{
		global $adminLoginEmail; // ('$adminLoginEmail' is specified in 'ini.inc.php')

		// Setup some additional headers:
		$emailHeaders = "From: " . $adminLoginEmail . "\n"
						. "Return-Path: " . $adminLoginEmail . "\n"
						. "X-Sender: " . $adminLoginEmail . "\n"
						. "X-Mailer: PHP\n"
						. "X-Priority: 3\n"
						. "Content-Type: text/plain; charset=ISO-8859-1";

		// Send the email:
		mail($emailRecipient, $emailSubject, $emailBody, $emailHeaders);
	}

	// --------------------------------------------------------------------

	// BUILD FIELD NAME LINKS
	// (i.e., build clickable column headers for each available column based on the field names of the relevant mysql table)
	function buildFieldNameLinks($href, $query, $oldQuery, $newORDER, $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, $formType, $submitType, $linkName, $orig_fieldname)
	{
		if ("$orig_fieldname" == "") // if there's no fixed original fieldname specified (as is the case for the 'Links' column)
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

		if ($linkName == "") // if there's no fixed link name specified (as is the case for the 'Links' column)...
			$linkName = $fieldname; // ...use the attribute's name as link name

		// Setup some variables (in order to enable sorting by clicking on column titles)
		// NOTE: Column sorting with any queries that include the 'LIMIT'... parameter
		//       will (technically) work. However, every new query will limit the selection to a *different* list of records!! ?:-/
		if ("$newORDER" == "") // if there's no fixed ORDER BY string specified (as is the case for the 'Links' column)
			{
				if ($info->numeric == "1") // Check if the field's data type is numeric (if so we'll append " DESC" to the ORDER clause)
					$newORDER = ("ORDER BY " . $orig_fieldname . " DESC"); // Build the appropriate ORDER BY clause (sort numeric fields in DESCENDING order)
				else
					$newORDER = ("ORDER BY " . $orig_fieldname); // Build the appropriate ORDER BY clause
			}

		if ("$orig_fieldname" == "pages") // when original field name = 'pages' then...
			{
				$newORDER = str_replace("ORDER BY pages", "ORDER BY first_page DESC", $newORDER); // ...sort by 'first_page' instead
				$orig_fieldname = "first_page"; // adjust '$orig_fieldname' variable accordingly
			}

		if ("$orig_fieldname" == "volume") // when original field name = 'volume' then...
			{
				$newORDER = str_replace("ORDER BY volume", "ORDER BY volume_numeric DESC", $newORDER); // ...sort by 'volume_numeric' instead
				$orig_fieldname = "volume_numeric"; // adjust '$orig_fieldname' variable accordingly
			}

		if ("$orig_fieldname" == "marked") // when original field name = 'marked' then...
			$newORDER = str_replace("ORDER BY marked", "ORDER BY marked DESC", $newORDER); // ...sort 'marked' column in DESCENDING order (so that 'yes' sorts before 'no')

		if ("$orig_fieldname" == "last_login") // when original field name = 'last_login' (defined in 'users' table) then...
			$newORDER = str_replace("ORDER BY last_login", "ORDER BY last_login DESC", $newORDER); // ...sort 'last_login' column in DESCENDING order (so that latest date+time sorts first)

		$orderBy = ereg_replace("ORDER BY ", "", $newORDER); // remove 'ORDER BY ' phrase in order to store just the 'ORDER BY' field spec within the 'orderBy' variable

		// call the 'newORDERclause()' function to replace the ORDER clause:
		$queryURLNewOrder = newORDERclause($newORDER, $query);
		
		// toggle sort order for the 1st-level sort attribute:
		if (preg_match("/ORDER BY $orig_fieldname(?! DESC)/", $query)) // if 1st-level sort is by this attribute (in ASCending order)...
			$queryURLNewOrder = preg_replace("/(ORDER%20BY%20$orig_fieldname)(?!%20DESC)/", "\\1%20DESC", $queryURLNewOrder); // ...change sort order to DESCending
		elseif (preg_match("/ORDER BY $orig_fieldname DESC/", $query)) // if 1st-level sort is by this attribute (in DESCending order)...
			$queryURLNewOrder = preg_replace("/(ORDER%20BY%20$orig_fieldname)%20DESC/", "\\1", $queryURLNewOrder); // ...change sort order to ASCending

		// start the table header tag & print the attribute name as link:
		$tableHeaderLink = "$HTMLbeforeLink<a href=\"$href?sqlQuery=$queryURLNewOrder&amp;showQuery=$showQuery&amp;showLinks=$showLinks&amp;formType=$formType&amp;showRows=$showRows&amp;rowOffset=$rowOffset&amp;submit=$submitType&amp;orderBy=" . rawurlencode($orderBy) . "&amp;oldQuery=" . rawurlencode($oldQuery) . "\">$linkName</a>";

		// append sort indicator after the 1st-level sort attribute:
		if (preg_match("/ORDER BY $orig_fieldname(?! DESC)(?=,| LIMIT|$)/", $query)) // if 1st-level sort is by this attribute (in ASCending order)...
			$tableHeaderLink .= "&nbsp;<img src=\"img/sort_asc.gif\" alt=\"(up)\" title=\"sorted by field '" . $orig_fieldname . "' (ascending order)\" width=\"8\" height=\"10\" hspace=\"0\" border=\"0\">"; // ...append an upward arrow image
		elseif (preg_match("/ORDER BY $orig_fieldname DESC/", $query)) // if 1st-level sort is by this attribute (in DESCending order)...
			$tableHeaderLink .= "&nbsp;<img src=\"img/sort_desc.gif\" alt=\"(down)\" title=\"sorted by field '" . $orig_fieldname . "' (descending order)\" width=\"8\" height=\"10\" hspace=\"0\" border=\"0\">"; // ...append a downward arrow image

		$tableHeaderLink .=  $HTMLafterLink; // append any necessary HTML

		return $tableHeaderLink;
	}

	// --------------------------------------------------------------------

	//	REPLACE ORDER CLAUSE IN SQL QUERY
	function newORDERclause($newORDER, $query)
	{
		$queryNewOrder = str_replace('LIMIT','¥LIMIT',$query); // put a unique delimiter in front of the 'LIMIT'... parameter (in order to keep any 'LIMIT' parameter)
		$queryNewOrder = ereg_replace('ORDER BY [^¥]+',$newORDER,$queryNewOrder); // replace old 'ORDER BY'... parameter by new one
		$queryNewOrder = str_replace('¥',' ',$queryNewOrder); // remove the unique delimiter again
		$queryURLNewOrder = rawurlencode($queryNewOrder); // URL encode query
		return $queryURLNewOrder;
	}

	// --------------------------------------------------------------------

	//	BUILD BROWSE LINKS
	// (i.e., build a TABLE row with links for "previous" & "next" browsing, as well as links to intermediate pages)
	function buildBrowseLinks($href, $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, $maxPageNo, $formType, $displayType, $exportFormat, $exportOrder, $orderBy, $headerMsg)
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

		// Start a <TABLE> row:
		$BrowseLinks = "\n<tr>";
		$BrowseLinks .= "\n\t<td align=\"center\" valign=\"top\" colspan=\"$NoColumns\">";

		// a) If there's a page range below the one currently shown,
		// create a "[xx-xx]" link (linking directly to the previous range of pages):
		if ($pageOffset > "0")
			{
				$previousRangeFirstPage = ($pageOffset - $maxPageNo + 1); // calculate the first page of the next page range

				$previousRangeLastPage = ($previousRangeFirstPage + $maxPageNo - 1); // calculate the last page of the next page range

				$BrowseLinks .= "\n\t\t<a href=\"" . $href
					. "?sqlQuery=" . rawurlencode($query)
					. "&amp;submit=$displayType"
					. "&amp;exportFormatSelector=" . rawurlencode($exportFormat)
					. "&amp;exportOrder=$exportOrder"
					. "&amp;orderBy=" . rawurlencode($orderBy)
					. "&amp;headerMsg=" . rawurlencode($headerMsg)
					. "&amp;showQuery=$showQuery"
					. "&amp;showLinks=$showLinks"
					. "&amp;formType=$formType"
					. "&amp;showRows=$showRows"
					. "&amp;rowOffset=" . (($pageOffset - $maxPageNo) * $showRows)
					. "&amp;oldQuery=" . rawurlencode($oldQuery)
					. "\">[" . $previousRangeFirstPage . "&#8211;" . $previousRangeLastPage . "] </a>";
			}

		// b) Are there any previous pages?
		if ($rowOffset > 0)
			// Yes, so create a previous link
			$BrowseLinks .= "\n\t\t<a href=\"" . $href
				. "?sqlQuery=" . rawurlencode($query)
				. "&amp;submit=$displayType"
				. "&amp;exportFormatSelector=" . rawurlencode($exportFormat)
				. "&amp;exportOrder=$exportOrder"
				. "&amp;orderBy=" . rawurlencode($orderBy)
				. "&amp;headerMsg=" . rawurlencode($headerMsg)
				. "&amp;showQuery=$showQuery"
				. "&amp;showLinks=$showLinks"
				. "&amp;formType=$formType"
				. "&amp;showRows=$showRows"
				. "&amp;rowOffset=$previousOffset"
				. "&amp;oldQuery=" . rawurlencode($oldQuery)
				. "\">&lt;&lt;</a>";
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
							. "&amp;exportFormatSelector=" . rawurlencode($exportFormat)
							. "&amp;exportOrder=$exportOrder"
							. "&amp;orderBy=" . rawurlencode($orderBy)
							. "&amp;headerMsg=" . rawurlencode($headerMsg)
							. "&amp;showQuery=$showQuery"
							. "&amp;showLinks=$showLinks"
							. "&amp;formType=$formType"
							. "&amp;showRows=$showRows"
							. "&amp;rowOffset=$x"
							. "&amp;oldQuery=" . rawurlencode($oldQuery)
							. "\">$page</a>";
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
				. "&amp;exportFormatSelector=" . rawurlencode($exportFormat)
				. "&amp;exportOrder=$exportOrder"
				. "&amp;orderBy=" . rawurlencode($orderBy)
				. "&amp;headerMsg=" . rawurlencode($headerMsg)
				. "&amp;showQuery=$showQuery"
				. "&amp;showLinks=$showLinks"
				. "&amp;formType=$formType"
				. "&amp;showRows=$showRows"
				. "&amp;rowOffset=$nextOffset"
				. "&amp;oldQuery=" . rawurlencode($oldQuery)
				. "\">&gt;&gt;</a>";
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
					. "&amp;exportFormatSelector=" . rawurlencode($exportFormat)
					. "&amp;exportOrder=$exportOrder"
					. "&amp;orderBy=" . rawurlencode($orderBy)
					. "&amp;headerMsg=" . rawurlencode($headerMsg)
					. "&amp;showQuery=$showQuery"
					. "&amp;showLinks=$showLinks"
					. "&amp;formType=$formType"
					. "&amp;showRows=$showRows"
					. "&amp;rowOffset=" . (($pageOffset + $maxPageNo) * $showRows)
					. "&amp;oldQuery=" . rawurlencode($oldQuery)
					. "\"> [" . $nextRangeFirstPage . "&#8211;" . $nextRangeLastPage . "]</a>";
			}

		$BrowseLinks .= "\n\t</td>";
		$BrowseLinks .= "\n</tr>";
		return $BrowseLinks;
	}

	// --------------------------------------------------------------------

	//	BUILD REFINE SEARCH ELEMENTS
	// (i.e., provide options to refine the search results as well as the diplayed columns)
	function buildRefineSearchElements($href, $queryURL, $showQuery, $showLinks, $showRows, $NoColumns, $refineSearchSelectorElements1, $refineSearchSelectorElements2, $refineSearchSelectorElementSelected)
	{
		// Start a FORM:
		$RefineSearchRow = "\n<form action=\"$href\" method=\"POST\" name=\"refineSearch\">"
				. "\n\t<input type=\"hidden\" name=\"formType\" value=\"refineSearch\">"
				. "\n\t<input type=\"hidden\" name=\"sqlQuery\" value=\"$queryURL\">" // embed the current sqlQuery so that it can be re-applied when displaying refined results
				. "\n\t<input type=\"hidden\" name=\"showQuery\" value=\"$showQuery\">" // embed the current value of '$showQuery' so that it's available when displaying refined results
				. "\n\t<input type=\"hidden\" name=\"showLinks\" value=\"$showLinks\">" // embed the current value of '$showLinks' so that it's available when displaying refined results
				. "\n\t<input type=\"hidden\" name=\"showRows\" value=\"$showRows\">"; // embed the current value of '$showRows' so that it's available when displaying refined results

		// Start a TABLE:
		$RefineSearchRow .= "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds a search form that enables you to refine the previous search result\">";

		// Append a TABLE ROW with a field POPUP, a search TEXT ENTRY FIELD and a submit BUTTON for searching within found records
		$RefineSearchRow .= "\n<tr>"
							. "\n\t<td align=\"center\" valign=\"top\" colspan=\"$NoColumns\">";
		// Build the form elements:
		$RefineSearchRow .= "\n\t\tSearch within Results:&nbsp;&nbsp;&nbsp;&nbsp;"
							. "\n\t\t<select name=\"refineSearchSelector\">"
							. "\n\t\t\t<option>";
		$RefineSearchRow .= splitAndMerge(", *", "</option>\n\t\t\t<option>", $refineSearchSelectorElements1); // build correct option tags from the column items provided
		$RefineSearchRow .= "</option>";

		if (session_is_registered("loginEmail") AND !empty($refineSearchSelectorElements2)) // if a user is logged in -AND- there were any additional elements specified...
		{
			// ...add these additional elements to the popup menu:
			$RefineSearchRow .= "\n\t\t\t<option>";
			$RefineSearchRow .= splitAndMerge(", *", "</option>\n\t\t\t<option>", $refineSearchSelectorElements2); // build correct option tags from the column items provided
			$RefineSearchRow .= "</option>";
		}
		
		$RefineSearchRow = ereg_replace("<option>$refineSearchSelectorElementSelected", "<option selected>$refineSearchSelectorElementSelected", $RefineSearchRow); // add 'selected' attribute

		$RefineSearchRow .= "\n\t\t</select>&nbsp;&nbsp;"
							. "\n\t\t<input type=\"text\" name=\"refineSearchName\" size=\"12\">&nbsp;&nbsp;"
							. "\n\t\t<input type=\"submit\" name=\"submit\" value=\"Refine\">";
		// Finish the data and row tags:
		$RefineSearchRow .= "\n\t</td>"
							. "\n</tr>";

		// Append another TABLE ROW with two pairs of RADIO buttons that control 1) the diplay of columns 2) whether to include or exclude matched records
		$RefineSearchRow .= "\n<tr>"
							. "\n\t<td align=\"center\" valign=\"top\" colspan=\"$NoColumns\">";
		// Build the form elements:
		$RefineSearchRow .= "\n\t\t<input type=\"radio\" name=\"showRefineSearchFieldRadio\" value=\"1\">&nbsp;Show&nbsp;&nbsp;"
							. "\n\t\t<input type=\"radio\" name=\"showRefineSearchFieldRadio\" value=\"0\">&nbsp;Hide column&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;"
							. "\n\t\t<input type=\"radio\" name=\"refineSearchActionRadio\" value=\"1\" checked>&nbsp;Restrict to&nbsp;&nbsp;"
							. "\n\t\t<input type=\"radio\" name=\"refineSearchActionRadio\" value=\"0\">&nbsp;Exclude matched records";
		// Finish the data and row tags:
		$RefineSearchRow .= "\n\t</td>"
							. "\n</tr>";

		// Finish the table:
		$RefineSearchRow .= "\n</table>";

		// Finish the form:
		$RefineSearchRow .= "\n</form>";

		return $RefineSearchRow;
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
	//     type     - MIME type of file
	//     tmp_name - name of temporary file on server
	//     error    - holds an error number >0 if something went wrong, otherwise 0 (I don't know when this element was added. It may not be present in your PHP version... ?:-/)
	//     size     - size of file in bytes

	// depending what happend on upload, they will contain the following values (PHP 4.1 and above):
	//              no file upload  upload exceeds 'upload_max_filesize'  successful upload
	//              --------------  ------------------------------------  -----------------
	//     name           ""                       [name]                      [name]
	//     type           ""                         ""                        [type]
	//     tmp_name       ""                         ""                      [tmp_name]
	//     error          4                          1                           0
	//     size           0                          0                         [size]
	
	// The function prefers the $_FILES array if it is available, falling back
	// to $HTTP_POST_FILES and $HTTP_POST_VARS as necessary.
	
	function getUploadInfo($name)
	{
	global $HTTP_POST_FILES, $HTTP_POST_VARS;
	
		unset ($unset);

		// Look for information in PHP 4.1 $_FILES array first.
		// Note: The entry in $_FILES might be present even if no file was uploaded (see above).
		//       Check the 'tmp_name' and/or the 'error' member to make sure there is a file.
		if (isset($_FILES))
			if (isset($_FILES[$name]))
				return ($_FILES[$name]);

		// Look for information in PHP 4 $HTTP_POST_FILES array next.
		// (Again, check the 'tmp_name' and/or the 'error' member to make sure there is a file.)
		if (isset($HTTP_POST_FILES))
			if (isset($HTTP_POST_FILES[$name]))
				return ($HTTP_POST_FILES[$name]);

		// Look for PHP 3 style upload variables.
		// Check the _name member, because $HTTP_POST_VARS[$name] might not
		// actually be a file field.
		if (isset($HTTP_POST_VARS[$name])
			&& isset($HTTP_POST_VARS[$name . "_name"]))
		{
			// Map PHP 3 elements to PHP 4-style element names
			$info = array ();
			$info["name"] = $HTTP_POST_VARS[$name . "_name"];
			$info["tmp_name"] = $HTTP_POST_VARS[$name];
			$info["size"] = $HTTP_POST_VARS[$name . "_size"];
			$info["type"] = $HTTP_POST_VARS[$name . "_type"];
			return ($info);
		}
		return (@$unset);
	}

	// --------------------------------------------------------------------
?>
