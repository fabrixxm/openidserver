<?php

/**
 * Friendica SQL-backed OpenID stores.
 *
 * @package OpenIDServer
 * @author Fabio Comuni <fabrixxm@kirgroup.com>
 * @copyright 2013 Fabio Comuni
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache
 */

/**
 * @access private
 */
require_once 'Auth/OpenID/Interface.php';
require_once 'Auth/OpenID/Nonce.php';

/**
 * @access private
 */
require_once 'Auth/OpenID.php';

/**
 * @access private
 */
require_once 'Auth/OpenID/Nonce.php';

class FriendicaStore extends Auth_OpenID_OpenIDStore {


	function FriendicaStore($associations_table = null,
								  $nonces_table = null)
	{
		global $db;
		
		$this->connection = $db;
		
		$this->associations_table_name = "oid_associations";
		$this->nonces_table_name = "oid_nonces";


		if (!is_null($associations_table)) {
			$this->associations_table_name = $associations_table;
		}

		if (!is_null($nonces_table)) {
			$this->nonces_table_name = $nonces_table;
		}

		$this->max_nonce_age = 6 * 60 * 60;

		// Create an empty SQL strings array.
		$this->sql = array();

		// Call this method (which should be overridden by subclasses)
		// to populate the $this->sql array with SQL strings.
		$this->setSQL();

		// Verify that all required SQL statements have been set, and
		// raise an error if any expected SQL strings were either
		// absent or empty.
		list($missing, $empty) = $this->_verifySQL();

		if ($missing) {
			trigger_error("Expected keys in SQL query list: " .
						  implode(", ", $missing),
						  E_USER_ERROR);
			return;
		}

		if ($empty) {
			trigger_error("SQL list keys have no SQL strings: " .
						  implode(", ", $empty),
						  E_USER_ERROR);
			return;
		}
	}

	function tableExists($table_name)
	{
		$r = q("SELECT * FROM %s LIMIT 0", $table_name,$this->resultToBool());
		return $this->resultToBool();
	}


	/**
	 * Retrurn true if las query don't resulted in error
	 */
	function resultToBool()
	{
		return $this->connection->error == false;
	}

	/**
	 * This method should be overridden by subclasses.  This method is
	 * called by the constructor to set values in $this->sql, which is
	 * an array keyed on sql name.
	 */
    function setSQL()
    {
    	$assoc_table = $this->associations_table_name;
    	$nonce_table = $this->nonces_table_name;
    	
        $this->sql['nonce_table'] =
            "CREATE TABLE $nonce_table (\n".
            "  server_url VARCHAR(2047) NOT NULL,\n".
            "  timestamp INTEGER NOT NULL,\n".
            "  salt CHAR(40) NOT NULL,\n".
            "  UNIQUE (server_url(255), timestamp, salt)\n".
            ") ENGINE=InnoDB";

        $this->sql['assoc_table'] =
            "CREATE TABLE $assoc_table (\n".
            "  server_url VARCHAR(2047) NOT NULL,\n".
            "  handle VARCHAR(255) NOT NULL,\n".
            "  secret BLOB NOT NULL,\n".
            "  issued INTEGER NOT NULL,\n".
            "  lifetime INTEGER NOT NULL,\n".
            "  assoc_type VARCHAR(64) NOT NULL,\n".
            "  PRIMARY KEY (server_url(255), handle)\n".
            ") ENGINE=InnoDB";

        $this->sql['set_assoc'] =
            "REPLACE INTO $assoc_table (server_url, handle, secret, issued,\n".
            "  lifetime, assoc_type) VALUES ('%s', '%s', %s, %d, %d, '%s')";

        $this->sql['get_assocs'] =
            "SELECT handle, secret, issued, lifetime, assoc_type FROM $assoc_table ".
            "WHERE server_url = '%s'";

        $this->sql['get_assoc'] =
            "SELECT handle, secret, issued, lifetime, assoc_type FROM $assoc_table ".
            "WHERE server_url = '%s' AND handle = '%s'";

        $this->sql['remove_assoc'] =
            "DELETE FROM $assoc_table WHERE server_url = '%s' AND handle = '%s'";

        $this->sql['add_nonce'] =
            "INSERT INTO $nonce_table (server_url, timestamp, salt) VALUES ('%s', %d, '%s')";

        $this->sql['clean_nonce'] =
            "DELETE FROM $nonce_table WHERE timestamp < %d";

        $this->sql['clean_assoc'] =
            "DELETE FROM $assoc_table WHERE issued + lifetime < %d";
    }


