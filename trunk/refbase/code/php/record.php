<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./record.php
	// Created:    29-Jul-02, 16:39
	// Modified:   03-Oct-04, 21:27

	// Form that offers to add
	// records or edit/delete
	// existing ones.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'header.inc.php'; // include header
	include 'footer.inc.php'; // include footer
	include 'include.inc.php'; // include common functions
	include "ini.inc.php"; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// Extract session variables:
	if (isset($_SESSION['errors']))
	{
		$errors = $_SESSION['errors']; // read session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current error message is still available to this script via '$errors':
		deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}
	else
		$errors = array(); // initialize the '$errors' variable in order to prevent 'Undefined variable...' messages

	if (isset($_SESSION['formVars']))
	{
		$formVars = $_SESSION['formVars']; // read session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current form variables are still available to this script via '$formVars':
		deleteSessionVariable("formVars"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}
	else
		$formVars = array();

	// --------------------------------------------------------------------

	if (isset($_REQUEST['recordAction']))
		$recordAction = $_REQUEST['recordAction']; // check whether the user wants to *add* a record or *edit* an existing one
	else
		$recordAction = ""; // if the 'recordAction' parameter wasn't set we set the '$recordAction' variable to the empty string ("") to prevent 'Undefined index: recordAction...' notification messages

	if (isset($_REQUEST['mode']))
		$mode = $_REQUEST['mode']; // check whether the user wants to add a record by use of an *import* form (e.g., the parameter "mode=import" will be set by 'import_csa.php')
	else
		$mode = ""; // if the 'mode' parameter wasn't set we set the '$mode' variable to the empty string ("") to prevent 'Undefined index: mode...' notification messages

	if (isset($_REQUEST['importSource']))
		$importSource = $_REQUEST['importSource']; // get the source from which the imported data originate (e.g., if data have been imported via 'import_csa.php', the 'importSource' value will be 'csa')
	else
		$importSource = ""; // if the 'importSource' parameter wasn't set we set the '$importSource' variable to the empty string ("") to prevent 'Undefined index: importSource...' notification messages

	if (isset($_REQUEST['serialNo']))
		$serialNo = $_REQUEST['serialNo']; // fetch the serial number of the record to edit
	else
		$serialNo = ""; // this is actually unneccessary, but we do it for clarity reasons here

	if (isset($_REQUEST['oldQuery']))
		$oldQuery = $_REQUEST['oldQuery']; // fetch the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	else
		$oldQuery = ""; // if the 'oldQuery' parameter wasn't set we set the '$oldQuery' variable to the empty string ("") to prevent 'Undefined index: oldQuery...' notification messages
	
	$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "
	$oldQuery = ereg_replace('(\\\\)+','\\\\',$oldQuery);

	// Setup some required variables:

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString'])) // if there's no stored message available
	{
		if (empty($errors)) // provide one of the default messages:
		{
			$errors = array(); // re-assign an empty array (in order to prevent 'Undefined variable "errors"...' messages when calling the 'fieldError' function later on)
			if ($recordAction == "edit") // *edit* record
				$HeaderString = "Edit the following record:";
			else // *add* record will be the default action if no parameter is given
			{
				$HeaderString = "Add a record to the database";
				if (isset($_REQUEST['source'])) // when importing data, we display the original source data if the 'source' parameters is present:
					$HeaderString .= ". Original source data:\n<br>\n<br>\n<code>" . $_REQUEST['source'] . "</code>"; // the 'source' parameter gets passed by 'import_csa.php'
				else
					$HeaderString .= ":";
			}
		}
		else // -> there were errors validating the data entered by the user
			$HeaderString = "<b><span class=\"warning\">There were validation errors regarding the data you entered. Please check the comments above the respective fields:</span></b>";
	}
	else // there is already a stored message available
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

	// if the user isn't logged in -OR- any normal user is logged in (not the admin)...
	if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail)))
		$fieldLock = " readonly"; // ... lock the 'location' & 'file' fields
	else // if the admin is logged in...
		$fieldLock = ""; // ...the 'location' & 'file' fields won't be locked (since the admin should be able to freely add or edit any records)

	if ($recordAction == "edit") // *edit* record
	{
		$pageTitle = "Edit Record"; // set the correct page title
	}
	else
	{
		$recordAction = "add"; // *add* record will be the default action if no parameter is given
		$pageTitle = "Add Record"; // set the correct page title
		$serialNo = "(not assigned yet)";

		// if the user isn't logged in -OR- any normal user is logged in (not the admin)...
		if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail)))
			// ...provide a generic info string within the (locked) 'location' field that informs the user about the automatic fill in of his user name & email address
			// (IMPORTANT: if you change this information string you must also edit the corresponding 'ereg(...)' pattern in 'modify.php'!)
			$locationName = "(your name &amp; email address will be filled in automatically)";
		else // if the admin is logged in...
			$locationName = ""; // ...keep the 'location' field empty
	}

	if (isset($loginEmail)) // if a user is logged in
	{
		$loginEmailArray = split("@", $loginEmail); // split the login email address at '@'
		$loginEmailUserName = $loginEmailArray[0]; // extract the user name (which is the first element of the array '$loginEmailArray')
		$callNumberPrefix = $abbrevInstitution . " @ " . $loginEmailUserName; // we make use of the session variable '$abbrevInstitution' to construct a correct call number prefix, like: 'IP� @ msteffens'
	}

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:
	// if the script was called with parameters (like: 'record.php?recordAction=edit&serialNo=...')
	if ($recordAction == "edit")
	{
		// for the selected record, select *all* available fields:
		// (note: we also add the 'serial' column at the end in order to provide standardized input [compare processing of form 'sql_search.php' in 'search.php'])
		if (isset($_SESSION['loginEmail'])) // if a user is logged in, show user specific fields:
			$query = "SELECT author, title, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, approved, notes, file, serial, location, type, call_number, created_date, created_time, created_by, modified_date, modified_time, modified_by, orig_record, contribution_id, online_publication, online_citation, marked, copy, selected, user_keys, user_notes, user_file, user_groups, bibtex_id, related, serial, url, doi"
					. " FROM refs LEFT JOIN user_data ON serial = record_id AND user_id =" . $loginUserID . " WHERE serial RLIKE \"^(" . $serialNo . ")$\""; // since we'll only fetch one record, the ORDER BY clause is obsolete here
		else // if NO user logged in, don't display any user specific fields:
			$query = "SELECT author, title, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, approved, notes, file, serial, location, type, call_number, created_date, created_time, created_by, modified_date, modified_time, modified_by, orig_record, contribution_id, online_publication, online_citation, serial, url, doi"
					. " FROM refs WHERE serial RLIKE \"^(" . $serialNo . ")$\""; // since we'll only fetch one record, the ORDER BY clause is obsolete here
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// Initialize some variables (to prevent "Undefined variable..." messages):
	$isEditorCheckBox = "";
	$contributionIDCheckBox = "";
	$locationSelectorName = "";

	if ($recordAction == "edit" AND empty($errors))
		{
			// (3a) RUN the query on the database through the connection:
			$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

			if (@ mysql_num_rows($result) == 1) // this condition is added here to avoid the case that clicking on a search result item which got deleted in the meantime invokes a seemingly correct but empty 'edit record' search form
			{
				// (3b) EXTRACT results:
				$row = mysql_fetch_array($result); //fetch the current row into the array $row (it'll be always *one* row, but anyhow)
				
				// fetch attributes of the current record into variables:
				$authorName = htmlentities($row['author']);
				$titleName = htmlentities($row['title']);
				$yearNo = htmlentities($row['year']);
				$publicationName = htmlentities($row['publication']);
				$abbrevJournalName = htmlentities($row['abbrev_journal']);
				$volumeNo = htmlentities($row['volume']);
				$issueNo = htmlentities($row['issue']);
				$pagesNo = htmlentities($row['pages']);
				$addressName = htmlentities($row['address']);
				$corporateAuthorName = htmlentities($row['corporate_author']);
				$keywordsName = htmlentities($row['keywords']);
				$abstractName = htmlentities($row['abstract']);
				$publisherName = htmlentities($row['publisher']);
				$placeName = htmlentities($row['place']);
				$editorName = htmlentities($row['editor']);
				$languageName = htmlentities($row['language']);
				$summaryLanguageName = htmlentities($row['summary_language']);
				$origTitleName = htmlentities($row['orig_title']);
				$seriesEditorName = htmlentities($row['series_editor']);
				$seriesTitleName = htmlentities($row['series_title']);
				$abbrevSeriesTitleName = htmlentities($row['abbrev_series_title']);
				$seriesVolumeNo = htmlentities($row['series_volume']);
				$seriesIssueNo = htmlentities($row['series_issue']);
				$editionNo = htmlentities($row['edition']);
				$issnName = htmlentities($row['issn']);
				$isbnName = htmlentities($row['isbn']);
				$mediumName = htmlentities($row['medium']);
				$areaName = htmlentities($row['area']);
				$expeditionName = htmlentities($row['expedition']);
				$conferenceName = htmlentities($row['conference']);
				$notesName = htmlentities($row['notes']);
				$approvedRadio = htmlentities($row['approved']);
				$locationName = htmlentities($row['location']);
				$callNumberName = $row['call_number']; // contents of the 'call_number' field will get encoded depending on who's logged in (normal user vs. admin)
													// (for normal users being logged in, the field's contents won't get HTML encoded at all, since the data will
													//  get *rawurlencoded* when including them within a hidden form tag; for the admin being logged in, the data
													//  will get HTML encoded below)

				// if a normal user is logged in, we'll only display the user's *own* call number within the 'call_number' field:
				if ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail))
				{
					if (ereg("(^|.*;) *$callNumberPrefix *@ +([^@;]+)", $callNumberName)) // if the user's call number prefix occurs within the contents of the 'call_number' field
					{
						$callNumberNameUserOnly = ereg_replace("(^|.*;) *$callNumberPrefix *@ +([^@;]+).*", "\\2", $callNumberName); // extract the user's *own* call number from the full contents of the 'call_number' field
						$callNumberNameUserOnly = htmlentities($callNumberNameUserOnly);
					}
					else
						$callNumberNameUserOnly = "";
				}
				elseif ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail)) // admin logged in
				{
					$callNumberNameUserOnly = ""; // the 'call_number' field will be empty if no user is logged in (note that '$callNumberNameUserOnly' won't be used at all, if the admin is logged in)
					$callNumberName = htmlentities($callNumberName); // if the admin is logged in we display the full contents of the 'call_number' field, so we'll need to HTML encode the data
				}
				else // nobody logged in
				{
					$callNumberNameUserOnly = ""; // the 'call_number' field will be empty if no user is logged in (note that '$callNumberNameUserOnly' won't be used at all, if the admin is logged in)
					// note that, as for normal users being logged in, the call number field contents won't get HTML encoded here, since the data will get *rawurlencoded* when including them within a hidden form tag
				}

				$serialNo = htmlentities($row['serial']);
				$typeName = htmlentities($row['type']);
				$thesisName = htmlentities($row['thesis']);

				if (isset($row['marked'])) // 'marked' field is only provided if a user is logged in
					$markedRadio = htmlentities($row['marked']);
				else
					$markedRadio = "";

				if (isset($row['copy'])) // 'copy' field is only provided if a user is logged in
					$copyName = htmlentities($row['copy']);
				else
					$copyName = "";

				if (isset($row['selected'])) // 'selected' field is only provided if a user is logged in
					$selectedRadio = htmlentities($row['selected']);
				else
					$selectedRadio = "";

				if (isset($row['user_keys'])) // 'user_keys' field is only provided if a user is logged in
					$userKeysName = htmlentities($row['user_keys']);
				else
					$userKeysName = "";

				if (isset($row['user_notes'])) // 'user_notes' field is only provided if a user is logged in
					$userNotesName = htmlentities($row['user_notes']);
				else
					$userNotesName = "";

				if (isset($row['user_file'])) // 'user_file' field is only provided if a user is logged in
					$userFileName = htmlentities($row['user_file']);
				else
					$userFileName = "";

				if (isset($row['user_groups'])) // 'user_groups' field is only provided if a user is logged in
					$userGroupsName = htmlentities($row['user_groups']);
				else
					$userGroupsName = "";

				if (isset($row['bibtex_id'])) // 'bibtex_id' field is only provided if a user is logged in
					$bibtexIDName = htmlentities($row['bibtex_id']);
				else
					$bibtexIDName = "";

				if (isset($row['related'])) // 'related' field is only provided if a user is logged in
					$relatedName = htmlentities($row['related']);
				else
					$relatedName = "";

				$fileName = htmlentities($row['file']);
				$urlName = htmlentities($row['url']);
				$doiName = htmlentities($row['doi']);
				$contributionID = $row['contribution_id'];
				$onlinePublication = $row['online_publication'];
				$onlineCitationName = $row['online_citation'];
				$createdDate = $row['created_date'];
				$createdTime = $row['created_time'];
				$createdBy = htmlentities($row['created_by']);
				$modifiedDate = $row['modified_date'];
				$modifiedTime = $row['modified_time'];
				$modifiedBy = htmlentities($row['modified_by']);
				$origRecord = $row['orig_record'];
			}
			else
				showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

		}
	else // if ($recordAction == "add") -OR- ($recordAction == "edit" but there were some errors on submit)
		{
			if ($recordAction == "add" AND $mode == "import" AND empty($errors)) // if the user wants to import record data by use of an import form (like 'import_csa.php')
			{
				// read field data from a GET/POST request:
				if (isset($_REQUEST['author']))
					$authorName = $_REQUEST['author'];
				else
					$authorName = "";

				if (isset($_REQUEST['title']))
					$titleName = $_REQUEST['title'];
				else
					$titleName = "";

				if (isset($_REQUEST['year']))
					$yearNo = $_REQUEST['year'];
				else
					$yearNo = "";

				if (isset($_REQUEST['publication']))
					$publicationName = $_REQUEST['publication'];
				else
					$publicationName = "";

				if (isset($_REQUEST['abbrev_journal']))
					$abbrevJournalName = $_REQUEST['abbrev_journal'];
				else
					$abbrevJournalName = "";

				if (isset($_REQUEST['volume']))
					$volumeNo = $_REQUEST['volume'];
				else
					$volumeNo = "";

				if (isset($_REQUEST['issue']))
					$issueNo = $_REQUEST['issue'];
				else
					$issueNo = "";

				if (isset($_REQUEST['pages']))
					$pagesNo = $_REQUEST['pages'];
				else
					$pagesNo = "";

				if (isset($_REQUEST['address']))
					$addressName = $_REQUEST['address'];
				else
					$addressName = "";

				if (isset($_REQUEST['corporate_author']))
					$corporateAuthorName = $_REQUEST['corporate_author'];
				else
					$corporateAuthorName = "";

				if (isset($_REQUEST['keywords']))
					$keywordsName = $_REQUEST['keywords'];
				else
					$keywordsName = "";

				if (isset($_REQUEST['abstract']))
					$abstractName = $_REQUEST['abstract'];
				else
					$abstractName = "";

				if (isset($_REQUEST['publisher']))
					$publisherName = $_REQUEST['publisher'];
				else
					$publisherName = "";

				if (isset($_REQUEST['place']))
					$placeName = $_REQUEST['place'];
				else
					$placeName = "";

				if (isset($_REQUEST['editor']))
					$editorName = $_REQUEST['editor'];
				else
					$editorName = "";

				if (isset($_REQUEST['language']))
					$languageName = $_REQUEST['language'];
				else
					$languageName = "";

				if (isset($_REQUEST['summary_language']))
					$summaryLanguageName = $_REQUEST['summary_language'];
				else
					$summaryLanguageName = "";

				if (isset($_REQUEST['orig_title']))
					$origTitleName = $_REQUEST['orig_title'];
				else
					$origTitleName = "";

				if (isset($_REQUEST['series_editor']))
					$seriesEditorName = $_REQUEST['series_editor'];
				else
					$seriesEditorName = "";

				if (isset($_REQUEST['series_title']))
					$seriesTitleName = $_REQUEST['series_title'];
				else
					$seriesTitleName = "";

				if (isset($_REQUEST['abbrev_series_title']))
					$abbrevSeriesTitleName = $_REQUEST['abbrev_series_title'];
				else
					$abbrevSeriesTitleName = "";

				if (isset($_REQUEST['series_volume']))
					$seriesVolumeNo = $_REQUEST['series_volume'];
				else
					$seriesVolumeNo = "";

				if (isset($_REQUEST['series_issue']))
					$seriesIssueNo = $_REQUEST['series_issue'];
				else
					$seriesIssueNo = "";

				if (isset($_REQUEST['edition']))
					$editionNo = $_REQUEST['edition'];
				else
					$editionNo = "";

				if (isset($_REQUEST['issn']))
					$issnName = $_REQUEST['issn'];
				else
					$issnName = "";

				if (isset($_REQUEST['isbn']))
					$isbnName = $_REQUEST['isbn'];
				else
					$isbnName = "";

				$mediumName = "";

				if (isset($_REQUEST['area']))
					$areaName = $_REQUEST['area'];
				else
					$areaName = "";

				$expeditionName = "";

				if (isset($_REQUEST['conference']))
					$conferenceName = $_REQUEST['conference'];
				else
					$conferenceName = "";

				if (isset($_REQUEST['notes']))
					$notesName = $_REQUEST['notes'];
				else
					$notesName = "";

				$approvedRadio = "";
				$locationName = $locationName; // supply some generic info: "(...will be filled in automatically)" [as defined at the top of this script]
				$callNumberName = "";
				$callNumberNameUserOnly = "";
				$serialNo = $serialNo; // supply some generic info: "(not assigned yet)" [as defined at the top of this script]

				if (isset($_REQUEST['type']))
					$typeName = $_REQUEST['type'];
				else
					$typeName = "";

				if (isset($_REQUEST['thesis']))
					$thesisName = $_REQUEST['thesis'];
				else
					$thesisName = "";

				$markedRadio = "";
				$copyName = "";
				$selectedRadio = "";
				$userKeysName = "";
				$userNotesName = "";
				$userFileName = "";
				$userGroupsName = "";
				$bibtexIDName = "";
				$relatedName = "";
				$fileName = "";

				if (isset($_REQUEST['url']))
					$urlName = $_REQUEST['url'];
				else
					$urlName = "";

				if (isset($_REQUEST['doi']))
					$doiName = $_REQUEST['doi'];
				else
					$doiName = "";

				$contributionID = "";
				$onlinePublication = "";
				$onlineCitationName = "";
				$createdDate = ""; // for INSERTs, 'created_...' and 'modified_...' variables will get fresh values in 'modify.php' anyhow 
				$createdTime = "";
				$createdBy = "";
				$modifiedDate = "";
				$modifiedTime = "";
				$modifiedBy = "";
				$origRecord = "";
			}
			else // the user tried to add or edit a record but...
			{
				if (!empty($errors)) // ...there were some errors on submit. -> Re-load the data that were submitted by the user:
				{
					$authorName = $formVars['authorName'];

					if (isset($formVars['isEditorCheckBox'])) // the user did mark the "is Editor" checkbox
						$isEditorCheckBox = $formVars['isEditorCheckBox'];

					$titleName = $formVars['titleName'];
					$yearNo = $formVars['yearNo'];
					$publicationName = $formVars['publicationName'];
					$abbrevJournalName = $formVars['abbrevJournalName'];
					$volumeNo = $formVars['volumeNo'];
					$issueNo = $formVars['issueNo'];
					$pagesNo = $formVars['pagesNo'];
					$addressName = $formVars['addressName'];
					$corporateAuthorName = $formVars['corporateAuthorName'];
					$keywordsName = $formVars['keywordsName'];
					$abstractName = $formVars['abstractName'];
					$publisherName = $formVars['publisherName'];
					$placeName = $formVars['placeName'];
					$editorName = $formVars['editorName'];
					$languageName = $formVars['languageName'];
					$summaryLanguageName = $formVars['summaryLanguageName'];
					$origTitleName = $formVars['origTitleName'];
					$seriesEditorName = $formVars['seriesEditorName'];
					$seriesTitleName = $formVars['seriesTitleName'];
					$abbrevSeriesTitleName = $formVars['abbrevSeriesTitleName'];
					$seriesVolumeNo = $formVars['seriesVolumeNo'];
					$seriesIssueNo = $formVars['seriesIssueNo'];
					$editionNo = $formVars['editionNo'];
					$issnName = $formVars['issnName'];
					$isbnName = $formVars['isbnName'];
					$mediumName = $formVars['mediumName'];
					$areaName = $formVars['areaName'];
					$expeditionName = $formVars['expeditionName'];
					$conferenceName = $formVars['conferenceName'];
					$notesName = $formVars['notesName'];
					$approvedRadio = $formVars['approvedRadio'];

					if ($recordAction == "edit")
						$locationName = $formVars['locationName'];
					else
						$locationName = $locationName; // supply some generic info: "(...will be filled in automatically)" [as defined at the top of this script]

					$callNumberName = $formVars['callNumberName'];
					if (ereg("%40", $callNumberName)) // if '$callNumberName' still contains URL encoded data... ('%40' is the URL encoded form of the character '@', see note below!)
						$callNumberName = rawurldecode($callNumberName); // ...URL decode 'callNumberName' variable contents (it was URL encoded before incorporation into a hidden tag of the 'record' form to avoid any HTML syntax errors)
																		// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_POST'!
																		//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

					$callNumberNameUserOnly = $formVars['callNumberNameUserOnly'];

					if ($recordAction == "edit")
						$serialNo = $formVars['serialNo'];
					else
						$serialNo = $serialNo; // supply some generic info: "(not assigned yet)" [as defined at the top of this script]

					$typeName = $formVars['typeName'];
					$thesisName = $formVars['thesisName'];
					$markedRadio = $formVars['markedRadio'];
					$copyName = $formVars['copyName'];
					$selectedRadio = $formVars['selectedRadio'];
					$userKeysName = $formVars['userKeysName'];
					$userNotesName = $formVars['userNotesName'];
					$userFileName = $formVars['userFileName'];
					$userGroupsName = $formVars['userGroupsName'];
					$bibtexIDName = $formVars['bibtexIDName'];
					$relatedName = $formVars['relatedName'];
					$fileName = $formVars['fileName'];
					$urlName = $formVars['urlName'];
					$doiName = $formVars['doiName'];

					$contributionID = $formVars['contributionIDName'];
					$contributionID = rawurldecode($contributionID); // URL decode 'contributionID' variable contents (it was URL encoded before incorporation into a hidden tag of the 'record' form to avoid any HTML syntax errors) [see above!]

					// check if we need to set the checkbox in front of "This is a ... publication.":
					if (isset($formVars['contributionIDCheckBox'])) // the user did mark the contribution ID checkbox
						$contributionIDCheckBox = $formVars['contributionIDCheckBox'];

					if (isset($formVars['locationSelectorName']))
						$locationSelectorName = $formVars['locationSelectorName'];
					else
						$locationSelectorName = "";

					// check if we need to set the "Online publication" checkbox:
					if (isset($formVars['onlinePublicationCheckBox'])) // the user did mark the "Online publication" checkbox
						$onlinePublication = "yes";
					else
						$onlinePublication = "no";

					$onlineCitationName = $formVars['onlineCitationName'];
					$createdDate = ""; // for INSERTs, 'created_...' and 'modified_...' variables will get fresh values in 'modify.php' anyhow 
					$createdTime = "";
					$createdBy = "";
					$modifiedDate = "";
					$modifiedTime = "";
					$modifiedBy = "";
					$origRecord = $formVars['origRecord'];
				}
				else // add a new record -> display an empty form (i.e., set all variables to an empty string [""] or their default values, respectively):
				{
					$authorName = "";
					$titleName = "";
					$yearNo = "";
					$publicationName = "";
					$abbrevJournalName = "";
					$volumeNo = "";
					$issueNo = "";
					$pagesNo = "";
					$addressName = "";
					$corporateAuthorName = "";
					$keywordsName = "";
					$abstractName = "";
					$publisherName = "";
					$placeName = "";
					$editorName = "";
					$languageName = "";
					$summaryLanguageName = "";
					$origTitleName = "";
					$seriesEditorName = "";
					$seriesTitleName = "";
					$abbrevSeriesTitleName = "";
					$seriesVolumeNo = "";
					$seriesIssueNo = "";
					$editionNo = "";
					$issnName = "";
					$isbnName = "";
					$mediumName = "";
					$areaName = "";
					$expeditionName = "";
					$conferenceName = "";
					$notesName = "";
					$approvedRadio = "";
					$locationName = $locationName; // supply some generic info: "(...will be filled in automatically)" [as defined at the top of this script]
					$callNumberName = "";
					$callNumberNameUserOnly = "";
					$serialNo = $serialNo; // supply some generic info: "(not assigned yet)" [as defined at the top of this script]
					$typeName = "Journal Article";
					$thesisName = "";
					$markedRadio = "";
					$copyName = "true";
					$selectedRadio = "";
					$userKeysName = "";
					$userNotesName = "";
					$userFileName = "";
					$userGroupsName = "";
					$bibtexIDName = "";
					$relatedName = "";
					$fileName = "";
					$urlName = "";
					$doiName = "";
					$contributionID = "";
					$onlinePublication = "";
					$onlineCitationName = "";
					$createdDate = ""; // for INSERTs, 'created_...' and 'modified_...' variables will get fresh values in 'modify.php' anyhow 
					$createdTime = "";
					$createdBy = "";
					$modifiedDate = "";
					$modifiedTime = "";
					$modifiedBy = "";
					$origRecord = "";
				}
			}
		}

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (4a) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- " . $pageTitle, "index,follow", "Add, edit or delete a record in the " . htmlentities($officialDatabaseName), "", false, "confirmDelete.js", $viewType);
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, $oldQuery);

	// (4b) DISPLAY results:
	// Start <form> and <table> holding the form elements:
	echo "\n<form onsubmit=\"return(confirmDelete(this.submit))\" enctype=\"multipart/form-data\" action=\"modify.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"record\">";
	echo "\n<input type=\"hidden\" name=\"submit\" value=\"" . $pageTitle . "\">"; // provide a default value for the 'submit' form tag (then, hitting <enter> within a text entry field will act as if the user clicked the 'Add/Edit Record' button)
	echo "\n<input type=\"hidden\" name=\"recordAction\" value=\"" . $recordAction . "\">";
	echo "\n<input type=\"hidden\" name=\"oldQuery\" value=\"" . rawurlencode($oldQuery) . "\">"; // we include a link to the formerly displayed results page as hidden form tag so that it's available on the subsequent receipt page that follows any add/edit/delete action!
	echo "\n<input type=\"hidden\" name=\"contributionIDName\" value=\"" . rawurlencode($contributionID) . "\">";
	echo "\n<input type=\"hidden\" name=\"origRecord\" value=\"" . $origRecord . "\">";

	if ($recordAction == "edit")
	{
		// the following hidden form tags are included in order to have their values available when a record is moved to the 'deleted' table:
		echo "\n<input type=\"hidden\" name=\"createdDate\" value=\"" . $createdDate . "\">";
		echo "\n<input type=\"hidden\" name=\"createdTime\" value=\"" . $createdTime . "\">";
		echo "\n<input type=\"hidden\" name=\"createdBy\" value=\"" . $createdBy . "\">";
		echo "\n<input type=\"hidden\" name=\"modifiedDate\" value=\"" . $modifiedDate . "\">";
		echo "\n<input type=\"hidden\" name=\"modifiedTime\" value=\"" . $modifiedTime . "\">";
		echo "\n<input type=\"hidden\" name=\"modifiedBy\" value=\"" . $modifiedBy . "\">";
	}

	// include a hidden tag that indicates the login status *at the time this page was loaded*:
	// Background: We use the session variable "$loginEmail" to control whether a user is logged in or not. However, if a user is working in different browser windows/tabs
	//             the state/contents of a particular window might have changed due to any login/logout actions performed by the user. As an example, a user (who's currently NOT logged in!)
	//             could open several records in edit view to *different* browser windows. Then he realizes that he forgot to login and logs in on the last browser window. He submits that
	//             window and displays the next of his windows (where he still appears to be logged out). He doesn't notice the obsolete login status and goes on editing/submitting this window.
	//             Since the session variable is global, it WILL be possible to submit the form in that window! This proceedure will cause the following problems:
	// Problems:   1. For normal users, the user's *own* call number will get removed from the 'call_number' field contents! The user's call number prefix will remain, though.
	//                (the user's call number gets deleted, since the call number form field is left blank if a user isn't logged in)
	//             2. For normal users as well as for admins, any contribution ID that exists within the "contribution_id" field will be removed
	//                (this is, since the contribution ID checkbox isn't shown when the user isn't logged in)
	// Solution:   Since the above problems can't be circumvented easily with the current design, we simply include a hidden form tag, that indicates the user's login status on a
	//             *per page* basis. Then, 'modify.php' will only allow submitting of forms where "pageLoginStatus=logged in". If a user is already logged in, but the "pageLoginStatus" of the currently
	//             displayed page still states "logged out", he'll need to reload the page or click on the login link to update the "pageLoginStatus" first. This will avoid the problems outlined above.
	if (isset($loginEmail)) // if a user is logged in...
		echo "\n<input type=\"hidden\" name=\"pageLoginStatus\" value=\"logged in\">"; // ...the user was logged IN when loading this page
	else // if no user is logged in...
		echo "\n<input type=\"hidden\" name=\"pageLoginStatus\" value=\"logged out\">"; // ...the user was logged OUT when loading this page

	// if the user isn't logged in -OR- any normal user is logged in (not the admin)...
	if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail)))
		// except the admin, no user will be presented with the complete contents of the 'call_number' field! This is to prevent normal users
		// to mess with other user's personal call numbers. Instead, normal users will always only see their own id number within the 'call_number' field.
		// This should also avoid confusion how this field should/must be edited properly. Of course, the full contents of the 'call_number' field must be
		// preserved, therefore we include them within a hidden form tag:
		echo "\n<input type=\"hidden\" name=\"callNumberName\" value=\"" . rawurlencode($callNumberName) . "\">"; // ...include the *full* contents of the 'call_number' field (if nobody -OR- a normal user is logged in)

	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" width=\"600\" summary=\"This table holds a form that offers to add records or edit existing ones\">"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>Author</b></td>"
			. "\n\t<td colspan=\"5\" class=\"mainfieldsbg\">" . fieldError("authorName", $errors) . "<input type=\"text\" name=\"authorName\" value=\"$authorName\" size=\"70\" title=\"the author(s) of this publication (e.g. 'Clough, LM; de Broyer, H-C; Ambrose Jr., WG'); please separate multiple authors with a semicolon &amp; a space ('; ')\">";

	if ($isEditorCheckBox == "1" OR ereg(" *\(eds?\)$", $authorName)) // if the '$isEditorCheckBox' variable is set to 1 -OR- if 'author' field ends with either " (ed)" or " (eds)"
		$isEditorCheckBoxIsChecked = " checked"; // mark the 'is Editor' checkbox
	else
		$isEditorCheckBoxIsChecked = ""; // don't mark the 'is Editor' checkbox

	echo "\n\t&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" name=\"isEditorCheckBox\" value=\"1\"$isEditorCheckBoxIsChecked title=\"mark this checkbox if the author is actually the editor (info will be also copied to the editor field)\">&nbsp;&nbsp;<b>is Editor</b></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>Title</b></td>"
			. "\n\t<td colspan=\"3\" class=\"mainfieldsbg\">" . fieldError("titleName", $errors) . "<input type=\"text\" name=\"titleName\" value=\"$titleName\" size=\"48\" title=\"the title of this publication; please don't append any dot to the title!\"></td>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>Type</b></td>";

	if (!isset($_SESSION['user_types']))
		$exportTypeDisabled = " disabled"; // disable the type popup if the session variable holding the user's types isn't available
	else
		$exportTypeDisabled = "";

	$recordType = "\n\t<td align=\"right\" class=\"mainfieldsbg\">"
				. "\n\t\t<select name=\"typeName\" title=\"please specify the type of this publication (e.g. 'Journal Article' for a paper)\"$exportTypeDisabled>";
	
	if (isset($_SESSION['user_types']))
	{
		$optionTags = buildSelectMenuOptions($_SESSION['user_types'], " *; *", "\t\t\t"); // build properly formatted <option> tag elements from the items listed in the 'user_types' session variable
		$recordType .= $optionTags;
	}
	else
		$recordType .= "<option>(no types available)</option>";

	$recordType .= "\n\t\t</select>"
				. "\n\t</td>";

	if (!empty($typeName))
		$recordType = ereg_replace("<option>$typeName", "<option selected>$typeName", $recordType);
	
	echo "$recordType"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>Year</b></td>"
			. "\n\t<td class=\"mainfieldsbg\">" . fieldError("yearNo", $errors) . "<input type=\"text\" name=\"yearNo\" value=\"$yearNo\" size=\"14\" title=\"please specify years in 4-digit format, like '1998'\"></td>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>Publication</b></td>"
			. "\n\t<td class=\"mainfieldsbg\">" . fieldError("publicationName", $errors) . "<input type=\"text\" name=\"publicationName\" value=\"$publicationName\" size=\"14\" title=\"the full title of the journal or the book title\"></td>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>Abbrev Journal</b></td>"
			. "\n\t<td align=\"right\" class=\"mainfieldsbg\">" . fieldError("abbrevJournalName", $errors) . "<input type=\"text\" name=\"abbrevJournalName\" value=\"$abbrevJournalName\" size=\"14\" title=\"the abbreviated journal title\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>Volume</b></td>"
			. "\n\t<td class=\"mainfieldsbg\">" . fieldError("volumeNo", $errors) . "<input type=\"text\" name=\"volumeNo\" value=\"$volumeNo\" size=\"14\" title=\"the volume of the specified publication\"></td>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>Issue</b></td>"
			. "\n\t<td class=\"mainfieldsbg\"><input type=\"text\" name=\"issueNo\" value=\"$issueNo\" size=\"14\" title=\"the issue of the specified volume\"></td>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>Pages</b></td>"
			. "\n\t<td align=\"right\" class=\"mainfieldsbg\">" . fieldError("pagesNo", $errors) . "<input type=\"text\" name=\"pagesNo\" value=\"$pagesNo\" size=\"14\" title=\"papers &amp; book chapters: e.g. '12-18' (no 'pp'!), whole books: e.g. '316 pp'\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Corporate Author</b></td>"
			. "\n\t<td colspan=\"3\" class=\"otherfieldsbg\"><input type=\"text\" name=\"corporateAuthorName\" value=\"$corporateAuthorName\" size=\"48\" title=\"author affiliation\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Thesis</b></td>";

	$thesisType = "\n\t<td align=\"right\" class=\"otherfieldsbg\">\n\t\t<select name=\"thesisName\" title=\"if this is a thesis, specify the degree here\">\n\t\t\t<option></option>\n\t\t\t<option>Bachelor's thesis</option>\n\t\t\t<option>Master's thesis</option>\n\t\t\t<option>Ph.D. thesis</option>\n\t\t\t<option>Diploma thesis</option>\n\t\t\t<option>Doctoral thesis</option>\n\t\t\t<option>Habilitation thesis</option>\n\t\t</select>\n\t</td>";
	if (!empty($thesisName))
		$thesisType = ereg_replace("<option>$thesisName", "<option selected>$thesisName", $thesisType);

	echo "$thesisType"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Address</b></td>"
			. "\n\t<td colspan=\"5\" class=\"otherfieldsbg\"><input type=\"text\" name=\"addressName\" value=\"$addressName\" size=\"85\" title=\"any contact information\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Keywords</b></td>"
			. "\n\t<td colspan=\"5\" class=\"otherfieldsbg\"><input type=\"text\" name=\"keywordsName\" value=\"$keywordsName\" size=\"85\" title=\"keywords given by the authors, please enter your own keywords below; multiple items should be separated with a semicolon &amp; a space ('; ')\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Abstract</b></td>"
			. "\n\t<td colspan=\"5\" class=\"otherfieldsbg\"><textarea name=\"abstractName\" rows=\"6\" cols=\"83\" title=\"the abstract for this publication (if any)\">$abstractName</textarea></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Publisher</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"publisherName\" value=\"$publisherName\" size=\"14\" title=\"the publisher of this publication\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Place</b></td>"
			. "\n\t<td class=\"otherfieldsbg\" class=\"otherfieldsbg\"><input type=\"text\" name=\"placeName\" value=\"$placeName\" size=\"14\" title=\"the place of publication\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Editor</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"editorName\" value=\"$editorName\" size=\"14\" title=\"the editor(s) of this publication (e.g. 'Clough, LM; de Broyer, H-C; Ambrose Jr., WG'); please separate multiple editors with a semicolon &amp; a space ('; ')\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Language</b></td>"
			. "\n\t<td class=\"otherfieldsbg\">" . fieldError("languageName", $errors) . "<input type=\"text\" name=\"languageName\" value=\"$languageName\" size=\"14\" title=\"language of the body text\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Summary Language</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"summaryLanguageName\" value=\"$summaryLanguageName\" size=\"14\" title=\"language of the summary or abstract (if any)\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Orig Title</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"origTitleName\" value=\"$origTitleName\" size=\"14\" title=\"original title of this publication (if any)\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Series Editor</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"seriesEditorName\" value=\"$seriesEditorName\" size=\"14\" title=\"if this publication belongs to a series, specify the series editor(s) here\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Series Title</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"seriesTitleName\" value=\"$seriesTitleName\" size=\"14\" title=\"if this publication belongs to a series, give the full title of the series here\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Abbrev Series Title</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"abbrevSeriesTitleName\" value=\"$abbrevSeriesTitleName\" size=\"14\" title=\"if this publication belongs to a series, give the abbreviated title of the series here\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Series Volume</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"seriesVolumeNo\" value=\"$seriesVolumeNo\" size=\"14\" title=\"if this publication belongs to a series, enter the volume of the series here\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Series Issue</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"seriesIssueNo\" value=\"$seriesIssueNo\" size=\"14\" title=\"if this publication belongs to a series, enter the issue of the series volume here\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Edition</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"editionNo\" value=\"$editionNo\" size=\"14\" title=\"if it's not the first edition, please specify the edition number of this publication\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>ISSN</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"issnName\" value=\"$issnName\" size=\"14\" title=\"if this publication is a journal or dissertation, please specify it's ISSN number\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>ISBN</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"isbnName\" value=\"$isbnName\" size=\"14\" title=\"if this publication is a book (chapter), please specify it's ISBN number\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Medium</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"mediumName\" value=\"$mediumName\" size=\"14\" title=\"please specify if not paper (like e.g. CD-ROM, cassettes, disks, transparencies, negatives, slides, etc.)\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Area</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"areaName\" value=\"$areaName\" size=\"14\" title=\"the area of investigation this publication deals with; multiple items should be separated with a semicolon &amp; a space ('; ')\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Expedition</b></td>"
			. "\n\t<td class=\"otherfieldsbg\"><input type=\"text\" name=\"expeditionName\" value=\"$expeditionName\" size=\"14\" title=\"the name of the expedition where sampling took place\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Conference</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"conferenceName\" value=\"$conferenceName\" size=\"14\" title=\"any conference this publication was initially presented at\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Notes</b></td>"
			. "\n\t<td colspan=\"3\" class=\"otherfieldsbg\"><input type=\"text\" name=\"notesName\" value=\"$notesName\" size=\"48\" title=\"enter any generic notes here\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Approved</b></td>";

	$approved = "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"radio\" name=\"approvedRadio\" value=\"yes\" title=\"choose 'yes' if you've verified this record for correctness, otherwise set to 'no'\">&nbsp;&nbsp;yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"approvedRadio\" value=\"no\" title=\"choose 'yes' if you've verified this record for correctness, otherwise set to 'no'\">&nbsp;&nbsp;no</td>";
	if ($approvedRadio == "yes")
		$approved = ereg_replace("name=\"approvedRadio\" value=\"yes\"", "name=\"approvedRadio\" value=\"yes\" checked", $approved);
	else // ($approvedRadio == "no")
		$approved = ereg_replace("name=\"approvedRadio\" value=\"no\"", "name=\"approvedRadio\" value=\"no\" checked", $approved);

	echo "$approved"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>Location</b></td>"
			. "\n\t<td colspan=\"5\" class=\"otherfieldsbg\"><input type=\"text\" name=\"locationName\" value=\"$locationName\" size=\"85\" title=\"shows all users who have added this record to their personal literature data set.$fieldLock\"$fieldLock></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>Call Number</b></td>";

	// if the user isn't logged in -OR- any normal user is logged in (not the admin)...
	if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail)))
		// ...we just show the user's own call number (if any):
		echo "\n\t<td colspan=\"3\" class=\"mainfieldsbg\">" . fieldError("callNumberNameUserOnly", $errors) . "<input type=\"text\" name=\"callNumberNameUserOnly\" value=\"$callNumberNameUserOnly\" size=\"48\" title=\"enter your own reference number that uniquely identifies this record for you\"></td>";
	else // if the admin is logged in...
		// ...we display the full contents of the 'call_number' field:
		echo "\n\t<td colspan=\"3\" class=\"mainfieldsbg\"><input type=\"text\" name=\"callNumberName\" value=\"$callNumberName\" size=\"48\" title=\"institutional_abbreviation @ user_id @ user_reference_id\"></td>";

	echo "\n\t<td width=\"74\" class=\"mainfieldsbg\"><b>Serial</b></td>"
			. "\n\t<td align=\"right\" class=\"mainfieldsbg\"><input type=\"text\" name=\"serialNo\" value=\"$serialNo\" size=\"14\" title=\"this is the unique serial number for this record\" readonly></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>Marked</b></td>";

	$marked = "\n\t<td class=\"userfieldsbg\"><input type=\"radio\" name=\"markedRadio\" value=\"yes\" title=\"mark this record if you'd like to easily retrieve it afterwards\">&nbsp;&nbsp;yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"markedRadio\" value=\"no\" title=\"mark this record if you'd like to easily retrieve it afterwards\">&nbsp;&nbsp;no</td>";
	if ($markedRadio == "yes")
		$marked = ereg_replace("name=\"markedRadio\" value=\"yes\"", "name=\"markedRadio\" value=\"yes\" checked", $marked);
	else // ($markedRadio == "no")
		$marked = ereg_replace("name=\"markedRadio\" value=\"no\"", "name=\"markedRadio\" value=\"no\" checked", $marked);

	echo "$marked"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>Copy</b></td>";
	
	$copy = "\n\t<td class=\"userfieldsbg\">\n\t\t<select name=\"copyName\" title=\"set to 'true' if you own a copy of this publication, adjust otherwise if not\">\n\t\t\t<option>true</option>\n\t\t\t<option>fetch</option>\n\t\t\t<option>ordered</option>\n\t\t\t<option>false</option>\n\t\t</select>\n\t</td>";
	if (!empty($copyName))
		$copy = ereg_replace("<option>$copyName", "<option selected>$copyName", $copy);
	
	echo "$copy"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>Selected</b></td>";

	$selected = "\n\t<td align=\"right\" class=\"userfieldsbg\"><input type=\"radio\" name=\"selectedRadio\" value=\"yes\" title=\"select this record if this is one of your important publications\">&nbsp;&nbsp;yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"selectedRadio\" value=\"no\" title=\"select this record if this is one of your important publications\">&nbsp;&nbsp;no</td>";
	if ($selectedRadio == "yes")
		$selected = ereg_replace("name=\"selectedRadio\" value=\"yes\"", "name=\"selectedRadio\" value=\"yes\" checked", $selected);
	else // ($selectedRadio == "no")
		$selected = ereg_replace("name=\"selectedRadio\" value=\"no\"", "name=\"selectedRadio\" value=\"no\" checked", $selected);

	echo "$selected"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>User Keys</b></td>"
			. "\n\t<td colspan=\"5\" class=\"userfieldsbg\"><input type=\"text\" name=\"userKeysName\" value=\"$userKeysName\" size=\"85\" title=\"enter your personal keywords here; multiple items should be separated with a semicolon &amp; a space ('; ')\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>User Notes</b></td>"
			. "\n\t<td colspan=\"3\" class=\"userfieldsbg\"><input type=\"text\" name=\"userNotesName\" value=\"$userNotesName\" size=\"48\" title=\"enter your personal notes here\"></td>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>User File</b></td>"
			. "\n\t<td align=\"right\" class=\"userfieldsbg\"><input type=\"text\" name=\"userFileName\" value=\"$userFileName\" size=\"14\" title=\"if this record corresponds to any personal file(s) or directory on your disk, you can enter the file/dir spec(s) here\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>User Groups</b></td>";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_user_groups", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_user_groups'...
	// adjust the title string for the user groups text entry field:
	{
		$userGroupsFieldLock = "";
		$userGroupsTitle = "if this records belongs to one of your personal groups, enter the group's name here; multiple groups should be separated with a semicolon &amp; a space ('; ')";
	}
	else
	{
		$userGroupsFieldLock = " disabled"; // it would be more consistent to remove the user groups field completely from the form if the user has no permission to use the user groups feature; but since this would complicate the processing quite a bit, we just disable the field (for now)
		$userGroupsTitle = "not available since you have no permission to use the user groups feature";
	}

	echo "\n\t<td colspan=\"3\" class=\"userfieldsbg\"><input type=\"text\" name=\"userGroupsName\" value=\"$userGroupsName\" size=\"48\"$userGroupsFieldLock title=\"$userGroupsTitle\"></td>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>BibTeX ID</b></td>"
			. "\n\t<td align=\"right\" class=\"userfieldsbg\"><input type=\"text\" name=\"bibtexIDName\" value=\"$bibtexIDName\" size=\"14\" title=\"the custom identifier that uniquely describes this record when working with BibTeX (keep empty for automatic ID generation)\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"userfieldsbg\"><b>Related</b></td>"
			. "\n\t<td colspan=\"5\" class=\"userfieldsbg\"><input type=\"text\" name=\"relatedName\" value=\"$relatedName\" size=\"85\" title=\"to directly link this record to any other records enter their serial numbers here; multiple serials should be separated with a semicolon &amp; a space ('; ')\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>File</b></td>"
			. "\n\t<td colspan=\"3\" class=\"otherfieldsbg\"><input type=\"text\" name=\"fileName\" value=\"$fileName\" size=\"48\" title=\"if there's a file associated with this record (e.g. a PDF file) please use the upload button and select the file to copy it to the server\"$fieldLock></td>";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_upload", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_upload'...
	// adjust the title string for the upload button:
	{
		$uploadButtonLock = "";
		$uploadTitle = "upload any file that's associated with this record";
	}
	else
	{
		$uploadButtonLock = " disabled"; // disabling of the upload button doesn't seem to work in all browsers (e.g., it doesn't work in Safari on MacOSX Panther, but does work with Mozilla & Camino) ?:-/
		$uploadTitle = "not available since you have no permission to upload any files"; // similarily, not all browsers will show title strings for disabled buttons (Safari does, Mozilla & Camino do not)
	}

	echo "\n\t<td valign=\"bottom\" colspan=\"2\" class=\"otherfieldsbg\">" . fieldError("uploadFile", $errors) . "<input type=\"file\" name=\"uploadFile\" size=\"17\"$uploadButtonLock title=\"$uploadTitle\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>URL</b></td>"
			. "\n\t<td colspan=\"3\" class=\"otherfieldsbg\"><input type=\"text\" name=\"urlName\" value=\"$urlName\" size=\"48\" title=\"the web address providing more information for this publication (if any)\"></td>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\"><b>DOI</b></td>"
			. "\n\t<td align=\"right\" class=\"otherfieldsbg\"><input type=\"text\" name=\"doiName\" value=\"$doiName\" size=\"14\" title=\"the unique 'digital object identifier' of this publication (if available)\"></td>"
			. "\n</tr>";

	if ($onlinePublication == "yes") // if the 'online_publication' field value is "yes"
		$onlinePublicationCheckBoxIsChecked = " checked"; // mark the 'Online publication' checkbox
	else
		$onlinePublicationCheckBoxIsChecked = ""; // don't mark the 'Online publication' checkbox

	echo "\n<tr>"
			. "\n\t<td width=\"74\" class=\"otherfieldsbg\">&nbsp;</td>"
			. "\n\t<td colspan=\"3\" class=\"otherfieldsbg\">\n\t\t<input type=\"checkbox\" name=\"onlinePublicationCheckBox\" value=\"1\"$onlinePublicationCheckBoxIsChecked title=\"mark this checkbox if this record refers to an online publication that has no print equivalent (yet)\">&nbsp;"
			. "\n\t\tOnline publication. Cite with this text:&nbsp;<input type=\"text\" name=\"onlineCitationName\" value=\"$onlineCitationName\" size=\"9\" title=\"enter any additional info that's required to locate the online location of this publication\">\n\t</td>";

	if (isset($loginEmail)) // if a user is logged in...
	{
		// ...we'll show a checkbox where the user can state that the current publication stems form his own institution
		if ($contributionIDCheckBox == "1" OR ereg("$abbrevInstitution", $contributionID)) // if the '$contributionIDCheckBox' variable is set to 1 -OR- if the currrent user's abbreviated institution name is listed within the 'contribution_id' field
			$contributionIDCheckBoxIsChecked = " checked";
		else
			$contributionIDCheckBoxIsChecked = "";

		if ($origRecord > 0) // if the current record has been identified as duplicate entry...
			$contributionIDCheckBoxLock = " disabled"; // ...we lock the check box (since the original entry, and not the dup entry, should be marked instead)
		else
			$contributionIDCheckBoxLock = "";

		if (eregi("^[aeiou]", $abbrevInstitution))
			$n = "n";
		else
			$n = "";

		echo "\n\t<td colspan=\"2\" class=\"otherfieldsbg\">\n\t\t<input type=\"checkbox\" name=\"contributionIDCheckBox\" value=\"1\"$contributionIDCheckBoxIsChecked title=\"mark this checkbox if one of the authors of this publication belongs to your own institution\"$contributionIDCheckBoxLock>&nbsp;"
				. "\n\t\tThis is a" . $n . " " . htmlentities($abbrevInstitution) . " publication.\n\t</td>"; // we make use of the session variable '$abbrevInstitution' here
	}
	else
	{
		echo "\n\t<td colspan=\"2\" class=\"otherfieldsbg\">&nbsp;</td>";
	}

	echo "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\">&nbsp;</td>"
			. "\n\t<td colspan=\"5\">&nbsp;</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td width=\"74\">Location Field:</td>";

	$locationSelector = "\n\t<td colspan=\"3\">\n\t\t<select name=\"locationSelectorName\" title=\"choose 'Add' if this record belongs to your personal literature data set, choose 'Remove' to delete it again from your own literature data set\">\n\t\t\t<option>Don't touch</option>\n\t\t\t<option>Add</option>\n\t\t\t<option>Remove</option>\n\t\t</select>&nbsp;&nbsp;\n\t\tmy name &amp; email address\n\t</td>";
	if ($recordAction == "edit" AND !empty($locationSelectorName))
		$locationSelector = ereg_replace("<option>$locationSelectorName", "<option selected>$locationSelectorName", $locationSelector);
	elseif ($recordAction == "add")
	{
		$locationSelector = ereg_replace("<option>Add", "<option selected>Add", $locationSelector); // select the appropriate menu entry ...
		if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail))) // ... and if the user isn't logged in -OR- any normal user is logged in (not the admin) ...
			$locationSelector = ereg_replace("<select", "<select disabled", $locationSelector); // ... disable the popup menu. This is, since the current user & email address will be always written to the location field when adding new records. An orphaned record would be produced if the user could chose anything other than 'Add'! (Note that the admin is permitted to override this behaviour)
	}

	echo "$locationSelector"
			. "\n\t<td align=\"right\" colspan=\"2\">";

	// Note that, normally, we don't show interface items which the user isn't allowed to use (see the delete button). But, in the case of the add/edit button we make an exception here and just grey the button out.
	// This is, since otherwise the form would have no submit button at all, which would be pretty odd. The title string of the button explains why it is disabled.
	if ($recordAction == "edit") // adjust the title string for the edit button
	{
		if (isset($_SESSION['user_permissions']) AND ereg("allow_edit", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_edit'...
		{
			$addEditButtonLock = "";
			$addEditTitle = "submit your changes to this record";
		}
		else
		{
			$addEditButtonLock = " disabled";
			$addEditTitle = "not available since you have no permission to edit any records";
		}
	}
	else // if ($recordAction == "add") // adjust the title string for the add button
	{
		if (isset($_SESSION['user_permissions']) AND ereg("allow_add", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_add'...
		{
			$addEditButtonLock = "";
			$addEditTitle = "add this record to the database";
		}
		else
		{
			$addEditButtonLock = " disabled";
			$addEditTitle = "not available since you have no permission to add any records";
		}
	}

	// display an ADD/EDIT button:
	echo "<input type=\"submit\" name=\"submit\" value=\"$pageTitle\"$addEditButtonLock title=\"$addEditTitle\">";

	if (isset($_SESSION['user_permissions']) AND ereg("allow_delete", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_delete'...
	// ... display a delete button:
	{
		if ($recordAction == "edit") // add a DELETE button (CAUTION: the delete button must be displayed *AFTER* the edit button, otherwise DELETE will be the default action if the user hits return!!)
									// (this is since the first displayed submit button represents the default submit action in several browsers!! [like OmniWeb or Mozilla])
		{
			if (!isset($loginEmail) OR ((!ereg($loginEmail,$locationName) OR ereg(";",$locationName)) AND ($loginEmail != $adminLoginEmail))) // if the user isn't logged in -OR- any normal user is logged in & the 'location' field doesn't list her email address -OR- if the 'location' field contains more than one user (which is indicated by a semicolon character)...
			{
				// build an informative title string:
				if (!isset($loginEmail)) // if the user isn't logged in
					$deleteTitle = "you can't delete this record since you're not logged in";

				elseif (!ereg($loginEmail, $locationName)) // if any normal user is logged in & the 'location' field doesn't list her email address
					$deleteTitle = "you can't delete this record since it doesn't belong to your personal literature data set";

				// Note that we use '$row['location']' instead of the '$locationName' variable for the following tests since for the latter high ASCII characters were converted into HTML entities.
				// E.g., the german umlaut '�' would be presented as '&uuml;', thus containing a semicolon character *within* the user's name!
				elseif (ereg(";", $row['location'])) // if the 'location' field contains more than one user (which is indicated by a semicolon character)
				{
					// if we made it here, the current user is listed within the 'location' field of this record
					if (ereg("^[^;]+;[^;]+$", $row['location'])) // the 'location' field does contain exactly one ';' => two authors, i.e., there's only one "other user" listed within the 'location' field
						$deleteTitle = "you can't delete this record since it also belongs to the personal literature data set of another user";
					elseif (ereg("^[^;]+;[^;]+;[^;]+", $row['location'])) // the 'location' field does contain at least two ';' => more than two authors, i.e., there are two or more "other users" listed within the 'location' field
						$deleteTitle = "you can't delete this record since it also belongs to the personal literature data set of other users";
				}
	
				$deleteButtonLock = " disabled"; // ...we lock the delete button (since a normal user shouldn't be allowed to delete records that belong to other users)
			}
			else
			{
				$deleteTitle = "pressing this button will remove this record from the database";
				$deleteButtonLock = "";
			}
	
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" name=\"submit\" value=\"Delete Record\"$deleteButtonLock title=\"$deleteTitle\">";
		}
	}

	echo "</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";
	
	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// SHOW ERROR IN RED:
	function fieldError($fieldName, $errors)
	{
		if (isset($errors[$fieldName]))
			return "<b><span class=\"warning2\">" . $errors[$fieldName] . "</span></b><br>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc.php')
	displayfooter($oldQuery);

	// --------------------------------------------------------------------
?>

</body>
</html> 