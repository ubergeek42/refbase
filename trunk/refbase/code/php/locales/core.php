<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author.
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./locales/core.php
	// Author:     Jochen Wendebaum <mailto:wendebaum@users.sourceforge.net>
	//
	// Created:    01-Oct-04, 12:00
	// Modified:   02-Apr-05, 13:43

	$f = "locales/".$locale."/common.inc"; // get filename

	ob_start();
		readfile( $f ); // read the file contents
		$s = "\$loc=array(".ob_get_contents().");"; 
		eval( $s );    // ...and store everything into $loc
	ob_end_clean();
?>