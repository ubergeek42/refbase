refbase 0.6.1b1 Readme
======================


About refbase
-------------
refbase is a web-based solution for managing scientific literature,
references and citations. This readme will give you a quick overview
about what's there and what's still missing, how to setup your files
& database tables and where to find out more.



What is there?
--------------
Currently, the following features have been implemented:

- search the whole database by use of different search forms

- search within results functionality

- browse found records

- view selected records in detail

- export selected records to different citation formats

- extract literature cited within a text and build an appropriate
  reference list

- login/logout capabilities

- user management

- add/edit/delete records (logged in users only)

- easy customization of application specific variables via one global
  'ini' file



What's almost there?
--------------------
Note, that this distribution is a beta release! It contains features
that are not fully implemented yet and that may not work as expected.
These are:

- 'Show My Refs': search form that shows up on the main page after
  successful login which will allow a user to easily search his *own*
  literature only

- the admin user can now access a list of all users by clicking the
  'Manage Users' link after login. The existing feature set should work
  correctly. However, we plan to add further options (like deleting
  users or 'search within results' functionality)



What is missing?
----------------
The following (planned) enhancements are still missing (the uppermost
items are the ones that we plan to implement first):

- proper management of the user specific fields 'marked', 'user_keys'
  & 'user_notes' (currently these fields are not user specific but
  global)

- ability to save any records or queries for easy later retrieval

- user customization: provide a user specific preferences page where
  one can specify the default columns that are visible in query results
  or the default fields that are visible in particular search forms

- availability of additional citation formats on export

- (batch) import of full ASFA records

- validation of data entered by the user

- 'copy citation to clipboard' functionality



How to install?
---------------
The following is just a (very) quick guide to installation:

- you'll need an existing mySQL installation (see <http://www.mysql.com/>
  for further information) as well as some basic knowledge of mySQL

- for help with the setup of the mySQL database tables please see the doc
  entries at <http://sourceforge.net/docman/?group_id=64647>

- move all files from within the 'files' directory to your web directory

- currently you'll need to edit your server config file in order to
  safely setup refbase. If you are using the Apache web server, open
  its 'httpd.conf' file and add something like the following in order
  to prevent include files from getting served to remote users:

  <Files ~ "*\.inc">
	  Order allow,deny
	  Deny from all
  </Files>
  
  <Files ~ "*\.inc\.php">
	  Order allow,deny
	  Deny from all
  </Files>

  if you want to serve your refbase files from a separate directory
  (lets assume its named 'refs') then you'll need to make this directory
  available to your web server. Again, for Apache use something like the
  following:
  
  Alias /refs/ "/PATH/TO/YOUR/DIRECTORY/"
  
  <Directory "/PATH/TO/YOUR/DIRECTORY">
      Options Indexes MultiViews IncludesNoExec
      AllowOverride None
      Order allow,deny
      Allow from all
  </Directory>

  now, you should be able to access the main page ('index.php') by typing:
  
  <http://YOUR_SERVER_ADDRESS/refs/>



How to setup the database?
--------------------------
In order to properly configure your database you need to edit some of
the configuration files:

- open the file 'db.inc' in a text editor & edit the values of the
  following variables:

  - '$databaseName' must contain the name of your mysql database

  - '$password' must contain the password that's required to access your
     mysql database

- open the file 'ini.inc.php' & edit all values of the contained variables
  to fit your needs. Please see the comments within the file for further
  guidance.



How to add the first user to the database?
------------------------------------------
By default, only the admin user will be able to add new users to the
database. Of course, there has to be an admin user in the first
place. The following shows you how to setup the very first user:

- open the file 'ini.inc.php' in a text editor and make sure that
  the variable '$addNewUsers' is set to 'everyone'.

- then open the file 'user_details.php' in your web browser (and make
  sure that the URL reads just 'user_details.php' and does NOT contain
  any parameters, e.g. like 'user_details.php?userID=1').

- you should see an empty form now. At a minimum, you have to specify
  your first & last name, your institutional abbreviation as well as
  your email address and password. Then click the 'Add User' button.

- if all goes well, you'll be logged in (as can be seen from a login
  message at the top right corner of the page) and shown a receipt
  page with the account details you just entered.

- now, go back to 'ini.inc.php' and set the variable '$addNewUsers' to
  'admin'. This prevents other users from messing with your users table.

- additionally, you should set the variable '$adminLoginEmail' in
  'ini.inc.php' to the email address of the newly created user in
  order to grant this user admin status. Any further users can now
  be added to the database by logging in as admin and clicking the
  'Add User' link. Similarly, existing users can be managed by clicking
  the 'Manage Users' link.



Rules for data import
---------------------
Some notes how to prepare data for upload into your newly created mySQL
tables:

- fields are separated by tabs, records are separated by returns
  (if not specified otherwise within the LOAD DATA statement)

- the order of fields must resemble the field order specified in the
  mySQL table 'refs'

- DATE format must be YYYY-MM-DD and TIME format must be HH:MM:SS

- carriage returns *within* fields must be represented by a newline
  character ('\n', ASCII character 10)

- empty fields must be indicated by \N

- character encoding: higher ASCII chars must be encoded as ISO-8859-1

- file encoding must be UNIX

- assuming your data file is named 'refs.txt', you should be able to
  upload your data via use of the mysql command:

  LOAD DATA LOCAL INFILE "/PATH/TO/FILE/refs.txt" INTO TABLE refs;

  or, alternatively, use something like the following from your shell:

  mysqlimport --local -u root -p YOUR_DB_NAME "/PATH/TO/FILE/refs.txt"



Known issues
------------
We are aware of the following problems, bugs and/or limitations:

- currently, the simple and advanced search forms employ lots of
  dynamic popup menus to ease user entry of data. This works well for
  small databases (<10.000 records) but may cause significant speed
  problems for larger databases!

- if you click on login/logout within the first of any query results
  pages *before* clicking somewhere else, you'll get an 'Error 1065:
  Query was empty'. This won't happen if you clicked somewhere else
  first.

- export as 'Text Citation' doesn't work properly on records that
  were added via the web interface.



Further information
-------------------
For more information about the refbase project and pointers to
working examples of refbase please visit:

  <http://www.refbase.net/>  or  <http://refbase.sourceforge.net/>



--
project:  Web Reference Database (refbase) <http://www.refbase.net/>
package:  refbase
release:  0.6.1b1
updated:  30-Jun-2003
contact:  Matthias Steffens <mailto:refbase@extracts.de>
license:  GNU General Public License (GPL)
