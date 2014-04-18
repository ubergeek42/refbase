<?php

/**
 * Refbase entry renderer using citation templates (cite_journal only for now)
 */
class RefbaseRendererCitationTemplate extends RefbaseRenderer {

	/**
	 * Constructor (simply inherit from parent)
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * List fields required to build template
	 */
	public function getFieldList() {
		return array( 'type',
		              'serial',
		              'author',
		              'year',
		              'title',
		              'language',
		              'publication',
		              'volume',
		              'issue',
		              'pages',
		              'place',
		              'publisher',
		              'issn',
		              'doi' );
	}

	/**
	 * Prepare text for template (supports journal articles only)
	 */
	public function render( $entry, & $cite, $options )	{

		$cite = "";
		$ret = true;
		if ( $entry["type"] == "Journal Article" ) {
			$cite .= "{{cite_journal|url=" . $this->refbaseURL . "show.php?";
			$cite .= "record=" . $entry['serial'];
			if( !empty( $entry["author"] ) ) {
				$author = $entry["author"];
				$aulast = RefbaseTools::extractAuthorsLastName
					( " *; *", " *, *", 1, $author );
				$aufirst = RefbaseTools::extractAuthorsGivenName
					( " *; *", " *, *", 1, $author );
				if( !empty( $aulast ) ) {
					$cite .= "|last=" . $aulast;
				}
				if( !empty( $aufirst ) ) {
					$cite .= "|first=" . $aufirst;
					if( !empty( $aulast ) ) {
						$cite .= "|authorlink=$aufirst $aulast";
					}
				}
				$authorcount = count( preg_split( "/ *; */", $author ) );
				$au = "";
				for ( $i=0; $i < $authorcount - 1; $i++ ) {
					$aul = RefbaseTools::extractAuthorsLastName
						( " *; *", " *, *", $i + 2, $author );
					$auf = RefbaseTools::extractAuthorsGivenName
						( " *; *", " *, *", $i + 2, $author );
					if ( !empty( $aul ) ) {
						if ( !empty( $auf ) ) {
							$au .= "[[$auf $aul|$aul, $auf]]; ";
						}
					}
				}
				if ( !empty( $au ) ) {
					$cite .= "|coauthors=" . trim( $au, '; ' );
				}
			}
			if( !empty( $entry["year"] ) ) {
				$cite .= "|year=" . $entry['year'];
			}
			if( !empty( $entry["title"] ) ) {
				$title = RefbaseTools::searchReplaceText( $entry['title'],
				                                          true );
				$cite .= "|title=" . $title;
			}
			if( !empty( $entry["language"] ) )
				$cite .= "|language=" . $entry['language'];
			if( !empty( $entry["publication"] ) )
				$cite .= "|journal=" . $entry['publication'];
			if( !empty( $entry["volume"] ) )
				$cite .= "|volume=" . $entry['volume'];
			if( !empty( $entry["issue"] ) )
				$cite .= "|issue=" . $entry['issue'];
			if( !empty( $entry["pages"] ) )
				$cite .= "|pages=" . $entry['pages'];
			if( !empty( $entry["place"] ) )
				$cite .= "|location=" . $entry['place'];
			if( !empty( $entry["publiser"] ) )
				$cite .= "|publisher=" . $entry['publisher'];
			if( !empty( $entry["issn"] ) )
				$cite .= "|issn=" . $entry['issn'];
			if( !empty( $entry["doi"] ) )
				$cite .= "|doi=" . $entry['doi'];
			$cite .= "}}";
			$ret &= true;
		} else {
			$cite .= wfMessage( 'refbase-error-cite_journal-type' )->text();
			$ret &= false;
		}
		return $ret;
	}

}

