<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./receipt.php
	// Created:    2-Jan-03, 22:43
	// Modified:   13-Dec-03, 23:20

	// This php script will display a feedback page after any action of
	// adding/editing/deleting a record. It will display links to the
	// modified/added record as well as to the previous search results page (if any)

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

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// Extract the type of action requested by the user (either 'add', 'edit', 'delet' or ''):
	// ('' will be treated equal to 'add')
	$recordAction = $_REQUEST['recordAction'];
	if ("$recordAction" == "")
		$recordAction = "add"; // '' will be treated equal to 'add'

	// Extract the id number of the record that was added/edited/deleted by the user:
	$serialNo = $_REQUEST['serialNo'];

	// Extract the header message that was returned by 'modify.php':
	$HeaderString = $_REQUEST['headerMsg'];

	// Extract generic variables from the request:
	$oldQuery = $_REQUEST['oldQuery']; // fetch the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "

	// --------------------------------------------------------------------

	// (4) DISPLAY HEADER & RESULTS
	//     (NOTE: Since there's no need to query the database here, we won't perform any of the following: (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (5) CLOSE CONNECTION)

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// (4a) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- Record Action Feedback", "noindex,nofollow", "Feedback page that confirms any adding, editing or deleting of records in the " . htmlentities($officialDatabaseName), "", false, "");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, $oldQuery);


	// (4b) DISPLAY results:
	// First, construct the correct sql query that will link back to the added/edited record:
	if (session_is_registered("loginEmail")) // if a user is logged in, show user specific fields:
		$sqlQuery = "SELECT author, title, type, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved, location, call_number, serial, marked, copy, selected, user_keys, user_notes, user_file"
				. " FROM refs LEFT JOIN user_data ON serial = record_id AND user_id =" . $loginUserID . " WHERE serial RLIKE \"^(" . $serialNo . ")$\" ORDER BY author, year DESC, publication"; // we simply use the fixed default ORDER BY clause here
	else // if NO user logged in, don't display any user specific fields:
		$sqlQuery = "SELECT author, title, type, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved, location, call_number, serial"
				. " FROM refs WHERE serial RLIKE \"^(" . $serialNo . ")$\" ORDER BY author, year DESC, publication"; // we simply use the fixed default ORDER BY clause here

	$sqlQuery = rawurlencode($sqlQuery);
	
	// Second, we'll have to URL encode the sqlQuery part within '$oldQuery' while maintaining the rest unencoded(!):
	$oldQuerySQLPart = preg_replace("/sqlQuery=(.+?)&amp;.+/", "\\1", $oldQuery); // extract the sqlQuery part within '$oldQuery'
	$oldQueryOtherPart = preg_replace("/sqlQuery=.+?(&amp;.+)/", "\\1", $oldQuery); // extract the remaining part after the sqlQuery
	$oldQuerySQLPart = rawurlencode($oldQuerySQLPart); // URL encode sqlQuery part within '$oldQuery'
	$oldQueryPartlyEncoded = "sqlQuery=" . $oldQuerySQLPart . $oldQueryOtherPart; // Finally, we merge everything again


	// Build a TABLE, containing one ROW and DATA tag:
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds links to the added/edited records as well as to the previously displayed search results page\">"
		. "\n<tr>"
		. "\n\t<td valign=\"top\">"
		. "\n\t\tChoose how to proceed:&nbsp;&nbsp;";

	if ($recordAction != "delet")
		echo "\n\t\t<a href=\"search.php?sqlQuery=" . $sqlQuery . "&amp;showQuery=0&amp;showLinks=1&amp;formType=sqlSearch&amp;submit=Display&amp;oldQuery=" . rawurlencode($oldQuery) . "\">Show " . $recordAction . "ed record</a>";

	if ($recordAction != "delet" && $oldQuery != "")
		echo "\n\t\t&nbsp;&nbsp;-OR-&nbsp;&nbsp;";

	if ($oldQuery != "") // only provide a link to any previous search results if '$oldQuery' isn't empty (which occurs for "Add Record")
		echo "\n\t\t<a href=\"search.php?" . $oldQueryPartlyEncoded . "\">Display previous search results</a>";

	if ($recordAction != "delet" || $oldQuery != "")
		echo "\n\t\t&nbsp;&nbsp;-OR-&nbsp;&nbsp;";

		echo "\n\t\t<a href=\"index.php\">Goto " . htmlentities($officialDatabaseName) . " Home</a>"; // we include the link to the home page here so that "Choose how to proceed:" never stands without any link to go

	echo "\n\t</td>"
		. "\n</tr>"
		. "\n</table>";

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter($oldQuery);

	// --------------------------------------------------------------------
?>
</body>
</html> 
