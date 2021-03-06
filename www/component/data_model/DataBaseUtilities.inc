<?php 

/** Utility methods for DataBase, not included in SQLQuery because it should be used exceptionnaly
 */
class DataBaseUtilities {
	
	/**
	 * Create a table in the database
	 * @param DataBaseSystem $db_system the connection to the database
	 * @param datamodel\Table $table the table to create
	 * @param string|null $suffix any suffix to add at the end of the table name 
	 */
	public static function createTable($db_system, &$table, $suffix = null, $output = false) {
		$sql = "CREATE TABLE `".$table->getName().($suffix <> null ? $suffix : "")."` (";
		
		$sub_model = $suffix == null ? null : substr($suffix,1);
		
		$ref = new ReflectionClass("\datamodel\Table");
		$columns = $table->internalGetColumnsFor($sub_model);
		$p = $ref->getProperty("indexes");
		$p->setAccessible(true);
		$indexes = $p->getValue($table);
		
		$first = true;
		foreach ($columns as $col) {
			if ($first) $first = false; else $sql .= ", ";
			$sql .= $col->getSQL($db_system, $table->getName().($suffix <> null ? $suffix : ""));
		}
		$pk = $table->getPrimaryKey();
		if ($pk <> null)
			$sql .= ", PRIMARY KEY(`".$pk->name."`)";
		else {
			$key = $table->getKey();
			if ($key <> null) {
				$sql .= ", UNIQUE KEY `table_key` (";
				$first = true;
				foreach ($key as $colname) {
					if ($first) $first = false; else $sql .= ",";
					$sql .= "`".$colname."`";
				}
				$sql .= ")";
			}
		}
		foreach ($indexes as $index) {
			$unique = true;
			for ($i = 1; $i < count($index); $i++)
				foreach ($columns as $c) if ($c->name == $index[$i]) { $unique &= $c->unique; break; }
			$sql .= ", ".($unique ? "UNIQUE " : "")."KEY `".$index[0]."` (";
			for ($i = 1; $i < count($index); $i++) {
				if ($i > 1) $sql .= ",";
				$sql .= "`".$index[$i]."`";
			}
			$sql .= ")";
		}
		$sql .= ")";
		$sql .= " ENGINE=InnoDB";
		if ($output)
			return $sql;
		$db_system->execute($sql);
	}
	
	/**
	 * Remove a table from the database
	 * @param DataBaseSystem $db_system the connection to the database
	 * @param datamodel\Table $table the table to remove
	 * @param string|null $suffix any suffix to add at the end of the table name 
	 */
	public static function dropTable($db_system, &$table, $suffix = null) {
		$db_system->execute("DROP TABLE `".$table->getName().($suffix <> null ? $suffix : "")."`");
	}
	
	public static function importSQL(&$db_system, $file, $delimiter = ';') {
		set_time_limit(0);
		if (is_file($file) === true) {
			$file = fopen($file, 'r');
			if (is_resource($file) === true) {
				$query = array();
				while (feof($file) === false) {
					$query[] = fgets($file);
					if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
						$query = trim(implode('', $query));
						$db_system->execute($query);
					}
					if (is_string($query) === true)
						$query = array();
				}
				return fclose($file);
			}
		}
		return false;
	}
	
}
?>