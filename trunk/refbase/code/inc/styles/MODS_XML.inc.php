<?php
  // Project:    Web Reference Database (refbase) <http://www.refbase.net>
  // Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
  //             original author.
  //
  //             This code is distributed in the hope that it will be useful,
  //             but WITHOUT ANY WARRANTY.  Please see the GNU General Public
  //             License for more details.
  //
  // File:       mods.inc.php
  // Author:     Richard Karnesky <mailto:karnesky@northwestern.edu>
  //
  // Created:    02-Oct-04
  // Modified:   04-Oct-04

  // This exports MODS XML.  It should be moved to a file or directory to be
  // included by  any cite/export functions which rely on it.
  // It is based on the citeRecord functions used by this project.

  //  Requires ActiveLink PHP XML Package, which is available under the GPL from:
  //    <http://www.active-link.com/software/>


  // Import the ActiveLink Packages
  require_once("classes/include.php");
  import("org.active-link.xml.XML");
  import("org.active-link.xml.XMLDocument");
  
  // For more on MODS, see:
  //   <http://www.loc.gov/standards/mods/>
  //   <http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html>

  // TODO:
  //   Stuff in '// NOTE' comments
  //   citekey
  //   There's a lot of overlap in the portions that depend on types.  I plan
  //     on refactoring this, so that they can make calls to the same function.

  // I need to add these fields:
  //   series_editor
  //   series_title
  //   abbrev_series_title
  //   series_volume
  //   series_issue

  // I don't know what to do with some fields
  // See <http://www.loc.gov/standards/mods/v3/mods-3-0-outline.html>
  //   address--This could be name->affiliation, but it would need to be parsed
  //            in a smart mannter
  //   corporate_author
  //   keywords
  //   medium
  //   summary_language
  //   area
  //   thesis
  //   expedition
  //   conference
  //   file
  //   notes
  //   ALL USER Fields


  // --------------------------------------------------------------------

  // Separates people's names and then those names into their functional parts:
  //   {{Family1,{Given1-1,Given1-2}},{Family2,{Given2}}})
  // Adds these to an array of XMLBranches.
  function separateNames($betweenNamesDelim, $nameGivenDelim, $betweenGivensDelim, $names, $type, $role)
  {
    $nameArray = array();
    $nameArray = split($betweenNamesDelim, $names); // get a list of all authors for this record
    foreach ($nameArray as $singleName)
      {
        $nameBranch = new XMLBranch("name");
        $nameBranch->setTagAttribute("type",$type);

        list($singleNameFamily, $singleNameGivens) = split($nameGivenDelim, $singleName);

        $nameFamilyBranch = new XMLBranch("namePart");
        $nameFamilyBranch->setTagAttribute("type","family");
        $nameFamilyBranch->setTagContent($singleNameFamily);
        $nameBranch->addXMLBranch($nameFamilyBranch);

        $singleNameGivenArray = split($betweenGivensDelim,$singleNameGivens);
        foreach ($singleNameGivenArray as $singleNameGiven)
          {
            $nameGivenBranch = new XMLBranch("namePart");
            $nameGivenBranch->setTagAttribute("type","given");
            $nameGivenBranch->setTagContent($singleNameGiven);
            $nameBranch->addXMLBranch($nameGivenBranch);
          }

        $nameBranch->setTagContent($role,"name/role/roleTerm");
        $nameBranch->setTagAttribute("authority","marcrelator","name/role/roleTerm");
        $nameBranch->setTagAttribute("type","text","name/role/roleTerm");

        array_push($nameArray,$nameBranch);
      }
    return $nameArray;
  }

  // --------------------------------------------------------------------


  // --- BEGIN CITATION STYLE ---

  // NOTE: There is currently no mechanism in the cite styles to format groups
  //       of records.  Therefore, the individual records WON'T be contained in
  //       <modsCollection />, as they should be.  We should either  modify the
  //       cite styles to allow custom headers and footers or to handle sets of
  //       references.
  function citeRecord($row, $citeStyle)
  {
    $recordDoc = new XMLDocument();
    $recordDoc->setXML(modsRecord($row));
    $cite = $recordDoc->getXMLString();

    // Process the XML string so that it can be displayed on a webpage
    $cite = str_replace ('<?xml version="1.0" encoding="UTF-8" ?>', '', $cite);
    $cite = str_replace ('<', '&lt;', $cite);
    $cite = str_replace ('>', '&gt;', $cite);
    $cite = "<PRE>".$cite."</PRE>";
    
    return $cite;
  }

  // --- END CITATION STYLE ---

  // --- BEGIN EXPORT STYLE ---

  // Right now, individual records are objects and collections of records are
  // strings
  function exportRecord($row, $exportStyle)
  {
    $mods = modsRecord($row);
    return $mods;
  }
  function exportCollection($exportArray, $exportStyle)
  {
  $modsCollectionDoc = new XMLDocument();
  $modsCollection = new XML("modsCollection");
  $modsCollection->setTagAttribute("xmlns","http://www.loc.gov/mods/v3");
  foreach ($exportArray as $mods)
    {
      $modsCollection->addXMLasBranch($mods);
    }
  $modsCollectionDoc->setXML($modsCollection);
  $exportText = $modsCollectionDoc->getXMLString();
  
  return $exportText;
}
  

  // --- END CITATION STYLE ---
  // Returns an XML object (mods) of a single record
  function modsRecord($row)
  {
    // --- BEGIN TYPE * ---
    //   | These apply to everything

    // Create an XML object for a single record.
    // Later, we might add an ID attribute (e.g. a cite key)
    $record = new XML("mods");
                              

    // titleInfo
    //   Regular Title
    if (!empty($row['title']))
      {
        $record->setTagContent($row['title'],"mods/titleInfo/title");
      }
    //   Translated Title
    //   NOTE: This field is excluded by the default cite SELECT method
    if (!empty($row['orig_title']))
      {
        $orig_title = new XMLBranch("titleInfo");
        $orig_title->setTagAttribute("type","translated");
        $orig_title->setTagContent($row['orig_title'],"titleInfo/title");
        $record->addXMLBranch($orig_title); // No check for subbranches--should be filled.
      }
        
    // name
    //   author
    if (!empty($row['author']))
      {
        if (ereg(" *\(eds?\)$",$row['author']))
          {
            $author = ereg_replace("[ \r\n]*\(eds?\)", "", $row['author']);
            $nameArray = separateNames("; ", ", ", " ", $author, "personal", "editor");
          }
        else if ($row['type'] == "Map")
          {
            $nameArray = separateNames("; ", ", ", " ", $row['author'], "personal", "cartographer");
          }
        else
          {
            $nameArray = separateNames("; ", ", ", " ", $row['author'], "personal", "author");
          }
        foreach ($nameArray as $singleName)
          {
             $record->addXMLBranch($singleName);
          }
      }

    // originInfo
    if ((!empty($row['year']))||(!empty($row['publisher']))||(!empty($row['place'])))
      {
        $origin = new XMLBranch("originInfo");

        // dateIssued
        if (!empty($row['year']))
          {
            $origin->setTagContent($row['year'],"originInfo/dateIssued");
          }

        // Book Chapters and Journal Articles only have a dateIssued
        // (editions, places, and publisers are associated with the host)
        if (!ereg("Book Chapter|Journal Article", $row['type']))
          {
            // publisher
            if (!empty($row['publisher']))
              {
                $origin->setTagContent($row['publisher'],"originInfo/publisher");
              }
            // place
            if (!empty($row['place']))
              {
                $origin->setTagContent($row['place'],"originInfo/place/placeTerm");
                $origin->setTagAttribute("type","text","originInfo/place/placeTerm");
              }
            // edition
             if (!empty($row['edition']))
              {
                $origin->setTagContent($row['edition'],"originInfo/edition");
              }
          }

        if ($origin->hasBranch())
          {
            $record->addXMLBranch($origin);
          }
      }

    // language
    if (!empty($row['language']))
      {
        $record->setTagContent($row['language'],"mods/language");
      }

    // abstract
    // NOTE: This field is excluded by the default cite SELECT method
    if (!empty($row['abstract']))
      {
        $record->setTagContent($row['abstract'],"mods/abstract");
      }

    // typeOfResource
    // maps are 'cartographic' and everything else is 'text'
    if ($row['type'] == "Map")
      {
        $record->setTagContent("cartographic","mods/typeOfResource");
      }
    else
      {
        $record->setTagContent("text","mods/typeOfResource");
      }

    // location
    //   Physical Location
    //   NOTE: This field is excluded by the default cite SELECT method
    //         This should also be parsed later
    if (!empty($row['location']))
      {
        $location = new XMLBranch("location");
        $location->setTagContent($row['location'],"location/physicalLocation");
        $record->addXMLBranch($location);
      }
    //   URL (also an identifier)
    //   NOTE: This field is excluded by the default cite SELECT method
    if (!empty($row['url']))
      {
        $location = new XMLBranch("location");
        $location->setTagContent($row['url'],"location/url");
        
        $identifier = new XMLBranch("identifier");
        $identifier->setTagContent($row['url']);
        $identifier->setTagAttribute("type","uri");

        $record->addXMLBranch($location);
        $record->addXMLBranch($identifier);
      }

    // identifier
    //   doi
    if (!empty($row['doi']))
      {
        $identifier = new XMLBranch("identifier");
        $identifier->setTagContent($row['doi']);
        $identifier->setTagAttribute("type","doi");
        $record->addXMLBranch($identifier);
      }
    //   local--CALL NUMBER
    //   NOTE: This should really be parsed!
    if (!empty($row['call_number']))
      {
        $identifier = new XMLBranch("identifier");
        $identifier->setTagContent($row['call_number']);
        $identifier->setTagAttribute("type","local");
        $record->addXMLBranch($identifier);
      }
 
    // --- END TYPE * ---


    // --- BEGIN TYPE != BOOK CHAPTER || JOURNAL ARTICLE ---
    //   | BOOK WHOLE, JOURNAL, MANUSCRIPT, and MAP have some info as a branh
    //   | off the root, where as BOOK CHAPTER and JOURNAL ARTICLE plase it in
    //   | the relatedItem branch.

    if (!ereg("Book Chapter|Journal Article", $row['type']))
      {
        // genre
        // NOTE: Is there a better MARC genre[1] for 'manuscript?'
        //   [1]<http://www.loc.gov/marc/sourcecode/genre/genrelist.html>
        if ($row['type'] == "Book Whole")
          {
            $record->setTagContent("book","mods/genre");
            $record->setTagAttribute("authority","marc","mods/genre");
          }
        else if ($row['type'] == "Journal")
          {
            $genremarc = new XMLBranch("genre");
            $genre = new XMLBranch("genre");

            $genremarc->setTagContent("periodical");
            $genremarc->setTagAttribute("authority","marc");

            $genre->setTagContent("academic journal");
            
            $record->addXMLBranch($genremarc);
            $record->addXMLBranch($genre);
          }
        else if ($row['type'] == "Manuscript")
          {
            $record->setTagContent("loose-leaf","mods/genre");
            $record->setTagAttribute("authority","marc","mods/genre");
          }
        else if ($row['type'] == "Map")
          {
            $record->setTagContent("map","mods/genre");
            $record->setTagAttribute("authority","marc","mods/genre");
          }
        else if (!empty($row['type'])) // catch-all: just don't use a MARC genre
          {
            $record->setTagContent($row['type'],"mods/genre");
          }

        // identifier
        //   isbn
        if (!empty($row['isbn']))
          {
            $identifier = new XMLBranch("identifier");
            $identifier->setTagContent($row['isbn']);
            $identifier->setTagAttribute("type","isbn");
            $record->addXMLBranch($identifier);
          }
        //   issn
        if (!empty($row['issn']))
          {
            $identifier = new XMLBranch("identifier");
            $identifier->setTagContent($row['issn']);
            $identifier->setTagAttribute("type","issn");
            $record->addXMLBranch($identifier);
          }

        // name
        //   editor
        if (!empty($row['editor']))
          {
            $nameArray = separateNames("; ", ", ", " ", $row['editor'], "personal", "cartographer");
          }

      }

    // --- END TYPE != BOOK CHAPTER || JOURNAL ARTICLE ---


    // --- BEGIN TYPE == BOOK CHAPTER || JOURNAL ARTICLE ---
    //   | NOTE: These are currently the only types that have publication,
    //   |       abbrev_journal, volume, issue, pages added.
    //   | A lot of info goes into the relatedItem branch
    
    else //if (ereg("Book Chapter|Journal Article", $row['type']))
      {
        // relatedItem
        $related = new XMLBranch("relatedItem");
        $related->setTagAttribute("type", "host");

        // title (Publication)
        if (!empty($row['publication']))
          {
            $related->setTagContent($row['publication'],"relatedItem/titleInfo/title"); 
          }
        
        // title (Abbreviated Journa)
        if (!empty($row['abbrev_journal']))
          {
            $titleabbrev = NEW XMLBranch("titleInfo");
            $titleabbrev->setTagAttribute("type","abbreviated");
            $titleabbrev->setTagContent($row['abbrev_journal'],"titleInfo/title");
            $related->addXMLBranch($titleabbrev);
          }

        // originInfo
        $relorigin = new XMLBranch("originInfo");

        // dateIssued
        if (!empty($row['year']))
          {
            $relorigin->setTagContent($row['year'],"originInfo/dateIssued");
          }

        // publisher
          if (!empty($row['publisher']))
            {
              $relorigin->setTagContent($row['publisher'],"originInfo/publisher");
            }
        // place
        if (!empty($row['place']))
            {
              $relorigin->setTagContent($row['place'],"originInfo/place/placeTerm");
              $relorigin->setTagAttribute("type","text","originInfo/place/placeTerm");
            }
          // edition
          if (!empty($row['edition']))
            {
              $relorigin->setTagContent($row['edition'],"originInfo/edition");
            }
        if ($relorigin->hasBranch())
          {
            $related->addXMLBranch($relorigin);
          }

        if ($row['type'] == "Journal Article")
          {
            $related->setTagContent("continuing","relatedItem/originInfo/issuance");
            $genremarc = new XMLBranch("genre");
            $genre = new XMLBranch("genre");

            $genremarc->setTagContent("periodical");
            $genremarc->setTagAttribute("authority","marc");

            $genre->setTagContent("academic journal");
            
            $related->addXMLBranch($genremarc);
            $related->addXMLBranch($genre);
          }
        else //if ($row['type'] == "Book Chapter")
          {
            $related->setTagContent("book","relatedItem/genre");
            $related->setTagAttribute("authority","marc","relatedItem/genre");
          }
        if ((!empty($row['year']))||(!empty($row['volume']))||(!empty($row['issue']))||(!empty($row['pages'])))
          {
            $part = new XMLBranch("part");
            if (!empty($row['year']))
              {
                $part->setTagContent($row['year'],"date");
              }
            if (!empty($row['volume']))
              {
                $detailvolume = new XMLBranch("detail");
                $detailvolume->setTagContent($row['volume'],"detail/number");
                $detailvolume->setTagAttribute("type", "volume");
                $part->addXMLBranch($detailvolume);
              }
            if (!empty($row['issue']))
              {
                $detailnumber = new XMLBranch("detail");
                $detailnumber->setTagContent($row['issue'],"detail/number");
                $detailnumber->setTagAttribute("type", "number");
                $part->addXMLBranch($detailnumber);
              }
            if (!empty($row['pages']))
              {
                $pages = new XMLBranch("extent");
                if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
                  {
                    list($pagestart, $pageend) = split('[-]', $row['pages']); // split the page range into start and end pages
                    if ($pagestart < $pageend) // extents MUST span multiple pages
                      {
                        $pages->setTagAttribute("unit","page");
                        $pages->setTagContent($pagestart,"extent/start");
                        $pages->setTagContent($pageend,"extent/end");
                      }
                    else
                      {
                        $part->setTagContent($row['pages'],"part/detail/number");
                        $part->setTagAttribute("type","page","part/detail");
                      }
                  }
                else
                  {
                    $part->setTagContent($row['pages'],"part/detail/number");
                    $part->setTagAttribute("type","page","part/detail");
                  }
                $part->addXMLBranch($pages);
              }
            $related->addXMLBranch($part);
          }
        // identifier
        //   isbn
        if (!empty($row['isbn']))
          {
            $identifier = new XMLBranch("identifier");
            $identifier->setTagContent($row['isbn']);
            $identifier->setTagAttribute("type","isbn");
            $related->addXMLBranch($identifier);
          }
        //   issn
        if (!empty($row['issn']))
          {
            $identifier = new XMLBranch("identifier");
            $identifier->setTagContent($row['issn']);
            $identifier->setTagAttribute("type","issn");
            $related->addXMLBranch($identifier);
          }
        // name
        //   editor
        if (!empty($row['editor']))
          {
            $nameArray = separateNames("; ", ", ", " ", $row['editor'], "personal", "cartographer");
          }

        $record->addXMLBranch($related);
      }

    // --- END TYPE != BOOK CHAPTER || JOURNAL ARTICLE ---


    return $record;
  }
?>
