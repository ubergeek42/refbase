<?php

/**
 * Enum for citation types
 */
abstract class RefbaseCitationType {

	// Minimal reference type (author, title, publication, year)
	const CT_MINIMAL = 0;

	// Request citation from refbase installation (using show.php interface)
	const CT_RB = 1;

	/**
	 * Convert string to RefbaseCitationType
	 */
	static public function decodeCitationType ( $str, & $citeStyle ) {
		if ( strtolower( $str ) == 'minimal' ) {
			return self::CT_MINIMAL;
		} elseif ( preg_match( '/rb-(.*)/', strtolower( $str ),
		                       $citeStyle ) ) {
			return self::CT_RB;
		} else {
			return null;
		}
	}

}

/**
 * Helper class to generate citation text
 */
class RefbaseCitationCreator {

	/// Citation type
	private $citationType;

	/// Citation style (only with $citationType = CT_RB)
	private $citationStyle = "";

	/// Location of refbase installation (may differ from $dbHost if using https
	/// for instance)
	protected $refbaseURL = "";

	/**
	 * Constructor
	 */
	public function __construct( $citationTypeStr ) {
		global $wgRefbaseURL;
		$this->refbaseURL = $wgRefbaseURL;
		$this->citationType =
			RefbaseCitationType::decodeCitationType( $citationTypeStr,
			                                         $citeStyle );
		if ( !empty( $citeStyle ) ) {
			$this->citationStyle = $citeStyle[1];
		}
		wfDebug('refbase-decode-in:' . $citationTypeStr . "\n");
		wfDebug('refbase-decode:' . $this->citationType . ", " . var_export($this->citationStyle,true)."\n");
	}

	/**
	 * Create citation text
	 */
	public function createCitation( $entry, & $cite ) {

		switch( $this->citationType ) {

		case RefbaseCitationType::CT_MINIMAL:
			$cite  = $entry['author'] . ", " . $entry['title'] . ", " .
			         $entry['publication'] . ", " . $entry['year'] . ".";
			break;

		case RefbaseCitationType::CT_RB:
			$url = $this->refbaseURL . "show.php?" .
			       "record=" . $entry['serial'] .
			       "&submit=Cite&exportType=text&citeType=ASCII";
			if ( !empty( $this->citationStyle ) ) {
				$url .= "&citeStyle=" . $this->citationStyle;
			}
			wfDebug('refbase-getcite:' . $url . "\n");

			// Get citation from url (add http authentication if desired)
			global $wgRefbaseURLAuth;

			if ( !empty( $wgRefbaseURLAuth ) ) {
				if ( strcmp( strtolower( $wgRefbaseURLAuth ),
				             'default' ) == 0 ) {
					if ( isset( $_SERVER['PHP_AUTH_USER'] ) &&
					     isset( $_SERVER['PHP_AUTH_PW'] ) ) {
						$username = $_SERVER['PHP_AUTH_USER'];
						$password = $_SERVER['PHP_AUTH_PW'];
						$authStr = "Authorization: Basic " .
						           base64_encode( "$username:$password" );
					} else {
						$authStr = '';
					}
				} else {
					preg_match( "/([^:]*):(.*)$/", $wgRefbaseURLAuth, $out);
					$username = $out[1];
					$password = $out[2];
					$authStr = "Authorization: Basic " .
					           base64_encode( "$username:$password" );
				}
				$param = array( 'http' => array( 'header'  => $authStr ) );
				$context = stream_context_create( $param );
				$cite = trim( file_get_contents( $url, false, $context ) );
			} else {
				$cite = trim( file_get_contents( $url ) );
			}
			break;

		default:
			$cite = wfMessage( 'refbase-error-citation-type' )->text();
		}

		return true;
	}

	/*
	 * Get list of required fields to produce the citation in the desired format
	 */
	public function getFieldList() {

		switch( $this->citationType ) {

		case RefbaseCitationType::CT_MINIMAL:
			$fieldList = array( 'author',
			                    'title',
			                    'publication',
			                    'year' );
			break;

		case RefbaseCitationType::CT_RB:
			$fieldList = array( 'serial' );
			break;

		default:
			$fieldList = array();
		}

		return $fieldList;
	}

}
