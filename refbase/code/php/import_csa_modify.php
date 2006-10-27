<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./import_csa_modify.php
	// Created:    21-Nov-03, 22:46
	// Modified:   24-Nov-03, 02:24

	// This php script accepts input from 'import_csa.php'. It will process the CSA full record data
	// and call 'record.php' with all provided fields pre-filled. The user can then verify the data,
	// add or modify any details as necessary and add the record to the database.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password
	include 'include.inc'; // include common functions
	include "ini.inc.php"; // include common variables

	// just for DEBUGGING purposes:
//		include 'header.inc'; // include header
//		include 'footer.inc'; // include footer

	// --------------------------------------------------------------------

	// Initialize the session
	session_start();

	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!

	// Register an error array - just in case!
	if (!session_is_registered("errors"))
		session_register("errors");
	
	// Clear any errors that might have been found previously:
	$errors = array();
	
	// --------------------------------------------------------------------

	// First of all, check if the user is logged in:
	if (!session_is_registered("loginEmail")) // -> if the user isn't logged in
	{
		header("Location: user_login.php?referer=" . rawurlencode($HTTP_REFERER)); // ask the user to login first, then he'll get directed back to 'import_csa.php'

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// Get the form used by the user:
	$formType = $_REQUEST['formType'];

	// Get the source text containing the CSA record(s):
	$sourceText = $_REQUEST['sourceText'];

	// Check whether we're supposed to display the original source data:
	$showSource = $_REQUEST['showSource'];

	// Remove any text preceeding the record(s):
	$sourceText = preg_replace("/.*(?=Record 1 of \d+)/s", "", $sourceText);

	// Check the correct parameters have been passed:
	// We assume valid data input if the '$sourceText' variable is not empty and if it does contain
	// at least the following three field identifiers: "TI: Title", "SO: Source", "AU: Author" (only exception: for book monographs we accept "ED: Editor" instead of "AU: Author")
	// In addition, each of these field identifiers must be followed by a return and/or newline and four spaces!
	$rejectReasonsArray = array();

	if (empty($sourceText))
		$rejectReasonsArray[] = "empty form";
	
	if (!preg_match("/^TI: Title *[\r\n]+ {4,4}/m", $sourceText))
		$rejectReasonsArray[] = "empty title";

	if (!preg_match("/^SO: Source *[\r\n]+ {4,4}/m", $sourceText))
		$rejectReasonsArray[] = "empty source";

	if (!preg_match("/^AU: Author *[\r\n]+ {4,4}/m", $sourceText) AND !preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $sourceText))
			$rejectReasonsArray[] = "empty author";

	if (!preg_match("/^AU: Author *[\r\n]+ {4,4}/m", $sourceText) AND !preg_match("/^ED: Editor *[\r\n]+ {4,4}/m", $sourceText) AND preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $sourceText))
		$rejectReasonsArray[] = "empty author";

	if (!empty($rejectReasonsArray)) // if any of the required fields is missing
	{
		session_register("HeaderString"); // save an error message
		$HeaderString = "<b><span class=\"warning\">Incorrect input format!</span></b> Please use the CSA 'full record' data format.";

		// Redirect the browser back to the CSA import form:
		header("Location: import_csa.php"); // Note: if 'header("Location: $HTTP_REFERER")' is used, the error message won't get displayed! ?:-/
		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}


	// Split input text on the header text preceeding each CSA record (e.g. "\nRecord 4 of 52\n"):
	$recordArray = preg_split("/\s*Record \d+ of \d+\s*/", $sourceText);

	// --------------------------------------------------------------------

	// just for DEBUGGING purposes:
		// If there's no stored message available:
//		if (!session_is_registered("HeaderString"))
//			$HeaderString = "Data extracted from CSA full record(s):"; // Provide the default message
//		else
//			session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'
		// Show the login status:
//		showLogin(); // (function 'showLogin()' is defined in 'include.inc')
	
		// (2a) Display header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
//		displayHTMLhead(htmlentities($officialDatabaseName) . " -- Add Literature from CSA Record Data", "index,follow", "Extract literature data from Cambridge Scientific Abstracts and add the data to the " . htmlentities($officialDatabaseName), "", false, "");
//		showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

	// just for DEBUGGING purposes:
