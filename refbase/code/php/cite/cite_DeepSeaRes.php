<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./cite/cite_DeepSeaRes.php
	// Created:    28-Sep-04, 23:36
	// Modified:   13-Oct-04, 22:57

	// This is a citation style file (which must reside within the 'cite/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecord()' function that outputs a reference list from selected records according to the citation style used by
	// the journal "Deep Sea Research".

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// --------------------------------------------------------------------


	// --- BEGIN CITATION STYLE ---

	function citeRecord($row, $citeStyle)
	{
		$record = ""; // make sure that our buffer variable is empty

		// --- BEGIN TYPE = JOURNAL ARTICLE ----------------------------------------------------------------------------------------------------

		if ($row['type'] == "Journal Article")
			{
				if (!empty($row['author']))			// author
					{
						// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
						//   1. pattern describing old delimiter that separates different authors
						//   2. new delimiter that separates different authors
						//   3. pattern describing old delimiter that separates author name & initials (within one author)
						//   4. new delimiter that separates author name & initials (within one author)
						//   5. new delimiter that separates multiple initials (within one author)
						//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   7. contents of the author field
						$author = reArrangeAuthorContents(" *; *",
															", ",
															" *, *",
															", ",
															".",
															false,
															$row['author']);
						$record .= $author . ", ";
					}

				if (!empty($row['year']))				// year
					$record .= "" . $row['year'] . ". ";

				if (!empty($row['title']))			// title
					{
						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
						$record .= " ";
					}

				if (!empty($row['publication']))		// publication
					$record .= $row['publication'] . " ";

				if (!empty($row['volume']))			// volume
					$record .= $row['volume'];

				if (!empty($row['issue']))			// issue
					$record .= " (" . $row['issue'] . ")";

				if ($row['online_publication'] == "yes") // this record refers to an online article
				{
					// instead of any pages info (which normally doesn't exist for online publications) we append
					// an optional string (given in 'online_citation') plus the DOI:

					if (!empty($row['online_citation']))			// online_citation
					{
						if (!empty($row['publication'])||!empty($row['volume'])||!empty($row['issue']))		// only add "," if either publication, volume or issue isn't empty
							$record .= ",";

						$record .= " " . $row['online_citation'];
					}

					if (!empty($row['doi']))			// doi
					{
						if (!empty($row['publication'])||!empty($row['volume'])||!empty($row['issue']))		// only add "," if either publication, volume or issue isn't empty
							$record .= ",";

						$record .= " doi:" . $row['doi'];
					}
				}
				else // $row['online_publication'] == "no" -> this record refers to a printed article, so we append any pages info instead:
				{
					if (!empty($row['pages']))			// pages
						{
							if (!empty($row['volume'])||!empty($row['issue']))		// only add "," if either volume or issue isn't empty
								$record .= ", ";
							if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
								$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
							else
								$pagesDisplay = $row['pages'];
							$record .= $pagesDisplay;
						}
				}
				
				if (!ereg("\. *$", $record))
					$record .= ".";
			}

		// --- BEGIN TYPE = BOOK CHAPTER -------------------------------------------------------------------------------------------------------

		elseif ($row['type'] == "Book Chapter")
			{
				if (!empty($row['author']))			// author
					{
						// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
						//   1. pattern describing old delimiter that separates different authors
						//   2. new delimiter that separates different authors
						//   3. pattern describing old delimiter that separates author name & initials (within one author)
						//   4. new delimiter that separates author name & initials (within one author)
						//   5. new delimiter that separates multiple initials (within one author)
						//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   7. contents of the author field
						$author = reArrangeAuthorContents(" *; *",
															", ",
															" *, *",
															", ",
															".",
															false,
															$row['author']);
						$record .= $author . ", ";
					}

				if (!empty($row['year']))				// year
					$record .= "" . $row['year'] . ". ";

				if (!empty($row['title']))			// title
					{
						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
						$record .= " ";
					}

				if (!empty($row['editor']))			// editor
					{
						// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
						//   1. pattern describing old delimiter that separates different authors
						//   2. new delimiter that separates different authors
						//   3. pattern describing old delimiter that separates author name & initials (within one author)
						//   4. new delimiter that separates author name & initials (within one author)
						//   5. new delimiter that separates multiple initials (within one author)
						//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   7. contents of the author field
						$editor = reArrangeAuthorContents(" *; *",
															", ",
															" *, *",
															", ",
															".",
															false,
															$row['editor']);
						$record .= "In: " . $editor;
						if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
							$record .= " (Eds.)";
						else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
							$record .= " (Ed.)";
					}

				$publication = ereg_replace("[ \r\n]*\(Eds?:[^\)\r\n]*\)", "", $row['publication']);
				if (!empty($publication))			// publication
					$record .= ", " . $publication . ". ";
				else
					if (!empty($row['editor']))
						$record .= ". ";

				if (!empty($row['series_title'])) // if there's a series title, series information will replace the publisher & place information
					{
						$record .= $row['series_title'];	// series title

						if (!empty($row['series_volume'])||!empty($row['series_issue']))
							$record .= " ";

						if (!empty($row['series_volume']))	// series volume
							$record .= $row['series_volume'];

						if (!empty($row['series_issue']))	// series issue
							$record .= "(" . $row['series_issue'] . ")";

						if (!empty($row['pages']))
							$record .= ", ";

					}
				else // if there's NO series title available, we'll insert the publisher & place instead:
					{
						if (!empty($row['publisher']))		// publisher
							{
								$record .= $row['publisher'];
								if (!empty($row['place']))
									$record .= ", ";
								else
									if (!ereg(",$", $row['publisher']))
										$record .= ",";
									$record .= " ";
							}

						if (!empty($row['place']))			// place
							{
								$record .= $row['place'];
								if (!empty($row['pages']))
									{
										if (!ereg(",$", $row['place']))
											$record .= ",";
										$record .= " ";
									}
							}
					}

				if (!empty($row['pages']))			// pages
					{
						if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
							$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
						else
							$pagesDisplay = $row['pages'];
						$record .= "pp. " . $pagesDisplay;
					}
				
				if (!ereg("\. *$", $record))
					$record .= ".";
			}

		// --- BEGIN TYPE = BOOK WHOLE / MAP / MANUSCRIPT / JOURNAL ----------------------------------------------------------------------------

		elseif (ereg("Book Whole|Map|Manuscript|Journal", $row['type']))
			{
				if (!empty($row['author']))			// author
					{
						$author = ereg_replace("[ \r\n]*\(eds?\)", "", $row['author']);

						// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
						//   1. pattern describing old delimiter that separates different authors
						//   2. new delimiter that separates different authors
						//   3. pattern describing old delimiter that separates author name & initials (within one author)
						//   4. new delimiter that separates author name & initials (within one author)
						//   5. new delimiter that separates multiple initials (within one author)
						//   6. boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   7. contents of the author field
						$author = reArrangeAuthorContents(" *; *",
															", ",
															" *, *",
															", ",
															".",
															false,
															$author);
						$record .= $author . ", ";
					}

				if (!empty($row['year']))				// year
					$record .= "" . $row['year'] . ". ";

				if (!empty($row['title']))			// title
					{
						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
						$record .= " ";
					}

				if (!empty($row['thesis']))			// thesis
					$record .= $row['thesis'] . ". ";

				if (!empty($row['publisher']))		// publisher
					{
						$record .= $row['publisher'];
						if (!empty($row['place']))
							$record .= ", ";
						else
							if (!ereg("[?!.]$", $row['publisher']))
								$record .= ". ";
							else
								$record .= " ";
					}

				if (!empty($row['place']))			// place
					{
						$record .= $row['place'];
						if (!empty($row['series_title']) || !empty($row['pages']))
							{
								if (!ereg(",$", $row['place']))
									$record .= ",";
								$record .= " ";
							}
					}

				if (!empty($row['series_title']))	// series title
					{
						$record .= $row['series_title'];

						if (!empty($row['series_volume']))	// series volume (will get appended only if there's also a series title!)
						{
							$record .= " ";
							$record .= $row['series_volume'];
						}

						if (!empty($row['pages']))
							{
								if (!ereg(",$", $row['series_volume']))
									$record .= ",";
								$record .= " ";
							}
					}

				if (!empty($row['pages']))			// pages
					{
						if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
							// Note that we'll check for page ranges here although for whole books the 'pages' field should NOT contain a page range but the total number of pages! (like: "623 pp")
							$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
						else
							$pagesDisplay = $row['pages'];
						$record .= $pagesDisplay;
					}
				
				if (!ereg("\. *$", $record))
					$record .= ".";
			}

		// --- BEGIN POST-PROCESSING -----------------------------------------------------------------------------------------------------------

		// do some further cleanup:
		$record = ereg_replace("[ \r\n]*$", "", $record); // remove whitespace at end of line
		$record = ereg_replace("([0-9]+) *pp\.$", "\\1pp.", $record); // remove space between (book whole) page numbers & "pp"


		return $record;
	}

	// --- END CITATION STYLE ---

