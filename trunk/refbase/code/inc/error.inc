<?
/*
Source code example for Web Database Applications

Unless otherwise stated, the source code distributed with this book can be
redistributed in source or binary form so long as an acknowledgment appears
in derived source files.
The citation should list that the code comes from Hugh E.
Williams and David Lane, "Web Database Application with PHP and MySQL"
published by O'Reilly & Associates.
This code is under copyright and cannot be included in any other book,
publication, or educational product without permission from O'Reilly &
Associates.
No warranty is attached; we cannot take responsibility for errors or fitness
for use.
*/
?>
<?
	function showerror()
	// includes code from 'footer.inc'
	{
		die("\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"80%\">\n<tr>\n\t<td valign=\"top\"> Error "
		. mysql_errno()
		. " : " . mysql_error()
		. "&nbsp;&nbsp;<a href=\"javascript:history.back()\">Go Back</a></td>\n</tr>\n</table>\n"
		. "\n<p align=\"center\">&nbsp;</p>"
		. "\n<hr align=\"center\" width=\"80%\">"
		. "\n<p align=\"center\"><a href=\"simple_search.php\">Simple Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"advanced_search.php\">Advanced Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"sql_search.php\">SQL Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"library_search.php\">Library Search</a></p>"
		. "\n<p align=\"center\"><a href=\"http://www.uni-kiel.de/ipoe/\">IP&Ouml; Home</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"index.php\">Literature Database Home</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"record.php\">Add Record</a></p>"
		. "\n<p align=\"center\">"
		.  date(r)
		. "</p>\n"
		. "\n</body>"
		. "\n</html>");
	}
?>
