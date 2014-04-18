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

	/// Method to access database (mysql or PDO)
	private $dbAccessMethod = "";

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
		global $wgRefbaseDbAccessMethod;

		// Read from global configuration
		$this->dbHost          = $wgRefbaseDbHost;
		$this->dbName          = $wgRefbaseDbName;
		$this->dbUser          = $wgRefbaseDbUser;
		$this->dbPass          = $wgRefbaseDbPass;
		$this->dbRefTable      = $wgRefbaseDbRefTable;
		$this->dbUserDataTable = $wgRefbaseDbUserDataTable;
		$this->dbCharset       = $wgRefbaseDbCharset;
		$this->dbAccessMethod  = $wgRefbaseDbAccessMethod;
	}

	/**
	 * Query by serial number or cite key entry
	 */
	public function getEntry( $input, $tagTypeList, & $outputEntry,
	                          $fieldList ) {

		// List of fields to extract (prefix 'r.' to each element)
		$fieldPref = $fieldList;
		array_walk( $fieldPref, function ( &$value, $key) {
				$value="r.$value";
			} );
		$fieldPref = join(",", $fieldPref);

		$flagFound = false;
		for ( $i = 0; $i < count($tagTypeList) && ! $flagFound; $i++ ) {

			$tagType = $tagTypeList[$i];

			// Query string
			$queryStr = "";
			if ( $tagType === 'citekey' ) {
				$queryStr = "SELECT $fieldPref " .
					"FROM " . $this->dbRefTable . " r " .
					"INNER JOIN " . $this->dbUserDataTable . " u " .
					"ON r.serial = u.data_id " .
					"WHERE u.cite_key='$input'";
			} else {
				$queryStr = "SELECT $fieldPref " .
					"FROM " . $this->dbRefTable . " r " .
					"WHERE r.serial='$input'";
			}

			if ( strtolower( $this->dbAccessMethod ) === 'pdo' ) {

				// Connect and query
				$link = new PDO( 'mysql:host=' . $this->dbHost . ';dbname=' .
				                 $this->dbName . ';charset=' . $this->dbCharset,
				                 $this->dbUser, $this->dbPass );
				$dbexec = $link->prepare( $queryStr );

				try {
					// Perform query
					$outputEntry = $dbexec->execute();
				} catch( PDOException $ex ) {
					$outputEntry = wfMessage( 'refbase-error-dbquery' )->text() .
						$ex->getMessage();
					return false;
				}

				$outputEntry = $dbexec->fetch(PDO::FETCH_ASSOC);

			} elseif ( strtolower( $this->dbAccessMethod ) === 'mysql' ) {

				$link = mysql_connect( $this->dbHost, $this->dbUser,
				                       $this->dbPass ) or die("db error");
				if ( !$link ) {
					$outputEntry = wfMessage( 'refbase-error-mysqlconn' )->text();
					return false;
				}

				if ( !mysql_select_db( $this->dbName, $link ) ) {
					$outputEntry = wfMessage( 'refbase-error-mysqldb' )->text() .
						mysql_error();
					return false;
				}

				$result = mysql_query( $queryStr );
				if ( !$result ) {
					$outputEntry = wfMessage( 'refbase-error-dbquery' )->text() .
						mysql_error();
					return false;
				}
				$outputEntry = mysql_fetch_array($result);

			}
			if ( !empty( $outputEntry ) ) {
				$flagFound = true;
			}
		}

		if ( empty( $outputEntry ) ) {
			$outputEntry = wfMessage( 'refbase-error-notfound' )->text();
			return false;
		}

		return true;
	}


}

