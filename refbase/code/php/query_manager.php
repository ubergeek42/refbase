<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./query_manager.php
	// Created:    04-Feb-04, 22:29
	// Modified:   29-Aug-04, 20:39

	// This script enables you to manage your custom queries.
	// It offers a form to save the current query or update/delete any of your saved queries.
	// Saved queries are user specific and can be accessed from a popup on the main page.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
	// Incorporate some include files:
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// Extract session variables (only necessary if register globals is OFF!):
	if (isset($_SESSION['errors']))
		$errors = $_SESSION['errors'];
	else
		$errors = array(); // initialize variable (in order to prevent 'Undefined index/variable...' messages)

	if (isset($_SESSION['formVars']))
		$formVars = $_SESSION['formVars'];
	else
		$formVars = array(); // initialize variable (in order to prevent 'Undefined index/variable...' messages)

	// The current values of the session variables 'errors' and 'formVars' get stored in '$errors' or '$formVars', respectively. (either automatically if
	// register globals is ON, or explicitly if register globals is OFF [by uncommenting the code above]).
	// We need to clear these session variables here, since they would otherwise be still there on a subsequent call of 'query_manager.php'!
	// Note: though we clear the session variables, the current error message (or form variables) is still available to this script via '$errors' (or '$formVars', respectively).
	deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	deleteSessionVariable("formVars");

	// --------------------------------------------------------------------

	// A user must be logged in to save, modify or delete any queries:
	if (!isset($_SESSION['loginEmail']))
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You must login to save, modify or delete any queries!</span></b>";

		// save the URL of the currently displayed page:
		$referer = $_SERVER['HTTP_REFERER'];

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("referer", $referer);

		header("Location: user_login.php");
		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// Extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";


	// Check if the script was called with parameters (like: 'query_manager.php?customQuery=1&sqlQuery=...&showQuery=...&showLinks=...')
	// If so, the parameter 'customQuery=1' will be set:
	if (isset($_REQUEST['customQuery']))
		$customQuery = $_REQUEST['customQuery']; // accept any previous SQL queries
	else
		$customQuery = "0";


	if (isset($_REQUEST['queryAction']))
		$queryAction = $_REQUEST['queryAction']; // check whether the user wants to *add* a query or *edit* an existing one
	else
		$queryAction = "add"; // *add* query will be the default action if no parameter is given


	if (isset($_REQUEST['queryID']))
		$queryID = $_REQUEST['queryID']; // fetch the query ID of the query to edit
	else
		$queryID = "";


	if (isset($_REQUEST['oldQuery']))
	{
		$oldQuery = $_REQUEST['oldQuery']; // get the query URL of the formerly displayed results page (if available) so that its's available on the subsequent receipt page that follows any add/edit/delete action!
		if (ereg('sqlQuery%3D', $oldQuery)) // if '$oldQuery' still contains URL encoded data... ('%3D' is the URL encoded form of '=', see note below!)
			$oldQuery = rawurldecode($oldQuery); // ...URL decode old query URL
											// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
											//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!
		$oldQuery = str_replace('\"','"',$oldQuery); // replace any \" with "
	}
	else
		$oldQuery = ""; // if the 'oldQuery' parameter wasn't set we set the '$oldQuery' variable to the empty string ("") to prevent 'Undefined index: oldQuery...' notification messages


	// get the referring URL (if any):
	if (isset($_SERVER['HTTP_REFERER']))
		$referer = $_SERVER['HTTP_REFERER'];
	else
		$referer = ""; // if there's no HTTP referer available we provide the empty string here
	

	// Setup some required variables:

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
	{
		if (empty($errors)) // provide one of the default messages:
		{
			$errors = array(); // re-assign an empty array (in order to prevent 'Undefined variable "errors"...' messages when calling the 'fieldError' function later on)
			if ($queryAction == "edit") // *edit* query
				$HeaderString = "Edit saved query:";
			else // *add* query will be the default action if no parameter is given
			{
				if ($customQuery == "1") // the script was called with parameters
					$HeaderString = "Save your current query:"; // Provide the default message
				else // the script was called without any custom SQL query
					$HeaderString = "Save a query for later retrieval:"; // Provide the default message
			}
		}
		else // -> there were errors validating the data entered by the user
			$HeaderString = "<b><span class=\"warning\">There were validation errors regarding the data you entered:</span></b>";

	}
	else
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}


	// Adjust the page (= button) title & the help text:
	if ($queryAction == "edit") // *edit* query
	{
		$pageTitle = "Edit Query"; // set the correct page title

		$helpText = "Modify the elements of your query as needed and click the <em>Save Query</em> button. You can change the name of your query, refine the SQL query string or modify any of the display options.";
	}
	else // *add* query
	{
		$pageTitle = "Add Query"; // set the correct page title

		// Adjust the help text:
		if ($customQuery == "1") // if the script was called with parameters
			$helpText = "Name your query and click the <em>Save Query</em> button. If you like, you can refine your query or modify any of the display options before saving.";
		else
			$helpText = "Enter your query and set the display options to suit your needs. Then, name your query and click the <em>Save Query</em> button.";
	}

	// --------------------------------------------------------------------

	if ($queryAction == "edit" && empty($errors))
	{
		$exit = false;

		// CONSTRUCT SQL QUERY:
		// for the selected query, select *all* fields that are available in the form:
		$query = "SELECT query_id, user_id, query_name, display_type, view_type, query, show_query, show_links, show_rows, cite_style_selector, cite_order"
				. " FROM queries WHERE query_id = $queryID"; // since we'll only fetch one record, the ORDER BY clause is obsolete here


		// (1) OPEN CONNECTION, (2) SELECT DATABASE
		connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

		// (3a) RUN the query on the database through the connection:
		$result = queryMySQLDatabase($query, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		if (@ mysql_num_rows($result) == 1) // this condition is added here to avoid the case that editing a query item which got deleted in the meantime invokes a seemingly correct but empty 'edit query' form
		{
			// (3b) EXTRACT results:
			$row = mysql_fetch_array($result); //fetch the current row into the array $row (it'll be always *one* row, but anyhow)

			// check whether the user tries to edit a query that does not belong to his own set of saved queries:
			if ($row['user_id'] != getUserID($loginEmail)) // the function 'getUserID' and the '$loginEmail' variable are specified in 'include.inc.php'
			{
				// save an error message:
				$HeaderString = "<b><span class=\"warning\">You can only edit your own queries!</span></b>";
				$exit = true;
			}
		}
		else // the query did NOT return any results (since we searched for a unique primary key of the queries table, the number of rows found can be only 1 or 0)
		{
			// save an error message:
			$HeaderString = "<b><span class=\"warning\">The specified query does not exist!</span></b>";
			$exit = true;
		}

		if ($exit)
		{
			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

			header("Location: index.php"); // relocate back to the main page
			exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
		}
	}

	// --------------------------------------------------------------------

	// assign correct values to the form variables:
	if (empty($errors))
	{
		if ($queryAction == "edit")
		{
			// fetch attributes of the current query into variables:
			$queryID = $row['query_id'];
			$queryName = $row['query_name'];
			$displayType = $row['display_type'];
			$queryViewType = $row['view_type'];
			$sqlQuery = $row['query'];
			$showQuery = $row['show_query'];
			$showLinks = $row['show_links'];
			$showRows = $row['show_rows'];
			$citeStyle = htmlentities($row['cite_style_selector']);
			$citeOrder = $row['cite_order'];
			$origQueryName = $row['query_name'];
		}
		else // $queryAction == "add"
		{
			$queryID = "0"; // since '' would cause an SQL error we use '0' to indicate that there's no query ID
			$queryName = "";
			$queryViewType = ""; // ('' will produce the default 'Web' view)
			$origQueryName = "";

			if ($customQuery == "1") // the script was called with parameters
			{
				$displayType = $_REQUEST['displayType']; // extract the type of display requested by the user (either 'Display', 'Cite' or '')

				$sqlQuery = $_REQUEST['sqlQuery']; // accept any previous SQL queries
					$sqlQuery = str_replace('\"','"',$sqlQuery); // convert \" into "
					$sqlQuery = str_replace('\\\\','\\',$sqlQuery);
		
				$showQuery = $_REQUEST['showQuery']; // extract the $showQuery parameter
				$showLinks = $_REQUEST['showLinks']; // extract the $showLinks parameter
				$showRows = $_REQUEST['showRows']; // extract the $showRows parameter
				$citeStyle = $_REQUEST['citeStyleSelector']; // get the cite style chosen by the user (only occurs in 'extract.php' form and in query result lists)
				$citeOrder = $_REQUEST['citeOrder']; // get the citation sort order chosen by the user (only occurs in 'extract.php' form and in query result lists)
			}
			else // if there was no previous SQL query provide the default query and options:
			{
				$displayType = ""; // ('' will produce the default columnar output style)
				$sqlQuery = "SELECT author, title, year, created_by, modified_date, modified_time, modified_by FROM refs WHERE modified_date = CURDATE() ORDER BY modified_date DESC, modified_time DESC";
				$showQuery = "0";
				$showLinks = "1";
				$showRows = "5";
				$citeStyle = "";
				$citeOrder = "";
			}			
		}
	}
	else // there were some errors on submit
	{
		// load the form data that were entered by the user:
		$queryID = $formVars['queryID'];
		$queryName = $formVars['queryName'];
		$displayType = $formVars['displayType'];
		$queryViewType = $formVars['queryViewType'];

		$sqlQuery = $formVars['sqlQuery'];
			$sqlQuery = str_replace('\"','"',$sqlQuery); // convert \" into "
			$sqlQuery = str_replace('\\\\','\\',$sqlQuery);

		if (isset($formVars['showQuery']))
			$showQuery = $formVars['showQuery'];
		else
			$showQuery = "";

		if (isset($formVars['showLinks']))
			$showLinks = $formVars['showLinks'];
		else
			$showLinks = "";

		$showRows = $formVars['showRows'];

		if (isset($formVars['citeStyleSelector']))
			$citeStyle = $formVars['citeStyleSelector'];
		else
			$citeStyle = "";
		if (ereg("%20", $citeStyle)) // if '$citeStyle' still contains URL encoded data... ('%20' is the URL encoded form of a space, see note below!)
			$citeStyle = rawurldecode($citeStyle); // ...URL decode 'citeStyle' statement (it was URL encoded before incorporation into a hidden tag of the 'sqlSearch' form to avoid any HTML syntax errors)
														// NOTE: URL encoded data that are included within a *link* will get URL decoded automatically *before* extraction via '$_REQUEST'!
														//       But, opposed to that, URL encoded data that are included within a form by means of a *hidden form tag* will NOT get URL decoded automatically! Then, URL decoding has to be done manually (as is done here)!

		$citeOrder = $formVars['citeOrder'];

		if (isset($formVars['origQueryName']))
			$origQueryName = rawurldecode($formVars['origQueryName']); // get the original query name that was included within a hidden form tag (and since it got URL encoded, we'll need to decode it again)
		else
			$origQueryName = "";
	}

	// set display options according to the fetched attribute values:
	if ($showQuery == "1")
		$checkQuery = " checked";
	else
		$checkQuery = "";

	if ($showLinks == "1")
		$checkLinks = " checked";
	else
		$checkLinks = "";

	if ($queryViewType == "Print")
	{
		$webViewTypeSelected = "";
		$printViewTypeSelected = " selected";
	}
	else // '$queryViewType' is 'Web' or ''
	{
		$webViewTypeSelected = " selected";
		$printViewTypeSelected = "";
	}


	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(htmlentities($officialDatabaseName) . " -- " . $pageTitle, "index,follow", "Manage queries that are used to search the " . htmlentities($officialDatabaseName), "", false, "", $viewType);
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, $oldQuery);

	// (2b) Start <form> and <table> holding the form elements:
	// note: we provide a default value for the 'submit' form tag so that hitting <enter> within a text entry field will act as if the user clicked the 'Add/Edit Query' button
