<?php
  // Project:    Web Reference Database (refbase) <http://www.refbase.net>
  // Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
  //             original author.
  //
  //             This code is distributed in the hope that it will be useful,
  //             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
  //             License for more details.
  //
  // File:       ./includes/modsxml.inc.php
  // Author:     Richard Karnesky <mailto:karnesky@northwestern.edu>
  //
  // Created:    02-Oct-04, 12:00
  // Modified:   21-Mar-05, 20:37

  // This include file contains functions that'll export records to MODS XML.
  // Requires ActiveLink PHP XML Package, which is available under the GPL from:
  // <http://www.active-link.com/software/>


  // Import the ActiveLink Packages
  require_once("classes/include.php");
  import("org.active-link.xml.XML");
  import("org.active-link.xml.XMLDocument");


  // For more on MODS, see:
  //   <http://www.loc.gov/standards/mods/>
  //   <http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html>

  // TODO:
  //   Stuff in '// NOTE' comments
  //   There's a lot of overlap in the portions that depend on types.  I plan
  //     on refactoring this, so that they can make calls to the same function.

  // I don't know what to do with some fields
  // See <http://www.loc.gov/standards/mods/v3/mods-3-0-outline.html>
  //   - Require clever parsing
  //     - address (?name->affiliation?)
  //     - medium  (?typeOfResource?)
  //   - Don't know how refbase users use these
  //     - corporate_author (could be either an affiliation or a separate name)
  //     - area (could be either topic or geographic, so we do nothing)
  //     - expedition
  //   - Can't find a place in MODS XML
  //     - file


  // --------------------------------------------------------------------

  // Generates relatedItem branch for series
  function serialBranch($series_editor, $series_title, $abbrev_series_title,
                        $series_volume, $series_issue) {
    $series = new XMLBranch("relatedItem");
    $series->setTagAttribute("type", "host");

    // title
    if (!empty($series_title))
      $series->setTagContent($series_title, "relatedItem/titleInfo/title");

    // abbrev. title
    if (!empty($abbrev_series_title)) {
      $titleabbrev = NEW XMLBranch("titleInfo");
      $titleabbrev->setTagAttribute("type", "abbreviated");
      $titleabbrev->setTagContent($abbrev_series_title, "titleInfo/title");
      $series->addXMLBranch($titleabbrev);
    }

    // editor
    if (!empty($series_editor)) {
      if (ereg(" *\(eds?\)$", $series_editor))
        $series_editor = ereg_replace("[ \r\n]*\(eds?\)", "", $series_editor);
      $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $series_editor,
                                 "personal", "editor");
      foreach ($nameArray as $singleName)
        $series->addXMLBranch($singleName);
    }

    // volume, issue
    if ((!empty($series_volume)) || (!empty($series_issue))) {
      $part = new XMLBranch("part");
      if (!empty($series_volume)) {
        $detailvolume = new XMLBranch("detail");
        $detailvolume->setTagContent($series_volume, "detail/number");
        $detailvolume->setTagAttribute("type", "volume");
        $part->addXMLBranch($detailvolume);
      }
      if (!empty($series_issue)) {
        $detailnumber = new XMLBranch("detail");
        $detailnumber->setTagContent($series_issue, "detail/number");
        $detailnumber->setTagAttribute("type", "number");
        $part->addXMLBranch($detailnumber);
      }
      $series->addXMLBranch($part);
    }

    return $series;
  }

  // Separates people's names and then those names into their functional parts:
  //   {{Family1,{Given1-1,Given1-2}},{Family2,{Given2}}})
  // Adds these to an array of XMLBranches.
  function separateNames($betweenNamesDelim, $nameGivenDelim,
                         $betweenGivensDelim, $names, $type, $role) {
    $nameArray = array();
    $nameArray = preg_split($betweenNamesDelim, $names); // get a list of all authors
    foreach ($nameArray as $singleName){
      $nameBranch = new XMLBranch("name");
      $nameBranch->setTagAttribute("type", $type);

      list($singleNameFamily, $singleNameGivens) = preg_split($nameGivenDelim,
                                                              $singleName);

      $nameFamilyBranch = new XMLBranch("namePart");
      $nameFamilyBranch->setTagAttribute("type", "family");
      $nameFamilyBranch->setTagContent($singleNameFamily);
      $nameBranch->addXMLBranch($nameFamilyBranch);

      $singleNameGivenArray = split($betweenGivensDelim, $singleNameGivens);
      foreach ($singleNameGivenArray as $singleNameGiven) {
        $nameGivenBranch = new XMLBranch("namePart");
        $nameGivenBranch->setTagAttribute("type", "given");
        $nameGivenBranch->setTagContent($singleNameGiven);
        $nameBranch->addXMLBranch($nameGivenBranch);
      }

      $nameBranch->setTagContent($role,"name/role/roleTerm");
      $nameBranch->setTagAttribute("authority", "marcrelator",
                                   "name/role/roleTerm");
      $nameBranch->setTagAttribute("type", "text", "name/role/roleTerm");

      array_push($nameArray, $nameBranch);
    }
    return $nameArray;
  }

  // --------------------------------------------------------------------

  function modsCollection($result) {
 
    global $contentTypeCharset; // defined in 'ini.inc.php'

    // Individual records are objects and collections of records are strings

    $exportArray = array(); // Array for individually exported records

    // Generate the export for each record and push them onto an array:
    while ($row = @ mysql_fetch_array($result)) {
      // Define an array of search & replace actions (to ensure that special
      // characters are encoded with their entities)
      // (Note that the order of array elements IS important since it defines
      //  when a search/replace action gets executed)
      $exportSearchReplacePatterns = array("&" => "&amp;",
                                           "<" => "&lt;",
                                           ">"  => "&gt;");

      // Perform search & replace actions on each of the fields:
      foreach ($row as $rowFieldName => $rowFieldValue)
        // function 'searchReplaceText()' is defined in 'include.inc.php'
        $row[$rowFieldName] = searchReplaceText($exportSearchReplacePatterns,
                                                $row[$rowFieldName]); 

      // Note: except from the above conversion of angle brackets (i.e., '<'
      //       and '>') and ampersands ('&'), data will be exported as fetched
      //       from the MySQL database, i.e., there's NO conversion of:
      //        - higher ASCII chars
      //        - "human readable markup" that's used within plain text fields
      //          of the database to define rich text characters like italics,
      //          etc. (see '$markupSearchReplacePatterns' in 'ini.inc.php')

      $record = modsRecord($row); // Export the current record as MODS XML

      if (!empty($record)) // unless the record buffer is empty...
        array_push($exportArray, $record); // ...add it to an array of exports
    }

    $modsCollectionDoc = new XMLDocument();
    $modsCollectionDoc->setEncoding($contentTypeCharset);
    $modsCollection = new XML("modsCollection");
    $modsCollection->setTagAttribute("xmlns", "http://www.loc.gov/mods/v3");
    foreach ($exportArray as $mods) 
      $modsCollection->addXMLasBranch($mods);
    $modsCollectionDoc->setXML($modsCollection);
    $modsCollectionString = $modsCollectionDoc->getXMLString();

    return $modsCollectionString;
  }

  // --------------------------------------------------------------------

  // Returns an XML object (mods) of a single record
  function modsRecord($row) {
    $exportPrivate = True;  // This will be a global variable or will be used
                            // when modsRow is called and will determine if we
                            // export user-specific data

    // --- BEGIN TYPE * ---
    //   | These apply to everything

    // Create an XML object for a single record.
    $record = new XML("mods");
    if (!empty($row['cite_key']))
      $record->setTagAttribute("ID", $row['cite_key']);

    // titleInfo
    //   Regular Title
    if (!empty($row['title']))
      $record->setTagContent($row['title'], "mods/titleInfo/title");

    //   Translated Title
    //   NOTE: This field is excluded by the default cite SELECT method
    if (!empty($row['orig_title'])) {
      $orig_title = new XMLBranch("titleInfo");
      $orig_title->setTagAttribute("type", "translated");
      $orig_title->setTagContent($row['orig_title'], "titleInfo/title");
      $record->addXMLBranch($orig_title);
    }

    // name
    //   author
    if (!empty($row['author'])) {
      if (ereg(" *\(eds?\)$", $row['author'])) {
        $author = ereg_replace("[ \r\n]*\(eds?\)", "", $row['author']);
        $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $author, "personal",
                                   "editor");
      }
      else if ($row['type'] == "Map") {
        $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $row['author'], "personal",
                                   "cartographer");
      }
      else {
        $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $row['author'], "personal",
                                   "author");
      }
      foreach ($nameArray as $singleName) {
        $record->addXMLBranch($singleName);
      }
    }
    //   conference
    if (!empty($row['conference'])) { 
      $nameBranch = new XMLBranch("name");
      $nameBranch->setTagAttribute("type", "conference");
      $nameBranch->setTagContent($row['conference']);
      $record->addXMLBranch($nameBranch);
    }

    // originInfo
    if ((!empty($row['year'])) || (!empty($row['publisher'])) ||
         (!empty($row['place']))) {
      $origin = new XMLBranch("originInfo");

      // dateIssued
      if (!empty($row['year']))
        $origin->setTagContent($row['year'], "originInfo/dateIssued");

      // Book Chapters and Journal Articles only have a dateIssued
      // (editions, places, and publisers are associated with the host)
      if (!ereg("Book Chapter|Journal Article", $row['type'])) {
        // publisher
        if (!empty($row['publisher']))
          $origin->setTagContent($row['publisher'], "originInfo/publisher");
        // place
        if (!empty($row['place'])) {
          $origin->setTagContent($row['place'], "originInfo/place/placeTerm");
          $origin->setTagAttribute("type", "text",
                                   "originInfo/place/placeTerm");
        }
        // edition
        if (!empty($row['edition']))
          $origin->setTagContent($row['edition'], "originInfo/edition");
      }

      if ($origin->hasBranch())
        $record->addXMLBranch($origin);
    }

    // language
    if (!empty($row['language']))
      $record->setTagContent($row['language'], "mods/language");

    // abstract
    // NOTE: This field is excluded by the default cite SELECT method
    if (!empty($row['abstract'])) {
      $abstract = new XMLBranch("abstract");
      $abstract->setTagContent($row['abstract']);
      if (!empty($row['summary_language'])) {
        $abstract->setTagAttribute("lang", $row['summary_language']);
      }
      $record->addXMLBranch($abstract);
    }

    // subject
    //   keywords
    if (!empty($row['keywords'])) {
      $subjectArray = array();
      $subjectArray = preg_split("/\s*;\s*/", $row['keywords']); // "unrelated" keywords
      foreach ($subjectArray as $singleSubject) {
        $subjectBranch = new XMLBranch("subject");

        $topicArray = array();
        $topicArray = preg_split("/\s*,\s*/", $singleSubject); // "related" keywords
        foreach ($topicArray as $singleTopic) {
          $topicBranch = new XMLBranch("topic");
          $topicBranch->setTagContent($singleTopic);

          $subjectBranch->addXMLBranch($topicBranch);
        }
        $record->addXMLBranch($subjectBranch);
      }
    }
    //   userkeys
    //   NOTE: a copy of the above.  Needs to be a separate function later.
    if ((!empty($row['user_keys'])) && $exportPrivate) {
      $subjectArray = array();
      $subjectArray = preg_split("/\s*;\s*/", $row['user_keys']); // "unrelated" keywords
      foreach ($subjectArray as $singleSubject) {
        $subjectBranch = new XMLBranch("subject");

        $topicArray = array();
        $topicArray = preg_split("/\s*,\s*/", $singleSubject); // "related" keywords
        foreach ($topicArray as $singleTopic) {
          $topicBranch = new XMLBranch("topic");
          $topicBranch->setTagContent($singleTopic);

          $subjectBranch->addXMLBranch($topicBranch);
        }
        $record->addXMLBranch($subjectBranch);
      }
    }
    //   user_groups
    //   NOTE: a copy of the above.  Needs to be a separate function later.
    if ((!empty($row['user_groups'])) && $exportPrivate) {
      $subjectArray = array();
      $subjectArray = preg_split("/\s*;\s*/", $row['user_groups']); // "unrelated" keywords
      foreach ($subjectArray as $singleSubject) {
        $subjectBranch = new XMLBranch("subject");

        $topicArray = array();
        $topicArray = preg_split("/\s*,\s*/", $singleSubject); // "related" keywords
        foreach ($topicArray as $singleTopic) {
          $topicBranch = new XMLBranch("topic");
          $topicBranch->setTagContent($singleTopic);

          $subjectBranch->addXMLBranch($topicBranch);
        }
        $record->addXMLBranch($subjectBranch);
      }
    }
 
    // notes
    if (!empty($row['notes']))
      $record->setTagContent($row['notes'], "mods/note");
    // user_notes
    if ((!empty($row['user_notes'])) && $exportPrivate)
      $record->setTagContent($row['user_notes'], "mods/note");

    // typeOfResource
    // maps are 'cartographic' and everything else is 'text'
    $type = new XMLBranch("typeOfResource");
    if ($row['type'] == "Map") {
      $type->setTagContent("cartographic");
    }
    else {
      $type->setTagContent("text");
    }
    if ($row['type'] == "Manuscript") {
      $type->setTagAttribute("manuscript", "yes");
    }
    $record->addXMLBranch($type);

    // location
    //   Physical Location
    //   NOTE: This field is excluded by the default cite SELECT method
    //         This should also be parsed later
    if (!empty($row['location'])) {
      $location = new XMLBranch("location");
      $locationArray = array();
      $locationArray = preg_split("/\s*;\s*/", $row['location']);
      foreach ($locationArray as $singleLocation) {
        $locationBranch = new XMLBranch("physicalLocation");
        $locationBranch->setTagContent($singleLocation);
        $location->addXMLBranch($locationBranch);
      }
      $record->addXMLBranch($location);
    }
    //   URL (also an identifier)
    //   NOTE: This field is excluded by the default cite SELECT method
    if (!empty($row['url'])) {
      $location = new XMLBranch("location");
      $location->setTagContent($row['url'], "location/url");

      $identifier = new XMLBranch("identifier");
      $identifier->setTagContent($row['url']);
      $identifier->setTagAttribute("type", "uri");

      $record->addXMLBranch($location);
      $record->addXMLBranch($identifier);
    }

    // identifier
    //   doi
    if (!empty($row['doi'])) {
      $identifier = new XMLBranch("identifier");
      $identifier->setTagContent($row['doi']);
      $identifier->setTagAttribute("type", "doi");
      $record->addXMLBranch($identifier);
    }
    //   cite_key
    if (!empty($row['cite_key'])) {
      $identifier = new XMLBranch("identifier");
      $identifier->setTagContent($row['cite_key']);
      $identifier->setTagAttribute("type", "citekey");
      $record->addXMLBranch($identifier);
    }
    //   local--CALL NUMBER
    //   NOTE: This should really be parsed!
    if (!empty($row['call_number'])) {
      $identifierArray = array();
      $identifierArray = preg_split("/\s*;\s*/", $row['call_number']);
      foreach ($identifierArray as $singleIdentifier) {
        $identifierBranch = new XMLBranch("identifier");
        $identifierBranch->setTagContent($singleIdentifier);
        $identifierBranch->setTagAttribute("type","local");
        $record->addXMLBranch($identifierBranch);
      }
    }

    // --- END TYPE * ---


    // --- BEGIN TYPE != BOOK CHAPTER || JOURNAL ARTICLE ---
    //   | BOOK WHOLE, JOURNAL, MANUSCRIPT, and MAP have some info as a branch
    //   | off the root, where as BOOK CHAPTER and JOURNAL ARTICLE place it in
    //   | the relatedItem branch.

    if (!ereg("Book Chapter|Journal Article", $row['type'])) {
      // name
      //   editor
      if (!empty($row['editor'])) {
        $editor=$row['editor'];
	$author=$row['author'];
        if (ereg(" *\(eds?\)$", $editor))
          $editor = ereg_replace("[ \r\n]*\(eds?\)", "", $editor);
        if (ereg(" *\(eds?\)$", $author))
          $author = ereg_replace("[ \r\n]*\(eds?\)", "", $author);
        if ($editor != $author) {
          $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $editor,
                                     "personal", "editor");
          foreach ($nameArray as $singleName)
            $record->addXMLBranch($singleName);
        }
      }

      // genre
      //   type
      //      NOTE: Is there a better MARC genre[1] for 'manuscript?'
      //            [1]<http://www.loc.gov/marc/sourcecode/genre/genrelist.html>
      $genremarc = new XMLBranch("genre");
      $genre = new XMLBranch("genre");
      $genremarc->setTagAttribute("authority", "marc");

      if ($row['type'] == "Book Whole") {
        $genremarc->setTagContent("book");
      }
      else if ($row['type'] == "Journal") {
        $genremarc->setTagContent("periodical");
        $genre->setTagContent("academic journal");
      }
      else if ($row['type'] == "Manuscript") {
        $genremarc->setTagContent("loose-leaf");
        $genre->setTagContent("manuscript");
      }
      else if ($row['type'] == "Map") {
        $genremarc->setTagContent("map");
      }
      else if (!empty($row['type'])) { // catch-all: don't use a MARC genre
        $genre->setTagContent($row['type']);
      }
      if ($genremarc->hasLeaf())
        $record->addXMLBranch($genremarc);
      if ($genre->hasLeaf())
        $record->addXMLBranch($genre);
      //   thesis
      if (!empty($row['thesis'])) {
        $thesismarc = new XMLBranch("genre");
        $thesis = new XMLBranch("genre");

        $thesismarc->setTagContent("theses");
        $thesismarc->setTagAttribute("authority", "marc");

        $thesis->setTagContent($row['thesis']);

        $record->addXMLBranch($thesismarc);
        $record->addXMLBranch($thesis);
      }

      // physicalDescription
      //   pages
      if (!empty($row['pages'])) {
        $description = new XMLBranch("physicalDescription");
        $pages = new XMLBranch("extent");
        $pages->setTagAttribute("unit", "page");
        if (ereg("[0-9] *- *[0-9]", $row['pages'])) { // if a page range
          // split the page range into start and end pages
          list($pagestart, $pageend) = preg_split('/\s*[-]\s*/', $row['pages']);
          if ($pagestart < $pageend) { // extents MUST span multiple pages
            $pages->setTagContent($pagestart, "extent/start");
            $pages->setTagContent($pageend, "extent/end");
          }
          else {
            $pages->setTagContent($row['pages']);
          }
        }
        else if (ereg("[0-9] *pp?", $row['pages'])) { // if a number of pages
          list($pagetotal) = preg_split('/\s*pp?/', $row['pages']);
          $pages->setTagContent($pagetotal, "extent/total");
        }          
        else {
          $pages->setTagContent($row['pages']);
        }
        $description->addXMLBranch($pages);
        $record->addXMLBranch($description);
      }

      // identifier
      //   isbn
      if (!empty($row['isbn'])) {
        $identifier = new XMLBranch("identifier");
        $identifier->setTagContent($row['isbn']);
        $identifier->setTagAttribute("type", "isbn");
        $record->addXMLBranch($identifier);
      }
      //   issn
      if (!empty($row['issn'])) {
        $identifier = new XMLBranch("identifier");
        $identifier->setTagContent($row['issn']);
        $identifier->setTagAttribute("type", "issn");
        $record->addXMLBranch($identifier);
      }

      // series
      if ((!empty($row['series_editor'])) || (!empty($row['series_title'])) ||
          (!empty($row['abbrev_series_title'])) ||
          (!empty($row['series_volume'])) || (!empty($row['series_issue']))) {
        $record->addXMLBranch(serialBranch($row['series_editor'],
                                           $row['series_title'],
                                           $row['abbrev_series_title'],
                                           $row['series_volume'],
                                           $row['series_issue']));
      }
    }

    // --- END TYPE != BOOK CHAPTER || JOURNAL ARTICLE ---


    // --- BEGIN TYPE == BOOK CHAPTER || JOURNAL ARTICLE ---
    //   | NOTE: These are currently the only types that have publication,
    //   |       abbrev_journal, volume, and issue added.
    //   | A lot of info goes into the relatedItem branch

    else { //if (ereg("Book Chapter|Journal Article", $row['type']))
      // relatedItem
      $related = new XMLBranch("relatedItem");
      $related->setTagAttribute("type", "host");

      // title (Publication)
      if (!empty($row['publication']))
        $related->setTagContent($row['publication'],
                                "relatedItem/titleInfo/title");

      // title (Abbreviated Journa)
      if (!empty($row['abbrev_journal'])) {
        $titleabbrev = NEW XMLBranch("titleInfo");
        $titleabbrev->setTagAttribute("type", "abbreviated");
        $titleabbrev->setTagContent($row['abbrev_journal'], "titleInfo/title");
        $related->addXMLBranch($titleabbrev);
      }

      // originInfo
      $relorigin = new XMLBranch("originInfo");
      // dateIssued
      if (!empty($row['year']))
        $relorigin->setTagContent($row['year'],"originInfo/dateIssued");
      // publisher
      if (!empty($row['publisher']))
        $relorigin->setTagContent($row['publisher'], "originInfo/publisher");
      // place
      if (!empty($row['place'])) {
        $relorigin->setTagContent($row['place'], "originInfo/place/placeTerm");
        $relorigin->setTagAttribute("type", "text",
                                    "originInfo/place/placeTerm");
      }
      // edition
      if (!empty($row['edition']))
        $relorigin->setTagContent($row['edition'], "originInfo/edition");
      if ($relorigin->hasBranch())
        $related->addXMLBranch($relorigin);

      if ($row['type'] == "Journal Article") {
        $related->setTagContent("continuing",
                                "relatedItem/originInfo/issuance");
        $genremarc = new XMLBranch("genre");
        $genre = new XMLBranch("genre");
      
        $genremarc->setTagContent("periodical");
        $genremarc->setTagAttribute("authority", "marc");

        $genre->setTagContent("academic journal");

        $related->addXMLBranch($genremarc);
        $related->addXMLBranch($genre);
      }
      else { //if ($row['type'] == "Book Chapter")
        $related->setTagContent("book", "relatedItem/genre");
        $related->setTagAttribute("authority", "marc", "relatedItem/genre");
      }
      //   thesis
      if (!empty($row['thesis'])) {
        $thesismarc = new XMLBranch("genre");
        $thesis = new XMLBranch("genre");

        $thesismarc->setTagContent("theses");
        $thesismarc->setTagAttribute("authority", "marc");

        $thesis->setTagContent($row['thesis']);

        $related->addXMLBranch($thesismarc);
        $related->addXMLBranch($thesis);
      }

      if ((!empty($row['year'])) || (!empty($row['volume'])) ||
          (!empty($row['issue'])) || (!empty($row['pages']))) {
        $part = new XMLBranch("part");
        if (!empty($row['year']))
          $part->setTagContent($row['year'], "date");
        if (!empty($row['volume'])) {
          $detailvolume = new XMLBranch("detail");
          $detailvolume->setTagContent($row['volume'], "detail/number");
          $detailvolume->setTagAttribute("type", "volume");
          $part->addXMLBranch($detailvolume);
        }
        if (!empty($row['issue'])) {
          $detailnumber = new XMLBranch("detail");
          $detailnumber->setTagContent($row['issue'], "detail/number");
          $detailnumber->setTagAttribute("type", "number");
          $part->addXMLBranch($detailnumber);
        }
        if (!empty($row['pages'])) {
          $pages = new XMLBranch("extent");
          if (ereg("[0-9] *- *[0-9]", $row['pages'])) { // if a page range
            // split the page range into start and end pages
            list($pagestart, $pageend) = preg_split('/\s*[-]\s*/', $row['pages']);
            if ($pagestart < $pageend) { // extents MUST span multiple pages
              $pages->setTagAttribute("unit", "page");
              $pages->setTagContent($pagestart, "extent/start");
              $pages->setTagContent($pageend, "extent/end");
            }
            else {
              $part->setTagContent($row['pages'], "part/detail/number");
              $part->setTagAttribute("type", "page", "part/detail");
            }
          }
          else {
            $part->setTagContent($row['pages'], "part/detail/number");
            $part->setTagAttribute("type", "page", "part/detail");
          }
          $part->addXMLBranch($pages);
        }
        $related->addXMLBranch($part);
      }

      // identifier
      //   isbn
      if (!empty($row['isbn'])) {
        $identifier = new XMLBranch("identifier");
        $identifier->setTagContent($row['isbn']);
        $identifier->setTagAttribute("type", "isbn");
        $related->addXMLBranch($identifier);
      }
      //   issn
      if (!empty($row['issn'])) {
        $identifier = new XMLBranch("identifier");
        $identifier->setTagContent($row['issn']);
        $identifier->setTagAttribute("type", "issn");
        $related->addXMLBranch($identifier);
      }
      // name
      //   editor
      if (!empty($row['editor']))
        $nameArray = separateNames("/\s*;\s*/", "/\s*,\s*/", " ", $row['editor'], "personal",
                                   "editor");

      // series
      if ((!empty($row['series_editor'])) || (!empty($row['series_title'])) ||
          (!empty($row['abbrev_series_title'])) ||
          (!empty($row['series_volume'])) || (!empty($row['series_issue']))) {
        $related->addXMLBranch(serialBranch($row['series_editor'],
                                            $row['series_title'],
                                            $row['abbrev_series_title'],
                                            $row['series_volume'],
                                            $row['series_issue']));
      }

      $record->addXMLBranch($related);
    }

    // --- END TYPE == BOOK CHAPTER || JOURNAL ARTICLE ---


    return $record;
  }
?>
