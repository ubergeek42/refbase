<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the function's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/import.inc.php
	// Created:    13-Jan-06, 21:00
	// Modified:   17-Jan-06, 18:57

	// This file contains functions
	// that are used when importing
	// records into the database.

	// ISITOCSA
	// This function converts records from "ISI Web of Science" format to "CSA" format
	// in order to enable import of ISI WoS records via the import form provided by 'import_csa.php'.
	// ISI WoS records must contain at least the tags "PT" and "SO" and end with "\nER\n".
	// 
	// Authors: this function was originally written by Joachim Almergren <joachim.almergren@umb.no>
	//          and was re-written by Matthias Steffens <mailto:refbase@extracts.de> to enable batch import
	function IsiToCsa($isiSourceData)
	{
		// Function preferences:
		$extractAllAddresses = false; // if set to 'true', all addresses will be extracted from the ISI "C1" field;
									// set to 'false' if you only want to extract the first address given in the ISI "C1" field

		$extractEmail = true; // if set to 'true', the first email address will be extracted from the ISI "EM" field and appended to the first address in "AF: Affiliation";
							 // set to 'false' if you don't want to extract the email address

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
									"AF: Affiliation"          => "C1",
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

		// SPLIT INPUT text on the "ER" (= end of record) tag that terminates every ISI record:
		$isiRecordsArray = preg_split("/\s*[\r\n]ER *[\r\n]\s*/", $isiSourceData, -1, PREG_SPLIT_NO_EMPTY); // (the 'PREG_SPLIT_NO_EMPTY' flag causes only non-empty pieces to be returned)
		$recordsCount = count($isiRecordsArray); // count how many records are available

		$csaRecordsArray = array(); // initialize array variable which will hold all records that were converted to CSA format

		// --------------------------------------------------------------------

		// LOOP OVER EACH RECORD:
		for ($i=0; $i<$recordsCount; $i++) // for each record...
		{
			// we'll only process an array element if it's text does contain the "PT" tag as well as the "SO" tag:
			if ((preg_match("/^PT /m", $isiRecordsArray[$i])) AND (preg_match("/^SO /m", $isiRecordsArray[$i]))) // ...process this record:
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
				$isiRecordFieldsArray = preg_split("/[\r\n]+(?=\w\w )/", $isiRecordsArray[$i]);

				// LOOP OVER EACH FIELD:
				foreach ($isiRecordFieldsArray as $recordField)
				{
					// we'll only process an array element if it starts with two letters followed by a space
					if (preg_match("/^\w\w /", $recordField))
					{
						// split each field into its tag and its field data:
						list ($recordFieldTag, $recordFieldData) = preg_split("/(?<=^\w\w) /", $recordField);

						foreach ($isiToCsaTagsArray as $csaTag => $isiTag) // for each ISI field that we'd like to convert...
						{
							if ($recordFieldTag == $isiTag)
							{
								// replace found ISI field identifier tag with the corresponding CSA tag:
								$recordFieldTag = $csaTag;

								// add a space to the beginning of any data line that starts with only three spaces (instead of four):
								$recordFieldData = preg_replace("/^   (?! )/m", "    ", $recordFieldData);

								// convert ISI publication type "J" into CSA format ("Journal Article"):
								if (($recordFieldTag == "PT: Publication Type") AND ($recordFieldData == "J"))
									$recordFieldData = "Journal Article";

								// merge multiple authors (that are printed on separate lines) with a semicolon (';') and a space:
								if ($recordFieldTag == "AU: Author")
									$recordFieldData = preg_replace("/\s*[\r\n]\s*/", "; ", $recordFieldData);

								// process address info:
								if ($recordFieldTag == "AF: Affiliation")
								{
									// remove any trailing punctuation from end of string:
									$recordFieldData = preg_replace("/[[:punct:]]+$/", "", $recordFieldData);

									$recordFieldDataArray = array(); // initialize array variable

									// if the address data string contains multiple addresses (which are given as one address per line):
									if (preg_match("/[\r\n]/", $recordFieldData))
										// split address data string into individual addresses:
										$recordFieldDataArray = preg_split("/[[:punct:]\s]*[\r\n]\s*/", $recordFieldData);
									else
										// use the single address as given:
										$recordFieldDataArray[] = $recordFieldData;

									// append the first email address from ISI "EM" field to the first address in "AF: Affiliation":
									if (($extractEmail) AND (!empty($emailAddress)))
										$recordFieldDataArray[0] .= ", Email: " . $emailAddress;

									if ($extractAllAddresses)
										// merge multiple addresses with a semicolon (';') and a space:
										$recordFieldData = implode("; ", $recordFieldDataArray);
									else
										// use only the first address in "AF: Affiliation":
										$recordFieldData = $recordFieldDataArray[0];
								}

								// if a comma (',') is used as keyword delimiter, we'll convert it into a semicolon (';'):
								if (($recordFieldTag == "DE: Descriptors") AND (!ereg(";", $recordFieldData)))
									$recordFieldData = preg_replace("/ *, */", "; ", $recordFieldData);

								// if all of the record data is in uppercase letters, we attempt to convert the string to something more readable:
								if ((preg_match("/^[[:upper:]\W\d]+$/", $recordFieldData)) AND ($isiTag != "UT")) // we exclude the ISI record ID from the ISI "UT" field
									// convert upper case to title case (converts e.g. "ELSEVIER SCIENCE BV" into "Elsevier Science Bv"):
									// (note that this case transformation won't do the right thing for author initials and abbreviations,
									//  but the result is better than the whole string being upper case, IMHO)
									$recordFieldData = preg_replace("/\b(\w)(\w+)/e", "strtoupper('\\1').strtolower('\\2')", $recordFieldData); // the 'e' modifier allows to execute perl code within the replacement pattern

								// merge again field tag and data:
								$recordField = $recordFieldTag . "\n    " . $recordFieldData;

								// append this field to array of CSA fields:
								$csaRecordFieldsArray[] = $recordField;

								// process next ISI field in '$isiRecordFieldsArray':
								continue;
							}
						}
					}
				}

				// append "JP: Journal Pages" field with generated page range to array of CSA fields:
				if (!empty($pageRange))
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

	// PUBMEDTOCSA
	// This function takes a PubMed ID and fetches corresponding PubMed XML record data from the PubMed server.
	// Record data will be converted to CSA format which can be imported via 'import_csa_modify.php'.
	// 
	// Authors: this function was originally written in Python by Andreas Hildebrandt <anhi@bioinf.uni-sb.de>
	//          and was ported to PHP by Marc Sturm <sturm@informatik.uni-tuebingen.de>
	function PubmedToCsa($pubmedID)
	{	
		$months     = array('Jan' => '01', 'Feb' => '02', 'Mar' => '03', 'Apr' => '04', 'May' => '05', 'Jun' => '06', 
							'Jul' => '07', 'Aug' => '08', 'Sep' => '09', 'Oct' => '10', 'Nov' => '11', 'Dec' => '12');
		$use_proxy=false; 
		function proxy_url($proxy_url)
		{
		   $proxy_name = 'www-cache.informatik.uni-tuebingen.de';
		   $proxy_port = 3128;
		   $proxy_user = '';
		   $proxy_pass = '';
		   $proxy_cont = '';	
		   $proxy_fp = fsockopen($proxy_name, $proxy_port);
		   if (!$proxy_fp) {return false;}
		   fputs($proxy_fp, "GET $proxy_url HTTP/1.0\r\nHost: $proxy_name\r\n");
		   fputs($proxy_fp, "Proxy-Authorization: Basic " . base64_encode("$proxy_user:$proxy_pass") . "\r\n\r\n");
		   while(!feof($proxy_fp)) { $proxy_cont .= fread($proxy_fp,4096); }
		   fclose($proxy_fp);
		   $proxy_cont = substr($proxy_cont, strpos($proxy_cont,"\r\n\r\n")+4);
		   return $proxy_cont;
		}

		if ($use_proxy) 
			$file = proxy_url("http://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id=".escapeshellcmd($pubmedID)."&retmode=xml");
		else
			$file = file_get_contents("http://www.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id=".escapeshellcmd($pubmedID)."&retmode=xml");

		$doc = DOMDocument::loadXML($file);
		$doc->preserveWhiteSpace = false;
		$xpath = new DOMXPath($doc);

		//-------------------------------------------------------------------------
		//  This parses the XML data:
		//   1) Find the article (assume only one at this point...)
		//   2) Do we need to add "et.al" to Authors?
		//   3) Only one affiliation...
		//-------------------------------------------------------------------------

		$articles = $doc->getElementsByTagName('PubmedArticle');
		foreach ($articles as $ref) 
		{
			$med = $ref->getElementsByTagName('MedlineCitation')->item(0);
			$article = $med->getElementsByTagName('Article')->item(0);
			$title = $xpath->query("ArticleTitle/text()", $article)->item(0)->nodeValue;
			$result .= "TI: Title\n    $title\n";	
			$author_list = $article->getElementsByTagName('AuthorList')->item(0);
			if ($author_list->attributes->getNamedItem('CompleteYN')->value == 'N')
				$add_et_al = true;
			else
				$add_et_al = false;

			$authors = $author_list->getElementsByTagName('Author');

			foreach ($authors as $author)
			{
				$author_line .= $xpath->query("LastName/text()", $author)->item(0)->nodeValue;
				$author_line .= ", ";
				$forename = $xpath->query("ForeName/text()", $author);
				if ($forename->length == 0)
					$forename = $xpath->query("Initials/text()", $author);
				if ($forename->length > 0)	
					$author_line .= $forename->item(0)->nodeValue;
				$author_line .= "; ";
			}
			if ($add_et_al)
				$author_line = substr($author_line,0,-2) . " et al.";
			else
				$author_line = substr($author_line,0,-2);

			$result .= "AU: Author\n    $author_line\n";	

			$affiliation = $xpath->query("Affiliation/text()", $article);
			if ($affiliation->length > 0)
				$result .= "AF: Affiliation\n    ".$affiliation->item(0)->nodeValue."\n";

			if ($ref->getElementsByTagName('MedlineJournalInfo')->length == 0) {
				print "No useable source information given!";
				exit(1);
			}

			$source = $xpath->query("MedlineJournalInfo/MedlineTA/text()", $med)->item(0)->nodeValue.". ";
			if ($xpath->query("Journal/JournalIssue/Volume/text()", $article)->length > 0)
					$source .= "Vol. " . $xpath->query("Journal/JournalIssue/Volume/text()", $article)->item(0)->nodeValue;
			if ($xpath->query("Journal/JournalIssue/Issue/text()", $article)->length > 0)
					$source .= " no. " . $xpath->query("Journal/JournalIssue/Issue/text()", $article)->item(0)->nodeValue;
			if ($xpath->query("Pagination/MedlinePgn/text()", $article)->length > 0)
				$source .= ",\n    pp. " . $xpath->query("Pagination/MedlinePgn/text()", $article)->item(0)->nodeValue;
			if ($xpath->query("Journal/JournalIssue/PubDate/Year", $article)->length > 0)
				$source .= ". " . $xpath->query("Journal/JournalIssue/PubDate/Year/text()", $article)->item(0)->nodeValue . ".";
			if ($source != "")
				$result .=  "SO: Source\n    " . $source . "\n";

			if ($xpath->query("Journal/ISSN", $article)->length > 0)
				$result .=  "IS: ISSN\n    " . $xpath->query("Journal/ISSN/text()", $article)->item(0)->nodeValue . "\n";
			if ($xpath->query("Abstract/AbstractText", $article)->length > 0)
				$result .=  "AB: Abstract\n    " . $xpath->query("Abstract/AbstractText/text()", $article)->item(0)->nodeValue . "\n";
			if ($xpath->query("Language", $article)->length > 0)
				$result .=  "LA: Language\n    " . $xpath->query("Language/text()", $article)->item(0)->nodeValue . "\n";

		$pubdate = "";
		if ($xpath->query("Journal/JournalIssue/PubDate", $article)->length > 0) 
		{
			$year = $xpath->query("Journal/JournalIssue/PubDate/Year/text()", $article);
			if ($year > 0)
			{
				$pubdate = $year->item(0)->nodeValue;
				$month = $xpath->query("Journal/JournalIssue/PubDate/Month/text()", $article);
				if ($month > 0)
				{
					$pubdate .= $months[$month->item(0)->nodeValue];
					$day = $xpath->query("Journal/JournalIssue/PubDate/Day/text()", $article);
					if ($day->length > 0)
						$pubdate .= $day->item(0)->nodeValue;
					else
						$pubdate .= "00";
				}else{
					$pubdate = $pubdate . "00";
				}
			}
			$result .=  "PD: Publication Date\n    " . $pubdate . "\n";
		}

		$ptl = $article->getElementsByTagName('PublicationTypeList');
		$publication_type = "";
		if ($ptl->length > 0)
		{
			$pts = $xpath->query("PublicationTypeList/PublicationType/text()", $article);
			for ($i=0; $i<$pts->length ; ++$i)
			//{
				$publication_type .= $pts->item($i)->nodeValue . "; ";
			//}
		}
		if ($publication_type != "")
			$result .=  "PT: Publication Type\n    " . substr($publication_type,0,-2) . "\n";

		// collect all MeshHeadings and put them as descriptors.
		// this currently ignores all other types of keywords
		$descs = $xpath->query("MeshHeadingList/MeshHeading/DescriptorName/text()", $med);
		$desc_line = "";

		for ($i=0; $i<$descs->length ; ++$i)
			$desc_line .= $descs->item($i)->nodeValue . "; ";			

		if ($desc_line != "")
			$result .=  "DE: Descriptors\n    " . substr($desc_line,0,-2) . "\n";

		$year = $xpath->query("Journal/JournalIssue/PubDate/Year/text()", $article)	;
		if ($year > 0)
			$result .=  "PY: Publication Year\n    " . $year->item(0)->nodeValue . "\n";
		}

		return $result;
	}

	// --------------------------------------------------------------------

?>