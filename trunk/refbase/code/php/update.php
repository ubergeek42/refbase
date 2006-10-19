<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./update.php
	// Created:    01-Mar-05, 20:47
	// Modified:   19-Oct-06, 13:30

	// This file will update any refbase MySQL database installation from v0.8.0 (and, to a certain extent, intermediate cvs versions) to v0.9.0.
	// (Note that this script currently doesn't offer any conversion from 'latin1' to 'utf8')
	// CAUTION: YOU MUST REMOVE THIS SCRIPT FROM YOUR WEB DIRECTORY AFTER THE UPDATE!!

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// Initialize preferred display language:
	// (note that 'locales.inc.php' has to be included *after* the call to the 'start_session()' function)
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// This specifies the name of the database that handles the MySQL user access privileges.
	// Unless you've fiddled with it, you shouldn't change the default value ('mysql'):
	$adminDatabaseName = 'mysql';

	// Extract any parameters passed to the script:
	if (isset($_POST['adminUserName']))
		$adminUserName = $_POST['adminUserName'];
	else
		$adminUserName = "";

	if (isset($_POST['adminPassword']))
		$adminPassword = $_POST['adminPassword'];
	else
		$adminPassword = "";

	// --------------------------------------------------------------------

	// Check the correct parameters have been passed:
	if (empty($adminUserName) AND empty($adminPassword))
	{
		// if 'update.php' was called without any valid parameters:
		//Display an update form:

		if (isset($_SESSION['errors']))
		{
			$errors = $_SESSION['errors'];

			// Note: though we clear the session variable, the current error message is still available to this script via '$errors':
			deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}
		else
			$errors = array(); // initialize the '$errors' variable in order to prevent 'Undefined variable...' messages

		if (isset($_SESSION['formVars']))
		{
			$formVars = $_SESSION['formVars'];

			// Note: though we clear the session variable, the current form variables are still available to this script via '$formVars':
			deleteSessionVariable("formVars"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}
		else
		{
			// Reset the '$formVars' variable (since we're providing the default values):
			$formVars = array();

			// provide the default values:
			$formVars["adminUserName"] = "root";
			$formVars["adminPassword"] = "";
		}

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString']))
		{
			if (empty($errors)) // provide the default message:
				$HeaderString = "To update refbase v0.8.0 please fill out the form below and click the <em>Update</em> button:";
			else // -> there were errors when validating the fields
				$HeaderString = "<b><span class=\"warning\">There were validation errors regarding the details you entered. Please check the comments above the respective fields:</span></b>";
		}
		else
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

		// Show the login status:
		showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

		// DISPLAY header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Update", "index,follow", "Update form for the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString, "");

		// Start <form> and <table> holding the form elements:
?>

<form action="update.php" method="POST">
<input type="hidden" name="formType" value="update">
<input type="hidden" name="submit" value="Update"><?php // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Update" button) ?>

<table align="center" border="0" cellpadding="0" cellspacing="12" width="95%" summary="This table holds the update form">
	<tr>
		<td colspan="3"><h3>refbase Update</h3></td>
	</tr>
	<tr>
		<td width="190" valign="top"><b>Important Note:</b></td>
		<td valign="top" colspan="2">
			Before executing this script, you <span class="warning">must edit</span> the new include file <span class="warning"><em>db.inc.php</em></span> (sub-dir <em>initialize/</em>) in a text editor and re-enter the values from your old <em>db.inc</em> file for the variables <em>$databaseName</em>, <em>$username</em> and <em>$password</em>! Then, proceed with this form:
		</td>
	</tr>
	<tr>
		<td valign="top"><b>MySQL Admin User:</b></td>
		<td valign="top"><?php echo fieldError("adminUserName", $errors); ?>

			<input type="text" name="adminUserName" value="<?php echo $formVars["adminUserName"]; ?>" size="30">
		</td>
		<td valign="top">Give the name of an administrative user that has full access to the MySQL admin database. Often, this is the 'root' user.</td>
	</tr>
	<tr>
		<td valign="top"><b>MySQL Admin Password:</b></td>
		<td valign="top"><?php
	// the form won't remember the password, so we'll ask the user to re-type it...
	if (!empty($errors) AND !isset($errors["adminPassword"])) // ...if there were some validation errors but not with the password field
		echo "\n\t\t\t<b>Please type your password again:</b>\n\t\t\t<br>";
	else
		echo fieldError("adminPassword", $errors);
?>

			<input type="password" name="adminPassword" size="30">
		</td>
		<td valign="top">Please enter the password for the administrative user you've specified above.</td>
	</tr>
	<tr>
		<td valign="top">&nbsp;</td>
		<td valign="top" align="right">
			<input type="submit" name="submit" value="Update">
		</td>
		<td valign="top">&nbsp;</td>
	</tr>
</table>
</form><?php

		// --------------------------------------------------------------------

		// DISPLAY THE HTML FOOTER:
		// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
		showPageFooter($HeaderString, "");

		displayHTMLfoot();

		// --------------------------------------------------------------------

	}
	else // some parameters have been passed, so let's validate the fields:
	{

		// --------------------------------------------------------------------

		// Clear any errors that might have been found previously:
			$errors = array();

		// Write the (POST) form variables into an array:
		foreach($_POST as $varname => $value)
			$formVars[$varname] = $value;


		// Validate the 'adminUserName' field:
		if (empty($formVars["adminUserName"]))
			// The 'adminUserName' field cannot be a null string
			$errors["adminUserName"] = "This field cannot be blank:";


		// Validate the 'adminPassword' field:
		if (empty($formVars["adminPassword"]))
			// The 'adminPassword' field cannot be a null string
			$errors["adminPassword"] = "This field cannot be blank:";

		// --------------------------------------------------------------------

		// Now the script has finished the validation, check if there were any errors:
		if (count($errors) > 0)
		{
			// Write back session variables:
			saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
			saveSessionVariable("formVars", $formVars);

			// There are errors. Relocate back to the update form:
			header("Location: update.php");

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}

		// --------------------------------------------------------------------

		// If we made it here, then the data is considered valid!

		// (1) Open the database connection and use the mysql database:
		if (!($connection = @ mysql_connect($hostName,$adminUserName,$adminPassword)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to connect to the host:", "");

		if (!(mysql_select_db($databaseName, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to connect to the database:", "");

		// --------------------------------------------------------------------

    // (2) SQL queries
		// (2.1) Create new MySQL table user_options
    if (!($result = @ mysql_query ("CREATE TABLE IF NOT EXISTS ". $tableUserOptions .
      " (option_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, ".
      "user_id MEDIUMINT UNSIGNED NOT NULL, ".
      "export_cite_keys ENUM('yes','no') NOT NULL, ".
      "autogenerate_cite_keys ENUM('yes','no') NOT NULL, ".
      "prefer_autogenerated_cite_keys ENUM('no','yes') NOT NULL, ".
      "use_custom_cite_key_format ENUM('no','yes') NOT NULL, ".
      "cite_key_format VARCHAR(255), ".
      "uniquify_duplicate_cite_keys ENUM('yes','no') NOT NULL, ".
      "nonascii_chars_in_cite_keys ENUM('transliterate','strip','keep'), ".
      "use_custom_text_citation_format ENUM('no','yes') NOT NULL, ".
      "text_citation_format VARCHAR(255), ".
      "INDEX (user_id));",
      $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
        showErrorMsg("The following error occurred while trying to crate the user_options table:", "");

    // (2.2) Insert default user options for anyone who's not logged in
    $values = "(NULL, 0, 'yes', 'yes', 'no', 'no', '<:authors:><:year:>', 'yes', 'transliterate', 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>')";
    insertIfNotExists("user_id", 0, $tableUserOptions, $values);

    // (2.3) Insert default user options for all users
    $values = "NULL, 1, 'yes', 'yes', 'no', 'yes', '<:authors[2|+|++]:><:year:>', 'yes', 'transliterate', 'no', '<:authors[2| & | et al.]:>< :year:>< {:recordIdentifier:}>')";
    // Check how many users are contained in table 'users':
  	$queryUserIDs = "SELECT user_id FROM " . $databaseName . ".users";

 		// Run the query on the mysql database through the connection:
  	if (!($result = @ mysql_query ($queryUserIDs, $connection)))
  		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
  			showErrorMsg("The following error occurred while trying to query the database:", "");

   		$rowsFound = @ mysql_num_rows($result);
   		if ($rowsFound > 0) { // If there were rows (= user IDs) found ...
        while ($row = @ mysql_fetch_array($result)) {
          insertIfNotExists("user_id", $row['user_id'], $tableUserOptions, $values);
        }
      }
    
    // (3) Errors
		// If any of the new tables/fields exist already, we stop script execution and issue an error message:
		if (!empty($resultArray1))
		{
			$HeaderString = "Nothing was updated! The following errors occurred while checking your database:";

			if (count($resultArray1) > 1)
				$resultArray1String = "\n<br>";
			else
				$resultArray1String = "";

			$resultArray1String .= implode("\n<br>", $resultArray1); // merge array elements into a string

			// Note that we don't use the 'showErrorMsg()' function here since we want to provide a custom 'errorMsg' parameter:
			header("Location: error.php?errorNo=&errorMsg=" . rawurlencode($resultArray1String) . "&headerMsg=" . rawurlencode($HeaderString) . "&oldQuery=");

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}

		if (!empty($resultArray2))
		{
			$HeaderString = "Update process interrupted! The following errors occurred while updating your database:";
			$resultArray2String = implode("\n<br>", $resultArray2); // merge array elements into a string

			// Note that we don't use the 'showErrorMsg()' function here since we want to provide a custom 'errorMsg' parameter:
			header("Location: error.php?errorNo=&errorMsg=" . rawurlencode($resultArray2String) . "&headerMsg=" . rawurlencode($HeaderString) . "&oldQuery=");

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}

		// (4) Close the database connection:
		if (!(mysql_close($connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to disconnect from the database:", "");

		// --------------------------------------------------------------------

		// Provide a feedback page:

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString'])) // provide one of the default messages:
		{
			if (!empty($resultArray2)) // if there were any execution errors
				$HeaderString = "The following errors occurred while trying to import the SQL data into the database:";
			else // assume that the update was successful
				$HeaderString = "Update of the Web Reference Database was successful!";
		}
		else
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

		// Show the login status:
		showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

		// DISPLAY header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Update Feedback", "index,follow", "Update feedback for the " . encodeHTML($officialDatabaseName), "", false, "", $viewType, array());
		showPageHeader($HeaderString, "");

		// Start a <table>:
?>

<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds the update feedback info"><?php

		if (!empty($resultArray2)) // if there were any execution errors:
		// Note that since we now stop script execution directly after the 'exec()' command, the following {...} block is kinda unnecessary now...
		{
?>

	<tr>
		<td valign="top"><b>Errors:</b></td>
		<td><?php echo encodeHTML($resultArray2String); ?></td>
	</tr>
	<tr>
		<td valign="top">&nbsp;</td>
		<td>
			<a href="update.php">Go Back</a>
		</td>
	</tr><?php

		}
		else // no execution errors -> inform the user about successful database update:
		{
?>

	<tr>
		<td colspan="2"><h3>Welcome to refbase v0.9.0!</h3></td>
	</tr>
	<tr>
		<td valign="top"><b>Important Note:</b></td>
		<td>
			The files <em>update.php</em> and <em>update.sql</em> (as well as <em>install.php</em> and <em>install.sql</em>) are only provided for update/installation purposes and are not needed anymore. Due to security considerations you should <span class="warning">remove these files</span> from your web directory NOW!!
		</td>
	</tr>
	<tr>
		<td valign="top"><b>Configure refbase:</b></td>
		<td>
			In order to re-establish your existing settings, please open <em>ini.inc.php</em> (sub-dir <em>initialize/</em>) in a text editor and restore all values from your old <em>ini.inc.php</em> file. The new include file contains many new settings which you should check out and adopt to your needs if needed. Please see the comments within the file for further information.
		</td>
	</tr><?php

		}
?>

</table><?php

		// --------------------------------------------------------------------

		// DISPLAY THE HTML FOOTER:
		// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
		showPageFooter($HeaderString, "");

		displayHTMLfoot();

		// --------------------------------------------------------------------

	}

	// --------------------------------------------------------------------

	// SHOW ERROR IN RED:
	function fieldError($fieldName, $errors)
	{
		if (isset($errors[$fieldName]))
			echo "\n\t\t\t<b><span class=\"warning\">" . $errors[$fieldName] . "</span></b>\n\t\t\t<br>";
	}

	// --------------------------------------------------------------------
  // Check for the presence of a value in a table.
  // If it doesn't exist, add the given row to that same table.
  function insertIfNotExists($keyColumn, $keyValue, $table, $values) {
    $query = "SELECT " . $keyColumn . " FROM " . $table . " WHERE " . $keyColumn . "=" . quote_smart($keyValue);
    if (!($result = @ mysql_query ($query, $connection)))
      if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
        showErrorMsg("The following error occurred while trying to query the database:", "");

    $rowsFound = @ mysql_num_rows($result);
    if ($rowsFound == 0)
      $query = "INSERT INTO " . $table . " VALUES " . quote_smart($values);
  }
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
?>
