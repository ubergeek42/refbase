<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the function's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/install.inc.php
	// Created:    16-Aug-06, 18:00
	// Modified:   16-Aug-06, 20:29

	// This file contains functions
	// that are used when installing
	// or updating a refbase database.

	// --------------------------------------------------------------------

	// This function attempts to find a file (or program) on disk. It searches directories
	// given in '$fileLocations' for existing file/program names given in '$fileNames'.
	// Note that, currently, this function won't look into subdirectories.
	// 
	// Authors: Richard Karnesky <mailto:karnesky@gmail.com> and
	//          Matthias Steffens <mailto:refbase@extracts.de>
	function locateFile($fileLocations, $fileNames, $returnParentDirOnly)
	{
		$filePath = "";

		foreach ($fileLocations as $location)
		{
			foreach ($fileNames as $name)
			{
				if (file_exists("$location/$name"))
				{
					if ($returnParentDirOnly)
						$filePath = realpath($location) . "/";
					else
						$filePath = realpath("$location/$name");

					break 2;
				}
			}
		}

		return $filePath;
	}

	// --------------------------------------------------------------------
?>
