<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./cite/cite_AnnGlaciol_JGlaciol.php
	// Created:    07-Sep-05, 14:53
	// Modified:   10-Oct-05, 17:28

	// This is a citation style file (which must reside within the 'cite/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecord()' function that outputs a reference list from selected records according to the citation style used by
	// the journals "Annals of Glaciology" and "Journal of Glaciology" (International Glaciological Society, www.igsoc.org).

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
						//   2. for all authors except the last author: new delimiter that separates different authors
						//   3. for the last author: new delimiter that separates the last author from all other authors
						//   4. pattern describing old delimiter that separates author name & initials (within one author)
						//   5. for the first author: new delimiter that separates author name & initials (within one author)
						//   6. for all authors except the first author: new delimiter that separates author name & initials (within one author)
						//   7. new delimiter that separates multiple initials (within one author)
						//   8. for the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   9. for all authors except the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//  10. contents of the author field
						$author = reArrangeAuthorContents(" *; *", // 1.
															", ", // 2.
															" and ", // 3.
															" *, *", // 4.
															", ", // 5.
															" ", // 6.
															".", // 7.
															false, // 8.
															true, // 9.
															$row['author']); // 10.

						if (!ereg("\. *$", $author))
							$record .= $author . ".";
						else
							$record .= $author;
					}

				if (!empty($row['year']))				// year
					{
						if (!empty($row['author']))
							$record .= " ";

						$record .= $row['year'] . ".";
					}

				if (!empty($row['title']))			// title
					{
						if (!empty($row['author']) || !empty($row['year']))
							$record .= " ";

						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
					}

				// From here on we'll assume that at least one of the fields 'author', 'year' or 'title' did contain some contents
				// if this is not the case, the output string will begin with a space. However, any preceding/trailing whitespace will be removed at the cleanup stage (see below)

				if (!empty($row['abbrev_journal']))		// abbreviated journal name
					$record .= " <i>" . $row['abbrev_journal'] . "</i>";

				// if there's no abbreviated journal name, we'll use the full journal name
				elseif (!empty($row['publication']))	// publication (= journal) name
					$record .= " <i>" . $row['publication'] . "</i>";

				if (!empty($row['volume']))			// volume
					{
						if (!empty($row['abbrev_journal']) || !empty($row['publication']))
							$record .= ",";

						$record .= " <b>" . $row['volume'] . "</b>";
					}

				if (!empty($row['issue']))			// issue
					$record .= "(" . $row['issue'] . ")";

				if ($row['online_publication'] == "yes") // this record refers to an online article
				{
					// instead of any pages info (which normally doesn't exist for online publications) we append
					// an optional string (given in 'online_citation') plus the DOI:
					// (NOTE: I'm not really sure how to format an online publication for this cite style)

					if (!empty($row['online_citation']))			// online_citation
					{
						if (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication']))		// only add "," if either volume, issue, abbrev_journal or publication isn't empty
							$record .= ",";

						$record .= " " . $row['online_citation'];
					}

					if (!empty($row['doi']))			// doi
					{
						if (!empty($row['online_citation']) OR (empty($row['online_citation']) AND (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication']))))		// only add "," if online_citation isn't empty, or else if either volume, issue, abbrev_journal or publication isn't empty
							$record .= ",";

						$record .= " doi:" . $row['doi'];
					}
				}
				else // $row['online_publication'] == "no" -> this record refers to a printed article, so we append any pages info instead:
				{
					if (!empty($row['pages']))			// pages
						{
							if (!empty($row['volume']) || !empty($row['issue']) || !empty($row['abbrev_journal']) || !empty($row['publication']))		// only add "," if either volume, issue, abbrev_journal or publication isn't empty
								$record .= ",";

							if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
								$record .= " " . (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
							else
								$record .= " " . $row['pages'];
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
						//   2. for all authors except the last author: new delimiter that separates different authors
						//   3. for the last author: new delimiter that separates the last author from all other authors
						//   4. pattern describing old delimiter that separates author name & initials (within one author)
						//   5. for the first author: new delimiter that separates author name & initials (within one author)
						//   6. for all authors except the first author: new delimiter that separates author name & initials (within one author)
						//   7. new delimiter that separates multiple initials (within one author)
						//   8. for the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   9. for all authors except the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//  10. contents of the author field
						$author = reArrangeAuthorContents(" *; *", // 1.
															", ", // 2.
															" and ", // 3.
															" *, *", // 4.
															", ", // 5.
															" ", // 6.
															".", // 7.
															false, // 8.
															true, // 9.
															$row['author']); // 10.

						if (!ereg("\. *$", $author))
							$record .= $author . ".";
						else
							$record .= $author;
					}

				if (!empty($row['year']))				// year
					{
						if (!empty($row['author']))
							$record .= " ";

						$record .= $row['year'] . ".";
					}

				if (!empty($row['title']))			// title
					{
						if (!empty($row['author']) || !empty($row['year']))
							$record .= " ";

						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
					}

				// From here on we'll assume that at least one of the fields 'author', 'year' or 'title' did contain some contents
				// if this is not the case, the output string will begin with a space. However, any preceding/trailing whitespace will be removed at the cleanup stage (see below)

				if (!empty($row['editor']))			// editor
					{
						// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
						//   1. pattern describing old delimiter that separates different authors
						//   2. for all authors except the last author: new delimiter that separates different authors
						//   3. for the last author: new delimiter that separates the last author from all other authors
						//   4. pattern describing old delimiter that separates author name & initials (within one author)
						//   5. for the first author: new delimiter that separates author name & initials (within one author)
						//   6. for all authors except the first author: new delimiter that separates author name & initials (within one author)
						//   7. new delimiter that separates multiple initials (within one author)
						//   8. for the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   9. for all authors except the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//  10. contents of the author field
						$editor = reArrangeAuthorContents(" *; *", // 1.
															", ", // 2.
															" and ", // 3.
															" *, *", // 4.
															", ", // 5.
															" ", // 6.
															".", // 7.
															false, // 8.
															true, // 9.
															$row['editor']); // 10.

						$record .= " <i>In</i> " . $editor;
						if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
							$record .= ", <i>eds</i>.";
						else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
							$record .= ", <i>ed</i>.";
					}

				$publication = ereg_replace("[ \r\n]*\(Eds?:[^\)\r\n]*\)", "", $row['publication']);
				if (!empty($publication))			// publication
					$record .= " <i>" . $publication . "</i>.";

				if (!empty($row['place']))			// place
					$record .= " " . $row['place'];

				if (!empty($row['publisher']))		// publisher
					{
						if (!empty($row['place']))
							$record .= ",";

						$record .= " " . $row['publisher'];
					}

				if (!empty($row['pages']))			// pages
					{
						if (!empty($row['place']) || !empty($row['publisher']))
							$record .= ",";

						if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
							$record .= " " . (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages'])); // replace hyphen with em dash
						else
							$record .= " " . $row['pages'];
					}

				if (!ereg("\. *$", $record))
					$record .= ".";

				if (!empty($row['abbrev_series_title']) OR !empty($row['series_title'])) // if there's either a full or an abbreviated series title
					{
						$record .= " (";

						if (!empty($row['abbrev_series_title']))
							$record .= $row['abbrev_series_title'];	// abbreviated series title

						// if there's no abbreviated series title, we'll use the full series title instead:
						elseif (!empty($row['series_title']))
							$record .= $row['series_title'];	// full series title

						if (!empty($row['series_volume'])||!empty($row['series_issue']))
							$record .= " ";

						if (!empty($row['series_volume']))	// series volume
							$record .= $row['series_volume'];

						if (!empty($row['series_issue']))	// series issue (I'm not really sure if -- for this cite style -- the series issue should be rather ommitted here)
							$record .= "(" . $row['series_issue'] . ")";

						$record .= ".)";
					}
			}

		// --- BEGIN TYPE = BOOK WHOLE / MAP / MANUSCRIPT / JOURNAL ----------------------------------------------------------------------------

		elseif (ereg("Book Whole|Map|Manuscript|Journal", $row['type']))
			{
				if (!empty($row['author']))			// author
					{
						$author = ereg_replace("[ \r\n]*\(eds?\)", "", $row['author']);

						// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
						//   1. pattern describing old delimiter that separates different authors
						//   2. for all authors except the last author: new delimiter that separates different authors
						//   3. for the last author: new delimiter that separates the last author from all other authors
						//   4. pattern describing old delimiter that separates author name & initials (within one author)
						//   5. for the first author: new delimiter that separates author name & initials (within one author)
						//   6. for all authors except the first author: new delimiter that separates author name & initials (within one author)
						//   7. new delimiter that separates multiple initials (within one author)
						//   8. for the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   9. for all authors except the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//  10. contents of the author field
						$author = reArrangeAuthorContents(" *; *", // 1.
															", ", // 2.
															" and ", // 3.
															" *, *", // 4.
															", ", // 5.
															" ", // 6.
															".", // 7.
															false, // 8.
															true, // 9.
															$author); // 10.

						// if the author is actually the editor of the resource we'll append ', ed' (or ', eds') to the author string:
						// [to distinguish editors from authors in the 'author' field, the 'modify.php' script does append ', ed' (or ', eds') if appropriate,
						//  so we're just checking for these identifier strings here. Alternatively, we could check whether the editor field matches the author field]
						if (ereg("[ \r\n]*\(ed\)", $row['author'])) // single editor
							$author = $author . ", <i>ed</i>";
						elseif (ereg("[ \r\n]*\(eds\)", $row['author'])) // multiple editors
							$author = $author . ", <i>eds</i>";

						if (!ereg("\. *$", $author))
							$record .= $author . ".";
						else
							$record .= $author;
					}

				if (!empty($row['year']))				// year
					{
						if (!empty($row['author']))
							$record .= " ";

						$record .= $row['year'] . ".";
					}

				if (!empty($row['title']))			// title
					{
						if (!empty($row['author']) || !empty($row['year']))
							$record .= " ";

						$record .= "<i>" . $row['title'] . "</i>";
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
					}

				if (!empty($row['thesis']))			// thesis
					{
						$record .= " (" . $row['thesis'];
						$record .= ", " . $row['publisher'] . ".)";
					}
				else  // not a thesis
					{
						if (!empty($row['place']))			// place
							$record .= " " . $row['place'];

						if (!empty($row['publisher']))		// publisher
							{
								if (!empty($row['place']))
									$record .= ",";

								$record .= " " . $row['publisher'];
							}

//						if (!empty($row['pages']))			// pages
//							{
//								if (!empty($row['place']) || !empty($row['publisher']))
//									$record .= ",";
//		
//								if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
//									$record .= " " . (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages'])); // replace hyphen with em dash
//								else
//									$record .= " " . $row['pages'];
//							}

						if (!ereg("\. *$", $record))
							$record .= ".";
					}

				if (!empty($row['abbrev_series_title']) OR !empty($row['series_title'])) // if there's either a full or an abbreviated series title
					{
						$record .= " (";

						if (!empty($row['abbrev_series_title']))
							$record .= $row['abbrev_series_title'];	// abbreviated series title

						// if there's no abbreviated series title, we'll use the full series title instead:
						elseif (!empty($row['series_title']))
							$record .= $row['series_title'];	// full series title

						if (!empty($row['series_volume'])||!empty($row['series_issue']))
							$record .= " ";

						if (!empty($row['series_volume']))	// series volume
							$record .= $row['series_volume'];

						if (!empty($row['series_issue']))	// series issue (I'm not really sure if -- for this cite style -- the series issue should be rather ommitted here)
							$record .= "(" . $row['series_issue'] . ")";

						$record .= ".)";
					}
			}

		// --- BEGIN POST-PROCESSING -----------------------------------------------------------------------------------------------------------

		// do some further cleanup:
		$record = trim($record); // remove any preceding or trailing whitespace


		return $record;
	}

	// --- END CITATION STYLE ---

