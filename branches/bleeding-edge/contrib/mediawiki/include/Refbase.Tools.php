<?php

/**
 * Refbase helper functions (cleanup strings, author extraction)
 */
class RefbaseTools {


	/// Character translation table
	private static $transtab_refbase_html = array(
		"/__(?!_)(.+?)__/"     =>  "<u>\\1</u>", // the pattern for underline (__...__) must come before the one for italic (_..._)
		"/_(.+?)_/"            =>  "<i>\\1</i>",
		"/\\*\\*(.+?)\\*\\*/"  =>  "<b>\\1</b>",
		"/\\[super:(.+?)\\]/i" =>  "<sup>\\1</sup>",
		"/\\[sub:(.+?)\\]/i"   =>  "<sub>\\1</sub>",
		"/\\[permil\\]/"       =>  "&permil;",
		"/\\[infinity\\]/"     =>  "&infin;",
		"/\\[alpha\\]/"        =>  "&alpha;",
		"/\\[beta\\]/"         =>  "&beta;",
		"/\\[gamma\\]/"        =>  "&gamma;",
		"/\\[delta\\]/"        =>  "&delta;",
		"/\\[epsilon\\]/"      =>  "&epsilon;",
		"/\\[zeta\\]/"         =>  "&zeta;",
		"/\\[eta\\]/"          =>  "&eta;",
		"/\\[theta\\]/"        =>  "&theta;",
		"/\\[iota\\]/"         =>  "&iota;",
		"/\\[kappa\\]/"        =>  "&kappa;",
		"/\\[lambda\\]/"       =>  "&lambda;",
		"/\\[mu\\]/"           =>  "&mu;",
		"/\\[nu\\]/"           =>  "&nu;",
		"/\\[xi\\]/"           =>  "&xi;",
		"/\\[omicron\\]/"      =>  "&omicron;",
		"/\\[pi\\]/"           =>  "&pi;",
		"/\\[rho\\]/"          =>  "&rho;",
		"/\\[sigmaf\\]/"       =>  "&sigmaf;",
		"/\\[sigma\\]/"        =>  "&sigma;",
		"/\\[tau\\]/"          =>  "&tau;",
		"/\\[upsilon\\]/"      =>  "&upsilon;",
		"/\\[phi\\]/"          =>  "&phi;",
		"/\\[chi\\]/"          =>  "&chi;",
		"/\\[psi\\]/"          =>  "&psi;",
		"/\\[omega\\]/"        =>  "&omega;",
		"/\\[Alpha\\]/"        =>  "&Alpha;",
		"/\\[Beta\\]/"         =>  "&Beta;",
		"/\\[Gamma\\]/"        =>  "&Gamma;",
		"/\\[Delta\\]/"        =>  "&Delta;",
		"/\\[Epsilon\\]/"      =>  "&Epsilon;",
		"/\\[Zeta\\]/"         =>  "&Zeta;",
		"/\\[Eta\\]/"          =>  "&Eta;",
		"/\\[Theta\\]/"        =>  "&Theta;",
		"/\\[Iota\\]/"         =>  "&Iota;",
		"/\\[Kappa\\]/"        =>  "&Kappa;",
		"/\\[Lambda\\]/"       =>  "&Lambda;",
		"/\\[Mu\\]/"           =>  "&Mu;",
		"/\\[Nu\\]/"           =>  "&Nu;",
		"/\\[Xi\\]/"           =>  "&Xi;",
		"/\\[Omicron\\]/"      =>  "&Omicron;",
		"/\\[Pi\\]/"           =>  "&Pi;",
		"/\\[Rho\\]/"          =>  "&Rho;",
		"/\\[Sigma\\]/"        =>  "&Sigma;",
		"/\\[Tau\\]/"          =>  "&Tau;",
		"/\\[Upsilon\\]/"      =>  "&Upsilon;",
		"/\\[Phi\\]/"          =>  "&Phi;",
		"/\\[Chi\\]/"          =>  "&Chi;",
		"/\\[Psi\\]/"          =>  "&Psi;",
		"/\\[Omega\\]/"        =>  "&Omega;",
		"/(?:\"|&quot;)(.+?)(?:\"|&quot;)/" => "&ldquo;\\1&rdquo;",
		"/ +- +/"              =>  " &#8211; "
		);

