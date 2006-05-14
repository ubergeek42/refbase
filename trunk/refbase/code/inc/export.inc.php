<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the function's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/export.inc.php
	// Created:    09-May-06, 15:34
	// Modified:   10-May-06, 00:44

	// This file contains functions
	// that are used when exporting
	// records from the database.

	// --------------------------------------------------------------------

	// This function takes a BibTeX file (as generated by bibutils) and
	// converts any contained refbase markup into proper LaTeX/BibTeX markup:
	function standardizeBibtexOutput($bibtexSourceText)
	{
		// We convert refbase fontshape markup (italic, bold) into LaTeX commands of the 'textcomp' package,
		// super- and subscript as well as greek symbols get converted into the respective commands in math mode.
		// You may need to adopt the LaTeX markup to something that suits your individual needs:
		//									"refbase markup"       =>  'LaTeX markup'
		$markupSearchReplacePatterns = array("/\\\\_(.+?)\\\\_/"   =>  '\\textit{\\1}',  // or use '\\it{\\1}' (the backslashes before the substrings were inserted by bibutils: '_word_' gets '\_word\_')
											"/\\*\\*(.+?)\\*\\*/"  =>  '\\textbf{\\1}',  // or use '\\bf{\\1}'
											"/\\[super:(.+?)\\]/i" =>  '$^{\\1}$', // or use '\\textsuperscript{\\1}'
											"/\\[sub:(.+?)\\]/i"   =>  '$_{\\1}$', // or use '\\textsubscript{\\1}' if defined in your package
											"/\\[permil\\]/"       =>  '{\\textperthousand}',
											"/\\[infinity\\]/"     =>  '$\\infty$',
											"/\\[alpha\\]/"        =>  '$\\alpha$',
											"/\\[beta\\]/"         =>  '$\\beta$',
											"/\\[gamma\\]/"        =>  '$\\gamma$',
											"/\\[delta\\]/"        =>  '$\\delta$',
											"/\\[epsilon\\]/"      =>  '$\\epsilon$',
											"/\\[zeta\\]/"         =>  '$\\zeta$',
											"/\\[eta\\]/"          =>  '$\\eta$',
											"/\\[theta\\]/"        =>  '$\\theta$',
											"/\\[iota\\]/"         =>  '$\\iota$',
											"/\\[kappa\\]/"        =>  '$\\kappa$',
											"/\\[lambda\\]/"       =>  '$\\lambda$',
											"/\\[mu\\]/"           =>  '$\\mu$',
											"/\\[nu\\]/"           =>  '$\\nu$',
											"/\\[xi\\]/"           =>  '$\\xi$',
											"/\\[omicron\\]/"      =>  '$o$',
											"/\\[pi\\]/"           =>  '$\\pi$',
											"/\\[rho\\]/"          =>  '$\\rho$',
											"/\\[sigmaf\\]/"       =>  '$\\varsigma$',
											"/\\[sigma\\]/"        =>  '$\\sigma$',
											"/\\[tau\\]/"          =>  '$\\tau$',
											"/\\[upsilon\\]/"      =>  '$\\upsilon$',
											"/\\[phi\\]/"          =>  '$\\phi$',
											"/\\[chi\\]/"          =>  '$\\chi$',
											"/\\[psi\\]/"          =>  '$\\psi$',
											"/\\[omega\\]/"        =>  '$\\omega$',
											"/\\[Alpha\\]/"        =>  '$A$',
											"/\\[Beta\\]/"         =>  '$B$',
											"/\\[Gamma\\]/"        =>  '$\\Gamma$',
											"/\\[Delta\\]/"        =>  '$\\Delta$',
											"/\\[Epsilon\\]/"      =>  '$E$',
											"/\\[Zeta\\]/"         =>  '$Z$',
											"/\\[Eta\\]/"          =>  '$H$',
											"/\\[Theta\\]/"        =>  '$\\Theta$',
											"/\\[Iota\\]/"         =>  '$I$',
											"/\\[Kappa\\]/"        =>  '$K$',
											"/\\[Lambda\\]/"       =>  '$\\Lambda$',
											"/\\[Mu\\]/"           =>  '$M$',
											"/\\[Nu\\]/"           =>  '$N$',
											"/\\[Xi\\]/"           =>  '$\\Xi$',
											"/\\[Omicron\\]/"      =>  '$O$',
											"/\\[Pi\\]/"           =>  '$\\Pi$',
											"/\\[Rho\\]/"          =>  '$R$',
											"/\\[Sigma\\]/"        =>  '$\\Sigma$',
											"/\\[Tau\\]/"          =>  '$T$',
											"/\\[Upsilon\\]/"      =>  '$\\Upsilon$',
											"/\\[Phi\\]/"          =>  '$\\Phi$',
											"/\\[Chi\\]/"          =>  '$X$',
											"/\\[Psi\\]/"          =>  '$\\Psi$',
											"/\\[Omega\\]/"        =>  '$\\Omega$',
											"/^(?=(URL|LOCATION|NOTE|KEYWORDS)=)/mi" =>  'opt');

		// Perform above search & replace actions on the given BibTeX text:
		$bibtexSourceText = searchReplaceText($markupSearchReplacePatterns, $bibtexSourceText, true); // function 'searchReplaceText()' is defined in 'include.inc.php'


		return $bibtexSourceText;
	}

	// --------------------------------------------------------------------
?>
