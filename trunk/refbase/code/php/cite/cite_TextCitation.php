<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./cite/cite_TextCitation.php
	// Created:    28-Sep-04, 23:46
	// Modified:   26-Feb-06, 13:58

	// This is a citation style file (which must reside within the 'cite/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecord()' function that outputs a reference list from selected records according to the citation style defined
	// by a user's custom text citation format (or by the default format given in '$defaultTextCitationFormat' in 'ini.inc.php').

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// --------------------------------------------------------------------


	// --- BEGIN CITATION STYLE ---

	function citeRecord($row, $citeStyle)
	{
		global $defaultTextCitationFormat; // defined in 'ini.inc.php'
		global $userOptionsArray; // '$userOptionsArray' is made globally available by function 'generateCitations()' in 'search.php'

		// output records suitable for citation within a text, e.g., like: "Ambrose 1991 {3735}", "Ambrose & Renaud 1995 {3243}" or "Ambrose et al. 2001 {4774}"


		if (!empty($userOptionsArray) AND ($userOptionsArray['use_custom_text_citation_format'] == "yes")) // if the user wants to use a custom text citation format
			$textCitationFormat = $userOptionsArray['text_citation_format'];

		else // use the default text citation format that was specified by the admin in 'ini.inc.php'
			$textCitationFormat = $defaultTextCitationFormat;

		// this is a stupid hack that maps the names of the '$row' array keys to those used
		// by the '$formVars' array (which is required by function 'parsePlaceholderString()')
		// (eventually, the '$formVars' array should use the MySQL field names as names for its array keys)
		$formVars = buildFormVarsArray($row); // function 'buildFormVarsArray()' is defined in 'include.inc.php'

		// generate a text citation according to the given naming scheme:
		$record = parsePlaceholderString($formVars, $textCitationFormat, "<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>"); // function 'parsePlaceholderString()' is defined in 'include.inc.php'


		// Perform search & replace actions on the text:
		$searchReplaceActionsArray = array('(et +al\.)' => '<i>\\1</i>'); // print 'et al.' in italic

		$record = searchReplaceText($searchReplaceActionsArray, $record, false); // function 'searchReplaceText()' is defined in 'include.inc.php'


		return $record;
	}

	// --- END CITATION STYLE ---

