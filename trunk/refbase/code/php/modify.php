<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./modify.php
	// Created:    18-Dec-02, 23:08
	// Modified:   05-Sep-03, 23:20

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
	$isEditorCheckBox = $_REQUEST['isEditorCheckBox'];
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
	$copyName = $_REQUEST['copyName'];
	$markedRadio = $_REQUEST['markedRadio'];
	$approvedRadio = $_REQUEST['approvedRadio'];
	$fileName = $_REQUEST['fileName'];
	$serialNo = $_REQUEST['serialNo'];
	$typeName = $_REQUEST['typeName'];
	$notesName = $_REQUEST['notesName'];
	$userKeysName = $_REQUEST['userKeysName'];
	$userNotesName = $_REQUEST['userNotesName'];
	$userFileName = $_REQUEST['userFileName'];
	$urlName = $_REQUEST['urlName'];
	$doiName = $_REQUEST['doiName'];
	$locationSelector = $_REQUEST['locationSelector'];

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

	// (1) OPEN CONNECTION, (2) SELECT DATABASE

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

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:
	// First, setup some required variables:
	$currentDate = date('Y-m-d'); // get the current date in a format recognized by mySQL (which is 'YYYY-MM-DD', e.g.: '2003-12-31')
	$currentTime = date('H:i:s'); // get the current time in a format recognized by mySQL (which is 'HH:MM:SS', e.g.: '23:59:49')
	$currentUser = $loginFirstName . " " . $loginLastName . " (" . $loginEmail . ")"; // here we use session variables to construct the user name, e.g.: 'Matthias Steffens (msteffens@ipoe.uni-kiel.de)'

	$loginEmailArray = split("@", $loginEmail); // split the login email address at '@'
	$loginEmailUserName = $loginEmailArray[0]; // extract the user name (which is the first element of the array '$loginEmailArray')
	$callNumberPrefix = $abbrevInstitution . " @ " . $loginEmailUserName; // again, we use session variables to construct a correct call number prefix, like: 'IP… @ msteffens'

	if ($isEditorCheckBox == "1" OR ereg("^(Book Whole|Journal|Manuscript|Map)$", $typeName)) // if the user did mark the 'is Editor' checkbox -OR- if the record type is either 'Book Whole', 'Journal', 'Map' or 'Manuscript'...
		if (!empty($editorName) AND empty($authorName)) // ...and if the 'Editor' field has some content while the 'Author' field is blank...
		{
			$authorName = $editorName; // duplicate field contents from 'editor' to 'author' field
			$isEditorCheckBox = "1"; // since the user entered something in the 'editor' field (but not the 'author' field), we need to make sure that the 'is Editor' is marked
		}

	if ($isEditorCheckBox == "1" AND ereg("^(Book Whole|Journal|Manuscript|Map)$", $typeName)) // if the user did mark the 'is Editor' checkbox -AND- the record type is either 'Book Whole', 'Journal', 'Map' or 'Manuscript'...
	{
		$authorName = ereg_replace(" *\(eds?\)$","",$authorName); // ...remove any existing editor info from the 'author' string, i.e., kill any trailing " (ed)" or " (eds)"

		if (!empty($authorName)) // if the 'Author' field has some content...
			$editorName = $authorName; // ...duplicate field contents from 'author' to 'editor' field (CAUTION: this will overwrite any existing contents in the 'editor' field!)

		if (!empty($authorName)) // if 'author' field isn't empty
		{
			if (!ereg(";", $authorName)) // if the 'author' field does NOT contain a ';' (which would delimit multiple authors) => single author
				$authorName .= " (ed)"; // append " (ed)" to the end of the 'author' string
			else // the 'author' field does contain at least one ';' => multiple authors
				$authorName .= " (eds)"; // append " (eds)" to the end of the 'author' string
		}
	}
	else // the 'is Editor' checkbox is NOT checked -OR- the record type is NOT 'Book Whole', 'Journal', 'Map' or 'Manuscript'...
	{
		if (ereg(" *\(eds?\)$", $authorName)) // if 'author' field ends with either " (ed)" or " (eds)"
			$authorName = ereg_replace(" *\(eds?\)$","",$authorName); // remove any existing editor info from the 'author' string, i.e., kill any trailing " (ed)" or " (eds)"

		if ($authorName == $editorName) // if the 'Author' field contents equal the 'Editor' field contents...
			$editorName = ""; // ...clear contents of 'editor' field (that is, we assume that the user did uncheck the 'is Editor' checkbox, which was previously marked)
	}
	

	if (!empty($authorName))
	{
		$first_author = ereg_replace("^([^;]+).*","\\1",$authorName); // extract first author from 'author' field
		$first_author = trim($first_author); // remove leading & trailing whitespace (if any)

		if (!ereg(";", $authorName)) // if the 'author' field does NOT contain a ';' (which would delimit multiple authors) => single author
			$author_count = "1"; // indicates a single author
		elseif (ereg("^[^;]+;[^;]+$", $authorName)) // the 'author' field does contain exactly one ';' => two authors
			$author_count = "2"; // indicates two authors
		elseif (ereg("^[^;]+;[^;]+;[^;]+", $authorName)) // the 'author' field does contain at least two ';' => more than two authors
			$author_count = "3"; // indicates three (or more) authors
	}

	if (!empty($pagesNo))
		$first_page = ereg_replace("^[^0-9]*([0-9]+).*","\\1",$pagesNo); // extract first page from 'pages' field


	// Is this an update?
	if ($recordAction == "edit") // alternative method to check for an 'edit' action: if (ereg("^[0-9]+$",$serialNo)) // a valid serial number must be an integer
								// yes, the form already contains a valid serial number, so we'll have to update the relevant record:
	{
			// UPDATE - construct queries to update the relevant record
			$queryRefs = "UPDATE refs SET "
					. "author = \"$authorName\", "
					. "first_author = \"$first_author\", "
					. "author_count = \"$author_count\", "
					. "title = \"$titleName\", "
					. "year = \"$yearNo\", "
					. "publication = \"$publicationName\", "
					. "abbrev_journal = \"$abbrevJournalName\", "
					. "volume = \"$volumeNo\", "
					. "issue = \"$issueNo\", "
					. "pages = \"$pagesNo\", "
					. "first_page = \"$first_page\", "
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

			if (($locationSelector == "Add") AND (!ereg("$loginEmail", $locationName))) // add the current user to the 'location' field (if he/she isn't listed already within the 'location' field):
			{
				$locationName = ereg_replace("(.+)", "\\1; $currentUser", $locationName); // the 'location' field does already contain some content
				$locationName = ereg_replace("^$", "$currentUser", $locationName); // the 'location' field is empty
			}
			elseif ($locationSelector == "Remove") // remove the current user from the 'location' field:
			{ // the only pattern that's really unique is the users email address, the user's name may change (since it can be modified by the user). This is why we dont use '$currentUser' here:
				$locationName = ereg_replace("^[^;]*\( *$loginEmail *\) *; *", "", $locationName); // the current user occurs after some other user within the 'location' field
				$locationName = ereg_replace(" *;[^;]*\( *$loginEmail *\) *", "", $locationName); // the current user is listed at the very beginning of the 'location' field
				$locationName = ereg_replace("^[^;]*\( *$loginEmail *\) *$", "", $locationName); // the current user is the only one listed within 'location' field
			}
			// else if $locationSelector == "Don't touch", we just accept the contents of the 'location' field as entered by the user

			$queryRefs .= "location = \"$locationName\", "
					. "call_number = \"$callNumberName\", "
					. "approved = \"$approvedRadio\", "
					. "file = \"$fileName\", "
					. "type = \"$typeName\", "
					. "notes = \"$notesName\", "
					. "url = \"$urlName\", "
					. "doi = \"$doiName\", "
					. "modified_date = \"$currentDate\", "
					. "modified_time = \"$currentTime\", "
					. "modified_by = \"$currentUser\" "
					. "WHERE serial = $serialNo";

			// first, we need to check if there's already an entry for the current record & user within the 'user_data' table:
			// CONSTRUCT SQL QUERY:
			$query = "SELECT data_id FROM user_data WHERE record_id = $serialNo AND user_id = $loginUserID"; // '$loginUserID' is provided as session variable

			if (!($result = @ mysql_query($query, $connection))) // (3) RUN the query on the database through the connection:
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

			if (mysql_num_rows($result) == 1) // if there's already an existing user_data entry, we perform an UPDATE action:
				$queryUserData = "UPDATE user_data SET "
								. "marked = \"$markedRadio\", "
								. "copy = \"$copyName\", "
								. "user_keys = \"$userKeysName\", "
								. "user_notes = \"$userNotesName\", "
								. "user_file = \"$userFileName\" "
								. "WHERE record_id = $serialNo AND user_id = $loginUserID"; // '$loginUserID' is provided as session variable
			else // otherwise we perform an INSERT action:
				$queryUserData = "INSERT INTO user_data SET "
								. "marked = \"$markedRadio\", "
								. "copy = \"$copyName\", "
								. "user_keys = \"$userKeysName\", "
								. "user_notes = \"$userNotesName\", "
								. "user_file = \"$userFileName\", "
								. "record_id = \"$serialNo\", "
								. "user_id = \"$loginUserID\", " // '$loginUserID' is provided as session variable
								. "data_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value
	}

	elseif ($recordAction == "delet") // (Note that if you delete the mother record within the 'refs' table, the corresponding child entry within the 'user_data' table will remain!)
			$queryRefs = "DELETE FROM refs WHERE serial = $serialNo";

	else // if the form does NOT contain a valid serial number, we'll have to add the data:
	{
			// INSERT - construct queries to add data as new record
			$queryRefs = "INSERT INTO refs SET "
					. "author = \"$authorName\", "
					. "first_author = \"$first_author\", "
					. "author_count = \"$author_count\", "
					. "title = \"$titleName\", "
					. "year = \"$yearNo\", "
					. "publication = \"$publicationName\", "
					. "abbrev_journal = \"$abbrevJournalName\", "
					. "volume = \"$volumeNo\", "
					. "issue = \"$issueNo\", "
					. "pages = \"$pagesNo\", "
					. "first_page = \"$first_page\", "
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

			// currently, the value for '$locationSelector' will be always '' when performing an INSERT, since the popup is fixed to 'Add' and disabled (which, in turn, will result in an empty value to be returned)
			if (($locationSelector == "Add") OR ($locationSelector == ""))
				$queryRefs .= "location = \"$currentUser\", "; // ...insert the current user

// A (more relaxed) alternative way of processing the location field would be the following:
//			if ($locationName == "") // if there's no location info provided by the user...
//				$queryRefs .= "location = \"$currentUser\", "; // ...insert the current user
//			else
//				$queryRefs .= "location = \"$locationName\", "; // ...use the information as entered by the user

			if ($callNumberName == "") // if there's no call number info provided by the user...
				$queryRefs .= "call_number = \"$callNumberPrefix\", "; // ...insert the user's call number prefix
			elseif (!ereg("@", $callNumberName)) // if there's a call number provided by the user that does NOT contain any '@' already...
				$queryRefs .= "call_number = \"" . $callNumberPrefix . " @ " . $callNumberName . "\", "; // ...then we assume the user entered a call number for this record which should be prefixed with the user's call number prefix
			else
				$queryRefs .= "call_number = \"$callNumberName\", "; // ...use the information as entered by the user

			$queryRefs .= "approved = \"$approvedRadio\", "
					. "file = \"$fileName\", "
					. "serial = NULL, " // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value
					. "type = \"$typeName\", "
					. "notes = \"$notesName\", "
					. "url = \"$urlName\", "
					. "doi = \"$doiName\", "
					. "created_date = \"$currentDate\", "
					. "created_time = \"$currentTime\", "
					. "created_by = \"$currentUser\", "
					. "modified_date = \"$currentDate\", "
					. "modified_time = \"$currentTime\", "
					. "modified_by = \"$currentUser\"";

			// '$queryUserData' will be set up after '$queryRefs' has been conducted (see below), since the serial number of the newly created 'refs' record is required for the '$queryUserData' query
	}

	// Apply some clean-up to the sql query:
	// if a field of type=NUMBER is empty, we set it back to NULL (otherwise the empty string would be converted to "0" ?:-/)
	if (ereg("^$|^0$",$volumeNo))
		$queryRefs = ereg_replace("\"$volumeNo\"", "NULL", $queryRefs);
	if (ereg("^$|^0$",$seriesVolumeNo))
		$queryRefs = ereg_replace("\"$seriesVolumeNo\"", "NULL", $queryRefs);
	if (ereg("^$|^0$",$editionNo))
		$queryRefs = ereg_replace("\"$editionNo\"", "NULL", $queryRefs);

	// --------------------------------------------------------------------

	// (3) RUN QUERY, (4) DISPLAY HEADER & RESULTS

	// (3) RUN the query on the database through the connection:
	if ($recordAction == "edit")
	{
		if (!($result = @ mysql_query($queryRefs, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("Your query:\n<br>\n<br>\n<code>$queryRefs</code>\n<br>\n<br>\n caused the following error:", $oldQuery);

		if (!($result = @ mysql_query($queryUserData, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("Your query:\n<br>\n<br>\n<code>$queryUserData</code>\n<br>\n<br>\n caused the following error:", $oldQuery);
	}
	elseif ($recordAction == "add")
	{
		if (!($result = @ mysql_query($queryRefs, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("Your query:\n<br>\n<br>\n<code>$queryRefs</code>\n<br>\n<br>\n caused the following error:", $oldQuery);

		// Get the record id that was created
		$serialNo = @ mysql_insert_id($connection); // find out the unique ID number of the newly created record (Note: this function should be called immediately after the
													// SQL INSERT statement! After any subsequent query it won't be possible to retrieve the auto_increment identifier value for THIS record!)

		$queryUserData = "INSERT INTO user_data SET "
				. "marked = \"$markedRadio\", "
				. "copy = \"$copyName\", "
				. "user_keys = \"$userKeysName\", "
				. "user_notes = \"$userNotesName\", "
				. "user_file = \"$userFileName\", "
				. "record_id = \"$serialNo\", "
				. "user_id = \"$loginUserID\", " // '$loginUserID' is provided as session variable
				. "data_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value

		if (!($result = @ mysql_query($queryUserData, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("Your query:\n<br>\n<br>\n<code>$queryUserData</code>\n<br>\n<br>\n caused the following error:", $oldQuery);
	}
	else // '$recordAction' is "delet" (Note that if you delete the mother record within the 'refs' table, the corresponding child entry within the 'user_data' table will remain!)
		if (!($result = @ mysql_query($queryRefs, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("Your query:\n<br>\n<br>\n<code>$queryRefs</code>\n<br>\n<br>\n caused the following error:", $oldQuery);

	// Build correct header message:
	$headerMsg = "The record no. " . $serialNo . " has been successfully " . $recordAction . "ed.";


	// (4) Call 'receipt.php' which displays links to the modifyed/added record as well as to the previous search results page (if any)
	//     (routing feedback output to a different script page will avoid any reload problems effectively!)
	header("Location: receipt.php?recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery));

	// --------------------------------------------------------------------

	// (5) CLOSE CONNECTION

	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to disconnect from the database:", $oldQuery);

	// --------------------------------------------------------------------
?>
