<?
/*
IPOE literature database footer:
*/
?>
<?
	function displayfooter()
	{
		echo "\n<p align=\"center\">&nbsp;</p>"
			. "\n<hr align=\"center\" width=\"80%\">"
			. "\n<p align=\"center\"><a href=\"simple_search.php\">Simple Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"advanced_search.php\">Advanced Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"sql_search.php\">SQL Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"library_search.php\">Library Search</a></p>"
			. "\n<p align=\"center\"><a href=\"http://www.uni-kiel.de/ipoe/\">IP&Ouml; Home</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"index.php\">Literature Database Home</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"record.php\">Add Record</a></p>"
			. "\n<p align=\"center\">";

		echo date(r);

		echo "</p>\n";
	}
?>
