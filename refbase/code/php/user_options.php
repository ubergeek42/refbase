<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./user_options.php
	// Created:    24-Oct-04, 19:31
	// Modified:   27-Feb-04, 18:14

	// This script provides options which are individual for each user.
	// 
	// 

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

	// Extract session variables (only necessary if register globals is OFF!):
	if (isset($_SESSION['errors']))
		$errors = $_SESSION['errors'];
	else
		$errors = array(); // initialize variable (in order to prevent 'Undefined index/variable...' messages)

	if (isset($_SESSION['formVars']))
		$formVars = $_SESSION['formVars'];
	else
		$formVars = array(); // initialize variable (in order to prevent 'Undefined index/variable...' messages)

	// The current values of the session variables 'errors' and 'formVars' get stored in '$errors' or '$formVars', respectively. (either automatically if
	// register globals is ON, or explicitly if register globals is OFF).
	// We need to clear these session variables here, since they would otherwise be there even if 'user_options.php' gets called with a different userID!
	// Note: though we clear the session variables, the current error message (or form variables) is still available to this script via '$errors' (or '$formVars', respectively).
	deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	deleteSessionVariable("formVars");

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// A user must be logged in in order to call 'user_options.php':
	if (!isset($_SESSION['loginEmail']))
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You must login to view your user account options!</span></b>";

		// save the URL of the currently displayed page:
		$referer = $_SERVER['HTTP_REFERER'];

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("referer", $referer);

		header("Location: user_login.php");
		exit;
	}

	// --------------------------------------------------------------------

	// Set the '$userID' variable:
	if (isset($_REQUEST['userID'])) // for normal users NOT being logged in -OR- for the admin:
		$userID = $_REQUEST['userID'];
	else
		$userID = NULL; // '$userID = ""' wouldn't be correct here, since then any later 'isset($userID)' statement would resolve to true!

	if (isset($_SESSION['loginEmail']) && ($loginEmail != $adminLoginEmail)) // a normal user IS logged in ('$adminLoginEmail' is specified in 'ini.inc.php')
		// Check this user matches the userID (viewing and modifying other user's account options is only allowed to the admin)
		if ($userID != getUserID($loginEmail)) // (function 'getUserID()' is defined in 'include.inc.php')
		{
			// save an error message:
			$HeaderString = "<b><span class=\"warning\">You can only edit your own user data!</span></b>";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
	
			$userID = getUserID($loginEmail); // re-establish the user's correct user_id
		}

	// --------------------------------------------------------------------

	// Check the correct parameters have been passed
	if ($userID == "") // note that we can't use 'empty($userID)' here, since 'userID=0' must be allowed so that the admin can edit options for the default user (= no user logged in)
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">Missing parameters for script 'user_options.php'!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Redirect the browser back to the calling page
		header("Location: index.php"); // Note: if 'header("Location: " . $_SERVER['HTTP_REFERER'])' is used, the error message won't get displayed! ?:-/
		exit;
	}

	// --------------------------------------------------------------------

	// Set header message:
	if (!isset($_SESSION['HeaderString'])) // if there's no stored message available
	{
		if (empty($errors)) // provide the default messages:
			$HeaderString = "Modify your account options:";
		else // -> there were errors validating the user's options
			$HeaderString = "<b><span class=\"warning\">There were validation errors regarding the options you selected. Please check the comments above the respective fields:</span></b>";
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


	// CONSTRUCT SQL QUERY:
	$query = "SELECT first_name, last_name, email, language FROM $tableUsers WHERE user_id = " . $userID;

	// (3a) RUN the query on the database through the connection:
	$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	// (3b) EXTRACT results:
	$row = mysql_fetch_array($result); //fetch the current row into the array $row

	// If the admin is logged in AND the displayed user data are NOT his own, we overwrite the default header message:
	// (Since the admin is allowed to view and edit account data from other users, we have to provide a dynamic header message in that case)
	if (($loginEmail == $adminLoginEmail) && ($userID != getUserID($loginEmail))) // ('$adminLoginEmail' is specified in 'ini.inc.php')
		$HeaderString = "Edit account options for <b>" . encodeHTML($row["first_name"]) . " " . encodeHTML($row["last_name"]) . " (" . $row["email"] . ")</b>:";

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (4) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- User Options", "noindex,nofollow", "User options offered by the " . encodeHTML($officialDatabaseName), "\n\t<meta http-equiv=\"expires\" content=\"0\">", true, "", $viewType);
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

	// --------------------------------------------------------------------

	if (empty($errors))
	{
		// Reset the '$formVars' variable (since we're loading from the user tables):
		$formVars = array();

		// Reset the '$errors' variable:
		$errors = array();

		// Load all the form variables with user data & options:
		$formVars["language"] = $row["language"];
	}


	// Get all languages that were setup and enabled by the admin:
	$languagesArray = getLanguages(""); // function 'getLanguages()' is defined in 'include.inc.php'

	$languageOptionTags = buildSelectMenuOptions($languagesArray, " *; *", "\t\t\t", false); // build properly formatted <option> tag elements from language items returned by function 'getLanguages()'
	$userLanguage = getLanguages($userID); // get the preferred language for the current user
	$languageOptionTags = ereg_replace("<option>$userLanguage[0]", "<option selected>$userLanguage[0]", $languageOptionTags); // select the user's preferred language

	// Get all reference types that are available (admin logged in) or which were enabled for the current user (normal user logged in):
	$typeOptionTags = returnFormatsStylesTypesAsOptionTags($userID, "type", "");

	// Get all citation styles that are available (admin logged in) or which were enabled for the current user (normal user logged in):
	$styleOptionTags = returnFormatsStylesTypesAsOptionTags($userID, "style", "");

	// Get all export formats that are available (admin logged in) or which were enabled for the current user (normal user logged in):
	$formatOptionTags = returnFormatsStylesTypesAsOptionTags($userID, "format", "export");

	if ($loginEmail == $adminLoginEmail) // if the admin is logged in
		$selectListIdentifier = "Enabled";
	else // if ($loginEmail != $adminLoginEmail) // if a normal user is logged in
		$selectListIdentifier = "Show";
		

	// Start <form> and <table> holding all the form elements:
