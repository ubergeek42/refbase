<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./header.inc.php
	// Created:    28-Jul-02, 11:21
	// Modified:   12-Oct-04, 14:04

	// This is the header include file.
	// It contains functions that provide the HTML header
	// as well as the visible header that gets displayed on every page.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// --------------------------------------------------------------------

	// Inserts the HTML <head>...</head> block as well as the initial <body> tag:
	function displayHTMLhead($pageTitle, $metaRobots, $metaDescription, $additionalMeta, $includeJavaScript, $includeJavaScriptFile, $viewType)
	{
		global $contentTypeCharset; // these variables are specified in 'ini.inc.php' 
		global $defaultStyleSheet;
		global $printStyleSheet;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
		"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title><? echo $pageTitle; ?></title>
	<meta name="date" content="<? echo date('d-M-y'); ?>">
	<meta name="robots" content="<? echo $metaRobots; ?>">
	<meta name="description" lang="en" content="<? echo $metaDescription; ?>">
	<meta name="keywords" lang="en" content="science academic literature scientific references search citation web database mysql php"><?php

		if (!empty($additionalMeta))
			echo $additionalMeta;
?>

	<meta http-equiv="content-language" content="en">
	<meta http-equiv="content-type" content="text/html; charset=<? echo $contentTypeCharset; ?>">
	<meta http-equiv="Content-Style-Type" content="text/css"><?php

		if ($viewType == "Print")
		{
?>

	<link rel="stylesheet" href="<? echo $printStyleSheet; ?>" type="text/css" title="CSS Definition"><?php
		}
		else
		{
?>

	<link rel="stylesheet" href="<? echo $defaultStyleSheet; ?>" type="text/css" title="CSS Definition"><?php
		}

		if (!empty($includeJavaScriptFile))
		{
?>

	<script language="JavaScript" type="text/javascript" src="<? echo $includeJavaScriptFile; ?>">
		</script><?php
		}

		if ($includeJavaScript)
		{
?>

	<script language="JavaScript" type="text/javascript">
		function checkall(val,formpart){
			x=0;
			while(document.queryResults.elements[x]){
				if(document.queryResults.elements[x].name==formpart){
					document.queryResults.elements[x].checked=val;
				}
				x++;
			}
		}
	</script><?php
		}
?>

</head>
<body><?php
	}

	// --------------------------------------------------------------------

	// Displays the visible header:
	function showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, $oldQuery)
	{
		global $officialDatabaseName; // these variables are defined in 'ini.inc.php'
		global $hostInstitutionName;
		global $hostInstitutionAbbrevName;
		global $hostInstitutionURL;
?>

<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This holds the title logo and info">
<tr>
	<td valign="middle" rowspan="2" align="left" width="170"><a href="<? echo $hostInstitutionURL; ?>"><img src="img/logo.gif" border="0" alt="<? echo htmlentities($hostInstitutionAbbrevName); ?> Home" title="<? echo htmlentities($hostInstitutionName); ?>" width="143" height="107"></a></td>
	<td>
		<h2><? echo htmlentities($officialDatabaseName); ?></h2>
		<span class="smallup">
			<a href="index.php" title="goto main page">Home</a>&nbsp;|&nbsp;
			<a href="simple_search.php" title="search the main fields of the database">Simple Search</a>&nbsp;|&nbsp;
			<a href="advanced_search.php" title="search all fields of the database">Advanced Search</a>&nbsp;|&nbsp;<?php

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("allow_add", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains 'allow_add'...
		{
		// ... include a link to 'record.php?recordAction=add...':
?>

			<a href="record.php?recordAction=add&amp;oldQuery=<? echo rawurlencode($oldQuery); ?>" title="add a record to the database">Add Record</a>&nbsp;|&nbsp;<?php
		}

		// -------------------------------------------------------
		if (isset($_SESSION['user_permissions']) AND ereg("(allow_import|allow_batch_import)", $_SESSION['user_permissions'])) // if the 'user_permissions' session variable contains either 'allow_import' or 'allow_batch_import'...
		{
		// ... include a link to 'import_csa.php':
?>

			<a href="import_csa.php" title="import a record from Cambridge Scientific Abstracts">CSA Import</a>&nbsp;|&nbsp;<?php
		}

		// -------------------------------------------------------
?>

			<a href="help.php" title="display help">Help</a>
		</span>
	</td>
	<td class="small" align="right" valign="middle"><? echo $loginWelcomeMsg; ?><br><? echo $loginStatus; ?></td>
</tr>
<tr>
	<td><? echo $HeaderString; ?></td>
	<td class="small" align="right" valign="middle"><? echo $loginLinks; ?></td>
</tr>
</table>
<hr align="center" width="95%"><?php
	}

	// --------------------------------------------------------------------
?>
