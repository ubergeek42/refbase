<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./modify.php
	// Created:    18-Dec-02, 23:08
	// Modified:   20-Jan-03, 23:29

	// This php script will perform adding, editing & deleting of records.
	// It then calls 'receipt.php' which displays links to the modified/added record
	// as well as to the previous search results page (if any).

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password
	include 'include.inc'; // include common functions

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
		header("Location: user_login.php?referer=" . rawurlencode($HTTP_REFERER)); // ask the user to login first, then he'll get directed back to 'record.php'

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// [ Extract form variables sent through POST/GET by use of the '$_REQUEST' variable ]
	// [ !! NOTE !!: for details see <http://www.php.net/release_4_2_1.php> & <http://www.php.net/manual/en/language.variables.predefined.php> ]

	// Extract the form used by the user:
	$formType = $_REQUEST['formType'];

	// Extract the type of action requested by the user (either 'add', 'edit' or ''):
	// ('' will be treated equal to 'add')
	$recordAction = $_REQUEST['recordAction'];

	// Determine the button that was hit by the user (either 'Add Record', 'Edit Record', 'Delete Record' or ''):
	// '$submitAction' is only used to determine any 'delet' action! (where '$submitAction' = 'Delete Record')
	// (otherwise, only '$recordAction' controls how to proceed)
	$submitAction = $_REQUEST['submit'];
	if ("$submitAction" == "Delete Record") // *delete* record
		$recordAction = "delet";

	// Extract generic variables from the request:
	$oldQuery = $_REQUEST['oldQuery']; // fetch the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	if (ereg('sqlQuery%3D', $oldQuery)) // if '$oldQuery' still contains URL encoded data... ('%3D' is the URL encoded form of '=', see note below!)
		$oldQuery = rawurldecode($oldQuery); // ...URL decode old query URL (it was URL encoded before incorporation into a hidden tag of the 'record' form to avoid any HTML syntax errors)
										// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
										//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!
	$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "

	// Extract all form values provided by 'record.php':
	$authorName = $_REQUEST['authorName'];
	$titleName = $_REQUEST['titleName'];
	$yearNo = $_REQUEST['yearNo'];
	$publicationName = $_REQUEST['publicationName'];
	$abbrevJournalName = $_REQUEST['abbrevJournalName'];
	$volumeNo = $_REQUEST['volumeNo'];
	$issueNo = $_REQUEST['issueNo'];
	$pagesNo = $_REQUEST['pagesNo'];
	$addressName = $_REQUEST['addressName'];
	$corporateAuthorName = $_REQUEST['corporateAuthorName'];
	$keywordsName = $_REQUEST['keywordsName'];
	$abstractName = $_REQUEST['abstractName'];
	$publisherName = $_REQUEST['publisherName'];
	$placeName = $_REQUEST['placeName'];
	$editorName = $_REQUEST['editorName'];
	$languageName = $_REQUEST['languageName'];
	$summaryLanguageName = $_REQUEST['summaryLanguageName'];
	$OrigTitleName = $_REQUEST['OrigTitleName'];
	$seriesEditorName = $_REQUEST['seriesEditorName'];
	$seriesTitleName = $_REQUEST['seriesTitleName'];
	$abbrevSeriesTitleName = $_REQUEST['abbrevSeriesTitleName'];
	$seriesVolumeNo = $_REQUEST['seriesVolumeNo'];
	$seriesIssueNo = $_REQUEST['seriesIssueNo'];
	$editionNo = $_REQUEST['editionNo'];
	$issnName = $_REQUEST['issnName'];
	$isbnName = $_REQUEST['isbnName'];
	$mediumName = $_REQUEST['mediumName'];
	$areaName = $_REQUEST['areaName'];
	$expeditionName = $_REQUEST['expeditionName'];
	$conferenceName = $_REQUEST['conferenceName'];
	$locationName = $_REQUEST['locationName'];
	$callNumberName = $_REQUEST['callNumberName'];
	$reprintStatusName = $_REQUEST['reprintStatusName'];
	$markedRadio = $_REQUEST['markedRadio'];
	$approvedRadio = $_REQUEST['approvedRadio'];
	$fileName = $_REQUEST['fileName'];
	$serialNo = $_REQUEST['serialNo'];
	$typeName = $_REQUEST['typeName'];
	$notesName = $_REQUEST['notesName'];
	$userKeysName = $_REQUEST['userKeysName'];
	$userNotesName = $_REQUEST['userNotesName'];
	$urlName = $_REQUEST['urlName'];
	$doiName = $_REQUEST['doiName'];

	// --------------------------------------------------------------------

	// VALIDATE data fields:

	// CAUTION: validation of fields is currently disabled, since, IMHO, there are too many open questions how to implement this properly
	//          and without frustrating the user! Uncomment the commented code below to enable the current validation features:
	