?>

<form method="POST" action="user_options_modify.php" name="userOptions">
<input type="hidden" name="userID" value="<? echo $userID ?>">
<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds a form with user options">
<tr>
	<td align="left" width="169"><b>Display Options:</b></td>
	<td align="left" width="169">Use language:</td>
	<td><? echo fieldError("languageName", $errors); ?>

		<select name="languageName"><? echo $languageOptionTags; ?>

		</select>
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td align="left" valign="top"><? echo $selectListIdentifier; ?> reference types:</td>
	<td valign="top"><? echo fieldError("referenceTypeSelector", $errors); ?>

		<select name="referenceTypeSelector[]" multiple><? echo $typeOptionTags; ?>

		</select>
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td align="left" valign="top"><? echo $selectListIdentifier; ?> citation styles:</td>
	<td valign="top"><? echo fieldError("citationStyleSelector", $errors); ?>

		<select name="citationStyleSelector[]" multiple><? echo $styleOptionTags; ?>

		</select>
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td align="left" valign="top"><? echo $selectListIdentifier; ?> export formats:</td>
	<td valign="top"><? echo fieldError("exportFormatSelector", $errors); ?>

		<select name="exportFormatSelector[]" multiple><? echo $formatOptionTags; ?>

		</select>
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		<input type="submit" value="Submit">
	</td>
