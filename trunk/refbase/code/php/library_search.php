<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>IP&Ouml; Literature Database -- Library Search</title>
	<meta name="date" content=<?php echo "\"" . date("d-M-y") . "\""; ?>>
	<meta name="robots" content="index,follow">
	<meta name="description" lang="en" content="Search the IP&Ouml; Library">
	<meta name="keywords" lang="en" content="search citation web database polar marine science literature references mysql php">
	<meta http-equiv="content-language" content="en">
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<link rel="stylesheet" href="style.css" type="text/css" title="CSS Definition">
</head>
<body>
<?php
	// Search formular providing the main fields. Searches will be restricted to records belonging to the IPOE library

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
	showheader($result, "Search the IP&Ouml; library:");

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"librarySearch\">"
			. "\n<input type=\"hidden\" name=\"showQuery\" value=\"0\">";
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"80%\" summary=\"This table holds the search form\">"
			. "\n<tr>"
			. "\n\t<th align=\"left\">Show</th>\n\t<th align=\"left\">Field</th>\n\t<th align=\"left\">&nbsp;</th>\n\t<th align=\"left\">That...</th>\n\t<th align=\"left\">Search String</th>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showAuthor\" value=\"1\" checked></td>"
			. "\n\t<td width=\"40\"><b>Author:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"authorSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"authorName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showTitle\" value=\"1\" checked></td>"
			. "\n\t<td><b>Title:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"titleSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"titleName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showYear\" value=\"1\" checked></td>"
			. "\n\t<td><b>Year:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"yearSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"yearNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showEditor\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Editor:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"editorSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"editorName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showSeriesTitle\" value=\"1\" checked></td>"
			. "\n\t<td><b>Series:</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"seriesTitleRadio\" value=\"1\" checked></td>"
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
	selectDistinct($connection,
				 "refs",
				 "series_title",
				 "seriesTitleName",
				 "All",
				 "All");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">or:</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"seriesTitleRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"seriesTitleSelector2\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"seriesTitleName2\" size=\"42\"></td>"
			. "\n</tr>";

	// (4) Complete the form:
	echo "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showVolume\" value=\"1\"></td>"
			. "\n\t<td><b>Volume:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"volumeSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t\t<option>is greater than</option>\n\t\t\t<option>is less than</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"volumeNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showPages\" value=\"1\" checked></td>"
			. "\n\t<td><b>Pages:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"pagesSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"pagesNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showPublisher\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Publisher:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"publisherSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"publisherName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showPlace\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Place:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"placeSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"placeName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showCallNumber\" value=\"1\" checked></td>"
			. "\n\t<td width=\"40\"><b>Signature:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"callNumberSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"callNumberName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showKeywords\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Keywords:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"keywordsSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"keywordsName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showNotes\" value=\"1\"></td>"
			. "\n\t<td width=\"40\"><b>Notes:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"130\">\n\t\t<select name=\"notesSelector\">\n\t\t\t<option>contains</option>\n\t\t\t<option>does not contain</option>\n\t\t\t<option>is equal to</option>\n\t\t\t<option>is not equal to</option>\n\t\t\t<option>starts with</option>\n\t\t\t<option>ends with</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"notesName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><b>Output Options:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showLinks\" value=\"1\">&nbsp;&nbsp;&nbsp;Display Links</td>"
			. "\n\t<td valign=\"middle\">Show&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"showRows\" value=\"10\" size=\"4\">&nbsp;&nbsp;&nbsp;records per page"
			. "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" value=\"Search\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>1st&nbsp;sort&nbsp;by:</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector1\">\n\t\t\t<option selected>author</option>\n\t\t\t<option>title</option>\n\t\t\t<option>year</option>\n\t\t\t<option>editor</option>\n\t\t\t<option>series_title</option>\n\t\t\t<option>series_volume</option>\n\t\t\t<option>pages</option>\n\t\t\t<option>publisher</option>\n\t\t\t<option>place</option>\n\t\t\t<option>call_number</option>\n\t\t\t<option>keywords</option>\n\t\t\t<option>notes</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio1\" value=\"0\" checked>&nbsp;&nbsp;&nbsp;ascending&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio1\" value=\"1\">&nbsp;&nbsp;&nbsp;descending\n\t</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>2nd&nbsp;sort&nbsp;by:</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector2\">\n\t\t\t<option>author</option>\n\t\t\t<option>title</option>\n\t\t\t<option selected>year</option>\n\t\t\t<option>editor</option>\n\t\t\t<option>series_title</option>\n\t\t\t<option>series_volume</option>\n\t\t\t<option>pages</option>\n\t\t\t<option>publisher</option>\n\t\t\t<option>place</option>\n\t\t\t<option>call_number</option>\n\t\t\t<option>keywords</option>\n\t\t\t<option>notes</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio2\" value=\"0\">&nbsp;&nbsp;&nbsp;ascending&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio2\" value=\"1\" checked>&nbsp;&nbsp;&nbsp;descending\n\t</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>3rd&nbsp;sort&nbsp;by:</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector3\">\n\t\t\t<option>author</option>\n\t\t\t<option selected>title</option>\n\t\t\t<option>year</option>\n\t\t\t<option>editor</option>\n\t\t\t<option>series_title</option>\n\t\t\t<option>series_volume</option>\n\t\t\t<option>pages</option>\n\t\t\t<option>publisher</option>\n\t\t\t<option>place</option>\n\t\t\t<option>call_number</option>\n\t\t\t<option>keywords</option>\n\t\t\t<option>notes</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio3\" value=\"0\" checked>&nbsp;&nbsp;&nbsp;ascending&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio3\" value=\"1\">&nbsp;&nbsp;&nbsp;descending\n\t</td>"
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
							$defaultValue)
	{
	 $defaultWithinResultSet = FALSE;

	 // Query to find distinct values of $columnName
	 // in $tableName
	 // Note: in order to avoid book names we'll restrict the query to records whose location contains 'IP… Library'!
	 $distinctQuery = "SELECT DISTINCT $columnName
						FROM $tableName WHERE location RLIKE \"IPÖ Library\" ORDER BY $columnName";

	 // Run the distinctQuery on the databaseName
	 if (!($resultId = @ mysql_query ($distinctQuery, 
										$connection)))
		showerror();

	 // Retrieve all distinct values
	 $i = 0;
	 while ($row = @ mysql_fetch_array($resultId))
		$resultBuffer[$i++] = $row[$columnName];

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
