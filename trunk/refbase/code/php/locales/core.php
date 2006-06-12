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
	// Modified:   05-Jun-06, 16:06

	$f = "locales/".$locale."/common.inc"; // get filename

	ob_start();
		readfile( $f ); // read the file contents
		$s = "\$loc=array(".ob_get_contents().");"; 
		eval( $s );    // ...and store everything into $loc
	ob_end_clean();

	// HTML encode higher ASCII characters in locales:
	foreach ($loc as $locKey => $locValue)
	{
		$loc[$locKey] = encodeHTML($locValue); // function 'encodeHTML()' is defined in 'include.inc.php'

		if (preg_match("/&lt;a href=&quot;.+?&quot;&gt;.+?&lt;\/a&gt;/", $loc[$locKey])) // dirty hack to allow URLs within (otherwise HTML encoded) locales
			$loc[$locKey] = preg_replace("/&lt;a href=&quot;(.+?)&quot;&gt;(.+?)&lt;\/a&gt;/", "<a href=\"\\1\">\\2</a>", $loc[$locKey]);
	}
?>