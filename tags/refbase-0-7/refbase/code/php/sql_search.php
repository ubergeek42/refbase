<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./sql_search.php
	// Created:    29-Jul-02, 16:39
	// Modified:   16-Nov-03, 21:49

	// Search formular that offers to specify a custom sql query.
	// It offers some output options (like how many records to display per page)
	// and provides some examples and links for further information on sql queries.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
	// Incorporate some include files:
	include 'header.inc'; // include header
	include 'footer.inc'; // include footer
	include 'include.inc'; // include common functions
	include "ini.inc.php"; // include common variables

	// --------------------------------------------------------------------

	// Connect to a session
	session_start();

	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!

	// --------------------------------------------------------------------

	// If there's no stored message available:
	if (!session_is_registered("HeaderString"))
		$HeaderString = "Search the database by use of a SQL query:"; // Provide the default message
	else
		session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

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

			$displayType = $_REQUEST['submit']; // extract the type of display requested by the user (either 'Display', 'Export' or '')
			$exportFormat = $_REQUEST['exportFormatSelector']; // get the export format chosen by the user (only occurs in 'extract.php' form and in query result lists)
			$exportOrder = $_REQUEST['exportOrder']; // get the export sort order chosen by the user (only occurs in 'extract.php' form and in query result lists)

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
			$checkQuery = " checked";
			$checkLinks = " checked";
			$showRows = "10";
			$displayType = ""; // ('' will produce the default columnar output style)
			$exportFormat = "";
			$exportOrder = "";
			$oldQuery = "";
		}

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- SQL Search", "index,follow", "Search the " . htmlentities($officialDatabaseName), "", false, "");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, $oldQuery);

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"sqlSearch\">"
			. "\n<input type=\"hidden\" name=\"submit\" value=\"$displayType\">"
			. "\n<input type=\"hidden\" name=\"exportFormatSelector\" value=\"" . rawurlencode($exportFormat) . "\">"
			. "\n<input type=\"hidden\" name=\"exportOrder\" value=\"$exportOrder\">"
			. "\n<input type=\"hidden\" name=\"oldQuery\" value=\"" . rawurlencode($oldQuery) . "\">";
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the search form\">"
			. "\n<tr>\n\t<td width=\"58\" valign=\"top\"><b>SQL Query:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td><textarea name=\"sqlQuery\" rows=\"6\" cols=\"60\">$sqlQuery</textarea></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Output Options:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><input type=\"checkbox\" name=\"showLinks\" value=\"1\"$checkLinks>&nbsp;&nbsp;&nbsp;Display Links"
			. "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Show&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"showRows\" value=\"$showRows\" size=\"4\">&nbsp;&nbsp;&nbsp;records per page</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><input type=\"checkbox\" name=\"showQuery\" value=\"1\"$checkQuery>&nbsp;&nbsp;&nbsp;Display SQL query</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td><br><input type=\"submit\" value=\"Search\"></td>"
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
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter($oldQuery);

	// --------------------------------------------------------------------
?>
</body>
</html> 
