<?php
/**
 * Slim Database.
 *
 * These are the set of methods that power the slim database
 *
 * @author Aminu Ibrahim Bakori <aminuibakori@live.com>
 * @version 1.0.0
 * @license The MIT License (MIT)
 * @copyright Copyright (c) <2014> <Aminu Ibrahim Bakori>
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class SlimDB 
{
	private $db = '';
	private $name = '';
	private $table = '';

	/**
     * Open Slim Database.
     *
     * This open a Slim database if it exists or
	 * creates a new database if not exist.
     *
     * @param dbName $dbName The database name with complete path
     */
	public function open($dbName = '') 
	{
		if($dbName != null) {
			if(substr($dbName, -5, 5) == ".slim") {
				if(file_exists($dbName)) {
					$openDB = file_get_contents($dbName);
					$this->name = $dbName;
					$this->db = $openDB;
					return $this;
				}else {
					$new = fopen($dbName, 'w');
					$data = array(
						'name'=> basename($dbName, ".slim").PHP_EOL,
						'tables'=> array(),
						'data' => array(),
						);
					$new = file_put_contents($dbName, json_encode($data));
					$openDB = file_get_contents($dbName);
					$this->name = $dbName;
					$this->db = $openDB;
					return $this;
				}
			}else {
				throw new Exception("Not a valid slim database.", 1);
			}
		}else {
			throw new Exception("You need to specify the database name.", 1);
		}
	}

	/**
     * Create new table.
     *
     * Create a new table set the col names and
	 * create a node for data entry.
     *
     * @param Info $info The table info<name, cols> must be in JSON format
     * @example {"name": "tblusers", "cols": {"name", "age"} }
     */
	public function newTable($_info = '') 
	{
		if($this->db != '') {
			$info = json_decode($_info, true);

			if(is_array($info) && array_key_exists('name', $info) && array_key_exists('cols', $info) && !empty($info['cols'])) {
				$database = json_decode($this->db, true);
				if(array_key_exists($info['name'], $database['tables'])) {
					throw new Exception("Table '".$info['name']."' already exist.", 1);
				}else {
					$tablecols = array();
					array_push($tablecols, "_id");
					foreach ($info['cols'] as $key => $col) {
						array_push($tablecols, $col);
					}
					array_push($tablecols, "created_at");
					array_push($tablecols, "updated_at");

					$database['tables'][$info['name']] = $tablecols;
					$database['data'][$info['name']] = array();
					$openDB = file_put_contents($this->name, json_encode($database));
					$openDB = file_get_contents($this->name);
					$this->db = $openDB;
					return $this;
				}
			}else {
				throw new Exception("Invalid query.", 1);
			}
		}else {
			throw new Exception("You need to select a database.", 1);		
		}
	}

	/**
     * Drop Table.
     *
     * Drop database table with data
     *
     * @param tableName $tableName The table name
     */
	public function dropTable($tableName = '') 
	{
		if($this->db != '') {
			$database = json_decode($this->db, true);
			if(array_key_exists($tableName, $database['tables'])) {
				unset($database['tables'][$tableName]);
				unset($database['data'][$tableName]);
				$openDB = file_put_contents($this->name, json_encode($database));
				$openDB = file_get_contents($this->name);
				$this->db = $openDB;
				return $this;
			}else {
				throw new Exception("Table '".$tableName."' does not exist.", 1);
			}
		}else {
			throw new Exception("You need to select a database.", 1);		
		}
	}

	/**
     * Set Table.
     *
     * Sets table name if exists to the default table for incomming requests
     *
     * @param tableName $tableName The table name to set as current table
     */
	public function setTable($tableName = '') 
	{
		if($this->db != '') {
			$database = json_decode($this->db, true);
			if(array_key_exists($tableName, $database['tables'])) {
				$this->table = $tableName;
				return $this;
			}else {
				throw new Exception("Table '".$tableName."' does not exist.", 1);
			}
		}else {
			throw new Exception("You need to select a database.", 1);		
		}
	}

	/**
     * Insert.
     *
     * Insert data to current table
     *
     * @param data $data The insert informatiom
     */
	public function insert($data = '') 
	{
		if($this->db != '') {
			$database = json_decode($this->db, true);
			$data = json_decode($data, true);
			if($this->table != '') {
				foreach ($data as $key => $dt) {
					$e = 0;
					foreach ($database['tables'][$this->table] as $key2 => $col) {
						if($col == $key) {
							$e = 1;
						}
					}
					if($e == 0) {
						throw new Exception("Column '".$key."' does not exist in the table ".$this->table, 1);
					}
				}
				if(count($data) == count($database['tables'][$this->table]) - 3) {
					$insertData = array();
					$insertData['_id'] = $this->generateObjectId();
					foreach ($data as $key => $dt) {
						$insertData[$key] = $dt;
					}
					$insertData['created_at'] = date('Y-m-d G:i:s');
					$insertData['updated_at'] = date('Y-m-d G:i:s');

					array_push($database['data'][$this->table], $insertData);
					$openDB = file_put_contents($this->name, json_encode($database));
					$openDB = file_get_contents($this->name);
					$this->db = $openDB;

					return $this;
				}else {
					throw new Exception("All table columns can not be null.", 1);
				}
			}else {
				throw new Exception("You need to set a table.", 1);
			}			
		}else {
			throw new Exception("You need to select a database.", 1);		
		}
	}

	/**
     * Select.
     *
     * Select data to current table
     *
     * @param where $where search using the where keywords if none given returns the whole table
     */
	public function select($where = '') 
	{
		if($this->db != '') {
			$database = json_decode($this->db, true);
			$where = json_decode($where, true);
			if($this->table != '') {
				if($where != null) {
					foreach ($where as $key => $dt) {
						$e = 0;
						foreach ($database['tables'][$this->table] as $key2 => $col) {
							if($col == $key) {
								$e = 1;
							}
						}
						if($e == 0) {
							throw new Exception("Column '".$key."' does not exist in the table ".$this->table, 1);
						}
					}

					$returnData = array();
					foreach ($database['data'][$this->table] as $key => $data) {
						$e = 0;
						foreach ($where as $k => $w) {
							if($database['data'][$this->table][$key][$k] == $w) {
								$e += 1;
							}else {
								$e = 0;
							}
						}
						if(count($where) == $e) {
							array_push($returnData, $database['data'][$this->table][$key]);
						}
					}

					return json_decode(json_encode($returnData, false));
				}else {
					return json_decode(json_encode($database['data'][$this->table], false));
				}
			}else {
				throw new Exception("You need to set a table.", 1);
			}			
		}else {
			throw new Exception("You need to select a database.", 1);		
		}
	}

	/**
     * Delete.
     *
     * Delete data from current table
     *
     * @param where $where search using the where keywords
     */
	public function delete($where = '') 
	{
		if($this->db != '') {
			$database = json_decode($this->db, true);
			$where = json_decode($where, true);
			if($this->table != '') {
				if($where != null) {
					foreach ($where as $key => $dt) {
						$e = 0;
						foreach ($database['tables'][$this->table] as $key2 => $col) {
							if($col == $key) {
								$e = 1;
							}
						}
						if($e == 0) {
							throw new Exception("Column '".$key."' does not exist in the table ".$this->table, 1);
						}
					}

					foreach ($database['data'][$this->table] as $key => $data) {
						$e = 0;
						foreach ($where as $k => $w) {
							if($database['data'][$this->table][$key][$k] == $w) {
								$e += 1;
							}else {
								$e = 0;
							}
						}
						if(count($where) == $e) {
							unset($database['data'][$this->table][$key]);
						}
					}
					$openDB = file_put_contents($this->name, json_encode($database));
					$openDB = file_get_contents($this->name);
					$this->db = $openDB;
					return $this;
				}else {
					$database['data'][$this->table] = array();
					$openDB = file_put_contents($this->name, json_encode($database));
					$openDB = file_get_contents($this->name);
					$this->db = $openDB;
					return $this;
				}
			}else {
				throw new Exception("You need to set a table.", 1);
			}			
		}else {
			throw new Exception("You need to select a database.", 1);		
		}
	}

	/**
     * Update.
     *
     * Update data to current table
     *
     * @param info $info<> 
     * @example {"where": "col1: "...", "col2":"..."", "set": ""col1": "...", "col2": "...""} 
     */
	public function update($info = '') 
	{
		if($this->db != '') {
			$database = json_decode($this->db, true);
			$info = json_decode($info, true);
			if($this->table != '') {
				if($info != null) {
					if($info['where'] == null) {
						foreach ($info['set'] as $key => $dt) {
							$e = 0;
							foreach ($database['tables'][$this->table] as $key2 => $col) {
								if($col == $key) {
									$e = 1;
								}
							}
							if($e == 0) {
								throw new Exception("Column '".$key."' does not exist in the table ".$this->table, 1);
							}
						}

						foreach ($database['data'][$this->table] as $key => $data) {
							foreach ($info['set'] as $k => $value) {
								$database['data'][$this->table][$key][$k] = $value;
							}
							$database['data'][$this->table][$key]['updated_at'] = date('Y-m-d G:i:s');
						}
						$openDB = file_put_contents($this->name, json_encode($database));
						$openDB = file_get_contents($this->name);
						$this->db = $openDB;
						return $this;
					}else {
						foreach ($info['where'] as $key => $dt) {
							$e = 0;
							foreach ($database['tables'][$this->table] as $key2 => $col) {
								if($col == $key) {
									$e = 1;
								}
							}
							if($e == 0) {
								throw new Exception("Column '".$key."' does not exist in the table ".$this->table, 1);
							}
						}

						foreach ($info['set'] as $key => $dt) {
							$e = 0;
							foreach ($database['tables'][$this->table] as $key2 => $col) {
								if($col == $key) {
									$e = 1;
								}
							}
							if($e == 0) {
								throw new Exception("Column '".$key."' does not exist in the table ".$this->table, 1);
							}
						}

						foreach ($database['data'][$this->table] as $key => $data) {
							$e = 0;
							foreach ($info['where'] as $k => $w) {
								if($database['data'][$this->table][$key][$k] == $w) {
									$e += 1;
								}else {
									$e = 0;
								}
							}
							if(count($info['where']) == $e) {
								foreach ($info['set'] as $k => $value) {
									$database['data'][$this->table][$key][$k] = $value;
								}
								$database['data'][$this->table][$key]['updated_at'] = date('Y-m-d G:i:s');
							}
						}
						$openDB = file_put_contents($this->name, json_encode($database));
						$openDB = file_get_contents($this->name);
						$this->db = $openDB;
						return $this;
					}
				}else {
					throw new Exception("Invalid request.", 1);
				}
			}else {
				throw new Exception("You need to set a table.", 1);
			}			
		}else {
			throw new Exception("You need to select a database.", 1);		
		}
	}

	/**
     * Generate Object Id.
     *
     * This generate unique object id for each item in the database
     *
     */
	public function generateObjectId() 
	{
		$timestamp = time();
		$hostname = php_uname('n');
		$processid = getmypid();
		$id = rand(0, 9999);
        $bin = sprintf("%s%s%s%s", pack('N', $timestamp), substr(md5($hostname), 0, 3), pack('n', $processid), substr(pack('N', $id), 1, 3));
        
        $result = '';
        for($i = 0; $i < 12; $i++) {
            $result.=sprintf("%02x", ord($bin[$i]));
        }
        
        return $result;
    }
}
?>