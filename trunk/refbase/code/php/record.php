<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./record.php
	// Created:    29-Jul-02, 16:39
	// Modified:   29-Dec-03, 14:19

	// Form that offers to add
	// records or edit/delete
	// existing ones.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password
	include 'header.inc'; // include header
	include 'footer.inc'; // include footer
	include 'include.inc'; // include common functions
	include "ini.inc.php"; // include common variables

	// --------------------------------------------------------------------

	// Connect to a session
	session_start();
	
	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!

	// --------------------------------------------------------------------

	$recordAction = $_REQUEST['recordAction']; // check whether the user wants to *add* a record or *edit* an existing one
	$mode = $_REQUEST['mode']; // check whether the user wants to add a record by use of an *import* form (e.g., the parameter "mode=import" will be set by 'import_csa.php')
	$importSource = $_REQUEST['importSource']; // get the source from which the imported data originate (e.g., if data have been imported via 'import_csa.php', the 'importSource' value will be 'csa')

	if (isset($_REQUEST['serialNo']))
		$serialNo = $_REQUEST['serialNo']; // fetch the serial number of the record to edit
	else
		$serialNo = ""; // this is actually unneccessary, but we do it for clarity reasons here

	$oldQuery = $_REQUEST['oldQuery']; // fetch the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "
	$oldQuery = ereg_replace('(\\\\)+','\\\\',$oldQuery);

	// Setup some required variables:

	// If there's no stored message available:
	if (!session_is_registered("HeaderString")) // if there's no stored message available
	{
		if (empty($errors)) // provide one of the default messages:
		{
			$errors = array(); // re-assign an empty array (in order to prevent 'Undefined variable "errors"...' messages when calling the 'fieldError' function later on)
			if ("$recordAction" == "edit") // *edit* record
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
		session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

	// if the user isn't logged in -OR- any normal user is logged in (not the admin)...
	if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail)))
		$fieldLock = " readonly"; // ... lock the 'location' & 'file' fields
	else // if the admin is logged in...
		$fieldLock = ""; // ...the 'location' & 'file' fields won't be locked (since the admin should be able to freely add or edit any records)

	if ("$recordAction" == "edit") // *edit* record
	{
		$pageTitle = "Edit Record"; // set the correct page title
	}
	else // *add* record will be the default action if no parameter is given
	{
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
		$callNumberPrefix = $abbrevInstitution . " @ " . $loginEmailUserName; // we make use of the session variable '$abbrevInstitution' to construct a correct call number prefix, like: 'IP… @ msteffens'
	}

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:
	// if the script was called with parameters (like: 'record.php?recordAction=edit&serialNo=...')
	if ("$recordAction" == "edit")
	{
		// for the selected record, select *all* available fields:
		// (note: we also add the 'serial' column at the end in order to provide standardized input [compare processing of form 'sql_search.php' in 'search.php'])
		if (session_is_registered("loginEmail")) // if a user is logged in, show user specific fields:
			$query = "SELECT author, title, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, approved, notes, file, serial, location, type, call_number, created_date, created_time, created_by, modified_date, modified_time, modified_by, orig_record, contribution_id, online_publication, online_citation, marked, copy, selected, user_keys, user_notes, user_file, serial, url, doi"
					. " FROM refs LEFT JOIN user_data ON serial = record_id AND user_id =" . $loginUserID . " WHERE serial RLIKE \"^(" . $serialNo . ")$\""; // since we'll only fetch one record, the ORDER BY clause is obsolete here
		else // if NO user logged in, don't display any user specific fields:
			$query = "SELECT author, title, year, publication, abbrev_journal, volume, issue, pages, corporate_author, thesis, address, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, approved, notes, file, serial, location, type, call_number, created_date, created_time, created_by, modified_date, modified_time, modified_by, orig_record, contribution_id, online_publication, online_citation, serial, url, doi"
					. " FROM refs WHERE serial RLIKE \"^(" . $serialNo . ")$\""; // since we'll only fetch one record, the ORDER BY clause is obsolete here
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (4) DISPLAY HEADER & RESULTS, (5) CLOSE CONNECTION

	// (1) OPEN the database connection:
	//      (variables are set by include file 'db.inc'!)
	if (!($connection = @ mysql_connect($hostName, $username, $password)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to connect to the host:", "");

	// (2) SELECT the database:
	//      (variables are set by include file 'db.inc'!)
	if (!(mysql_select_db($databaseName, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to connect to the database:", "");

	if ("$recordAction" == "edit")
		{
			// (3a) RUN the query on the database through the connection:
			if (!($result = @ mysql_query ($query, $connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

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
				$OrigTitleName = htmlentities($row['orig_title']);
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
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

		}
	else // if ("$recordAction" == "add"), i.e., adding a new record...
		{
			if ($mode == "import") // if the user wants to import record data by use of an import form (like 'import_csa.php')
			{
				// read field data from a GET/POST request:
				$authorName = $_REQUEST['author'];
				$titleName = $_REQUEST['title'];
				$yearNo = $_REQUEST['year'];
				$publicationName = $_REQUEST['publication'];
				$abbrevJournalName = $_REQUEST['abbrev_journal'];
				$volumeNo = $_REQUEST['volume'];
				$issueNo = $_REQUEST['issue'];
				$pagesNo = $_REQUEST['pages'];
				$addressName = $_REQUEST['address'];
				$corporateAuthorName = $_REQUEST['corporate_author'];
				$keywordsName = $_REQUEST['keywords'];
				$abstractName = $_REQUEST['abstract'];
				$publisherName = $_REQUEST['publisher'];
				$placeName = $_REQUEST['place'];
				$editorName = $_REQUEST['editor'];
				$languageName = $_REQUEST['language'];
				$summaryLanguageName = $_REQUEST['summary_language'];
				$OrigTitleName = $_REQUEST['orig_title'];
				$seriesEditorName = $_REQUEST['series_editor'];
				$seriesTitleName = $_REQUEST['series_title'];
				$abbrevSeriesTitleName = $_REQUEST['abbrev_series_title'];
				$seriesVolumeNo = $_REQUEST['series_volume'];
				$seriesIssueNo = $_REQUEST['series_issue'];
				$editionNo = $_REQUEST['edition'];
				$issnName = $_REQUEST['issn'];
				$isbnName = $_REQUEST['isbn'];
				$mediumName = "";
				$areaName = $_REQUEST['area'];
				$expeditionName = "";
				$conferenceName = $_REQUEST['conference'];
				$notesName = $_REQUEST['notes'];
				$approvedRadio = "";
				$locationName = $locationName; // supply some generic info: "(...will be filled in automatically)" [as defined at the top of this script]
				$callNumberName = "";
				$callNumberNameUserOnly = "";
				$serialNo = $serialNo; // supply some generic info: "(not assigned yet)" [as defined at the top of this script]
				$typeName = $_REQUEST['type'];
				$thesisName = $_REQUEST['thesis'];
				$markedRadio = "";
				$copyName = "";
				$selectedRadio = "";
				$userKeysName = "";
				$userNotesName = "";
				$userFileName = "";
				$fileName = "";
				$urlName = $_REQUEST['url'];
				$doiName = $_REQUEST['doi'];
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
			else // ...set all variables to "":
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
				$OrigTitleName = "";
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
				$typeName = "";
				$thesisName = "";
				$markedRadio = "";
				$copyName = "";
				$selectedRadio = "";
				$userKeysName = "";
				$userNotesName = "";
				$userFileName = "";
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

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// (4a) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- " . $pageTitle, "index,follow", "Add, edit or delete a record in the " . htmlentities($officialDatabaseName), "", false, "confirmDelete.js");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, $oldQuery);

	// (4b) DISPLAY results:
	// Start <form> and <table> holding the form elements:
	echo "\n<form onsubmit=\"return(confirmDelete(this.submit))\" enctype=\"multipart/form-data\" action=\"modify.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"record\">";
	echo "\n<input type=\"hidden\" name=\"submit\" value=\"" . $pageTitle . "\">"; // provide a default value for the 'submit' form tag (then, hitting <enter> within a text entry field will act as if the user clicked the 'Add/Edit Record' button)
	echo "\n<input type=\"hidden\" name=\"recordAction\" value=\"" . $recordAction . "\">";
	echo "\n<input type=\"hidden\" name=\"oldQuery\" value=\"" . rawurlencode($oldQuery) . "\">"; // we include a link to the formerly displayed results page as hidden form tag so that it's available on the subsequent receipt page that follows any add/edit/delete action!
	echo "\n<input type=\"hidden\" name=\"contributionIDName\" value=\"" . rawurlencode($contributionID) . "\">";

	if ("$recordAction" == "edit")
	{
		// the following hidden form tags are included in order to have their values available when a record is moved to the 'deleted' table:
		echo "\n<input type=\"hidden\" name=\"createdDate\" value=\"" . $createdDate . "\">";
		echo "\n<input type=\"hidden\" name=\"createdTime\" value=\"" . $createdTime . "\">";
		echo "\n<input type=\"hidden\" name=\"createdBy\" value=\"" . $createdBy . "\">";
		echo "\n<input type=\"hidden\" name=\"modifiedDate\" value=\"" . $modifiedDate . "\">";
		echo "\n<input type=\"hidden\" name=\"modifiedTime\" value=\"" . $modifiedTime . "\">";
		echo "\n<input type=\"hidden\" name=\"modifiedBy\" value=\"" . $modifiedBy . "\">";
		echo "\n<input type=\"hidden\" name=\"origRecord\" value=\"" . $origRecord . "\">";
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
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Author</b></td>"
			. "\n\t<td colspan=\"5\" bgcolor=\"#DEDEDE\">" . fieldError("authorName", $errors) . "<input type=\"text\" name=\"authorName\" value=\"$authorName\" size=\"70\" title=\"the author(s) of this publication (e.g. 'Clough, LM; de Broyer, H-C; Ambrose Jr., WG'); please separate multiple authors with a semicolon &amp; a space ('; ')\">";

	if (ereg(" *\(eds?\)$", $authorName)) // if 'author' field ends with either " (ed)" or " (eds)"
		$isEditorCheckBoxIsChecked = " checked"; // mark the 'is Editor' checkbox
	else
		$isEditorCheckBoxIsChecked = ""; // don't mark the 'is Editor' checkbox

	echo "\n\t&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"checkbox\" name=\"isEditorCheckBox\" value=\"1\"$isEditorCheckBoxIsChecked title=\"mark this checkbox if the author is actually the editor (info will be also copied to the editor field)\">&nbsp;&nbsp;<b>is Editor</b></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Title</b></td>"
			. "\n\t<td colspan=\"3\" bgcolor=\"#DEDEDE\">" . fieldError("titleName", $errors) . "<input type=\"text\" name=\"titleName\" value=\"$titleName\" size=\"48\" title=\"the title of this publication; please don't append any dot to the title!\"></td>"
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Type</b></td>";

	$recordType = "\n\t<td align=\"right\" bgcolor=\"#DEDEDE\">\n\t\t<select name=\"typeName\" title=\"please specify the type of this publication (e.g. 'Journal Article' for a paper)\">\n\t\t\t<option>Journal Article</option>\n\t\t\t<option>Book Chapter</option>\n\t\t\t<option>Book Whole</option>\n\t\t\t<option>Journal</option>\n\t\t\t<option>Manuscript</option>\n\t\t\t<option>Map</option>\n\t\t</select>\n\t</td>";
	if (("$recordAction" == "edit") OR ($mode == "import"))
		$recordType = ereg_replace("<option>$typeName", "<option selected>$typeName", $recordType);
	
	echo "$recordType"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Year</b></td>"
			. "\n\t<td bgcolor=\"#DEDEDE\">" . fieldError("yearNo", $errors) . "<input type=\"text\" name=\"yearNo\" value=\"$yearNo\" size=\"14\" title=\"please specify years in 4-digit format, like '1998'\"></td>"
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Publication</b></td>"
			. "\n\t<td bgcolor=\"#DEDEDE\">" . fieldError("publicationName", $errors) . "<input type=\"text\" name=\"publicationName\" value=\"$publicationName\" size=\"14\" title=\"the full title of the journal or the book title\"></td>"
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Abbrev Journal</b></td>"
			. "\n\t<td align=\"right\" bgcolor=\"#DEDEDE\">" . fieldError("abbrevJournalName", $errors) . "<input type=\"text\" name=\"abbrevJournalName\" value=\"$abbrevJournalName\" size=\"14\" title=\"the abbreviated journal title\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Volume</b></td>"
			. "\n\t<td bgcolor=\"#DEDEDE\">" . fieldError("volumeNo", $errors) . "<input type=\"text\" name=\"volumeNo\" value=\"$volumeNo\" size=\"14\" title=\"the volume of the specified publication\"></td>"
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Issue</b></td>"
			. "\n\t<td bgcolor=\"#DEDEDE\"><input type=\"text\" name=\"issueNo\" value=\"$issueNo\" size=\"14\" title=\"the issue of the specified volume\"></td>"
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Pages</b></td>"
			. "\n\t<td align=\"right\" bgcolor=\"#DEDEDE\">" . fieldError("pagesNo", $errors) . "<input type=\"text\" name=\"pagesNo\" value=\"$pagesNo\" size=\"14\" title=\"papers &amp; book chapters: e.g. '12-18' (no 'pp'!), whole books: e.g. '316 pp'\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Corporate Author</b></td>"
			. "\n\t<td colspan=\"3\"><input type=\"text\" name=\"corporateAuthorName\" value=\"$corporateAuthorName\" size=\"48\" title=\"author affiliation\"></td>"
			. "\n\t<td width=\"74\"><b>Thesis</b></td>";

	$thesisType = "\n\t<td align=\"right\">\n\t\t<select name=\"thesisName\" title=\"if this is a thesis, specify the degree here\">\n\t\t\t<option></option>\n\t\t\t<option>Bachelor's thesis</option>\n\t\t\t<option>Master's thesis</option>\n\t\t\t<option>Ph.D. thesis</option>\n\t\t\t<option>Diploma thesis</option>\n\t\t\t<option>Doctoral thesis</option>\n\t\t\t<option>Habilitation thesis</option>\n\t\t</select>\n\t</td>";
	if ((!empty($thesisName)) AND (("$recordAction" == "edit") OR ($mode == "import")))
		$thesisType = ereg_replace("<option>$thesisName", "<option selected>$thesisName", $thesisType);

	echo "$thesisType"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Address</b></td>"
			. "\n\t<td colspan=\"5\"><input type=\"text\" name=\"addressName\" value=\"$addressName\" size=\"85\" title=\"any contact information\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Keywords</b></td>"
			. "\n\t<td colspan=\"5\"><input type=\"text\" name=\"keywordsName\" value=\"$keywordsName\" size=\"85\" title=\"keywords given by the authors, please enter your own keywords below; multiple items should be separated with a semicolon &amp; a space ('; ')\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Abstract</b></td>"
			. "\n\t<td colspan=\"5\"><textarea name=\"abstractName\" rows=\"6\" cols=\"83\" title=\"the abstract for this publication (if any)\">$abstractName</textarea></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Publisher</b></td>"
			. "\n\t<td><input type=\"text\" name=\"publisherName\" value=\"$publisherName\" size=\"14\" title=\"the publisher of this publication\"></td>"
			. "\n\t<td width=\"74\"><b>Place</b></td>"
			. "\n\t<td><input type=\"text\" name=\"placeName\" value=\"$placeName\" size=\"14\" title=\"the place of publication\"></td>"
			. "\n\t<td width=\"74\"><b>Editor</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"editorName\" value=\"$editorName\" size=\"14\" title=\"the editor(s) of this publication (e.g. 'Clough, LM; de Broyer, H-C; Ambrose Jr., WG'); please separate multiple editors with a semicolon &amp; a space ('; ')\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Language</b></td>"
			. "\n\t<td>" . fieldError("languageName", $errors) . "<input type=\"text\" name=\"languageName\" value=\"$languageName\" size=\"14\" title=\"language of the body text\"></td>"
			. "\n\t<td width=\"74\"><b>Summary Language</b></td>"
			. "\n\t<td><input type=\"text\" name=\"summaryLanguageName\" value=\"$summaryLanguageName\" size=\"14\" title=\"language of the summary or abstract (if any)\"></td>"
			. "\n\t<td width=\"74\"><b>Orig Title</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"OrigTitleName\" value=\"$OrigTitleName\" size=\"14\" title=\"original title of this publication (if any)\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Series Editor</b></td>"
			. "\n\t<td><input type=\"text\" name=\"seriesEditorName\" value=\"$seriesEditorName\" size=\"14\" title=\"if this publication belongs to a series, specify the series editor(s) here\"></td>"
			. "\n\t<td width=\"74\"><b>Series Title</b></td>"
			. "\n\t<td><input type=\"text\" name=\"seriesTitleName\" value=\"$seriesTitleName\" size=\"14\" title=\"if this publication belongs to a series, give the full title of the series here\"></td>"
			. "\n\t<td width=\"74\"><b>Abbrev Series Title</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"abbrevSeriesTitleName\" value=\"$abbrevSeriesTitleName\" size=\"14\" title=\"if this publication belongs to a series, give the abbreviated title of the series here\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Series Volume</b></td>"
			. "\n\t<td><input type=\"text\" name=\"seriesVolumeNo\" value=\"$seriesVolumeNo\" size=\"14\" title=\"if this publication belongs to a series, enter the volume of the series here\"></td>"
			. "\n\t<td width=\"74\"><b>Series Issue</b></td>"
			. "\n\t<td><input type=\"text\" name=\"seriesIssueNo\" value=\"$seriesIssueNo\" size=\"14\" title=\"if this publication belongs to a series, enter the issue of the series volume here\"></td>"
			. "\n\t<td width=\"74\"><b>Edition</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"editionNo\" value=\"$editionNo\" size=\"14\" title=\"if it's not the first edition, please specify the edition number of this publication\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>ISSN</b></td>"
			. "\n\t<td><input type=\"text\" name=\"issnName\" value=\"$issnName\" size=\"14\" title=\"if this publication is a journal or dissertation, please specify it's ISSN number\"></td>"
			. "\n\t<td width=\"74\"><b>ISBN</b></td>"
			. "\n\t<td><input type=\"text\" name=\"isbnName\" value=\"$isbnName\" size=\"14\" title=\"if this publication is a book (chapter), please specify it's ISBN number\"></td>"
			. "\n\t<td width=\"74\"><b>Medium</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"mediumName\" value=\"$mediumName\" size=\"14\" title=\"please specify if not paper (like e.g. CD-ROM, cassettes, disks, transparencies, negatives, slides, etc.)\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Area</b></td>"
			. "\n\t<td><input type=\"text\" name=\"areaName\" value=\"$areaName\" size=\"14\" title=\"the area of investigation this publication deals with; multiple items should be separated with a semicolon &amp; a space ('; ')\"></td>"
			. "\n\t<td width=\"74\"><b>Expedition</b></td>"
			. "\n\t<td><input type=\"text\" name=\"expeditionName\" value=\"$expeditionName\" size=\"14\" title=\"the name of the expedition where sampling took place\"></td>"
			. "\n\t<td width=\"74\"><b>Conference</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"conferenceName\" value=\"$conferenceName\" size=\"14\" title=\"any conference this publication was initially presented at\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Notes</b></td>"
			. "\n\t<td colspan=\"3\"><input type=\"text\" name=\"notesName\" value=\"$notesName\" size=\"48\" title=\"enter any generic notes here\"></td>"
			. "\n\t<td width=\"74\"><b>Approved</b></td>";

	$approved = "\n\t<td align=\"right\"><input type=\"radio\" name=\"approvedRadio\" value=\"yes\" title=\"choose 'yes' if you've verified this record for correctness, otherwise set to 'no'\">&nbsp;&nbsp;yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"approvedRadio\" value=\"no\" title=\"choose 'yes' if you've verified this record for correctness, otherwise set to 'no'\">&nbsp;&nbsp;no</td>";
	if ("$recordAction" == "edit")
		if ($approvedRadio == "yes")
			$approved = ereg_replace("name=\"approvedRadio\" value=\"yes\"", "name=\"approvedRadio\" value=\"yes\" checked", $approved);
		else // ($approvedRadio == "no")
			$approved = ereg_replace("name=\"approvedRadio\" value=\"no\"", "name=\"approvedRadio\" value=\"no\" checked", $approved);
	elseif ("$recordAction" == "add") // set $approvedRadio to "no"
		$approved = ereg_replace("name=\"approvedRadio\" value=\"no\"", "name=\"approvedRadio\" value=\"no\" checked", $approved);

	echo "$approved"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Location</b></td>"
			. "\n\t<td colspan=\"5\"><input type=\"text\" name=\"locationName\" value=\"$locationName\" size=\"85\" title=\"shows all users who have added this record to their personal literature data set.$fieldLock\"$fieldLock></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Call Number</b></td>";

	// if the user isn't logged in -OR- any normal user is logged in (not the admin)...
	if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail)))
		// ...we just show the user's own call number (if any):
		echo "\n\t<td colspan=\"3\" bgcolor=\"#DEDEDE\">" . fieldError("callNumberNameUserOnly", $errors) . "<input type=\"text\" name=\"callNumberNameUserOnly\" value=\"$callNumberNameUserOnly\" size=\"48\" title=\"enter your own reference number that uniquely identifies this record for you\"></td>";
	else // if the admin is logged in...
		// ...we display the full contents of the 'call_number' field:
		echo "\n\t<td colspan=\"3\" bgcolor=\"#DEDEDE\"><input type=\"text\" name=\"callNumberName\" value=\"$callNumberName\" size=\"48\" title=\"institutional_abbreviation @ user_id @ user_reference_id\"></td>";

	echo "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Serial</b></td>"
			. "\n\t<td align=\"right\" bgcolor=\"#DEDEDE\"><input type=\"text\" name=\"serialNo\" value=\"$serialNo\" size=\"14\" title=\"this is the unique serial number for this record\" readonly></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" bgcolor=\"#FFFFCC\"><b>Marked</b></td>";

	$marked = "\n\t<td bgcolor=\"#FFFFCC\"><input type=\"radio\" name=\"markedRadio\" value=\"yes\" title=\"mark this record if you'd like to easily retrieve it afterwards\">&nbsp;&nbsp;yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"markedRadio\" value=\"no\" title=\"mark this record if you'd like to easily retrieve it afterwards\">&nbsp;&nbsp;no</td>";
	if ("$recordAction" == "edit")
		if ($markedRadio == "yes")
			$marked = ereg_replace("name=\"markedRadio\" value=\"yes\"", "name=\"markedRadio\" value=\"yes\" checked", $marked);
		else // ($markedRadio == "no")
			$marked = ereg_replace("name=\"markedRadio\" value=\"no\"", "name=\"markedRadio\" value=\"no\" checked", $marked);
	elseif ("$recordAction" == "add") // set $markedRadio to "no"
		$marked = ereg_replace("name=\"markedRadio\" value=\"no\"", "name=\"markedRadio\" value=\"no\" checked", $marked);

	echo "$marked"
			. "\n\t<td width=\"74\" bgcolor=\"#FFFFCC\"><b>Copy</b></td>";
	
	$copy = "\n\t<td bgcolor=\"#FFFFCC\">\n\t\t<select name=\"copyName\" title=\"set to 'true' if you own a copy of this publication, adjust otherwise if not\">\n\t\t\t<option>true</option>\n\t\t\t<option>fetch</option>\n\t\t\t<option>ordered</option>\n\t\t\t<option>false</option>\n\t\t</select>\n\t</td>";
	if ("$recordAction" == "edit")
		$copy = ereg_replace("<option>$copyName", "<option selected>$copyName", $copy);
	
	echo "$copy"
			. "\n\t<td width=\"74\" bgcolor=\"#FFFFCC\"><b>Selected</b></td>";

	$selected = "\n\t<td align=\"right\" bgcolor=\"#FFFFCC\"><input type=\"radio\" name=\"selectedRadio\" value=\"yes\" title=\"select this record if this is one of your important publications\">&nbsp;&nbsp;yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"selectedRadio\" value=\"no\" title=\"select this record if this is one of your important publications\">&nbsp;&nbsp;no</td>";
	if ("$recordAction" == "edit")
		if ($selectedRadio == "yes")
			$selected = ereg_replace("name=\"selectedRadio\" value=\"yes\"", "name=\"selectedRadio\" value=\"yes\" checked", $selected);
		else // ($selectedRadio == "no")
			$selected = ereg_replace("name=\"selectedRadio\" value=\"no\"", "name=\"selectedRadio\" value=\"no\" checked", $selected);
	elseif ("$recordAction" == "add") // set $selectedRadio to "no"
		$selected = ereg_replace("name=\"selectedRadio\" value=\"no\"", "name=\"selectedRadio\" value=\"no\" checked", $selected);

	echo "$selected"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" bgcolor=\"#FFFFCC\"><b>User Keys</b></td>"
			. "\n\t<td colspan=\"5\" bgcolor=\"#FFFFCC\"><input type=\"text\" name=\"userKeysName\" value=\"$userKeysName\" size=\"85\" title=\"enter your personal keywords here; multiple items should be separated with a semicolon &amp; a space ('; ')\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" bgcolor=\"#FFFFCC\"><b>User Notes</b></td>"
			. "\n\t<td colspan=\"5\" bgcolor=\"#FFFFCC\"><input type=\"text\" name=\"userNotesName\" value=\"$userNotesName\" size=\"85\" title=\"enter your personal notes here\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" bgcolor=\"#FFFFCC\"><b>User File</b></td>"
			. "\n\t<td colspan=\"5\" bgcolor=\"#FFFFCC\"><input type=\"text\" name=\"userFileName\" value=\"$userFileName\" size=\"85\" title=\"if this record corresponds to any personal file(s) or directory on your disk, you can enter the file/dir spec(s) here\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>File</b></td>"
			. "\n\t<td colspan=\"3\"><input type=\"text\" name=\"fileName\" value=\"$fileName\" size=\"48\" title=\"if there's a file associated with this record (e.g. a PDF file) please use the upload button and select the file to copy it to the server\"$fieldLock></td>"
			. "\n\t<td valign=\"bottom\" colspan=\"2\">" . fieldError("uploadFile", $errors) . "<input type=\"file\" name=\"uploadFile\" size=\"17\" title=\"upload any file that's associated with this record\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>URL</b></td>"
			. "\n\t<td colspan=\"3\"><input type=\"text\" name=\"urlName\" value=\"$urlName\" size=\"48\" title=\"the web address providing more information for this publication (if any)\"></td>"
			. "\n\t<td width=\"74\"><b>DOI</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"doiName\" value=\"$doiName\" size=\"14\" title=\"the unique 'digital object identifier' of this publication (if available)\"></td>"
			. "\n</tr>";

	if ($onlinePublication == "yes") // if the 'online_publication' field value is "yes"
		$onlinePublicationCheckBoxIsChecked = " checked"; // mark the 'Online publication' checkbox
	else
		$onlinePublicationCheckBoxIsChecked = ""; // don't mark the 'Online publication' checkbox

	echo "\n<tr>"
			. "\n\t<td width=\"74\">&nbsp;</td>"
			. "\n\t<td colspan=\"3\">\n\t\t<input type=\"checkbox\" name=\"onlinePublicationCheckBox\" value=\"1\"$onlinePublicationCheckBoxIsChecked title=\"mark this checkbox if this record refers to an online publication that has no print equivalent (yet)\">&nbsp;"
			. "\n\t\tOnline publication. Cite with this text:&nbsp;<input type=\"text\" name=\"onlineCitationName\" value=\"$onlineCitationName\" size=\"9\" title=\"enter any additional info that's required to locate the online location of this publication\">\n\t</td>";

	if (isset($loginEmail)) // if a user is logged in...
	{
		// ...we'll show a checkbox where the user can state that the current publication stems form his own institution
		if (ereg("$abbrevInstitution", $contributionID)) // if the currrent user's abbreviated institution name is listed within the 'contribution_id' field
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

		echo "\n\t<td colspan=\"2\">\n\t\t<input type=\"checkbox\" name=\"contributionIDCheckBox\" value=\"1\"$contributionIDCheckBoxIsChecked title=\"mark this checkbox if one of the authors of this publication belongs to your own institution\"$contributionIDCheckBoxLock>&nbsp;"
				. "\n\t\tThis is a" . $n . " " . htmlentities($abbrevInstitution) . " publication.\n\t</td>"; // we make use of the session variable '$abbrevInstitution' here
	}
	else
	{
		echo "\n\t<td colspan=\"2\">&nbsp;</td>";
	}

	echo "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\">&nbsp;</td>"
			. "\n\t<td colspan=\"5\">&nbsp;</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td width=\"74\">Location Field:</td>";

	$locationSelector = "\n\t<td colspan=\"3\">\n\t\t<select name=\"locationSelector\" title=\"choose 'Add' if this record belongs to your personal literature data set, choose 'Remove' to delete it again from your own literature data set\">\n\t\t\t<option>Don't touch</option>\n\t\t\t<option>Add</option>\n\t\t\t<option>Remove</option>\n\t\t</select>&nbsp;&nbsp;\n\t\tmy name &amp; email address\n\t</td>";
	if (ereg("^Add", $pageTitle)) // if '$recordAction' == "add"
	{
		$locationSelector = ereg_replace("<option>Add", "<option selected>Add", $locationSelector); // select the appropriate menu entry ...
		if ((!isset($loginEmail)) OR ((isset($loginEmail)) AND ($loginEmail != $adminLoginEmail))) // ... and if the user isn't logged in -OR- any normal user is logged in (not the admin) ...
			$locationSelector = ereg_replace("<select", "<select disabled", $locationSelector); // ... disable the popup menu. This is, since the current user & email address will be always written to the location field when adding new records. An orphaned record would be produced if the user could chose anything other than 'Add'! (Note that the admin is permitted to override this behaviour)
	}

	echo "$locationSelector"
			. "\n\t<td align=\"right\" colspan=\"2\">"
			. "<input type=\"submit\" name=\"submit\" value=\"$pageTitle\">";
			
	if ("$recordAction" == "edit") // add a DELETE button (CAUTION: the delete button must be displayed *AFTER* the edit button, otherwise DELETE will be the default action if the user hits return!!)
								// (this is since the first displayed submit button represents the default submit action in several browsers!! [like OmniWeb or Mozilla])
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" name=\"submit\" value=\"Delete Record\">";

	echo "</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";
	
	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to disconnect from the database:", "");

	// --------------------------------------------------------------------

	// SHOW ERROR IN RED:
	function fieldError($fieldName, $errors)
	{
		if (isset($errors[$fieldName]))
			return "<b><span class=\"warning2\">" . $errors[$fieldName] . "</span></b><br>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter($oldQuery);

	// --------------------------------------------------------------------
?>
</body>
</html> 
