<?php

/**
 * Refbase hooks and parser
 */
class RefbaseHooks {

	/**
	 * Register <timelinetable> hook
	 */
	public static function efRefbaseParserInit( $parser ) {
		$parser->setHook( 'refbase',
		                  'RefbaseHooks::efRefbaseRender' );
		return true;
	}

	/**
	 * After tidy
	 */
	public static function efRefbaseAfterTidy( & $parser, & $text ) {
		// find markers in $text
		// replace markers with actual output
		global $markerList;
		for ( $i = 0; $i < count( $markerList ); $i++ ) {
			$text = preg_replace( '/xx-marker'.$i.'-xx/',
			                      $markerList[$i], $text );
		}
		return true;
	}

	/**
	 * Define the html code as a marker, then change it back to text in
	 * 'efTimelineAfterTidy'. This is done to prevent the html code from being
	 * modified afterwards.
	 */
	private static function makeOutputString ( $str ) {
		global $markerList;
		$makercount = count( $markerList );
		$marker = "xx-marker" . $makercount . "-xx";
		$markerList[$makercount] = $str;
		return $marker;
	}

	/**
	 * Add <pre></pre> tags around error message and return
	 */
	private static function makeErrorOutputString ( $errMsg ) {
		$errMsg = "Refbase: <br/>" . $errMsg;
		return self::makeOutputString( "<pre>" . $errMsg . "</pre>" );
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

		// Read arguments
		if ( isset( $args['tagtype'] ) ) {
			$tagType = $args['tagtype'];
		} else {
			$tagType = $wgRefbaseDefaultTagType;
		}
		if ( !strtolower( $tagType ) === 'serial' &&
		     !strtolower( $tagType ) === 'citekey' ) {
			$errStr = wfMessage( 'refbase-error-tagtype' );
			return self::makeErrorOutputString( $errStr );
		}
		if ( isset( $args['outputtype'] ) ) {
			$outputType = $args['outputtype'];
		} else {
			$outputType = $wgRefbaseDefaultOutputType;
		}
		if ( !strtolower( $outputType ) === 'cite' &&
		     !strtolower( $outputType ) === 'footnote' ) {
			$errStr = wfMessage( 'refbase-error-outputtype' );
			return self::makeErrorOutputString( $errStr );
		}

		// Perform database query to get entry
		$refbaseDbConnector = new RefbaseConnector();
		$entry = "";
		if ( !$refbaseDbConnector->getEntry( $input, $tagType, $entry ) ) {
			return self::makeErrorOutputString( $entry );
		}

		// Generate output
		$refbaseRenderer = new RefbaseRenderer();
		$citekey = ( strtolower( $tagType ) === 'citekey' ) ? $input : "";
		$outputStr = $refbaseRenderer->render( $entry, $outputType, $citekey );

		$outputStr = $parser->recursiveTagParse( $outputStr );
		return self::makeOutputString( $outputStr );
	}
}