	// EXTRACT AUTHOR'S LAST NAME
	// this function takes the contents of the author field and will extract the last name of a particular author (specified by position)
	// (e.g., setting '$authorPosition' to "1" will return the 1st author's last name)
	//  Note: this function assumes that:
	//        1. within one author object, there's only *one* delimiter separating author name & initials!
	//        2. author objects are stored in the db as "<author_name><author_initials_delimiter><author_initials>", i.e., initials follow *after* the author's name!
	//  Required Parameters:
	//        1. pattern describing delimiter that separates different authors
	//        2. pattern describing delimiter that separates author name & initials (within one author)
	//        3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name)
	//        4. contents of the author field
	public static function extractAuthorsLastName( $oldBetweenAuthorsDelim, $oldAuthorsInitialsDelim, $authorPosition, $authorContents ) {
		$authorsArray = preg_split( "/" . $oldBetweenAuthorsDelim . "/", $authorContents ); // get a list of all authors for this record
		$authorPosition = $authorPosition - 1; // php array elements start with "0", so we decrease the authors position by 1
		$singleAuthor = $authorsArray[$authorPosition]; // for the author in question, extract the full author name (last name & initials)
		$singleAuthorArray = preg_split( "/" . $oldAuthorsInitialsDelim . "/", $singleAuthor ); // then, extract author name & initials to separate list items
		$singleAuthorsLastName = $singleAuthorArray[0]; // extract this author's last name into a new variable
		return $singleAuthorsLastName;
	}

	// EXTRACT AUTHOR'S GIVEN NAME
	// this function takes the contents of the author field and will extract the given name of a particular author (specified by position)
	// (e.g., setting '$authorPosition' to "1" will return the 1st author's given name)
	//  Required Parameters:
	//        1. pattern describing delimiter that separates different authors
	//        2. pattern describing delimiter that separates author name & initials (within one author)
	//        3. position of the author whose last name shall be extracted (e.g., "1" will return the 1st author's last name)
	//        4. contents of the author field
	public static function extractAuthorsGivenName( $oldBetweenAuthorsDelim, $oldAuthorsInitialsDelim, $authorPosition, $authorContents ) {
		$authorsArray = preg_split( "/" . $oldBetweenAuthorsDelim . "/", $authorContents ); // get a list of all authors for this record
		$authorPosition = $authorPosition - 1; // php array elements start with "0", so we decrease the authors position by 1
		$singleAuthor = $authorsArray[$authorPosition]; // for the author in question, extract the full author name (last name & initials)
		$singleAuthorArray = preg_split( "/" . $oldAuthorsInitialsDelim . "/", $singleAuthor ); // then, extract author name & initials to separate list items
		if ( !empty($singleAuthorArray[1]) ) {
			$singleAuthorsGivenName = $singleAuthorArray[1]; // extract this author's last name into a new variable
		} else {
			$singleAuthorsGivenName = '';
		}
		return $singleAuthorsGivenName;
	}

	// Perform search & replace actions on the given text input:
	// ('$includesSearchPatternDelimiters' must be a boolean value that specifies whether the leading and trailing slashes
	//  are included within the search pattern ['true'] or not ['false'])
	public static function searchReplaceText( $sourceString, $includesSearchPatternDelimiters ) {
		// apply the search & replace actions defined in '$transtab_refbase_html' to the text passed in '$sourceString':
		foreach ( self::$transtab_refbase_html as $searchString => $replaceString ) {
			if ( !$includesSearchPatternDelimiters ) {
				$searchString = "/" . $searchString . "/"; // add search pattern delimiters
			}
			if ( preg_match($searchString, $sourceString ) ) {
				$sourceString = preg_replace( $searchString, $replaceString, $sourceString );
			}
		}
		return $sourceString;
	}


}

