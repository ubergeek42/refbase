<?php
/*
Source code example for Web Database Applications

Unless otherwise stated, the source code distributed with this book can be
redistributed in source or binary form so long as an acknowledgment appears
in derived source files.
The citation should list that the code comes from Hugh E.
Williams and David Lane, "Web Database Application with PHP and MySQL"
published by O'Reilly & Associates.
This code is under copyright and cannot be included in any other book,
publication, or educational product without permission from O'Reilly &
Associates.
No warranty is attached; we cannot take responsibility for errors or fitness
for use.
*/

// NOTE: Edit the variables '$databaseName' and '$password' to suit your setup!
// CAUTION: To avoid security risks you must not permit any remote user to view
//          this file! E.g., this can be done by adjusting the config file of
//          your server ("httpd.conf" in case of the apache web server) to
//          disallow viewing of "*\.inc" files ("Deny from all").

?>
<?php
   $hostName = "localhost";
   $databaseName = "MYSQL_DATABASE_NAME_GOES_HERE";
   $username = "root";
   $password = "MYSQL_PASSWORD_GOES_HERE";

   function clean($input, $maxlength)
   {
      $input = substr($input, 0, $maxlength);
      $input = EscapeShellCmd($input);
      return ($input);
   }
?>
