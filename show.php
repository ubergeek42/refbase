<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./show.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    02-Nov-03, 14:10
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script serves as a routing page which takes e.g. any record serial number, date, year, author, contribution ID or thesis that was passed
	// as parameter to the script, builds an appropriate SQL query and passes that to 'search.php' which will then display the corresponding
	// record(s). This allows to provide short URLs (like: '.../show.php?record=12345') for email announcements or to generate publication lists.
	// TODO: I18n


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// Extract the ID of the client from which the query originated:
	// this identifier is used to identify queries that originated from the refbase command line clients ("cli-refbase-1.1", "cli-refbase_import-1.0") or from a bookmarklet (e.g., "jsb-refbase-1.0")
	// (note that 'client' parameter has to be extracted *before* the call to the 'start_session()' function, since it's value is required by this function)
	if (isset($_REQUEST['client']))
		$client = $_REQUEST['client'];
	else
		$client = "";

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// Initialize preferred display language:
	// (note that 'locales.inc.php' has to be included *after* the call to the 'start_session()' function)
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// Extract any generic parameters passed to the script:
	// (they control how found records are presented on screen)

	// Extract the type of display requested by the user. Normally, this will be one of the following:
	//  - '' => if the 'submit' parameter is empty, this will produce the default view
	//  - 'List' => display records using the columnar output style ('displayColumns()' function)
	//  - 'Display' => display details for all found records ('displayDetails()' function)
	//  - 'Cite' => build a proper citation for all found records ('generateCitations()' function)
	//  - 'Export' => generate and return found records in the specified export format ('generateExport()' function)
	if (isset($_REQUEST['submit']))
		$displayType = $_REQUEST['submit'];
	else
		$displayType = "";

	// Note that for 'show.php' we don't accept any other display types than '', 'List', 'Display', 'Cite', 'Export' and 'Browse',
	// if any other types were specified, we'll use the default view that's given in session variable 'userDefaultView'. Also note
	// that the display type is changed further down below.
	if (!empty($displayType) AND !preg_match("/^(List|Display|Cite|Export|Browse)$/i", $displayType))
		$displayType = "";

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	if (isset($_REQUEST['showQuery']) AND ($_REQUEST['showQuery'] == "1"))
		$showQuery = "1";
	else
		$showQuery = "0"; // don't show the SQL query by default

	if (isset($_REQUEST['showLinks']) AND ($_REQUEST['showLinks'] == "0"))
		$showLinks = "0";
	else
		$showLinks = "1"; // show the links column by default

	if (isset($_REQUEST['showRows']) AND preg_match("/^[0-9]+$/", $_REQUEST['showRows'])) // NOTE: we cannot use "^[1-9]+[0-9]*$" here since 'maximumRecords=0' is used in 'opensearch.php' queries to return just the number of found records (and not the full record data)
		$showRows = $_REQUEST['showRows']; // contains the desired number of search results (OpenSearch equivalent: '{count}')
	else
		$showRows = $_SESSION['userRecordsPerPage']; // get the default number of records per page preferred by the current user

	if (isset($_REQUEST['startRecord'])) // contains the offset of the first search result, starting with one (OpenSearch equivalent: '{startIndex}')
		$rowOffset = ($_REQUEST['startRecord']) - 1; // first row number in a MySQL result set is 0 (not 1)
	else
		$rowOffset = ""; // if no value to the 'startRecord' parameter is given, we'll output records starting with the first record in the result set

	if (isset($_REQUEST['wrapResults']) AND ($_REQUEST['wrapResults'] == "0"))
		$wrapResults = "0"; // 'wrapResults=0' causes refbase to output only a partial document structure containing solely the search results (e.g. for HTML, everything is omitted except for the <table> block containing the search results)
	else
		$wrapResults = "1"; // we'll output a full document (HTML, RTF, LaTeX, etc) structure unless the 'wrapResults' parameter is set explicitly to "0"

	if (isset($_REQUEST['citeStyle']) AND !empty($_REQUEST['citeStyle']))
		$citeStyle = $_REQUEST['citeStyle'];
	else
		$citeStyle = $defaultCiteStyle; // if no cite style was given, we'll use the default cite style which is defined by the '$defaultCiteStyle' variable in 'ini.inc.php'

	if (isset($_REQUEST['citeOrder']))
		$citeOrder = $_REQUEST['citeOrder']; // get information how citation data should be sorted (if this parameter is set to 'year', records will be listed in blocks sorted by year)
	else
		$citeOrder = "";

	// for citation output, get information how citation data shall be returned:
	// - 'html' => return citations as HTML with mime type 'text/html'
	// - 'RTF' => return citations as RTF data with mime type 'application/rtf'
	// - 'PDF' => return citations as PDF data with mime type 'application/pdf'
	// - 'LaTeX' => return citations as LaTeX data with mime type 'application/x-latex'
	// - 'Markdown' => return citations as Markdown TEXT data with mime type 'text/plain'
	// - 'ASCII' => return citations as TEXT data with mime type 'text/plain'
	// - 'LaTeX .bbl' => return citations as LaTeX .bbl file (for use with LaTeX/BibTeX) with mime type 'application/x-latex'
	if (isset($_REQUEST['citeType']) AND preg_match("/^(html|RTF|PDF|LaTeX|Markdown|ASCII|LaTeX \.bbl)$/i", $_REQUEST['citeType']))
		$citeType = $_REQUEST['citeType'];
	else
		$citeType = "html";

	if (isset($_REQUEST['exportFormat']) AND !empty($_REQUEST['exportFormat']))
		$exportFormat = $_REQUEST['exportFormat'];
	else
		$exportFormat = $defaultExportFormat; // if no export format was given, we'll use the default export format which is defined by the '$defaultExportFormat' variable in 'ini.inc.php'

	// for export, get information how exported data shall be returned; possible values:
	// - 'text' => return data with mime type 'text/plain'
	// - 'html' => return data with mime type 'text/html
	// - 'xml' => return data with mime type 'application/xml
	// - 'rss' => return data with mime type 'application/rss+xml'
	// - 'file' => return data as downloadable file
	// - 'email' => send data as email (to the user's login email address)
	if (isset($_REQUEST['exportType']) AND preg_match("/^(text|html|xml|rss|file|email)$/i", $_REQUEST['exportType']))
		$exportType = $_REQUEST['exportType'];
	else
		$exportType = "html";

	if (isset($_REQUEST['exportStylesheet']))
		$exportStylesheet = $_REQUEST['exportStylesheet']; // extract any stylesheet information that has been specified for XML export formats
	else
		$exportStylesheet = "";

	if (isset($_REQUEST['headerMsg']))
		$headerMsg = stripTags($_REQUEST['headerMsg']); // we'll accept custom header messages but strip HTML tags from the custom header message to prevent cross-site scripting (XSS) attacks (function 'stripTags()' is defined in 'include.inc.php')
						// Note: custom header messages are provided so that it's possible to include an information string within a link. This info string could
						//       e.g. describe who's publications are being displayed (e.g.: "Publications of Matthias Steffens:"). I.e., a link pointing to a
						//       persons own publications can include the appropriate owner information (it will show up as header message)
	else
		$headerMsg = "";

	// --------------------------------------------------------------------

	// Extract any parameters that are specific to 'show.php':
	// (these parameters control which records will be returned by 'search.php')

	// Note: you can combine different parameters to achieve an "AND" query, e.g.:
	//
	//       show.php?contribution_id=AWI&author=steffens&year=2005
	//
	//       which will find all records where:  'contribution_id' contains 'AWI'  -AND-  'author' contains 'steffens'  -AND-  'year' contains '2005'

	if (isset($_REQUEST['serial']))
		$serial = $_REQUEST['serial']; // get the record serial number that was entered by a user in the 'show.php' web form

	elseif (isset($_REQUEST['record']))
		$serial = $_REQUEST['record']; // get the record serial number that was passed by an URL of the form '.../show.php?record=12345' (as it occurs in RSS feeds and email announcements)
	else
		$serial = "";

	if (isset($_REQUEST['recordIDSelector']))
		$recordIDSelector = $_REQUEST['recordIDSelector']; // get the value returned from the 'recordIDSelector' drop down menu (returned value is either 'serial', 'call_number' or 'cite_key')
	else
		$recordIDSelector = "";

	if (isset($_REQUEST['recordConditionalSelector']))
		$recordConditionalSelector = $_REQUEST['recordConditionalSelector']; // get the value returned from the 'recordConditionalSelector' drop down menu (returned value is either 'is equal to', 'contains' or 'is within list')
	else
	{
		if (isset($_REQUEST['record'])) // normally, '$recordConditionalSelector' get's only specified in the 'show.php' web form but not in RSS/Email announcement URLs, but...
			$recordConditionalSelector = "is equal to"; // ...if 'show.php' was called from a RSS/Email announcement URL (like '.../show.php?record=12345') we'll have to make sure that the serial field will be matched fully and not only partly
		else
			$recordConditionalSelector = "";
	}

	// If the 'records' parameter is present and contains any number(s) or 'all' as value, it will override any given 'serial' or 'record' parameters.
	// This param was introduced to provide an easy 'Show All' link ('.../show.php?records=all') which will display all records in the database.
	// It does also allow to easily link to multiple records (such as in '.../show.php?records=1234,5678,90123', or, for consecutive ranges, '.../show.php?records=123-131').
	// Mixing of record serial numbers and number ranges is also supported (e.g. '.../show.php?records=123-141,145,147,150-152').
	if (isset($_REQUEST['records']))
	{
		// if the 'records' parameter is given, it's value must be either 'all' or any number(s) (or number ranges such as "123-141") delimited by any other characters than digits or hyphens:
		if (preg_match("/^all$/i", $_REQUEST['records']))
		{
			// '.../show.php?records=all' is effectively a more nice looking variant of 'show.php?serial=%2E%2B&recordConditionalSelector=contains':
			$serial = ".+"; // show all records
			$recordConditionalSelector = "contains";
		}
		elseif (preg_match("/[0-9]/", $_REQUEST['records'])) // show all records whose serial numbers match the given numbers (or number ranges)
		{
			// split on any character that's not a digit or a hyphen ("-"):
			$recordSerialsArray = preg_split("/[^\d-]+/", $_REQUEST['records']);

			// loop over '$recordSerialsArray' and explode any record serial number ranges (such as "123-141" or "150-152"):
			$ct = count($recordSerialsArray);
			for ($i=0; $i < $ct; $i++)
			{
				if (preg_match("/\d+-\d+/", $recordSerialsArray[$i])) // match serial number range
				{
					$recordSerialsRange = preg_split("/-/", $recordSerialsArray[$i]); // extract start & end of serial number range into an array

					// explode serial number range (e.g. transform "150-152" into "150,151,152")
					$recordSerialsArray[$i] = $recordSerialsRange[0];
					for ($recordSerial = $recordSerialsRange[0] + 1; $recordSerial <= $recordSerialsRange[1]; $recordSerial++)
						$recordSerialsArray[$i] .= "," . $recordSerial;
				}
			}

			// '.../show.php?records=1,12,123,1234' is effectively a more nice looking variant of 'show.php?serial=1,12,123,1234&recordConditionalSelector=is%20within%20list':
			$serial = join(",", $recordSerialsArray); // join again '$recordSerialsArray' using "," as delimiter
			$recordConditionalSelector = "is within list";
		}
	}

	if (isset($_REQUEST['date']))
		$date = $_REQUEST['date'];
	else
		$date = "";

	if (isset($_REQUEST['time']))
		$time = $_REQUEST['time'];
	else
		$time = "";

	if (isset($_REQUEST['when'])) // if given only 'edited' is recognized as value
		$when = $_REQUEST['when']; // get info about what kind of date shall be searched for ("when=edited" -> search field 'modified_date'; otherwise -> search field 'created_date')
	else
		$when = "";

	if (isset($_REQUEST['range'])) // given value must be either 'after', 'before', 'equal_or_after' or 'equal_or_before'
		$range = $_REQUEST['range']; // check the date range ("range=after" -> return all records whose created/modified date/time is after '$date'/'$time'; "range=before" -> return all records whose created/modified date/time is before '$date'/'$time')
	else
		$range = "";

	if (isset($_REQUEST['year']))
		$year = $_REQUEST['year'];
	else
		$year = "";

	if (isset($_REQUEST['author']))
		$author = $_REQUEST['author'];
	else
		$author = "";

	if (isset($_REQUEST['without']) AND preg_match("/^dups$/i", $_REQUEST['without'])) // if given only 'dups' is currently recognized as value
		$without = $_REQUEST['without']; // check whether duplicate records should be excluded ("without=dups" -> exclude duplicate records)
	else
		$without = "";

	if (isset($_REQUEST['title']))
		$title = $_REQUEST['title'];
	else
		$title = "";

	if (isset($_REQUEST['publication']))
		$publication = $_REQUEST['publication'];
	else
		$publication = "";

	if (isset($_REQUEST['abbrev_journal']))
		$abbrevJournal = $_REQUEST['abbrev_journal'];
	else
		$abbrevJournal = "";

	if (isset($_REQUEST['keywords']))
		$keywords = $_REQUEST['keywords'];
	else
		$keywords = "";

	if (isset($_REQUEST['abstract']))
		$abstract = $_REQUEST['abstract'];
	else
		$abstract = "";

	if (isset($_REQUEST['area']))
		$area = $_REQUEST['area'];
	else
		$area = "";

	if (isset($_REQUEST['expedition']))
		$expedition = $_REQUEST['expedition'];
	else
		$expedition = "";

	if (isset($_REQUEST['notes']))
		$notes = $_REQUEST['notes'];
	else
		$notes = "";

	if (isset($_REQUEST['location']))
		$location = $_REQUEST['location'];
	else
		$location = "";

	if (isset($_REQUEST['type']))
		$type = $_REQUEST['type'];
	else
		$type = "";

	if (isset($_REQUEST['contribution_id']))
		$contributionID = $_REQUEST['contribution_id'];
	else
		$contributionID = "";

	if (isset($_REQUEST['thesis'])) // given value must be either 'yes' (= find only theses) or 'no' (= exclude any theses) or a search string (like 'master', 'bachelor' or 'doctor')
		$thesis = $_REQUEST['thesis'];
	else
		$thesis = "";

	// NOTE: When querying any user-specific fields (i.e. 'marked', 'copy', 'selected',
	//       'user_keys', 'user_notes', 'user_file', 'user_groups', 'cite_key'), the
	//       'userID' parameter must be given as well!

	if (isset($_REQUEST['selected'])) // given value must be either 'yes' or 'no'
		$selected = $_REQUEST['selected']; // if e.g. "selected=yes", we'll restrict the search results to those records that have the 'selected' bit set to 'yes' for a particular user.
	else
		$selected = "";					//            (the 'selected' parameter can be queried with a user ID that's different from the current user's own user ID, see note at "Build FROM clause")

	if (isset($_REQUEST['only']))
	{
		if ($_REQUEST['only'] == "selected"); // the 'only=selected' parameter/value combination was used in refbase-0.8.0 and earlier but is now replaced by 'selected=yes' (we still read it for reasons of backwards compatibility)
			$selected = "yes";
	}

	if (isset($_REQUEST['ismarked'])) // given value must be either 'yes' or 'no' (note that this parameter is named 'ismarked' instead of 'marked' to avoid any name collisions with the 'marked' parameter that's used in conjunction with checkboxes!)
		$marked = $_REQUEST['ismarked']; // if e.g. "ismarked=yes", we'll restrict the search results to those records that have the 'marked' bit set to 'yes' for a particular user.
	else
		$marked = "";					//            (currently, the 'ismarked' parameter can NOT be queried with a user ID that's different from the current user's own user ID!)

	if (isset($_REQUEST['user_keys']))
		$userKeys = $_REQUEST['user_keys'];
	else
		$userKeys = "";					//            (currently, the 'user_keys' parameter can NOT be queried with a user ID that's different from the current user's own user ID!)

	if (isset($_REQUEST['user_notes']))
		$userNotes = $_REQUEST['user_notes'];
	else
		$userNotes = "";					//            (currently, the 'user_notes' parameter can NOT be queried with a user ID that's different from the current user's own user ID!)

	if (isset($_REQUEST['user_groups']))
		$userGroups = $_REQUEST['user_groups'];
	else
		$userGroups = "";					//            (currently, the 'user_groups' parameter can NOT be queried with a user ID that's different from the current user's own user ID!)

	// NOTE: Actually, the 'cite_key' can be queried with a foreign user ID! This
	//        was permitted in function 'verifySQLQuery()' to allow every user to
	//        query other user's 'cite_key' fields using 'sru.php' (e.g., by URLs
	//        like: 'sru.php?version=1.1&query=bib.citekey=...&x-info-2-auth1.0-authenticationToken=email=...')
	if (isset($_REQUEST['cite_key']))
		$citeKey = $_REQUEST['cite_key'];
	else
		$citeKey = "";

	if (isset($_REQUEST['call_number']))
		$callNumber = $_REQUEST['call_number'];
	else								// IMPORTANT: We treat any 'call_number' query as specific to every user, i.e. a user can only query his own call numbers.
		$callNumber = "";

	if (isset($_REQUEST['userID']) AND preg_match("/^[0-9]+$/", $_REQUEST['userID']))
		$userID = $_REQUEST['userID']; // when searching user specific fields, this parameter specifies the user's user ID. I.e., the 'userID' parameter does only make
									// sense when specified together with any of the user-specific parameters. As an example, "show.php?author=...&selected=yes&userID=2"
	else							// will show every record where the user who's identified by user ID "2" has set the selected bit to "yes".
		$userID = "";

	if (isset($_REQUEST['by']))
		$browseByField = $_REQUEST['by'];
	else
		$browseByField = "";

	if (isset($_REQUEST['where']))
		$where = stripSlashesIfMagicQuotes($_REQUEST['where']); // remove slashes from custom WHERE clause if 'magic_quotes_gpc = On'; function 'stripSlashesIfMagicQuotes()' is defined in 'include.inc.php')
	else
		$where = "";

	if (isset($_REQUEST['queryType']))
		$queryType = $_REQUEST['queryType'];
	else
		$queryType = "";

	if ($queryType == "or")
		$queryType = "OR"; // we allow for lowercase 'or' but convert it to uppercase (in an attempt to increase consistency & legibility of the SQL query) 

	if ($queryType != "OR") // if given value is 'OR' multiple parameters will be connected by 'OR', otherwise an 'AND' query will be performed
		$queryType = "AND";


	// normally, 'show.php' requires that parameters must be specified explicitly to gain any view that's different from the default view
	// There's one exception to this general rule which is if a user uses 'show.php' to query a *single* record by use of its record identifier (e.g. via '.../show.php?record=12345' or via the web form when using the "is equal to" option).
	// In this case we'll directly jump to details view:
	if (!empty($serial)) // if the 'record' parameter is present
		if (empty($displayType) AND (($recordConditionalSelector == "is equal to") OR (empty($recordConditionalSelector) AND is_numeric($serial)))) // if the 'displayType' parameter wasn't explicitly specified -AND- we're EITHER supposed to match record identifiers exactly OR '$recordConditionalSelector' wasn't specified and '$serial' is a number (which is the case for email announcement URLs: '.../show.php?record=12345')
			$displayType = "Display"; // display record details (instead of the default view)

	// Note that for 'show.php' we don't accept any other display types than '', 'List', 'Display', 'Cite', 'Export' and 'Browse',
	// if any other types were specified, we'll use the default view that's given in session variable 'userDefaultView':
	if (empty($displayType))
		$displayType = $_SESSION['userDefaultView']; // get the default view for the current user

	// shift some variable contents based on the value of '$recordIDSelector':
	if ($recordIDSelector == "call_number")
	{
		$callNumber = $serial; // treat content in '$serial' as call number
		$serial = "";
	}

	elseif ($recordIDSelector == "cite_key")
	{
		$citeKey = $serial; // treat content in '$serial' as cite key
		$serial = "";
	}


	// -------------------------------------------------------------------------------------------------------------------


	// Check the correct parameters have been passed:
	if (empty($serial) AND empty($date) AND empty($time) AND empty($year) AND empty($author) AND empty($title) AND empty($publication) AND empty($abbrevJournal) AND empty($keywords)
	    AND empty($abstract) AND empty($area) AND empty($expedition) AND empty($notes) AND empty($location) AND empty($type) AND empty($contributionID) AND empty($thesis) AND empty($without)
	    AND (empty($selected) OR (!empty($selected) AND empty($userID))) AND (empty($marked) OR (!empty($marked) AND empty($userID))) AND (empty($userKeys) OR (!empty($userKeys)
	    AND empty($userID))) AND (empty($userNotes) OR (!empty($userNotes) AND empty($userID))) AND (empty($userGroups) OR (!empty($userGroups) AND empty($userID))) AND
	    (empty($citeKey) OR (!empty($citeKey) AND empty($userID))) AND empty($callNumber) AND empty($where) AND (empty($browseByField) OR (!empty($browseByField)
	    AND $displayType != "Browse")))
	{
		// if 'show.php' was called without any valid parameters, we'll present a form where a user can input a record serial number.
		// Currently, this form will not present form elements for other supported options (like searching by date, year or author),
		// since this would just double search functionality from other search forms.

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString']))
			$HeaderString = "Display details for a particular record by entering its record identifier:"; // Provide the default message
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// Show the login status:
		showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

		// DISPLAY header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- " . $loc["ShowRecord"], "index,follow", "Search the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString);

		// Define variables holding drop-down elements, i.e. build properly formatted <option> tag elements:
		$dropDownConditionalsArray = array("is equal to"    => $loc["equal to"],
		                                   "contains"       => $loc["contains"],
		                                   "is within list" => $loc["is within list"]);

		$dropDownItems1 = buildSelectMenuOptions($dropDownConditionalsArray, "//", "\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'

		$dropDownFieldNameArray = array("serial" => $loc["DropDownFieldName_Serial"]);

		if (isset($_SESSION['loginEmail'])) // if a user is logged in
		{
			// add drop down items for user-specific record identifiers:
			$dropDownFieldNameArray["call_number"] = $loc["DropDownFieldName_MyCallNumber"];
			$dropDownFieldNameArray["cite_key"] = $loc["DropDownFieldName_MyCiteKey"];

			// adjust the width of the table cell holding the drop down:
			$recordIDCellWidth = "140";
		}
		else
			$recordIDCellWidth = "85";

		$dropDownItems2 = buildSelectMenuOptions($dropDownFieldNameArray, "//", "\t\t\t", true); // function 'buildSelectMenuOptions()' is defined in 'include.inc.php'

		// Build HTML elements that allow for search suggestions for text entered by the user:
		if (isset($_SESSION['userAutoCompletions']) AND ($_SESSION['userAutoCompletions'] == "yes"))
			$suggestElements = buildSuggestElements("recordID", "showSuggestions", "showSuggestProgress", "id-recordIDSelector-", "\t\t", "[';',',',' ']"); // function 'buildSuggestElements()' is defined in 'include.inc.php'
		else
			$suggestElements = "";

		// Start <form> and <table> holding the form elements:
		// 
		// TODO: use divs + CSS styling (instead of a table-based layout) for _all_ output, especially for 'viewType=Mobile'
?>

<form action="show.php" method="GET" name="show">
<input type="hidden" name="formType" value="show">
<input type="hidden" name="submit" value="<?php echo $loc["ButtonTitle_ShowRecord"]; ?>">
<input type="hidden" name="showLinks" value="1">
<input type="hidden" name="userID" value="<?php echo $loginUserID; // '$loginUserID' is made available globally by the 'start_session()' function ?>">
<table id="queryform" align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds a form that offers to show a record by its serial number, call number or cite key">
<tr>
	<td width="120">
		<div class="sect"><?php echo $loc["ShowRecord"]; ?>:</div>
	</td>
	<td width="<?php echo $recordIDCellWidth; ?>">
		<select id="recordIDSelector" name="recordIDSelector"><?php echo $dropDownItems2; ?>

		</select>
	</td>
	<td width="122">
		<select id="recordConditionalSelector" name="recordConditionalSelector"><?php echo $dropDownItems1; ?>

		</select>
	</td>
	<td>
		<input type="text" id="recordID" name="serial" value="" size="24"><?php echo $suggestElements; ?>

	</td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td><input type="submit" name="submit" value="<?php echo $loc["ButtonTitle_ShowRecord"]; ?>" title="display record details for the entered record identifier"></td>
</tr>
</table>
<table class="showhide" align="center" border="0" cellpadding="0" cellspacing="10" width="95%">
<tr>
	<td class="small" width="120" valign="top">
		<a href="javascript:toggleVisibility('helptxt','helpToggleimg','helpToggletxt','<?php echo rawurlencode($loc["HelpAndExamples"]); ?>')"<?php echo addAccessKey("attribute", "search_help"); ?> title="<?php echo $loc["LinkTitle_ToggleVisibility"] . addAccessKey("title", "search_help"); ?>">
			<img id="helpToggleimg" class="toggleimg" src="img/closed.gif" alt="<?php echo $loc["LinkTitle_ToggleVisibility"]; ?>" width="9" height="9" hspace="0" border="0">
			<span id="helpToggletxt" class="toggletxt"><?php echo $loc["HelpAndExamples"]; ?></span>
		</a>
	</td>
</tr>
</table>
<table id="helptxt" align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds some help text and example queries" style="display: none;">
<tr>
	<td width="120" valign="top">
		<div class="sect"><?php echo $loc["Help"]; ?>:</div>
	</td>
	<td class="helpbody" valign="top">
		<div class="even">
			If you know the record identifier (i.e. the database serial number<?php if (isset($_SESSION['loginEmail'])) { echo ", call number or cite key"; } ?>) for a specific record, you can enter it here, then press the <em>Show Record</em> button to jump directly to that record. While the <em>is equal to</em> option requires exact matches, the <em>contains</em> option allows for partial matches. You can use the <em>is within list</em> option to enter multiple record IDs (delimited by whitespace).
		</div>
	</td>
</tr>
<tr>
	<td width="120" valign="top">
		<div class="sect"><?php echo $loc["Examples"]; ?>:</div>
	</td>
	<td class="examples" valign="top">
		<div class="even">
			Jump to record number 12 and display its record details in Details view:
			<pre>[ serial ]   [ is equal to ]   12</pre>
		</div>
		<div class="odd">
			List records with serial numbers 1, 6 and 12:
			<pre>[ serial ]   [ is within list ]   1 6 12</pre>
		</div>
<?php
	if (isset($_SESSION['loginEmail'])) // if a user is logged in
	{
?>
		<div class="even">
			Find all records where any of my entries contains "lib" in the <em>call_number</em> field:
			<pre>[ my call_number ]   [ contains ]   lib</pre>
		</div>
		<div class="odd">
			Display records where my <em>cite_key</em> field equals "Meiners2002", "MaelkkiTamsalu1985" or "GranskogEtal2006":
			<pre>[ my cite_key ]   [ is within list ]   Meiners2002 MaelkkiTamsalu1985 GranskogEtal2006</pre>
		</div>
<?php
	}
?>
	</td>
</tr>
</table>
</form><?php

		// --------------------------------------------------------------------

		// DISPLAY THE HTML FOOTER:
		// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
		showPageFooter($HeaderString);

		displayHTMLfoot();

		// --------------------------------------------------------------------

	}


	// -------------------------------------------------------------------------------------------------------------------


	else // the script was called with at least one of the following parameters: 'record', 'records', 'date', 'time', 'year', 'author', 'title', 'publication', 'abbrev_journal', 'keywords', 'abstract', 'area', 'expedition', 'notes', 'location', 'type', 'contribution_id', 'thesis', 'without', 'selected', 'marked', 'user_keys', 'user_notes', 'user_groups', 'cite_key', 'call_number', 'where', 'by'
	{
		// CONSTRUCT SQL QUERY:
		// TODO: build the complete SQL query using functions 'buildFROMclause()' and 'buildORDERclause()'

		// Note: the 'verifySQLQuery()' function that gets called by 'search.php' to process query data with "$formType = sqlSearch" will add the user specific fields to the 'SELECT' clause
		// and the 'LEFT JOIN...' part to the 'FROM' clause of the SQL query if a user is logged in. It will also add 'orig_record', 'serial', 'file', 'url', 'doi', 'isbn' & 'type' columns
		// as required. Therefore it's sufficient to provide just the plain SQL query here:

		// Build SELECT clause:

		$additionalFields = "";

		if (preg_match("/^Cite$/i", $displayType))
		{
			// Note that the if clause below is very weak since it will break if "Text Citation" gets renamed or translated (when localized).
			// Problem: The above mentioned 'verifySQLQuery()' function requires that 'selected' is the only user-specific field present in the SELECT or WHERE clause of the SQL query.
			//          If this is not the case (as with 'cite_key' being added below) the passed user ID will be replaced with the ID of the currently logged in user.
			//          As a result, you won't be able to see your colleagues selected publications by using an URL like '../show.php?author=steffens&userID=2&selected=yes&submit=Cite&citeOrder=year'
			//          On the other hand, if the 'cite_key' field isn't included within the SELECT clause, user-specific cite keys can't be written out instead of serials when citing as "Text Citation".
			//          Since the latter is of minor importance we'll require $citeStyle == "Text Citation" here:
			if (!empty($userID)) // if the 'userID' parameter was specified...
				$additionalFields = "cite_key"; // add user-specific fields which are required in Citation view
		}
		elseif (!preg_match("/^Display$/i", $displayType)) // List view or Browse view
		{
			if (!empty($recordIDSelector)) // if a record identifier (either 'serial', 'call_number' or 'cite_key') was entered via the 'show.php' web form
				$additionalFields = escapeSQL($recordIDSelector); // display the appropriate column
		}

		if ((preg_match("/^Display$/i", $displayType)) AND (isset($_SESSION['lastDetailsViewQuery']))) // get SELECT clause from any previous Details view query:
			$query = "SELECT " . extractSELECTclause($_SESSION['lastDetailsViewQuery']); // function 'extractSELECTclause()' is defined in 'include.inc.php'
		else // generate new SELECT clause:
			$query = buildSELECTclause($displayType, $showLinks, $additionalFields, false, false, "", $browseByField); // function 'buildSELECTclause()' is defined in 'include.inc.php'

		// Build FROM clause:
		// We'll explicitly add the 'LEFT JOIN...' part to the 'FROM' clause of the SQL query if '$userID' isn't empty. This is done since the 'verifySQLQuery()' function
		// (mentioned above) excludes the 'selected' field from its magic. By that we allow the 'selected' field to be queried by any user (using 'show.php')
		// (e.g., by URLs of the form: 'show.php?author=...&userID=...&selected=yes').
		if (!empty($userID)) // the 'userID' parameter was specified -> we include user specific fields
			$query .= " FROM $tableRefs LEFT JOIN $tableUserData ON serial = record_id AND user_id = " . quote_smart($userID); // add FROM clause (including the 'LEFT JOIN...' part); '$tableRefs' and '$tableUserData' are defined in 'db.inc.php'
		else
			$query .= " FROM $tableRefs"; // add FROM clause


		// Build WHERE clause:
		$query .= " WHERE";

		$multipleParameters = false;

		// serial/record:
		if (!empty($serial))
		{
			// first, check if the user is allowed to display any record details:
			if (preg_match("/^Display$/i", $displayType) AND isset($_SESSION['user_permissions']) AND !preg_match("/allow_details_view/", $_SESSION['user_permissions'])) // no, the 'user_permissions' session variable does NOT contain 'allow_details_view'...
			{
				// return an appropriate error message:
				$HeaderString = returnMsg($loc["NoPermission"] . $loc["NoPermission_ForDisplayDetails"] . "!", "warning", "strong", "HeaderString"); // function 'returnMsg()' is defined in 'include.inc.php'

				if (!preg_match("/^cli/i", $client))
					header("Location: show.php"); // redirect back to 'show.php'

				exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
			}

			$query .= connectConditionals();

			if ($recordConditionalSelector == "is equal to")
				$query .= " serial = " . quote_smart($serial);

			elseif ($recordConditionalSelector == "is within list")
			{
				// replace any non-digit chars with "|":
				$serial = preg_replace("/\D+/", "|", $serial);
				// strip "|" from beginning/end of string (if any):
				$serial = preg_replace("/^\|?(.+?)\|?$/", "\\1", $serial);

				$query .= " serial RLIKE " . quote_smart("^(" . $serial . ")$");
			}

			else // $recordConditionalSelector == "contains"
				$query .= " serial RLIKE " . quote_smart($serial);
		}

		// date + time:
		if (!empty($date) AND !empty($time)) // if both, 'date' AND 'time' parameters are present:
		{
			if ($when == "edited")
			{
				$queryDateField = "modified_date";
				$queryTimeField = "modified_time";
			}
			else
			{
				$queryDateField = "created_date";
				$queryTimeField = "created_time";
			}

			if ($range == "after")
			{
				// return all records whose created/modified time is after '$time' of the given '$date' -OR- where the created/modified date is after '$date':
				$searchOperatorDate = ">";
				$searchOperatorTime = ">";
			}

			elseif ($range == "equal_or_after")
			{
				// return all records whose created/modified time is equal or after '$time' of the given '$date' -OR- where the created/modified date is after '$date':
				$searchOperatorDate = ">";
				$searchOperatorTime = ">=";
			}

			elseif ($range == "before")
			{
				// return all records whose created/modified time is before '$time' of the given '$date' -OR- where the created/modified date is before '$date':
				$searchOperatorDate = "<";
				$searchOperatorTime = "<";
			}

			elseif ($range == "equal_or_before")
			{
				// return all records whose created/modified time is equal or before '$time' of the given '$date' -OR- where the created/modified date is before '$date':
				$searchOperatorDate = "<";
				$searchOperatorTime = "<=";
			}

			else
			{
				// return all records whose created/modified date & time matches exactly '$date' and '$time':
				$searchOperatorDate = "=";
				$searchOperatorTime = "=";
			}

			$query .= connectConditionals();

			if (($searchOperatorDate == "=") AND ($searchOperatorTime == "="))
				$query .= " " . $queryDateField . " = " . quote_smart($date) . " AND " . $queryTimeField . " = " . quote_smart($time);
			else
				$query .= " ((" . $queryDateField . " = " . quote_smart($date) . " AND " . $queryTimeField . " " . $searchOperatorTime . " " . quote_smart($time) . ") OR " . $queryDateField . " " . $searchOperatorDate . " " . quote_smart($date) . ")";
		}

		// date:
		elseif (!empty($date)) // if only the 'date' parameter is present (and not the 'time' parameter):
		{
			if ($range == "after")
				$searchOperator = ">"; // return all records whose created/modified date is after '$date'
			elseif ($range == "equal_or_after")
				$searchOperator = ">="; // return all records whose created/modified date equals or is after '$date'
			elseif ($range == "before")
				$searchOperator = "<"; // return all records whose created/modified date is before '$date'
			elseif ($range == "equal_or_before")
				$searchOperator = "<="; // return all records whose created/modified date equals or is before '$date'
			else
				$searchOperator = "="; // return all records whose created/modified date matches exactly '$date'

			$query .= connectConditionals();

			if ($when == "edited")
				$query .= " modified_date " . $searchOperator . " " . quote_smart($date);
			else
				$query .= " created_date " . $searchOperator . " " . quote_smart($date);
		}

		// time:
		elseif (!empty($time)) // if only the 'time' parameter is present (and not the 'date' parameter):
		{
			if ($range == "after")
				$searchOperator = ">"; // return all records whose created/modified time is after '$time'
			elseif ($range == "equal_or_after")
				$searchOperator = ">="; // return all records whose created/modified time equals or is after '$time'
			elseif ($range == "before")
				$searchOperator = "<"; // return all records whose created/modified time is before '$time'
			elseif ($range == "equal_or_before")
				$searchOperator = "<="; // return all records whose created/modified time equals or is before '$time'
			else
				$searchOperator = "="; // return all records whose created/modified time matches exactly '$time'

			$query .= connectConditionals();

			if ($when == "edited")
				$query .= " modified_time " . $searchOperator . " " . quote_smart($time);
			else
				$query .= " created_time " . $searchOperator . " " . quote_smart($time);
		}

		// year:
		if (!empty($year))
		{
			$query .= connectConditionals();
			$query .= " year RLIKE " . quote_smart($year);
		}

		// author:
		if (!empty($author))
		{
			$query .= connectConditionals();
			$query .= " author RLIKE " . quote_smart($author);
		}

		// without:
		if (!empty($without))
		{
			$query .= connectConditionals();
			if (preg_match("/^dups$/i", $without))
				$query .= " (orig_record IS NULL OR orig_record < 0)";
		}

		// title:
		if (!empty($title))
		{
			$query .= connectConditionals();
			$query .= " title RLIKE " . quote_smart($title);
		}

		// publication:
		if (!empty($publication))
		{
			$query .= connectConditionals();
			$query .= " publication RLIKE " . quote_smart($publication);
		}

		// abbrev_journal:
		if (!empty($abbrevJournal))
		{
			$query .= connectConditionals();
			$query .= " abbrev_journal RLIKE " . quote_smart($abbrevJournal);
		}

		// keywords:
		if (!empty($keywords))
		{
			$query .= connectConditionals();
			$query .= " keywords RLIKE " . quote_smart($keywords);
		}

		// abstract:
		if (!empty($abstract))
		{
			$query .= connectConditionals();
			$query .= " abstract RLIKE " . quote_smart($abstract);
		}

		// area:
		if (!empty($area))
		{
			$query .= connectConditionals();
			$query .= " area RLIKE " . quote_smart($area);
		}

		// expedition:
		if (!empty($expedition))
		{
			$query .= connectConditionals();
			$query .= " expedition RLIKE " . quote_smart($expedition);
		}

		// notes:
		if (!empty($notes))
		{
			$query .= connectConditionals();
			$query .= " notes RLIKE " . quote_smart($notes);
		}

		// location:
		if (!empty($location))
		{
			$query .= connectConditionals();
			$query .= " location RLIKE " . quote_smart($location);
		}

		// type:
		if (!empty($type))
		{
			$query .= connectConditionals();
			$query .= " type RLIKE " . quote_smart($type);
		}

		// contribution_id:
		if (!empty($contributionID))
		{
			$query .= connectConditionals();
			$query .= " contribution_id RLIKE " . quote_smart($contributionID);
		}

		// thesis:
		if (!empty($thesis))
		{
			$query .= connectConditionals();

			if ($thesis == "yes")
				$query .= " thesis RLIKE \".+\"";				
			elseif ($thesis == "no")
				$query .= " (thesis IS NULL OR thesis = \"\")";
			else
				$query .= " thesis RLIKE " . quote_smart($thesis);
		}

		// selected:
		if (!empty($selected) AND !empty($userID))
		{
			$query .= connectConditionals();
			$query .= " selected RLIKE " . quote_smart($selected); // we use 'selected RLIKE "..."' instead of 'selected = "..."' to allow command line utilities to query for '-s=.+' which will display records with 'selected=yes' AND with 'selected=no'
		}

		// marked:
		if (!empty($marked) AND !empty($userID))
		{
			$query .= connectConditionals();
			$query .= " marked RLIKE " . quote_smart($marked); // regarding the use of RLIKE, see note for 'selected'
		}

		// user_notes:
		if (!empty($userNotes) AND !empty($userID))
		{
			$query .= connectConditionals();
			$query .= " user_notes RLIKE " . quote_smart($userNotes);
		}

		// user_groups:
		if (!empty($userGroups) AND !empty($userID))
		{
			$query .= connectConditionals();
			$query .= " user_groups RLIKE " . quote_smart($userGroups);
		}

		// user_keys:
		if (!empty($userKeys) AND !empty($userID))
		{
			$query .= connectConditionals();
			$query .= " user_keys RLIKE " . quote_smart($userKeys);
		}

		// cite_key:
		if (!empty($citeKey) AND !empty($userID))
		{
			$query .= connectConditionals();

			if ($recordConditionalSelector == "is equal to")
				$query .= " cite_key = " . quote_smart($citeKey);

			elseif ($recordConditionalSelector == "is within list")
			{
				$citeKey = preg_quote($citeKey, ""); // escape any meta characters
				// replace any whitespace characters with "|":
				$citeKey = preg_replace("/\s+/", "|", $citeKey);
				// strip "|" from beginning/end of string (if any):
				$citeKey = preg_replace("/^\|?(.+?)\|?$/", "\\1", $citeKey);

				$query .= " cite_key RLIKE " . quote_smart("^(" . $citeKey . ")$");
			}

			else // $recordConditionalSelector == "contains"
				$query .= " cite_key RLIKE " . quote_smart($citeKey);
		}

		// call_number:
		if (!empty($callNumber))
		{
			$query .= connectConditionals();

			// since 'show.php' will only allow a user to query his own call numbers we need to build a complete call number prefix (e.g. 'IPÖ @ msteffens') that's appropriate for this user:
			$callNumberPrefix = getCallNumberPrefix(); // function 'getCallNumberPrefix()' is defined in 'include.inc.php'

			if ($recordConditionalSelector == "is equal to")
				$query .= " call_number RLIKE " . quote_smart("(^|.*;) *" . $callNumberPrefix . " @ " . $callNumber . " *(;.*|$)");

			elseif ($recordConditionalSelector == "is within list")
			{
				$callNumber = preg_quote($callNumber, ""); // escape any meta characters
				// replace any whitespace characters with "|":
				$callNumber = preg_replace("/\s+/", "|", $callNumber);
				// strip "|" from beginning/end of string (if any):
				$callNumber = preg_replace("/^\|?(.+?)\|?$/", "\\1", $callNumber);

				$query .= " call_number RLIKE " . quote_smart("(^|.*;) *" . $callNumberPrefix . " @ (" . $callNumber . ") *(;.*|$)");
			}

			else // $recordConditionalSelector == "contains"
				$query .= " call_number RLIKE " . quote_smart($callNumberPrefix . " @ [^@;]*" . $callNumber . "[^@;]*");
		}

		// where:
		if (!empty($where))
		{
			$query .= connectConditionals();

			$sanitizedWhereClause = extractWHEREclause(" WHERE " . $where); // attempt to sanitize custom WHERE clause from SQL injection attacks (function 'extractWHEREclause()' is defined in 'include.inc.php')
			$query .= " (" . $sanitizedWhereClause . ")"; // add custom WHERE clause
		}

		// If, for some odd reason, 'records=all' was passed together with other parameters (such as in '.../show.php?records=all&author=steffens') we'll remove again
		// the generic WHERE clause part (i.e. ' serial RLIKE ".+"') from the query since its superfluous and would confuse other features (such as the "Seach within Results" functionality):
		if (preg_match('/WHERE serial RLIKE "\.\+" AND/i', $query))
			$query = preg_replace('/WHERE serial RLIKE "\.\+" AND/i', 'WHERE', $query); // remove superfluous generic WHERE clause

		elseif (preg_match("/WHERE$/i", $query)) // if still no WHERE clause was added (which is the case for URLs like 'show.php?submit=Browse&by=author')
			$query .= " serial RLIKE \".+\""; // add generic WHERE clause


		// Build GROUP BY clause:
		if (preg_match("/^Browse$/i", $displayType))
			$query .= " GROUP BY " . escapeSQL($browseByField); // for Browse view, group records by the chosen field


		// Build ORDER BY clause:
		if (preg_match("/^Browse$/i", $displayType))
		{
			$query .= " ORDER BY records DESC, " . escapeSQL($browseByField);
		}
		else
		{
			if ($citeOrder == "year")
				$query .= " ORDER BY year DESC, first_author, author_count, author, title"; // sort records first by year (descending), then in the usual way

			elseif ($citeOrder == "type") // sort records first by record type (and thesis type), then in the usual way:
				$query .= " ORDER BY type DESC, thesis DESC, first_author, author_count, author, year, title";

			elseif ($citeOrder == "type-year") // sort records first by record type (and thesis type), then by year (descending), then in the usual way:
				$query .= " ORDER BY type DESC, thesis DESC, year DESC, first_author, author_count, author, title";

			elseif ($citeOrder == "creation-date") // sort records such that newly added/edited records get listed top of the list:
				$query .= " ORDER BY created_date DESC, created_time DESC, modified_date DESC, modified_time DESC, serial DESC";

			else // if any other or no 'citeOrder' parameter is specified
			{
				if (!empty($recordIDSelector)) // if a record identifier (either 'serial', 'call_number' or 'cite_key') was entered via the 'show.php' web form
					$query .= " ORDER BY " . escapeSQL($recordIDSelector) . ", author, year DESC, publication"; // sort by the appropriate column

				else // supply the default ORDER BY clause:
				{
					if (preg_match("/^Cite$/i", $displayType))
						$query .= " ORDER BY first_author, author_count, author, year, title";
					else
						$query .= " ORDER BY author, year DESC, publication";
				}
			}
		}

		// Build the correct query URL:
		// (we skip unnecessary parameters here since 'search.php' will use it's default values for them)
		$queryParametersArray = array("sqlQuery"         => $query,
		                              "client"           => $client,
		                              "formType"         => "sqlSearch",
		                              "submit"           => $displayType,
		                              "viewType"         => $viewType,
		                              "showQuery"        => $showQuery,
		                              "showLinks"        => $showLinks,
		                              "showRows"         => $showRows,
		                              "rowOffset"        => $rowOffset,
		                              "wrapResults"      => $wrapResults,
		                              "citeOrder"        => $citeOrder,
		                              "citeStyle"        => $citeStyle,
		                              "exportFormat"     => $exportFormat,
		                              "exportType"       => $exportType,
		                              "exportStylesheet" => $exportStylesheet,
		                              "citeType"         => $citeType,
		                              "headerMsg"        => $headerMsg
		                             );

		// Save the URL of the current 'show.php' request to the 'referer' session variable:
		// NOTE: since function 'start_session()' prefers '$_SESSION['referer']' over '$_SERVER['HTTP_REFERER']', this means that '$referer'
		//       contains a 'show.php' URL and not e.g. a '*_search.php' URL; this, in turn, can prevent the "NoPermission_ForSQL" warning
		//       if a user clicked the "Show All" link in the header of any of the '*_search.php' pages
		//       (see notes above the "NoPermission_ForSQL" error message in 'search.php')
//		if (isset($_SERVER['REQUEST_URI']))
//			saveSessionVariable("referer", $_SERVER['REQUEST_URI']); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Call 'search.php' in order to display record details:
		if ($_SERVER['REQUEST_METHOD'] == "POST")
		{
			// save POST data to session variable:
			// NOTE: If the original request was a POST (as is the case for the refbase command line client) saving POST data to a session
			//       variable allows to retain large param/value strings (that would exceed the maximum string limit for GET requests).
			//       'search.php' will then write the saved POST data back to '$_POST' and '$_REQUEST'. (see also note and commented code below)
			saveSessionVariable("postData", $queryParametersArray);

			header("Location: search.php?client=" . $client); // we also pass the 'client' parameter in the GET request so that it's available to 'search.php' before sessions are initiated
		}
		else
		{
			$queryURL = generateURL("search.php", "html", $queryParametersArray, false); // function 'generateURL()' is defined in 'include.inc.php'

			header("Location: $queryURL");
		}

		// NOTE: If the original request was a POST (as is the case for the refbase command line client), we must also pass the data via POST to 'search.php'
		//       in order to retain large param/value strings (that would exceed the maximum string limit for GET requests). We could POST the data via function
		//       'sendPostRequest()' as shown in the commented code below. However, the problem with this is that this does NOT *redirect* to 'search.php' but
		//       directly prints results from within this script ('show.php'). Also, the printed results include the full HTTP response, including the HTTP header.
//		$queryURL = "";
//		foreach ($queryParametersArray as $varname => $value)
//			$queryURL .= "&" . $varname . "=" . rawurlencode($value);
//		$queryURL = trimTextPattern($queryURL, "&", true, false); // remove again param delimiter from beginning of query URL (function 'trimTextPattern()' is defined in 'include.inc.php')
//
//		if ($_SERVER['REQUEST_METHOD'] == "POST") // redirect via a POST request:
//		{
//			// extract the host & path on server from the base URL:
//			$host = preg_replace("#^[^:]+://([^/]+).*#", "\\1", $databaseBaseURL); // variable '$databaseBaseURL' is defined in 'ini.inc.php'
//			$path = preg_replace("#^[^:]+://[^/]+(/.*)#", "\\1", $databaseBaseURL);
//
//			// send POST request:
//			$httpResult = sendPostRequest($host, $path . "search.php", $databaseBaseURL . "show.php", $queryURL); // function 'sendPostRequest()' is defined in 'include.inc.php'
//			echo $httpResult;
//		}
//		else // redirect via a GET request:
//			header("Location: search.php?$queryURL");
	}


	// -------------------------------------------------------------------------------------------------------------------


	// this function will connect multiple WHERE clause parts with " AND" if required:
	function connectConditionals()
	{
		global $multipleParameters;
		global $queryType;

		if ($multipleParameters)
		{
			$queryConnector = " " . $queryType;
		}
		else
		{
			$queryConnector = "";
			$multipleParameters = true;
		}

		return $queryConnector;
	}
?>
