<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>IP&Ouml; Literature Database -- Record Action Feedback</title>
	<meta name="date" content=<?php echo "\"" . date("d-M-y") . "\""; ?>>
	<meta name="robots" content="index,follow">
	<meta name="description" lang="en" content="Feedback page that confirms any adding, editing or deleting of records in the IP&Ouml; Literature Database">
	<meta name="keywords" lang="en" content="search citation web database polar marine science literature references mysql php">
	<meta http-equiv="content-language" content="en">
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<link rel="stylesheet" href="style.css" type="text/css" title="CSS Definition">
</head>
<body>
<?php
	// This php script will display a feedback page after any action of adding/editing/deleting a record.
	// It will display links to the modifyed/added record as well as to the previous search results page (if any)

	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password
//	include 'error.inc'; // include the 'showerror()' function -> CAUTION: Currently, this script does NOT use the standard 'showerror()' function from 'error.inc'! (since the error no. & message is obtained from 'modify.php' and passed as parameter to 'showReceiptError')
	include 'header.inc'; // include header
	include 'footer.inc'; // include footer

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// --------------------------------------------------------------------

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// Check if any error occurred while processing the database UPDATE/INSERT/DELETE
	$error = $_REQUEST['error'];
	$errorNo = $_REQUEST['errorNo'];
	$errorMsg = $_REQUEST['errorMsg'];
	$errorMsg = ereg_replace("\\\\(['\"])","\\1",$errorMsg); // replace any \" or \' with " or ', respectively

	// Extract the type of action requested by the user (either 'add', 'edit', 'delet' or ''):
	// ('' will be treated equal to 'add')
	$recordAction = $_REQUEST['recordAction'];
	if ("$recordAction" == "")
		$recordAction = "add"; // '' will be treated equal to 'add'

	// Extract the id number of the record that was added/edited/deleted by the user:
	$serialNo = $_REQUEST['serialNo'];

	// Extract the header message that was returned by 'modify.php':
	$headerMsg = $_REQUEST['headerMsg'];

	// Extract generic variables from the request:
	$oldQuery = $_REQUEST['oldQuery']; // fetch the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "

	// --------------------------------------------------------------------

	// (4) DISPLAY HEADER & RESULTS
	//     (NOTE: Since there's no need to query the database here, we won't perform any of the following: (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (5) CLOSE CONNECTION)

	// (4a) DISPLAY header:
	showheader($headerMsg);


	if ($error != 1) // no error occurred while processing the database UPDATE/INSERT/DELETE
	{
		// (4b) DISPLAY results:
		// First, construct the correct sql query that will link back to the added/edited record:
		$sqlQuery = "SELECT author, title, year, publication, abbrev_journal, volume, issue, pages, address, corporate_author, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, location, call_number, reprint_status, marked, approved, file, serial, type, notes, user_keys, user_notes";
		$sqlQuery .= " FROM refs WHERE serial RLIKE \"^" . $serialNo . "$\" ORDER BY author, year DESC, publication"; // we simply use the fixed default ORDER BY clause here
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
	
			echo "\n\t\t<a href=\"index.php\">Goto Literature Database Home</a>"; // we include the link to the home page here so that "Choose how to proceed:" never stands without any link to go
	
		echo "\n\t</td>"
			. "\n</tr>"
			. "\n</table>";
	}
	else // some error occurred while processing the database UPDATE/INSERT/DELETE
	{
		showReceiptError($errorNo, $errorMsg);
	}

	// --------------------------------------------------------------------

	// BUILD THE HTML HEADER:
	function showheader($headerMsg)
	{
		// call the 'displayheader()' function from 'header.inc'):
		displayheader();

		// finalize header containing the appropriate header string:
		echo "\n<tr>"
//			. "\n\t<td>&nbsp;</td>" // img in 'header.inc' now spans this row (by rowspan="2")
			. "\n\t<td>$headerMsg</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n<hr align=\"center\" width=\"95%\">";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter($oldQuery);

	// --------------------------------------------------------------------

	function showReceiptError($errorNo, $errorMsg) // CAUTION: Currently, this script does NOT use the standard 'showerror()' function from 'error.inc'! (since the error no. & message is obtained from 'modify.php' and passed as parameter to 'showReceiptError')
	// includes code from 'footer.inc'
	{
		die("\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\">\n<tr>\n\t<td valign=\"top\"> Error "
		. $errorNo
		. " : " . $errorMsg
		. "&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>\n</tr>\n</table>"
		. "\n<hr align=\"center\" width=\"95%\">"
		. "\n<p align=\"center\"><a href=\"simple_search.php\">Simple Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"advanced_search.php\">Advanced Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"sql_search.php\">SQL Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"library_search.php\">Library Search</a></p>"
		. "\n<p align=\"center\"><a href=\"http://www.uni-kiel.de/ipoe/\">IP&Ouml; Home</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"index.php\">Literature Database Home</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"record.php\">Add Record</a></p>"
		. "\n<p align=\"center\">"
		.  date(r)
		. "</p>"
		. "\n</body>"
		. "\n</html>");
	}

	// --------------------------------------------------------------------
?>
</body>
</html> 
