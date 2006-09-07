<?php
  // Project:    Web Reference Database (refbase) <http://www.refbase.net>
  // Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
  //             original author.
  //
  //             This code is distributed in the hope that it will be useful,
  //             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
  //             License for more details.
  //
  // File:       ./includes/openurl.inc.php
  // Author:     Richard Karnesky <mailto:karnesky@gmail.com>
  //
  // Created:    06-Sep-06, 16:30
  // Modified:   07-Sep-06, 12:00

  // Generate OpenURL and COinS data

  function openURL($row) {
    global $openURLResolver;
    global $hostInstitutionAbbrevName;

    $co = contextObject($row); 
    $co = ereg_replace("rft.", "", $co);
    
    $openURL = "<A HREF=\"" . $openURLResolver . "?ctx_ver=Z39.88-2004" . $co . "&sid=refbase:" . $hostInstitutionAbbrevName . "\"><img src=\"img/xref.gif\" alt=\"openurl\" title=\"find record details (via OpenURL)\" width=\"18\" height=\"20\" hspace=\"0\" border=\"0\"></A>";
    return $openURL;
  }

  function coins($row) {
    // fmt_info (type)
    //
    // 'dissertation' is compatible with the 1.0 spec, but not the 0.1 spec
    if (!empty($row['thesis']))
      $fmt .= "&rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Adissertation";
    else if (ereg("Journal", $row['type']))
      $fmt .= "&rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal";
    else if (ereg("Book", $row['type']))
      $fmt .= "&rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook";
    // 'dc' (dublin core) is compatible with the 1.0 spec, but not the 0.1 spec.
    // We default to this, as it is the most generic type.
    else
      $fmt .= "&rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Adc";
      
    $co = contextObject($row); 
    $co = ereg_replace(" ", "+", $co);

    $coins = "<span class=\"Z3988\" title=\"ctx_ver=Z39.88-2004" . $fmt . $co
           . "\">&nbsp;</span>";
    return $coins;
  }

  function contextObject($row) {
    global $databaseBaseURL;
    foreach ($row as $rowFieldName => $rowFieldValue) {
      $row[$rowFieldName] = encodeHTMLspecialchars($row[$rowFieldName]);
    }

    // rfr_id
    $co .= "&rfr_id=info%3Asid%2F" . ereg_replace("http://", "", $databaseBaseURL);

    // genre (type)
    if ($row['type'] == "Journal Article")
      $co .= "&rft.genre=article";
    else if ($row['type'] == "Book Chapter")
      $co .= "&rft.genre=bookitem";
    else if ($row['type'] == "Book")
      $co .= "&rft.genre=book";
    else if ($row['type'] == "Journal")
      $co .= "&rft.genre=journal";

    // atitle, btitle, title (title, publication)
    if (($row['type'] == "Journal Article") || ($row['type'] == "Book Chapter")){
      if (!empty($row['title']))
        $co .= "&rft.atitle=" . $row['title'];
      if (!empty($row['publication'])) {
        $co .= "&rft.title=" . $row['publication'];
        if ($row['type'] == "Book Chapter")
          $co .= "&rft.btitle=" . $row['publication'];
      }
    }
    else if (!empty($row['title']))
      $co .= "&rft.title=" . $row['title'];
    if (($row['type'] == "Book Whole") && (!empty($row['title'])))
      $co .= "&rft.btitle=" . $row['title'];

    // stitle (abbrev_journal)
    if (!empty($row['abbrev_journal']))
      $co .= "&rft.stitle=" . $row['abbrev_journal'];

    // series (series_title)
    if (!empty($row['series_title']))
      $co .= "&rft.series=" . $row['series_title'];

    // issn
    if (!empty($row['issn']))
      $co .= "&rft.issn=" . $row['issn'];

    // isbn
    if (!empty($row['isbn']))
      $co .= "&rft.isbn=" . $row['isbn'];

    // date (year)
    if (!empty($row['year']))
      $co .= "&rft.date=" . $row['year'];

    // volume
    if (!empty($row['volume']))
      $co .= "&rft.volume=" . $row['volume'];

    // issue
    if (!empty($row['issue']))
      $co .= "&rft.issue=" . $row['issue'];
   
    // spage, epage, tpages (pages)
    // NOTE: lifted from modsxml.inc.php--should throw some into a new include file
    if (!empty($row['pages'])){
      if (ereg("[0-9] *- *[0-9]", $row['pages'])){
        list($pagestart, $pageend) = preg_split('/\s*[-]\s*/', $row['pages']);
        if ($pagestart < $pageend) {
          $co .= "&rft.spage=" . $pagestart;
          $co .= "&rft.epage=" . $pageend;
        }
      }
      else if ($row['type'] == "Book Whole")
        $co .= "&rft.tpages=" . $pages;
      else
        $co .= "&rft.spage=" . $pages;
    }

    // aulast,aufirst (author)
    if (!empty($row['author'])) {
      $author = $row['author'];
      $aulast .= extractAuthorsLastName(" *; *", " *, *", 1, $author);
      $aufirst .= extractAuthorsGivenName(" *; *", " *, *", 1, $author);
      if (!empty($aulast))
        $co .= "&rft.aulast=" . $aulast;
      if (!empty($aufirst))
        $co .= "&rft.aufirst=" . $aufirst;
    }

    // pub (publisher)
    if (!empty($row['publisher']))
      $co .= "&rft.pub=" . $row['publisher'];

    // place
    if (!empty($row['place']))
      $co .= "&rft.place=" . $row['place'];

    // id (doi, url)
    if (!empty($row['doi']))
      $co .= "&rft_id=info:doi/" . $row['doi'];
    else if (!empty($row['url']))
      $co .= "&rft_id=" . $row['url'];

    return $co;
  }
?>