?>

<form action="query_modify.php" method="POST">
<input type="hidden" name="formType" value="saveQuery">
<input type="hidden" name="submit" value="<? echo $pageTitle; ?>">
<input type="hidden" name="queryAction" value="<? echo $queryAction; ?>">
<input type="hidden" name="queryID" value="<? echo $queryID; ?>">
<input type="hidden" name="displayType" value="<? echo $displayType; ?>">
<input type="hidden" name="citeStyleSelector" value="<? echo rawurlencode($citeStyle); ?>">
<input type="hidden" name="citeOrder" value="<? echo $citeOrder; ?>">
<input type="hidden" name="oldQuery" value="<? echo rawurlencode($oldQuery); ?>">
<input type="hidden" name="origQueryName" value="<? echo rawurlencode($origQueryName); ?>">
<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds forms that enable you to manage your custom queries">
	<tr>
		<td width="58" valign="top"><b>Query Name:</b></td>
		<td width="10">&nbsp;</td>
		<td>
			<? echo fieldError("queryName", $errors); ?><input type="text" name="queryName" value="<? echo htmlentities($queryName); ?>" size="33">
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="submit" value="<? echo $pageTitle; ?>"><?php

	if ($queryAction == "edit") // add a DELETE button (CAUTION: the delete button must be displayed *AFTER* the edit button, otherwise DELETE will be the default action if the user hits return!!)
								// (this is since the first displayed submit button represents the default submit action in several browsers!! [like OmniWeb or Mozilla])
	{
?>

			&nbsp;&nbsp;&nbsp;<input type="submit" name="submit" value="Delete Query"><?php
	}
