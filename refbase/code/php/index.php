<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>IP&Ouml; Literature Database -- Home</title>
	<meta name="date" content=<?php echo "\"" . date("d-M-y") . "\""; ?>>
	<meta name="robots" content="index,follow">
	<meta name="description" lang="en" content="Search the IP&Ouml; Literature Database">
	<meta name="keywords" lang="en" content="search citation web database polar marine science literature references mysql php">
	<meta http-equiv="content-language" content="en">
	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1">
	<meta http-equiv="Content-Style-Type" content="text/css">
	<link rel="stylesheet" href="style.css" type="text/css" title="CSS Definition">
</head>
<body>
<?php
	// The main page

	// This is included to hide the username and password:
	include 'db.inc';
	include 'error.inc';
	include 'header.inc';
	include 'footer.inc';

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
	// --------------------------------------------------------------------

	// CONSTRUCT SQL QUERY:
	$query = "SELECT COUNT(serial) FROM refs"; // query the total number of records

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (4) DISPLAY HEADER, (5) CLOSE CONNECTION

	// (1) OPEN the database connection:
	//      (variables are set by include file 'db.inc'!)
	if (!($connection = @ mysql_connect($hostName, $username, $password)))
	{
		showheader($result, "The following error occurred while trying to connect to the host:");
		showerror();
	}

	// (2) SELECT the database:
	//      (variables are set by include file 'db.inc'!)
	if (!(mysql_select_db($databaseName, $connection)))
	{
		showheader($result, "The following error occurred while trying to connect to the database:");
		showerror();
	}

	// (3a) RUN the query on the database through the connection:
	if (!($result = @ mysql_query ($query, $connection)))
	{
		showheader($result, "The following error occurred while trying to query the database:");
		showerror();
	}

	// (3b) EXTRACT results:
	$row = mysql_fetch_row($result); //fetch the current row into the array $row (it'll be always *one* row, but anyhow)
	$recordCount = $row[0]; // extract the contents of the first (and only) row

	// (4) DISPLAY header:
	// call the 'showheader()' function:
	showheader($result, "Welcome! This database provides access to polar &amp; marine literature.");

	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
	{
		showheader($result, "The following error occurred while trying to disconnect from the database:");
		showerror();
	}

	// --------------------------------------------------------------------

	//	BUILD THE HTML HEADER:
	function showheader($result, $HeaderString)
	{
		// call the 'displayheader()' function from 'header.inc'):
		displayheader();

		// finalize header containing the appropriate header string:
		echo "<tr>\n\t<td>&nbsp;</td>"
			. "\n\t<td colspan=\"2\">$HeaderString</td>"
			. "\n</tr>"
			. "\n<tr align=\"center\">\n\t<td colspan=\"3\">&nbsp;</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n<hr align=\"center\" width=\"80%\">";
	}

	// --------------------------------------------------------------------
?>

<table align="center" border="0" cellpadding="0" cellspacing="5" width="75%" summary="This table explains features, goals and usage of the IP&Ouml; literature database">
	<tr>
		<td colspan="2"><h3>Goals &amp; Features</h3></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>This web database is an attempt to provide a comprehensive and platform-independent literature resource for scientists working in the field of polar &amp; marine sciences.
			<br>
			<br>
			This database offers:
			<ul type="circle">
				<li>a comprehensive dataset on polar &amp; marine literature<?php
	// report the total number of records:
	echo ", currently featuring " . $recordCount . " records";
?></li>
				<li>a clean &amp; standardized interface</li>
				<li>a multitude of search options, including both, simple &amp; advanced as well as powerful SQL search options</li>
				<li>various display &amp; export options</li>
			</ul>
		</td>
	</tr>
	<tr>
		<td colspan="2"><h3>Search</h3></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>Search the literature database:
			<ul type="circle">
				<li><a href="simple_search.php">Simple Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search the main fields of the database</li>
				<li><a href="advanced_search.php">Advanced Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search all fields of the database</li>
				<li><a href="sql_search.php">SQL Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search the database by use of a SQL query</li>
				<li><a href="library_search.php">Library Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search the library of the Institut f&uuml;r Polar&ouml;kologie</li>
			</ul>
			<br>
			Or, alternatively:
<?php
	// Get the current year in order to include it into the query URL:
	$CurrentYear = date(Y);
	echo "\t\t\t<ul type=\"circle\">\n";
	echo "\t\t\t\t<li>view the 10 database entries that were <a href=\"search.php?sqlQuery=SELECT+serial%2C+author%2C+title%2C+year%2C+publication%2C+volume+FROM+refs+ORDER+BY+serial+DESC+LIMIT+10&amp;showQuery=0&amp;showLinks=1&amp;formType=sqlSearch&amp;showRows=10\">added most recently</a>.</li>";
	echo "\n\t\t\t\t<li>view all database entries that were <a href=\"search.php?sqlQuery=SELECT+author%2C+title%2C+year%2C+publication%2C+volume+FROM+refs+WHERE+year+%3D+$CurrentYear+ORDER+BY+author%2C+publication%2C+volume&amp;showQuery0=&amp;showLinks=1&amp;formType=sqlSearch&amp;showRows=20\">published in $CurrentYear</a>.</li>";
	echo "\n\t\t\t\t<li><a href=\"extract.php\">extract literature</a> cited within a text and build an appropriate reference list.</li>";
	echo "\n\t\t\t</ul>\n";
?>
		</td>
	</tr>
	<tr>
		<td colspan="2"><h3>About</h3></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>This literature database is maintained by the <a href="http://www.uni-kiel.de/ipoe/">Institut f&uuml;r Polar&ouml;kologie</a> (IP&Ouml;), Kiel. You're welcome to send any questions or suggestions to our <a href="mailto:&#105;&#112;&#111;&#101;&#108;&#105;&#116;&#64;&#105;&#112;&#111;&#101;&#46;&#117;&#110;&#105;&#45;&#107;&#105;&#101;&#108;&#46;&#100;&#101;">feedback</a> address. The database is powered by <a href="http://www.refbase.net">refbase</a>, an open source database front-end for managing scientific literature &amp; citations that was initiated at IP&Ouml;.</td>
	</tr>
</table><?php
	// --------------------------------------------------------------------

	//	DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter();

	// --------------------------------------------------------------------
?>
</body>
</html> 
