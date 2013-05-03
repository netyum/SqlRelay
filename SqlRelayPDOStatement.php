<?php
class SqlRelayPDOStatement { //implements Traversable {

	protected $_sqlr;

	protected $_cur = null;

	/**
	* Statement options
	*
	* @var array
	*/
	protected $_options = array();
    
	/*
	* Default fetch mode for this statement
	* @var integer
	*/
	protected $_fetchMode = null;
    
	/*
	* Bound columns for bindColumn()
	*/
	protected $_boundColumns = array();

	public function __construct(&$cur,
                                SqlRelayPDO $sqlr,
				$statement,
                                array $options = array())
	{
        
		$this->_cur = $cur;
		$this->_sqlr = $sqlr;
		if ($this->_cur==null) $this->_cur = $this->_sqlr->openCursor();
		$this->_options = $options;
	}


	protected function bindToColumn($result)
	{
		if($result !== false) {
			foreach($this->_boundColumns as $bound) {
				$key = $bound['column']-1;
				$array = array_slice($result, $key, 1);
				if($bound['type']===PDO::PARAM_INT) {
					$bound['param'] = (int)array_pop($array);
				} else {
					$bound['param'] = array_pop($array);
				}
			}
		}
    }

	/**
	 *  绑定一列到一个 PHP 变量
	 *  @return boolean  成功时返回 TRUE， 或者在失败时返回 FALSE。	 */
	public function bindColumn($column, &$param, $type, $maxlen=null, $driverdata=null)
	{
		if($maxlen !== null || $driverdata !== null) {
			throw new SqlRelayPDOException('$maxlen and $driverdata parameters are not implemented for SqlRelayPDOStatement::bindColumn()');
		}

		if($type !== PDO::PARAM_INT && $type !== PDO::PARAM_STR) {
			throw new SqlRelayPDOException('Only PDO::PARAM_INT and PDO::PARAM_STR are implemented for the $type parameter of SqlRelayPDOStatement::bindColumn()');
		}
    
		$this->_boundColumns[] = array(
			'column'=>$column,
			'param'=>&$param,
			'type'=>$type
		);
	}

	/**
	 *  绑定一个参数到指定的变量名 
	 *  @return boolean  成功时返回 TRUE， 或者在失败时返回 FALSE。	 */
	public function bindParam($parameter, $variable, $data_type = PDO::PARAM_STR, $length=-1, $driver_options = null)
	{
		if($driver_options !== null) {
			throw new SqlRelayPDOException('$driver_options is not implemented for SqlRelayPDOStatement::bindParam()');
		}
		if ($this->_cur==null) $this->_cur = $this->_sqlr->openCursor();

		sqlrcur_inputBind($this->_cur, ltrim($parameter,':'), $variable);
		return true;
	}

