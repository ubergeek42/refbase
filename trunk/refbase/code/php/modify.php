<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./modify.php
	// Created:    18-Dec-02, 23:08
	// Modified:   17-Oct-04, 20:38

	// This php script will perform adding, editing & deleting of records.
	// It then calls 'receipt.php' which displays links to the modified/added record
	// as well as to the previous search results page (if any).

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// Clear any errors that might have been found previously:
	$errors = array();
	
	// Write the (POST) form variables into an array:
	foreach($_POST as $varname => $value)
		$formVars[$varname] = trim($value); // remove any leading or trailing whitespace from the field's contents & copy the trimmed string to the '$formVars' array
//		$formVars[$varname] = trim(clean($value, 50)); // the use of the clean function would be more secure!

	// --------------------------------------------------------------------

	// Extract form variables sent through POST:
	// Note: Although we could use the '$formVars' array directly below (e.g.: $formVars['pageLoginStatus'] etc., like in 'user_validation.php'), we'll read out
	//       all variables individually again. This is done to enhance readability. (A smarter way of doing so seems be the use of the 'extract()' function, but that
	//       may expose yet another security hole...)

	// Extract the page's login status (which indicates the user's login status at the time the page was loaded):
	$pageLoginStatus = $formVars['pageLoginStatus'];

	// First of all, check if this script was called by something else than 'record.php':
	if (!ereg(".+/record.php\?.+", $_SERVER['HTTP_REFERER']))
	{
		// save an appropriate error message:
		$HeaderString = "<b><span class=\"warning\">Invalid call to script 'modify.php'!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		
		if (!empty($_SERVER['HTTP_REFERER'])) // if the referer variable isn't empty
			header("Location: " . $_SERVER['HTTP_REFERER']); // redirect to calling page
		else
			header("Location: index.php"); // redirect to main page ('index.php')

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}
	// If the referring page is 'record.php' (i.e., if this script was called by 'record.php'), check if the user is logged in:
	elseif ((!isset($_SESSION['loginEmail'])) OR ($pageLoginStatus != "logged in")) // if the user isn't logged in -OR- the page's login status still does NOT state "logged in" (since the page wasn't reloaded after the user logged in elsewhere)
	{
		// the user is logged in BUT the page's login status still does NOT state "logged in" (since the page wasn't reloaded after the user logged IN elsewhere):
		if ((isset($_SESSION['loginEmail'])) AND ($pageLoginStatus != "logged in"))
		{
			// save an appropriate error message:
			$HeaderString = "<b><span class=\"warning\">You did login elsewhere leaving this page in an out-dated state!</span></b><br>Record data had to be reloaded omitting your changes. Please re-edit this record:";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

			header("Location: " . $_SERVER['HTTP_REFERER']); // redirect to 'record.php'

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}

		// the user is NOT logged in BUT the page's login status still states that he's "logged in" (since the page wasn't reloaded after the user logged OUT elsewhere):
		if ((!isset($_SESSION['loginEmail'])) AND ($pageLoginStatus == "logged in"))
		{
			// save an appropriate error message:
			$HeaderString = "<b><span class=\"warning\">You're not logged in anymore!</span></b><br>This may be due to a time out, or, because you did logout elsewhere. Please login again:";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		}

		// else if the user isn't logged in yet: ((!isset($_SESSION['loginEmail'])) AND ($pageLoginStatus != "logged in"))
		header("Location: user_login.php?referer=" . rawurlencode($_SERVER['HTTP_REFERER'])); // ask the user to login first, then he'll get directed back to 'record.php'

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// if we made it here, the user is regularly logged in: (isset($_SESSION['loginEmail'] == true) AND ($pageLoginStatus == "logged in")


	// Extract the form used by the user:
	$formType = $formVars['formType'];

	// Extract the type of action requested by the user (either 'add', 'edit' or ''):
	$recordAction = $formVars['recordAction'];

	// $recordAction == '' will be treated equal to 'add':
	if (empty($recordAction))
		$recordAction = "add"; // we set it explicitly here so that we can employ this variable within message strings, etc

	// Determine the button that was hit by the user (either 'Add Record', 'Edit Record', 'Delete Record' or ''):
	// '$submitAction' is only used to determine any 'delet' action! (where '$submitAction' = 'Delete Record')
	// (otherwise, only '$recordAction' controls how to proceed)
	$submitAction = $formVars['submit'];
	if ($submitAction == "Delete Record") // *delete* record
		$recordAction = "delet";


	// now, check if the (logged in) user is allowed to perform the current record action (i.e., add, edit or delete a record):
	$notPermitted = false;

	// if the (logged in) user...
	if ($recordAction == "edit") // ...wants to edit the current record...
	{
		if (isset($_SESSION['user_permissions']) AND !ereg("allow_edit", $_SESSION['user_permissions'])) // ...BUT the 'user_permissions' session variable does NOT contain 'allow_edit'...
		{
			$notPermitted = true;
			// save an appropriate error message:
			$HeaderString = "<b><span class=\"warning\">You have no permission to edit this record!</span></b>";
		}
	}
	elseif ($recordAction == "delet") // ...wants to delete the current record...
	{	
		if (isset($_SESSION['user_permissions']) AND !ereg("allow_delete", $_SESSION['user_permissions'])) // ...BUT the 'user_permissions' session variable does NOT contain 'allow_delete'...
		{
			$notPermitted = true;
			// save an appropriate error message:
			$HeaderString = "<b><span class=\"warning\">You have no permission to delete this record!</span></b>";
		}
	}
	else // if ($recordAction == "add" OR $recordAction == "") // ...wants to add the current record...
	{	
		if (isset($_SESSION['user_permissions']) AND !ereg("allow_add", $_SESSION['user_permissions'])) // ...BUT the 'user_permissions' session variable does NOT contain 'allow_add'...
		{
			$notPermitted = true;
			// save an appropriate error message:
			$HeaderString = "<b><span class=\"warning\">You have no permission to add any new records to the database!</span></b>";
		}
	}

	if ($notPermitted)
	{
		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		header("Location: " . $_SERVER['HTTP_REFERER']); // redirect to 'record.php'

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}


	// if we made it here, we assume that the user is allowed to perform the current record action

	// Extract generic variables from the request:
	$oldQuery = $formVars['oldQuery']; // fetch the query URL of the formerly displayed results page so that its's available on the subsequent receipt page that follows any add/edit/delete action!
	if (ereg('sqlQuery%3D', $oldQuery)) // if '$oldQuery' still contains URL encoded data... ('%3D' is the URL encoded form of '=', see note below!)
		$oldQuery = rawurldecode($oldQuery); // ...URL decode old query URL (it was URL encoded before incorporation into a hidden tag of the 'record' form to avoid any HTML syntax errors)
										// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_POST'!
										//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!
	$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "

	// Extract all form values provided by 'record.php':
	$authorName = $formVars['authorName'];
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
	$locationName = $formVars['locationName'];

	$callNumberName = $formVars['callNumberName'];
	if (ereg("%40", $callNumberName)) // if '$callNumberName' still contains URL encoded data... ('%40' is the URL encoded form of the character '@', see note below!)
		$callNumberName = rawurldecode($callNumberName); // ...URL decode 'callNumberName' variable contents (it was URL encoded before incorporation into a hidden tag of the 'record' form to avoid any HTML syntax errors)
														// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_POST'!
														//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

	$callNumberNameUserOnly = $formVars['callNumberNameUserOnly'];
	$serialNo = $formVars['serialNo'];
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
	$contributionIDCheckBox = $formVars['contributionIDCheckBox'];
	$locationSelectorName = $formVars['locationSelectorName'];
	$onlinePublicationCheckBox = $formVars['onlinePublicationCheckBox'];
	$onlineCitationName = $formVars['onlineCitationName'];
	$createdDate = $formVars['createdDate'];
	$createdTime = $formVars['createdTime'];
	$createdBy = $formVars['createdBy'];
	$modifiedDate = $formVars['modifiedDate'];
	$modifiedTime = $formVars['modifiedTime'];
	$modifiedBy = $formVars['modifiedBy'];
	$origRecord = $formVars['origRecord'];

	// check if a file was uploaded:
	// (note that to have file uploads work, HTTP file uploads must be allowed within your 'php.ini' configuration file
	//  by setting the 'file_uploads' parameter to 'On'!)
	// extract file information into a four (or five) element associative array containing the following information about the file:

	//     name     - original name of file on client
	//     type     - MIME type of file
	//     tmp_name - name of temporary file on server
	//     error    - holds an error number >0 if something went wrong, otherwise 0 (I don't know when this element was added. It may not be present in your PHP version... ?:-/)
	//     size     - size of file in bytes

	// depending what happend on upload, they will contain the following values:
	//              no file upload  'upload exceeds upload_max_filesize'  successful upload
	//              --------------  ------------------------------------  -----------------
	//     name:          ""                       [name]                      [name]
	//     type:          ""                         ""                        [type]
	//     tmp_name:      ""                         ""                      [tmp_name]
	//     error:         4                          1                           0
	//     size:          0                          0                         [size]
	$uploadFile = getUploadInfo("uploadFile"); // function 'getUploadInfo()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// VALIDATE data fields:

	// NOTE: for all fields that are validated here must exist error parsing code (of the form: " . fieldError("languageName", $errors) . ")
	//       in front of the respective <input> form field in 'record.php'! Otherwise the generated error won't be displayed!

	// Validate fields that MUST not be empty:
	// Validate the 'Call Number' field:
	if (ereg("[@;]", $callNumberNameUserOnly))
		$errors["callNumberNameUserOnly"] = "Your call number cannot contain the characters '@' and ';' (since they function as delimiters):"; // the user's personal reference ID cannot contain the characters '@' and ';' since they are used as delimiters (within or between call numbers)
	elseif ($recordAction == "edit" AND !empty($callNumberNameUserOnly) AND !ereg("$loginEmail", $locationName) AND !ereg("^(Add|Remove)$", $locationSelectorName)) // if the user specified some reference ID within an 'edit' action -BUT- there's no existing call number for this user within the contents of the 'location' field -AND- the user doesn't want to add it either...
		$errors["callNumberNameUserOnly"] = "You cannot specify a call number unless you add this record to your personal literature set! This can be done by setting the 'Location Field' popup below to 'Add'."; // warn the user that he/she has to set the Location Field popup to 'Add' if he want's to add this record to his personal literature set

	// Validate the 'uploadFile' field:
	// (whose file name characters must be within [a-zA-Z0-9+_.-] and which must not exceed
	//  the 'upload_max_filesize' specified within your 'php.ini' configuration file)
	if (!empty($uploadFile) && !empty($uploadFile["name"])) // if the user attempted to upload a file
	{
		// NOTE: 'is_uploaded_file()' does NOT seem to work for me! ?:-/ (using PHP 4.3.4 on MacOSX 10.3.2)
		// The 'is_uploaded_file()' function returns 'true' if the file named by '$uploadFile["name"]' was uploaded via HTTP POST. This is useful to help ensure
		// that a malicious user hasn't tried to trick the script into working on files upon which it should not be working - for instance, /etc/passwd.
//		if (is_uploaded_file($_FILES["uploadFile"]))
//		{
			if (empty($uploadFile["tmp_name"])) // no tmp file exists => we assume that the maximum upload file size was exceeded!
			// or check via 'error' element instead: "if ($uploadFile["error"] == 1)" (the 'error' element exists since PHP 4.2.0)
			{
				$maxFileSize = ini_get("upload_max_filesize");
				$fileError = "File size must not be greater than " . $maxFileSize . ":";
		
				$errors["uploadFile"] = $fileError; // inform the user that the maximum upload file size was exceeded
			}
			else // a tmp file exists...
			{
				// since 'is_uploaded_file()' does NOT seem to work for me, here's a clumsy workaround, that at least tries to prevent hackers from gaining access to the systems 'passwd' file:
				if (eregi("^passwd$", $uploadFile["name"])) // ...BUT its file name equals 'passwd'
					$errors["uploadFile"] = "This file name is not allowed!"; // file name must not be 'passwd'

				// check for invalid file name extensions:
				if (eregi("\.(exe|com|bat|zip|php|phps|php3|cgi)$", $uploadFile["name"])) // ...BUT has a invalid file name extension (adjust the regex pattern if you want more relaxed file name validation)
					$errors["uploadFile"] = "You cannot upload this type of file!"; // file name must not end with .exe, .com, .bat, .zip, .php, .phps, .php3 or .cgi

				// check for invalid file name characters:
				if (!ereg("^[a-zA-Z0-9+_.-]+$", $uploadFile["name"])) // ...BUT has invalid characters in its name (adjust the regex pattern if you want more relaxed file name validation)
					$errors["uploadFile"] = "File name characters can only be alphanumeric ('a-zA-Z0-9'), plus ('+'), minus ('-'), substring ('_') or a dot ('.'):"; // characters of file name must be within [a-zA-Z0-9+_.-]
			}
//		}
//		else // a malicious user may have tried to trick the script into working on files upon which it should not be working - for instance, /etc/passwd
//			$errors["uploadFile"] = "Couldn't upload your file:"; // inform the user that there was a problem with the upload
	}


	// CAUTION: validation of other fields is currently disabled, since, IMHO, there are too many open questions how to implement this properly
	//          and without frustrating the user! Uncomment the commented code below to enable the current validation features:
	
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

	// --------------------------------------------------------------------

	// Now the script has finished the validation, check if there were any errors:
	if (count($errors) > 0)
	{
		// Write back session variables:
		saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("formVars", $formVars);

		// There are errors. Relocate back to the record entry form:
		header("Location: " . $_SERVER['HTTP_REFERER']);

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// If we made it here, then the data is considered valid!

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase($oldQuery); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:

	// First, setup some required variables:
	$currentDate = date('Y-m-d'); // get the current date in a format recognized by mySQL (which is 'YYYY-MM-DD', e.g.: '2003-12-31')
	$currentTime = date('H:i:s'); // get the current time in a format recognized by mySQL (which is 'HH:MM:SS', e.g.: '23:59:49')
	$currentUser = $loginFirstName . " " . $loginLastName . " (" . $loginEmail . ")"; // here we use session variables to construct the user name, e.g.: 'Matthias Steffens (msteffens@ipoe.uni-kiel.de)'

	$loginEmailArray = split("@", $loginEmail); // split the login email address at '@'
	$loginEmailUserName = $loginEmailArray[0]; // extract the user name (which is the first element of the array '$loginEmailArray')
	$callNumberPrefix = $abbrevInstitution . " @ " . $loginEmailUserName; // again, we use session variables to construct a correct call number prefix, like: 'IP« @ msteffens'


	// provide some magic that figures out what do to depending on the state of the 'is Editor' check box
	// and the content of the 'author', 'editor' and 'type' fields:
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
	

	// assign correct values to the calculation fields 'first_author', 'author_count', 'first_page', 'volume_numeric' and 'series_volume_numeric':
	if (!empty($authorName))
	{
		$first_author = ereg_replace("^([^;]+).*","\\1",$authorName); // extract first author from 'author' field
		$first_author = trim($first_author); // remove leading & trailing whitespace (if any)
		$first_author = ereg_replace(" *\(eds?\)$","",$first_author); // remove any existing editor info from the 'first_author' string, i.e., kill any trailing " (ed)" or " (eds)"

		if (!ereg(";", $authorName)) // if the 'author' field does NOT contain a ';' (which would delimit multiple authors) => single author
			$author_count = "1"; // indicates a single author
		elseif (ereg("^[^;]+;[^;]+$", $authorName)) // the 'author' field does contain exactly one ';' => two authors
			$author_count = "2"; // indicates two authors
		elseif (ereg("^[^;]+;[^;]+;[^;]+", $authorName)) // the 'author' field does contain at least two ';' => more than two authors
			$author_count = "3"; // indicates three (or more) authors
	}

	if (!empty($pagesNo))
	{
		if (ereg("([0-9]+)",$pagesNo)) // if the 'pages' field contains any numeric value(s)
			$first_page = ereg_replace("^[^0-9]*([0-9]+).*","\\1",$pagesNo); // extract first page from 'pages' field
		else
			$first_page = "0"; // will get transformed into 'NULL' further down...
	}

	if (!empty($volumeNo))
	{
		if (ereg("([0-9]+)",$volumeNo)) // if the 'volume' field contains any numeric value(s)
			$volumeNumericNo = ereg_replace("^[^0-9]*([0-9]+).*","\\1",$volumeNo); // extract first number from 'volume' field
		else
			$volumeNumericNo = "0"; // will get transformed into 'NULL' further down...
	}

	if (!empty($seriesVolumeNo))
	{
		if (ereg("([0-9]+)",$seriesVolumeNo)) // if the 'series_volume' field contains any numeric value(s)
			$seriesVolumeNumericNo = ereg_replace("^[^0-9]*([0-9]+).*","\\1",$seriesVolumeNo); // extract first number from 'series_volume' field
		else
			$seriesVolumeNumericNo = "0"; // will get transformed into 'NULL' further down...
	}


	// manage 'location' field data:
	if ((($locationSelectorName == "Add") OR ($locationSelectorName == "")) AND (!ereg("$loginEmail", $locationName))) // add the current user to the 'location' field (if he/she isn't listed already within the 'location' field):
	// note: if the current user is NOT logged in -OR- if any normal user is logged in, the value for '$locationSelectorName' will be always '' when performing an INSERT,
	//       since the popup is fixed to 'Add' and disabled (which, in turn, will result in an empty value to be returned)
	{
		if (ereg("^(\(your name &(amp;)? email address will be filled in automatically\))?$", $locationName)) // if the 'location' field is either completely empty -OR- does only contain the information string (that shows up on 'add' for normal users)
			$locationName = ereg_replace("^.*$", "$currentUser", $locationName);
		else // if the 'location' field does already contain some user content:
			$locationName = ereg_replace("^(.+)$", "\\1; $currentUser", $locationName);
	}
	elseif ($locationSelectorName == "Remove") // remove the current user from the 'location' field:
	{ // the only pattern that's really unique is the users email address, the user's name may change (since it can be modified by the user). This is why we dont use '$currentUser' here:
		$locationName = ereg_replace("^[^;]*\( *$loginEmail *\) *; *", "", $locationName); // the current user is listed at the very beginning of the 'location' field
		$locationName = ereg_replace(" *;[^;]*\( *$loginEmail *\) *", "", $locationName); // the current user occurs after some other user within the 'location' field
		$locationName = ereg_replace("^[^;]*\( *$loginEmail *\) *$", "", $locationName); // the current user is the only one listed within the 'location' field
	}
	// else if '$locationSelectorName' == "Don't touch" -OR- if the user is already listed within the 'location' field, we just accept the contents of the 'location' field as entered by the user


	// manage 'call_number' field data:
	if ($loginEmail != $adminLoginEmail) // if any normal user is logged in (not the admin):
	{
		if (ereg("$loginEmail", $locationName)) // we only process the user's call number information if the current user is listed within the 'location' field:
		{
			// Note that, for normal users, we process the user's call number information even if the '$locationSelectorName' is NOT set to 'Add'.
			// This is done, since the user should be able to update his/her personal reference ID while the '$locationSelectorName' is set to 'Don't touch'.
			// If the '$locationSelectorName' is set to 'Remove', then any changes made to the personal reference ID will be discarded anyhow.
	
			// build a correct call number string for the current user & record:
			if ($callNumberNameUserOnly == "") // if the user didn't enter any personal reference ID for this record...
				$callNumberNameUserOnly = $callNumberPrefix . " @ "; // ...insert the user's call number prefix only
			else // if the user entered (or modified) his/her personal reference ID for this record...
				$callNumberNameUserOnly = $callNumberPrefix . " @ " . $callNumberNameUserOnly; // ...prefix the entered reference ID with the user's call number prefix
	
			// insert or update the user's call number within the full contents of the 'call_number' field:
			if ($callNumberName == "") // if the 'call_number' field is empty...
				$callNumberName = $callNumberNameUserOnly; // ...insert the user's call number prefix
			elseif (ereg("$callNumberPrefix", $callNumberName)) // if the user's call number prefix occurs within the contents of the 'call_number' field...
				$callNumberName = ereg_replace("$callNumberPrefix *@ *[^@;]*", "$callNumberNameUserOnly", $callNumberName); // ...replace the user's *own* call number within the full contents of the 'call_number' field
			else // if the 'call_number' field does already have some content -BUT- there's no existing call number prefix for the current user...
				$callNumberName = $callNumberName . "; " . $callNumberNameUserOnly; // ...append the user's call number to any existing call numbers
		}
	}
	else // if the admin is logged in:
		if ($locationSelectorName == "Add") // we only add the admin's call number information if he/she did set the '$locationSelectorName' to 'Add'
		{
			if ($callNumberName == "") // if there's no call number info provided by the admin...
				$callNumberName = $callNumberPrefix . " @ "; // ...insert the admin's call number prefix
			elseif (!ereg("@", $callNumberName)) // if there's a call number provided by the admin that does NOT contain any '@' already...
				$callNumberName = $callNumberPrefix . " @ " . $callNumberName; // ...then we assume the admin entered a personal refernce ID for this record which should be prefixed with his/her call number prefix
			// the contents of the 'call_number' field do contain the '@' character, i.e. we assume one or more full call numbers to be present
			elseif (!ereg("$callNumberPrefix", $callNumberName)) // if the admin's call number prefix does NOT already occur within the contents of the 'call_number' field...
			{
				if (ereg("; *[^ @;][^@;]*$", $callNumberName)) // for the admin we offer autocompletion of the call number prefix if he/she just enters his/her reference ID after the last full call number (separated by '; ')
					// e.g., the string 'IP« @ mschmid @ 123; 1778' will be autocompleted to 'IP« @ mschmid @ 123; IP« @ msteffens @ 1778' (with 'msteffens' being the admin user)
					$callNumberName = ereg_replace("^(.+); *([^@;]+)$", "\\1; $callNumberPrefix @ \\2", $callNumberName); // insert the admin's call number prefix before any reference ID that stand's at the end of the string of call numbers
				else
					$callNumberName = $callNumberName . "; " . $callNumberPrefix . " @ "; // ...append the admin's call number prefix to any existing call numbers
			}
		}
		// otherwise we simply use the information as entered by the admin

	if ($locationSelectorName == "Remove") // remove the current user's call number from the 'call_number' field:
	{
		$callNumberName = ereg_replace("^ *$callNumberPrefix *@ *[^@;]*; *", "", $callNumberName); // the user's call number is listed at the very beginning of the 'call_number' field
		$callNumberName = ereg_replace(" *; *$callNumberPrefix *@ *[^@;]*", "", $callNumberName); // the user's call number occurs after some other call number within the 'call_number' field
		$callNumberName = ereg_replace("^ *$callNumberPrefix *@ *[^@;]*$", "", $callNumberName); // the user's call number is the only one listed within the 'call_number' field
	}


	// process information of any file that was uploaded:
	if (!empty($uploadFile) && !empty($uploadFile["tmp_name"])) // if there was a file uploaded successfully
	{
		$tmpFilePath = $uploadFile["tmp_name"];
		$newFileName = $uploadFile["name"];
		
		if (!empty($abbrevJournalName))
		{
			$abbrevJournalDIR = ereg_replace("[^a-zA-Z]", "", $abbrevJournalName); // strip everything but letters from the abbreviated journal name
			$abbrevJournalDIR = strtolower($abbrevJournalDIR) . "/"; // convert string to lowercase & append a slash
		}
		else
			$abbrevJournalDIR = "";
		
		// if there's an existing sub-directory (within the default files directory '$filesBaseDir') whose name equals '$abbrevJournalDIR'
		// we'll copy the new file into that sub-directory (in an attempt to group files together which belong to the same journal),
		// otherwise we just copy the file to the root-level of '$filesBaseDir':
		if (!empty($abbrevJournalDIR) && is_dir($filesBaseDir . $abbrevJournalDIR)) // ('$filesBaseDir' is specified in 'ini.inc.php')
		{
			$destFilePath = $filesBaseDir . $abbrevJournalDIR . $newFileName; // new file will be copied into sub-directory within '$filesBaseDir'...

			// copy the new subdir name & file name to the 'file' field variable:
			// Note: if a user uploads a file and there was already a file specified within the 'file' field, the old file will NOT get removed
			//       from the files directory! Automatic file removal is ommitted on purpose since it's way more difficult to recover an
			//       inadvertently deleted file than to delete it manually.
			$fileName = $abbrevJournalDIR . $newFileName;
		}
		else
		{
			$destFilePath = $filesBaseDir . $newFileName; // new file will be copied to root-level of '$filesBaseDir'...
			$fileName = $newFileName; // copy the new file name to the 'file' field variable (see note above!)
		}

		// copy uploaded file from temporary location to the default file directory specified in '$filesBaseDir':
		copy($tmpFilePath, $destFilePath);
	}

	// check if we need to set the 'contribution_id' field:
	// (we'll make use of the session variable '$abbrevInstitution' here)
	if ($contributionIDCheckBox == "1") // the user want's to add this record to the list of publications that were published by a member of his institution
	{
		if (!empty($contributionID)) // if the 'contribution_id' field is NOT empty...
		{
			if (!ereg("$abbrevInstitution", $contributionID)) // ...and the current user's 'abbrev_institution' value isn't listed already within the 'contribution_id' field
				$contributionID = $contributionID . "; " . $abbrevInstitution; // append the user's 'abbrev_institution' value to the end of the 'contribution_id' field
		}
		else // the 'contribution_id' field is empty
			$contributionID = $abbrevInstitution; // insert the current user's 'abbrev_institution' value
	}
	else // if present, remove the current user's abbreviated institution name from the 'contribution_id' field:
	{
		if (ereg("$abbrevInstitution", $contributionID)) // if the current user's 'abbrev_institution' value is listed within the 'contribution_id' field, we'll remove it:
		{
			$contributionID = ereg_replace("^ *$abbrevInstitution *[^;]*; *", "", $contributionID); // the user's abbreviated institution name is listed at the very beginning of the 'contribution_id' field
			$contributionID = ereg_replace(" *; *$abbrevInstitution *[^;]*", "", $contributionID); // the user's abbreviated institution name occurs after some other institutional abbreviation within the 'contribution_id' field
			$contributionID = ereg_replace("^ *$abbrevInstitution *[^;]*$", "", $contributionID); // the user's abbreviated institution name is the only one listed within the 'contribution_id' field
		}
	}

	// check if we need to set the 'online_publication' field:
	if ($onlinePublicationCheckBox == "1") // the user did mark the "Online publication" checkbox
		$onlinePublication = "yes";
	else
		$onlinePublication = "no";

	// remove any meaningless delimiter(s) from the beginning or end of a field string:
	// Note:  - this cleanup is only done for fields that may contain sub-elements, which are the fields:
	//          'author', 'keywords', 'place', 'language', 'summary_language', 'area', 'user_keys' and 'user_groups'
	//        - currently, only the semicolon (optionally surrounded by whitespace) is supported as sub-element delimiter
	$authorName = trimTextPattern($authorName, "( *; *)+", true, true); // function 'trimTextPattern()' is defined in 'include.inc.php'
	$keywordsName = trimTextPattern($keywordsName, "( *; *)+", true, true);
	$placeName = trimTextPattern($placeName, "( *; *)+", true, true);
	$languageName = trimTextPattern($languageName, "( *; *)+", true, true);
	$summaryLanguageName = trimTextPattern($summaryLanguageName, "( *; *)+", true, true);
	$areaName = trimTextPattern($areaName, "( *; *)+", true, true);
	$userKeysName = trimTextPattern($userKeysName, "( *; *)+", true, true);
	$userGroupsName = trimTextPattern($userGroupsName, "( *; *)+", true, true);


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
					. "volume_numeric = \"$volumeNumericNo\", "
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
					. "orig_title = \"$origTitleName\", "
					. "series_editor = \"$seriesEditorName\", "
					. "series_title = \"$seriesTitleName\", "
					. "abbrev_series_title = \"$abbrevSeriesTitleName\", "
					. "series_volume = \"$seriesVolumeNo\", "
					. "series_volume_numeric = \"$seriesVolumeNumericNo\", "
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
					. "approved = \"$approvedRadio\", "
					. "file = \"$fileName\", "
					. "type = \"$typeName\", "
					. "thesis = \"$thesisName\", "
					. "notes = \"$notesName\", "
					. "url = \"$urlName\", "
					. "doi = \"$doiName\", "
					. "contribution_id = \"$contributionID\", "
					. "online_publication = \"$onlinePublication\", "
					. "online_citation = \"$onlineCitationName\", "
					. "modified_date = \"$currentDate\", "
					. "modified_time = \"$currentTime\", "
					. "modified_by = \"$currentUser\" "
					. "WHERE serial = $serialNo";

			// first, we need to check if there's already an entry for the current record & user within the 'user_data' table:
			// CONSTRUCT SQL QUERY:
			$query = "SELECT data_id FROM user_data WHERE record_id = $serialNo AND user_id = $loginUserID"; // '$loginUserID' is provided as session variable

			// (3) RUN the query on the database through the connection:
			$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

			if (mysql_num_rows($result) == 1) // if there's already an existing user_data entry, we perform an UPDATE action:
				$queryUserData = "UPDATE user_data SET "
								. "marked = \"$markedRadio\", "
								. "copy = \"$copyName\", "
								. "selected = \"$selectedRadio\", "
								. "user_keys = \"$userKeysName\", "
								. "user_notes = \"$userNotesName\", "
								. "user_file = \"$userFileName\", "
								. "user_groups = \"$userGroupsName\", "
								. "bibtex_id = \"$bibtexIDName\", "
								. "related = \"$relatedName\" "
								. "WHERE record_id = $serialNo AND user_id = $loginUserID"; // '$loginUserID' is provided as session variable
			else // otherwise we perform an INSERT action:
				$queryUserData = "INSERT INTO user_data SET "
								. "marked = \"$markedRadio\", "
								. "copy = \"$copyName\", "
								. "selected = \"$selectedRadio\", "
								. "user_keys = \"$userKeysName\", "
								. "user_notes = \"$userNotesName\", "
								. "user_file = \"$userFileName\", "
								. "user_groups = \"$userGroupsName\", "
								. "bibtex_id = \"$bibtexIDName\", "
								. "related = \"$relatedName\", "
								. "record_id = \"$serialNo\", "
								. "user_id = \"$loginUserID\", " // '$loginUserID' is provided as session variable
								. "data_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value
	}

	elseif ($recordAction == "delet") // (Note that if you delete the mother record within the 'refs' table, the corresponding child entry within the 'user_data' table will remain!)
	{
			// Instead of deleting data, deleted records will be moved to the "deleted" table. Data will be stored within the "deleted" table
			// until they are removed manually. This is to provide the admin with a simple recovery method in case a user did delete some data by accident...
			// INSERT - construct queries to add data as new record
			$queryDeleted = "INSERT INTO deleted SET "
					. "author = \"$authorName\", "
					. "first_author = \"$first_author\", "
					. "author_count = \"$author_count\", "
					. "title = \"$titleName\", "
					. "year = \"$yearNo\", "
					. "publication = \"$publicationName\", "
					. "abbrev_journal = \"$abbrevJournalName\", "
					. "volume = \"$volumeNo\", "
					. "volume_numeric = \"$volumeNumericNo\", "
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
					. "orig_title = \"$origTitleName\", "
					. "series_editor = \"$seriesEditorName\", "
					. "series_title = \"$seriesTitleName\", "
					. "abbrev_series_title = \"$abbrevSeriesTitleName\", "
					. "series_volume = \"$seriesVolumeNo\", "
					. "series_volume_numeric = \"$seriesVolumeNumericNo\", "
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
					. "approved = \"$approvedRadio\", "
					. "file = \"$fileName\", "
					. "serial = \"$serialNo\", " // it's important to keep the old PRIMARY KEY (since user specific data may be still associated with this record id)
					. "type = \"$typeName\", "
					. "thesis = \"$thesisName\", "
					. "notes = \"$notesName\", "
					. "url = \"$urlName\", "
					. "doi = \"$doiName\", "
					. "contribution_id = \"$contributionID\", "
					. "online_publication = \"$onlinePublication\", "
					. "online_citation = \"$onlineCitationName\", "
					. "created_date = \"$createdDate\", "
					. "created_time = \"$createdTime\", "
					. "created_by = \"$createdBy\", "
					. "modified_date = \"$modifiedDate\", "
					. "modified_time = \"$modifiedTime\", "
					. "modified_by = \"$modifiedBy\", "
					. "orig_record = \"$origRecord\", "
					. "deleted_date = \"$currentDate\", " // store information about when and by whom this record was deleted...
					. "deleted_time = \"$currentTime\", "
					. "deleted_by = \"$currentUser\"";

			// since data have been moved from table "refs" to table "deleted", its now safe to delete the data from table "refs":
			$queryRefs = "DELETE FROM refs WHERE serial = $serialNo";
	}

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
					. "volume_numeric = \"$volumeNumericNo\", "
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
					. "orig_title = \"$origTitleName\", "
					. "series_editor = \"$seriesEditorName\", "
					. "series_title = \"$seriesTitleName\", "
					. "abbrev_series_title = \"$abbrevSeriesTitleName\", "
					. "series_volume = \"$seriesVolumeNo\", "
					. "series_volume_numeric = \"$seriesVolumeNumericNo\", "
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
					. "approved = \"$approvedRadio\", "
					. "file = \"$fileName\", "
					. "serial = NULL, " // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value
					. "type = \"$typeName\", "
					. "thesis = \"$thesisName\", "
					. "notes = \"$notesName\", "
					. "url = \"$urlName\", "
					. "doi = \"$doiName\", "
					. "contribution_id = \"$contributionID\", "
					. "online_publication = \"$onlinePublication\", "
					. "online_citation = \"$onlineCitationName\", "
					. "created_date = \"$currentDate\", "
					. "created_time = \"$currentTime\", "
					. "created_by = \"$currentUser\", "
					. "modified_date = \"$currentDate\", "
					. "modified_time = \"$currentTime\", "
					. "modified_by = \"$currentUser\"";

			// '$queryUserData' will be set up after '$queryRefs' has been conducted (see below), since the serial number of the newly created 'refs' record is required for the '$queryUserData' query
	}

	// Apply some clean-up to the sql query:
	// if a field of type=NUMBER is empty, we set it back to NULL (otherwise the empty string would be converted to "0")
	if (ereg("^$|^0$",$volumeNumericNo))
	{
		$queryRefs = ereg_replace("\"$volumeNumericNo\"", "NULL", $queryRefs);
		$queryDeleted = ereg_replace("\"$volumeNumericNo\"", "NULL", $queryDeleted);
	}
	if (ereg("^$|^0$",$first_page))
	{
		$queryRefs = ereg_replace("\"$first_page\"", "NULL", $queryRefs);
		$queryDeleted = ereg_replace("\"$first_page\"", "NULL", $queryDeleted);
	}
	if (ereg("^$|^0$",$seriesVolumeNumericNo))
	{
		$queryRefs = ereg_replace("\"$seriesVolumeNumericNo\"", "NULL", $queryRefs);
		$queryDeleted = ereg_replace("\"$seriesVolumeNumericNo\"", "NULL", $queryDeleted);
	}
	if (ereg("^$|^0$",$editionNo))
	{
		$queryRefs = ereg_replace("\"$editionNo\"", "NULL", $queryRefs);
		$queryDeleted = ereg_replace("\"$editionNo\"", "NULL", $queryDeleted);
	}
	if (ereg("^$|^0$",$origRecord))
	{
		$queryRefs = ereg_replace("\"$origRecord\"", "NULL", $queryRefs);
		$queryDeleted = ereg_replace("\"$origRecord\"", "NULL", $queryDeleted);
	}

	// --------------------------------------------------------------------

	// (3) RUN QUERY, (4) DISPLAY HEADER & RESULTS

	// (3) RUN the query on the database through the connection:
	if ($recordAction == "edit")
	{
		$result = queryMySQLDatabase($queryRefs, $oldQuery); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		$result = queryMySQLDatabase($queryUserData, $oldQuery); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		getUserGroups("user_data", $loginUserID); // update the 'userGroups' session variable (function 'getUserGroups()' is defined in 'include.inc.php')
	}
	elseif ($recordAction == "add")
	{
		$result = queryMySQLDatabase($queryRefs, $oldQuery); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		// Get the record id that was created
		$serialNo = @ mysql_insert_id($connection); // find out the unique ID number of the newly created record (Note: this function should be called immediately after the
													// SQL INSERT statement! After any subsequent query it won't be possible to retrieve the auto_increment identifier value for THIS record!)

		$queryUserData = "INSERT INTO user_data SET "
				. "marked = \"$markedRadio\", "
				. "copy = \"$copyName\", "
				. "selected = \"$selectedRadio\", "
				. "user_keys = \"$userKeysName\", "
				. "user_notes = \"$userNotesName\", "
				. "user_file = \"$userFileName\", "
				. "user_groups = \"$userGroupsName\", "
				. "bibtex_id = \"$bibtexIDName\", "
				. "related = \"$relatedName\", "
				. "record_id = \"$serialNo\", "
				. "user_id = \"$loginUserID\", " // '$loginUserID' is provided as session variable
				. "data_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value

		$result = queryMySQLDatabase($queryUserData, $oldQuery); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		getUserGroups("user_data", $loginUserID); // update the 'userGroups' session variable (function 'getUserGroups()' is defined in 'include.inc.php')


		// Send EMAIL announcement:
		if ($sendEmailAnnouncements == "yes") // ('$sendEmailAnnouncements' is specified in 'ini.inc.php')
		{
			// first, build an appropriate author string:
			// Call the 'extractAuthorsLastName()' function (defined in 'include.inc.php') to extract the last name of a particular author (specified by position). Required Parameters:
			//   1. pattern describing delimiter that separates different authors
			//   2. pattern describing delimiter that separates author name & initials (within one author)
			//   3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name)
			//   4. contents of the author field
			$author_string = extractAuthorsLastName(" *; *", // get last name of first author
												" *, *",
												1,
												$authorName);
	
			if ($author_count == "2") // two authors
			{
				$author_string .= " & ";
				$author_string .= extractAuthorsLastName(" *; *", // get last name of second author
													" *, *",
													2,
													$authorName);
			}
	
			if ($author_count == "3") // at least three authors
				$author_string .= " et al";		
		
			// send a notification email to the mailing list email address '$mailingListEmail' (specified in 'ini.inc.php'):
			$emailRecipient = "Literature Database Announcement List <" . $mailingListEmail . ">";
	
			$emailSubject = "New entry: " . $author_string . " " . $yearNo;
			if (!empty($publicationName))
			{
				$emailSubject .= " (" . $publicationName;
				if (!empty($volumeNo))
					$emailSubject .= " " . $volumeNo . ")";
				else
					$emailSubject .= ")";
			}
	
			$emailBody = "The following record has been added to the " . $officialDatabaseName . ":"
						. "\n\n  author:       " . $authorName
						. "\n  title:        " . $titleName
						. "\n  year:         " . $yearNo
						. "\n  publication:  " . $publicationName
						. "\n  volume:       " . $volumeNo
						. "\n  issue:        " . $issueNo
						. "\n  pages:        " . $pagesNo
						. "\n\n  added by:     " . $loginFirstName . " " . $loginLastName
						. "\n  details:      " . $databaseBaseURL . "show.php?record=" . $serialNo // ('$databaseBaseURL' is specified in 'ini.inc.php')
						. "\n";

			sendEmail($emailRecipient, $emailSubject, $emailBody);
		}
	}
	else // '$recordAction' is "delet" (Note that if you delete the mother record within the 'refs' table, the corresponding child entry within the 'user_data' table will remain!)
	{
		$result = queryMySQLDatabase($queryDeleted, $oldQuery); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		$result = queryMySQLDatabase($queryRefs, $oldQuery); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'
	}

	// Build correct header message:
	$headerMsg = "The record no. " . $serialNo . " has been successfully " . $recordAction . "ed.";


	// (4) Call 'receipt.php' which displays links to the modifyed/added record as well as to the previous search results page (if any)
	//     (routing feedback output to a different script page will avoid any reload problems effectively!)
	header("Location: receipt.php?recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery));

	// --------------------------------------------------------------------

	// (5) CLOSE CONNECTION

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase($oldQuery); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>
