<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./user_options_modify.php
	// Created:    26-Oct-04, 20:57
	// Modified:   31-Oct-04, 23:09

	// This script validates user options selected within the form provided by 'user_options.php'.
	// If validation succeeds, it UPDATEs the corresponding table fields for that user and redirects to a receipt page;
	// if it fails, it creates error messages and these are later displayed by 'user_options.php'.

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
		$formVars[$varname] = $value;

	// --------------------------------------------------------------------

	// First of all, check if this script was called by something else than 'user_options.php':
	if (!ereg(".+/user_options.php", $_SERVER['HTTP_REFERER']))
	{
		// save an appropriate error message:
		$HeaderString = "<b><span class=\"warning\">Invalid call to script 'user_options_modify.php'!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		
		if (!empty($_SERVER['HTTP_REFERER'])) // if the referer variable isn't empty
			header("Location: " . $_SERVER['HTTP_REFERER']); // redirect to calling page
		else
			header("Location: index.php"); // redirect to main page ('index.php')

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// VALIDATE FORM DATA:

	// (Note: checking for missing/incorrect input of the language field isn't really necessary if a popup is used as input field -- as it is right now)
//	// Validate the language
//	if (empty($formVars["languageName"]))
//		// Language cannot be a null string
//		$errors["languageName"] = "The language field cannot be blank:";

	// Note: currently, the user must select at least one item within the type/style/format lists. Alternatively, we could grey out the corresponding interface elements
	//       if a user deselects all items. Or, hiding the corresponding interface elements *completely* would actually give the user the possibility to remove unwanted/unneeded "features"!

	// Validate the reference type selector
	if (empty($formVars["referenceTypeSelector"]))
		$errors["referenceTypeSelector"] = "You must choose at least one reference type:";

	// Validate the citation style selector
	if (empty($formVars["citationStyleSelector"]))
		$errors["citationStyleSelector"] = "You must choose at least one citation style:";

	// Validate the export format selector
	if (empty($formVars["exportFormatSelector"]))
		$errors["exportFormatSelector"] = "You must choose at least one export format:";

	// --------------------------------------------------------------------

	// Now the script has finished the validation, check if there were any errors:
	if (count($errors) > 0)
	{
		// Write back session variables:
		saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("formVars", $formVars);

		// There are errors. Relocate back to the client form:
		header("Location: user_options.php?userID=" . $_REQUEST['userID']); // 'userID' got included as hidden form tag by 'user_options.php'

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// If we made it here, then the data is considered valid!

	// CONSTRUCT SQL QUERY:
	// If a user is logged in and has submitted 'user_options.php' with a 'userID' parameter:
	// (while the admin has no restrictions, a normal user can only submit 'user_options.php' with his own 'userID' as parameter!)
	if (isset($_SESSION['loginEmail']) && ($_REQUEST['userID'] != "")) // -> perform an update:
	{
		if ($loginEmail != $adminLoginEmail) // if not admin logged in ('$adminLoginEmail' is specified in 'ini.inc.php')
			$userID = getUserID($loginEmail); // Get the 'user_id' using 'loginEmail' (function 'getUserID()' is defined in 'include.inc.php')
		else // if the admin is logged in he should be able to make any changes to account data/options of _other_ users...
			$userID = $_REQUEST['userID']; // ...in this case we accept 'userID' from the GET/POST request (it got included as hidden form tag by 'user_options.php')

		// UPDATE - construct queries to update the relevant table fields for this user

		// a) update the language field of the 'users' table:
		$queryArray[] = "UPDATE users SET "
						. "language = \"" . $formVars["languageName"] . "\" "
						. "WHERE user_id = $userID";


		if ($loginEmail == $adminLoginEmail) // if the admin is logged in
		{
			// b) update all entries for this user within the 'user_types' table:

			//     - first, get a list of IDs for all types within the 'user_types' table that are available and were enabled by the admin for the current user:
			$enabledUserTypesArray = getEnabledUserFormatsStylesTypes($userID, "type", "", true); // function 'getEnabledUserFormatsStylesTypes()' is defined in 'include.inc.php'

			$enabledUserTypesInSelectedTypesArray = array_intersect($enabledUserTypesArray, $formVars["referenceTypeSelector"]);

			$enabledUserTypesNOTInSelectedTypesArray = array_diff($enabledUserTypesArray, $formVars["referenceTypeSelector"]);

			$selectedTypesNOTInEnabledUserTypesArray = array_diff($formVars["referenceTypeSelector"], $enabledUserTypesArray);

			if (!empty($enabledUserTypesNOTInSelectedTypesArray))
			{
				// - remove types which do exist within the 'user_types' table but were deselected by the admin:
				$enabledUserTypesNOTInSelectedTypesString = implode("|", $enabledUserTypesNOTInSelectedTypesArray); // join array of type IDs using a pipe as separator

				$queryArray[] = "DELETE FROM user_types "
								. "WHERE user_id = $userID AND type_id RLIKE \"^(" . $enabledUserTypesNOTInSelectedTypesString . ")$\"";
			}
	
			if (!empty($selectedTypesNOTInEnabledUserTypesArray))
			{
				// - insert types that were selected by the admin but which do not yet exist within the 'user_types' table:
				$selectedTypesNOTInEnabledUserTypesString = implode("|", $selectedTypesNOTInEnabledUserTypesArray); // join array of type IDs using a pipe as separator

				$insertTypesQuery = "INSERT INTO user_types VALUES ";

				foreach ($selectedTypesNOTInEnabledUserTypesArray as $newUserTypeID)
					$insertTypesQueryValues[] = "(NULL, $newUserTypeID, $userID, 'true')";

				$queryArray[] = $insertTypesQuery . implode(", ", $insertTypesQueryValues) . ";";
			}

			// ---------------------
			// c) update all entries for this user within the 'user_styles' table:

			//     - first, get a list of IDs for all styles within the 'user_styles' table that are available and were enabled by the admin for the current user:
			$enabledUserStylesArray = getEnabledUserFormatsStylesTypes($userID, "style", "", true); // function 'getEnabledUserFormatsStylesTypes()' is defined in 'include.inc.php'

			$enabledUserStylesInSelectedStylesArray = array_intersect($enabledUserStylesArray, $formVars["citationStyleSelector"]);

			$enabledUserStylesNOTInSelectedStylesArray = array_diff($enabledUserStylesArray, $formVars["citationStyleSelector"]);

			$selectedStylesNOTInEnabledUserStylesArray = array_diff($formVars["citationStyleSelector"], $enabledUserStylesArray);

			if (!empty($enabledUserStylesNOTInSelectedStylesArray))
			{
				// - remove styles which do exist within the 'user_styles' table but were deselected by the admin:
				$enabledUserStylesNOTInSelectedStylesString = implode("|", $enabledUserStylesNOTInSelectedStylesArray); // join array of style IDs using a pipe as separator

				$queryArray[] = "DELETE FROM user_styles "
								. "WHERE user_id = $userID AND style_id RLIKE \"^(" . $enabledUserStylesNOTInSelectedStylesString . ")$\"";
			}
	
			if (!empty($selectedStylesNOTInEnabledUserStylesArray))
			{
				// - insert styles that were selected by the admin but which do not yet exist within the 'user_styles' table:
				$selectedStylesNOTInEnabledUserStylesString = implode("|", $selectedStylesNOTInEnabledUserStylesArray); // join array of style IDs using a pipe as separator

				$insertStylesQuery = "INSERT INTO user_styles VALUES ";

				foreach ($selectedStylesNOTInEnabledUserStylesArray as $newUserStyleID)
					$insertStylesQueryValues[] = "(NULL, $newUserStyleID, $userID, 'true')";

				$queryArray[] = $insertStylesQuery . implode(", ", $insertStylesQueryValues) . ";";
			}
	
			// ---------------------
			// d) update all entries for this user within the 'user_formats' table:

			//     - first, get a list of IDs for all formats within the 'user_formats' table that are available and were enabled by the admin for the current user:
			$enabledUserFormatsArray = getEnabledUserFormatsStylesTypes($userID, "format", "export", true); // function 'getEnabledUserFormatsStylesTypes()' is defined in 'include.inc.php'

			$enabledUserFormatsInSelectedFormatsArray = array_intersect($enabledUserFormatsArray, $formVars["exportFormatSelector"]);

			$enabledUserFormatsNOTInSelectedFormatsArray = array_diff($enabledUserFormatsArray, $formVars["exportFormatSelector"]);

			$selectedFormatsNOTInEnabledUserFormatsArray = array_diff($formVars["exportFormatSelector"], $enabledUserFormatsArray);

			if (!empty($enabledUserFormatsNOTInSelectedFormatsArray))
			{
				// - remove formats which do exist within the 'user_formats' table but were deselected by the admin:
				$enabledUserFormatsNOTInSelectedFormatsString = implode("|", $enabledUserFormatsNOTInSelectedFormatsArray); // join array of format IDs using a pipe as separator

				$queryArray[] = "DELETE FROM user_formats "
								. "WHERE user_id = $userID AND format_id RLIKE \"^(" . $enabledUserFormatsNOTInSelectedFormatsString . ")$\"";
			}
	
			if (!empty($selectedFormatsNOTInEnabledUserFormatsArray))
			{
				// - insert formats that were selected by the admin but which do not yet exist within the 'user_formats' table:
				$selectedFormatsNOTInEnabledUserFormatsString = implode("|", $selectedFormatsNOTInEnabledUserFormatsArray); // join array of format IDs using a pipe as separator

				$insertFormatsQuery = "INSERT INTO user_formats VALUES ";

				foreach ($selectedFormatsNOTInEnabledUserFormatsArray as $newUserFormatID)
					$insertFormatsQueryValues[] = "(NULL, $newUserFormatID, $userID, 'true')";

				$queryArray[] = $insertFormatsQuery . implode(", ", $insertFormatsQueryValues) . ";";
			}
		}

		// ---------------------------------------------------------------

		else // if a normal user is logged in
		{
			// b) update all entries for this user within the 'user_types' table:
			$typeIDString = implode("|", $formVars["referenceTypeSelector"]); // join array of type IDs using a pipe as separator
	
			$queryArray[] = "UPDATE user_types SET "
							. "show_type = \"true\" "
							. "WHERE user_id = $userID AND type_id RLIKE \"^(" . $typeIDString . ")$\"";
	
			$queryArray[] = "UPDATE user_types SET "
							. "show_type = \"false\" "
							. "WHERE user_id = $userID AND type_id NOT RLIKE \"^(" . $typeIDString . ")$\"";
	
			// c) update all entries for this user within the 'user_styles' table:
			$styleIDString = implode("|", $formVars["citationStyleSelector"]); // join array of style IDs using a pipe as separator
	
			$queryArray[] = "UPDATE user_styles SET "
							. "show_style = \"true\" "
							. "WHERE user_id = $userID AND style_id RLIKE \"^(" . $styleIDString . ")$\"";
	
			$queryArray[] = "UPDATE user_styles SET "
							. "show_style = \"false\" "
							. "WHERE user_id = $userID AND style_id NOT RLIKE \"^(" . $styleIDString . ")$\"";
	
			// d) update all entries for this user within the 'user_formats' table:
			$formatIDString = implode("|", $formVars["exportFormatSelector"]); // join array of format IDs using a pipe as separator
	
			$queryArray[] = "UPDATE user_formats SET "
							. "show_format = \"true\" "
							. "WHERE user_id = $userID AND format_id RLIKE \"^(" . $formatIDString . ")$\"";
	
			$queryArray[] = "UPDATE user_formats SET "
							. "show_format = \"false\" "
							. "WHERE user_id = $userID AND format_id NOT RLIKE \"^(" . $formatIDString . ")$\"";
		}
 	}

	// --------------------------------------------------------------------

	// (3) RUN the queries on the database through the connection:
	foreach($queryArray as $query)
		$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	// ----------------------------------------------

	// we'll only update the appropriate session variables if either a normal user is logged in -OR- the admin is logged in AND the updated user data are his own:
	if (($loginEmail != $adminLoginEmail) | (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
	{
		// Write back session variables:
		saveSessionVariable("userLanguage", $formVars["languageName"]); // function 'saveSessionVariable()' is defined in 'include.inc.php'
	
		// Note: the user's types/styles/formats will be written to their corresponding session variables in function 'getVisibleUserFormatsStylesTypes()'
		//       which will be called by the following receipt page ('user_receipt.php') anyhow, so we won't call the function here...
	}

	// Clear the 'errors' and 'formVars' session variables so a future <form> is blank:
	deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	deleteSessionVariable("formVars");

	// ----------------------------------------------

	// (4) Now show the user RECEIPT:
	header("Location: user_receipt.php?userID=$userID");

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>
