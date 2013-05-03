<?php
class SqlRelayPDO
{

	protected $_con = null;

	protected $_cur = null;

	protected $_inTransaction = false;

	/**
	 * Driver options
	 *
	 * @var array
	 */
	protected $_options = array();

	/**
	 *  dsn = "sqlr:instanceName:port:socket"
	 *
	 */
	public function __construct($dsn, $username="", $password="")
	{
		if (strpos($dsn, ':') !== false) {
			$dsn = explode(':', $dsn);
			if (count($dsn)!=4) {
				throw new SqlRelayPDOException('dsn format error');
			}

			if ($dsn[0] != 'sqlr') {
				throw new SqlRelayPDOException('dsn driver error. explame: sqlr.');
			}

			$this->_con = sqlrcon_alloc ($dsn[1], $dsn[2], $dsn[3],  $username, $password,  0, 1);
			if (!$this->inTransaction()) {
				sqlrcon_autoCommitOn($this->_con);
			}
			
			$this->_cur = $this->openCursor();
			//sqlrcur_getColumnInfo($this->_cur); //设置获取字段信息
			sqlrcur_setResultSetBufferSize($this->_cur,0); //设置全部结果集都缓冲
		}
	}

	public function openCursor() {
		return sqlrcur_alloc($this->_con);
	}

	public function closeCursor() {
		$this->_cur = null;
	}

	/**
	 * 启动一个事务
	 * @return boolean  成功时返回 TRUE， 或者在失败时返回 FALSE。	 */
	public function beginTransaction()
	{
		if ($this->inTransaction()) {
		    throw new SqlRelayPDOException('There is already an active transaction');
		}

 		$status = sqlrcon_autoCommitOff($this->_con);
		$this->_inTransaction = $status=1 ? true : false;

		return $this->_inTransaction;

	}

	/**
	 * 提交一个事务 
	 * 数据库连接返回到自动提交模式直到下次调用 beginTransaction() 开始一个新的事务为止。	 * @return boolean  成功时返回 TRUE， 或者在失败时返回 FALSE。	 */
	public function commit()
	{
		if (!$this->inTransaction()) {
			throw new SqlRelayPDOException('There is no active transaction');
		}

		if (sqlrcon_commit($this->_con)) {
			$this->_inTransaction = false;
			return true;
		}

		return false;
	}

	public function errorCode()
	{
		$error = $this->errorInfo();
		return $error[0];
	}

	public function errorInfo()
	{
		if ($this->_cur==null) $this->_cur = $this->openCursor();
		$message = sqlrcur_errorMessage($this->_cur);
		$code = sqlrcur_errorNumber($this->_cur);

		return array($code, $message);
	}

	/**
	 *  执行一条 SQL 语句，并返回受影响的行数 
	 *  在一个单独的函数调用中执行一条 SQL 语句，返回受此语句影响的行数。	 *  不会从一条 SELECT 语句中返回结果	 *  @return int  返回受修改或删除 SQL 语句影响的行数。如果没有受影响的行，则返回0
	 */
	public function exec($statement)
	{
		$stmt = $this->prepare($statement);
		$stmt->execute();

		return $stmt->rowCount();
	}

	/**
	 *  取回一个数据库连接的属性 
	 *  @return mixed  成功调用则返回请求的 PDO 属性值。不成功则返回 null。	 */
	public function getAttribute($attribute)
	{
		if (isset($this->_options[$attribute])) {
		    return $this->_options[$attribute];
		}
		return null;
	}

	/**
	 *  检查是否在一个事务内
	 *  检查驱动内的一个事务当前是否处于激活。此方法仅对支持事务的数据库驱动起作用。
	 *  @return boolean  如果当前事务处于激活，则返回 TRUE ，否则返回 FALSE 。 
	 */
	public function inTransaction()
	{
		return $this->_inTransaction;
	}

	/**
	 *  返回最后插入行的ID或序列值 
	 *  @param name 序列名	 *  @return int  返回序列currval
	 */
	public function lastInsertId($name = null)
	{
		trigger_error(
			'SQLSTATE[IM001]: Driver does not support this function: driver does not support lastInsertId()',
			E_USER_WARNING);
	}

	public function prepare($statement, $driver_options = array())
	{
		if ($this->_cur==null) $this->_cur = $this->openCursor();
		sqlrcur_prepareQuery($this->_cur, $statement);
		return new SqlRelayPDOStatement($this->_cur, $this, $statement, $driver_options);
	}

	public function query($statement)
	{
		if ($this->_cur==null) $this->_cur = $this->openCursor();
		$stmt = $this->prepare($statment);
		$stmt->execute();
	}

	public function quote($string, $parameter_type = PDO::PARAM_STR)
	{
		if($parameter_type !== PDO::PARAM_STR) {
			throw new PDOException('Only PDO::PARAM_STR is currently implemented for the $parameter_type of SqlRelayPDO::quote()');
     }
		return "'" . str_replace("'", "''", $string) . "'";
	}


	/**
	 *  回滚一个事务 
	 *  @return boolean  成功时返回 TRUE， 或者在失败时返回 FALSE。	 */
	public function rollBack()
	{
		if (!$this->inTransaction()) {
			throw new SqlRelayPDOException('There is no active transaction');
		}

		if (sqlrcon_rollback($this->_con)) {
		    $this->_inTransaction = false;
			sqlrcon_autoCommitOn($this->_con);
		    return true;
		}

		return false;
	}

	/**
	 *  设置属性 
	 *  @return boolean  成功时返回 TRUE， 或者在失败时返回 FALSE。	 */
	public function setAttribute($attribute, $value)
	{
		$this->_options[$attribute] = $value;
		return true;
	}

	public function getColumnList($tablename)
	{
		if ($this->_cur==null) $this->_cur = $this->openCursor();
		sqlrcur_getColumnList($this->_cur, $tablename, "");
		$stmt =  new SqlRelayPDOStatement($this->_cur, $this, "");
		return $stmt->fetchAll();
	}
}

