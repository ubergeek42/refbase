<?php
/**
 * Internationalisation for Refbase
 *
 * @Refbase.i18n.php
 * @ingroup Extensions
 */
$messages = array();

/** English
 * @author thibault marin
 */
$messages['en'] = array(

	// Description
	'refbase-desc' => "This extension allows inclusion of bibliographic references from a refbase installation",

	// Error messages
	'refbase-error-tagtype'    => 'Unsupported tag type (should be "serial" or "citekey").',
	'refbase-error-outputtype' => 'Unsupported output type (should be "cite" or "footnote").',
	'refbase-error-dbquery'    => 'Error in database query: ',
	'refbase-error-notfound'   => 'Entry not found'
);

/** Message documentation
 * @author thibault marin
 */
$messages['qqq'] = array(
	'refbase-desc' => "{{desc}}",

	'refbase-error-tagtype'     => 'Error message displayed when the tag type used is not supported.',
	'refbase-error-outputttype' => 'Error message displayed when the output type used is not supported.',
	'refbase-error-dbquery'     => 'Error message displayed when refbase database query failed (followed by error message).',
	'refbase-error-notfound'    => 'Error message displayed when key was not gound in database'
);

