<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>IP&Ouml; Literature Database -- Advanced Search</title>
	<meta name="date" content=<?php echo "\"" . date("d-M-y") . "\""; ?>>
	<meta name="robots" content="index,follow">
	<meta name="description" lang="en" content="Search the IP&Ouml; Literature Database">
	<meta name="keywords" lang="en" content="search citation web database polar marine science literature references mysql php">
	<meta http-equiv="content-language" content="en">
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<link rel="stylesheet" href="style.css" type="text/css" title="CSS Definition">
	<script language="JavaScript" type="text/javascript">
		function checkall(val){
			x=0;
			while(document.queryForm.elements[x]){
				if(document.queryForm.elements[x].type=="checkbox"){
					document.queryForm.elements[x].checked=val;
				}
				x++;
			}
		}
	</script> 
</head>
<body>
<?php
	// Search formular providing all of the fields

	// This is included to hide the username and password:
	include 'db.inc';
	include 'error.inc';
	include 'header.inc';
	include 'footer.inc';

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
	// --------------------------------------------------------------------

	// (1) Open the database connection and use the literature database:
	if (!($connection = @ mysql_connect($hostName,$username,$password)))
	{
		showheader($result, "The following error occurred while trying to connect to the host:");
		showerror();
	}

	if (!(mysql_select_db($databaseName, $connection)))
	{
		showheader($result, "The following error occurred while trying to connect to the database:");
		showerror();
	}

	// (2a) Display header:
	showheader($result, "Search all fields of the database:");

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"POST\" name=\"queryForm\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"advancedSearch\">"
			. "\n<input type=\"hidden\" name=\"showQuery\" value=\"0\">";
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"80%\" summary=\"This table holds the search form\">"
			. "\n<tr>"
			. "\n\t<th align=\"left\">Show</th>\n\t<th align=\"left\">Field</th>\n\t<th align=\"left\">&nbsp;</th>\n\t<th align=\"left\">That...</th>\n\t<th align=\"left\">Search String</th>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showAuthor\" value=\"1\" checked></td>"
			. "\n\t<td width=\"40\"><b>Author:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"125\">\n\t\t<select name=\"authorSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"authorName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showAddress\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Address:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"125\">\n\t\t<select name=\"addressSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"addressName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showCorporateAuthor\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Corporate Author:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"125\">\n\t\t<select name=\"corporateAuthorSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"corporateAuthorName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showTitle\" value=\"1\" checked></td>"
			. "\n\t<td><b>Title:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"titleSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"titleName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showOrigTitle\" value=\"1\"></td>"
			. "\n\t<td><b>Original Title:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"OrigTitleSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"OrigTitleName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showYear\" value=\"1\" checked></td>"
			. "\n\t<td><b>Year:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"yearSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"yearNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showPublication\" value=\"1\" checked></td>"
			. "\n\t<td><b>Publication:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"publicationRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"publicationSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "publication",
				 "publicationName",
				 "All",
				 "All",
				 "type",
				 "\"journal\"",
				 false,
				 "");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"publicationRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"publicationSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"publicationName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showAbbrevJournal\" value=\"1\"></td>"
			. "\n\t<td><b>Abbreviated Journal:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"abbrevJournalRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"abbrevJournalSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "abbrev_journal",
				 "abbrevJournalName",
				 "All",
				 "All",
				 "type",
				 "\"journal\"",
				 false,
				 "");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"abbrevJournalRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"abbrevJournalSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"abbrevJournalName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showEditor\" value=\"1\"></td>"
			. "\n\t<td><b>Editor:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"editorSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"editorName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showVolume\" value=\"1\" checked></td>"
			. "\n\t<td><b>Volume:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"volumeSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"volumeNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showIssue\" value=\"1\"></td>"
			. "\n\t<td><b>Issue:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"issueSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"issueNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showPages\" value=\"1\" checked></td>"
			. "\n\t<td><b>Pages:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"pagesSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"pagesNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showSeriesTitle\" value=\"1\"></td>"
			. "\n\t<td><b>Series Title:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"seriesTitleRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"seriesTitleSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "series_title",
				 "seriesTitleName",
				 "All",
				 "All",
				 "",
				 "",
				 false,
				 "");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"seriesTitleRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"seriesTitleSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"seriesTitleName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showAbbrevSeriesTitle\" value=\"1\"></td>"
			. "\n\t<td><b>Abbreviated Series Title:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"abbrevSeriesTitleRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"abbrevSeriesTitleSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "abbrev_series_title",
				 "abbrevSeriesTitleName",
				 "All",
				 "All",
				 "",
				 "",
				 false,
				 "");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"abbrevSeriesTitleRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"abbrevSeriesTitleSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"abbrevSeriesTitleName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showSeriesEditor\" value=\"1\"></td>"
			. "\n\t<td><b>Series Editor:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"seriesEditorSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"seriesEditorName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showSeriesVolume\" value=\"1\"></td>"
			. "\n\t<td><b>Series Volume:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"seriesVolumeSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"seriesVolumeNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showSeriesIssue\" value=\"1\"></td>"
			. "\n\t<td><b>Series Issue:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"seriesIssueSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"seriesIssueNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showPublisher\" value=\"1\"></td>"
			. "\n\t<td><b>Publisher:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"publisherRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"publisherSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "publisher",
				 "publisherName",
				 "All",
				 "All",
				 "",
				 "",
				 false,
				 "");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"publisherRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"publisherSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"publisherName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showPlace\" value=\"1\"></td>"
			. "\n\t<td><b>Place of Publication:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"placeRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"placeSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "place",
				 "placeName",
				 "All",
				 "All",
				 "",
				 "",
				 true,
				 " *[,;()] *");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"placeRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"placeSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"placeName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showEdition\" value=\"1\"></td>"
			. "\n\t<td><b>Edition:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"editionSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"editionNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showMedium\" value=\"1\"></td>"
			. "\n\t<td><b>Medium:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"mediumSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"mediumName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showISSN\" value=\"1\"></td>"
			. "\n\t<td><b>ISSN:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"issnSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"issnName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showISBN\" value=\"1\"></td>"
			. "\n\t<td><b>ISBN:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"isbnSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"isbnName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showLanguage\" value=\"1\"></td>"
			. "\n\t<td><b>Language:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"languageRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"languageSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "language",
				 "languageName",
				 "All",
				 "All",
				 "",
				 "",
				 true,
				 " *[,;()] *");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"languageRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"languageSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"languageName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showSummaryLanguage\" value=\"1\"></td>"
			. "\n\t<td><b>Summary Language:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"summaryLanguageRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"summaryLanguageSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "summary_language",
				 "summaryLanguageName",
				 "All",
				 "All",
				 "",
				 "",
				 true,
				 " *[,;()] *");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"summaryLanguageRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"summaryLanguageSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"summaryLanguageName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showKeywords\" value=\"1\"></td>"
			. "\n\t<td><b>Keywords:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"keywordsSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"keywordsName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showAbstract\" value=\"1\"></td>"
			. "\n\t<td><b>Abstract:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"abstractSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"abstractName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showArea\" value=\"1\"></td>"
			. "\n\t<td><b>Area:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"areaRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"areaSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "area",
				 "areaName",
				 "All",
				 "All",
				 "",
				 "",
				 true,
				 " *[,;()] *");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"areaRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"areaSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"areaName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showExpedition\" value=\"1\"></td>"
			. "\n\t<td><b>Expedition:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"expeditionSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"expeditionName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showConference\" value=\"1\"></td>"
			. "\n\t<td><b>Conference:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"conferenceSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"conferenceName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showDOI\" value=\"1\"></td>"
			. "\n\t<td><b>DOI:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"doiSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"doiName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showURL\" value=\"1\"></td>"
			. "\n\t<td><b>URL:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"urlSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"urlName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showLocation\" value=\"1\"></td>"
			. "\n\t<td><b>Location:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"locationRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"locationSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "location",
				 "locationName",
				 "All",
				 "All",
				 "",
				 "",
				 true,
				 " *[,;()] *");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"locationRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"locationSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"locationName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showCallNumber\" value=\"1\"></td>"
			. "\n\t<td><b>Call Number:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"callNumberSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"callNumberName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showFile\" value=\"1\"></td>"
			. "\n\t<td><b>File Name:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"fileSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"fileName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showReprintStatus\" value=\"1\"></td>"
			. "\n\t<td><b>Reprint Status:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"reprintStatusSelector\">\n\t\t\t<option selected>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<select name=\"reprintStatusName\">\n\t\t\t<option selected>All</option>\n\t\t\t<option>true</option>\n\t\t\t<option>false</option>\n\t\t\t<option>requested</option>\n\t\t\t<option>fetch</option>\n\t\t</select>\n\t</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showNotes\" value=\"1\"></td>"
			. "\n\t<td><b>Notes:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"notesSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"notesName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showUserKeys\" value=\"1\"></td>"
			. "\n\t<td><b>User Keys:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"userKeysRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"userKeysSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "user_keys",
				 "userKeysName",
				 "",
				 "",
				 "type",
				 "\"journal\"",
				 true,
				 " *[,;()] *");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"userKeysRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"userKeysSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"userKeysName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showUserNotes\" value=\"1\"></td>"
			. "\n\t<td><b>User Notes:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"userNotesSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"userNotesName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showSerial\" value=\"1\"></td>"
			. "\n\t<td><b>Serial:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"serialSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t\t<option>is within list</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"serialNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showType\" value=\"1\"></td>"
			. "\n\t<td><b>Record Type:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"typeRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"typeSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "type",
				 "typeName",
				 "All",
				 "All",
				 "",
				 "",
				 false,
				 "");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"typeRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"typeSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"typeName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showMarked\" value=\"1\"></td>"
			. "\n\t<td><b>Marked:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td><input type=\"radio\" name=\"markedRadio\" value=\"1\">&nbsp;&nbsp;yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"markedRadio\" value=\"0\">&nbsp;&nbsp;no</td>"
			. "\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showApproved\" value=\"1\"></td>"
			. "\n\t<td><b>Approved:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td><input type=\"radio\" name=\"approvedRadio\" value=\"1\">&nbsp;&nbsp;yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"radio\" name=\"approvedRadio\" value=\"0\">&nbsp;&nbsp;no</td>"
			. "\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showCreatedDate\" value=\"1\"></td>"
			. "\n\t<td><b>Date Created:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"createdDateSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"createdDateNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showCreatedTime\" value=\"1\"></td>"
			. "\n\t<td><b>Time Created:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"createdTimeSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"createdTimeNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showCreatedBy\" value=\"1\"></td>"
			. "\n\t<td><b>Created By:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"createdByRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"createdBySelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "created_by",
				 "createdByName",
				 "All",
				 "All",
				 "",
				 "",
				 false,
				 "");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"createdByRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"createdBySelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"createdByName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showModifiedDate\" value=\"1\"></td>"
			. "\n\t<td><b>Date Modified:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"modifiedDateSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"modifiedDateNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showModifiedTime\" value=\"1\"></td>"
			. "\n\t<td><b>Time Modified:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"modifiedTimeSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"modifiedTimeNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showModifiedBy\" value=\"1\"></td>"
			. "\n\t<td><b>Modified By:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"modifiedByRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"modifiedBySelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	// 7. Restrict query to field... (keep empty if no restriction wanted)
	// 8. ...where field contents are...
	// 9. Split field contents into substrings? (yes = true, no = false)
	// 10. POSIX-PATTERN to split field contents into substrings (in order to obtain actual values)
	selectDistinct($connection,
				 "refs",
				 "modified_by",
				 "modifiedByName",
				 "All",
				 "All",
				 "",
				 "",
				 false,
				 "");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"modifiedByRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"modifiedBySelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"modifiedByName2\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><b>Output Options:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showLinks\" value=\"1\" checked>&nbsp;&nbsp;&nbsp;Display Links</td>"
			. "\n\t<td valign=\"middle\">Show&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"showRows\" value=\"10\" size=\"4\">&nbsp;&nbsp;&nbsp;records per page"
			. "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" value=\"Search\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>1st&nbsp;sort&nbsp;by:</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector1\">\n\t\t\t<option selected>author\n\t\t\t<option>address</option>\n\t\t\t<option>corporate_author</option>\n\t\t\t<option></option>\n\t\t\t<option>title</option>\n\t\t\t<option>orig_title</option>\n\t\t\t<option></option>\n\t\t\t<option>year</option>\n\t\t\t<option>publication</option>\n\t\t\t<option>abbrev_journal</option>\n\t\t\t<option>editor</option>\n\t\t\t<option></option>\n\t\t\t<option>volume</option>\n\t\t\t<option>issue</option>\n\t\t\t<option>pages</option>\n\t\t\t<option></option>\n\t\t\t<option>series_title</option>\n\t\t\t<option>abbrev_series_title</option>\n\t\t\t<option>series_editor</option>\n\t\t\t<option>series_volume</option>\n\t\t\t<option>series_issue</option>\n\t\t\t<option></option>\n\t\t\t<option>publisher</option>\n\t\t\t<option>place</option>\n\t\t\t<option></option>\n\t\t\t<option>edition</option>\n\t\t\t<option>medium</option>\n\t\t\t<option>issn</option>\n\t\t\t<option>isbn</option>\n\t\t\t<option></option>\n\t\t\t<option>language</option>\n\t\t\t<option>summary_language</option>\n\t\t\t<option></option>\n\t\t\t<option>keywords</option>\n\t\t\t<option>abstract</option>\n\t\t\t<option></option>\n\t\t\t<option>area</option>\n\t\t\t<option>expedition</option>\n\t\t\t<option>conference</option>\n\t\t\t<option></option>\n\t\t\t<option>doi</option>\n\t\t\t<option>url</option>\n\t\t\t<option></option>\n\t\t\t<option>location</option>\n\t\t\t<option>call_number</option>\n\t\t\t<option>file</option>\n\t\t\t<option>reprint_status</option>\n\t\t\t<option></option>\n\t\t\t<option>notes</option>\n\t\t\t<option>user_keys</option>\n\t\t\t<option>user_notes</option>\n\t\t\t<option></option>\n\t\t\t<option>serial</option>\n\t\t\t<option>type</option>\n\t\t\t<option>marked</option>\n\t\t\t<option>approved</option>\n\t\t\t<option></option>\n\t\t\t<option>created_date</option>\n\t\t\t<option>created_time</option>\n\t\t\t<option>created_by</option>\n\t\t\t<option></option>\n\t\t\t<option>modified_date</option>\n\t\t\t<option>modified_time</option>\n\t\t\t<option>modified_by</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio1\" value=\"0\" checked>&nbsp;&nbsp;&nbsp;ascending&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio1\" value=\"1\">&nbsp;&nbsp;&nbsp;descending\n\t</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>2nd&nbsp;sort&nbsp;by:</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector2\">\n\t\t\t<option>author\n\t\t\t<option>address</option>\n\t\t\t<option>corporate_author</option>\n\t\t\t<option></option>\n\t\t\t<option>title</option>\n\t\t\t<option>orig_title</option>\n\t\t\t<option></option>\n\t\t\t<option selected>year</option>\n\t\t\t<option>publication</option>\n\t\t\t<option>abbrev_journal</option>\n\t\t\t<option>editor</option>\n\t\t\t<option></option>\n\t\t\t<option>volume</option>\n\t\t\t<option>issue</option>\n\t\t\t<option>pages</option>\n\t\t\t<option></option>\n\t\t\t<option>series_title</option>\n\t\t\t<option>abbrev_series_title</option>\n\t\t\t<option>series_editor</option>\n\t\t\t<option>series_volume</option>\n\t\t\t<option>series_issue</option>\n\t\t\t<option></option>\n\t\t\t<option>publisher</option>\n\t\t\t<option>place</option>\n\t\t\t<option></option>\n\t\t\t<option>edition</option>\n\t\t\t<option>medium</option>\n\t\t\t<option>issn</option>\n\t\t\t<option>isbn</option>\n\t\t\t<option></option>\n\t\t\t<option>language</option>\n\t\t\t<option>summary_language</option>\n\t\t\t<option></option>\n\t\t\t<option>keywords</option>\n\t\t\t<option>abstract</option>\n\t\t\t<option></option>\n\t\t\t<option>area</option>\n\t\t\t<option>expedition</option>\n\t\t\t<option>conference</option>\n\t\t\t<option></option>\n\t\t\t<option>doi</option>\n\t\t\t<option>url</option>\n\t\t\t<option></option>\n\t\t\t<option>location</option>\n\t\t\t<option>call_number</option>\n\t\t\t<option>file</option>\n\t\t\t<option>reprint_status</option>\n\t\t\t<option></option>\n\t\t\t<option>notes</option>\n\t\t\t<option>user_keys</option>\n\t\t\t<option>user_notes</option>\n\t\t\t<option></option>\n\t\t\t<option>serial</option>\n\t\t\t<option>type</option>\n\t\t\t<option>marked</option>\n\t\t\t<option>approved</option>\n\t\t\t<option></option>\n\t\t\t<option>created_date</option>\n\t\t\t<option>created_time</option>\n\t\t\t<option>created_by</option>\n\t\t\t<option></option>\n\t\t\t<option>modified_date</option>\n\t\t\t<option>modified_time</option>\n\t\t\t<option>modified_by</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio2\" value=\"0\">&nbsp;&nbsp;&nbsp;ascending&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio2\" value=\"1\" checked>&nbsp;&nbsp;&nbsp;descending\n\t</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>3rd&nbsp;sort&nbsp;by:</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector3\">\n\t\t\t<option>author\n\t\t\t<option>address</option>\n\t\t\t<option>corporate_author</option>\n\t\t\t<option></option>\n\t\t\t<option>title</option>\n\t\t\t<option>orig_title</option>\n\t\t\t<option></option>\n\t\t\t<option>year</option>\n\t\t\t<option selected>publication</option>\n\t\t\t<option>abbrev_journal</option>\n\t\t\t<option>editor</option>\n\t\t\t<option></option>\n\t\t\t<option>volume</option>\n\t\t\t<option>issue</option>\n\t\t\t<option>pages</option>\n\t\t\t<option></option>\n\t\t\t<option>series_title</option>\n\t\t\t<option>abbrev_series_title</option>\n\t\t\t<option>series_editor</option>\n\t\t\t<option>series_volume</option>\n\t\t\t<option>series_issue</option>\n\t\t\t<option></option>\n\t\t\t<option>publisher</option>\n\t\t\t<option>place</option>\n\t\t\t<option></option>\n\t\t\t<option>edition</option>\n\t\t\t<option>medium</option>\n\t\t\t<option>issn</option>\n\t\t\t<option>isbn</option>\n\t\t\t<option></option>\n\t\t\t<option>language</option>\n\t\t\t<option>summary_language</option>\n\t\t\t<option></option>\n\t\t\t<option>keywords</option>\n\t\t\t<option>abstract</option>\n\t\t\t<option></option>\n\t\t\t<option>area</option>\n\t\t\t<option>expedition</option>\n\t\t\t<option>conference</option>\n\t\t\t<option></option>\n\t\t\t<option>doi</option>\n\t\t\t<option>url</option>\n\t\t\t<option></option>\n\t\t\t<option>location</option>\n\t\t\t<option>call_number</option>\n\t\t\t<option>file</option>\n\t\t\t<option>reprint_status</option>\n\t\t\t<option></option>\n\t\t\t<option>notes</option>\n\t\t\t<option>user_keys</option>\n\t\t\t<option>user_notes</option>\n\t\t\t<option></option>\n\t\t\t<option>serial</option>\n\t\t\t<option>type</option>\n\t\t\t<option>marked</option>\n\t\t\t<option>approved</option>\n\t\t\t<option></option>\n\t\t\t<option>created_date</option>\n\t\t\t<option>created_time</option>\n\t\t\t<option>created_by</option>\n\t\t\t<option></option>\n\t\t\t<option>modified_date</option>\n\t\t\t<option>modified_time</option>\n\t\t\t<option>modified_by</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio3\" value=\"0\" checked>&nbsp;&nbsp;&nbsp;ascending&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio3\" value=\"1\">&nbsp;&nbsp;&nbsp;descending\n\t</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td align=\"center\" valign=\"top\" colspan=\"5\"><a href=\"JavaScript:checkall(true)\">Select All</a>&nbsp;&nbsp;&nbsp;<a href=\"JavaScript:checkall(false)\">Deselect All</a></td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";

	// (5) Close the database connection:
	if (!(mysql_close($connection)))
	{
		showheader($result, "The following error occurred while trying to disconnect from the database:");
		showerror();
	}

	// --------------------------------------------------------------------

	// THE SELECTDISTINCT FUNCTION:
	function selectDistinct ($connection,
							$tableName,
							$columnName,
							$pulldownName,
							$additionalOption,
							$defaultValue,
							$RestrictToField,
							$RestrictToFieldContents,
							$SplitValues,
							$SplitPattern)
	{
	$defaultWithinResultSet = FALSE;

	// Query to find distinct values of $columnName
	// in $tableName
	if ($RestrictToField == "")
		 $distinctQuery = "SELECT DISTINCT $columnName
						FROM $tableName ORDER BY $columnName";
	else
		 $distinctQuery = "SELECT DISTINCT $columnName
						FROM $tableName WHERE $RestrictToField RLIKE $RestrictToFieldContents ORDER BY $columnName";

	// Run the distinctQuery on the databaseName
	if (!($resultId = @ mysql_query ($distinctQuery, 
										$connection)))
		showerror();

	// Retrieve all distinct values
	$i = 0;
	while ($row = @ mysql_fetch_array($resultId))
		if ($SplitValues == true) // If desired, split field contents into substrings
			{
				// split field data on the pattern specified in $SplitPattern:
				$splittedFieldData = split($SplitPattern, $row[$columnName]); // yields an array as a result
				// ... copy all array elements to end of $resultBuffer:
				foreach($splittedFieldData as $element)
					$resultBuffer[$i++] = $element;
			}
		else // copy field data (as is) to end of $resultBuffer:
			$resultBuffer[$i++] = $row[$columnName];

	if ($SplitValues == true) // (otherwise, data are already DISTINCT and ORDERed BY!)
		{
			// remove duplicate values from array:
			$resultBuffer = array_unique($resultBuffer);
			// sort in ascending order:
			sort($resultBuffer);
		}

	// Start the select widget
	echo "\n\t\t<select name=\"$pulldownName\">";		 

	// Is there an additional option?
	if (isset($additionalOption))
		// Yes, but is it the default option?
		if ($defaultValue == $additionalOption)
			// Show the additional option as selected
			echo "\n\t\t\t<option selected>$additionalOption</option>";
		else
			// Just show the additional option
			echo "\n\t\t\t<option>$additionalOption</option>";

	// check for a default value
	if (isset($defaultValue))
	{
		// Yes, there's a default value specified

		// Check if the defaultValue is in the 
		// database values
		foreach ($resultBuffer as $result)
			if ($result == $defaultValue)
				// Yes, show as selected
				echo "\n\t\t\t<option selected>$result</option>";
			else
				// No, just show as an option
				echo "\n\t\t\t<option>$result</option>";
	}	// end if defaultValue
	else 
	{ 
		// No defaultValue
		
		// Show database values as options
		foreach ($resultBuffer as $result)
			echo "\n\t\t\t<option>$result</option>";
	}
	echo "\n\t\t</select>";
	} // end of function

	// --------------------------------------------------------------------

	// BUILD THE HTML HEADER:
	function showheader($result, $HeaderString)
	{
		// call the 'displayheader()' function from 'header.inc'):
		displayheader();

		// finalize header containing the appropriate header string:
		echo "\n<tr>"
//			. "\n\t<td>&nbsp;</td>" // img in 'header.inc' now spans this row (by rowspan="2")
			. "\n\t<td>$HeaderString</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n<hr align=\"center\" width=\"80%\">";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter();

	// --------------------------------------------------------------------

?>
</body>
</html> 
