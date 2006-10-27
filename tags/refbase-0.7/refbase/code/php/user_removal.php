<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./user_removal.php
	// Created:    16-Apr-02, 10:54 Uhr
	// Modified:   01-Jul-03, 0:44 Uhr

	// This script deletes a user from the 'users' and 'auth' tables.
	// The script can be only called by the admin. If the removal succeeds, it redirects to 'users.php'.
	// Note that there's no further verification! If you clicked 'Delete User' on 'user_receipt.php' the user will be killed immediately.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password
	include 'include.inc'; // include common functions
	include "ini.inc.php"; // include common variables

	// --------------------------------------------------------------------

	// Initialize a session
	session_start();

	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!
	
	// Check if the admin is logged in
	if (!(session_is_registered("loginEmail") && ($loginEmail == $adminLoginEmail))) // ('$adminLoginEmail' is specified in 'ini.inc.php')
	{
		session_register("HeaderString"); // save an error message
		$HeaderString = "<b><span class=\"warning\">You must be logged in as admin to remove any users!</span></b>";

		session_register("referer"); // save the URL of the currently displayed page
		$referer = $HTTP_REFERER;

		header("Location: index.php");
		exit;
	}

	// Check the correct parameters have been passed
	if ($userID == "")
	{
		session_register("HeaderString"); // save an error message
		$HeaderString = "<b><span class=\"warning\">Incorrect parameters to script 'user_removal.php'!</span></b>";

		// Redirect the browser back to the calling page
		header("Location: index.php"); // Note: if 'header("Location: $HTTP_REFERER")' is used, the error message won't get displayed! ?:-/
		exit;
	}

	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:
	// If the admin is logged in:
	if (session_is_registered("loginEmail") && ($loginEmail == $adminLoginEmail)) // -> perform a delete action:
	{
		// DELETE - construct a query to delete the relevant record
		// ... from the users table:
		$query = "DELETE FROM users WHERE user_id = $userID";

		// ... from the auth table:
		$query2 = "DELETE FROM auth WHERE user_id = $userID";
	}

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

	// (3a) RUN the first query on the database through the connection:
	if (!($result = @ mysql_query($query, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

	// (3b) RUN the second query on the database through the connection:
	if (!($result = @ mysql_query($query2, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("Your query:\n<br>\n<br>\n<code>$query2</code>\n<br>\n<br>\n caused the following error:", "");

	// ----------------------------------------------

	// (4) File a message and go back to the list of users:
	session_register("HeaderString"); // save an informative message
	$HeaderString = "User was deleted successfully!";

	header("Location: users.php"); // re-direct to the list of users

	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to disconnect from the database:", "");

	// --------------------------------------------------------------------
?>
