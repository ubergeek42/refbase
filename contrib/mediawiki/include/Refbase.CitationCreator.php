<?php

/**
 * Enum for citation types
 */
abstract class RefbaseCitationType {

	// Minimal reference type (author, title, publication, year)
	const CT_MINIMAL = 0;

	// Request citation from refbase installation (using unAPI interface)
	const CT_UNAPI     = 1;

	/**
	 * Convert string to RefbaseCitationType
	 */
	static public function decodeCitationType ( $str ) {
		switch ( strtolower( $str ) ) {
		case "minimal":
			return self::CT_MINIMAL;
		case "unapi":
			return self::CT_UNAPI;
		default:
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

	/// Location of refbase installation (may differ from $dbHost if using https
	/// for instance)
	protected $refbaseURL = "";

	/**
	 * Constructor
	 */
	public function __construct( $citationTypeStr ) {
		global $wgRefbaseURL;
		$this->refbaseURL = $wgRefbaseURL;
		$this->citationType = RefbaseCitationType::decodeCitationType( $citationTypeStr );
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

		case RefbaseCitationType::CT_UNAPI:
			$url = $this->refbaseURL . "unapi.php?id=" . $this->refbaseURL .
			       "show.php?record=" . $entry['serial'] . "&format=text";
			$cite = trim( file_get_contents( $url ) );
			break;

		default:
			$cite = wfMessage( 'refbase-error-citation-type' );
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

		case RefbaseCitationType::CT_UNAPI:
			$fieldList = array( 'serial' );
			break;

		default:
			$fieldList = array();
		}

		return $fieldList;
	}

}
