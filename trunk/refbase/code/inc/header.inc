<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./header.inc
	// Created:    28-Jul-02, 11:21
	// Modified:   16-Nov-03, 21:30

	// This is the header include file.
	// It contains functions that provide the HTML header
	// as well as the visible header that gets displayed on every page.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// --------------------------------------------------------------------

	// Inserts the HTML <head>...</head> block as well as the initial <body> tag:
	function displayHTMLhead($pageTitle, $metaRobots, $metaDescription, $additionalMeta, $includeJavaScript, $includeJavaScriptFile)
	{
		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\""
			. "\n\t\t\"http://www.w3.org/TR/html4/loose.dtd\">"
			. "\n<html>"
			. "\n<head>"
			. "\n\t<title>" . $pageTitle . "</title>"
			. "\n\t<meta name=\"date\" content=\"" . date('d-M-y') . "\">"
			. "\n\t<meta name=\"robots\" content=\"" . $metaRobots . "\">"
			. "\n\t<meta name=\"description\" lang=\"en\" content=\"" . $metaDescription . "\">"
			. "\n\t<meta name=\"keywords\" lang=\"en\" content=\"search citation web database polar marine science literature references mysql php\">"
			. $additionalMeta
			. "\n\t<meta http-equiv=\"content-language\" content=\"en\">"
			. "\n\t<meta http-equiv=\"content-type\" content=\"text/html; charset=iso-8859-1\">"
			. "\n\t<meta http-equiv=\"Content-Style-Type\" content=\"text/css\">"
			. "\n\t<link rel=\"stylesheet\" href=\"style.css\" type=\"text/css\" title=\"CSS Definition\">";

		if ($includeJavaScriptFile != "")
			echo "\n\t<script language=\"JavaScript\" type=\"text/javascript\" src=\"" . $includeJavaScriptFile . "\">"
				. "\n\t</script>";

		if ($includeJavaScript == true)
			echo "\n\t<script language=\"JavaScript\" type=\"text/javascript\">"
				. "\n\t\tfunction checkall(val,formpart){"
				. "\n\t\t\tx=0;"
				. "\n\t\t\twhile(document.queryResults.elements[x]){"
				. "\n\t\t\t\tif(document.queryResults.elements[x].name==formpart){"
				. "\n\t\t\t\t\tdocument.queryResults.elements[x].checked=val;"
				. "\n\t\t\t\t}"
				. "\n\t\t\t\tx++;"
				. "\n\t\t\t}"
				. "\n\t\t}"
				. "\n\t</script>";

		echo "\n</head>"
			. "\n<body>\n";
	}

	// --------------------------------------------------------------------

	// Displays the visible header:
	function showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, $oldQuery)
	{
		global $officialDatabaseName;
		global $hostInstitutionAbbrevName;
		global $hostInstitutionURL;

		echo "<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This holds the title logo and info\">"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\" rowspan=\"2\" align=\"left\" width=\"170\"><a href=\"$hostInstitutionURL\"><img src=\"img/logo.gif\" border=\"0\" alt=\"" . htmlentities($hostInstitutionAbbrevName) . " Logo\" width=\"143\" height=\"107\"></a></td>"
			. "\n\t<td>"
			. "\n\t\t<h2>" . htmlentities($officialDatabaseName) . "</h2>"
			. "\n\t\t<span class=\"smallup\"><a href=\"index.php\">Home</a>&nbsp;|&nbsp;<a href=\"simple_search.php\">Simple Search</a>&nbsp;|&nbsp;<a href=\"advanced_search.php\">Advanced Search</a>&nbsp;|&nbsp;<a href=\"record.php?recordAction=add&amp;oldQuery=" . rawurlencode($oldQuery) . "\">Add Record</a>&nbsp;|&nbsp;<a href=\"import_csa.php\">CSA Import</a></span>"
			. "\n\t</td>"
			. "\n\t<td align=\"right\" valign=\"middle\">" . $loginWelcomeMsg . "<br>" . $loginStatus . "</td>"
			. "\n</tr>"
			. "\n<tr>"
//			. "\n\t<td>&nbsp;</td>" // img in 'header.inc' now spans this row (by rowspan="2")
			. "\n\t<td>" . $HeaderString . "</td>"
			. "\n\t<td align=\"right\" valign=\"middle\">" . $loginLinks . "</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n<hr align=\"center\" width=\"95%\">";
	}

	// --------------------------------------------------------------------
?>
