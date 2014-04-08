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
		foreach ( $entry as $row ) {
			if ( $row["type"] == "Journal Article" ) {
				$cite .= "{{cite_journal|url=" . $this->refbaseURL . "show.php?";
				$cite .= "record=" . $row['serial'];
				if( !empty( $row["author"] ) ) {
					$author = $row["author"];
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
				if( !empty( $row["year"] ) ) {
					$cite .= "|year=" . $row['year'];
				}
				if( !empty( $row["title"] ) ) {
					$title = RefbaseTools::searchReplaceText( $row['title'],
					                                          true );
					$cite .= "|title=" . $title;
				}
				if( !empty( $row["language"] ) )
					$cite .= "|language=" . $row['language'];
				if( !empty( $row["publication"] ) )
					$cite .= "|journal=" . $row['publication'];
				if( !empty( $row["volume"] ) )
					$cite .= "|volume=" . $row['volume'];
				if( !empty( $row["issue"] ) )
					$cite .= "|issue=" . $row['issue'];
				if( !empty( $row["pages"] ) )
					$cite .= "|pages=" . $row['pages'];
				if( !empty( $row["place"] ) )
					$cite .= "|location=" . $row['place'];
				if( !empty( $row["publiser"] ) )
					$cite .= "|publisher=" . $row['publisher'];
				if( !empty( $row["issn"] ) )
					$cite .= "|issn=" . $row['issn'];
				if( !empty( $row["doi"] ) )
					$cite .= "|doi=" . $row['doi'];
				$cite .= "}}";
				$ret &= true;
			} else {
				$cite .= wfMessage( 'refbase-error-cite_journal-type' );
				$ret &= false;
			}
		}
		return $ret;
	}

}

