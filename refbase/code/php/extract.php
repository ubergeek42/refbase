<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>IP&Ouml; Literature Database -- Extract Cited Literature</title>
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
	// Search formular offering the extraction of literature cited within a text

	// This is included to hide the username and password:
	include 'header.inc';
	include 'footer.inc';

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
	// --------------------------------------------------------------------

	// (2a) Display header:
	showheader($result, "Extract literature cited within a text and build an appropriate reference list:");

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"extractSearch\">";
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"80%\" summary=\"This table holds the search form\">"
			. "\n<tr>\n\t<td width=\"58\" valign=\"top\"><b>Extract Citations From:</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td><textarea name=\"sourceText\" rows=\"6\" cols=\"60\">Paste your text here...</textarea></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Serial Delimiters:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Specify the character(s) that enclose record serial numbers:</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Start Delimiter:&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"startDelim\" value=\"{\" size=\"4\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;End Delimiter:&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"endDelim\" value=\"}\" size=\"4\"></td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Output Options:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">Show&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"showRows\" value=\"100\" size=\"4\">&nbsp;&nbsp;&nbsp;records per page</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<input type=\"submit\" name=\"submit\" value=\"Export\">&nbsp;&nbsp;&nbsp;"
			. "\n\t\tin Format:&nbsp;&nbsp;"
			. "\n\t\t<select name=\"exportFormatSelector\">"
			. "\n\t\t\t<option>Polar Biol</option>"
			. "\n\t\t\t<option>Mar Biol</option>"
			. "\n\t\t\t<option>MEPS</option>"
			. "\n\t\t\t<option>Deep Sea Res</option>"
			. "\n\t\t\t<option>Text Citation</option>"
			. "\n\t\t</select>\n\t</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td align=\"center\" colspan=\"3\">&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Help:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\">This form enables you to extract all citations from your text and build an appropriate reference list. To have this work simply include the serial numbers of your cited records within your text (as shown below) and enclose the serials by some preferrably unique characters. These delimiters must be specified in the text fields above.</td>"
			. "\n</tr>"
			. "\n<tr>\n\t<td valign=\"top\"><b>Example:</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><code>Results of the german south polar expedition were published by Hennings (1906) {1141} as well as several other authors (e.g.: Wille 1924 {1785}; Heiden &amp; Kolbe 1928 {1127}).</code></td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";
	
	// --------------------------------------------------------------------

	// BUILD THE HTML HEADER:
	function showheader($result, $HeaderString)
	{
		// call the 'displayheader()' function from 'header.inc'):
		displayheader();

		// finalize header containing the appropriate header string:
		echo "\n<tr>"
//			. "\n\t<td>&nbsp;</td>" // img in 'header.inc' now spans this row (by rowspan="2")
			. "\n\t<td>$HeaderString</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n<hr align=\"center\" width=\"80%\">";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter();

	// --------------------------------------------------------------------

?>
</body>
</html> 
