<?php
  // Copyright:  Richard Karnesky <mailto:karnesky@gmail.com>
  //             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
  //             Please see the GNU General Public License for more details.


if( !defined( 'MEDIAWIKI' ) )
{
	echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
	die( -1 );
}

$wgExtensionCredits['parserhook'][] = array(
    'path' => __FILE__,
    'name' => 'Refbase',
    'author' => array( 'Richard Karnesky', 'Thibault Marin' ),
    'url' => 'https://www.mediawiki.org/wiki/Extension:Refbase',
    'description' => 'refbase-desc',
    'version'  => 1.0,
    'license-name' => "",   // Short name of the license, links LICENSE or COPYING file if existing - string, added in 1.23.0
);

/**
 * Extension class
 */
$wgAutoloadClasses['RefbaseHooks'] =
    dirname( __FILE__ ) . '/Refbase.Hooks.php';
$wgAutoloadClasses['RefbaseRenderer'] =
    dirname( __FILE__ ) . '/Refbase.Renderer.php';
$wgAutoloadClasses['RefbaseConnector'] =
    dirname( __FILE__ ) . '/Refbase.Connector.php';

/**
 * Register hooks
 */
$wgHooks['ParserFirstCallInit'][] =
    'RefbaseHooks::efRefbaseParserInit';
$wgHooks['ParserAfterTidy'][] = 'RefbaseHooks::efRefbaseAfterTidy';

/**
 * Internationalization
 */
$wgExtensionMessagesFiles['Refbase'] =
    dirname( __FILE__ ) . '/Refbase.i18n.php';

/**
 * Parameters (modify in LocalSettings.php)
 */

// refbase database host
$wgRefbaseDbHost = "localhost";

// Database name
$wgRefbaseDbName = "literature";

// User name for database
$wgRefbaseDbUser = "litwww";

// Database password
$wgRefbaseDbPass = "%l1t3ratur3?";

// Database charset
$wgRefbaseDbCharset = "utf8";

// Table with references
$wgRefbaseDbRefTable = "refs";

// Table with user data (cite key)
$wgRefbaseDbUserDataTable = "user_data";

// Host for refbase instance (used for url links).  This may differ from the
// database host if using https for instance (requires a trailing slash)
$wgRefbaseURL = "http://".$_SERVER['HTTP_HOST']."/refbase/";

// Default tag input: when using <refbase>XXX</refbase>, XXX can refer to the
// serial number ('serial' type) or the citation key ('citekey' type)
$wgRefbaseDefaultTagType = "serial";

// Default output type: may use cite_journal, ref or link
$wgRefbaseDefaultOutputType = 'cite_journal';

