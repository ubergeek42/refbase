<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Jochen Wendebaum <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./locales/core.php
	// Created:    12-Oct-04, 12:00
	// Modified:   12-Oct-04, 12:00

	// This is the locales include file.
	// It contains functions that read the locales depending on the personal settings of the 
	// user or the default language, if no personal information can be found

  $locale = $defaultLanguage; // todo: get the personal language information

  include 'locales/core.php'; // include the locales

?>