//		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds data extracted from your CSA record\">";


	// LOOP OVER EACH RECORD:
	foreach ($recordArray as $singleRecord) // for each record...
	{
		// Again, we assume a single record as valid if the '$singleRecord' variable is not empty and if it does contain
		// at least the following three field identifiers: "TI: Title", "SO: Source", "AU: Author" (only exception: for book monographs we accept "ED: Editor" instead of "AU: Author")
		// In addition, each of these field identifiers must be followed by a return and/or newline and four spaces!
		if (!empty($singleRecord) AND preg_match("/^TI: Title *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/^SO: Source *[\r\n]+ {4,4}/m", $singleRecord) AND (preg_match("/^AU: Author *[\r\n]+ {4,4}/m", $singleRecord) OR (preg_match("/^ED: Editor *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord))))
		{
			// if the "AU: Author" field is missing BUT the "ED: Editor" is present (which is allowed for book monographs, see above):
			// we replace the "ED: Editor" field identifier with "AU: Author" (this will keep any " (ed)" and " (eds)" tags in place which, in turn, will cause the "is Editor" checkbox in 'record.php' to get marked)
			if (!preg_match("/^AU: Author *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/^ED: Editor *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord))
				$singleRecord = preg_replace("/^ED: Editor(?= *[\r\n]+ {4,4})/m", "AU: Author", $singleRecord);

			$fieldArray = preg_split("/[\r\n]+(?=\w\w: )/", $singleRecord); // split each record into its fields

			// initialize some variables:
			$fieldParametersArray = array(); // setup an empty array (it will hold the parameters that get passed to 'record.php')
			$additionalDocumentTypeInfo = ""; // will be used with the "PT: Publication Type" field
			$environmentalRegime = ""; // will be used with the "ER: Environmental Regime" field


			// GENERATE EXTRA FIELDS:
			// check if the fields "MT: Monograph Title", "JN: Journal Name", "JV: Journal Volume", "JI: Journal Issue" and "JP: Journal Pages" are present,
			// if not, we attempt to generate them from the "SO: Source" field:
			$sourceField = preg_replace("/.*SO: Source *[\r\n]+ {4,4}(.+?)(?=([\r\n]+\w\w: |\s*\z)).*/ms", "\\1", $singleRecord); // first, we need to extract the "SO: Source" field data from the record text
			$sourceField = preg_replace("/\s{2,}/", " ", $sourceField); // remove any hard returns and extra spaces within the source field data string

			// if the current record is of type "Book Monograph" but the field "MT: Monograph Title" is missing:
			if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord) AND !preg_match("/^MT: Monograph Title *[\r\n]+ {4,4}/m", $singleRecord))
			{
				$extractedSourceFieldData = preg_replace("/^([^.[]+).*/", "\\1", $sourceField); // attempt to extract the full monograph title from the source field

				if (preg_match("/^[[:upper:]\W\d]+$/", $extractedSourceFieldData)) // if all of the words within the monograph title are uppercase, we attempt to convert the string to something more readable:
					$extractedSourceFieldData = ucwords(strtolower($extractedSourceFieldData)); // perform case transformation (e.g. convert "BIOLOGY AND ECOLOGY OF GLACIAL RELICT CRUSTACEA" into "Biology And Ecology Of Glacial Relict Crustacea")

				$fieldArray[] = "MT: Monograph Title\r\n    " . $extractedSourceFieldData; // add field "MT: Monograph Title" to the array of fields
			}
			// else if the current record is of type "Journal Article", "Report", etc (or wasn't specified) but the field "JN: Journal Name" is missing:
			elseif (!preg_match("/^JN: Journal Name *[\r\n]+ {4,4}/m", $singleRecord)) // preg_match("/^(PT: Publication Type\s+(Journal Article|Report)|DT: Document Type\s+(J|R))/m", $singleRecord)
			{
				if (ereg("\[", $sourceField)) // if the source field data contain a square bracket we assume a format like: "Journal of Phycology [J. Phycol.]. Vol. 37, no. s3, pp. 18-18. Jun 2001."
					$extractedSourceFieldData = preg_replace("/^([^.[]+).*/", "\\1", $sourceField); // attempt to extract the full journal name from the source field
				else // source field format might be something like: "Phycologia, vol. 34, no. 2, pp. 135-144, 1995"
					$extractedSourceFieldData = preg_replace("/^([^.,]+).*/", "\\1", $sourceField); // attempt to extract the full journal name from the source field

				if (preg_match("/^[[:upper:]\W\d]+$/", $extractedSourceFieldData)) // if all of the words within the journal name are uppercase, we attempt to convert the string to something more readable:
					$extractedSourceFieldData = ucwords(strtolower($extractedSourceFieldData)); // perform case transformation (e.g. convert "POLAR BIOLOGY" into "Polar Biology")

				$fieldArray[] = "JN: Journal Name\r\n    " . $extractedSourceFieldData; // add field "JN: Journal Name" to the array of fields
			}

			// if the "JV: Journal Volume" is missing BUT the "SO: Source" field contains a volume specification:
			if (!preg_match("/^JV: Journal Volume *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/(?<=\W)vol[. ]+[\w\/-]+/i", $sourceField))
			{
				$extractedSourceFieldData = preg_replace("/.*(?<=\W)vol[. ]+([\w\/-]+).*/i", "\\1", $sourceField); // attempt to extract the journal volume from the source field

				$fieldArray[] = "JV: Journal Volume\r\n    " . $extractedSourceFieldData; // add field "JV: Journal Volume" to the array of fields
			}

			// if the "JI: Journal Issue" is missing BUT the "SO: Source" field contains an issue specification:
			if (!preg_match("/^JI: Journal Issue *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/(?<=\W)no[. ]+[\w\/-]+/i", $sourceField))
			{
				$extractedSourceFieldData = preg_replace("/.*(?<=\W)no[. ]+([\w\/-]+).*/i", "\\1", $sourceField); // attempt to extract the journal issue from the source field

				$fieldArray[] = "JI: Journal Issue\r\n    " . $extractedSourceFieldData; // add field "JI: Journal Issue" to the array of fields
			}

			// if the "JP: Journal Pages" is missing BUT the "SO: Source" field contains a pages specification:
			if (!preg_match("/^JP: Journal Pages *[\r\n]+ {4,4}/m", $singleRecord) AND preg_match("/((?<=\W)pp?[. ]+[\w\/,-]+|[\d,]+ *pp\b)/i", $sourceField))
			{
				if (preg_match("/(?<=\W)pp?[. ]+[\w\/,-]+/i", $sourceField)) // e.g. "pp. 212-217" or "p. 216" etc
					$extractedSourceFieldData = preg_replace("/.*(?<=\W)pp?[. ]+([\w\/,-]+).*/i", "\\1", $sourceField); // attempt to extract the journal pages from the source field
				elseif (preg_match("/[\d,]+ *pp\b/", $sourceField)) // e.g. "452 pp"
					$extractedSourceFieldData = preg_replace("/.*?([\d,]+ *pp)\b.*/i", "\\1", $sourceField); // attempt to extract the journal pages from the source field

				$extractedSourceFieldData = preg_replace("/,/", "", $extractedSourceFieldData); // remove any thousands separators from journal pages

				$fieldArray[] = "JP: Journal Pages\r\n    " . $extractedSourceFieldData; // add field "JP: Journal Pages" to the array of fields
			}


			// Additionally, we extract the abbreviated journal name from the "SO: Source" field (if available):
			if (ereg("\[", $sourceField)) // if the source field data contain a square bracket we assume a format like: "Journal of Phycology [J. Phycol.]. Vol. 37, no. s3, pp. 18-18. Jun 2001."
			{
				$extractedSourceFieldData = preg_replace("/.*\[(.+?)\].*/", "\\1", $sourceField); // attempt to extract the abbreviated journal name from the source field
				$extractedSourceFieldData = preg_replace("/\./", "", $extractedSourceFieldData); // remove any dots from the abbreviated journal name

				if (preg_match("/^[[:upper:]\W\d]+$/", $extractedSourceFieldData)) // if all of the words within the abbreviated journal name are uppercase, we attempt to convert the string to something more readable:
					$extractedSourceFieldData = ucwords(strtolower($extractedSourceFieldData)); // perform case transformation (e.g. convert "BALT SEA ENVIRON PROC" into "Balt Sea Environ Proc")

				$fieldArray[] = "JA: Abbrev Journal Name\r\n    " . $extractedSourceFieldData; // add field "JA: Abbrev Journal Name" to the array of fields (note that this field normally does NOT occur within the CSA full record format!)
			}
			// (END GENERATE EXTRA FIELDS)


			// LOOP OVER EACH FIELD:
			foreach ($fieldArray as $singleField) // for each field within the current record...
			{
				$singleField = preg_replace("/^(\w\w: [^\r\n]+)[\r\n]+ {4,4}/", "\\1___LabelDataSplitter___", $singleField); // insert a unique text string between the field identifier and the field data
				$fieldLabelPlusDataArray = preg_split("/___LabelDataSplitter___/", $singleField); // split each field into a 2-element array containing [0] the field identifier and [1] the field data

				$fieldLabelPlusDataArray[1] = preg_replace("/\s{2,}/", " ", $fieldLabelPlusDataArray[1]); // remove any hard returns and extra spaces within the data string
				$fieldLabelPlusDataArray[1] = trim($fieldLabelPlusDataArray[1]); // remove any preseeding and trailing whitespace from the field data

				if (ereg("AU: Author", $fieldLabelPlusDataArray[0]))
					$fieldLabelPlusDataArray[1] = preg_replace("/\*/", "", $fieldLabelPlusDataArray[1]); // remove any asterisk ("*")

				elseif (ereg("ED: Editor", $fieldLabelPlusDataArray[0]))
					$fieldLabelPlusDataArray[1] = preg_replace("/ \(eds?\)(?= *$| *;)/", "", $fieldLabelPlusDataArray[1]); // remove " (ed)" and/or " (eds)"

				elseif (ereg("TI: Title|AB: Abstract", $fieldLabelPlusDataArray[0]))
				{
					if (ereg("TI: Title", $fieldLabelPlusDataArray[0]))
					{
						$fieldLabelPlusDataArray[1] = preg_replace("/--/", "-", $fieldLabelPlusDataArray[1]); // remove en-dash markup
						$fieldLabelPlusDataArray[1] = preg_replace("/ *\. *$/", "", $fieldLabelPlusDataArray[1]); // remove any dot from end of title
					}

					if (preg_match("/ su(b|per)\(.+?\)/", $fieldLabelPlusDataArray[1]))
						$fieldLabelPlusDataArray[1] = preg_replace("/ (su(?:b|per))\((.+?)\)/", "[\\1:\\2]", $fieldLabelPlusDataArray[1]); // transform " sub(...)" & " super(...)" markup into "[sub:...]" & "[super:...]" markup
					if (preg_match("/(?<= )mu /", $fieldLabelPlusDataArray[1]))
						$fieldLabelPlusDataArray[1] = preg_replace("/(?<= )mu /", "µ", $fieldLabelPlusDataArray[1]); // transform "mu " markup into "µ" markup
				}

				// just for DEBUGGING purposes:
//					echo "\n<tr>\n\t<td valign=\"top\" width=\"230\"><b>" . $fieldLabelPlusDataArray[0] . "</b></td><td valign=\"top\">" . $fieldLabelPlusDataArray[1] . "</td>\n</tr>";


				// BUILD URL PARAMETERS:
				// build a list of key/value pairs that will be passed as parameters to 'record.php':

				// "AU: Author":
				if (ereg("AU: Author", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "author=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "TI: Title":
				elseif (ereg("TI: Title", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "title=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "PT: Publication Type":
				elseif (ereg("PT: Publication Type", $fieldLabelPlusDataArray[0])) // could also check for "DT: Document Type" (but DT was added only recently)
				{
					if (ereg("[;:,.]", $fieldLabelPlusDataArray[1])) // if the "PT: Publication Type" field contains a delimiter (e.g. like: "Journal Article; Conference")
					{
						$correctDocumentType = preg_replace("/(.+?)\s*[;:,.]\s*.*/", "\\1", $fieldLabelPlusDataArray[1]); // extract everything before this delimiter
						$additionalDocumentTypeInfo = preg_replace("/.*?\s*[;:,.]\s*(.+)/", "\\1", $fieldLabelPlusDataArray[1]); // extract everything after this delimiter
						$additionalDocumentTypeInfo = rawurlencode($additionalDocumentTypeInfo); // this info will be appended to any notes field data (see below)
					}
					else // we take the "PT: Publication Type" field contents as they are
						$correctDocumentType = $fieldLabelPlusDataArray[1];

					// Note that for books the "PT: Publication Type" field will always start with "Book Monograph", no matter whether the referenced
					// publication is a whole book or just a book chapter within that book! This is a design flaw within the CSA full record format.
					// So we can only apply some "good guessing" whether the current record actually references a complete book or just a book chapter:
					if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
					{
						// and if the source field contains some page specification like "213 pp." (AND NOT something like "pp. 76-82" or "p. 216")...
						if (preg_match("/[\d,]+ *pp\b/i", $sourceField) AND !preg_match("/(?<=\W)pp?[. ]+[\w\/,-]+/i", $sourceField))
							$correctDocumentType = "Book Whole"; // ...we assume its a whole book
						else
							$correctDocumentType = "Book Chapter"; // ...otherwise we assume its a book chapter (which may NOT always be correct!)
					}

					$fieldParametersArray[] = "type=" . rawurlencode($correctDocumentType);
				}

				// "PY: Publication Year":
				elseif (ereg("PY: Publication Year", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "year=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "JN: Journal Name":
				elseif (ereg("JN: Journal Name", $fieldLabelPlusDataArray[0]))
				{
					// if the current record is of type "Book Monograph" AND the field "JN: Journal Name" was given within the *original* record data (i.e., before adding stuff to it):
					if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord) AND preg_match("/^JN: Journal Name *[\r\n]+ {4,4}/m", $singleRecord))
						// for book monographs the publication title is given in "MT: Monograph Title"; if a "JN: Journal Name" was originally provided as well, we assume, it's the series title:
						$fieldParametersArray[] = "series_title=" . rawurlencode($fieldLabelPlusDataArray[1]);
					else
						$fieldParametersArray[] = "publication=" . rawurlencode($fieldLabelPlusDataArray[1]);
				}

				// "JA: Abbrev Journal Name":
				elseif (ereg("JA: Abbrev Journal Name", $fieldLabelPlusDataArray[0]))
				{
					if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
						// for book monographs the publication title is given in "MT: Monograph Title"; if a "JA: Abbrev Journal Name" is provided as well, we assume, it's the abbreviated series title:
						$fieldParametersArray[] = "abbrev_series_title=" . rawurlencode($fieldLabelPlusDataArray[1]);
					else
						$fieldParametersArray[] = "abbrev_journal=" . rawurlencode($fieldLabelPlusDataArray[1]);
				}

				// "MT: Monograph Title":
				elseif (ereg("MT: Monograph Title", $fieldLabelPlusDataArray[0]))
				{
					// if the source field contains some page specification like "213 pp." (AND NOT something like "pp. 76-82" or "p. 216")...
					if (preg_match("/[\d,]+ *pp\b/i", $sourceField) AND !preg_match("/(?<=\W)pp?[. ]+[\w\/,-]+/i", $sourceField))
						// ...we assume its a whole book (see above comment), in which case we assign the monograph title to the series title field:
						$fieldParametersArray[] = "series_title=" . rawurlencode($fieldLabelPlusDataArray[1]);
					else
						$fieldParametersArray[] = "publication=" . rawurlencode($fieldLabelPlusDataArray[1]);
				}

				// "JV: Journal Volume":
				elseif (ereg("JV: Journal Volume", $fieldLabelPlusDataArray[0]))
				{
					if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
						// for book monographs, if there's a volume given, we assume, it's the series volume:
						$fieldParametersArray[] = "series_volume=" . rawurlencode($fieldLabelPlusDataArray[1]);
					else
						$fieldParametersArray[] = "volume=" . rawurlencode($fieldLabelPlusDataArray[1]);
				}

				// "JI: Journal Issue":
				elseif (ereg("JI: Journal Issue", $fieldLabelPlusDataArray[0]))
				{
					if (preg_match("/^(PT: Publication Type\s+Book Monograph|DT: Document Type\s+B)/m", $singleRecord)) // if the current record is of type "Book Monograph"
						// for book monographs, if there's an issue given, we assume, it's the series issue:
						$fieldParametersArray[] = "series_issue=" . rawurlencode($fieldLabelPlusDataArray[1]);
					else
						$fieldParametersArray[] = "issue=" . rawurlencode($fieldLabelPlusDataArray[1]);
				}

				// "JP: Journal Pages":
				elseif (ereg("JP: Journal Pages", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "pages=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "AF: Affiliation" & "AF: Author Affilition":
				elseif (ereg("AF: (Author )?Affilia?tion", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "address=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "CA: Corporate Author":
				elseif (ereg("CA: Corporate Author", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "corporate_author=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "DE: Descriptors":
				elseif (ereg("DE: Descriptors", $fieldLabelPlusDataArray[0])) // currently, the fields "KW: Keywords" and "ID: Identifiers" are ignored!
					$fieldParametersArray[] = "keywords=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "AB: Abstract":
				elseif (ereg("AB: Abstract", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "abstract=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "PB: Publisher":
				elseif (ereg("PB: Publisher", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "publisher=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "ED: Editor":
				elseif (ereg("ED: Editor", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "editor=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "LA: Language":
				elseif (ereg("LA: Language", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "language=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "SL: Summary Language":
				elseif (ereg("SL: Summary Language", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "summary_language=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "OT: Original Title":
				elseif (ereg("OT: Original Title", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "orig_title=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "IS: ISSN":
				elseif (ereg("IS: ISSN", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "issn=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "IB: ISBN":
				elseif (ereg("IB: ISBN", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "isbn=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "ER: Environmental Regime":
				elseif (ereg("ER: Environmental Regime", $fieldLabelPlusDataArray[0]))
					$environmentalRegime = rawurlencode($fieldLabelPlusDataArray[1]); // this info will be appended to any notes field data (see below)

				// "CF: Conference":
				elseif (ereg("CF: Conference", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "conference=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "NT: Notes":
				elseif (ereg("NT: Notes", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "notes=" . rawurlencode($fieldLabelPlusDataArray[1]);

				// "DO: DOI":
				elseif (ereg("DO: DOI", $fieldLabelPlusDataArray[0]))
					$fieldParametersArray[] = "doi=" . rawurlencode($fieldLabelPlusDataArray[1]);
			}
			// (END LOOP OVER EACH FIELD)


			// just for DEBUGGING purposes:
//				echo "\n<tr>\n\t<td colspan=\"2\">$sourceField<td>\n</tr>";
//				echo "\n<tr>\n\t<td colspan=\"2\"><hr><td>\n</tr>";


			if (!empty($showSource)) // if we're supposed to display the original source data
				// append original source field data (they will be presented within the header message of 'record.php' for easy comparison with the extracted data):
				$fieldParametersArray[] = "source=" . rawurlencode($sourceField);

			$fieldParameters = implode("&", $fieldParametersArray); // merge list of parameters
			
			// we'll hack the "notes" parameter in order to append additional info:
			// (this cannot be done earlier above since we don't know about the presence & order of fields within the source text!)
			if (!empty($additionalDocumentTypeInfo)) // if the "PT: Publication Type" field contains some additional info
			{
				if (ereg("&notes=", $fieldParameters)) // and if the notes parameter is present
					$fieldParameters = preg_replace("/(?<=&notes=)([^&]+)/", "\\1; $additionalDocumentTypeInfo", $fieldParameters); // append additional info from "PT: Publication Type" field
				else // the notes parameter wasn't specified yet
					$fieldParameters = $fieldParameters . "&notes=" . $additionalDocumentTypeInfo; // add notes parameter with additional info from "PT: Publication Type" field
			}

			if (!empty($environmentalRegime)) // if the "ER: Environmental Regime" field contains some data
			{
				if (ereg("&notes=", $fieldParameters)) // and if the notes parameter is present
					$fieldParameters = preg_replace("/(?<=&notes=)([^&]+)/", "\\1; $environmentalRegime", $fieldParameters); // append "ER: Environmental Regime" field data
				else // the notes parameter wasn't specified yet
					$fieldParameters = $fieldParameters . "&notes=" . $environmentalRegime; // add notes parameter with "ER: Environmental Regime" field data
			}


			// RELOCATE TO IMPORT PAGE:
			// IMPORTANT: currently, the script will just call 'record.php' and load the form fields with the data of the *first* CSA record provided.
			// ALL other records will be skipped! (*batch* import of CSA full records might be enabled in future versions of refbase, but for now only the first record will be imported)
			header("Location: record.php?recordAction=add&mode=import&importSource=csa&" . $fieldParameters);
			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}

	// just for DEBUGGING purposes:
//		echo "\n</table>";

	// just for DEBUGGING purposes:
		// DISPLAY THE HTML FOOTER:
		// call the 'displayfooter()' function from 'footer.inc')
//		displayfooter("");

	// --------------------------------------------------------------------
?>
