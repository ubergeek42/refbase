<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./sql_search.php
	// Created:    29-Jul-02, 16:39
	// Modified:   29-Sep-04, 14:57

	// Search form that offers to specify a custom sql query.
	// It offers some output options (like how many records to display per page)
	// and provides some examples and links for further information on sql queries.

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

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
		$HeaderString = "Search the database by use of a SQL query:"; // Provide the default message
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

	// Check if the script was called with parameters (like: 'sql_search.php?customQuery=1&sqlQuery=...&showQuery=...&showLinks=...')
	// If so, the parameter 'customQuery=1' will be set:
	if (isset($_REQUEST['customQuery']))
		$customQuery = $_REQUEST['customQuery']; // accept any previous SQL queries
	else
		$customQuery = "0";

	if ($customQuery == "1") // the script was called with parameters
		{
			$sqlQuery = $_REQUEST['sqlQuery']; // accept any previous SQL queries
				$sqlQuery = str_replace('\"','"',$sqlQuery); // convert \" into "
				$sqlQuery = str_replace('\\\\','\\',$sqlQuery);
//				$sqlQuery = str_replace('\\\\\\\'','\'',$sqlQuery); // convert \\\' into '

			$showQuery = $_REQUEST['showQuery']; // extract the $showQuery parameter
			if ("$showQuery" == "1")
				$checkQuery = " checked";
			else
				$checkQuery = "";
			
			$showLinks = $_REQUEST['showLinks']; // extract the $showLinks parameter
			if ("$showLinks" == "1")
				$checkLinks = " checked";
			else
				$checkLinks = "";

			$showRows = $_REQUEST['showRows']; // extract the $showRows parameter

			$displayType = $_REQUEST['submit']; // extract the type of display requested by the user (either 'Display', 'Cite' or '')
			$citeStyle = $_REQUEST['citeStyleSelector']; // get the cite style chosen by the user (only occurs in 'extract.php' form and in query result lists)
			$citeOrder = $_REQUEST['citeOrder']; // get the citation sort order chosen by the user (only occurs in 'extract.php' form and in query result lists)

			$oldQuery = $_REQUEST['oldQuery']; // get the query URL of the formerly displayed results page (if available) so that its's available on the subsequent receipt page that follows any add/edit/delete action!
			if (ereg('sqlQuery%3D', $oldQuery)) // if '$oldQuery' still contains URL encoded data... ('%3D' is the URL encoded form of '=', see note below!)
				$oldQuery = rawurldecode($oldQuery); // ...URL decode old query URL
												// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
												//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!
			$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "
		}
	else // if there was no previous SQL query provide the default one:
		{
			$sqlQuery = "SELECT author, title, year, publication, volume, pages FROM refs WHERE year &gt; 2001 ORDER BY year DESC, author";
			$checkQuery = "";
			$checkLinks = " checked";
			$showRows = "10";
			$displayType = ""; // ('' will produce the default columnar output style)
			$citeStyle = "";
			$citeOrder = "";
			$oldQuery = "";
		}

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- SQL Search", "index,follow", "Search the " . htmlentities($officialDatabaseName), "", false, "", $viewType);
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, $oldQuery);

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"sqlSearch\">"
			. "\n<input type=\"hidden\" name=\"submit\" value=\"$displayType\">"
			. "\n<input type=\"hidden\" name=\"citeStyleSelector\" value=\"" . rawurlencode($citeStyle) . "\">"
			. "\n<input type=\"hidden\" name=\"citeOrder\" value=\"$citeOrder\">"
			. "\n<input type=\"hidden\" name=\"oldQuery\" value=\"" . rawurlencode($oldQuery) . "\">";
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the search form\">"
			. "\n<tr>\n\t<td width=\"58\" valign=\"top\"><b>SQL Query:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td><textarea name=\"sqlQuery\" rows=\"6\" cols=\"60\">$sqlQuery</textarea></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Display Options:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><input type=\"checkbox\" name=\"showLinks\" value=\"1\"$checkLinks>&nbsp;&nbsp;&nbsp;Display Links"
			. "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Show&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"showRows\" value=\"$showRows\" size=\"4\">&nbsp;&nbsp;&nbsp;records per page</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><input type=\"checkbox\" name=\"showQuery\" value=\"1\"$checkQuery>&nbsp;&nbsp;&nbsp;Display SQL query"
			. "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;View type:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"viewType\">"
			. "\n\t\t\t<option>Web</option>"
			. "\n\t\t\t<option>Print</option>"
			. "\n\t\t</select>"
			. "\n\t</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_sql_search", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_sql_search'...
	// adjust the title string for the search button
	{
		$sqlSearchButtonLock = "";
		$sqlSearchTitle = "search the database using the above query & display options";
	}
	else // Note, that disabling the submit button is just a cosmetic thing -- the user can still submit the form by pressing enter or by building the correct URL from scratch!
	{
		$sqlSearchButtonLock = " disabled";
		$sqlSearchTitle = "not available since you have no permission to perform custom SQL searches";
	}

	echo "\n\t<td><br><input type=\"submit\" value=\"Search\"$sqlSearchButtonLock title=\"$sqlSearchTitle\"></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td align=\"center\" colspan=\"3\">&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Examples:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td><code>SELECT author, title, year, publication FROM refs WHERE publication = \"Polar Biology\" AND author RLIKE \"Legendre|Ambrose\" ORDER BY year DESC, author</code></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\">&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td><code>SELECT serial, author, title, year, publication, volume FROM refs ORDER BY serial DESC LIMIT 10</code></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Help:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>The <a href=\"http://www.mysql.com/documentation/index.html\">MySQL online manual</a> has a <a href=\"http://www.mysql.com/documentation/mysql/bychapter/manual_Tutorial.html\">tutorial introduction</a> on using MySQL and provides a detailed description of the <a href=\"http://www.mysql.com/doc/S/E/SELECT.html\"><code>SELECT</code> syntax</a>.</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";
	
	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc.php')
	displayfooter($oldQuery);

	// --------------------------------------------------------------------
?>

</body>
</html> 
