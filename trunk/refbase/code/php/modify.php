<?php
	// This php script will perform adding, editing & deleting of records.
	// It then calls 'receipt.php' which displays links to the modifyed/added record as well as to the previous search results page (if any)

	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY from user input:

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
					. "doi = \"$doiName\""
					. "WHERE serial = $serialNo";

	elseif ($recordAction == "delet")
			$query = "DELETE FROM refs WHERE serial = $serialNo";

	else // if the form does NOT contain a valid serial number, we'll have to add the data:
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
					. "conference = \"$conferenceName\", "
					. "location = \"$locationName\", "
					. "call_number = \"$callNumberName\", "
					. "reprint_status = \"$reprintStatusName\", "
					. "marked = \"$markedRadio\", "
					. "approved = \"$approvedRadio\", "
					. "file = \"$fileName\", "
					. "serial = NULL, " // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value
					. "type = \"$typeName\", "
					. "notes = \"$notesName\", "
					. "user_keys = \"$userKeysName\", "
					. "user_notes = \"$userNotesName\", "
					. "url = \"$urlName\", "
					. "doi = \"$doiName\"";

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (4) DISPLAY HEADER & RESULTS, (5) CLOSE CONNECTION

	// (1) OPEN the database connection:
	//      (variables are set by include file 'db.inc'!)
	if (!($connection = @ mysql_connect($hostName, $username, $password)))
	{
		$error = 1;
		$errorNo = mysql_errno();
		$errorMsg = mysql_error();
		$headerMsg = "The following error occurred while trying to connect to the host:";
		header("Location: receipt.php?error=" . $error . "&errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery));
	}

	// (2) SELECT the database:
	//      (variables are set by include file 'db.inc'!)
	if (!(mysql_select_db($databaseName, $connection)))
	{
		$error = 1;
		$errorNo = mysql_errno();
		$errorMsg = mysql_error();
		$headerMsg = "The following error occurred while trying to connect to the database:";
		header("Location: receipt.php?error=" . $error . "&errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery));
	}

	if ($error != 1) // required, since the previous error statement does not call die() immediately! (as is the case within the other php scripts)
		// (3) RUN the query on the database through the connection:
		if (!($result = @ mysql_query($query, $connection)))
		{
			$error = 1;
			$errorNo = mysql_errno();
			$errorMsg = mysql_error();
			$headerMsg = "Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:";
			header("Location: receipt.php?error=" . $error . "&errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery));
		}

	if ($error != 1) // required, since the previous error statement does not call die() immediately! (as is the case within the other php scripts)
	{
		// Is this an insert?
		if ($recordAction != "edit" && $recordAction != "delet") // alternative method to check for an 'add' action: if (!ereg("^[0-9]+$",$serialNo)) // -> Yes, this an insert -- since '$serialNo' doesn't contain an integer. We'll have to add the data as a new record.
										//     [If there's no serial number yet, the string "(not assigned yet)" gets inserted by 'record.php' (on '$recordAction=add')]
				$serialNo = mysql_insert_id(); // find out the unique ID number of the newly created record (Note: this function should be called immediately after the
											// SQL INSERT statement! After any subsequent query it won't be possible to retrieve the auto_increment identifier value for THIS record!)
	
		// Build correct header message:
		$headerMsg = "The record no. " . $serialNo . " has been successfully " . $recordAction . "ed.";
	
	
		// (4) Call 'receipt.php' which displays links to the modifyed/added record as well as to the previous search results page (if any)
		//     (routing feedback output to a different script page will avoid any reload problems effectively!)
		header("Location: receipt.php?error=" . $error . "&errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery));
	}


	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
	{
		$error = 1;
		$errorNo = mysql_errno();
		$errorMsg = mysql_error();
		$headerMsg = "The following error occurred while trying to disconnect from the database:";
		header("Location: receipt.php?error=" . $error . "&errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&recordAction=" . $recordAction . "&serialNo=" . $serialNo . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery));
	}
?>
