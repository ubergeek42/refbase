<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./footer.inc.php
	// Created:    28-Jul-02, 11:30
	// Modified:   10-May-04, 01:54

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
		global $officialDatabaseName; // usage example: <a href="index.php">[? echo htmlentities($officialDatabaseName); ?]</a>
		global $hostInstitutionAbbrevName; // usage example: <a href="[? echo $hostInstitutionURL; ?]">[? echo htmlentities($hostInstitutionAbbrevName); ?] Home</a>
		global $hostInstitutionName; // (note: in the examples above, square brackets must be replaced by their respective angle brackets)
		global $hostInstitutionURL;
?>

<hr align="center" width="95%">
<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds the footer">
<tr>
	<td class="small" width="105"><a href="index.php" title="goto main page">Home</a></td>
	<td class="small" align="center">
		<a href="simple_search.php" title="search the main fields of the database">Simple Search</a>
		&nbsp;|&nbsp;
		<a href="advanced_search.php" title="search all fields of the database">Advanced Search</a>
		&nbsp;|&nbsp;
		<a href="sql_search.php" title="search the database by use of a SQL query">SQL Search</a>
		&nbsp;|&nbsp;
		<a href="library_search.php" title="search the library of the <? echo htmlentities($hostInstitutionName); ?>">Library Search</a>
	</td>
	<td class="small" align="right" width="105"><? echo date('D, j M Y'); ?></td>
</tr>
<tr>
	<td class="small" width="105"><a href="help.php" title="display help">Help</a></td>
	<td class="small" align="center">
		<a href="record.php?recordAction=add&amp;oldQuery=<? echo rawurlencode($oldQuery); ?>" title="add a record to the database">Add Record</a>
		&nbsp;|&nbsp;
		<a href="import_csa.php" title="import a record from Cambridge Scientific Abstracts">CSA Import</a>
		&nbsp;|&nbsp;
		<a href="show.php" title="display details for a particular record by entering its database serial number">Show Record</a>
		&nbsp;|&nbsp;
		<a href="extract.php" title="extract citations from a text and build an appropriate reference list">Extract Citations</a>
	</td>
	<td class="small" align="right" width="105"><? echo date('H:i:s O'); ?></td>
</tr>
</table><?php
	}
?>
