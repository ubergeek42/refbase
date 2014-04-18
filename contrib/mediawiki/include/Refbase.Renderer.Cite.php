<?php

/**
 * Refbase entry renderer using the Cite extension tag (<ref>)
 */
class RefbaseRendererCite extends RefbaseRenderer {

	/// Object generating citations
	private $citationCreator;

	/**
	 * Constructor (simply inherit from parent)
	 */
	public function __construct( $citationType ) {
		parent::__construct();

		$this->citationCreator = new RefbaseCitationCreator( $citationType );
	}

	/**
	 * List fields required to build template
	 */
	public function getFieldList() {
		$citeList = $this->citationCreator->getFieldList();
		return array_unique( array_merge( array(), $citeList ) );
	}

	/**
	 * Render output: add wiki link to refbase page, include citation in tooltip
	 */
	public function render( $entry, & $cite, $options ) {

		$citekey = $options['citekey'];

		$cite = "";
		// Simply link to refbase, and add tooltip
		// (form string [URL <span title="CITATION"> KEY </span>] )

		$citation  = "";
		$this->citationCreator->createCitation( $entry, $citation );

		// Use #tag method to properly pass inputs to <ref>
		$cite .= "{{#tag:ref|$citation|name=$citekey}}";

		return true;
	}

}