?>

		</td>
	</tr>
	<tr>
		<td align="center" colspan="3">&nbsp;</td>
	</tr>
	<tr>
		<td width="58" valign="top"><b>SQL Query:</b></td>
		<td width="10">&nbsp;</td>
		<td><? echo fieldError("sqlQuery", $errors); ?><textarea name="sqlQuery" rows="6" cols="60"><? echo $sqlQuery; ?></textarea></td>
	</tr>
	<tr>
		<td valign="top"><b>Display Options:</b></td>
		<td>&nbsp;</td>
		<td valign="top"><input type="checkbox" name="showLinks" value="1"<? echo $checkLinks; ?>>&nbsp;&nbsp;&nbsp;Display Links&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Show&nbsp;&nbsp;&nbsp;<input type="text" name="showRows" value="<? echo $showRows; ?>" size="4">&nbsp;&nbsp;&nbsp;records per page</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td valign="top"><input type="checkbox" name="showQuery" value="1"<? echo $checkQuery; ?>>&nbsp;&nbsp;&nbsp;Display SQL query&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;View type:&nbsp;&nbsp;
			<select name="queryViewType">
				<option<? echo $webViewTypeSelected; ?>>Web</option>
				<option<? echo $printViewTypeSelected; ?>>Print</option>
			</select>
		</td>
	</tr>
	<tr>
		<td align="center" colspan="3">&nbsp;</td>
	</tr>
	<tr>
		<td valign="top"><b>Help:</b></td>
		<td>&nbsp;</td>
		<td><? echo $helpText; ?></td>
	</tr>
	<tr>
		<td valign="top">&nbsp;</td>
		<td>&nbsp;</td>
		<td>The <a href="http://www.mysql.com/documentation/index.html">MySQL online manual</a> has a <a href="http://www.mysql.com/documentation/mysql/bychapter/manual_Tutorial.html">tutorial introduction</a> on using MySQL and provides a detailed description of the <a href="http://www.mysql.com/doc/S/E/SELECT.html"><code>SELECT</code> syntax</a>.</td>
	</tr>
</table>
</form><?php

	// --------------------------------------------------------------------

	// SHOW ERROR IN RED:
	function fieldError($fieldName, $errors)
	{
		if (isset($errors[$fieldName]))
			return "<b><span class=\"warning2\">" . $errors[$fieldName] . "</span></b><br>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc.php')
	displayfooter($oldQuery);

	// --------------------------------------------------------------------
?>

</body>
</html> 