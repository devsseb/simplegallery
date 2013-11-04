<?
namespace Database\Object;

class QueryResult
{
	/*
	 * Original query
	 *
	 */	
	private $query = null, $replaces = array();

	/*
	 * Parts of a query
	 *
	 */
	private
		$select = array(),
		$tables = array(),
		$where = array(),
		$having = array(),
		$orderBy = array(),
		$groupBy = array(),
		$limit = array(),
		
		$insert = null,
		$update = null,
		$values = array(),
		
		$delete = null
	;

	/*
	 * Query options
	 *
	 */
	private
		$one = false,
		$factorize = true
	;
	
	/*
	 * Result
	 *
	 */
	private $result, $idInserted, $entityName;

	public function __construct(\Database\Object\Query $query, $replaces = array())
	{
		$this->query = $query;
		$this->replaces = $replaces;
		
		$this->prepareOptions();

		if ($this->query->getDelete()) {
			// Delete
			$this->prepareDelete();
			$this->prepareWhere();
		} elseif ($this->query->getUpdate()) {
			// Update
			$this->prepareUpdate();
			$this->prepareValues();
			$this->prepareWhere();
		} elseif ($this->query->getInsert()) {
			// Insert
			$this->prepareInsert();
			$this->prepareValues();		
		} else {
			// Select
			$this->prepareTables();
			$this->prepareSelect();
			$this->prepareWhere();
			$this->prepareHaving();
			$this->prepareOrderBy();
			$this->prepareGroupBy();
			$this->prepareLimit();
		}
		
		$this->execute();
	}
	
	private function prepareOptions()
	{
		$this->one = $this->query->isOne();
		$this->factorize = $this->query->isFactorized();
	}
	
	public function isOne()
	{
		return $this->one;
	}
	
	public function isFactorized()
	{
		return $this->factorize;
	}
	
	private function prepareTables()
	{
		// Tables from
		if (!$from = $this->query->getFrom())
			$from = $this->query->getDefaultTable();

		$table = $this->parseTable($from);
		$table->join = false;
		$this->tables[$table->alias] = $table;

		// Tables join
		foreach ($this->query->getJoin() as $join) {
		
			$parse = $this->parseTable($join->table);
		
			$table = object(
				'database', $parse->database,
				'tableClass', $parse->tableClass,
				'tableName', $parse->tableName,
				'alias', $parse->alias,
				'join', object(
					'type', $join->type,
					'attribute', $join->attribute,
					'onDatabase', null,
					'onTableClass', null,
					'onAlias', null,
					'onAttribute', $join->onAttribute,
					'onAttributeFactorized', null,
					'relationship', null
				)
			);
		
			$tableClass = $table->tableClass;

			$onTableClass = $this->parseTable($join->onTable);
			$table->join->onDatabase = $onTableClass->database;
			$table->join->onAlias = $onTableClass->alias;
			$onTableClass = $table->join->onTableClass = $onTableClass->tableClass;

			// Automatic join
			if (!$table->join->onAlias)

				foreach ($this->tables as $tableInclude) {

					foreach ($tableClass::getLinks() as $link) {

						if (strtolower($tableInclude->tableClass) != strtolower($link->onTableClass))
							continue;

						$table->join->attribute = $link->attribute;
						$table->join->onDatabase = $tableInclude->database;
						$table->join->onTableClass = $tableInclude->tableClass;
						$table->join->onAlias = $tableInclude->alias;
						$table->join->onAttribute = $link->onAttribute;
						$table->join->onAttributeFactorized = $link->onAttributeFactorized;
						$table->join->relationship = $link->relationship;

						if ($link->relationship == \Database::R_N_N)
							
							$table->join->nn = object(
								'table', $link->nnTable,
								'attribute', $link->nnAttribute,
								'onAttribute', $link->nnOnAttribute,
								'attributes', $link->nnAttributes
							);

						
						break;
				
					}
				
					if ($table->join->onAlias)
						break;
				}

			if (!$table->join->onAlias)
				throw new \Exception('Unable to join table "' . $join->table . '"');
			
			$this->tables[$table->alias] = $table;
			
		}

	}
	
	public function getTables()
	{
		return $this->tables;
	}
	
	private function prepareSelect()
	{
		if ($this->isFactorized() and !$this->select = $this->query->getSelect()) {
			$db = \Database::getInstance();
			foreach ($this->tables as $alias => $table) {
				foreach (forward_static_call(array($table->tableClass, 'getAttributes')) as $attribute)
					$this->select[] = $db->protect($alias, \Database::FIELD) . '.' . 
						$db->protect($attribute, \Database::FIELD) . ' AS ' . 
						$db->protect('__' . $alias . '__' . $attribute, \Database::FIELD);
						
				if ($table->join and property_exists($table->join, 'nn'))
					foreach ($table->join->nn->attributes as $attribute)
						$this->select[] = $db->protect($table->join->nn->table, \Database::FIELD) . '.' . 
							$db->protect($attribute, \Database::FIELD) . ' AS ' . 
							$db->protect('__' . $alias . '__' . $attribute, \Database::FIELD);

			}

		} else
			$this->factorize = false;

	}
	
	public function getSelect()
	{
		return $this->select;
	}
	
	private function prepareWhere()
	{
		$this->where = $this->query->getWhere();
	}
	
	public function getWhere()
	{
		return $this->where;
	}
	
	private function prepareHaving()
	{
		$this->having = $this->query->getHaving();
	}
	
