<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./export/bibutils/export_xml2ris.php
	// Created:    28-Sep-04, 22:14
	// Modified:   07-Apr-05, 11:34

	// This is an export format file (which must reside within the 'export/' sub-directory of your refbase root directory). It contains a version of the
	// 'exportRecords()' function that outputs records according to the standard export format used e.g. by commercial bibliographic packages like 'Reference Manager' (http://www.refman.com).
	// This function is basically a wrapper for the bibutils 'xml2ris' command line tool (http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html).

	// --------------------------------------------------------------------

	// --- BEGIN EXPORT FORMAT ---

	// Export found records in 'RIS' format:

	// Requires the following packages (available under the GPL):
	//    - bibutils <http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html>
	//    - ActiveLink PHP XML Package <http://www.active-link.com/software/>

	function exportRecords($result, $exportFormat)
	{
		// get the absolute path for the bibutils package
		$bibutilsPath = getExternalUtilityPath("bibutils"); // function 'getExternalUtilityPath()' is defined in 'include.inc.php'

		// Generate and serve a MODS XML file of ALL records:
		$recordCollection = modsCollection($result); // function 'modsCollection()' is defined in 'modsxml.inc.php'

		// write the MODS XML data to a temporary file:
		$tempFile = tempnam("/tmp", "refbase-"); // Note: currently, we simply write to '/tmp' since I don't know how to dynamically query the current temp directory! ?:-/
		$tempFileHandle = fopen($tempFile, "w"); // open temp file with write permission
		fwrite($tempFileHandle, $recordCollection); // save data to temp file
		fclose($tempFileHandle); // close temp file

		// pass this temp file to the bibutils 'xml2ris' utility for conversion:
		exec($bibutilsPath . "xml2ris " . $tempFile, $resultArray);

		$resultString = ""; // initialize variable

		// Read out the execution result array:
		if (!empty($resultArray)) // if the shell command returned any results
		{
			reset($resultArray); // reset the internal array pointer to the first element
			while (list($key, $val) = each($resultArray))
				$resultString .= "\n" . trim($val); // append each of the array elements to a string
		}

		// return record data in RIS format:
		return $resultString;
	}

	// --- END EXPORT FORMAT ---

	// --------------------------------------------------------------------
?>
