<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./record.php
	// Created:    29-Jul-02, 16:39
	// Modified:   20-Jan-03, 23:29

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
	$serialNo = $_REQUEST['serialNo']; // fetch the serial number of the record to edit
	$oldQuery = $_REQUEST['oldQuery']; // fetch the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "

	// If there's no stored message available:
	if (!session_is_registered("HeaderString")) // if there's no stored message available
		if (empty($errors)) // provide one of the default messages:
			if ("$recordAction" == "edit") // *edit* record
				$HeaderString = "Edit the following record:";
			else // *add* record will be the default action if no parameter is given
				$HeaderString = "Add a record to the database:";
		else // -> there were errors validating the data entered by the user
			$HeaderString = "There were validation errors regarding the data you entered. Please check the comments above the respective fields.";
	else
		session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

	if ("$recordAction" == "edit") // *edit* record
	{
		$pageTitle = "Edit Record";
	}
	else // *add* record will be the default action if no parameter is given
	{
		$pageTitle = "Add Record";
		$serialNo = "(not assigned yet)";
	}

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:
	// if the script was called with parameters (like: 'record.php?recordAction=edit&serialNo=...')
	if ("$recordAction" == "edit")
		{
			// for the selected record, select *all* available fields:
			// (note: we also add the 'serial' column at the end in order to provide standardized input [compare processing of form 'sql_search.php' in 'search.php'])
			$query = "SELECT author, title, year, publication, abbrev_journal, volume, issue, pages, address, corporate_author, keywords, abstract, publisher, place, editor, language, summary_language, orig_title, series_editor, series_title, abbrev_series_title, series_volume, series_issue, edition, issn, isbn, medium, area, expedition, conference, location, call_number, reprint_status, marked, approved, file, serial, type, notes, user_keys, user_notes, serial, url, doi";
	
			$query .= " FROM refs WHERE serial RLIKE \"^(" . $serialNo . ")$\""; // since we'll only fetch one record, the ORDER BY clause is obsolete here
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
				$authorName = htmlentities($row[author]);
				$titleName = htmlentities($row[title]);
				$yearNo = htmlentities($row[year]);
				$publicationName = htmlentities($row[publication]);
				$abbrevJournalName = htmlentities($row[abbrev_journal]);
				$volumeNo = htmlentities($row[volume]);
				$issueNo = htmlentities($row[issue]);
				$pagesNo = htmlentities($row[pages]);
				$addressName = htmlentities($row[address]);
				$corporateAuthorName = htmlentities($row[corporate_author]);
				$keywordsName = htmlentities($row[keywords]);
				$abstractName = htmlentities($row[abstract]);
				$publisherName = htmlentities($row[publisher]);
				$placeName = htmlentities($row[place]);
				$editorName = htmlentities($row[editor]);
				$languageName = htmlentities($row[language]);
				$summaryLanguageName = htmlentities($row[summary_language]);
				$OrigTitleName = htmlentities($row[orig_title]);
				$seriesEditorName = htmlentities($row[series_editor]);
				$seriesTitleName = htmlentities($row[series_title]);
				$abbrevSeriesTitleName = htmlentities($row[abbrev_series_title]);
				$seriesVolumeNo = htmlentities($row[series_volume]);
				$seriesIssueNo = htmlentities($row[series_issue]);
				$editionNo = htmlentities($row[edition]);
				$issnName = htmlentities($row[issn]);
				$isbnName = htmlentities($row[isbn]);
				$mediumName = htmlentities($row[medium]);
				$areaName = htmlentities($row[area]);
				$expeditionName = htmlentities($row[expedition]);
				$conferenceName = htmlentities($row[conference]);
				$locationName = htmlentities($row[location]);
				$callNumberName = htmlentities($row[call_number]);
				$reprintStatusName = htmlentities($row[reprint_status]);
				$markedRadio = htmlentities($row[marked]);
				$approvedRadio = htmlentities($row[approved]);
				$fileName = htmlentities($row[file]);
				$serialNo = htmlentities($row[serial]);
				$typeName = htmlentities($row[type]);
				$notesName = htmlentities($row[notes]);
				$userKeysName = htmlentities($row[user_keys]);
				$userNotesName = htmlentities($row[user_notes]);
			}
			else
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

		}
	else // if ("$recordAction" == "add"), i.e., adding a new record...
		{
			// ...set all variables to "":
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
			$locationName = "";
			$callNumberName = "";
			$reprintStatusName = "";
			$markedRadio = "";
			$approvedRadio = "";
			$fileName = "";
			$serialNo = $serialNo; // supply some generic info: "(not assigned yet)" [as defined at the top]
			$typeName = "";
			$notesName = "";
			$userKeysName = "";
			$userNotesName = "";
		}

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// (4a) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- " . $pageTitle, "index,follow", "Add, edit or delete a record in the " . htmlentities($officialDatabaseName), "", false, "confirmDelete.js");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks);

	// (4b) DISPLAY results:
	// Start <form> and <table> holding the form elements:
	echo "\n<form onsubmit=\"return(confirmDelete(this.submit))\" action=\"modify.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"record\">";
	echo "\n<input type=\"hidden\" name=\"submit\" value=\"" . $pageTitle . "\">"; // provide a default value for the 'submit' form tag (then, hitting <enter> within a text entry field will act as if the user clicked the 'Add/Edit Record' button)
	echo "\n<input type=\"hidden\" name=\"recordAction\" value=\"" . $recordAction . "\">";
	echo "\n<input type=\"hidden\" name=\"oldQuery\" value=\"" . rawurlencode($oldQuery) . "\">"; // we include a link to the formerly displayed results page as hidden form tag so that it's available on the subsequent receipt page that follows any add/edit/delete action!
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" width=\"600\" summary=\"This table holds a form that offers to add records or edit existing ones\">"
			. "\n<tr>"
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Author</b></td>"
			. "\n\t<td colspan=\"5\" bgcolor=\"#DEDEDE\">" . fieldError("authorName", $errors) . "<input type=\"text\" name=\"authorName\" value=\"$authorName\" size=\"85\" title=\"please separate multiple authors with a semicolon &amp; a space ('; ')\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\" bgcolor=\"#DEDEDE\"><b>Title</b></td>"
			. "\n\t<td colspan=\"5\" bgcolor=\"#DEDEDE\">" . fieldError("titleName", $errors) . "<input type=\"text\" name=\"titleName\" value=\"$titleName\" size=\"85\" title=\"please don't append any dot to the title!\"></td>"
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
			. "\n\t<td width=\"74\"><b>Address</b></td>"
			. "\n\t<td colspan=\"3\"><input type=\"text\" name=\"addressName\" value=\"$addressName\" size=\"49\" title=\"any contact information\"></td>"
			. "\n\t<td width=\"74\"><b>Corporate Author</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"corporateAuthorName\" value=\"$corporateAuthorName\" size=\"14\" title=\"author affiliation\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Keywords</b></td>"
			. "\n\t<td colspan=\"5\"><input type=\"text\" name=\"keywordsName\" value=\"$keywordsName\" size=\"85\" title=\"keywords given by the authors, please enter your own keywords below\"></td>"
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
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"editorName\" value=\"$editorName\" size=\"14\" title=\"the editor(s) of this publication, please separate multiple editors with '; '\"></td>"
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
			. "\n\t<td><input type=\"text\" name=\"areaName\" value=\"$areaName\" size=\"14\" title=\"the area of investigation this publication deals with\"></td>"
			. "\n\t<td width=\"74\"><b>Expedition</b></td>"
			. "\n\t<td><input type=\"text\" name=\"expeditionName\" value=\"$expeditionName\" size=\"14\" title=\"the name of the expedition where sampling took place\"></td>"
			. "\n\t<td width=\"74\"><b>Conference</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"conferenceName\" value=\"$conferenceName\" size=\"14\" title=\"any conference this publication was initially presented at\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Location</b></td>"
			. "\n\t<td><input type=\"text\" name=\"locationName\" value=\"$locationName\" size=\"14\" title=\"the physical location of this publication, if it's at your place, give your full name &amp; email address\"></td>"
			. "\n\t<td width=\"74\"><b>Call Number</b></td>"
			. "\n\t<td><input type=\"text\" name=\"callNumberName\" value=\"$callNumberName\" size=\"14\" title=\"your_institutional_abbreviation @ your_user_id @ your_own_reference_id\"></td>"
			. "\n\t<td width=\"74\"><b>Reprint Status</b></td>";
	
	$reprintStatus = "\n\t<td align=\"right\">\n\t\t<select name=\"reprintStatusName\" title=\"set to 'true' if you own a copy of this publication, adjust otherwise if not\">\n\t\t\t<option>true</option>\n\t\t\t<option>false</option>\n\t\t\t<option>requested</option>\n\t\t\t<option>fetch</option>\n\t\t</select>\n\t</td>";
	if ("$recordAction" == "edit")
		$reprintStatus = ereg_replace("<option>$reprintStatusName", "<option selected>$reprintStatusName", $reprintStatus);
	
	echo "$reprintStatus"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Marked</b></td>";

	$marked = "\n\t<td><input type=\"radio\" name=\"markedRadio\" value=\"1\" title=\"mark this record if you'd like to easily retrieve it afterwards\">&nbsp;&nbsp;yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"markedRadio\" value=\"0\" title=\"mark this record if you'd like to easily retrieve it afterwards\">&nbsp;&nbsp;no</td>";
	if ("$recordAction" == "edit")
		if ($markedRadio == "Y")
			$marked = ereg_replace("name=\"markedRadio\" value=\"1\"", "name=\"markedRadio\" value=\"1\" checked", $marked);
		else // ($markedRadio == "N")
			$marked = ereg_replace("name=\"markedRadio\" value=\"0\"", "name=\"markedRadio\" value=\"0\" checked", $marked);

	echo "$marked"
			. "\n\t<td width=\"74\"><b>Approved</b></td>";

	$approved = "\n\t<td><input type=\"radio\" name=\"approvedRadio\" value=\"1\" title=\"choose 'yes' if you've verified this record for correctness, otherwise set to 'no'\">&nbsp;&nbsp;yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"approvedRadio\" value=\"0\" title=\"choose 'yes' if you've verified this record for correctness, otherwise set to 'no'\">&nbsp;&nbsp;no</td>";
	if ("$recordAction" == "edit")
		if ($approvedRadio == "Y")
			$approved = ereg_replace("name=\"approvedRadio\" value=\"1\"", "name=\"approvedRadio\" value=\"1\" checked", $approved);
		else // ($approvedRadio == "N")
			$approved = ereg_replace("name=\"approvedRadio\" value=\"0\"", "name=\"approvedRadio\" value=\"0\" checked", $approved);

	echo "$approved"
			. "\n\t<td width=\"74\"><b>File</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"fileName\" value=\"$fileName\" size=\"14\" title=\"if this record corresponds to a particular file on disk, please enter the file name\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>Serial</b></td>"
			. "\n\t<td><input type=\"text\" name=\"serialNo\" value=\"$serialNo\" size=\"14\" title=\"this is the unique serial number for this record\" readonly></td>"
			. "\n\t<td width=\"74\"><b>Record Type</b></td>";

	$recordType = "\n\t<td>\n\t\t<select name=\"typeName\" title=\"please specify the type of this publication (e.g. 'Journal Article' for a paper)\">\n\t\t\t<option>Book Chapter</option>\n\t\t\t<option>Book Whole</option>\n\t\t\t<option>Journal Article</option>\n\t\t\t<option>Journal</option>\n\t\t\t<option>Manuscript</option>\n\t\t\t<option>Map</option>\n\t\t</select>\n\t</td>";
	if ("$recordAction" == "edit")
		$recordType = ereg_replace("<option>$typeName", "<option selected>$typeName", $recordType);
	
	echo "$recordType"
			. "\n\t<td width=\"74\"><b>Notes</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"notesName\" value=\"$notesName\" size=\"14\" title=\"enter any generic notes here\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>User Keys</b></td>"
			. "\n\t<td colspan=\"3\"><input type=\"text\" name=\"userKeysName\" value=\"$userKeysName\" size=\"49\" title=\"enter your personal keywords here\"></td>"
			. "\n\t<td width=\"74\"><b>User Notes</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"userNotesName\" value=\"$userNotesName\" size=\"14\" title=\"enter your personal notes here\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\"><b>URL</b></td>"
			. "\n\t<td colspan=\"3\"><input type=\"text\" name=\"urlName\" value=\"$urlName\" size=\"49\" title=\"the web address providing more information for this publication (if any)\"></td>"
			. "\n\t<td width=\"74\"><b>DOI</b></td>"
			. "\n\t<td align=\"right\"><input type=\"text\" name=\"doiName\" value=\"$doiName\" size=\"14\" title=\"the unique 'document object identifier' of this publication (if available)\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\">&nbsp;</td>"
			. "\n\t<td colspan=\"5\">&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"74\">&nbsp;</td>"
			. "\n\t<td align=\"right\" colspan=\"5\">"
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
			return "<b><span class=\"warning\">" . $errors[$fieldName] . "</span></b><br>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter($oldQuery);

	// --------------------------------------------------------------------
?>
</body>
</html> 
