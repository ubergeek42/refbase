<?
/*============================================================================
   Filename:    isi2csa.php
   Last Change: 13 Dec. 2005  (Santa Lucia!)
   Developer:   Joachim Almergren
   Function:    This is a wrapper for converting ISI exports to CSA.
                And this is very EASY! Just translate the tags...

  Key ISI tags to use (to separate records):
  ------------------------------------------
    File start:      FN, VR
    Citation start:  PT
    Citation End:    ER
    File End:        EF
 
  *****************************************************
  **  For running non interactively (by "include")   **
  **  comment out the following lines:               **
  **  24-35, and move </BODY></HTML> to witin php    **
  *****************************************************
 
=============================================================================*/

/*error_reporting(E_ALL ^ E_NOTICE);
$isifile = "C:\\www\\xampp\\htdocs\\refbase\\zexec\\isi_export.txt";
$isi_data = file_get_contents($isifile);

print "<pre>";
echo "Original File:\n".$isi_data."\n";
echo "------------------------------";
echo IsiToCsa($isi_data);
//$csa_data = IsiToCsa($isi_data);
// echo $csa_data;
print "</pre>";
exit();*/

function IsiToCsa($isi_data)
{
//-------------------------------------------------------------------------
// Probably it would be more professional to construct a "tag" array here...
	$isi_tag_FN = "/^FN /m"; // Beginning of ISI export file
	$isi_tag_VR = "/^VR /m"; // [same]
	$isi_tag_PT = "/^PT /m"; // Beginning of record/item (Publication Type)
	$isi_tag_AU = "/^AU /m";
	$isi_tag_TI = "/^TI /m";
	$isi_tag_SO = "/^SO /m";
	$isi_tag_LA = "/^LA /m";
	$isi_tag_DT = "/^DT /m";
	$isi_tag_DE = "/^DE /m";
	$isi_tag_ID = "/^ID /m";
	$isi_tag_AB = "/^AB /m";
	$isi_tag_C1 = "/^C1 /m";
	$isi_tag_RP = "/^RP /m";
	$isi_tag_EM = "/^EM /m";
	$isi_tag_NR = "/^NR /m";
	$isi_tag_TC = "/^TC /m";
	$isi_tag_PU = "/^PU /m";
	$isi_tag_PI = "/^PI /m";
	$isi_tag_PA = "/^PA /m";
	$isi_tag_SN = "/^SN /m";
	$isi_tag_J9 = "/^J9 /m";
	$isi_tag_JI = "/^JI /m";
	$isi_tag_PD = "/^PD /m";
	$isi_tag_PY = "/^PY /m";
	$isi_tag_VL = "/^VL /m";
	$isi_tag_IS = "/^IS /m";
	$isi_tag_BP = "/^BP /m";
	$isi_tag_EP = "/^EP /m";
	$isi_tag_PG = "/^PG /m";
	$isi_tag_SC = "/^SC /m";
	$isi_tag_GA = "/^GA /m";
	$isi_tag_UT = "/^UT /m"; // ISI ID
	$isi_tag_ER = "/^ER/m";  // End of Record
	$isi_tag_EF = "/^EF/m";  // EOF

	// All CSA Tags (NB: some have a number in them!) [In order]
	// Does the CSA tag ORDER matter?
	$csa_tag_DN = "DN: Database Name\n   ";
	$csa_tag_TI = "TI: Title\n   ";
	$csa_tag_AU = "AU: Author\n   ";
	$csa_tag_AF = "AF: Affiliation\n   ";
	$csa_tag_SO = "SO: Source\n   ";
	$csa_tag_IS = "IS: ISSN\n   ";
	$csa_tag_AB = "AB: Abstract\n   ";
	$csa_tag_LA = "LA: Language\n   ";
	$csa_tag_SL = "SL: Summary Language\n   ";
	$csa_tag_PY = "PY: Publication Year\n   ";
	$csa_tag_PD = "PD: Publication Date\n   ";
	$csa_tag_PT = "PT: Publication Type\n   ";
	$csa_tag_DE = "DE: Descriptors\n   ";
	$csa_tag_TR = "TR: ASFA Input Center Number\n   ";
	$csa_tag_CL = "CL: Classification\n   ";
	$csa_tag_UD = "UD: Update\n   ";
	$csa_tag_SF = "SF: Subfile\n   ";
	$csa_tag_AN = "AN: Accession Number\n   ";
	$csa_tag_F1 = "F1: Fulltext Info\n   ";
	$csa_tag_A1 = "A1: Alert Info\n   ";
	$csa_tag_JN = "JN: Journal Name\n   ";
	$csa_tag_JP = "JP: Journal Pages\n   "; 
	$csa_tag_JV = "JV: Journal Volume\n   ";
	$csa_tag_JI = "JI: Journal Issue\n   ";
	$csa_tag_DT = "DT: Document Type\n   ";
	$csa_tag_BL = "BL: Bibliographic Level\n   ";
	$csa_tag_BL = "ER: Environmental Regime\n   ";
	$csa_tag_CF = "CF: Conference\n   ";
	$csa_tag_NT = "NT: Notes\n   "; // Good place to put the ISI ID from "UT"
	$csa_tag_DO = "DO: DOI\n   ";
	
	//=========================================================================
	// Remove any text preceeding the record(s):
	//$sourceText = preg_replace("/.*(?=Record 1 of \d+)/s", "", $sourceText); 

	//global $isi_data;
	unset($sourceText);
	$total_refs   = 0;
	$record_count = 0;
	$sourceText = trim($isi_data); // Trim white spaces from beginning and end of data

	// First we remove unused ISI tags and their text:    THIS IS FRAGILE!!
	$sourceText =  preg_replace("/^(FN|VR|ID|C1|RP|EM|NR|TC|PU|PI|PA|SN|J9|PG|SC|GA) .+/m", "", $sourceText);
	$sourceText =  preg_replace("/^\n   .+\n/m", "", $sourceText); // Remove single "lonely" lines of text
	$sourceText =  preg_replace("/^\n+/m", "", $sourceText);       // Remove single "\n"
	//echo "after tag removal:\n".$sourceText;

	// Need to count total number of records (references) in file
	if(preg_match('/^PT /', $isi_data))
		$total_refs[]++;
	$isi_recs = count($total_refs);
	//echo "Total Refs: $isi_recs \n";

	##-------------------------------------------------------------------------
	##  Special Treatment tags:
	##     VERY SPECIAL!
	##  Loop over all records
	##  We need to find and control the current "PT" number...
	##
	for ($record_count = 1; $record_count <= $isi_recs; $record_count++) {
		$record_string = "\nRecord $record_count of $isi_recs \n";
		$sourceText = preg_replace("/^PT .+/m", $record_string , $sourceText, 1);
		// This effectively removes the PT tag, and thus needs to be inserted again 
		// But since ISI only have (?) PT = J, it is easy!
	}
	##-------------------------------------------------------------------------
	##  Pages: JP
	##  NOTE: This will not work for multiple citations!
	##        Then it would need to be looped and without "/m"...
	##        "/m" treats the string as ONE line eventhough there are "\n"s...
	##
	preg_match("/^BP (.+)/m", $sourceText, $isi_page_bp);
	preg_match("/^EP (.+)/m", $sourceText, $isi_page_ep);
	$csa_pages	= preg_replace("/\s*/m", "", $isi_page_bp[1]."-".$isi_page_ep[1]);
	$sourceText  = preg_replace("/^BP .+/m", $csa_tag_JP.$csa_pages, $sourceText, 1);
	$sourceText  = preg_replace("/^EP .+\n/m", "", $sourceText); 
	//echo $csa_tag_JP.$csa_pages;

	##-------------------------------------------------------------------------
	##  Authors: AU
	##  (we could potentially just ignore this, since the CSA importer can handle it...(?)
	##  ATTENTION: Imported data are most likely to contain Carriage Returns, "\r"
	##             These should ideally be removed from the beginning...
	##
	$isi_au_beg = strpos($sourceText, "\nAU ") + 3;
	$isi_au_end = strpos($sourceText, "\nTI ") - 1; 
	$isi_au_len = $isi_au_end - $isi_au_beg;
	$isi_au_sub = trim(substr($sourceText, $isi_au_beg, $isi_au_len));
	$isi_au_sub = preg_replace("/ +/", " ", $isi_au_sub); // Remove excess spaces
	$isi_au_sub = preg_replace("/\r/", "",  $isi_au_sub); // Remove Windows <CR>'s
	$isi_author = preg_replace("/\n/", ";", $isi_au_sub); // Replace "\n"
	$sourceText = substr_replace($sourceText, $csa_tag_AU.$isi_author, $isi_au_beg-2, $isi_au_len+2);

	#--------------------------------------------------------------------------
	//$sourceText = preg_replace($isi_tag_FN, $csa_tag_DN, $sourceText); 
	//$sourceText = preg_replace($isi_tag_VR, "", $sourceText); 
	//$sourceText = preg_replace($isi_tag_PT, $csa_tag_PT, $sourceText); 
	$sourceText = preg_replace($isi_tag_TI, $csa_tag_TI, $sourceText); 
	$sourceText = preg_replace($isi_tag_SO, $csa_tag_SO, $sourceText); //Capitalize! or J1!
	$sourceText = preg_replace($isi_tag_LA, $csa_tag_LA, $sourceText); 
	$sourceText = preg_replace($isi_tag_DT, $csa_tag_DT, $sourceText); 
	$sourceText = preg_replace($isi_tag_DE, $csa_tag_DE, $sourceText); 
	//$sourceText = preg_replace($isi_tag_ID, $csa_tag_ID, $sourceText); // CL?
	$sourceText = preg_replace($isi_tag_AB, $csa_tag_AB, $sourceText); 
	//$sourceText = preg_replace($isi_tag_C1, $csa_tag_C1, $sourceText); 
	//$sourceText = preg_replace($isi_tag_RP, $csa_tag_RP, $sourceText); 
	//$sourceText = preg_replace($isi_tag_EM, $csa_tag_EM, $sourceText); 
	//$sourceText = preg_replace($isi_tag_NR, $csa_tag_NR, $sourceText); 
	//$sourceText = preg_replace($isi_tag_TC, $csa_tag_TC, $sourceText); 
	//$sourceText = preg_replace($isi_tag_PU, $csa_tag_PU, $sourceText); 
	//$sourceText = preg_replace($isi_tag_PI, $csa_tag_PI, $sourceText); 
	//$sourceText = preg_replace($isi_tag_PA, $csa_tag_PA, $sourceText); 
	//$sourceText = preg_replace($isi_tag_SN, $csa_tag_SN, $sourceText); 
	//$sourceText = preg_replace($isi_tag_J9, $csa_tag_J9, $sourceText); 
	$sourceText = preg_replace($isi_tag_JI, $csa_tag_JN, $sourceText); // !
	$sourceText = preg_replace($isi_tag_PD, $csa_tag_PD, $sourceText); 
	$sourceText = preg_replace($isi_tag_PY, $csa_tag_PY, $sourceText); 
	$sourceText = preg_replace($isi_tag_VL, $csa_tag_JV, $sourceText); // !
	$sourceText = preg_replace($isi_tag_IS, $csa_tag_JI, $sourceText); // !
	//$sourceText = preg_replace($isi_tag_PG, $csa_tag_PG, $sourceText); 
	//$sourceText = preg_replace($isi_tag_SC, $csa_tag_SC, $sourceText); 
	//$sourceText = preg_replace($isi_tag_GA, $csa_tag_GA, $sourceText); 
	$sourceText = preg_replace($isi_tag_UT, $csa_tag_NT, $sourceText); // !
	$sourceText = preg_replace($isi_tag_ER, "", $sourceText); // BL ??
	$sourceText = preg_replace($isi_tag_EF, "", $sourceText); // BL ??
	#--------------------------------------------------------------------------
	
	// Indent non-tagged lines by 1 space
	$sourceText = preg_replace("/^  /m", '   ', $sourceText); 

	return $sourceText;
}
// ============================================================================
//<!-- </BODY></HTML> -->
?>