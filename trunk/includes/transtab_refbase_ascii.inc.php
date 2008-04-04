<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_refbase_ascii.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    28-May-06, 18:24
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search & replace patterns for conversion from refbase markup to plain text. Removes refbase fontshape markup (italic, bold)
	// as well as markup for super- and subscript or greek letters from the text. Adopt to your needs if necessary.
	// Search & replace patterns must be specified as perl-style regular expression and search patterns must include the leading & trailing slashes.

	$transtab_refbase_ascii = array(

		"/_(.+?)_/"            =>  "\\1",
		"/\\*\\*(.+?)\\*\\*/"  =>  "\\1",
		"/\\[super:(.+?)\\]/i" =>  "\\1",
		"/\\[sub:(.+?)\\]/i"   =>  "\\1",
		"/\\[permil\\]/"       =>  "per mille",
		"/\\[infinity\\]/"     =>  "infinity",
		"/\\[alpha\\]/"        =>  "alpha",
		"/\\[beta\\]/"         =>  "beta",
		"/\\[gamma\\]/"        =>  "gamma",
		"/\\[delta\\]/"        =>  "delta",
		"/\\[epsilon\\]/"      =>  "epsilon",
		"/\\[zeta\\]/"         =>  "zeta",
		"/\\[eta\\]/"          =>  "eta",
		"/\\[theta\\]/"        =>  "theta",
		"/\\[iota\\]/"         =>  "iota",
		"/\\[kappa\\]/"        =>  "kappa",
		"/\\[lambda\\]/"       =>  "lambda",
		"/\\[mu\\]/"           =>  "mu",
		"/\\[nu\\]/"           =>  "nu",
		"/\\[xi\\]/"           =>  "xi",
		"/\\[omicron\\]/"      =>  "omicron",
		"/\\[pi\\]/"           =>  "pi",
		"/\\[rho\\]/"          =>  "rho",
		"/\\[sigmaf\\]/"       =>  "sigmaf",
		"/\\[sigma\\]/"        =>  "sigma",
		"/\\[tau\\]/"          =>  "tau",
		"/\\[upsilon\\]/"      =>  "upsilon",
		"/\\[phi\\]/"          =>  "phi",
		"/\\[chi\\]/"          =>  "chi",
		"/\\[psi\\]/"          =>  "psi",
		"/\\[omega\\]/"        =>  "omega",
		"/\\[Alpha\\]/"        =>  "Alpha",
		"/\\[Beta\\]/"         =>  "Beta",
		"/\\[Gamma\\]/"        =>  "Gamma",
		"/\\[Delta\\]/"        =>  "Delta",
		"/\\[Epsilon\\]/"      =>  "Epsilon",
		"/\\[Zeta\\]/"         =>  "Zeta",
		"/\\[Eta\\]/"          =>  "Eta",
		"/\\[Theta\\]/"        =>  "Theta",
		"/\\[Iota\\]/"         =>  "Iota",
		"/\\[Kappa\\]/"        =>  "Kappa",
		"/\\[Lambda\\]/"       =>  "Lambda",
		"/\\[Mu\\]/"           =>  "Mu",
		"/\\[Nu\\]/"           =>  "Nu",
		"/\\[Xi\\]/"           =>  "Xi",
		"/\\[Omicron\\]/"      =>  "Omicron",
		"/\\[Pi\\]/"           =>  "Pi",
		"/\\[Rho\\]/"          =>  "Rho",
		"/\\[Sigma\\]/"        =>  "Sigma",
		"/\\[Tau\\]/"          =>  "Tau",
		"/\\[Upsilon\\]/"      =>  "Upsilon",
		"/\\[Phi\\]/"          =>  "Phi",
		"/\\[Chi\\]/"          =>  "Chi",
		"/\\[Psi\\]/"          =>  "Psi",
		"/\\[Omega\\]/"        =>  "Omega",
		"/�/"                  =>  "-"

	);

?>