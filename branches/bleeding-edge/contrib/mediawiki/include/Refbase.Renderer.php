<?php

/**
 * Refbase entry renderer
 */
abstract class RefbaseRenderer {

	/// Location of refbase installation (may differ from $dbHost if using https
	/// for instance)
	protected $refbaseURL = "";

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wgRefbaseURL;
		$this->refbaseURL = $wgRefbaseURL;
	}

	/**
	 * Instantiation subclass instances
	 */
	public static function create( $outputType, $citationType = "" ) {
		if ( strtolower( $outputType ) == 'cite_journal' ) {
			return new RefbaseRendererCitationTemplate();
		} elseif ( strtolower( $outputType ) == 'link' ) {
			return new RefbaseRendererLink( $citationType );
		} elseif ( strtolower( $outputType ) == 'cite' ) {
			return new RefbaseRendererCite( $citationType );
		} else {
			return false;
		}
	}

	/**
	 * Returns the list of fields to extract from the database
	 */
	abstract public function getFieldList();

	/**
	 * Render entries
	 */
	abstract public function render( $entry, & $cite, $options );

}

