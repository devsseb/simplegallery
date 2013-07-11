<?
class Database
{

	private $connexion = null;
	
	private $file, $mail;
	public $prefix, $engine, $charset, $collate, $request_total;
	
	const STRING	= PDO::PARAM_STR;
	const INTEGER	= PDO::PARAM_INT;
	const NULL		= PDO::PARAM_NULL;
	const OBJECT	= PDO::PARAM_LOB;
	const BOOLEAN	= PDO::PARAM_BOOL;
	const FIELD		= 10;
	
	public $db = null;
	public function __construct($config)
	{
		if (is_string($config))
			$config = array('file' => $config);
		$this->setConfig($config);
		$this->connect();
	}

	public function	setConfig(Array $configuration)
	{
		$this->file = get($configuration, k('file'), 'database.sqlite');
		$this->prefix = get($configuration, k('prefix'), '');
		$this->engine = get($configuration, k('engine'), 'InnoDB');
		$this->charset = get($configuration, k('charset'), 'utf8');
		$this->collate = get($configuration, k('collate'), 'utf8_general_ci');
		$this->mail = get($configuration, k('mail'), array());
		if (!is_array($this->mail)) {
			$this->mail = array($this->mail);
		}
		
	}

	// Connecte la base de données
	public function connect()
	{
		$this->sql = 'Connecting ...';
		try {
			$this->connexion = new PDO('sqlite:' . $this->file, 'charset=UTF-8');
		}
		catch (Exception $exception) {
			$this->error($exception->getMessage());
		}
	}
	
	public function useDatabase($database)
	{
		if ($database != '') {
			return $this->execute('USE ' . $this->protect($database, self::FIELD) . ';');
		} else {
			return false;
		}
	}
	
	
	public function error($message)
	{
		$message = $_SERVER['REQUEST_URI'] . chr(10) . $message;

		foreach ($this->mail as $mail) {
			mail($mail, 'SQL Error on ' . $this->database, $message . chr(10) . chr(10) . $this->sql);
		}
		throw new Exception($message);
	}
	
	public function checkSql($sql)
	{
		$sql = trim($sql);
		
		$match = str_replace('\\\\', '', $sql);
		$toTest = '';
		$quote = null;
		preg_match('/(.*?)("|\')(.*)/', $match, $matches);
		while (count($matches) > 0) {

			$toTest.= $matches[1];
			$quote = $matches[2];
			$match = ' ' . $matches[3];
			
			preg_match('/(.*?)([^\\\]' . preg_quote($quote) . ')(.*)/', $match, $matches);
			if ($matches) {
				$match = $matches[3];
			} else {
				$match = '';
			}

			preg_match('/(.*?)("|\')(.*)/', $match, $matches);
		}
		$toTest.= $match;

		if (substr_count($toTest, ';') > 0 and preg_match('/;[\\s\\S]+/', $toTest) > 0) {
			$this->error('Multiple requests are not allowed');
		}
		
		return $sql;
	}
	
	// Éxecute une requête SQL
	// execute('SQL')
	// execute('SQL', array('column' => 'subSQL')
	// execute('SQL', array('column' => 'subSQL', true)
	// execute('SQL', true)
	public function execute($sql, $columns = array(), $oneLine = false)
	{

		$this->sql = $sql;

		$sql = $this->checkSql($sql);
		if (is_array($columns)) array_walk($columns, array($this, 'checkSql'));
		
		return $this->_execute($sql, $columns, $oneLine);
	}
	
	// Autorise l'execution de requête multiple
	public function executeMultiple($sql)
	{
		$this->sql = $sql;
		return $this->_execute($sql);
	}
	
