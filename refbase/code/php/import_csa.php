<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./import_csa.php
	// Created:    21-Nov-03, 22:05
	// Modified:   29-Sep-04, 19:16

	// Import form that offers to import records from the "Cambridge Scientific Abstracts" (CSA)
	// Internet Database Service (<http://www.csa1.co.uk/csa/index.html>). This import form requires
	// the "full record" format offered by the CSA Internet Database Service.

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
		$HeaderString = "Import a record from Cambridge Scientific Abstracts:"; // Provide the default message
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
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- Import CSA Record", "index,follow", "Search the " . htmlentities($officialDatabaseName), "", false, "", $viewType);
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"import_csa_modify.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"importCSA\">"
		. "\n<input type=\"hidden\" name=\"submit\" value=\"Import\">" // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Import" button)
		. "\n<input type=\"hidden\" name=\"showLinks\" value=\"1\">"; // embed '$showLinks=1' so that links get displayed on any 'display details' page
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the CSA import form\">"
			. "\n<tr>\n\t<td width=\"58\" valign=\"top\"><b>Import CSA Full Record:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td><textarea name=\"sourceText\" rows=\"6\" cols=\"60\">Paste the CSA record (in 'full record' format!) here...</textarea></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Options:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><input type=\"checkbox\" name=\"showSource\" value=\"1\" checked>&nbsp;&nbsp;&nbsp;Display original source data</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>";

	if (isset($_SESSION['user_permissions']) AND ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains either 'allow_import' or 'allow_batch_import'...
	// adjust the title string for the import button
	{
		$importButtonLock = "";
		$importTitle = "press this button to have the database parse your entered data and pre-fill the record entry form for you";
	}
	else // Note, that disabling the submit button is just a cosmetic thing -- the user can still submit the form by pressing enter or by building the correct URL from scratch!
	{
		$importButtonLock = " disabled";
		$importTitle = "not available since you have no permission to import any records";
	}

	echo "\n\t<td>\n\t\t<input type=\"submit\" name=\"submit\" value=\"Import\"$importButtonLock title=\"$importTitle\"></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td align=\"center\" colspan=\"3\">&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Help:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">This form enables you to import records from the <a href=\"" . $importCSArecordsURL . "\" target=\"top\">" // '$importCSArecordsURL' is defined in 'ini.inc.php'
			. "Cambridge Scientific Abstracts</a> (CSA) Internet Database Service. The form requires the 'full record' format offered by the CSA Internet Database Service. An example of a valid CSA 'full record' format is given below:</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Example:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><pre>Record 1 of 52

DN: Database Name
    ASFA: Aquatic Sciences and Fisheries Abstracts
TI: Title
    Diet composition influences the growth of the pelagic mysid
    shrimp, Mysis mixta (Mysidacea)
AU: Author
    Lehtiniemi, M; Vitasalo, M; Kuosa, H
AF: Affiliation
    Finnish Institute of Marine Research, P.O. Box 33, FIN-00931
    Helsinki, Finland
SO: Source
    Boreal Environment Research [Boreal Environ. Res.]. Vol. 7, no. 2,
    pp. 121-128. 2002.
IS: ISSN
    1239-6095
AB: Abstract
    We studied the growth, feeding, and elemental composition of Mysis
    mixta from June to September 1997 in the northern Baltic Sea. In
    June the juvenile population had a unimodal size distribution
    (mean length similar to 6 mm), but in July-August, the population
    was divided into two cohorts. A stomach content analysis showed
    that the mysids in the larger and faster growing cohort fed
    significantly more on crustacean zooplankton and pelagic material
    than the smaller one: the mean ratios of zooplankton:phytoplankton
    and pelagic:benthic particles in July-August were respectively
    0.27 and 0.11 for the small cohort, and 0.54 and 0.36 for the
    large cohort. This suggests that food quality and its energy
    content are important in influencing the growth of pelagic mysids
    in the northern Baltic. The C:N ratio of the two cohorts did not
    vary much, which shows that ingestion of food items with varying
    elemental content is not necessarily reflected in the elemental
    composition of consumers.
LA: Language
    English
SL: Summary Language
    English
PY: Publication Year
    2002
PD: Publication Date
    20020000
PT: Publication Type
    Journal Article
DE: Descriptors
    Growth; Feeding; Diets; Chemical composition; Seasonal variations;
    Mysis mixta; ANE, Baltic Sea
TR: ASFA Input Center Number
    CS0309883
CL: Classification
    Q1 01424 Age and growth; O 1070 Ecology/Community Studies
UD: Update
    200305
SF: Subfile
    ASFA 1: Biological Sciences &amp; Living Resources; Oceanic Abstracts
AN: Accession Number
    5449614
F1: Fulltext Info
    1239-6095,7,2,121-128,2002
A1: Alert Info
    20030606
JN: Journal Name
    Boreal Environment Research
JP: Journal Pages
    121-128
JV: Journal Volume
    7
JI: Journal Issue
    2
DT: Document Type
    J
BL: Bibliographic Level
    AS
</pre></td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";
	
	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc.php')
	displayfooter("");

	// --------------------------------------------------------------------
?>

</body>
</html> 
