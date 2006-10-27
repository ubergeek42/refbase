<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./users.php
	// Created:    29-Jun-03, 0:25 Uhr
	// Modified:   16-Nov-03, 21:33 Uhr

	// This script shows the admin a list of all user entries available within the 'users' table.
	// User data will be shown in the familiar column view, complete with links to show a user's
	// details and add, edit or delete a user.

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

	// Re-establish the existing session
	session_start();
	
	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!

	// Check if the admin is logged in
	if (!(session_is_registered("loginEmail") && ($loginEmail == $adminLoginEmail)))
	{
		session_register("HeaderString"); // save an error message
		$HeaderString = "<b><span class=\"warning\">You must be logged in as admin to view any user account details!</span></b>";

		session_register("referer"); // save the URL of the currently displayed page
		$referer = $HTTP_REFERER;

		header("Location: index.php");
		exit;
	}

	// --------------------------------------------------------------------

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// Extract the form used for searching:
	if (isset($_REQUEST['formType']))
		$formType = $_REQUEST['formType'];
	else
		$formType = "";
	
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
		$showRows = "";

	if (isset($_REQUEST['rowOffset']))
		$rowOffset = $_REQUEST['rowOffset'];
	else
		$rowOffset = "";

	// In order to generalize routines we have to query further variables here:
	if (isset($_REQUEST['exportFormatSelector']))
		$exportFormat = $_REQUEST['exportFormatSelector']; // get the export format chosen by the user (only occurs in 'extract.php' form  and in query result lists)
	else
		$exportFormat = "";

	if (isset($_REQUEST['oldQuery']))
		$oldQuery = $_REQUEST['oldQuery']; // get the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	else
		$oldQuery = "";

	// If $showLinks is empty we set it to true (i.e., show the links column by default):
	if ($showLinks == "")
		$showLinks = "1";

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:

	// --- Embedded sql query: ----------------------
	if ("$formType" == "sqlSearch") // the admin used a link with an embedded sql query for searching...
	{
		$query = str_replace(' FROM users',', user_id FROM users',$sqlQuery); // add 'user_id' column (which is required in order to obtain unique checkbox names as well as for use in the 'getUserID()' function)
		$query = str_replace('\"','"',$query); // replace any \" with "
		$query = str_replace('\\\\','\\',$query);
	}

	// --- Form within 'search.php': ---------------
	elseif ("$formType" == "refineSearch") // the user used the "Search within Results" form above the query results list (that was produced by 'users.php')
		$query = extractFormElementsRefine($displayType, $sqlQuery, $showLinks);

	else // build the default query:
		$query = "SELECT first_name, last_name, abbrev_institution, email, last_login, logins, user_id FROM users WHERE user_id RLIKE \".+\" ORDER BY last_login DESC, last_name, first_name";

	// ----------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (4) DISPLAY USERS, (5) CLOSE CONNECTION

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

	// (3) RUN the query on the database through the connection:
	if (!($result = @ mysql_query($query, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

	// ----------------------------------------------

	// (4a) DISPLAY header:
	$query = str_replace(', user_id FROM users',' FROM users',$query); // strip 'user_id' column from SQL query (so that it won't get displayed in query strings)

	$queryURL = rawurlencode($query); // URL encode SQL query

	// First, find out how many rows are available:
	$rowsFound = @ mysql_num_rows($result);
	if ($rowsFound > 0) // If there were rows found ...
		{
			// ... setup variables in order to facilitate "previous" & "next" browsing:
			// a) Set $rowOffset to zero if not previously defined, or if a wrong number (<=0) was given
			if (empty($rowOffset) || ($rowOffset <= 0))
				$rowOffset = 0;

			// Adjust the $showRows value, if a wrong number (<=0) was given
			if ($showRows <= 0)
				$showRows = 10;
			
			// b) The "Previous" page begins at the current offset LESS the number of rows per page
			$previousOffset = $rowOffset - $showRows;
			
			// c) The "Next" page begins at the current offset PLUS the number of rows per page
			$nextOffset = $rowOffset + $showRows;
			
			// d) Seek to the current offset
			mysql_data_seek($result, $rowOffset);
		}

	// Second, calculate the maximum result number on each page ('$showMaxRow' is required as parameter to the 'displayRows()' function)
	if (($rowOffset + $showRows) < $rowsFound)
		$showMaxRow = ($rowOffset + $showRows); // maximum result number on each page
	else
		$showMaxRow = $rowsFound; // for the last results page, correct the maximum result number if necessary

	// Third, build the appropriate header string (which is required as parameter to the 'showPageHeader()' function):
	if (!session_is_registered("HeaderString")) // if there's no stored message available provide the default message:
	{
		if ($rowsFound == 1)
			$HeaderString = " user found:";
		else
			$HeaderString = " users found:";

		if ($rowsFound > 0)
			$HeaderString = ($rowOffset + 1) . "&#8211;" . $showMaxRow . " of " . $rowsFound . $HeaderString;
		elseif ($rowsFound == 0)
			$HeaderString = $rowsFound . $HeaderString;
		else
			$HeaderString = $HeaderString; // well, this is actually bad coding but I do it for clearity reasons...
	}
	else
		session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

	// Now, show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// Then, call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- Manage Users", "noindex,nofollow", "Administration page that lists users of the " . htmlentities($officialDatabaseName) . ", with links for adding, editing or deleting any users", "", false, "");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

	// (4b) DISPLAY results:
	showUsers($result, $rowsFound, $query, $queryURL, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $exportFormat, $showMaxRow); // show all users

	// ----------------------------------------------

	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to disconnect from the database:", "");

	// --------------------------------------------------------------------
	
	// Display all users listed within the 'users' table
	function showUsers($result, $rowsFound, $query, $queryURL, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $exportFormat, $showMaxRow)
	{
		global $connection;
		global $HeaderString;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $loginEmail;
		global $adminLoginEmail;

		if ($rowsFound > 0) // If the query has results ...
		{
			// BEGIN RESULTS HEADER --------------------
			// 1) First, initialize some variables that we'll need later on
			// Note: In contrast to 'search.php', we don't hide any columns but the user_id column (see below)
			//       However, in order to maintain a similar code structure to 'search.php' we define $CounterMax here as well & simply set it to 0:
			$CounterMax = "0";

			// count the number of fields
			$fieldsFound = mysql_num_fields($result);
			// hide those last columns that were added by the script and not by the user
			$fieldsToDisplay = $fieldsFound-(1+$CounterMax); // (1+$CounterMax) -> $CounterMax is increased by 1 in order to hide the user_id column (which was added to make the checkbox work)

			// Calculate the number of all visible columns (which is needed as colspan value inside some TD tags)
			if ("$showLinks" == "1")
				$NoColumns = (1+$fieldsToDisplay+1); // add checkbox & Links column
			else
				$NoColumns = (1+$fieldsToDisplay); // add checkbox column

			// 2) Build a FORM & TABLE containing options to refine the search results as well as the diplayed columns
			//    First, specify which colums should be available in the popup menu (column items must be separated by a comma or comma+space!):
			//    Since 'users.php' can be only called by the admin we simply specify all fields within the first variable...
			$refineSearchSelectorElements1 = "first_name, last_name, title, institution, abbrev_institution, corporate_institution, address, address_line_1, address_line_2, address_line_3, zip_code, city, state, country, phone, email, url, keywords, notes, last_login, logins, user_id, marked, created_date, created_time, created_by, modified_date, modified_time, modified_by";
			$refineSearchSelectorElements2 = ""; // ... and keep the second one blank (compare with 'search.php')
			$refineSearchSelectorElementSelected = "last_name"; // this column will be selected by default
			// Call the 'buildRefineSearchElements()' function (defined in 'include.inc') which does the actual work:
			$RefineSearch = buildRefineSearchElements("users.php", $queryURL, $showQuery, $showLinks, $showRows, $NoColumns, $refineSearchSelectorElements1, $refineSearchSelectorElements2, $refineSearchSelectorElementSelected);
			echo $RefineSearch;

			// Start a TABLE
			echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays all users of this database\">";
		
			// Build a TABLE ROW with links for "previous" & "next" browsing, as well as links to intermediate pages
			// call the 'buildBrowseLinks()' function (defined in 'include.inc'):
			$BrowseLinks = buildBrowseLinks("users.php", $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, "25", "sqlSearch", "", "", "", "", ""); // Note: we set the last 3 fields ('$exportOrder', '$orderBy' & $headerMsg') to "" since they aren't (yet) required here
			echo $BrowseLinks;

			//    and insert a spacer TABLE ROW:
			echo "\n<tr align=\"center\">"
//				. "\n\t<td colspan=\"$NoColumns\">&nbsp;</td>" // uncomment this line and comment the next one if you prefer white space (instead of a divider line)
				. "\n\t<td colspan=\"$NoColumns\"><hr align=\"center\" width=\"100%\"></td>"
				. "\n</tr>";

			// For the column headers, start another TABLE ROW ...
			echo "\n<tr>";
	
			// ... print a marker ('x') column (which will hold the checkboxes within the results part)
			echo "\n\t<th align=\"left\" valign=\"top\">&nbsp;</th>";
	
			// for each of the attributes in the result set...
			for ($i=0; $i<$fieldsToDisplay; $i++)
			{
				// ...print out each of the attribute names
				// in that row as a separate TH (Table Header)...
				$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
				$HTMLafterLink = "</th>"; // close the table header tag
				// call the 'buildFieldNameLinks()' function (defined in 'include.inc'), which will return a properly formatted table header tag holding the current field's name
				// as well as the URL encoded query with the appropriate ORDER clause:
				$tableHeaderLink = buildFieldNameLinks("users.php", $query, $oldQuery, "", $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "", "", "");
				echo $tableHeaderLink; // print the attribute name as link
			 }
	
			if ("$showLinks" == "1")
				{
					$newORDER = ("ORDER BY user_id"); // Build the appropriate ORDER BY clause to facilitate sorting by Links column
	
					$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
					$HTMLafterLink = "</th>"; // close the table header tag
					// call the 'buildFieldNameLinks()' function (defined in 'include.inc'), which will return a properly formatted table header tag holding the current field's name
					// as well as the URL encoded query with the appropriate ORDER clause:
					$tableHeaderLink = buildFieldNameLinks("users.php", $query, $oldQuery, $newORDER, $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "sqlSearch", "", "Links", "user_id");
					echo $tableHeaderLink; // print the attribute name as link
				}
	
			// Finish the row
			echo "\n</tr>";
			// END RESULTS HEADER ----------------------
			
			// BEGIN RESULTS DATA COLUMNS --------------
			for ($rowCounter=0; (($rowCounter < $showRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
			{
				// ... start a TABLE ROW ...
				echo "\n<tr>";
	
				// ... print a column with a checkbox
				echo "\n\t<td align=\"left\" valign=\"top\" width=\"10\"><input type=\"checkbox\" name=\"marked[]\" value=\"" . $row["user_id"] . "\"></td>";
	
				// ... and print out each of the attributes
				// in that row as a separate TD (Table Data)
				// (Note: 'htmlentities($row[$i])' for HTML encoding higher ASCII will only work correctly if character encoding of data is ISO-8859-1!)
				for ($i=0; $i<$fieldsToDisplay; $i++)
				{
					// the following two lines will fetch the current attribute name:
					$info = mysql_fetch_field($result, $i); // get the meta-data for the attribute
					$orig_fieldname = $info->name; // get the attribute name

					if (ereg("^email$", $orig_fieldname))
						echo "\n\t<td valign=\"top\"><a href=\"mailto:" . $row["email"] . "\">" . $row["email"] . "</a></td>";
					else
						echo "\n\t<td valign=\"top\">" . htmlentities($row[$i]) . "</td>";
				}

				// embed appropriate links (if available):
				if ("$showLinks" == "1")
				{
					echo "\n\t<td valign=\"top\">";
	
					echo "\n\t\t<a href=\"user_receipt.php?userID=" . $row["user_id"]
						. "\"><img src=\"img/details.gif\" alt=\"details\" title=\"show details\" width=\"9\" height=\"17\" hspace=\"0\" border=\"0\"></a>&nbsp;&nbsp;";
	
					echo "\n\t\t<a href=\"user_details.php?userID=" . $row["user_id"]
						. "\"><img src=\"img/edit.gif\" alt=\"edit\" title=\"edit user\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>&nbsp;&nbsp;";
	
					$adminUserID = getUserID($adminLoginEmail, $connection); // ...get the admin's 'user_id' using his/her 'adminLoginEmail' (function 'getUserID()' is defined in 'include.inc')
					if ($row["user_id"] != $adminUserID) // we only provide a delete link if this user isn't the admin:
						echo "\n\t\t<a href=\"user_receipt.php?userID=" . $row["user_id"] . "&amp;userAction=Delete"
							. "\"><img src=\"img/delete.gif\" alt=\"delete\" title=\"delete user\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";
	
					echo "\n\t</td>";
				}
				// Finish the row
				echo "\n</tr>";
			}

			// BEGIN RESULTS FOOTER --------------------
			// Insert a spacer TABLE ROW:
			echo "\n<tr align=\"center\">"
//				. "\n\t<td colspan=\"$NoColumns\">&nbsp;</td>" // uncomment this line and comment the next one if you prefer white space (instead of a divider line)
				. "\n\t<td colspan=\"$NoColumns\"><hr align=\"center\" width=\"100%\"></td>"
				. "\n</tr>";

			// Again, insert the (already constructed) BROWSE LINKS
			// (i.e., a TABLE row with links for "previous" & "next" browsing, as well as links to intermediate pages)
			echo $BrowseLinks;
			// END RESULTS FOOTER ----------------------

			// Then, finish the table
			echo "\n</table>";
			// END RESULTS DATA COLUMNS ----------------
		}
		else
		{
			// Report that nothing was found:
			echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays all users of this database\">"
					. "\n<tr>"
					. "\n\t<td valign=\"top\">Sorry, but your query didn't produce any results!&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>"
					. "\n</tr>"
					. "\n</table>";
		}// end if $rowsFound body
	}

	// --------------------------------------------------------------------

	// EXTRACT FORM VARIABLES SENT THROUGH POST
	// (!! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php>)

	// Build the database query from user input provided by the "Search within Results" form above the query results list (which, in turn, was returned by 'users.php'):
	function extractFormElementsRefine($displayType, $sqlQuery, $showLinks)
	{
		$refineSearchSelector = $_POST['refineSearchSelector']; // extract field name chosen by the user
		$refineSearchName = $_POST['refineSearchName']; // extract search text entered by the user

		if (isset($_POST['showRefineSearchFieldRadio']))
			$showRefineSearchFieldRadio = $_POST['showRefineSearchFieldRadio']; // extract user option whether searched field should be displayed
		else
			$showRefineSearchFieldRadio = "";

		$refineSearchActionRadio = $_POST['refineSearchActionRadio']; // extract user option whether matched records should be included or excluded

		$query = rawurldecode($sqlQuery); // URL decode SQL query (it was URL encoded before incorporation into a hidden tag of the 'refineSearch' form to avoid any HTML syntax errors)
											// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
											//       But, opposed to that, URL encoded data that are included within a form by means of a hidden form tag will *NOT* get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

		if ("$showRefineSearchFieldRadio" == "1") // if the user checked the radio button next to 'Show column'...
			{
				if (!preg_match("/SELECT.*\W$refineSearchSelector\W.*FROM users/", $query)) // ...and the field is *not* already displayed...
					$query = str_replace(" FROM users",", $refineSearchSelector FROM users",$query); // ...then SHOW the field that was used for refining the search results
			}
		elseif ("$showRefineSearchFieldRadio" == "0") // if the user checked the radio button next to 'Hide column'...
			{
				if (ereg("SELECT.+$refineSearchSelector.+FROM users", $query)) // ...and the field *is* currently displayed...
					// for all columns except the first:
					$query = preg_replace("/(SELECT.+?), $refineSearchSelector( .*FROM users)/","\\1\\2",$query); // ...then HIDE the field that was used for refining the search results
					// for all columns except the last:
					$query = preg_replace("/(SELECT.*? )$refineSearchSelector, (.+FROM users)/","\\1\\2",$query); // ...then HIDE the field that was used for refining the search results
			}
		// else if $showRefineSearchFieldRadio == "" (which is the form's default) we don't change the display of any columns

		$query = str_replace(' FROM users',', user_id FROM users',$query); // add 'user_id' column (although it won't be visible the 'user_id' column gets included in every search query)
																		// (which is required in order to obtain unique checkbox names as well as for use in the 'getUserID()' function)

		if ("$refineSearchName" != "") // if the user typed a search string into the text entry field...
		{
			// Depending on the chosen output action, construct an appropriate SQL query:
			if ($refineSearchActionRadio == "1") // if the user checked the radio button next to "Restrict to matched records"
				{
					// for the field 'marked=no', force NULL values to be matched:
					if ($refineSearchSelector == "marked" AND $refineSearchName == "no")
						$query = str_replace("WHERE","WHERE ($refineSearchSelector RLIKE \"$refineSearchName\" OR $refineSearchSelector IS NULL) AND",$query); // ...add search field name & value to the sql query
					else // add default 'WHERE' clause:
						$query = str_replace("WHERE","WHERE $refineSearchSelector RLIKE \"$refineSearchName\" AND",$query); // ...add search field name & value to the sql query
				}
			else // $refineSearchActionRadio == "0" // if the user checked the radio button next to "Exclude matched records"
				{
					// for the field 'marked=yes', force NULL values to be excluded:
					if ($refineSearchSelector == "marked" AND $refineSearchName == "yes")
						$query = str_replace("WHERE","WHERE ($refineSearchSelector NOT RLIKE \"$refineSearchName\" OR $refineSearchSelector IS NULL) AND",$query); // ...add search field name & value to the sql query
					else // add default 'WHERE' clause:
						$query = str_replace("WHERE","WHERE $refineSearchSelector NOT RLIKE \"$refineSearchName\" AND",$query); // ...add search field name & value to the sql query
				}
			$query = str_replace(' AND user_id RLIKE ".+"','',$query); // remove any 'AND user_id RLIKE ".+"' which isn't required anymore
		}

		// else, if the user did NOT type a search string into the text entry field, we simply keep the old WHERE clause...


		return $query;
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter("");

	// --------------------------------------------------------------------
?>
</body>
</html>
