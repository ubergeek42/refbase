<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./results_header.inc.php
	// Created:    07-May-04, 14:38
	// Modified:   07-May-04, 20:51

	// This is the results header include file.
	// It contains functions that build the results header
	// which gets displayed on every search results page.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// --------------------------------------------------------------------

	function displayResultsHeader($formElementsGroup, $formElementsRefine, $formElementsDisplayOptions)
	{
?>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="94%" summary="This table holds the results header">
<tr>
	<td width="246">
<? echo $formElementsGroup; ?>
	</td>
	<td>
<? echo $formElementsRefine; ?>
	</td>
	<td align="right" width="246">
<? echo $formElementsDisplayOptions; ?>
	</td>
</tr>
</table><?php
	}
?>
