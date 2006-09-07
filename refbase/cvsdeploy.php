#! /usr/bin/env php
<?php
  // Project:    Web Reference Database (refbase) <http://www.refbase.net>
  // Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
  //             original author.
  //
  //             This code is distributed in the hope that it will be useful,
  //             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
  //             License for more details.
  //
  // File:       ./cvsdeploy.php
  // Author:     Richard Karnesky <mailto:karnesky@northwestern.edu>
  //
  // Created:    15-Dec-05, 19:34
  // Modified:   16-Jun-06, 23:13

  // TO DO: * modify cp to handle globs
  //        * modify cp to do file -> dir copies
  //        * handle our ActiveLink dependency
  //        * bibutils?
  //        * database?
  //        * old config

  // After you've checked refbase out from CVS, this script will copy files to
  // where they should be for deployment or packaging.


  if ($argc > 2 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
    ?>

    After you've checked refbase out from CVS, this script will copy files to
    where they should be for deployment or packaging.

    Usage:
    <?php echo $argv[0]; ?> <destination>

    <destination> is the destination directory (defaults to
    '/usr/local/www/refbase-cvs').

    With the --help, -help, -h, or -? options, you can get this help.

<?php
  } else {
    if ($argc < 2) {
      $d = '/usr/local/www/refbase-cvs/';
    } else {
      $d = $argv[1];
    }
    $source = getcwd();

    // note that realpath() will chop
    // any trailing delimiter like \ or /
    $dest = realpath($d);

    // OVERWRITES!!!!
    if(($dest!=FALSE) && (is_dir($dest))){
      $matching = glob("$dest/*");
      if($matching!=FALSE){
        $backup = $dest."-old";
        mkdir($backup);
        cp($dest,$backup);
        rm($dest);
      }
    }
    else{
      $dest = ereg_replace("[\/]?$", "", $d);
    }

    // make directories (clean this up later)
    if (!is_dir($dest)){
      mkdir($dest);
    }
    mkdir($dest."/css");
    mkdir($dest."/img");
    mkdir($dest."/includes");
    mkdir($dest."/initialize");

    // translation array
    $f = "./cvsdeploy.inc"; // get filename
    ob_start();
    readfile( $f );
    $s = "\$locs=array(".ob_get_contents().");"; 
    eval( $s );     // ...and store everything into $locs
    ob_end_clean();

    foreach ($locs as $key => $value){
      $s=$source.'/'.$key;
      $d=$dest.'/'.$value;
      if((is_dir($s))||(is_file($s))){
        cp($s,$d);
      }
    }

    // move citation style files from 'cite' to 'cite/styles'
    mkdir($dest."/cite/styles");
    $matching = glob($dest."/cite/cite_*");
    foreach ($matching as $match){
      cp($match,$dest."/cite/styles/".basename($match));
    }
    array_map('rm', $matching);
  }

  function cp($source,$dest) {
    /*
     Based on Anton Makarenko's script:
     <http://php.net/copy>
    */
    if (!is_dir($source)){
      if (is_file($source)){
        copy($source,$dest);
        return true;
      } else {
        trigger_error('source '.$source.' is not a file or a directory.', E_USER_ERROR);
        return false;
      }
    }
    if (!is_dir($dest)){
      trigger_error('destination '.$dest.' is not a directory.', E_USER_ERROR);
      return false;
    }
    if (!is_writable($dest)){
      trigger_error('destination '.$dest.' is not writable', E_USER_ERROR);
      return false;
    }

    $exceptions=array('.','..','CVS');
    //* Processing
    $handle=opendir($source);
    while (false!==($item=readdir($handle)))
      if (!in_array($item,$exceptions)) {
        //* cleanup for trailing slashes in directories destinations
        $from=str_replace('//','/',$source.'/'.$item);
        $to=str_replace('//','/',$dest.'/'.$item);

        if (is_file($from)) {
          if (@copy($from,$to)) {
            touch($to,filemtime($from)); // to track last modified time
          } else
            $errors[]='cannot copy file from '.$from.' to '.$to;
        }
        if (is_dir($from)) {
          if (@mkdir($to)) {
            $messages[]='Directory created: '.$to;
          } else
            $errors[]='cannot create directory '.$to;
          cp($from,$to);
        }
      }
      closedir($handle);
    return true;
  }

  function rm($fileglob) {
    /*
     Based on bishop's script:
     <http://php.net/unlink>
    */
    if (is_string($fileglob)) {
      if (is_file($fileglob)) {
        return unlink($fileglob);
      } else if (is_dir($fileglob)) {
        // note that on UNIX, * as the pattern will
        // NOT match dot-files and dot-directories
        $ok = rm("$fileglob/*");
        if (! $ok) {
          return false;
        }
        return rmdir($fileglob);
      } else {
        $matching = glob($fileglob);
        if ($matching === false) {
          trigger_error(sprintf('No files match supplied glob %s', $fileglob), E_USER_WARNING);
          return false;
        }     
        $rcs = array_map('rm', $matching);
        if (in_array(false, $rcs)) {
          return false;
        }
      }     
    } else if (is_array($fileglob)) {
      $rcs = array_map('rm', $fileglob);
      if (in_array(false, $rcs)) {
        return false;
      }
    } else {
      trigger_error('Param #1 must be filename or glob pattern, or array of filenames or glob patterns', E_USER_ERROR);
      return false;
    }
    return true;
  }
?>