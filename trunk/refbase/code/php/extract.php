<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./extract.php
	// Created:    29-Jul-02, 16:39
	// Modified:   16-Nov-03, 21:32

	// Search formular that offers to extract
	// literature cited within a text and build
	// an appropriate reference list from that.

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
		$HeaderString = "Extract literature cited within a text and build an appropriate reference list:"; // Provide the default message
	else
		session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- Extract Cited Literature", "index,follow", "Search the " . htmlentities($officialDatabaseName), "", false, "");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"extractSearch\">"
		. "\n<input type=\"hidden\" name=\"submit\" value=\"Export\">" // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Export" button)
		. "\n<input type=\"hidden\" name=\"showLinks\" value=\"1\">"; // embed '$showLinks=1' so that links get displayed on any 'display details' page
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the search form\">"
			. "\n<tr>\n\t<td width=\"58\" valign=\"top\"><b>Extract Citations From:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td><textarea name=\"sourceText\" rows=\"6\" cols=\"60\">Paste your text here...</textarea></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Serial Delimiters:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Specify the character(s) that enclose record serial numbers:</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Start Delimiter:&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"startDelim\" value=\"{\" size=\"4\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;End Delimiter:&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"endDelim\" value=\"}\" size=\"4\"></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Output Options:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Show&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"showRows\" value=\"100\" size=\"4\">&nbsp;&nbsp;&nbsp;records per page</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<input type=\"submit\" name=\"submit\" value=\"Export\">&nbsp;&nbsp;&nbsp;"
			. "\n\t\tin Format:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"exportFormatSelector\">"
			. "\n\t\t\t<option>Polar Biol</option>"
			. "\n\t\t\t<option>Mar Biol</option>"
			. "\n\t\t\t<option>MEPS</option>"
			. "\n\t\t\t<option>Deep Sea Res</option>"
			. "\n\t\t\t<option>Text Citation</option>"
			. "\n\t\t</select>&nbsp;&nbsp;&nbsp;"
			. "\n\t\tsort by:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"exportOrder\">"
			. "\n\t\t\t<option>author</option>"
			. "\n\t\t\t<option>year</option>"
			. "\n\t\t</select>\n\t</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td align=\"center\" colspan=\"3\">&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Help:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">This form enables you to extract all citations from your text and build an appropriate reference list. To have this work simply include the serial numbers of your cited records within your text (as shown below) and enclose the serials by some preferrably unique characters. These delimiters must be specified in the text fields above.</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Example:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><code>Results of the german south polar expedition were published by Hennings (1906) {1141} as well as several other authors (e.g.: Wille 1924 {1785}; Heiden &amp; Kolbe 1928 {1127}).</code></td>"
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
