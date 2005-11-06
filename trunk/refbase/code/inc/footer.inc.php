<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./footer.inc.php
	// Created:    28-Jul-02, 11:30
	// Modified:   08-Sep-05, 16:35

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
		global $officialDatabaseName; // usage example: <a href="index.php">[? echo encodeHTML($officialDatabaseName); ?]</a>
		global $hostInstitutionAbbrevName; // usage example: <a href="[? echo $hostInstitutionURL; ?]">[? echo encodeHTML($hostInstitutionAbbrevName); ?] Home</a>
		global $hostInstitutionName; // (note: in the examples above, square brackets must be replaced by their respective angle brackets)
		global $hostInstitutionURL;
?>

<hr align="center" width="95%">
<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds the footer">
<tr>
	<td class="small" width="105"><a href="index.php" title="goto main page">Home</a></td>
	<td class="small" align="center">
		<a href="show.php?records=all" title="show all records in the database">Show All</a>
		&nbsp;|&nbsp;
		<a href="simple_search.php" title="search the main fields of the database">Simple Search</a>
		&nbsp;|&nbsp;
		<a href="advanced_search.php" title="search all fields of the database">Advanced Search</a>
		&nbsp;|&nbsp;<?php

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("allow_sql_search", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_sql_search'...
		{
		// ... include a link to 'sql_search.php':
?>

		<a href="sql_search.php" title="search the database by use of a SQL query">SQL Search</a>
		&nbsp;|&nbsp;<?php
		}

		// -------------------------------------------------------
?>

		<a href="library_search.php" title="search the library of the <?php echo encodeHTML($hostInstitutionName); ?>">Library Search</a>
	</td>
	<td class="small" align="right" width="105"><?php echo date('D, j M Y'); ?></td>
</tr>
<tr>
	<td class="small" width="105"><!--<a href="help.php" title="display help">Help</a>--></td>
	<td class="small" align="center"><?php

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("allow_add", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_add'...
		{
		// ... include a link to 'record.php?recordAction=add...':
?>

		<a href="record.php?recordAction=add&amp;oldQuery=<?php echo rawurlencode($oldQuery); ?>" title="add a record to the database">Add Record</a>
		&nbsp;|&nbsp;<?php
		}

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains either 'allow_import' or 'allow_batch_import'...
		{
		// ... include a link to 'import_csa.php':
?>

		<a href="import_csa.php" title="import a record from Cambridge Scientific Abstracts">CSA Import</a>
		&nbsp;|&nbsp;<?php
		}

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("allow_details_view", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_details_view'...
		{
		// ... include a link to 'show.php':
?>

		<a href="show.php" title="display details for a particular record by entering its database serial number">Show Record</a>
		&nbsp;|&nbsp;<?php
		}

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("allow_cite", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_cite'...
		{
		// ... include a link to 'extract.php':
?>

		<a href="extract.php" title="extract citations from a text and build an appropriate reference list">Extract Citations</a><?php
		}

		// -------------------------------------------------------
?>

	</td>
	<td class="small" align="right" width="105"><?php echo date('H:i:s O'); ?></td>
</tr>
</table><?php
	}
?>
