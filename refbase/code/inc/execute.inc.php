<?php
  // Project:    Web Reference Database (refbase) <http://www.refbase.net>
  // Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
  //             original author.
  //
  //             This code is distributed in the hope that it will be useful,
  //             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
  //             License for more details.
  //
  // File:       ./includes/execute.inc.php
  // Author:     Richard Karnesky <mailto:karnesky@northwestern.edu>
  //
  // Created:    16-Dec-05, 18:00
  // Modified:   03-Jan-06, 23:23

  // This fixes 'exec()' on certain win32 systems.
  // Based on rivera at spamjoy dot unr dot edu's 'wind_exec()' function
  // <http://php.net/function.exec>
 
  // Note: Since the 'exec()' function is used, some things may not work if
  //'safe_mode' is set to 'On' in your 'php.ini' file. If you need or want to
  // keep 'safe_mode=ON' then you'll need to put the programs within the
  // directory that's specified in 'safe_mode_exec_dir'.

  // Export records using the bibutils program given in '$prog'
  function exportBibutils($result, $prog) {
    // Get the absolute path for the bibutils package
    // function 'getExternalUtilityPath()' is defined in 'include.inc.php'
    $bibutilsPath = getExternalUtilityPath("bibutils");

    // Generate and serve a MODS XML file of ALL records
    // function 'modsCollection()' is defined in 'modsxml.inc.php'
    $recordCollection = modsCollection($result);

    // Get the path of the current directory that's used to save session data
    $sessionSavePath = session_save_path();

    // Write the MODS XML data to a temporary file
    $tempFile = tempnam($sessionSavePath, "refbase-");
    $tempFileHandle = fopen($tempFile, "w"); // open temp file with write permission
    fwrite($tempFileHandle, $recordCollection); // save data to temp file
    fclose($tempFileHandle); // close temp file

    // Pass this temp file to the bibutils utility for conversion
    $outputFile = tempnam($sessionSavePath, "refbase-");
    $cmd = $bibutilsPath . $prog . " " . $tempFile . " > " . $outputFile;
    execute($cmd,$tempFile);
    unlink($tempFile);

    // Read the resulting output file and return the converted data
    $resultString = file_get_contents($outputFile);
    unlink($outputFile);
    return $resultString;
  }

  // Execute shell command
  function execute($cmd) {
    if (getenv("OS") == "Windows_NT") {
      win_execute($cmd);
      return;
    } else {
      exec($cmd);
      return;
    }
  }

  function win_execute($cmd) {
    $cmdline = "cmd /C ".$cmd;

    // Make a new instance of the COM object
    $WshShell = new COM("WScript.Shell");

    // Make the command window but dont show it.
    $oExec = $WshShell->Run($cmdline, 0, true);
  }
?>