	/**
	 * Resets the store by removing all records from the store's
	 * tables.
	 */
	function reset()
	{
		q("DELETE FROM %s", $this->associations_table_name);

		q("DELETE FROM %s", $this->nonces_table_name);
	}

	/**
	 * @access private
	 */
	function _verifySQL()
	{
		$missing = array();
		$empty = array();

		$required_sql_keys = array(
								   'nonce_table',
								   'assoc_table',
								   'set_assoc',
								   'get_assoc',
								   'get_assocs',
								   'remove_assoc'
								   );

		foreach ($required_sql_keys as $key) {
			if (!array_key_exists($key, $this->sql)) {
				$missing[] = $key;
			} else if (!$this->sql[$key]) {
				$empty[] = $key;
			}
		}

		return array($missing, $empty);
	}

	/**
	 * @access private
	 */
	function _fixSQL()
	{
		$replacements = array(
							  array(
									'value' => $this->nonces_table_name,
									'keys' => array('nonce_table',
													'add_nonce',
													'clean_nonce')
									),
							  array(
									'value' => $this->associations_table_name,
									'keys' => array('assoc_table',
													'set_assoc',
													'get_assoc',
													'get_assocs',
													'remove_assoc',
													'clean_assoc')
									)
							  );

		foreach ($replacements as $item) {
			$value = $item['value'];
			$keys = $item['keys'];

			foreach ($keys as $k) {
				if (is_array($this->sql[$k])) {
					foreach ($this->sql[$k] as $part_key => $part_value) {
						$this->sql[$k][$part_key] = sprintf($part_value,
															$value);
					}
				} else {
					$this->sql[$k] = sprintf($this->sql[$k], $value);
				}
			}
		}
	}

	function blobDecode($blob)
	{
		return $blob;
	}

    function blobEncode($blob)
    {
        return "0x" . bin2hex($blob);
    }

	function createTables()
	{
		$n = $this->create_nonce_table();
		$a = $this->create_assoc_table();

		if ($n && $a) {
			return true;
		} else {
			return false;
		}
	}

	function create_nonce_table()
	{
		if (!$this->tableExists($this->nonces_table_name)) {
			$r = q($this->sql['nonce_table']);
			return $this->resultToBool();
		}
		return true;
	}

	function create_assoc_table()
	{
		if (!$this->tableExists($this->associations_table_name)) {
			$r = q($this->sql['assoc_table']);
			return $this->resultToBool();
		}
		return true;
	}

	/**
	 * @access private
	 */
	function _set_assoc($server_url, $handle, $secret, $issued,
						$lifetime, $assoc_type)
	{
		return q($this->sql['set_assoc'],
					dbesc($server_url),
					dbesc($handle),
					dbesc($secret),
					intval($issued),
					intval($lifetime),
					dbesc($assoc_type)
		);
	}

	function storeAssociation($server_url, $association)
	{
		$this->_set_assoc(
					$server_url,
					$association->handle,
					$this->blobEncode($association->secret),
					$association->issued,
					$association->lifetime,
					$association->assoc_type
		);
		if ($this->resultToBool()) {
			// TODO: $this->connection->commit();
		} else {
			// TODO: $this->connection->rollback();
		}
	}

	/**
	 * @access private
	 */
	function _get_assoc($server_url, $handle)
	{
		$r = q($this->sql['get_assoc'], dbesc($server_url), dbesc($handle));
		if (count($r)>0) {
			return $r[0];
		} else {
		 return null;
		}
	}

	/**
	 * @access private
	 */
	function _get_assocs($server_url)
	{
		return q($this->sql['get_assocs'], dbesc($server_url));
	}

