<?php
	// This script shows the user a receipt for their user UPDATE or INSERT.
	// It carries out no database actions and can be bookmarked.
	// The user must be logged in to view it.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password
	include 'header.inc'; // include header
	include 'footer.inc'; // include footer
	include 'include.inc'; // include common functions
	include "ini.inc.php"; // include common variables

	// --------------------------------------------------------------------

	// Re-establish the existing session
	session_start();
	
	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!

	// Check if the user is logged in
	if (!session_is_registered("loginEmail") && ($userID != 0))
	{
		session_register("HeaderString"); // save an error message
		$HeaderString = "<b><span class=\"warning\">You must login to view your user account details!</span></b>";

		session_register("referer"); // save the URL of the currently displayed page
		$referer = $HTTP_REFERER;

		header("Location: user_login.php");
		exit;
	}

	// Check the correct parameters have been passed
	if (!isset($userID))
	{
		session_register("HeaderString"); // save an error message
		$HeaderString = "<b><span class=\"warning\">Incorrect parameters to script 'user_receipt.php'!</span></b>";

		// Redirect the browser back to the calling page
		header("Location: index.php"); // Note: if 'header("Location: $HTTP_REFERER")' is used, the error message won't get displayed! ?:-/
		exit;
	}

	// --------------------------------------------------------------------

 	if (session_is_registered("loginEmail") && ($loginEmail != $adminLoginEmail)) // ('$adminLoginEmail' is specified in 'ini.inc.php')
		// Check this user matches the userID (viewing user account details is only allowed to the admin)
		if ($userID != getUserID($loginEmail, NULL))
		{
			session_register("HeaderString"); // save an error message
			$HeaderString = "<b><span class=\"warning\">You can only view your own user receipt!<span></b>";
	
			$userID = getUserID($loginEmail, NULL); // re-establish the user's correct user_id
		}
//	else // if the admin is logged in he should be able to view account data of _other_ users...
//		$userID = $_REQUEST['userID']; // ...in this case we accept 'userID' from the GET/POST request (it got included as hidden form tag by 'user_details.php')

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (4) DISPLAY RECEIPT, (5) CLOSE CONNECTION

	// (1) OPEN the database connection:
	//      (variables are set by include file 'db.inc'!)
	if (!($connection = @ mysql_connect($hostName, $username, $password)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to connect to the host:", "");

	// (2) SELECT the database:
	//      (variables are set by include file 'db.inc'!)
	if (!(mysql_select_db($databaseName, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to connect to the database:", "");

	// ----------------------------------------------

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// Show the user confirmation:
	if ($userID == 0) // 'userID=0' is sent by 'user_validation.php' to indicate a NEW user who has successfully submitted 'user_details.php'
		showEmailConfirmation($userID);
	else
		showUserData($userID, $connection);

	// ----------------------------------------------

	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to disconnect from the database:", "");

	// --------------------------------------------------------------------

	// Show a new user a confirmation screen, confirming that the submitted user data have been correctly received:
	function showEmailConfirmation($userID)
	{
		global $HeaderString;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $loginEmail;
		global $adminLoginEmail;
		global $officialDatabaseName;

		// Build the correct header message:
		if (!session_is_registered("HeaderString"))
			$HeaderString = "Submission confirmation:"; // provide the default message
		else
			session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

		// Call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
		displayHTMLhead("IP&Ouml; Literature Database -- User Receipt", "noindex,nofollow", "Receipt page confirming correct submission of new user details to the IP&Ouml; Literature Database", "", false, "");
		showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks);

		$confirmationText = "Thanks for your interest in the " . $officialDatabaseName . "!"
					. "<br><br>The data you provided have been sent to our database admin."
					. "<br>We'll process your request and mail back to you as soon as we can!"
					. "<br><br>[Back to <a href=\"index.php\">Literature Database Home</a>]";

		// Start a table:
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays user submission feedback\">";

		echo "\n<tr>\n\t<td>" . $confirmationText . "</td>\n</tr>";

		echo "\n</table>";

	}

	// --------------------------------------------------------------------

	// Show the user an UPDATE receipt:
	// (if the admin is logged in, this function will also provide a 'new user INSERT' receipt)
	function showUserData($userID, $connection)
	{
		global $HeaderString;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;
		global $loginEmail;
		global $adminLoginEmail;

		// CONSTRUCT SQL QUERY:
		$query = "SELECT * FROM users WHERE user_id = $userID";

		// (3) RUN the query on the database through the connection:
		if (!($result = @ mysql_query($query, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

		// (4) EXTRACT results (since 'user_id' is the unique primary key for the 'users' table, there will be only one matching row)
		$row = @ mysql_fetch_array($result);

		// Build the correct header message:
		if (!session_is_registered("HeaderString"))
			$HeaderString = "Account details for <b>" . htmlentities($row["first_name"]) . " " . htmlentities($row["last_name"]) . " (" . $row["email"] . ")</b>:"; // provide the default message
		else
			session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

		// Call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
		displayHTMLhead("IP&Ouml; Literature Database -- User Receipt", "noindex,nofollow", "Receipt page confirming correct entry of user details for the IP&Ouml; Literature Database", "", false, "");
		showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks);

		// Start a table:
		echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table displays user account details\">";

		if (mysql_num_rows($result) == 1) // If there's a user associated with this user ID
		{
			// Display a password reminder:
			// (but only if a normal user is logged in -OR- the admin is logged in AND the updated user data are his own!)
			if (($loginEmail != $adminLoginEmail) | (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail, $connection))))
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

			// If the admin is logged in, display an 'Edit User Account' link:
		 	if (($loginEmail == $adminLoginEmail) && ($userID != getUserID($loginEmail, $connection)))
		 		echo "\n<tr>\n\t<td><a href=\"user_details.php?userID=" . $userID . "\">[Edit User Data]</a></td>\n</tr>";
		}
		else // no user exists with this user ID
			echo "\n<tr>\n\t<td>(No user exists with this user ID!)</td>\n</tr>";

		echo "\n</table>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter("");

	// --------------------------------------------------------------------
?>
</body>
</html>
