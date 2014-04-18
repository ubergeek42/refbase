<?php

/**
 * Refbase entry renderer using simple hyperlink and tooltip
 */
class RefbaseRendererLink extends RefbaseRenderer {

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
		return array_unique( array_merge( array( 'serial' ), $citeList ) );
	}

	/**
	 * Render output: add wiki link to refbase page, include citation in tooltip
	 */
	public function render( $entry, & $cite, $options ) {

		$citekey = $options['citekey'];
		$cite = "";
		// Simply link to refbase, and add tooltip
		// (form string [URL <span title="CITATION"> KEY </span>] )

		// Display the key (cite_key or serial number as wiki text)
		$wikiText = $citekey;

		// Add full citation as a tooltip
		$toolTip  = "";
		$this->citationCreator->createCitation( $entry, $toolTip );

		// Link to refbase page for current entry
		$link = $this->refbaseURL . "show.php?record=" . $entry['serial'];

		// Build full string
		$cite .= "[" . $link . " ";
		$cite .= Html::openElement( 'span', array( 'title' => "\"" . $toolTip . "\"" ) );
		$cite .= $wikiText . Html::closeElement( 'span' ) . "]";

		return true;
	}

}