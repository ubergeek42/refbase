<?
	// This is the footer include file.
	// It contains functions that build the footer
	// which gets displayed on every page.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// --------------------------------------------------------------------

	function displayfooter($oldQuery)
	{
		echo "\n<hr align=\"center\" width=\"95%\">"
			. "\n<p align=\"center\"><a href=\"simple_search.php\">Simple Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"advanced_search.php\">Advanced Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"sql_search.php\">SQL Search</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"library_search.php\">Library Search</a></p>"
			. "\n<p align=\"center\"><a href=\"http://www.uni-kiel.de/ipoe/\">IP&Ouml; Home</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"index.php\">Literature Database Home</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"record.php?recordAction=add&amp;oldQuery=" . rawurlencode($oldQuery) . "\">Add Record</a></p>"
			. "\n<p align=\"center\">";

		echo date(r);

		echo "</p>\n";
	}
?>
