<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./show.php
	// Created:    02-Nov-03, 14:10
	// Modified:   29-Sep-04, 18:07

	// This script serves as a routing page which takes any record serial number, date, year or author that was passed as parameter
	// to the script, builds an appropriate SQL query and passes that to 'search.php' which will then display the corresponding
	// record in details view. This is to provide short URLs (like: '.../show.php?record=12345') for email announcements etc.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// Extract any parameters passed to the script:
	if (isset($_REQUEST['record']))
		$serial = $_REQUEST['record']; // get record serial number
	else
		$serial = "";


	if (isset($_REQUEST['date']))
		$date = $_REQUEST['date']; // get date
	else
		$date = "";


	if (isset($_REQUEST['when']))
		$when = $_REQUEST['when']; // get info about what kind of date shall be searched for ("when=edited" -> search field 'modified_date'; otherwise -> search field 'created_date')
	else
		$when = "";


	if (isset($_REQUEST['range']))
		$range = $_REQUEST['range']; // check the date range ("range=after" -> return all records whose created/modifed date is after '$date'; "range=before" -> return all records whose created/modifed date is before '$date')
	else
		$range = "";


	if (isset($_REQUEST['year']))
		$year = $_REQUEST['year']; // get year
	else
		$year = "";


	if (isset($_REQUEST['author']))
		$author = $_REQUEST['author']; // get author
	else
		$author = "";


	if (isset($_REQUEST['without']))
		$without = $_REQUEST['without']; // when searching for authors, check whether duplicate records should be included
	else
		$without = "";


	if (isset($_REQUEST['headerMsg']))
		$headerMsg = $_REQUEST['headerMsg']; // we'll accept custom header messages as well
						// Note: custom header messages are provided so that it's possible to include an information string within a link. This info string could
						//       e.g. describe who's publications are being displayed (e.g.: "Publications of Matthias Steffens:"). I.e., a link pointing to a
						//       persons own publications can include the appropriate owner information (it will show up as header message)
	else
		$headerMsg = "";


	// currently, the following two fields will get only interpreted when searching for authors (i.e., the 'author' parameter must be present):
	if (isset($_REQUEST['only']))
		$only = $_REQUEST['only']; // check whether we are supposed to show records of a particular subset only. Currently, only "only=selected" is supported. If this
								// parameter and value is present, we'll restrict the search results to those records that have the 'selected' bit set for a particular user.
								// IMPORTANT: Since the 'selected' field is specific to every user (table 'user_data'), the 'userID' parameter must be specified as well!!
	else
		$only = "";


	if (isset($_REQUEST['userID']))
		$userID = $_REQUEST['userID']; // when searching user specific fields (like the 'selected' field), this parameter specifies the user's user ID.
									// I.e., the 'userID' parameter does only make sense when specified together with "only=selected". As an example,
									// "show.php?author=...&only=selected&userID=2" will show every record where the user who's identified by user ID "2" has set the selected bit to "yes".
	else
		$userID = "";


	// Check the correct parameters have been passed:
	if (empty($serial) AND empty($year) AND empty($date) AND empty($author))
	{
		// if 'show.php' was called without any valid parameters:

		// save an error message:
//		$HeaderString = "<b><span class=\"warning\">Incorrect or missing parameters to script 'show.php'!</span></b>";

		// Write back session variables:
//		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Redirect the browser back to the main page:
//		header("Location: index.php"); // Note: if 'header("Location: " . $_SERVER['HTTP_REFERER'])' is used, the error message won't get displayed! ?:-/
//		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

		// OR ALTERNATIVELY:
	
		// if 'show.php' was called without any valid parameters, we'll present a form where a user can input a record serial number.
		// Currently, this form will not present form elements for other supported options (like searching by date, year or author),
		// since this would just double search functionality from other search forms.

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString']))
			$HeaderString = "Display details for a particular record by entering its database serial number:"; // Provide the default message
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}
	
		// Extract the view type requested by the user (either 'Print', 'Web' or ''):
		// ('' will produce the default 'Web' output style)
		if (isset($_REQUEST['viewType']))
			$viewType = $_REQUEST['viewType'];
		else
			$viewType = "";

		// Show the login status:
		showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')
	
		// DISPLAY header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(htmlentities($officialDatabaseName) . " -- Show Record", "index,follow", "Search the " . htmlentities($officialDatabaseName), "", false, "", $viewType);
		showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");
	
		// Start <form> and <table> holding the form elements:
		echo "\n<form action=\"show.php\" method=\"POST\">";
		echo "\n<input type=\"hidden\" name=\"formType\" value=\"show\">"
			. "\n<input type=\"hidden\" name=\"submit\" value=\"Show Record\">" // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Show" button)
			. "\n<input type=\"hidden\" name=\"showLinks\" value=\"1\">"; // embed '$showLinks=1' so that links get displayed on any 'display details' page
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds a form that offers to show a record by its serial number\">"
				. "\n<tr>\n\t<td width=\"58\" valign=\"top\"><b>Record Serial:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
				. "\n\t<td><input type=\"text\" name=\"record\" value=\"\" size=\"40\"></td>"
				. "\n</tr>"
				. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>";

		if (isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_details_view'...
		// adjust the title string for the show record button
		{
			$showRecordButtonLock = "";
			$showRecordTitle = "display record details for the entered serial number";
		}
		else // Note, that disabling the submit button is just a cosmetic thing -- the user can still submit the form by pressing enter or by building the correct URL from scratch!
		{
			$showRecordButtonLock = " disabled";
			$showRecordTitle = "not available since you have no permission to display any record details";
		}

		echo "\n\t<td><input type=\"submit\" name=\"submit\" value=\"Show Record\"$showRecordButtonLock title=\"$showRecordTitle\"></td>"
				. "\n</tr>"
				. "\n<tr>\n\t<td align=\"center\" colspan=\"3\">&nbsp;</td>"
				. "\n</tr>"
				. "\n<tr>\n\t<td valign=\"top\"><b>Help:</b></td>\n\t<td>&nbsp;</td>"
				. "\n\t<td valign=\"top\">This form enables you to directly jump to a particular record and display its record details. Just enter the database serial number for that record and press the 'Show Record' button."
				. " (In order to view the database serial number of a particular record, click the <img src=\"img/details.gif\" alt=\"show details\" title=\"show details\" width=\"9\" height=\"17\" hspace=\"0\" border=\"0\" align=\"top\">"
				. " icon that's available in any list view next to that record and note the number listed within the 'Serial' field.)</td>"
				. "\n</tr>"
				. "\n</table>"
				. "\n</form>";

		// --------------------------------------------------------------------
	
		// DISPLAY THE HTML FOOTER:
		// call the 'displayfooter()' function from 'footer.inc.php')
		displayfooter("");
	
		// --------------------------------------------------------------------

		echo "</body>"
			. "\n</html>\n";
	}
	else // the script was called with at least one of the following parameters: 'record', 'date', 'year', 'author'
	{
		// Note: Parameters will be processed in the order "record, date, year, author". That means, if the 'record' parameter is present, it will trigger a search
		//       for a particular record serial number, no matter what other parameters have been passed to the script! If the 'record' parameter has not been specified
		//       we'll next look for the 'date' parameter. If it is present, all records created (or modified if "when=edited") on that particular date will be shown.
		//       If the 'record' and ' date' parameters aren't present, the 'year' parameter will be checked, showing all records that were published in that year.
		//       Finally, if no parameter but the 'author' parameter is present, we'll display all records that match the specified author.
	
		// CONSTRUCT SQL QUERY:
	
		// 'record' parameter is present:
		if (!empty($serial))
		{
			// first, check if the user is allowed to display any record details:
			if (isset($_SESSION['user_permissions']) AND !ereg("allow_details_view", $_SESSION['user_permissions'])) // no, the 'user_permissions' session variable does NOT contain 'allow_details_view'...
			{
				// save an appropriate error message:
				$HeaderString = "<b><span class=\"warning\">You have no permission to display any record details!</span></b>";
	
				// Write back session variables:
				saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		
				header("Location: show.php"); // redirect back to 'show.php'
	
				exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
			}

			// Note: the 'verifySQLQuery()' function that gets called by 'search.php' to process query data with "$formType = sqlSearch" will add the user specific fields to the 'SELECT' clause
			// and the 'LEFT JOIN...' part to the 'FROM' clause of the SQL query if a user is logged in. It will also add 'serial', 'file', 'url' & 'doi' columns
			// as required. Therefore it's sufficient to provide just the plain SQL query here:
			$query = "SELECT author, title, type, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, notes, approved, location, call_number, serial";
			//       (the above string MUST end with ", call_number, serial" in order to have the described query completion feature work correctly!
			$query .= " FROM refs WHERE serial RLIKE \"^(" . $serial . ")$\""; // add FROM & WHERE clause
			$query .= " ORDER BY author, year DESC, publication"; // add the default ORDER BY clause
	
			// Build the correct query URL:
			$queryURL = "sqlQuery=" . rawurlencode($query) . "&formType=sqlSearch&submit=Display&showLinks=1&headerMsg=" . rawurlencode($headerMsg); // we skip unnecessary parameters ('search.php' will use it's default values for them)
		}
		elseif (!empty($date)) // else if 'date' parameter is present:
		{
			$query = "SELECT author, title, year, publication, volume, pages";
	
			if ($range == "after")
				$searchOperator = ">"; // return all records whose created/modifed date is after '$date'
			elseif ($range == "before")
				$searchOperator = "<"; // return all records whose created/modifed date is before '$date'
			else
				$searchOperator = "="; // return all records whose created/modifed date matches exactly '$date'
	
			if ($when == "edited")
				$query .= " FROM refs WHERE modified_date " . $searchOperator . " \"" . $date . "\""; // add FROM & WHERE clause
			else
				$query .= " FROM refs WHERE created_date " . $searchOperator . " \"" . $date . "\""; // add FROM & WHERE clause
	
			$query .= " ORDER BY author, year DESC, publication"; // add the default ORDER BY clause
	
			// Build the correct query URL:
			$queryURL = "sqlQuery=" . rawurlencode($query) . "&formType=sqlSearch&showLinks=1&headerMsg=" . rawurlencode($headerMsg); // we skip unnecessary parameters ('search.php' will use it's default values for them)
		}
		elseif (!empty($year)) // else if 'year' parameter is present:
		{
			$query = "SELECT author, title, year, publication, volume, pages";
			$query .= " FROM refs WHERE year = " . $year; // add FROM & WHERE clause
			$query .= " ORDER BY author, year DESC, publication"; // add the default ORDER BY clause
	
			// Build the correct query URL:
			$queryURL = "sqlQuery=" . rawurlencode($query) . "&formType=sqlSearch&showLinks=1&headerMsg=" . rawurlencode($headerMsg); // we skip unnecessary parameters ('search.php' will use it's default values for them)
		}
		elseif (!empty($author)) // else if 'author' parameter is present:
		{
			$query = "SELECT type, author, year, title, publication, abbrev_journal, volume, issue, pages, thesis, editor, publisher, place, abbrev_series_title, series_title, series_editor, series_volume, series_issue, language, author_count, online_publication, online_citation, doi, serial";
	
			if (!empty($userID)) // the 'userID' parameter was specified -> we include user specific fields
				$query .= " FROM refs LEFT JOIN user_data ON serial = record_id AND user_id = $userID"; // add FROM clause (including the 'LEFT JOIN...' part)
			else
				$query .= " FROM refs"; // add FROM clause
	
			$query .= " WHERE author RLIKE \"" . $author . "\""; // add initial WHERE clause
	
			if ($without == "dups")
				$query .= " AND (orig_record IS NULL OR orig_record < 0)"; // add additional WHERE clause
	
			if ($only == "selected" AND !empty($userID)) // in order to search for user specific fields (like 'selected'), the 'userID' parameter must be given as well!
				$query .= " AND selected = \"yes\""; // add additional WHERE clause
	
			$query .= " ORDER BY year DESC, first_author, author_count, author, title"; // sort records first by year (descending), then in the usual way
	
			// Build the correct query URL:
			$queryURL = "sqlQuery=" . rawurlencode($query) . "&formType=sqlSearch&submit=Cite&showLinks=1&showRows=100&citeOrder=year&citeStyleSelector=" . rawurlencode($defaultCiteStyle) . "&headerMsg=" . rawurlencode($headerMsg); // we skip unnecessary parameters ('search.php' will use it's default values for them)
			// the variable '$defaultCiteStyle' is defined in 'ini.inc.php'
		}
	
		// call 'search.php' with the correct query URL in order to display record details:
		header("Location: search.php?$queryURL");
	}
?>
