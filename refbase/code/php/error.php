<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./error.php
	// Created:    5-Jan-03, 16:35
	// Modified:   16-Nov-03, 22:22

	// This php script will display an error page
	// showing any error that did occur. It will display
	// a link to the previous search results page (if any)

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

	// Check if any error occurred while processing the database UPDATE/INSERT/DELETE
	$errorNo = $_REQUEST['errorNo'];
	$errorMsg = $_REQUEST['errorMsg'];
	$errorMsg = ereg_replace("\\\\(['\"])","\\1",$errorMsg); // replace any \" or \' with " or ', respectively

	// Extract the header message that was returned by originating script:
	$HeaderString = $_REQUEST['headerMsg'];
	$HeaderString = ereg_replace("(\\\\)+(['\"])","\\2",$HeaderString); // replace any \" or \' with " or ', respectively (Note: the expression '\\\\' describes only *one* backslash! -> '\')

	// Extract generic variables from the request:
	$oldQuery = $_REQUEST['oldQuery']; // fetch the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "

	if (isset($HTTP_REFERER))
		$referer = $HTTP_REFERER;
	else
		$referer = "index.php"; // if there's no HTTP referer available we relocate back to the main page

	// --------------------------------------------------------------------

	// (4) DISPLAY HEADER & RESULTS
	//     (NOTE: Since there's no need to query the database here, we won't perform any of the following: (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (5) CLOSE CONNECTION)

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// (4a) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- Error", "noindex,nofollow", "Feedback page that shows any error that occurred while using the " . htmlentities($officialDatabaseName), "", false, "");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, $oldQuery);


	// URL encode the sqlQuery part within '$oldQuery' while maintaining the rest unencoded(!):
	$oldQuerySQLPart = preg_replace("/sqlQuery=(.+?)&amp;.+/", "\\1", $oldQuery); // extract the sqlQuery part within '$oldQuery'
	$oldQueryOtherPart = preg_replace("/sqlQuery=.+?(&amp;.+)/", "\\1", $oldQuery); // extract the remaining part after the sqlQuery
	$oldQuerySQLPart = rawurlencode($oldQuerySQLPart); // URL encode sqlQuery part within '$oldQuery'
	$oldQueryPartlyEncoded = "sqlQuery=" . $oldQuerySQLPart . $oldQueryOtherPart; // Finally, we merge everything again

	// Build appropriate links:
	$links = "\n<tr>"
			. "\n\t<td>"
			. "\n\t\tChoose how to proceed:&nbsp;&nbsp;"
			. "\n\t\t<a href=\"" . str_replace('&','&amp;',$referer) . "\">Go Back</a>"; // provide a 'go back' link (the following would only work with javascript: <a href=\"javascript:history.back()\">Go Back</a>")

	if ($oldQuery != "") // only provide a link to any previous search results if '$oldQuery' isn't empty
		$links .= "\n\t\t&nbsp;&nbsp;-OR-&nbsp;&nbsp;"
				. "\n\t\t<a href=\"search.php?" . $oldQueryPartlyEncoded . "\">Display previous search results</a>";

	$links .= "\n\t\t&nbsp;&nbsp;-OR-&nbsp;&nbsp;"
			. "\n\t\t<a href=\"index.php\">Goto " . htmlentities($officialDatabaseName) . " Home</a>" // we include the link to the home page here
			. "\n\t</td>"
			. "\n</tr>";

	showErrorMessage($errorNo, $errorMsg, $links, $oldQuery);

	// --------------------------------------------------------------------

	// SHOW ERROR MESSAGE:
	function showErrorMessage($errorNo, $errorMsg, $links, $oldQuery)
	// includes code from 'footer.inc'
	{
		global $officialDatabaseName;
		global $hostInstitutionAbbrevName;
		global $hostInstitutionURL;

		die("\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\">\n<tr>\n\t<td valign=\"top\"> Error "
		. $errorNo . " : <b>" . $errorMsg . "</b>"
		. "</td>\n</tr>"
		. $links		
		. "\n</table>"
		. "\n<hr align=\"center\" width=\"95%\">"
		. "\n<p align=\"center\"><a href=\"simple_search.php\">Simple Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"advanced_search.php\">Advanced Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"sql_search.php\">SQL Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"library_search.php\">Library Search</a></p>"
		. "\n<p align=\"center\"><a href=\"$hostInstitutionURL\">" . htmlentities($hostInstitutionAbbrevName) . " Home</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"index.php\">" . htmlentities($officialDatabaseName) . " Home</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"record.php?recordAction=add&amp;oldQuery=" . rawurlencode($oldQuery) . "\">Add Record</a></p>"
		. "\n<p align=\"center\">"
		.  date('r')
		. "</p>"
		. "\n</body>"
		. "\n</html>");
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc') // CAUTION: due to the use of die in 'showErrorMessage()' the 'displayfooter()' function is currently not used!
	displayfooter($oldQuery);

	// --------------------------------------------------------------------
?>
</body>
</html> 
