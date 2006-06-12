<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./update.php
	// Created:    01-Mar-05, 20:47
	// Modified:   27-May-06, 00:17

	// This file will update any refbase MySQL database installation from v0.7 to v0.8.
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

	if (isset($_POST['pathToMYSQL']))
		$pathToMYSQL = $_POST['pathToMYSQL'];
	else
		$pathToMYSQL = "";

	if (isset($_POST['databaseStructureFile']))
		$databaseStructureFile = $_POST['databaseStructureFile'];
	else
		$databaseStructureFile = "";

	if (isset($_POST['pathToBibutils']))
		$pathToBibutils = $_POST['pathToBibutils'];
	else
		$pathToBibutils = "";

//	if (isset($_POST['defaultCharacterSet']))
//		$defaultCharacterSet = $_POST['defaultCharacterSet'];
//	else
//		$defaultCharacterSet = "";

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(false);

	// --------------------------------------------------------------------

	// Check the correct parameters have been passed:
	if (empty($adminUserName) AND empty($adminPassword) AND empty($pathToMYSQL) AND empty($databaseStructureFile))
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
			$formVars["pathToMYSQL"] = "/usr/local/mysql/bin/mysql";
			$formVars["databaseStructureFile"] = "./update.sql";
			$formVars["pathToBibutils"] = "/usr/local/bin/";
