refbase 0.6 Readme
==================


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



What is missing?
----------------
The following (planned) enhancements are still missing (the uppermost
items are the ones that we plan to implement first):

- proper management of the user specific fields 'marked', 'user_keys'
  & 'user_notes' (currently these fields are not user specific but
  global)

- links & search forms that will allow a user to easily search his *own*
  literature only

- ability to save any records or queries for easy later retrieval

- user customization: provide a user specific preferences page where
  one can specify the default columns that are visible in query results
  or the default fields that are visible in particular search forms

- availability of additional citation formats on export

- (batch) import of full ASFA records

- easy customization of application specific variables via one global
  'ini' file

- validation of data entered by the user

- 'copy citation to clipboard' functionality



How to install?
---------------
Currently there's no doc entry yet on how to properly install refbase.
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
  
  <Files ~ "*\.inc.php">
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
release:  0.6
updated:  03-Jun-2003
contact:  Matthias Steffens <mailto:refbase@extracts.de>
license:  GNU General Public License (GPL)