	public function getHaving()
	{
		return $this->having;
	}
	
	private function prepareOrderBy()
	{
		$this->orderBy = $this->query->getOrderBy();
	}
	
	public function getOrderBy()
	{
		return $this->orderBy;
	}
	
	private function prepareGroupBy()
	{
		$this->groupBy = $this->query->getGroupBy();
	}
	
	public function getGroupBy()
	{
		return $this->groupBy;
	}
	
	private function prepareLimit()
	{
		$this->limit = $this->query->getLimit();
	}
	
	public function getLimit()
	{
		return $this->limit;
	}
	
	private function prepareInsert()
	{
		$this->factorize = false;
		$this->insert = $this->parseTable($this->query->getInsert());
	}
	
	public function getInsert()
	{
		return $this->insert;
	}
	
	private function prepareUpdate()
	{
		$this->factorize = false;
		$this->update = $this->parseTable($this->query->getUpdate());
	}
	
	public function getUpdate()
	{
		return $this->update;
	}
	
	private function prepareValues()
	{
		$this->values = $this->query->getValues();
	}
	
	public function getValues()
	{
		return $this->values;
	}
	
	private function prepareDelete()
	{
		$this->factorize = false;
		$this->delete = $this->parseTable($this->query->getDelete());
	}
	
	public function getDelete()
	{
		return $this->delete;
	}
	
	private function execute()
	{
		$db = \Database::getInstance();
		
		$this->result = $db->executeQuery($this, $this->replaces);

		if ($this->getInsert())
			$this->idInserted = $db->lastId();

		if ($this->isFactorized())
			$this->factorize();

		if ($this->isOne() and $this->result)
			$this->result = array_shift($this->result);

	}

	public function getIdInserted()
	{
		return $this->idInserted;
	}

	public function getEntityName()
	{
		return $this->entityName;
	}
	
	/*
	 * Parse a table
	 *
	 */
	private function parseTable($string)
	{

		preg_match('/(\\*?)(.*?)\\s*(?:as\\s(\\w+))?$/i', $string, $match);
		$tableIsBrut = (bool)$match[1];
		$string = $match[2];
		$alias = get($match, k(3));
		preg_match('/(?:(`?)(\\w+)\\1\\.)?(`?)(\\w+)\\3/i', $string, $match);

		$database = gete($match, k(2));
		$tableName = gete($match, k(4));
		$tableClass = (!$tableIsBrut and $tableName) ? '\\Database\\' . ucfirst($tableName) . 'Table' : null;
		if ($tableClass)
			$tableName = $tableClass::getDatabaseName();

		return object(
			'database', $database,
			'tableName', $tableName,
			'tableClass', $tableClass,
			'alias', gete($alias, $tableName)
		);
	}

	/*
	 * Factorize the result of a query
	 *
	 */
	private function factorize()
	{

		$aliases = array();

		// Save headers
		$head = array();
		if ($this->result) {
			foreach ($keys = array_keys(get_object_vars($this->result[0])) as $i => $key) {
			
				if (isset($nextAlias))
					$alias = $nextAlias;
				else
					$alias = substr($key, 2, strpos($key, '__', 2) - 2);
				if ($nextAlias = get($keys, k($i+1), ''))
					$nextAlias = substr($nextAlias, 2, strpos($nextAlias, '__', 2) - 2);
				$head[$key] = object(
					'alias', $alias,
					'attribute', substr($key, strlen($alias) + 4),
					'factorizeGroup', $alias !== $nextAlias
				);
			
				if (!array_key_exists($alias, $aliases))
					$aliases[$alias] = array();

				if (!$head[$key]->factorizeGroup)
					continue;

				$tableClass = $this->tables[$alias]->tableClass;
				$head[$key]->idAttribute = $tableClass::getId();

				if (!$this->entityName)
					$this->entityName = $tableClass::getEntityName();
			
			}
//			end($head)->factorizeGroup = true;
		
		} else {
		
			$tableClass = $this->tables[geta(array_keys($this->tables), k(0))]->tableClass;
			$this->entityName = $tableClass::getEntityName();

		}

		// Split result in array data entity
		foreach ($this->result as $line) {

			$data = object();

			foreach ($line as $key => $value) {

				$data->{$head[$key]->attribute} = $value;

				if ($head[$key]->factorizeGroup) {

					// Factorize
					if ($head[$key]->idAttribute) {
						if ($data->{$head[$key]->idAttribute}) {
							if (exists($aliases[$head[$key]->alias], $data->{$head[$key]->idAttribute}))
								continue;
							$aliases[$head[$key]->alias][$data->{$head[$key]->idAttribute}] = $data;
						} else
							$data = null;

						if ($join = $this->tables[$head[$key]->alias]->join) {

							$aliasEntity = end($aliases[$join->onAlias]);
		
							if ($join->relationship == \Database::R_N_1 or $join->relationship == \Database::R_1_1)
								$aliasEntity->{$join->onAttributeFactorized} = $data;
							else {
								if (!array_key_exists($join->onAttributeFactorized, $aliasEntity))
									$aliasEntity->{$join->onAttributeFactorized} = array();
								if ($data)
									$aliasEntity->{$join->onAttributeFactorized}[] = $data;
							}
			
						}
					
					
					} else
						$aliases[$alias][] = $data;					
					
					$data = object();
					
				}

			}

		}
		$this->result = reset($aliases)?:array();
	}

	public function get()
	{
		return $this->result;
	}

}
