<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./users.php
	// Created:    29-Jun-03, 0:25 Uhr
	// Modified:   29-Jun-03, 17:25 Uhr

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
	$formType = $_REQUEST['formType'];
	
	// Extract the type of display requested by the user (either 'Display', 'Export' or ''):
	// ('' will produce the default columnar output style)
	$displayType = $_REQUEST['submit'];

	// Extract other variables from the request:
	$sqlQuery = $_REQUEST['sqlQuery'];
	$showQuery = $_REQUEST['showQuery'];
	$showLinks = $_REQUEST['showLinks'];
	$showRows = $_REQUEST['showRows'];
	$rowOffset = $_REQUEST['rowOffset'];

	// In order to generalize routines we have to query further variables here:
	$exportFormat = $_REQUEST['exportFormatSelector']; // get the export format chosen by the user (only occurs in 'extract.php' form  and in query result lists)
	$oldQuery = $_REQUEST['oldQuery']; // get the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!

	// If $showLinks is empty we set it to true (i.e., show the links column by default):
	if ($showLinks == "")
		$showLinks = "1";

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:

	// --- embedded sql query: ----------------------
	if ("$sqlQuery" != "") // the admin used a link with an embedded sql query for searching...
		$query = str_replace(' FROM users',', user_id FROM users',$sqlQuery); // add 'user_id' column (which is required in order to obtain unique checkbox names)
	else
		$query = "SELECT first_name, last_name, abbrev_institution, institution, email, user_id FROM users ORDER BY last_name, first_name"; // build the default query

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
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks);

	// (4b) DISPLAY results:
	showUsers($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $exportFormat, $showMaxRow); // show all users

	// ----------------------------------------------

	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to disconnect from the database:", "");

	// --------------------------------------------------------------------
	
	// Display all users listed within the 'users' table
	function showUsers($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $exportFormat, $showMaxRow)
	{
		global $HeaderString;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $loginEmail;
		global $adminLoginEmail;

		if ($rowsFound > 0) 
		{
			// Start a TABLE
			echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays all users of this database\">";
		
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

			// Build a TABLE ROW with links for "previous" & "next" browsing, as well as links to intermediate pages
			// call the 'buildBrowseLinks()' function (defined in 'include.inc'):
			$BrowseLinks = buildBrowseLinks("users.php", $query, $oldQuery, $NoColumns, $rowsFound, $showQuery, $showLinks, $showRows, $rowOffset, $previousOffset, $nextOffset, "25", "", "", "");
			echo $BrowseLinks;

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
				$tableHeaderLink = buildFieldNameLinks("users.php", $query, $oldQuery, "", $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "", "", "", "");
				echo $tableHeaderLink; // print the attribute name as link
			 }
	
			if ("$showLinks" == "1")
				{
					$newORDER = ("ORDER BY user_id"); // Build the appropriate ORDER BY clause to facilitate sorting by Links column
	
					$HTMLbeforeLink = "\n\t<th align=\"left\" valign=\"top\">"; // start the table header tag
					$HTMLafterLink = "</th>"; // close the table header tag
					// call the 'buildFieldNameLinks()' function (defined in 'include.inc'), which will return a properly formatted table header tag holding the current field's name
					// as well as the URL encoded query with the appropriate ORDER clause:
					$tableHeaderLink = buildFieldNameLinks("users.php", $query, $oldQuery, $newORDER, $result, $i, $showQuery, $showLinks, $rowOffset, $showRows, $HTMLbeforeLink, $HTMLafterLink, "", "", "Links", "user_id");
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
	
					if ($row["email"] != $adminLoginEmail) // we only provide a delete link if this user isn't the admin:
						echo "\n\t\t<a href=\"user_receipt.php?userID=" . $row["user_id"] . "&amp;userAction=Delete"
							. "\"><img src=\"img/delete.gif\" alt=\"delete\" title=\"delete user\" width=\"11\" height=\"17\" hspace=\"0\" border=\"0\"></a>";
	
					echo "\n\t</td>";
				}
				// Finish the row
				echo "\n</tr>";
			}

			// BEGIN RESULTS FOOTER --------------------
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

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter("");

	// --------------------------------------------------------------------
?>
</body>
</html>