	private function _execute($sql, $columns = array(), $oneLine = false)
	{
		$result = null;
		
		if (is_bool($columns)) {
			$oneLine = $columns;
			$columns = array();
		}

		$sql = trim($sql);
		preg_match('/[a-zA-Z]*/', $sql, $query);
		if (in_array(strtolower($query[0]), array('select', 'describe', 'show'))) {
			if ($request = $this->connexion->query($sql)) {
				$request->setFetchMode(PDO::FETCH_OBJ);
				$result = $request->fetchAll();
				$request->closeCursor();
				unset($request);
				
				foreach ($columns as $column => $sql) {
					$sqlKeys = array();
					$end = 0;
					while(false !== $start = strpos($sql, '{', $end)) {
						if (false === $end = strpos($sql, '}', $start)) break;
						$sqlKeys[$start] = substr($sql, $start + 1, $end - $start - 1);
						$sql = str_replace('{' . $sqlKeys[$start] . '}', '', $sql);
					}
					foreach ($result as &$line) {

						foreach ($sqlKeys as $pos => $key) {
							$_sql = substr($sql, 0, $pos) . $this->protect($line->$key) . substr($sql, $pos);
						}

						$line->$column = $this->execute($_sql);
					}
					unset($line);
				}

				if ($oneLine) {
					if (count($result) > 0) {
						$result = $result[0];
					} else {
						$result = null;
					}
				}
			} else {
				$result = false;
			}
			
		} else {

			if (preg_match ('/INSERT INTO\s*([\S]+)\s*SET\s*([\S\s]*)/i', $sql, $match)) {
				preg_match_all('/\s*`?([_a-zA-Z]+)`?\s*=\s*(([\'"]?)(?:\\\\\3|[^\3])*?(?:[^,;]*)\3)?/', $match[2], $data);
				$sql = 'INSERT INTO ' . $match[1] . '(' . implode($data[1], ',') . ') VALUES(' . implode($data[2], ',') . ');';
			}

			$result = $this->connexion->exec($sql);
			$result = $result !== false;
			
		}
		if ($result === false) {
			$error = $this->connexion->errorInfo();	
			$this->error($error[2]);
		
		}
		$this->request_total++;
		
		return $result;	
	}
	
	// Créé une fonction si elle n'existe pas
	public function createFunction($sql, $ifNotExists = false)
	{
		preg_match('/CREATE FUNCTION ([a-zA-Z0-9_-]*)/', $sql, $name);
		$name = $name[1];
		
		$exists = false;
		$functions = $this->execute('SHOW FUNCTION STATUS WHERE Db = ' . $this->protect($this->database) . ';');
		foreach ($functions as $function) {
			if ($exists = $function['Name'] == $name) {
				break;
			}
		}

		if ($exists and !$ifNotExists) {
			$this->execute('DROP FUNCTION ' . $this->protect($name, self::FIELD) . ';');
			$exists = false;
		}
		
		if (!$exists) {
			$this->executeMultiple($sql);
		}
		
	}
	
	// Protège $value pour SQL
	public function protect($value, $type = self::STRING)
	{
		$result = $value;

		if (is_array($result)) {
			foreach ($result as &$value) {
				$value = $this->protect($value, $type);
			}
			return $result;
		}

		if ($type == self::FIELD) {
			$result = '`' . str_replace('`', '\\`', $value) . '`';
		} elseif ($type == self::INTEGER) {
			$result = $this->connexion->quote($value, $type);
			$result = (int)substr($result, 1 , strlen($result) - 2);
		} else {
			$result = $this->connexion->quote($value, $type);
		}

		return $result;
	}

	// Retourne le dernier ID auto généré par Mysql
	public function lastId()
	{	
		return $this->connexion->lastInsertId();
	}
	
	// Retourne la dernier total d'un limit
	public function lastTotal()
	{
		$result = $this->execute('SELECT FOUND_ROWS() AS total;');
		return $result[0]['total'];
	}
	
	// Retourne la date du jour
	public function now()
	{
		return date('Y-m-d H:i:s');
	}
	
	// Remet l'autoincrement à 0 dans une table
	public function resetAutoincrement($table)
	{
		return $this->execute('ALTER TABLE ' . $this->protect($table, self::FIELD) . ' AUTO_INCREMENT=0;');
	}
	
