<?php

/**
 * Refbase database connector
 */
class RefbaseConnector {

	/// Database location
	private $dbHost = "";

	/// Database name
	private $dbName = "";

	/// Database user
	private $dbUser = "";

	/// Database password
	private $dbPass = "";

	/// Character set
	private $dbCharset = "";

	/// Reference table
	private $dbRefTable = "";

	/// User data table (for cite key entry)
	private $dbUserDataTable = "";

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wgRefbaseDbHost;
		global $wgRefbaseDbName;
		global $wgRefbaseDbUser;
		global $wgRefbaseDbPass;
		global $wgRefbaseDbRefTable;
		global $wgRefbaseDbUserDataTable;
		global $wgRefbaseDbCharset;

		// Read from global configuration
		$this->dbHost          = $wgRefbaseDbHost;
		$this->dbName          = $wgRefbaseDbName;
		$this->dbUser          = $wgRefbaseDbUser;
		$this->dbPass          = $wgRefbaseDbPass;
		$this->dbRefTable      = $wgRefbaseDbRefTable;
		$this->dbUserDataTable = $wgRefbaseDbUserDataTable;
		$this->dbCharset       = $wgRefbaseDbCharset;
	}

	/**
	 * Query by serial number or cite key entry
	 */
	public function getEntry( $input, $tagType, & $outputEntry ) {

		// List of field to extract
		$field_list = "r.type, r.author, r.title, r.year, r.publication, " .
		              "r.volume, r.issue, r.pages, r.publisher, r.place, " .
		              "r.language, r.issn, r.doi, r.serial";

		// Query string
		$queryStr = "";
		if ( $tagType === 'citekey' ) {
			$queryStr = "SELECT $field_list " .
			            "FROM " . $this->dbRefTable . " r " .
			            "INNER JOIN " . $this->dbUserDataTable . " u " .
			            "ON r.serial = u.data_id " .
			            "WHERE u.cite_key='$input'";
		} else {
			$queryStr = "SELECT $field_list" .
			            "FROM " . $this->dbRefTable . " r " .
			            "WHERE r.serial='$input'";
		}

		// Connect and query
		$link = new PDO( 'mysql:host=' . $this->dbHost . ';dbname=' .
		                 $this->dbName . ';charset=' . $this->dbCharset,
		                 $this->dbUser, $this->dbPass );
		try {
			// Perform query
			$outputEntry = $link->query( $queryStr );
		} catch( PDOException $ex ) {
			$outputEntry = wfMessage( 'refbase-error-dbquery' ) .
			               $ex->getMessage();
			return false;
		}

		if ( empty( $outputEntry ) ) {
			$outputEntry = wfMessage( 'refbase-error-notfound' );
			return false;
		}

		return true;
	}


}

