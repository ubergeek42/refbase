<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./extract.php
	// Created:    29-Jul-02, 16:39
	// Modified:   20-May-04, 23:05

	// Search form that offers to extract
	// literature cited within a text and build
	// an appropriate reference list from that.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
	// Incorporate some include files:
	include 'header.inc.php'; // include header
	include 'footer.inc.php'; // include footer
	include 'include.inc.php'; // include common functions
	include "ini.inc.php"; // include common variables
	
	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session();

	// --------------------------------------------------------------------

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
		$HeaderString = "Extract citations from a text and build an appropriate reference list:"; // Provide the default message
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

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- Extract Citations", "index,follow", "Search the " . htmlentities($officialDatabaseName), "", false, "", $viewType);
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"POST\">";

	echo "\n<input type=\"hidden\" name=\"formType\" value=\"extractSearch\">"
		. "\n<input type=\"hidden\" name=\"submit\" value=\"Export\">"; // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Export" button)

	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the search form\">"
			. "\n<tr>\n\t<td width=\"58\" valign=\"top\"><b>Extract Citations From:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td><textarea name=\"sourceText\" rows=\"6\" cols=\"60\">Paste your text here...</textarea></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\" rowspan=\"2\"><b>Serial Delimiters:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Specify the character(s) that enclose record serial numbers:</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Start Delimiter:&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"startDelim\" value=\"{\" size=\"4\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;End Delimiter:&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"endDelim\" value=\"}\" size=\"4\"></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\" rowspan=\"2\"><b>Display Options:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><input type=\"checkbox\" name=\"showLinks\" value=\"1\" checked>&nbsp;&nbsp;&nbsp;Display Links"
			. "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Show&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"showRows\" value=\"100\" size=\"4\">&nbsp;&nbsp;&nbsp;records per page</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">View type:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"viewType\">"
			. "\n\t\t\t<option>Web</option>"
			. "\n\t\t\t<option>Print</option>"
			. "\n\t\t</select>"
			. "\n\t</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<br><input type=\"submit\" name=\"submit\" value=\"Export\">&nbsp;&nbsp;&nbsp;"
			. "\n\t\treferences using style:&nbsp;&nbsp;"
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
	// call the 'displayfooter()' function from 'footer.inc.php')
	displayfooter("");

	// --------------------------------------------------------------------
?>

</body>
</html> 
