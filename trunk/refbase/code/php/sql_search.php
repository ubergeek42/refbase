<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./sql_search.php
	// Created:    29-Jul-02, 16:39
	// Modified:   12-Jan-03, 16:35

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

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead("IP&Ouml; Literature Database -- SQL Search", "index,follow", "Search the IP&Ouml; Literature Database", "", false, "");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks);

	// Check if the script was called with parameters (like: 'sql_search.php?customQuery=1&sqlQuery=...&showQuery=...&showLinks=...')
	// If so, the parameter 'customQuery=1' will be set:
	$customQuery = $_REQUEST['customQuery']; // accept any previous SQL queries

	if ("$customQuery" == "1") // the script was called with parameters
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
		}
	else // if there was no previous SQL query provide the default one:
		{
			$sqlQuery = "SELECT author, title, year, publication, volume, pages FROM refs WHERE year &gt; 2001 ORDER BY year DESC, author";
			$checkQuery = " checked";
			$checkLinks = " checked";
			$showRows = "10";
		}

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"sqlSearch\">";
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
	displayfooter("");

	// --------------------------------------------------------------------
?>
</body>
</html> 
