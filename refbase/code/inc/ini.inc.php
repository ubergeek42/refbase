<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./ini.inc.php
	// Created:    12-Jan-03, 17:58
	// Modified:   30-Jun-03, 15:16

	// This is the customization include file.
	// It contains variables that are common to all scripts and whose values can/should be customized.
	// I.e., you can adjust their values as needed but you must not change the variable names themselves!

	// --------------------------------------------------------------------

	$officialDatabaseName = "INSTITUTION_NAME Literature Database"; // the official name of this literature database
																	// e.g. "IPOE Literature Database"

	$databaseBaseURL = "http://YOUR_SERVER_ADDRESS/PATH_ON_SERVER/"; // the base url for this literature database (i.e., the URL to the root directory)
																	// (IMPORTANT: the base url MUST end with a slash!)
																	// e.g. "http://polaris.ipoe.uni-kiel.de/refs/"

	$addNewUsers = "everyone"; // specifies who'll be allowed to add a new user to the users table (possible values are: "everyone", "admin")
								// VERY IMPORTANT NOTE: the value is set to "everyone" by default so that you'll be able to setup the very first user!
								// Its best to set up the admin as the first user (and specify his email address below!), then change the value of
								// $addNewUsers to "admin". By that you prevent other users to be able to mess with your users table.

	$adminLoginEmail = "ADMIN_EMAIL_ADDRESS"; // the admin email address (by which a user is granted admin status after successful login!)
											// e.g. "admin@ipoe.uni-kiel.de"

	$feedbackEmail = "FEEDBACK_EMAIL_ADDRESS"; // the feedback email address to which any support questions or suggestions should be sent
												// e.g. "ipoelit@ipoe.uni-kiel.de"

	$hostInstitutionName = "INSTITUTION_FULL_NAME"; // the full name of the institution hosting this literature database
													// e.g. "Institute for Polar Ecology"

	$hostInstitutionAbbrevName = "INSTITUTIONAL_ABBREVIATION"; // the abbreviated name of the institution hosting this literature database
																// e.g. "IPOE"

	$hostInstitutionURL = "INSTITUTION_WEB_ADDRESS"; // the URL of the institution hosting this literature database
													// e.g. "http://www.uni-kiel.de/ipoe/"

	// --------------------------------------------------------------------
?>
