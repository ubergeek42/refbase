<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./install.php
	// Created:    07-Jan-04, 22:00
	// Modified:   10-Jan-04, 02:28

	// This file will install the literature database for you. Note that you must have
	// an existing PHP and MySQL installation. Please see the readme for further information.
	// CAUTION: YOU MUST REMOVE THIS SCRIPT FROM YOUR WEB DIRECTORY AFTER INSTALLATION!!

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

	// Extract any parameters passed to the script:
	$adminDatabaseName = $_POST['adminDatabaseName'];
	$adminUserName = $_POST['adminUserName'];
	$adminPassword = $_POST['adminPassword'];
	$pathToMYSQL = $_POST['pathToMYSQL'];
	$databaseStructureFile = $_POST['databaseStructureFile'];

	// --------------------------------------------------------------------

	// Initialize the session
	session_start();

	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!

	// Register an error array - just in case!
	if (!session_is_registered("errors"))
		session_register("errors");

	// Clear any errors that might have been found previously:
	$errors = array();

	// --------------------------------------------------------------------

	// Check the correct parameters have been passed:
	if (empty($adminDatabaseName) OR empty($adminUserName) OR empty($adminPassword) AND empty($pathToMYSQL) OR empty($databaseStructureFile))
	{
		// if 'installation.php' was called without any valid parameters:
		//Display an installation form:

		// If there's no stored message available:
		if (!session_is_registered("HeaderString"))
			$HeaderString = "To install the refbase package please fill out the form below and click the 'Install' button:"; // Provide the default message
		else
			session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

		// Show the login status:
		showLogin(); // (function 'showLogin()' is defined in 'include.inc')

		// DISPLAY header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
		displayHTMLhead(htmlentities($officialDatabaseName) . " -- Installation", "index,follow", "Installation form for the " . htmlentities($officialDatabaseName), "", false, "");
		showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

		// Start <form> and <table> holding the form elements:
		?>
		<form action="install.php" method="POST">
		<input type="hidden" name="formType" value="install">
		<input type="hidden" name="submit" value="Install"><?php // provide a default value for the 'submit' form tag. Otherwise, some browsers may not recognize the correct output format when a user hits <enter> within a form field (instead of clicking the "Show" button) ?>
		<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds the installation form">
			<tr>
				<td colspan="3"><h3>refbase Installation</h3></td>
			</tr>
			<tr>
				<td width="185" valign="top"><b>Important Note:</b></td>
				<td width="10">&nbsp;</td>
				<td>Before executing this script, you'll need to <span class="warning">open the include file <em>db.inc</em></span> in a text editor and edit the values of the variables <em>$databaseName</em>, <em>$username</em> and <em>$password</em> to suit your setup! Then, proceed with this form:</td>
			</tr>
			<tr>
				<td valign="top"><b>MySQL Admin Database:</b></td>
				<td>&nbsp;</td>
				<td><input type="text" name="adminDatabaseName" value="mysql" size="40"></td>
			</tr>
			<tr>
				<td valign="top"><b>MySQL Admin User:</b></td>
				<td>&nbsp;</td>
				<td><input type="text" name="adminUserName" value="root" size="40"></td>
			</tr>
			<tr>
				<td valign="top"><b>MySQL Admin Password:</b></td>
				<td>&nbsp;</td>
				<td><input type="password" name="adminPassword" size="40"></td>
			</tr>
			<tr>
				<td valign="top"><b>Path to the MySQL application:</b></td>
				<td>&nbsp;</td>
				<td><input type="text" name="pathToMYSQL" value="/usr/local/mysql/bin/mysql" size="40"></td>
			</tr>
			<tr>
				<td valign="top"><b>Path to the database structure file:</b></td>
				<td>&nbsp;</td>
				<td><input type="text" name="databaseStructureFile" value="./install.sql" size="40"></td>
			</tr>
			<tr>
				<td valign="top">&nbsp;</td>
				<td>&nbsp;</td>
				<td><input type="submit" name="submit" value="Install"></td>
			</tr>
		</table>
		</form><?php

		// --------------------------------------------------------------------

		// DISPLAY THE HTML FOOTER:
		// call the 'displayfooter()' function from 'footer.inc')
		displayfooter("");

		// --------------------------------------------------------------------

		?>
		</body>
		</html><?php
	}
	else // the correct parameters have been passed, so we can proceed with the actual installation procedure:
	{

		// --------------------------------------------------------------------

		// Build the database queries required for installation:
		$queryGrantStatement = "GRANT SELECT,INSERT,UPDATE,DELETE ON " . $databaseName . ".* TO " . $username . "@" . $hostName . " IDENTIFIED BY '" . $password . "'";

		$queryCreateDB = "CREATE DATABASE IF NOT EXISTS " . $databaseName;

		// --------------------------------------------------------------------

		// (1) Open the database connection and use the mysql database:
		if (!($connection = @ mysql_connect($hostName,$adminUserName,$adminPassword)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to connect to the host:", "");

		if (!(mysql_select_db($adminDatabaseName, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to connect to the database:", "");

		// (2) Run the install queries on the mysql database through the connection:
		if (!($result = @ mysql_query ($queryGrantStatement, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to query the database:", "");

		if (!($result = @ mysql_query ($queryCreateDB, $connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to query the database:", "");

		// (5) Close the database connection:
		if (!(mysql_close($connection)))
			if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
				showErrorMsg("The following error occurred while trying to disconnect from the database:", "");


		// Import the literature database structure from file:
		exec($pathToMYSQL . " -h " . $hostName . " -u " . $adminUserName . " -p" . $adminPassword . " --database=" . $databaseName . " < " . $databaseStructureFile);

		// --------------------------------------------------------------------

		//Provide a feedback page:

		// If there's no stored message available:
		if (!session_is_registered("HeaderString"))
			$HeaderString = "Installation of the Web Reference Database was successfull!"; // Provide the default message
		else
			session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

		// Show the login status:
		showLogin(); // (function 'showLogin()' is defined in 'include.inc')

		// DISPLAY header:
		// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
		displayHTMLhead(htmlentities($officialDatabaseName) . " -- Installation Feedback", "index,follow", "Installation feedback for the " . htmlentities($officialDatabaseName), "", false, "");
		showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

		// Start <form> and <table> holding the form elements:
		?>
		<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds the installation feedback info">
			<tr>
				<td colspan="2"><h3>Welcome to refbase!</h3></td>
			</tr>
			<tr>
				<td valign="top"><b>Important Note:</b></td>
				<td>
					The <em>install.php</em> script is only provided for installation purposes and is not needed anymore. Due to security considerations you should <span class="warning">remove this script</span> from your web directory NOW!!
				</td>
			</tr>
			<tr>
				<td valign="top"><b>Setup users:</b></td>
				<td>
					Please setup the admin user account for your newly created literature database:
					<ul type="circle">
						<li>Goto <a href="index.php" target="_blank" title="Open the main page in a new window"><?php echo htmlentities($officialDatabaseName); ?></a></li>
						<li>Login with email address = <em>user@refbase.net</em> &amp; password = <em>start</em></li>
						<li>Click on <em>Add User</em> and enter the name, institutional abbreviation, email address and password of the admin user</li>
						<li>Open the file <em>ini.inc.php</em> in a text editor and change the value of the <em>$adminLoginEmail</em> variable to the email address you've specified for the admin user</li>
						<li>Log out, then login again using the email address and password of your newly created admin account</li>
						<li>You can now delete the initial user by choosing <em>Manage Users</em> and clicking the appropriate trash icon</li>
					</ul>
					If you want to add additional users use the <em>Add User</em> link and enter the user's name, institutional abbreviation, email address and password.
				</td>
			</tr>
			<tr>
				<td valign="top"><b>Configure refbase:</b></td>
				<td>
					In order to customize your literature database, please open again <em>ini.inc.php</em> in a text editor. This include file contains variables that are common to all scripts and whose values can/must be adopted to your needs. Please see the comments within the file for further information.
				</td>
			</tr>
		</table><?php

		// --------------------------------------------------------------------

		// DISPLAY THE HTML FOOTER:
		// call the 'displayfooter()' function from 'footer.inc')
		displayfooter("");

		// --------------------------------------------------------------------

		?>
		</body>
		</html> 
<?php
	}
?>