//	// NOTE: for all fields that are validated here must exist error parsing code (of the form: " . fieldError("languageName", $errors) . ")
//	//       in front of the respective <input> form field in 'record.php'! Otherwise the generated error won't be displayed!
//	
//
//	// Validate fields that SHOULD not be empty:
//	// Validate the 'Author' field:
//	if (empty($authorName))
//		$errors["authorName"] = "Is there really no author info for this record? Enter NULL to force empty:"; // Author should not be a null string
//
//	// Validate the 'Title' field:
//	if (empty($titleName))
//		$errors["titleName"] = "Is there really no title info for this record? Enter NULL to force empty:"; // Title should not be a null string
//
//	// Validate the 'Year' field:
//	if (empty($yearNo))
//		$errors["yearNo"] = "Is there really no year info for this record? Enter NULL to force empty:"; // Year should not be a null string
//
//	// Validate the 'Publication' field:
//	if (empty($publicationName))
//		$errors["publicationName"] = "Is there really no publication info for this record? Enter NULL to force empty:"; // Publication should not be a null string
//
//	// Validate the 'Abbrev Journal' field:
//	if (empty($abbrevJournalName))
//		$errors["abbrevJournalName"] = "Is there really no abbreviated journal info for this record? Enter NULL to force empty:"; // Abbrev Journal should not be a null string
//
//	// Validate the 'Volume' field:
//	if (empty($volumeNo))
//		$errors["volumeNo"] = "Is there really no volume info for this record? Enter NULL to force empty:"; // Volume should not be a null string
//
//	// Validate the 'Pages' field:
//	if (empty($pagesNo))
//		$errors["pagesNo"] = "Is there really no pages info for this record? Enter NULL to force empty:"; // Pages should not be a null string
//
//
//	// Validate fields that MUST not be empty:
//	// Validate the 'Language' field:
//	if (empty($languageName))
//		$errors["languageName"] = "The language field cannot be blank:"; // Language cannot be a null string
//
//
//	// Remove 'NULL' values that were entered by the user in order to force empty values for required text fields:
//	// (for the required number fields 'yearNo' & 'volumeNo' inserting 'NULL' will cause '0000' or '0' as value, respectively)
//	if ($authorName == "NULL")
//		$authorName = "";
//
//	if ($titleName == "NULL")
//		$titleName = "";
//
//	if ($publicationName == "NULL")
//		$publicationName = "";
//
//	if ($abbrevJournalName == "NULL")
//		$abbrevJournalName = "";
//
//	if ($pagesNo == "NULL")
//		$pagesNo = "";
//
//	// --------------------------------------------------------------------
//
//	// Now the script has finished the validation, check if there were any errors:
//	if (count($errors) > 0)
//	{
//
//		// There are errors. Relocate back to the record entry form:
//		header("Location: $HTTP_REFERER");
//
//		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
//	}

	// --------------------------------------------------------------------

	// If we made it here, then the data is considered valid!

	// CONSTRUCT SQL QUERY:
	// First, setup some required variables:
	$currentDate = date('Y-m-d'); // get the current date in a format recognized by mySQL (which is 'YYYY-MM-DD', e.g.: '2003-12-31')
	$currentTime = date('H:i:s'); // get the current time in a format recognized by mySQL (which is 'HH:MM:SS', e.g.: '23:59:49')
	$currentUser = $loginFirstName . " " . $loginLastName . " (" . $loginEmail . ")"; // here we use session variables to construct the user name, e.g.: 'Matthias Steffens (msteffens@ipoe.uni-kiel.de)'

	$loginEmailArray = split("@", $loginEmail); // split the login email address at '@'
	$loginEmailUserName = $loginEmailArray[0]; // extract the user name (which is the first element of the array '$loginEmailArray')
	$callNumberPrefix = $abbrevInstitution . " @ " . $loginEmailUserName; // again, we use session variables to construct a correct call number prefix, like: 'IP… @ msteffens'

	// Is this an update?
	if ($recordAction == "edit") // alternative method to check for an 'edit' action: if (ereg("^[0-9]+$",$serialNo)) // a valid serial number must be an integer
								// yes, the form already contains a valid serial number, so we'll have to update the relevant record:
			// UPDATE - construct a query to update the relevant record
			$query = "UPDATE refs SET "
					. "author = \"$authorName\", "
					. "title = \"$titleName\", "
					. "year = \"$yearNo\", "
					. "publication = \"$publicationName\", "
					. "abbrev_journal = \"$abbrevJournalName\", "
					. "volume = \"$volumeNo\", "
					. "issue = \"$issueNo\", "
					. "pages = \"$pagesNo\", "
					. "address = \"$addressName\", "
					. "corporate_author = \"$corporateAuthorName\", "
					. "keywords = \"$keywordsName\", "
					. "abstract = \"$abstractName\", "
					. "publisher = \"$publisherName\", "
					. "place = \"$placeName\", "
					. "editor = \"$editorName\", "
					. "language = \"$languageName\", "
					. "summary_language = \"$summaryLanguageName\", "
					. "orig_title = \"$OrigTitleName\", "
					. "series_editor = \"$seriesEditorName\", "
					. "series_title = \"$seriesTitleName\", "
					. "abbrev_series_title = \"$abbrevSeriesTitleName\", "
					. "series_volume = \"$seriesVolumeNo\", "
					. "series_issue = \"$seriesIssueNo\", "
					. "edition = \"$editionNo\", "
					. "issn = \"$issnName\", "
					. "isbn = \"$isbnName\", "
					. "medium = \"$mediumName\", "
					. "area = \"$areaName\", "
					. "expedition = \"$expeditionName\", "
					. "conference = \"$conferenceName\", "
					. "location = \"$locationName\", "
					. "call_number = \"$callNumberName\", "
					. "reprint_status = \"$reprintStatusName\", "
					. "marked = \"$markedRadio\", "
					. "approved = \"$approvedRadio\", "
					. "file = \"$fileName\", "
					. "type = \"$typeName\", "
					. "notes = \"$notesName\", "
					. "user_keys = \"$userKeysName\", "
					. "user_notes = \"$userNotesName\", "
					. "url = \"$urlName\", "
					. "doi = \"$doiName\", "
					. "modified_date = \"$currentDate\", "
					. "modified_time = \"$currentTime\", "
					. "modified_by = \"$currentUser\" "
					. "WHERE serial = $serialNo";

	elseif ($recordAction == "delet")
			$query = "DELETE FROM refs WHERE serial = $serialNo";

	else // if the form does NOT contain a valid serial number, we'll have to add the data:
	{
			// INSERT - construct a query to add data as new record
			$query = "INSERT INTO refs SET "
					. "author = \"$authorName\", "
					. "title = \"$titleName\", "
					. "year = \"$yearNo\", "
					. "publication = \"$publicationName\", "
					. "abbrev_journal = \"$abbrevJournalName\", "
					. "volume = \"$volumeNo\", "
					. "issue = \"$issueNo\", "
					. "pages = \"$pagesNo\", "
					. "address = \"$addressName\", "
					. "corporate_author = \"$corporateAuthorName\", "
					. "keywords = \"$keywordsName\", "
					. "abstract = \"$abstractName\", "
					. "publisher = \"$publisherName\", "
					. "place = \"$placeName\", "
					. "editor = \"$editorName\", "
					. "language = \"$languageName\", "
					. "summary_language = \"$summaryLanguageName\", "
					. "orig_title = \"$OrigTitleName\", "
					. "series_editor = \"$seriesEditorName\", "
					. "series_title = \"$seriesTitleName\", "
					. "abbrev_series_title = \"$abbrevSeriesTitleName\", "
					. "series_volume = \"$seriesVolumeNo\", "
					. "series_issue = \"$seriesIssueNo\", "
					. "edition = \"$editionNo\", "
					. "issn = \"$issnName\", "
					. "isbn = \"$isbnName\", "
					. "medium = \"$mediumName\", "
					. "area = \"$areaName\", "
					. "expedition = \"$expeditionName\", "
					. "conference = \"$conferenceName\", ";

			if ($locationName == "") // if there's no location info provided by the user...
				$query .= "location = \"$currentUser\", "; // ...insert the current user
			else
				$query .= "location = \"$locationName\", "; // ...use the information as entered by the user

			if ($callNumberName == "") // if there's no call number info provided by the user...
				$query .= "call_number = \"$callNumberPrefix\", "; // ...insert the user's call number prefix
			elseif (!ereg("@", $callNumberName)) // if there's a call number provided by the user that does NOT contain any '@' already...
				$query .= "call_number = \"" . $callNumberPrefix . " @ " . $callNumberName . "\", "; // ...then we assume the user entered a call number for this record which should be prefixed with the user's call number prefix
			else
				$query .= "call_number = \"$callNumberName\", "; // ...use the information as entered by the user

			$query .= "reprint_status = \"$reprintStatusName\", "
					. "marked = \"$markedRadio\", "
					. "approved = \"$approvedRadio\", "
					. "file = \"$fileName\", "
					. "serial = NULL, " // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value
					. "type = \"$typeName\", "
					. "notes = \"$notesName\", "
					. "user_keys = \"$userKeysName\", "
					. "user_notes = \"$userNotesName\", "
					. "url = \"$urlName\", "
					. "doi = \"$doiName\", "
					. "created_date = \"$currentDate\", "
					. "created_time = \"$currentTime\", "
					. "created_by = \"$currentUser\", "
					. "modified_date = \"$currentDate\", "
					. "modified_time = \"$currentTime\", "
					. "modified_by = \"$currentUser\"";
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (4) DISPLAY HEADER & RESULTS, (5) CLOSE CONNECTION

	// (1) OPEN the database connection:
	//      (variables are set by include file 'db.inc'!)
	if (!($connection = @ mysql_connect($hostName, $username, $password)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to connect to the host:", $oldQuery);

	// (2) SELECT the database:
	//      (variables are set by include file 'db.inc'!)
	if (!(mysql_select_db($databaseName, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to connect to the database:", $oldQuery);

	// (3) RUN the query on the database through the connection:
	if (!($result = @ mysql_query($query, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", $oldQuery);

	// Is this an insert?
	if ($recordAction != "edit" && $recordAction != "delet") // alternative method to check for an 'add' action: if (!ereg("^[0-9]+$",$serialNo)) // -> Yes, this an insert -- since '$serialNo' doesn't contain an integer. We'll have to add the data as a new record.
									//     [If there's no serial number yet, the string "(not assigned yet)" gets inserted by 'record.php' (on '$recordAction=add')]
			$serialNo = mysql_insert_id(); // find out the unique ID number of the newly created record (Note: this function should be called immediately after the
										// SQL INSERT statement! After any subsequent query it won't be possible to retrieve the auto_increment identifier value for THIS record!)

	// Build correct header message:
	$headerMsg = "The record no. " . $serialNo . " has been successfully " . $recordAction . "ed.";


	// (4) Call 'receipt.php' which displays links to the modifyed/added record as well as to the previous search results page (if any)
	//     (routing feedback output to a different script page will avoid any reload problems effectively!)
	header("Location: receipt.php?recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery));


	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to disconnect from the database:", $oldQuery);

	// --------------------------------------------------------------------
?>