	/**
	 *  把一个值绑定到一个参数  
	 *  @return boolean  成功时返回 TRUE， 或者在失败时返回 FALSE。	 */
	public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR )
	{
		return $this->bindParam($parameter, $value, $data_type);
	}

	/**
	 *  关闭游标，使语句能再次被执行。   
	 *  @return boolean  成功时返回 TRUE， 或者在失败时返回 FALSE。	 */
	public function closeCursor()
	{
		sqlrcur_free($this->_cur);
		$this->_cur = null;
		$this->_sqlr->closeCursor();
	}

	/**
	 *  返回结果集中的列数    
	 *  @return int  结果集中的列数。如果没有结果集返回0
	 */
	public function columnCoumnt()
	{
		if ($this->_cur==null) $this->_cur = $this->_sqlr->openCursor();
		return sqlrcur_colCount($this->_cur);
	}

	public function debugDumpParams()
	{
		throw new PDOException('debugDumpParams() method is not implemented for SqlRelayPDOStatement');

	}

	public function errorCode()
	{
		$error = $this->errorInfo();
		return $error[0];
	}

	public function errorInfo()
	{
		if ($this->_cur==null) $this->_cur = $this->_sqlr->openCursor();
		$message = sqlrcur_errorMessage($this->_cur);
		$code = sqlrcur_errorNumber($this->_cur);

		return array($code, $message);
	}

	/**
	 *  执行一条预处理语句	 *  @param array $input_parameters
	 *  @return boolean 成功时返回 TRUE， 或者在失败时返回 FALSE。 
	 */
	public function execute($input_parameters=array())
	{
		if (is_array($input_parameters)) {
			foreach ($input_parameters as $key => $value) {
				$bound = $this->bindParam($key, $input_parameters[$key]);
				if(!$bound) {
					throw new SqlRelayPDOException($input_parameters[$key].' could not be bound to '.$key.' with SqlRelayPDOStatement::bindParam()');
				}
			}
		}
		if ($this->_cur==null) $this->_cur = $this->_sqlr->openCursor();
		sqlrcur_executeQuery($this->_cur);
		return true;
	}

	public function fetch($fetch_style = PDO::FETCH_ASSOC, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
	{
		if ($this->_cur==null) $this->_cur = $this->_sqlr->openCursor();
		if($cursor_orientation !== PDO::FETCH_ORI_NEXT || $cursor_offset !== 0) {
			throw new SqlRelayPDOException('$cursor_orientation that is not PDO::FETCH_ORI_NEXT is not implemented for Oci8PDO_Statement::fetch()');
		}
    
		if($this->_fetchMode !== null) {
			$fetch_style = $this->_fetchMode;
		}
    
		if($fetch_style === PDO::FETCH_ASSOC) {
			$result = sqlrcur_getRowAssoc($this->_cur, $cursor_offset);
		} elseif($fetch_style === PDO::FETCH_NUM) {
			$result = sqlrcur_getRow($this->_cur, $cursor_offset);
		} elseif($fetch_style === PDO::FETCH_BOTH) {
			throw new SqlRelayPDOException('PDO::FETCH_BOTH is not implemented for SqlRelayPDOStatement::fetch()');
		} elseif($fetch_style === PDO::FETCH_BOUND) {
			throw new SqlRelayPDOException('PDO::FETCH_BOUND is not implemented for SqlRelayPDOStatement::fetch()');
		} elseif($fetch_style === PDO::FETCH_CLASS) {
			throw new SqlRelayPDOException('PDO::FETCH_CLASS is not implemented for SqlRelayPDOStatement::fetch()');
		} elseif($fetch_style === PDO::FETCH_INTO) {
			throw new SqlRelayPDOException('PDO::FETCH_INTO is not implemented for SqlRelayPDOStatement::fetch()');
		} elseif($fetch_style === PDO::FETCH_LAZY) {
			throw new SqlRelayPDOException('PDO::FETCH_LAZY is not implemented for SqlRelayPDOStatement::fetch()');
		} elseif($fetch_style === PDO::FETCH_OBJ) {
			throw new SqlRelayPDOException('PDO::FETCH_OBJ is not implemented for SqlRelayPDOStatement::fetch()');
		} else {
			throw new PDOException('This $fetch_style combination is not implemented for SqlRelayPDOStatement::fetch()');
		}
		$this->bindToColumn($result);
		return $result;
	}

	public function fetchAll($fetch_style = PDO::FETCH_ASSOC, $fetch_argument = null, $ctor_args = array())
	{
		if ($this->_cur==null) $this->_cur = $this->_sqlr->openCursor();
		if($this->_fetchMode !== null) {
			$fetch_style = $this->_fetchMode;
		}

		$count = sqlrcur_rowCount($this->_cur);
		if($fetch_style === PDO::FETCH_ASSOC) {
			$result = array();
			for($i=0; $i < $count; $i++){
				$result[$i] = sqlrcur_getRowAssoc($this->_cur, $i);
			}
		} elseif($fetch_style === PDO::FETCH_NUM) {
			$result = array();
			for($i=0; $i < $count; $i++){
				$result[$i] = sqlrcur_getRow($this->_cur, $i);
			}
		} elseif($fetch_style === PDO::FETCH_COLUMN) {
			$result = array();
			for($i=0; $i < $count; $i++){
				$res = sqlrcur_getRow($this->_cur, $i);
				$result[$i] = $res[0];
			}
		} elseif($fetch_style === PDO::FETCH_BOTH) {
			$result = array();
			for($i=0; $i < $count; $i++){
				$resAssoc = sqlrcur_getRowAssoc($this->_cur, $i);
				$res = sqlrcur_getRow($this->_cur, $i);
				$result[$i] = array_merge($resAssoc, $res);
			}
		} elseif($fetch_style === PDO::FETCH_BOUND) {
			throw new SqlRelayPDOException('PDO::FETCH_BOUND is not implemented for Oci8PDO_Statement::fetchAll()');
		} elseif($fetch_style === PDO::FETCH_CLASS) {
			throw new SqlRelayPDOException('PDO::FETCH_CLASS is not implemented for Oci8PDO_Statement::fetchAll()');
		} elseif($fetch_style === PDO::FETCH_INTO) {
			throw new SqlRelayPDOException('PDO::FETCH_INTO is not implemented for Oci8PDO_Statement::fetchAll()');
		} elseif($fetch_style === PDO::FETCH_LAZY) {
			throw new SqlRelayPDOException('PDO::FETCH_LAZY is not implemented for Oci8PDO_Statement::fetchAll()');
		} elseif($fetch_style === PDO::FETCH_OBJ) {
			throw new SqlRelayPDOException('PDO::FETCH_OBJ is not implemented for Oci8PDO_Statement::fetchAll()');
		} else {
			throw new SqlRelayPDOException('This $fetch_style combination is not implemented for Oci8PDO_Statement::fetch()');
		}

		return $result;
	}

	public function fetchColumn($column_number = 0)
	{
		if ($this->_cur==null) $this->_cur = $this->_sqlr->openCursor();
		$result = sqlrcur_getRow($this->_cur, 0);
		
		if($result===false) {
			return false;
		} elseif(!isset($result[$column_number])) {
			return false;
		} else {
			return $result[$column_number];
		} 
	}

	public function fetchObject($class_name = "stdClass", $ctor_args = array())
	{
		throw new SqlRelayPDOException('fetchObject() method is not implemented for SqlRelayPDOStatement');
	}

	public function getAttribute($attribute)
	{
		if (isset($this->_options[$attribute])) {
			return $this->_options[$attribute];
		}
		return null;
	}

	public function getColumnMeta($column)
	{
		throw new SqlRelayPDOException('Driver does not support this function: driver doesn\'t support meta data');
	}

	public function nextRowset()
	{
		throw new SqlRelayPDOException('nextRowset() method is not implemented for SqlRelayPDOStatement');
	}

	public function rowCount()
	{
		if ($this->_cur==null) $this->_cur = $this->_sqlr->openCursor();
		$rowCount = sqlrcur_totalRows($this->_cur);
		if ($rowCount != -1) {
			return $rowCount;
		}
		else {
			return sqlrcur_rowCount($this->_cur);
		}

	}

	public function setAttribute($attribute, $value)
	{
		$this->_options[$attribute] = $value;
		return true;
	}

	public function setFetchMode($mode)
	{
		$this->_fetchMode = $mode;
		return true;
	}
}