	// Éxécute des requêtes simple d'insert, update et delete selon le tableau
	/*
		$table : la table où doit être executée la requête
		$data : tableau de donnée à transmettre à mysql
				il peut être d'une dimension pour executer une seule requête ou
				de plusieurs dimensions pour executer plusieur requêtes.
				Chaques lignes doit obligatoirement comporter l'index "id" qui
				va déterminer que sera le type de requete.
				
					id > 0 : update
					id = 0 : insert
					id < 0 : delete
					
				Les autres clés doivent être nommé selon les champs à inserer ou
				à modifier.
				
				En cas d'insert, la valeur des ids est mise à jour avec les
				nouveaux ids générés.
		return : un tableau récapitulatif des requêtes executées comprenant les 
				clés 'insert', 'update' et 'delete'.
	*/
	public function executeArray($table, $data)
	{

		$records = object(
			'insert', object(
				'withId', array(),
				'withoutId', array()
			),
			'update', array(),
			'delete', array()
		);
		
		if (is_object($data))
			$data = array($data);		

		foreach($data as $line)
			if ((int)$line->id == 0)
				$records->insert->withoutId[] = $line;
			elseif (substr($line->id, 0, 1) == '+')
				$records->insert->withId[$line->id = (int)$line->id] = $line;
			elseif ($line->id > 0)
				$records->update[(int)$line->id] = $line;
			else
				$records->delete[-1*$line->id] = $line;

		// Delete
		foreach ($records->delete as $record)
			$this->execute('
				DELETE FROM
					' . $this->protect($table, self::FIELD) . '
				WHERE
					id = ' . $this->protect($record->id * -1) . '
				;
			');

		// Update
		foreach ($records->update as $record) {
			$set = array();
			foreach ($record as $field => $value)
				if (!is_object($value) and !is_array($value))
					$set[] = $this->protect($field, self::FIELD) . '=' . ($value === null ? 'NULL' : $this->protect($value));
			$this->execute('
				UPDATE
					' . $this->protect($table, self::FIELD) . '
				SET
					' . implode($set, ',') . '
				WHERE
					id = ' . $this->protect($record->id) . '
				;
			');
		}
		
		// Insert with id
		foreach ($records->insert->withId as $record) {
			$set = array();
			foreach ($record as $field => $value)
				if (!is_object($value) and !is_array($value))
					$set[] = $this->protect($field, self::FIELD) . '=' . ($value === null ? 'NULL' : $this->protect($value));
			$this->execute('
				INSERT INTO
					' . $this->protect($table, self::FIELD) . '
				SET
					' . implode($set, ',') . '
				;
			');
		}
		
		// Insert without id
		$nextId = get($this->execute('SELECT MAX(id) AS max FROM ' . $this->protect($table, self::FIELD) . ';', true), k('max')) + 1;
		foreach ($records->insert->withoutId as $record) {
			$record->id = $nextId++;
			$set = array();
			foreach ($record as $field => $value)
				if (!is_object($value) and !is_array($value))
					$set[] = $this->protect($field, self::FIELD) . '=' . ($value === null ? 'NULL' : $this->protect($value));
			$this->execute('
				INSERT INTO
					' . $this->protect($table, self::FIELD) . '
				SET
					' . implode($set, ',') . '
				;
			');
			$records->insert->withId[$record->id] = $record;
		}
		
		$records->insert = $records->insert->withId;

		return $records;
		
	}
	
	// Compare le tableau new avec (le contenu de $table / le tableau $old) selon le tableau de clé $keys et la condition $where
	public function compareArray($new, $tableOrOld, $keys = 'id', $where = null) {
	
		if (is_string($tableOrOld))
			$old = $this->execute('SELECT * FROM ' . $this->protect($tableOrOld, self::FIELD) . (is_null($where) ? '' : ' WHERE ' . $where) . ';');
		else
			$old = &$tableOrOld;

		if (!is_array($keys))
			$keys = array($keys);

		// Indexation du tableau old pour comparaison plus rapide
		$oldIndexed = array();
		foreach ($old as $line) {
			$line->id = (int)$line->id;
			$index = array();
			foreach ($keys as $key) {
				$index[] = $line->$key;
			}
			$oldIndexed[serialize($index)] = $line;
		}
		$old = $oldIndexed;

		// Comparaison
		$results = array();
		foreach ($new as $line) {
			$line->id = (int)get($line, k('id'), 0);
			$index = array();
			foreach ($keys as $key)
				$index[] = $line->$key;
			$index = serialize($index);
			$line->id = array_key_exists($index, $old) ? $old[$index]->id : '+' . $line->id;
			$results[] = $line;
			unset($old[$index]);
		}
		foreach ($old as $line) {
			$line->id*= -1;
			$results[] = $line;
		}
		return $results;
	}
	
	// Met à jour des données en comparant 2 tableaux
	public function updateArray($table, $new, $old = null, $keys = 'id', $where = null) {
		
		if (is_null($old))
			$old = $table;
		$results = $this->compareArray($new, $old, $keys, $where);
		return $this->executeArray($table, $results);

	}
	
	// Effectue un select simple sur une table
	// select(<table>[, <id>])
	// select(<table>[, <oneLine>])
	// select(<table>[, <orderBy>])
	public function select($table, $idOrOnelineOrOrderby = null)
	{
		$id = null;
		$orderBy = null;
		if (is_bool($idOrOnelineOrOrderby)) {
			$oneLine = $idOrOnelineOrOrderby;
		} elseif (is_numeric($idOrOnelineOrOrderby)) {
			$id = $idOrOnelineOrOrderby;
			$oneLine = true;
		} else {
			$oneLine = false;
			$orderBy = $idOrOnelineOrOrderby;
		}
		
		$sql = '
			SELECT
				*
			FROM
				' . $this->protect($table, self::FIELD) . '
			' . ($id !== null ? 'WHERE id = ' .  $this->protect($id) : '') . '
			' . ($orderBy !== null ? 'ORDER BY ' .  $this->protect($orderBy, self::FIELD) : '') . '
			;
		';
		return $this->execute($sql, $oneLine);
	}


}
?>
