<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./index.php
	// Created:    29-Jul-02, 16:45
	// Modified:   25-Nov-03, 21:56

	// This script builds the main page.
	// It provides login and quick search forms
	// as well as links to various search forms.

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

	// Connect to a session
	session_start();
	
	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!

	// --------------------------------------------------------------------

	// If there's no stored message available:
	if (!session_is_registered("HeaderString"))
		$HeaderString = "Welcome! This database provides access to " . htmlentities($scientificFieldDescriptor) . " literature."; // Provide the default welcome message
	else
		session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

	// CONSTRUCT SQL QUERY:
	$query = "SELECT COUNT(serial) FROM refs"; // query the total number of records

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (4) DISPLAY HEADER, (5) CLOSE CONNECTION

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

	// (3a) RUN the query on the database through the connection:
	if (!($result = @ mysql_query ($query, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to query the database:", "");

	// (3b) EXTRACT results:
	$row = mysql_fetch_row($result); //fetch the current row into the array $row (it'll be always *one* row, but anyhow)
	$recordCount = $row[0]; // extract the contents of the first (and only) row

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// (4) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- Home", "index,follow", "Search the " . htmlentities($officialDatabaseName), "", false, "");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to disconnect from the database:", "");

	// --------------------------------------------------------------------
?>
<table align="center" border="0" cellpadding="2" cellspacing="5" width="90%" summary="This table explains features, goals and usage of the <? echo htmlentities($officialDatabaseName); ?>">
	<tr>
		<td colspan="2"><h3>Goals &amp; Features</h3></td>
		<td width="163" valign="bottom"><?php
if (!session_is_registered("loginEmail"))
	{
?><div class="header"><b>Login:</b></div><?php
	}
else
	{
?><div class="header"><b>Show My Refs:</b></div><?php
	}
?></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>This web database is an attempt to provide a comprehensive and platform-independent literature resource for scientists working in the field of <? echo htmlentities($scientificFieldDescriptor); ?> sciences.
			<br>
			<br>
			This database offers:
			<ul type="circle">
				<li>a comprehensive dataset on <? echo htmlentities($scientificFieldDescriptor); ?> literature<?php
	// report the total number of records:
	echo ", currently featuring " . $recordCount . " records";
?></li>
				<li>a clean &amp; standardized interface</li>
				<li>a multitude of search options, including both, simple &amp; advanced as well as powerful SQL search options</li>
				<li>various display &amp; export options</li>
				<li><a href="import_csa.php">import</a> of full records from Cambridge Scientific Abstracts</li>
			</ul>
		</td>
		<td width="163" valign="top">
<?php
if (!session_is_registered("loginEmail"))
	{
?>
			<form action="user_login.php" method="POST">
				Email Address:
				<br>
				<input type="text" name="loginEmail" size="12">
				<br>
				Password:
				<br>
				<input type="password" name="loginPassword" size="12">
				<br>
				<input type="submit" value="Login">
			</form><?php
	}
else
	{
?>
			<form action="search.php" method="POST">
				<input type="hidden" name="formType" value="myRefsSearch">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<input type="radio" name="myRefsRadio" value="1" checked>&nbsp;All
				<br>
				<input type="radio" name="myRefsRadio" value="0">&nbsp;Only:
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findMarked" value="1">
				<select name="markedSelector">
					<option>marked</option>
					<option>not marked</option>
				</select>
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findSelected" value="1">
				<select name="selectedSelector">
					<option>selected</option>
					<option>not selected</option>
				</select>
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findCopy" value="1">&nbsp;copy:
				<select name="copySelector">
					<option>true</option>
					<option>fetch</option>
					<option>ordered</option>
					<option>false</option>
				</select>
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findUserKeys" value="1">&nbsp;key:&nbsp;&nbsp;
				<input type="text" name="userKeysName" size="7">
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findUserNotes" value="1">&nbsp;note:&nbsp;
				<input type="text" name="userNotesName" size="7">
				<br>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" name="findUserFile" value="1">&nbsp;file:&nbsp;&nbsp;&nbsp;
				<input type="text" name="userFileName" size="7">
				<br>
				<input type="submit" value="Show">
			</form><?php
	}
?>
		</td>
	</tr>
	<tr>
		<td colspan="2"><h3>Search</h3></td>
		<td width="163" valign="bottom"><div class="header"><b>Quick Search:</b></div></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>Search the literature database:
			<ul type="circle">
				<li><a href="simple_search.php">Simple Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search the main fields of the database</li>
				<li><a href="advanced_search.php">Advanced Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search all fields of the database</li>
				<li><a href="sql_search.php">SQL Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search the database by use of a SQL query</li>
				<li><a href="library_search.php">Library Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search the library of the <? echo htmlentities($hostInstitutionName); ?></li>
			</ul>
			<br>
			Or, alternatively:
<?php
	// Get the current year & date in order to include them into query URLs:
	$CurrentYear = date('Y');
	$CurrentDate = date('Y-m-d');
	// We'll also need yesterday's date for inclusion into query URLs:
	$TimeStampYesterday = mktime(0, 0, 0, date('m'), (date('d') - 1), date('Y'));
	$DateYesterday = date('Y-m-d', $TimeStampYesterday);
	// Plus, we'll calculate the date that's a week ago (again, for inclusion into query URLs):
	$TimeStampYesterday = mktime(0, 0, 0, date('m'), (date('d') - 7), date('Y'));
	$DateLastWeek = date('Y-m-d', $TimeStampYesterday);

	echo "\t\t\t<ul type=\"circle\">";
	echo "\n\t\t\t\t<li>view all database entries that were:";
	echo "\n\t\t\t\t\t<ul type=\"circle\">";
	echo "\n\t\t\t\t\t\t<li>added: <a href=\"show.php?date=" . $CurrentDate . "\">today</a> | <a href=\"show.php?date=" . $DateYesterday . "\">yesterday</a> | <a href=\"show.php?date=" . $DateLastWeek . "&amp;range=after\">last 7 days</a></li>";
	echo "\n\t\t\t\t\t\t<li>edited: <a href=\"show.php?date=" . $CurrentDate . "&amp;when=edited\">today</a> | <a href=\"show.php?date=" . $DateYesterday . "&amp;when=edited\">yesterday</a> | <a href=\"show.php?date=" . $DateLastWeek . "&amp;when=edited&amp;range=after\">last 7 days</a></li>";
	echo "\n\t\t\t\t\t\t<li>published in: <a href=\"show.php?year=" . $CurrentYear . "\">" . $CurrentYear . "</a> | <a href=\"show.php?year=" . ($CurrentYear - 1) . "\">" . ($CurrentYear - 1) . "</a> | <a href=\"show.php?year=" . ($CurrentYear - 2) . "\">" . ($CurrentYear - 2) . "</a> | <a href=\"show.php?year=" . ($CurrentYear - 3) . "\">" . ($CurrentYear - 3) . "</a></li>";
	echo "\n\t\t\t\t\t</ul>";
	echo "\n\t\t\t\t\t<br>";
	echo "\n\t\t\t\t</li>";
	echo "\n\t\t\t\t<li><a href=\"extract.php\">extract literature</a> cited within a text and build an appropriate reference list</li>";
	echo "\n\t\t\t\t<li><a href=\"show.php\">display details</a> for a particular record by entering its database serial number</li>";
	echo "\n\t\t\t</ul>\n";
?>
		</td>
		<td width="163" valign="top">
			<form action="search.php" method="POST">
				<input type="hidden" name="formType" value="quickSearch">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<select name="quickSearchSelector">
					<option selected>author</option>
					<option>title</option>
					<option>year</option>
					<option>keywords</option>
					<option>abstract</option>
				</select>
				<br>
				<input type="text" name="quickSearchName" size="12">
				<br>
				<input type="submit" value="Search">
			</form>
		</td>
	</tr>
	<tr>
		<td colspan="3"><h3>About</h3></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>This literature database is maintained by the <a href="<? echo $hostInstitutionURL; ?>"><? echo htmlentities($hostInstitutionName); ?></a> (<? echo htmlentities($hostInstitutionAbbrevName); ?>). You're welcome to send any questions or suggestions to our <a href="mailto:<? echo $feedbackEmail; ?>">feedback</a> address. The database is powered by <a href="http://www.refbase.net">refbase</a>, an open source database front-end for managing scientific literature &amp; citations.</td>
		<td width="163" valign="top"><a href="http://www.refbase.net/"><img src="img/refbase_credit.gif" alt="powered by refbase" width="80" height="44" hspace="0" border="0"></a></td>
	</tr>
</table><?php
	// --------------------------------------------------------------------

	//	DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter("");

	// --------------------------------------------------------------------
?>
</body>
</html> 
