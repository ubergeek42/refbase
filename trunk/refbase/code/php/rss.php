<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./rss.php
	// Created:    25-Sep-04, 12:10
	// Modified:   15-Feb-05, 11:58

	// This script will generate a dynamic RSS feed for the current query.
	// Useage: Perform your query until you've got the desired results. Then, copy the "track" link in the header
	// message of any search results page and use this URL as feed URL when subscribing within your Newsreader.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// Extract any parameters passed to the script:
	if (isset($_REQUEST['where']))
		$queryWhereClause = $_REQUEST['where']; // get the WHERE clause that was passed within the link
	else
		$queryWhereClause = "";

	if (isset($_REQUEST['showRows']))
		$showRows = $_REQUEST['showRows']; // get the number of records that shall be returned
	else
		$showRows = 0; 


	// Check the correct parameters have been passed:

	// Adjust the '$showRows' value if not previously defined, or if a wrong number (<=0 or float) was given
	if (empty($showRows) || ($showRows <= 0) || !ereg("^[0-9]+$", $showRows))
		$showRows = 10; // by default, we'll return those 10 records matching the specified WHERE clause that were added most recently

	if (empty($queryWhereClause)) // if 'rss.php' was called without the 'where' parameter:
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">Incorrect or missing parameters to script 'rss.php'!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Redirect the browser back to the main page:
		header("Location: index.php"); // Note: if 'header("Location: " . $_SERVER['HTTP_REFERER'])' is used, the error message won't get displayed! ?:-/
		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// If we made it here, then the script was called with all required parameters (which, currently, is just the 'where' parameter :)

	// CONSTRUCT SQL QUERY:

	// Note: the 'verifySQLQuery()' function that gets called below will add the user specific fields to the 'SELECT' clause and the
	// 'LEFT JOIN...' part to the 'FROM' clause of the SQL query if a user is logged in. It will also add 'serial', 'file', 'url' & 'doi' columns
	// as required. Therefore it's sufficient to provide just the plain SQL query here:
	$sqlQuery = "SELECT type, author, year, title, publication, abbrev_journal, volume, issue, pages, thesis, editor, publisher, place, abbrev_series_title, series_title, series_editor, series_volume, series_issue, language, author_count, online_publication, online_citation, doi, created_date, created_time, created_by, modified_date, modified_time, modified_by, serial";

	$sqlQuery .= " FROM $tableRefs WHERE " . $queryWhereClause; // add FROM clause and the specified WHERE clause

	$sqlQuery .= " ORDER BY created_date DESC, created_time DESC, modified_date DESC, modified_time DESC, serial DESC"; // sort records such that newly added/edited records get listed top of the list


	// since a malicious user could change the 'where' parameter manually to gain access to user-specific data of other users, we'll run the SQL query thru the 'verifySQLQuery()' function:
	// (this function does also add/remove user-specific query code as required and will fix problems with escape sequences within the SQL query)
	$query = verifySQLQuery($sqlQuery, "", "RSS", "1"); // function 'verifySQLQuery()' is defined in 'include.inc.php'

	// the 'verifySQLQuery()' function will save an error message to the 'HeaderString' session variable if something went wrong (e.g., if a user who's NOT logged in tries to query user specific fields)
	if (isset($_SESSION['HeaderString'])) // if there's a 'HeaderString' session variable
	{
		header("Location: index.php"); // redirect to main page ('index.php') which will display the error message stored within the 'HeaderString' session variable
		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// (3) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	// find out how many rows are available:
	$rowsFound = @ mysql_num_rows($result);
	
	// construct a meaningful channel description based on the specified 'WHERE' clause:
	$rssChannelDescription = "Displays all newly added records where " . explainSQLQuery($queryWhereClause) . "."; // function 'explainSQLQuery()' is defined in 'include.inc.php'

	// Generate RSS XML data from the result set (upto the limit given in '$showRows'):
	$rssFeed = generateRSS($result, $showRows, $rssChannelDescription); // function 'generateRSS()' is defined in 'include.inc.php'
	// --------------------------------------------------------------------

	// (4) DISPLAY search results as RSS feed:
	header('Content-type: text/plain; charset=' . $contentTypeCharset); // set mimetype to 'text/plain' and character encoding to the one given in '$contentTypeCharset' (which is defined in 'ini.inc.php')

	echo $rssFeed;

	// --------------------------------------------------------------------

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>