	function removeAssociation($server_url, $handle)
	{
		if ($this->_get_assoc($server_url, $handle) == null) {
			return false;
		}


		$r = q($this->sql['remove_assoc'], dbesc($server_url), dbesc($handle));
		if ($this->resultToBool()) {
			// TODO: $this->connection->commit();
		} else {
			// TODO: $this->connection->rollback();
		}

		return true;
	}

	function getAssociation($server_url, $handle = null)
	{
		if ($handle !== null) {
			$assoc = $this->_get_assoc($server_url, $handle);

			$assocs = array();
			if ($assoc) {
				$assocs[] = $assoc;
			}
		} else {
			$assocs = $this->_get_assocs($server_url);
		}

		if (!$assocs || (count($assocs) == 0)) {
			return null;
		} else {
			$associations = array();

			foreach ($assocs as $assoc_row) {
				$assoc = new Auth_OpenID_Association($assoc_row['handle'],
													 $assoc_row['secret'],
													 $assoc_row['issued'],
													 $assoc_row['lifetime'],
													 $assoc_row['assoc_type']);

				$assoc->secret = $this->blobDecode($assoc->secret);

				if ($assoc->getExpiresIn() == 0) {
					$this->removeAssociation($server_url, $assoc->handle);
				} else {
					$associations[] = array($assoc->issued, $assoc);
				}
			}

			if ($associations) {
				$issued = array();
				$assocs = array();
				foreach ($associations as $key => $assoc) {
					$issued[$key] = $assoc[0];
					$assocs[$key] = $assoc[1];
				}

				array_multisort($issued, SORT_DESC, $assocs, SORT_DESC,
								$associations);

				// return the most recently issued one.
				list($issued, $assoc) = $associations[0];
				return $assoc;
			} else {
				return null;
			}
		}
	}

	/**
	 * @access private
	 */
	function _add_nonce($server_url, $timestamp, $salt)
	{
		$sql = $this->sql['add_nonce'];
		$result = $q($sql, dbesc($server_url),intval($timestamp), dbesc($salt));
		if ($this->resultToBool()) {
			// TODO: $this->connection->rollback();
		} else {
			// TODO:$this->connection->commit();
		}
		return $this->resultToBool();
	}

	function useNonce($server_url, $timestamp, $salt)
	{
		global $Auth_OpenID_SKEW;

		if ( abs($timestamp - time()) > $Auth_OpenID_SKEW ) {
			return false;
		}

		return $this->_add_nonce($server_url, $timestamp, $salt);
	}

	/**
	 * "Octifies" a binary string by returning a string with escaped
	 * octal bytes.  This is used for preparing binary data for
	 * PostgreSQL BYTEA fields.
	 *
	 * @access private
	 */
	function _octify($str)
	{
		$result = "";
		for ($i = 0; $i < Auth_OpenID::bytes($str); $i++) {
			$ch = substr($str, $i, 1);
			if ($ch == "\\") {
				$result .= "\\\\\\\\";
			} else if (ord($ch) == 0) {
				$result .= "\\\\000";
			} else {
				$result .= "\\" . strval(decoct(ord($ch)));
			}
		}
		return $result;
	}

	/**
	 * "Unoctifies" octal-escaped data from PostgreSQL and returns the
	 * resulting ASCII (possibly binary) string.
	 *
	 * @access private
	 */
	function _unoctify($str)
	{
		$result = "";
		$i = 0;
		while ($i < strlen($str)) {
			$char = $str[$i];
			if ($char == "\\") {
				// Look to see if the next char is a backslash and
				// append it.
				if ($str[$i + 1] != "\\") {
					$octal_digits = substr($str, $i + 1, 3);
					$dec = octdec($octal_digits);
					$char = chr($dec);
					$i += 4;
				} else {
					$char = "\\";
					$i += 2;
				}
			} else {
				$i += 1;
			}

			$result .= $char;
		}

		return $result;
	}

	function cleanupNonces()
	{
		global $Auth_OpenID_SKEW;
		$v = time() - $Auth_OpenID_SKEW;

		$r = q($this->sql['clean_nonce'], intval($v));
		$num = 0; // TODO FIXME get # affected rows
		return $num;
	}

	function cleanupAssociations()
	{
		$r = q($this->sql['clean_assoc'],intval(time()));
		$num = 0; // TODO FIXME get # affected rows
		return $num;
	}
}


