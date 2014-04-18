<?php

/**
 * Refbase hooks and parser
 */
class RefbaseHooks {

	/**
	 * Register <refbase> hook
	 */
	public static function efRefbaseParserInit( $parser ) {
		$parser->setHook( 'refbase',
		                  'RefbaseHooks::efRefbaseRender' );
		return true;
	}

	/**
	 * Define special formatting for this tag
	 */
	private static function makeOutputString ( $str ) {
		return $str;
	}

	/**
	 * Add <pre></pre> tags around error message and return
	 */
	private static function makeErrorOutputString ( $errMsg ) {
		$errMsg = "Refbase: <br/>" . $errMsg;
		$preMsg = Html::openElement( 'pre' ) . $errMsg .
		          Html::closeElement( 'pre' );
		return self::makeOutputString( $preMsg );
	}

	/**
	 * Main function: parse input and create HTML table with events
	 */
	public static function efRefbaseRender( $input, array $args,
	                                        Parser $parser,
	                                        PPFrame $frame ) {
		// Global parameters
		global $wgRefbaseDefaultTagType;
		global $wgRefbaseDefaultOutputType;
		global $wgRefbaseDefaultCitationType;

		// Read arguments
		if ( isset( $args['tagtype'] ) ) {
			$tagType = $args['tagtype'];
		} else {
			$tagType = $wgRefbaseDefaultTagType;
		}
		if ( ! ( strtolower( $tagType ) === 'serial' ) &&
		     ! ( strtolower( $tagType ) === 'citekey' ) ) {
			$errStr = wfMessage( 'refbase-error-tagtype' )->text();
			return self::makeErrorOutputString( $errStr );
		}
		if ( isset( $args['output'] ) ) {
			$outputType = $args['output'];
		} else {
			$outputType = $wgRefbaseDefaultOutputType;
		}
		if ( ! ( strtolower( $outputType ) === 'cite_journal' ) &&
		     ! ( strtolower( $outputType ) === 'link' ) &&
		     ! ( strtolower( $outputType ) === 'cite' ) ) {
			$errStr = wfMessage( 'refbase-error-outputtype' )->text();
			return self::makeErrorOutputString( $errStr );
		}
		if ( isset( $args['citationtype'] ) ) {
			$citationType = $args['citationtype'];
		} else {
			$citationType = $wgRefbaseDefaultCitationType;
		}
		if ( ! ( strtolower( $citationType ) === 'minimal' ) &&
		     ! ( strtolower( substr( $citationType, 0, 3 ) ) === 'rb-' ) ) {
			$errStr = wfMessage( 'refbase-error-citation-type' )->text();
			return self::makeErrorOutputString( $errStr );
		}

		// Order tag types
		switch ( strtolower( $tagType ) ) {
		case 'serial':
			$tagTypeList = array( 'serial', 'citekey' );
			break;
		case 'citekey':
			$tagTypeList = array( 'citekey', 'serial' );
			break;
		}

		// Instantiate renderer based on options
		$refbaseRenderer = RefbaseRenderer::create( $outputType,
		                                            $citationType );
		// Request list of fields to extract
		$fieldList = $refbaseRenderer->getFieldList();

		// Perform database query to get entry
		$refbaseDbConnector = new RefbaseConnector();
		$entry = "";
		if ( !$refbaseDbConnector->getEntry( $input, $tagTypeList, $entry,
		                                     $fieldList ) ) {
			return self::makeErrorOutputString( $entry );
		}

		// Generate output
		$citekey = $input;
		$renderOpts = array( 'citekey' => $citekey );
		$outputStr = "";
		if ( !$refbaseRenderer->render( $entry, $outputStr, $renderOpts ) ) {
			return self::makeErrorOutputString( $outputStr );
		}

		$outputStr = $parser->recursiveTagParse( $outputStr );
		return self::makeOutputString( $outputStr );
	}
}