//			$formVars["defaultCharacterSet"] = "latin1";
		}

		// If there's no stored message available:
		if (!isset($_SESSION['HeaderString']))
		{
			if (empty($errors)) // provide the default message:
				$HeaderString = "To update refbase v0.7 please fill out the form below and click the <em>Update</em> button:";
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

//		// For the default character set, make sure that the correct popup menu entry is selected upon reload:
//		if ($formVars["defaultCharacterSet"] == "utf8")
//		{
//			$latin1CharacterSetSelected = "";
//			$unicodeCharacterSetSelected = " selected";
//		}
//		else // $formVars["defaultCharacterSet"] is 'latin1' or ''
//		{
//			$latin1CharacterSetSelected = " selected";
//			$unicodeCharacterSetSelected = "";
//		}

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
		<td valign="top"><b>Path to the MySQL application:</b></td>
		<td valign="top"><?php echo fieldError("pathToMYSQL", $errors); ?>

			<input type="text" name="pathToMYSQL" value="<?php echo $formVars["pathToMYSQL"]; ?>" size="30">
		</td>
		<td valign="top">Specify the full path to the 'mysql' command line interpreter. The given path represents a common location on unix systems, but yours may vary.</td>
	</tr>
	<tr>
		<td valign="top"><b>Path to the database structure file:</b></td>
		<td valign="top"><?php echo fieldError("databaseStructureFile", $errors); ?>

			<input type="text" name="databaseStructureFile" value="<?php echo $formVars["databaseStructureFile"]; ?>" size="30">
		</td>
		<td valign="top">The SQL file <em>update.sql</em> will insert any new database tables and update your existing tables.</td>
	</tr>
	<tr>
		<td valign="top"><b>Path to the bibutils directory [optional]:</b></td>
		<td valign="top"><?php echo fieldError("pathToBibutils", $errors); ?>

			<input type="text" name="pathToBibutils" value="<?php echo $formVars["pathToBibutils"]; ?>" size="30">
		</td>
		<td valign="top">If you'd like to use the export functionality you need to install <a href="http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html" title="bibutils home page">bibutils</a> and enter the full path to the bibutils utilities here. The given path just serves as an example and your path spec may be different. The path must end with a slash!</td>
	</tr><?php
// Currently, there's no support for character set transformation:
/*
?>

	<tr>
		<td valign="top"><b>Default character set:</b></td>
		<td valign="top"><?php echo fieldError("defaultCharacterSet", $errors); ?>

			<select name="defaultCharacterSet">
				<option<?php echo $latin1CharacterSetSelected; ?>>latin1</option>
				<option<?php echo $unicodeCharacterSetSelected; ?>>utf8</option>
			</select>
		</td>
		<td valign="top">Specify the default character set for the MySQL database used by refbase. Note that 'utf8' (Unicode) requires MySQL 4.1.x or greater, otherwise 'latin1' (i.e., 'ISO-8859-1 West European') will be used by default.</td>
	</tr><?php
*/
?>

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


		// Validate the 'pathToMYSQL' field:
		if (empty($formVars["pathToMYSQL"]))
			// The 'pathToMYSQL' field cannot be a null string
			$errors["pathToMYSQL"] = "This field cannot be blank:";

		elseif (ereg("[;|]", $formVars["pathToMYSQL"]))
			// For security reasons, the 'pathToMYSQL' field cannot contain the characters ';' or '|' (which would tie multiple shell commands together)
			$errors["pathToMYSQL"] = "Due to security reasons this field cannot contain the characters ';' or '|':";

		elseif (is_dir($formVars["pathToMYSQL"]))
			// Check if the specified path resolves to a directory
			$errors["pathToMYSQL"] = "You cannot specify a directory! Please give the path to the mysql command:";

		elseif (!is_readable($formVars["pathToMYSQL"]))
			// Check if the specified path resolves to the mysql application
			$errors["pathToMYSQL"] = "Your path specification is invalid (command not found):";

//		Note: Currently, the checks for whether the function is executable or whether it is mysql have been commented out,
//			  since they don't seem to work on windows! (see <http://sourceforge.net/forum/forum.php?thread_id=1021143&forum_id=218758>)

//		elseif (!is_executable($formVars["pathToMYSQL"]))
//			// Check if the given file is executable
//			$errors["pathToMYSQL"] = "This file does not appear to be an executable command:";

//		elseif (!ereg("(^|.*/)mysql$", $formVars["pathToMYSQL"]))
//			// Make sure that the given file is 'mysql'
//			$errors["pathToMYSQL"] = "This does not appear to be the 'mysql' command line interpreter:";


		// Validate the 'databaseStructureFile' field:
		if (empty($formVars["databaseStructureFile"]))
			// The 'databaseStructureFile' field cannot be a null string
			$errors["databaseStructureFile"] = "This field cannot be blank:";

		elseif (ereg("[;|]", $formVars["databaseStructureFile"]))
			// For security reasons, the 'databaseStructureFile' field cannot contain the characters ';' or '|' (which would tie multiple shell commands together)
			$errors["databaseStructureFile"] = "Due to security reasons this field cannot contain the characters ';' or '|':";

		elseif (is_dir($formVars["databaseStructureFile"]))
			// Check if the specified path resolves to a directory
			$errors["databaseStructureFile"] = "You cannot specify a directory! Please give the path to the database structure file:";

		elseif (!is_readable($formVars["databaseStructureFile"]))
			// Check if the specified path resolves to the database structure file
			$errors["databaseStructureFile"] = "Your path specification is invalid (file not found):";


		// Validate the 'pathToBibutils' field:
		if (!empty($formVars["pathToBibutils"])) // we'll only validate the 'pathToBibutils' field if it isn't empty (installation of bibutils is optional)
		{
			if (ereg("[;|]", $formVars["pathToBibutils"]))
				// For security reasons, the 'pathToBibutils' field cannot contain the characters ';' or '|' (which would tie multiple shell commands together)
				$errors["pathToBibutils"] = "Due to security reasons this field cannot contain the characters ';' or '|':";
	
			elseif (!is_readable($formVars["pathToBibutils"]))
				// Check if the specified path resolves to an existing directory
				$errors["pathToBibutils"] = "Your path specification is invalid (directory not found):";
	
			elseif (!is_dir($formVars["pathToBibutils"]))
				// Check if the specified path resolves to a directory (and not a file)
				$errors["pathToBibutils"] = "You must specify a directory! Please give the path to the directory containing the bibutils utilities:";
		}


		// Validate the 'defaultCharacterSet' field:
		// Note: Currently we're not generating an error & rooting back to the update form, if the user did choose 'utf8' but has some MySQL version < 4.1 installed.
		//       In this case, we'll simply ignore the setting and 'latin1' will be used by default.

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

		if (!(mysql_select_db($adminDatabaseName, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to connect to the database:", "");


//		// First, check if we're a dealing with MySQL version 4.1.x or greater:
//		// (MySQL 4.1.x is required if the refbase MySQL database/tables shall be updated using Unicode/UTF-8 as default character set)
//		$queryCheckVersion = "SELECT VERSION()";

//		// Run the version check query on the mysql database through the connection:
//		if (!($result = @ mysql_query ($queryCheckVersion, $connection)))
//			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
//				showErrorMsg("The following error occurred while trying to query the database:", "");

//		// Extract result:
//		$row = mysql_fetch_row($result); // fetch the current row into the array $row (it'll be always *one* row, but anyhow)
//		$mysqlVersionString = $row[0]; // extract the contents of the first (and only) row (returned version string will be something like "4.0.20-standard" etc.)
//		$mysqlVersion = preg_replace("/^(\d+\.\d+).+/", "\\1", $mysqlVersionString); // extract main version number (e.g. "4.0") from version string

		// --------------------------------------------------------------------

		// First, check which tables do exist within the user's existing literature database:
		$queryTables = "SHOW TABLES FROM " . $databaseName;

		// Run the query on the mysql database through the connection:
		if (!($result = @ mysql_query ($queryTables, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to query the database:", "");

		$resultArray1 = array();
		$addTablesArray = array("depends", "formats", "languages", "queries", "styles", "types", "user_formats", "user_permissions", "user_styles", "user_types");

		while ($row = @ mysql_fetch_array($result)) // for all database tables found, check if their names match the table names which we want to add using 'update.sql':
		{
			if (in_array($row[0], $addTablesArray))
				$resultArray1[] = "Table <em>" . $row[0] . "</em> already exists!";
		}

		// Second, check if fields which we're going to add do exist already:
		$updateTablesArray = array("deleted", "refs", "user_data", "users");
		$addFieldsArray = array("deleted.series_volume_numeric", "refs.series_volume_numeric", "user_data.user_groups", "user_data.cite_key", "user_data.related", "users.user_groups");

		foreach($updateTablesArray as $updateTable)
		{
			$queryFields = "SHOW FIELDS FROM " . $updateTable . " FROM " . $databaseName;

			// Run the query on the mysql database through the connection:
			if (!($result = @ mysql_query ($queryFields, $connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to query the database:", "");

			while ($row = @ mysql_fetch_array($result)) // for all fields found, check if their names match the field names which we want to add using 'update.sql':
			{
				$thisField = $updateTable . "." . $row["Field"];
				if (in_array($thisField, $addFieldsArray))
					$resultArray1[] = "Field <em>" . $row["Field"] . "</em> in table <em>" . $updateTable . "</em> already exists!";
			}
		}

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

		// IMPORT the literature database structure from file:
		exec($pathToMYSQL . " -h " . $hostName . " -u " . $adminUserName . " -p" . $adminPassword . " --database=" . $databaseName . " < " . $databaseStructureFile . " 2>&1", $resultArray2);

		// User note from <http://de2.php.net/manual/en/ref.exec.php> regarding the use of PHP's 'exec()' command:
		// From 'eremy at ntb dot co dot nz' (28-Sep-2003 03:18):
		// If an error occurs in the code you're trying to exec(), it can be challenging to figure out what's going
		// wrong, since php echoes back the stdout stream rather than the stderr stream where all the useful error
		// reporting's done. The solution is to add the code "2>&1" to the end of your shell command, which redirects
		// stderr to stdout, which you can then easily print using something like print `shellcommand 2>&1`.

		if (!empty($resultArray2))
		{
			$HeaderString = "Update process interrupted! The following errors occurred while updating your database:";
			$resultArray2String = implode("\n<br>", $resultArray2); // merge array elements into a string

			// Note that we don't use the 'showErrorMsg()' function here since we want to provide a custom 'errorMsg' parameter:
			header("Location: error.php?errorNo=&errorMsg=" . rawurlencode($resultArray2String) . "&headerMsg=" . rawurlencode($HeaderString) . "&oldQuery=");

			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}


		// Prepare the update queries and proceed with the actual update procedure:

		$queryArray = array(); // initialize array variable

		// Check how many users are contained in table 'users':
		$queryUserIDs = "SELECT user_id FROM " . $databaseName . ".users";

		// Run the query on the mysql database through the connection:
		if (!($result = @ mysql_query ($queryUserIDs, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to query the database:", "");

		// Extract result:
		$rowsFound = @ mysql_num_rows($result);
		if ($rowsFound > 0) // If there were rows (= user IDs) found ...
		{
			// Prepare queries which update the 'user_*' tables:
			$queryInsertUserFormats = "INSERT INTO " . $databaseName . ".user_formats VALUES ";
			$queryInsertUserStyles = "INSERT INTO " . $databaseName . ".user_styles VALUES ";
			$queryInsertUserTypes = "INSERT INTO " . $databaseName . ".user_types VALUES ";
			$queryInsertUserPermissions = "INSERT INTO " . $databaseName . ".user_permissions VALUES ";

			$i = 0;
			while ($row = @ mysql_fetch_array($result)) // for all user IDs found, insert corresponding user entries into the 'user_*' tables:
			{
				if ($i++ != 0)
				{
					$queryInsertUserFormats .= ", ";
					$queryInsertUserStyles .= ", ";
					$queryInsertUserTypes .= ", ";
					$queryInsertUserPermissions .= ", ";
				}

				$queryInsertUserFormats .= "(NULL, 1, " . $row['user_id'] . ", 'true'), (NULL, 2, " . $row['user_id'] . ", 'true'), (NULL, 3, " . $row['user_id'] . ", 'true'), (NULL, 4, " . $row['user_id'] . ", 'true'), (NULL, 5, " . $row['user_id'] . ", 'true'), (NULL, 6, " . $row['user_id'] . ", 'true'), (NULL, 7, " . $row['user_id'] . ", 'true'), (NULL, 8, " . $row['user_id'] . ", 'true'), (NULL, 9, " . $row['user_id'] . ", 'true'), (NULL, 10, " . $row['user_id'] . ", 'true'), (NULL, 11, " . $row['user_id'] . ", 'true')";
				$queryInsertUserStyles .= "(NULL, 1, " . $row['user_id'] . ", 'true'), (NULL, 2, " . $row['user_id'] . ", 'true'), (NULL, 3, " . $row['user_id'] . ", 'true'), (NULL, 4, " . $row['user_id'] . ", 'true'), (NULL, 5, " . $row['user_id'] . ", 'true')";
				$queryInsertUserTypes .= "(NULL, 1, " . $row['user_id'] . ", 'true'), (NULL, 2, " . $row['user_id'] . ", 'true'), (NULL, 3, " . $row['user_id'] . ", 'true'), (NULL, 4, " . $row['user_id'] . ", 'true'), (NULL, 5, " . $row['user_id'] . ", 'true'), (NULL, 6, " . $row['user_id'] . ", 'true')";
				$queryInsertUserPermissions .= "(NULL, " . $row['user_id'] . ", 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'no')";
			}

			$queryArray[] = $queryInsertUserFormats;
			$queryArray[] = $queryInsertUserStyles;
			$queryArray[] = $queryInsertUserTypes;
			$queryArray[] = $queryInsertUserPermissions;
		}

		if (!empty($pathToBibutils)) // we'll only update the bibutils path if '$pathToBibutils' isn't empty (installation of bibutils is optional)
			// Prepare query which updates the path to the bibutils utilities in table 'depends':
			$queryArray[] = "UPDATE " . $databaseName . ".depends SET depends_path = \"" . $pathToBibutils . "\" WHERE depends_external = \"bibutils\""; // update the bibutils path spec
		else // we set the 'depends_enabled' field in table 'depends' to 'false' to indicate that bibutils isn't installed
			$queryArray[] = "UPDATE " . $databaseName . ".depends SET depends_enabled = \"false\" WHERE depends_external = \"bibutils\""; // disable bibutils functionality


		// (2) Run the UPDATE queries on the mysql database through the connection:
		foreach($queryArray as $query)
			if (!($result = @ mysql_query ($query, $connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to query the database:", "");


		// (5) Close the database connection:
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
		<td colspan="2"><h3>Welcome to refbase v0.8!</h3></td>
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
?>
