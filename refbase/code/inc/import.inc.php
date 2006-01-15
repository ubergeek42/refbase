<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./includes/import.inc.php
	// Created:    13-Jan-06, 21:00
	// Modified:   15-Jan-06, 14:30

	// This file contains functions
	// that are used when importing
	// records.

	// ISITOCSA
	// This function converts records from "ISI Web of Science" format to "CSA" format
	// in order to enable import of ISI WoS records via the import form provided by 'import_csa.php'.
	// ISI WoS records must start with "FN ISI Export Format\nVR x.x\n" (with 'x' being a number)
	function IsiToCsa($isiSourceData)
	{	
		// Generate an array which lists all the CSA tags that are recognized by 'import_csa.php'
		// and match them with their corresponding ISI tags ("CSA tag" => "ISI tag"):
		$isiToCsaTagsArray = array(
									"PT: Publication Type"     => "PT",
									"AU: Author"               => "AU",
									"TI: Title"                => "TI",
									"SO: Source"               => "SO",
									"PY: Publication Year"     => "PY",
		//							"JN: Journal Name"         => "", // the 'import_csa.php' script will generate the full journal name from "SO: Source"
									"JA: Abbrev Journal Name"  => "JI",
		//							"MT: Monograph Title"      => "", // the ISI WoS database does only contain journal article (AFAIK)
									"JV: Journal Volume"       => "VL",
									"JI: Journal Issue"        => "IS",
		//							"JP: Journal Pages"        => "", // ISI WoS contains separate tags for start page ("BP") and end page ("EP"), we'll compute a "JP: Journal Pages" from these fields below
									"AF: Affiliation"          => "C1", // we'll also grab the email address from the ISI "EM" field and append it to "AF: Affiliation"
		//							"CA: Corporate Author"     => "",
									"DE: Descriptors"          => "DE",
									"AB: Abstract"             => "AB",
									"PB: Publisher"            => "PU",
		//							""                         => "PI", // AFAIK, CSA offers no field for the place of publisher (though it would be nice to import this info as well)
		//							"ED: Editor"               => "",
									"LA: Language"             => "LA",
		//							"SL: Summary Language"     => "",
		//							"OT: Original Title"       => "",
									"IS: ISSN"                 => "SN",
		//							"IB: ISBN"                 => "",
		//							"ER: Environmental Regime" => "",
		//							"CF: Conference"           => "",
									"NT: Notes"                => "UT", // we'll import the ISI record ID to the notes field
		//							"DO: DOI"                  => ""
								);

		// --------------------------------------------------------------------

		// SPLIT INPUT text on the header text preceeding each ISI record ("\nFN ISI Export Format\n"):
		$isiRecordsArray = preg_split("/\s*FN ISI Export Format\s*/", $isiSourceData, -1, PREG_SPLIT_NO_EMPTY); // (the 'PREG_SPLIT_NO_EMPTY' flag causes only non-empty pieces to be returned)
		$recordsCount = count($isiRecordsArray); // count how many records are available

		$csaRecordsArray = array(); // initialize array variable which will hold all records that were converted to CSA format

		// --------------------------------------------------------------------

		// LOOP OVER EACH RECORD:
		for ($i=0; $i<$recordsCount; $i++) // for each record...
		{
			// we ignore any array elements whose text does NOT start with the "VR x.x" tag:
			if (!preg_match("/^VR \d.\d[\r\n]/", $isiRecordsArray[$i]))
			{
				continue; // process next record (if any)
			}
			else // ...process this record:
			{
				$csaRecordFieldsArray = array(); // initialize array variable which will hold all fields that we've converted to CSA format

				// extract first email address from ISI "EM" field:
				if (preg_match("/^EM [^ \r\n]+/m", $isiRecordsArray[$i]))
					$emailAddress = preg_replace("/.*[\r\n]EM ([^ \r\n]+).*/s", "\\1", $isiRecordsArray[$i]);
				else
					$emailAddress = "";

				// extract start page (ISI "BP" field) and end page (ISI "EP" field):
				$pages = array();

				if (preg_match("/^BP [^ \r\n]+/m", $isiRecordsArray[$i]))
					$pages[] = preg_replace("/.*[\r\n]BP (\d+).*/s", "\\1", $isiRecordsArray[$i]);

				if (preg_match("/^EP [^ \r\n]+/m", $isiRecordsArray[$i]))
					$pages[] = preg_replace("/.*[\r\n]EP (\d+).*/s", "\\1", $isiRecordsArray[$i]);

				if (!empty($pages))
					$pageRange = implode("-", $pages);
				// if no start or end page is given, we'll try the ISI "PG" field that indicates the total number of pages:
				elseif (preg_match("/^PG [^ \r\n]+/m", $isiRecordsArray[$i]))
					$pageRange = preg_replace("/.*[\r\n]PG (\d+).*/s", "\\1 pp", $isiRecordsArray[$i]);
				else
					$pageRange = "";

				// split each record into its individual fields:
				$isiRecordFieldsArray = preg_split("/[\r\n]+(?=\w\w *)/", $isiRecordsArray[$i]);

				// LOOP OVER EACH FIELD:
				foreach ($isiRecordFieldsArray as $recordField)
				{
					foreach ($isiToCsaTagsArray as $csaTag => $isiTag) // for each ISI field that we'd like to convert...
					{
						if (preg_match("/^" . $isiTag . " /", $recordField))
						{
							// replace found ISI field identifier tag with the corresponding CSA tag:
							$recordField = preg_replace("/^" . $isiTag . " /", $csaTag . "\n    ", $recordField);

							// add a space to the beginning of any line that starts with only three spaces (instead of four):
							$recordField = preg_replace("/^   (?! )/m", "    ", $recordField);
		
							// convert ISI publication type into CSA format:
							if (preg_match("/^PT: Publication Type[\r\n]    J$/", $recordField))
								$recordField = preg_replace("/J$/", "Journal Article", $recordField);

							// merge multiple authors (that are printed on separate lines) with a semicolon (';') and a space:
							if (preg_match("/^AU: Author[\r\n]/", $recordField))
								$recordField = preg_replace("/(?<!^AU: Author)\s*[\r\n]\s*/m", "; ", $recordField);

							// process address info:
							if (preg_match("/^AF: Affiliation[\r\n]/", $recordField))
							{
								// merge multiple addresses (that are printed on separate lines) with a semicolon (';') and a space:
								$recordField = preg_replace("/(?<!^AF: Affiliation)[[:punct:]]*\s*[\r\n]\s*/m", "; ", $recordField);

								// remove any trailing punctuation:
								$recordField = preg_replace("/[[:punct:]]+$/", "", $recordField);

								// append the first email address from ISI "EM" field to "AF: Affiliation":
								if (!empty($emailAddress))
									$recordField = $recordField . "; Email: " . $emailAddress;
							}

							// normalize case in "PB: Publisher" field (converts e.g. "ELSEVIER SCIENCE BV" into "Elsevier Science Bv"):
							if (preg_match("/^PB: Publisher[\r\n]    .+/", $recordField))
								$recordField = preg_replace("/(^PB: Publisher[\r\n]    )(.+)/e", "'\\1'.ucwords(strtolower('\\2'))", $recordField); // uses the 'e' modifier to execute PHP code in replacement pattern

							// append this field to array of CSA fields:
							$csaRecordFieldsArray[] = $recordField;
						}
					}
				}

				// append "JP: Journal Pages" field with generated page range to array of CSA fields:
				$csaRecordFieldsArray[] = "JP: Journal Pages\n    " . $pageRange;

				// merge CSA fields into a string and prefix it with a CSA record identifier:
				$csaRecord = "Record " . ($i + 1) . " of " . $recordsCount . "\n\n" . implode("\n", $csaRecordFieldsArray);

				// append this record to array of CSA records:
				$csaRecordsArray[] = $csaRecord;
			}
		}

		// return all CSA records merged into a string:
		return implode("\n\n", $csaRecordsArray);
	}

	// --------------------------------------------------------------------
?>