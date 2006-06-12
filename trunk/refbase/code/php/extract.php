<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./extract.php
	// Created:    29-Jul-02, 16:39
	// Modified:   27-May-06, 00:09

	// Search form that offers to extract
	// literature cited within a text and build
	// an appropriate reference list from that.

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
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Extract Citations", "index,follow", "Search the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
	showPageHeader($HeaderString, "");

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"POST\">";

	echo "\n<input type=\"hidden\" name=\"formType\" value=\"extractSearch\">"
		. "\n<input type=\"hidden\" name=\"submit\" value=\"Cite\">"; // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Cite" button)

	if (!isset($_SESSION['user_styles']))
		$citeStyleDisabled = " disabled"; // disable the style popup (and other form elements) if the session variable holding the user's styles isn't available
	else
		$citeStyleDisabled = "";
		
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the search form\">"
			. "\n<tr>\n\t<td width=\"58\" valign=\"top\"><b>Extract Citations From:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td><textarea name=\"sourceText\" rows=\"6\" cols=\"60\"$citeStyleDisabled>Paste your text here...</textarea></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\" rowspan=\"2\"><b>Serial Delimiters:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Specify the character(s) that enclose record serial numbers:</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Start Delimiter:&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"startDelim\" value=\"{\" size=\"4\"$citeStyleDisabled>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;End Delimiter:&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"endDelim\" value=\"}\" size=\"4\"$citeStyleDisabled></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\" rowspan=\"2\"><b>Display Options:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><input type=\"checkbox\" name=\"showLinks\" value=\"1\"$citeStyleDisabled checked>&nbsp;&nbsp;&nbsp;Display Links"
			. "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Show&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"showRows\" value=\"100\" size=\"4\"$citeStyleDisabled>&nbsp;&nbsp;&nbsp;records per page</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">View type:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"viewType\"$citeStyleDisabled>"
			. "\n\t\t\t<option>Web</option>"
			. "\n\t\t\t<option>Print</option>"
			. "\n\t\t</select>"
			. "\n\t</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_cite", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_cite'...
	// adjust the title string for the show cite button
	{
		$citeButtonLock = "";
		$citeTitle = "build a reference list for all citations contained within the entered text";
	}
	else // Note, that disabling the submit button is just a cosmetic thing -- the user can still submit the form by pressing enter or by building the correct URL from scratch! (however, there's some code in 'search.php' that will prevent query execution)
	{
		$citeButtonLock = " disabled";
		$citeTitle = "not available since you have no permission to use the cite feature";
	}

	echo "\n\t<td>\n\t\t<br><input type=\"submit\" name=\"submit\" value=\"Cite\"$citeButtonLock title=\"$citeTitle\"$citeStyleDisabled>&nbsp;&nbsp;&nbsp;"
			. "\n\t\tusing style:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"citeStyleSelector\" title=\"choose the output style for your reference list\"$citeStyleDisabled>";

	if (isset($_SESSION['user_styles']))
	{
		$optionTags = buildSelectMenuOptions($_SESSION['user_styles'], " *; *", "\t\t\t", false); // build properly formatted <option> tag elements from the items listed in the 'user_styles' session variable
		echo $optionTags;
	}
	else
		echo "<option>(no styles available)</option>";

	echo "\n\t\t</select>&nbsp;&nbsp;&nbsp;"
			. "\n\t\tsort by:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"citeOrder\" title=\"choose the primary sort order for your reference list\"$citeStyleDisabled>"
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
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString, "");

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
