<?php
	// This file contains functions
	// that are shared between all
	// scripts

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password
	include "ini.inc.php"; // include common variables

	// --------------------------------------------------------------------

	// Untaint user data:
	function clean($input, $maxlength)
	{
		$input = substr($input, 0, $maxlength);
		$input = EscapeShellCmd($input);
		return ($input);
	}

	// --------------------------------------------------------------------

	// Show error (prepares error output and redirects it to 'error.php' which displays the error message):
	function showErrorMsg($headerMsg, $oldQuery)
	{
		$errorNo = mysql_errno();
		$errorMsg = mysql_error();
		header("Location: error.php?errorNo=" . $errorNo . "&errorMsg=" . rawurlencode($errorMsg) . "&headerMsg=" . rawurlencode($headerMsg) . "&oldQuery=" . rawurlencode($oldQuery));
		exit;
	}

	// --------------------------------------------------------------------

	// Show whether the user is logged in or not:
	function showLogin()
	{
		global $loginEmail;
		global $loginWelcomeMsg;
		global $loginFirstName;
		global $loginLastName;
		global $loginStatus;
		global $loginLinks;
		global $loginUserID;
		global $adminLoginEmail; // ('$adminLoginEmail' is specified in 'ini.inc.php')
		
		$referer = $_SERVER["REQUEST_URI"];

		if (!session_is_registered("loginUserID"))
			session_register("loginUserID");

		if (!session_is_registered("loginStatus"))
			session_register("loginStatus");

		if (!session_is_registered("loginLinks"))
			session_register("loginLinks");

		// Is the user logged in?
		if (session_is_registered("loginEmail"))
			{
				$loginWelcomeMsg = "Welcome<br><em>" . htmlentities($loginFirstName) . " " . htmlentities($loginLastName) . "</em>!";

				if ($loginEmail == $adminLoginEmail)
					$loginStatus = "You're logged in as<br><span class=\"warning\">Admin</span> (<em>" . $loginEmail . "</em>)";
				else
					$loginStatus = "You're logged in as<br><em>" . $loginEmail . "</em>";

				$loginLinks = "";
				if ($loginEmail == $adminLoginEmail) // if the admin is logged in, add an 'Add User' link:
					$loginLinks .= "<a href=\"user_details.php\">Add User</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				$loginLinks .= "<a href=\"user_details.php?userID=" . $loginUserID . "\">Change Details</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
				$loginLinks .= "<a href=\"user_logout.php\">Logout</a>";
			}
		else
			{
				$loginWelcomeMsg = "";
				$loginStatus = "";
				$loginLinks = "<a href=\"user_login.php?referer=" . rawurlencode($referer) . "\">Login</a>";
			}
	}

	// --------------------------------------------------------------------

	// Get the 'user_id' for the record entry in table 'auth' whose email matches that in 'loginEmail':
	function getUserID($loginEmail, $connection)
	{
		global $hostName;
		global $username;
		global $password;
		global $databaseName;

		$open = false;

		// If a connection parameter is not passed, then use our own connection to avoid any locking problems
		if (!isset($connection))
		{
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

			$open = true;
		}

		// CONSTRUCT SQL QUERY:
		// We find the user_id through the 'users' table, using the session variable holding their 'loginEmail'.
		$query = "SELECT user_id FROM auth WHERE email = '$loginEmail'";

		// (3) RUN the query on the database through the connection:
		if ($result = @ mysql_query ($query, $connection))
			$row = mysql_fetch_array($result);
		else
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

		if ($open == true)
		{
			// (5) CLOSE the database connection:
			if (!(mysql_close($connection)))
				if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
					showErrorMsg("The following error occurred while trying to disconnect from the database:", "");
		}

		return($row["user_id"]);
	}

	// --------------------------------------------------------------------

	// Generic function that provides email sending capability:
	function sendEmail($emailRecipient, $emailSubject, $emailBody)
	{
		global $adminLoginEmail; // ('$adminLoginEmail' is specified in 'ini.inc.php')

		// Setup some additional headers:
		$emailHeaders = "From: " . $adminLoginEmail . "\n"
						. "Return-Path: " . $adminLoginEmail . "\n"
						. "X-Sender: " . $adminLoginEmail . "\n"
						. "X-Mailer: PHP\n"
						. "X-Priority: 3";

		// Send the email:
		mail($emailRecipient, $emailSubject, $emailBody, $emailHeaders);
	}

	// --------------------------------------------------------------------
?>