</tr><?php
	if ($loginEmail == $adminLoginEmail) // if the admin is logged in, add form elements to set the user's permissions:
	{
		// Get the user permissions for the current user:
		$userPermissionsArray = getPermissions($userID, "user", false); // function 'getPermissions()' is defined in 'include.inc.php'

		// Setup variables to mark the checkboxes according to the user's permissions:
		if ($userPermissionsArray['allow_add'] == 'yes')
			$allowAddChecked = " checked";
		else
			$allowAddChecked = "";

		if ($userPermissionsArray['allow_edit'] == 'yes')
			$allowEditChecked = " checked";
		else
			$allowEditChecked = "";

		if ($userPermissionsArray['allow_delete'] == 'yes')
			$allowDeleteChecked = " checked";
		else
			$allowDeleteChecked = "";

		if ($userPermissionsArray['allow_download'] == 'yes')
			$allowDownloadChecked = " checked";
		else
			$allowDownloadChecked = "";

		if ($userPermissionsArray['allow_upload'] == 'yes')
			$allowUploadChecked = " checked";
		else
			$allowUploadChecked = "";

		if ($userPermissionsArray['allow_details_view'] == 'yes')
			$allowDetailsViewChecked = " checked";
		else
			$allowDetailsViewChecked = "";

		if ($userPermissionsArray['allow_print_view'] == 'yes')
			$allowPrintViewChecked = " checked";
		else
			$allowPrintViewChecked = "";

		if ($userPermissionsArray['allow_sql_search'] == 'yes')
			$allowSQLSearchChecked = " checked";
		else
			$allowSQLSearchChecked = "";

		if ($userPermissionsArray['allow_user_groups'] == 'yes')
			$allowUserGroupsChecked = " checked";
		else
			$allowUserGroupsChecked = "";

		if ($userPermissionsArray['allow_user_queries'] == 'yes')
			$allowUserQueriesChecked = " checked";
		else
			$allowUserQueriesChecked = "";

		if ($userPermissionsArray['allow_rss_feeds'] == 'yes')
			$allowRSSFeedsChecked = " checked";
		else
			$allowRSSFeedsChecked = "";

		if ($userPermissionsArray['allow_import'] == 'yes')
			$allowImportChecked = " checked";
		else
			$allowImportChecked = "";

		if ($userPermissionsArray['allow_batch_import'] == 'yes')
			$allowBatchImportChecked = " checked";
		else
			$allowBatchImportChecked = "";

		if ($userPermissionsArray['allow_export'] == 'yes')
			$allowExportChecked = " checked";
		else
			$allowExportChecked = "";

		if ($userPermissionsArray['allow_batch_export'] == 'yes')
			$allowBatchExportChecked = " checked";
		else
			$allowBatchExportChecked = "";

		if ($userPermissionsArray['allow_cite'] == 'yes')
			$allowCiteChecked = " checked";
		else
			$allowCiteChecked = "";

		if ($userPermissionsArray['allow_change_personinfo'] == 'yes')
			$allowChangePersonInfoChecked = " checked";
		else
			$allowChangePersonInfoChecked = "";
?>

<tr>
	<td align="left" height="15"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"><b>User Permissions:</b></td>
	<td>
		<input type="checkbox" name="allow_add" value="yes"<? echo $allowAddChecked; ?>>&nbsp;&nbsp;Add records
	</td>
	<td>
		<input type="checkbox" name="allow_download" value="yes"<? echo $allowDownloadChecked; ?>>&nbsp;&nbsp;File download
	</td>
</tr>
<tr>
	<td align="left" class="small">
		<!--<a href="JavaScript:checkall(true,'allow*')" title="select all permission options">Select All</a>&nbsp;&nbsp;&nbsp;-->
		<!--<a href="JavaScript:checkall(false,'allow*')" title="deselect all permission options">Deselect All</a>-->
	</td>
	<td>
		<input type="checkbox" name="allow_edit" value="yes"<? echo $allowEditChecked; ?>>&nbsp;&nbsp;Edit records
	</td>
	<td>
		<input type="checkbox" name="allow_upload" value="yes"<? echo $allowUploadChecked; ?>>&nbsp;&nbsp;File upload
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_delete" value="yes"<? echo $allowDeleteChecked; ?>>&nbsp;&nbsp;Delete records
	</td>
	<td></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_details_view" value="yes"<? echo $allowDetailsViewChecked; ?>>&nbsp;&nbsp;Details view
	</td>
	<td>
		<input type="checkbox" name="allow_sql_search" value="yes"<? echo $allowSQLSearchChecked; ?>>&nbsp;&nbsp;SQL search
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_print_view" value="yes"<? echo $allowPrintViewChecked; ?>>&nbsp;&nbsp;Print view
	</td>
	<td></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_user_groups" value="yes"<? echo $allowUserGroupsChecked; ?>>&nbsp;&nbsp;User groups
	</td>
	<td>
		<input type="checkbox" name="allow_rss_feeds" value="yes"<? echo $allowRSSFeedsChecked; ?>>&nbsp;&nbsp;RSS feeds
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_user_queries" value="yes"<? echo $allowUserQueriesChecked; ?>>&nbsp;&nbsp;User queries
	</td>
	<td></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_import" value="yes"<? echo $allowImportChecked; ?>>&nbsp;&nbsp;Import
	</td>
	<td>
		<input type="checkbox" name="allow_batch_import" value="yes"<? echo $allowBatchImportChecked; ?>>&nbsp;&nbsp;Batch import
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_export" value="yes"<? echo $allowExportChecked; ?>>&nbsp;&nbsp;Export
	</td>
	<td>
		<input type="checkbox" name="allow_batch_export" value="yes"<? echo $allowBatchExportChecked; ?>>&nbsp;&nbsp;Batch export
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_cite" value="yes"<? echo $allowCiteChecked; ?>>&nbsp;&nbsp;Cite
	</td>
	<td></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td>
		<input type="checkbox" name="allow_change_personinfo" value="yes"<? echo $allowChangePersonInfoChecked; ?>>&nbsp;&nbsp;Change personal info
	</td>
	<td></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2"></td>
</tr>
<tr>
	<td align="left"></td>
	<td colspan="2">
		<input type="submit" value="Submit">
	</td>
</tr><?php
	}
?>

</table>
</form><?php

	// --------------------------------------------------------------------

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// SHOW ERROR IN RED:
	function fieldError($fieldName, $errors)
	{
		if (isset($errors[$fieldName]))
			echo "\n\t\t<b><span class=\"warning\">" . $errors[$fieldName] . "</span></b>\n\t\t<br>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc.php')
	displayfooter("");

	// --------------------------------------------------------------------
?>

</body>
</html>
