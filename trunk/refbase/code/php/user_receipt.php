<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./user_receipt.php
	// Created:    16-Apr-02, 10:54
	// Modified:   30-May-04, 19:35

	// This script shows the user a receipt for their user UPDATE or INSERT.
	// It carries out no database actions and can be bookmarked.
	// The user must be logged in to view it.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'header.inc.php'; // include header
	include 'footer.inc.php'; // include footer
	include 'include.inc.php'; // include common functions
	include "ini.inc.php"; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session();

	// Extract the 'userID' parameter from the request:
	if (isset($_REQUEST['userID']))
		$userID = $_REQUEST['userID'];
	else
		$userID = ""; // we do it for clarity reasons here (and in order to prevent any 'Undefined variable...' messages)

	// Check if the user is logged in
	if (!isset($_SESSION['loginEmail']) && ($userID != 0))
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You must login to view your user account details!</span></b>";

		// save the URL of the currently displayed page:
		$referer = $_SERVER['HTTP_REFERER'];

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("referer", $referer);

		header("Location: user_login.php");
		exit;
	}

	// Check the correct parameters have been passed
	if ($userID == "")
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">Incorrect parameters to script 'user_receipt.php'!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Redirect the browser back to the calling page
		header("Location: index.php"); // Note: if 'header("Location: " . $_SERVER['HTTP_REFERER'])' is used, the error message won't get displayed! ?:-/
		exit;
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// For regular users, validate that the correct userID has been passed to the script:
 	if (isset($_SESSION['loginEmail']) && ($loginEmail != $adminLoginEmail))
		// check this user matches the userID (viewing user account details is only allowed to the admin)
		if ($userID != getUserID($loginEmail))
		{
			// otherwise save an error message:
			$HeaderString = "<b><span class=\"warning\">You can only view your own user receipt!<span></b>";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

			$userID = getUserID($loginEmail); // and re-establish the user's correct user_id
		}

	// Extract the type of action requested by the user, either 'delete' or ''.
	// ('' or anything else will be treated equal to 'edit').
	// We actually extract the variable 'userAction' only if the admin is logged in
	// (since only the admin will be allowed to delete a user):
 	if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail)) // ('$adminLoginEmail' is specified in 'ini.inc.php')
 	{
		if (isset($_REQUEST['userAction']))
			$userAction = $_REQUEST['userAction'];
		else
			$userAction = ""; // we do it for clarity reasons here (and in order to prevent any 'Undefined variable...' messages)

		if ($userAction == "Delete")
		{
			if ($userID == getUserID($loginEmail)) // if the admin userID was passed to the script
			{
				// save an error message:
				$HeaderString = "<b><span class=\"warning\">You cannot delete your own user data!<span></b>";

				// Write back session variables:
				saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

				$userAction = "Edit"; // and re-set the user action to 'edit'
			}
		}
		else
			$userAction = "Edit"; // everything that isn't a 'delete' action will be an 'edit' action
	}
	else // otherwise we simply assume an 'edit' action, no matter what was passed to the script (thus, no regular user will be able to delete a user)
		$userAction = "Edit";

	// Extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// --------------------------------------------------------------------

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// Show the user confirmation:
	if ($userID == 0) // 'userID=0' is sent by 'user_validation.php' to indicate a NEW user who has successfully submitted 'user_details.php'
		showEmailConfirmation($userID);
	else
		showUserData($userID, $userAction, $connection);

	// ----------------------------------------------

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// Show a new user a confirmation screen, confirming that the submitted user data have been correctly received:
	function showEmailConfirmation($userID)
	{
		global $HeaderString;
		global $viewType;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $loginEmail;
		global $adminLoginEmail;
		global $officialDatabaseName;

		// Build the correct header message:
		if (!isset($_SESSION['HeaderString']))
			$HeaderString = "Submission confirmation:"; // provide the default message
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// Call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(htmlentities($officialDatabaseName) . " -- User Receipt", "noindex,nofollow", "Receipt page confirming correct submission of new user details to the " . htmlentities($officialDatabaseName), "", false, "", $viewType);
		showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

		$confirmationText = "Thanks for your interest in the " . htmlentities($officialDatabaseName) . "!"
					. "<br><br>The data you provided have been sent to our database admin."
					. "<br>We'll process your request and mail back to you as soon as we can!"
					. "<br><br>[Back to <a href=\"index.php\">" . htmlentities($officialDatabaseName) . " Home</a>]";

		// Start a table:
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays user submission feedback\">";

		echo "\n<tr>\n\t<td>" . $confirmationText . "</td>\n</tr>";

		echo "\n</table>";

	}

	// --------------------------------------------------------------------

	// Show the user an UPDATE receipt:
	// (if the admin is logged in, this function will also provide a 'new user INSERT' receipt)
	function showUserData($userID, $userAction, $connection)
	{
		global $HeaderString;
		global $viewType;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $loginEmail;
		global $adminLoginEmail;
		global $officialDatabaseName;

		// CONSTRUCT SQL QUERY:
		$query = "SELECT * FROM users WHERE user_id = $userID";

		// (3) RUN the query on the database through the connection:
		$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		// (4) EXTRACT results (since 'user_id' is the unique primary key for the 'users' table, there will be only one matching row)
		$row = @ mysql_fetch_array($result);

		// Build the correct header message:
		if (!isset($_SESSION['HeaderString'])) // if there's no saved message
			if ($userAction == "Delete") // provide an appropriate header message:
				$HeaderString = "<b><span class=\"warning\">Delete user</span> " . htmlentities($row["first_name"]) . " " . htmlentities($row["last_name"]) . " (" . $row["email"] . ")</b>:";
			else // provide the default message:
				$HeaderString = "Account details for <b>" . htmlentities($row["first_name"]) . " " . htmlentities($row["last_name"]) . " (" . $row["email"] . ")</b>:";
		else
		{
			$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

			// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
			deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
		}

		// Call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
		displayHTMLhead(htmlentities($officialDatabaseName) . " -- User Receipt", "noindex,nofollow", "Receipt page confirming correct entry of user details for the " . htmlentities($officialDatabaseName), "", false, "", $viewType);
		showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

		// Start a table:
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays user account details\">";

		if (mysql_num_rows($result) == 1) // If there's a user associated with this user ID
		{
			// Display a password reminder:
			// (but only if a normal user is logged in -OR- the admin is logged in AND the updated user data are his own!)
			if (($loginEmail != $adminLoginEmail) | (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
				echo "\n<tr>\n\t<td><i>Please record your password somewhere safe for future use!</i></td>\n</tr>";
	
			// Print title, first name, last name and institutional abbreviation:
			echo "\n<tr>\n\t<td>\n\t\t";
			if (!empty($row["title"]))
				echo $row["title"] . ". ";
			echo htmlentities($row["first_name"]) . " " . htmlentities($row["last_name"]) . " (" . htmlentities($row["abbrev_institution"]) . ")"; // Since the first name, last name and abbrev. institution fields are mandatory, we don't need to check if they're empty
	
			// Print institution name:
			if (!empty($row["institution"]))
				echo "\n\t\t<br>\n\t\t" . htmlentities($row["institution"]);
	
			// Print corporate institution name:
			if (!empty($row["corporate_institution"]))
				echo "\n\t\t<br>\n\t\t" . htmlentities($row["corporate_institution"]);
	
			// If any of the address lines contain data, add a spacer row:
			if (!empty($row["address_line_1"]) || !empty($row["address_line_2"]) || !empty($row["address_line_3"]) || !empty($row["zip_code"]) || !empty($row["city"]) || !empty($row["state"]) || !empty($row["country"]))
				echo "\n\t\t<br>";
	
			// Print first address line:
			if (!empty($row["address_line_1"]))
				echo "\n\t\t<br>\n\t\t" . htmlentities($row["address_line_1"]);
	
			// Print second address line:
			if (!empty($row["address_line_2"]))
				echo "\n\t\t<br>\n\t\t" . htmlentities($row["address_line_2"]);
	
			// Print third address line:
			if (!empty($row["address_line_3"]))
				echo "\n\t\t<br>\n\t\t" . htmlentities($row["address_line_3"]);
	
			// Print zip code and city:
			if (!empty($row["zip_code"]) && !empty($row["city"])) // both fields are available
				echo "\n\t\t<br>\n\t\t" . htmlentities($row["zip_code"]) . " " . htmlentities($row["city"]);
			elseif (!empty($row["zip_code"]) && empty($row["city"])) // only 'zip_code' available
				echo "\n\t\t<br>\n\t\t" . htmlentities($row["zip_code"]);
			elseif (empty($row["zip_code"]) && !empty($row["city"])) // only 'city' field available
				echo "\n\t\t<br>\n\t\t" . htmlentities($row["city"]);
	
			// Print state:
			if (!empty($row["state"]))
				echo "\n\t\t<br>\n\t\t" . htmlentities($row["state"]);
	
			// Print country:
			if (!empty($row["country"]))
				echo "\n\t\t<br>\n\t\t" . htmlentities($row["country"]);
	
			// If any of the phone/url/email fields contain data, add a spacer row:
			if (!empty($row["phone"]) || !empty($row["url"]) || !empty($row["email"]))
				echo "\n\t\t<br>";
	
			// Print phone number:
			if (!empty($row["phone"]))
				echo "\n\t\t<br>\n\t\t" . "Phone: " . htmlentities($row["phone"]);
	
			// Print URL:
			if (!empty($row["url"]))
				echo "\n\t\t<br>\n\t\t" . "URL: <a href=\"" . $row["url"] . "\">" . $row["url"] . "</a>";
	
			// Print email:
				echo "\n\t\t<br>\n\t\t" . "Email: <a href=\"mailto:" . $row["email"] . "\">" . $row["email"] . "</a>"; // Since the email field is mandatory, we don't need to check if it's empty

			echo "\n\t</td>\n</tr>";

			// If the admin is logged in, display a button that will allow to edit/delete the currently shown user:
			if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail)) // ('$adminLoginEmail' is specified in 'ini.inc.php')
			{
				if ($userAction == "Delete")
					echo "\n<tr>\n\t<td>"
						. "\n\t\t<form action=\"user_removal.php\" method=\"POST\">"
						. "\n\t\t\t<input type=\"hidden\" name=\"userID\" value=\"" . $userID . "\">"
						. "\n\t\t\t<input type=\"submit\" value=\"" . $userAction . " User\">"
						. "\n\t\t</form>"
						. "\n\t</td>\n</tr>";
				else
					echo "\n<tr>\n\t<td>"
						. "\n\t\t<form action=\"user_details.php\" method=\"POST\">"
						. "\n\t\t\t<input type=\"hidden\" name=\"userID\" value=\"" . $userID . "\">"
						. "\n\t\t\t<input type=\"submit\" value=\"" . $userAction . " User\">"
						. "\n\t\t</form>"
						. "\n\t</td>\n</tr>";
			}
		}
		else // no user exists with this user ID
			echo "\n<tr>\n\t<td>(No user exists with this user ID!)</td>\n</tr>";

		echo "\n</table>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc.php')
	displayfooter("");

	// --------------------------------------------------------------------
?>

</body>
</html>
