<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./initialize/ini.inc.php
	// Created:    12-Jan-03, 17:58
	// Modified:   14-Oct-04, 23:19

	// This is the customization include file.
	// It contains variables that are common to all scripts and whose values can/should be customized.
	// I.e., you can adjust their values as needed but you must not change the variable names themselves!

	// --------------------------------------------------------------------

	// The official name of this literature database:
	$officialDatabaseName = "Your Literature Database"; // e.g. "IPÖ Literature Database"


	// The base url for this literature database (i.e., the URL to the root directory):
	// It will be used within RSS feeds and when sending notification emails to database users.
	// (IMPORTANT: the base url MUST end with a slash!)
	$databaseBaseURL = "http://YOUR_SERVER_ADDRESS/PATH_ON_SERVER/"; // e.g. "http://polaris.ipoe.uni-kiel.de/refs/"


	// This attributive string describes your scientific field and the kind of literature
	// that's going to be stored within this literature database. It will be used on 'index.php'.
	$scientificFieldDescriptor = "..."; // e.g. "polar & marine"


	// Specify who'll be allowed to add a new user to the users table:
	// Note, that you should leave this variable as it is, if you're going to use the 'install.php'
	// script and the provided database structure file ('install.sql') for installation. This variable
	// is only provided for people who want to install the refbase database manually (i.e. without using
	// 'install.php' & 'install.sql'). If so, setting this value to "everyone" enables you to add the
	// admin as the very first user (don't forget to specify his email address below!). Then, change the
	// value of $addNewUsers to "admin". By that you prevent other users from messing with your users
	// table. (If the value is set to "everyone", any user will be able to add users to the users table!)
	$addNewUsers = "admin"; // possible values: "everyone", "admin"


	// The admin email address (by which a user is granted admin status after successful login!):
	$adminLoginEmail = "user@refbase.net"; // e.g. "admin@ipoe.uni-kiel.de"


	// The feedback email address to which any support questions or suggestions should be sent:
	$feedbackEmail = "FEEDBACK_EMAIL_ADDRESS"; // e.g. "admin@ipoe.uni-kiel.de"


	// The full name of the institution hosting this literature database:
	$hostInstitutionName = "Institute for ..."; // e.g. "Institute for Polar Ecology"


	// The abbreviated name of the institution hosting this literature database:
	$hostInstitutionAbbrevName = "..."; // e.g. "IPÖ"


	// The URL of the institution hosting this literature database:
	$hostInstitutionURL = "INSTITUTION_WEB_ADDRESS"; // e.g. "http://www.uni-kiel.de/ipoe/"


	// Specify whether announcements should be sent to the email address given in '$mailingListEmail':
	// If $sendEmailAnnouncements = "yes", a short info will be mailed to the email address specified
	// in $mailingListEmail if a new record has been added to the database.
	$sendEmailAnnouncements = "no"; // possible values: "yes", "no"


	// The mailing list email address to which any announcements should be sent:
	$mailingListEmail = "ANNOUNCEMENT_EMAIL_ADDRESS"; // e.g. "ipoelit-announce@ipoe.uni-kiel.de"


	// The character encoding that's used as content-type for HTML output and announcement emails:
	// Note: the encoding type specified here must match the type of encoding you've chosen on install
	//       for your refbase MySQL tables!
	$contentTypeCharset = "ISO-8859-1"; // e.g. "ISO-8859-1" or "UTF-8"


	// The path to the default CSS stylesheet which will be used for all page views except print view:
	$defaultStyleSheet = "css/style.css"; // e.g. "css/style.css"


	// The path to the CSS stylesheet which will be used for print view:
	$printStyleSheet = "css/style_print.css"; // e.g. "css/style_print.css"


	// Specify who'll be allowed to see files associated with any records:
	// Set this variable to "everyone" if you want _any_ visitor of your database (whether he's logged
	// in or not) to be able to see links to any associated files. If you choose "login" instead, a
	// user must be logged in to view any files. Finally, use "user-specific" if you want to set this
	// permission individually for each user. Note that, setting this variable to either "everyone" or
	// "login" will override the user-specific permission setting for file downloads ("allow_download"
	// permission).
	$fileVisibility = "user-specific"; // possible values: "everyone", "login", "user-specific"


	// Specify a condition where files will be always made visible [optional]:
	// This variable can be used to specify a condition where the above rule of file visibility can be
	// by-passed (thus allowing download access to some particular files while all other files are
	// protected by the above rule). Files will be shown regardless of the above rule if the specified
	// condition is met. First param must be a valid field name from table 'refs', second param the
	// conditional expression (specified as /perl-style regular expression/ -> see note at the end of
	// this file). The given example will *always* show links to files where the 'thesis' field of the
	// corresponding record is not empty. If you do not wish to make any exception to the above rule,
	// just specify an empty array, like: '$fileVisibilityException = array();'. Use the "/.../i"
	// modifier to invoke case insensitive matching.
	$fileVisibilityException = array("thesis", "/.+/"); // e.g. 'array("thesis", "/.+/")'


	// Define what will be searched by "library_search.php":
	// refbase offers a "Library Search" feature that provides a separate search page for searching an
	// institution's library. All searches performed thru this search form will be restricted to
	// records that match the specified condition. First param must be a valid field name from table
	// 'refs', second param the conditional expression (specified as MySQL extended regular expression
	// -> see note at the end of this file). Of course, you could also use this feature to restrict
	// searches thru "library_search.php" by _any_ other condition. E.g., with "location" as the first
	// parameter and your own login email address as the second parameter, any "library" search would
	// be restricted to your personal literature data set.
	$librarySearchPattern = array("location", "library"); // e.g. 'array("location", "IPÖ Library")'


	// The base DIR path to your default file directory:
	// I.e., the local path to the root directory where any PDF files etc. are stored. This must be a
	// valid path specification to a local directory that's accessible (read+write) by the server. As an
	// example, if you're using the Apache web server on a unix machine and if your default file
	// directory (named "files") is located on the root level of your refbase script directory (named
	// "refs") the path spec could be something like: "/usr/local/httpd/htdocs/refs/files/"
	$filesBaseDir = "PATH_TO_FILES_BASE_DIRECTORY"; // e.g. "/usr/local/httpd/htdocs/refs/files/"


	// The URL to the default file directory that you've specified in $filesBaseDir:
	// Any string within the 'file' field of the 'refs' table that doesn't start with "http://" or
	// "ftp://" will get prefixed with this URL. If your files directory is within your refbase root
	// directory, specify a *relative* path (e.g.: "files/" if the directory is on the same level as the
	// refbase php scripts and it's named "files"). Alternatively, if your files directory is somewhere
	// else within your server's DocumentRoot, you must specify an *absolute* path (e.g.: "/files/" if
	// the directory is on the uppermost level of your DocumentRoot and it's named "files"). If,
	// instead, you want to use *complete* path specifications within the 'file' field (e.g. because
	// your files are located within multiple directories), simply don't specify any URL here, i.e.,
	// keep it empty: '$filesBaseURL = "";'
	// (IMPORTANT: if given, the base url MUST end with a slash!)
	$filesBaseURL = "URL_TO_FILES_BASE_DIRECTORY"; // e.g. "files/"


	// If your institution has access to particular databases of the "Cambridge Scientific Abstracts"
	// (CSA) Internet Database Service (http://www.csa1.co.uk/csa/index.html), you can specify the
	// direct URL to the database(s) below. Why that? The 'import_csa.php' script offers an import form
	// that enables a user to import records from the CSA Internet Database Service. The URL you specify
	// here will appear as link within the explanatory text of 'import_csa.php' pointing your users
	// directly to the CSA databases you have access to.
	// e.g. "http://www.csa1.co.uk/htbin/dbrng.cgi?username=...&amp;access=...&amp;cat=aquatic&amp;quick=1"
	$importCSArecordsURL = "http://www.csa1.co.uk/csa/index.html";


	// The name of the default citation style:
	// This name must correspond to an entry within the 'styles' MySQL table.
	// It will be used for citation output within 'show.php' and the 'generateRSS()' function.
	$defaultCiteStyle = "Polar Biol";


	// The default language selection, can be overwritten by userdefined language
	$defaultLanguage = "en"; // e.g. "en" oder "de"


	// The following search & replace actions will be applied to the 'title', 'address', 'keywords' and
	// 'abstract' fields. This feature is meant to provide richer text capabilities (like displaying
	// italics or super-/subscript) from the plain text data delivered by the MySQL database. It works
	// by means of "human readable markup" that's used within the plain text fields of the database to
	// define rich text characters (note that the current implementation only supports the 'title',
	// 'address', 'keywords' and 'abstract' fields!). E.g., if you enclose a particular word by
	// substrings (like '_in-situ_') this word will be output in italics. Similarly, '**word**' will
	// print the word in boldface, 'CO[sub:2]' will cause the number in 'CO2' to be set as subscript
	// while '[delta]' will produce a proper delta symbol. Feel free to customize this markup scheme to
	// your needs (the left column below represents regular expression patterns matching the human
	// readable markup that's used in your database while the right column represents the equivalent
	// HTML encoding). If you do not wish to perform any search and replace actions, just specify an
	// empty array, like: '$markupSearchReplacePatterns = array();'. Search & replace patterns must be
	// specified as perl-style regular expression (in this case, without the leading & trailing
	// slashes) -> see note at the end of this file.
	$markupSearchReplacePatterns = array("_(.+?)_"          =>  "<i>\\1</i>",
										"\\*\\*(.+?)\\*\\*" =>  "<b>\\1</b>",
										"\\[super:(.+?)\\]" =>  "<sup>\\1</sup>",
										"\\[sub:(.+?)\\]"   =>  "<sub>\\1</sub>",
										"\\[permil\\]"      =>  "&permil;",
										"\\[alpha\\]"       =>  "&alpha;",
										"\\[beta\\]"        =>  "&beta;",
										"\\[gamma\\]"       =>  "&gamma;",
										"\\[delta\\]"       =>  "&delta;",
										"\\[epsilon\\]"     =>  "&epsilon;",
										"\\[zeta\\]"        =>  "&zeta;",
										"\\[eta\\]"         =>  "&eta;",
										"\\[theta\\]"       =>  "&theta;",
										"\\[iota\\]"        =>  "&iota;",
										"\\[kappa\\]"       =>  "&kappa;",
										"\\[lambda\\]"      =>  "&lambda;",
										"\\[mu\\]"          =>  "&mu;",
										"\\[nu\\]"          =>  "&nu;",
										"\\[xi\\]"          =>  "&xi;",
										"\\[omicron\\]"     =>  "&omicron;",
										"\\[pi\\]"          =>  "&pi;",
										"\\[rho\\]"         =>  "&rho;",
										"\\[sigmaf\\]"      =>  "&sigmaf;",
										"\\[sigma\\]"       =>  "&sigma;",
										"\\[tau\\]"         =>  "&tau;",
										"\\[upsilon\\]"     =>  "&upsilon;",
										"\\[phi\\]"         =>  "&phi;",
										"\\[chi\\]"         =>  "&chi;",
										"\\[psi\\]"         =>  "&psi;",
										"\\[omega\\]"       =>  "&omega;",
										"\\[Alpha\\]"       =>  "&Alpha;",
										"\\[Beta\\]"        =>  "&Beta;",
										"\\[Gamma\\]"       =>  "&Gamma;",
										"\\[Delta\\]"       =>  "&Delta;",
										"\\[Epsilon\\]"     =>  "&Epsilon;",
										"\\[Zeta\\]"        =>  "&Zeta;",
										"\\[Eta\\]"         =>  "&Eta;",
										"\\[Theta\\]"       =>  "&Theta;",
										"\\[Iota\\]"        =>  "&Iota;",
										"\\[Kappa\\]"       =>  "&Kappa;",
										"\\[Lambda\\]"      =>  "&Lambda;",
										"\\[Mu\\]"          =>  "&Mu;",
										"\\[Nu\\]"          =>  "&Nu;",
										"\\[Xi\\]"          =>  "&Xi;",
										"\\[Omicron\\]"     =>  "&Omicron;",
										"\\[Pi\\]"          =>  "&Pi;",
										"\\[Rho\\]"         =>  "&Rho;",
										"\\[Sigma\\]"       =>  "&Sigma;",
										"\\[Tau\\]"         =>  "&Tau;",
										"\\[Upsilon\\]"     =>  "&Upsilon;",
										"\\[Phi\\]"         =>  "&Phi;",
										"\\[Chi\\]"         =>  "&Chi;",
										"\\[Psi\\]"         =>  "&Psi;",
										"\\[Omega\\]"       =>  "&Omega;");

	// --------------------------------------------------------------------

	// Note regarding the use of regular expressions:

	// Certain variables within this file expect you to enter search patterns as either "MySQL
	// extended" or "perl-style" regular expression. While regular expressions provide a powerful
	// syntax for searching they may be somewhat difficult to write and daunting if you're new to the
	// concept. If you require help coming up with a correct regular expression that matches your
	// needs, you may want to visit <http://grep.extracts.de/> for pointers to language-specific
	// documentation, tutorials, books and regex-aware applications. Alternatively, you're welcome to
	// post a message to the refbase help forum: <http://sourceforge.net/forum/forum.php?forum_id=218758>

?>
