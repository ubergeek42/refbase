<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./cite/cite_TextCitation.php
	// Created:    28-Sep-04, 23:46
	// Modified:   13-Oct-04, 22:58

	// This is a citation style file (which must reside within the 'cite/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecord()' function that outputs a reference list from selected records according to the citation style used
	// *within the text* by journals like "Polar Biology" (Springer-Verlag, springeronline.com) and others. Includes record IDs in curly brackets.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// --------------------------------------------------------------------


	// --- BEGIN CITATION STYLE ---

	function citeRecord($row, $citeStyle)
	{
		// output records suitable for citation within a text, like: "Ambrose 1991 {3735}", "Ambrose & Renaud 1995 {3243}" or "Ambrose et al. 2001 {4774}"

		// currently the following parameters are not available via the GUI but are provided as fixed values here:
		$authorConnector = " & "; // string that connects first and second author (if author_count = 2)
		$etalPrintItalic = true; // specifies if "et al" should be either printed in italic (true) or as regular text (false)
		$etalWithDot = true; // specifies whether "et al" is followed by a dot (true) or not (false)
		$yearWithBrackets = false; // specifies whether the year is enclosed by a brackets (true) or not (false)
		$recordIDStartDelimiter = "{"; // specifies the string that prefixes the record id
		$recordIDEndDelimiter = "}"; // specifies the string that suffixes the record id
		
		// Call the 'extractAuthorsLastName()' function (defined in 'include.inc.php') to extract the last name of a particular author (specified by position). Required Parameters:
		//   1. pattern describing delimiter that separates different authors
		//   2. pattern describing delimiter that separates author name & initials (within one author)
		//   3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name)
		//   4. contents of the author field
		$record = extractAuthorsLastName(" *; *",
											" *, *",
											1,
											$row['author']);

		if ($row['author_count'] == "1") // one author, like: "Ambrose 1991 {3735}"
			if ($yearWithBrackets)
				$record .= " (" . $row['year'] . ") " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;
			else
				$record .= " " . $row['year'] . " " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;


		elseif ($row['author_count'] == "2") // two authors, like "Ambrose & Renaud 1995 {3243}"
		{
			$record .= $authorConnector;

			// Call the 'extractAuthorsLastName()' function (defined in 'include.inc.php') extract the last name of a particular author (specified by position). Required Parameters:
			//   1. pattern describing delimiter that separates different authors
			//   2. pattern describing delimiter that separates author name & initials (within one author)
			//   3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name)
			//   4. contents of the author field
			$record .= extractAuthorsLastName(" *; *",
												" *, *",
												2,
												$row['author']);

			if ($yearWithBrackets)
				$record .= " (" . $row['year'] . ") " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;
			else
				$record .= " " . $row['year'] . " " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;
		}

		elseif ($row['author_count'] == "3") // three or more authors, like "Ambrose et al. 2001 {4774}"
		{
			$record .= " ";

			if ($etalPrintItalic)
				$record .= "<i>";

			$record .= "et al";

			if ($etalWithDot)
				$record .= ".";

			if ($etalPrintItalic)
				$record .= "</i>";

			if ($yearWithBrackets)
				$record .= " (" . $row['year'] . ") " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;
			else
				$record .= " " . $row['year'] . " " . $recordIDStartDelimiter . $row['serial'] . $recordIDEndDelimiter;
		}


		return $record;
	}

	// --- END CITATION STYLE ---

