<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Jochen Wendebaum <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./locales/core.php
	// Created:    01-Oct-04, 12:00
	// Modified:   12-Oct-04, 12:00
	
  $f = $refbaseDir ."locales/".$locale."/common.inc"; // get filename
  
  ob_start();
  	readfile( $f ); // read the file contents
  	$s = "\$loc=array(".ob_get_contents().");"; 
  	eval( $s );    // ...and store everything into $loc
  ob_end_clean();
?>