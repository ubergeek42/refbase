<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./db.inc
	// Created:    15-Oct-02, 19:11
	// Modified:   07-Sep-03, 12:13

	// This file holds crucial
	// database access information.
	// Please read the notes below!

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
	// NOTE: Edit the variables '$databaseName', $username and '$password' to suit your setup!
	// CAUTION: To avoid security risks you must not permit any remote user to view
	//          this file! E.g., this can be done by adjusting the config file of
	//          your server ("httpd.conf" in case of the apache web server) to
	//          disallow viewing of "*\.inc" files ("Deny from all").

?>
<?php
	$hostName = "localhost";
	$databaseName = "MYSQL_DATABASE_NAME_GOES_HERE";
	$username = "MYSQL_USER_NAME_GOES_HERE";
	$password = "MYSQL_PASSWORD_GOES_HERE";
?>
