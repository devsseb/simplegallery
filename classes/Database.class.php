<?php

/**
 * Classe de gestion de base de données
 *
 * @author		Sébastien DERRÉ
 * @copyright	2011 DIP
 * @version		1.0
*/

class Database
{
	
	private $connexion = null;
	
	private $server = '';
	private $port = 0;
	private $database = '';
	private $user = '';
	private $password = '';
	private $mail = array();
	
	public $prefix;
	public $engine = '';
	public $charset = '';
	public $collate = '';
	public $request_total = 0;
	
	const STRING	= PDO::PARAM_STR;
	const INTEGER	= PDO::PARAM_INT;
	const NULL		= PDO::PARAM_NULL;
	const OBJECT	= PDO::PARAM_LOB;
	const BOOLEAN	= PDO::PARAM_BOOL;
	const FIELD		= 10;
	
	public function __construct(Array $configuration)
	{
		$this->server = $this->get($configuration['server'], 'localhost');
		$this->port = $this->get($configuration['port'], 3306);
		$this->database = $this->get($configuration['database'], 'database');
		$this->user = $this->get($configuration['user'], 'root');
		$this->password = $this->get($configuration['password'], '');
		$this->prefix = $this->get($configuration['prefix'], '');
		$this->engine = $this->get($configuration['engine'], 'InnoDB');
		$this->charset = $this->get($configuration['charset'], 'utf8');
		$this->collate = $this->get($configuration['collate'], 'utf8_general_ci');
		$this->mail = $this->get($configuration['mail'], array());
		if (!is_array($this->mail)) {
			$this->mail = array($this->mail);
		}
		
	}
	
	// Retourne $default si $variable n'est pas instancier
	private function get(&$variable, $default = null)
	{
		return isset($variable) ? $variable : $default;
	}
	
	// Connecte la base de données
	public function connect()
	{
		$this->sql = 'Connexion ...';
		try {
			$this->connexion = new PDO('mysql:host=' . $this->server . ';port=' . $this->port . ';dbname=' . $this->database, $this->user, $this->password);
			if ($this->charset == 'utf8') {
				$this->execute('SET CHARACTER SET UTF8');
			}
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
	
	
	private function error($message)
	{
		$message = $_SERVER['REQUEST_URI'] . chr(10) . $message;
	
		foreach ($this->mail as $mail) {
			mail($mail, '[DEV CyberCE] SQL Error on ' . $this->database, $message . chr(10) . chr(10) . $this->sql);
		}
		throw new Exception($message);
	}
	
	public function checkSql($sql)
	{
		$sql = trim($sql);
		
		$match = $sql;
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
	public function execute($sql, $oneLine = false)
	{

		$this->sql = $sql;

		$sql = $this->checkSql($sql);

		return $this->_execute($sql, $oneLine);
	}
	
	// Autorise l'execution de requête multiple
	public function executeMultiple($sql)
	{
		$this->sql = $sql;
		return $this->_execute($sql);
	}
	
	private function _execute($sql, $oneLine = false)
	{
		$result = null;

		$sql = trim($sql);

		preg_match('/[a-zA-Z]*/', $sql, $query);
		if (in_array(strtolower($query[0]), array('select', 'describe', 'show'))) {
			if ($request = $this->connexion->query($sql)) {
				$request->setFetchMode(PDO::FETCH_ASSOC);
				$result = $request->fetchAll();
				$request->closeCursor();
				unset($request);
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
	{	$result = $value;
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
		$table : la table ou doit être executée la requête
		$data : référence du tableau de donnée à transmettre à mysql
				il peut être d'une dimension pour executer une seule requête ou
				de plusieurs dimensions pour executer plusieur requêtes.
				Chaques lignes doit obligatoirement comporter l'index 'id' qui
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
	public function executeArray($table, &$data)
	{
		$results = array('insert' => 0, 'update' => 0, 'delete' => 0);

		if (isset($data['id'])) {
			
			
			if ($data['id'] >= 0) {
				$set = array();
				foreach ($data as $field => $value) {
					$set[] = $this->protect($field, self::FIELD) . '=' . $this->protect($value);
				}
				
				$this->execute('
					' . ($data['id'] == 0 ? 'INSERT INTO' : 'UPDATE') . '
						' . $this->protect($table, self::FIELD) . '
					SET
						' . implode($set, ',') . '
					' . ($data['id'] == 0 ? '' : 'WHERE id = ' . $this->protect($data['id'])) . '
					;
				');
				$results[$data['id'] == 0 ? 'insert' : 'update']++;
			
				if ($data['id'] == 0) {
					$data['id'] = $this->lastId();
				}
			
			} else {
			
				$this->execute('
					DELETE FROM
						' . $this->protect($table, self::FIELD) . '
					WHERE
						id = ' . $this->protect($data['id'] * -1) . '
					;
				');
				$results['delete']++;
				
			}
			
			
		} else {
			
			foreach($data as $line) {
				$result = $this->executeArray($table, $line);
				$results['insert']+= $result['insert'];
				$results['update']+= $result['update'];
				$results['delete']+= $result['delete'];
			}
			
		}

		return $results;
		
	}
	
	// Compare le tableau new avec (le contenu de $table / le tableau $old) selon le tableau de clé $keys et la condition $where
	public function compareArray($new, $tableOrOld, $keys = 'id', $where = null) {
	
		if (is_string($tableOrOld)) {
			$old = $this->execute('SELECT * FROM ' . $this->protect($tableOrOld, self::FIELD) . (is_null($where) ? '' : ' WHERE ' . $where) . ';');
		} else {
			$old = &$tableOrOld;
		}

		if (!is_array($keys)) {
			$keys = array($keys);
		}
	
		// Indexation du tableau old pour comparaison plus rapide
		$oldIndexed = array();
		foreach ($old as $line) {
			$index = array();
			foreach ($keys as $key) {
				$index[] = $line[$key];
			}
			$index = implode(chr(9), $index);
			$oldIndexed[$index] = $line;
		}
		$old = $oldIndexed;
		
		// Comparaison
		$results = array();
		foreach ($new as $line) {
			$index = array();
			foreach ($keys as $key) {
				$index[] = $line[$key];
			}
			$index = implode(chr(9), $index);
			$results[] = array('id' => isset($old[$index]) ? $old[$index]['id'] : 0) + $line;
			unset($old[$index]);
		}
		foreach ($old as $line) {
			$line['id']*= -1;
			$results[] = $line;
		}
		return $results;
	}
	
	// Met à jour des données en comparant 2 tableaux
	public function updateArray($table, $new, $old = null, $keys = 'id', $where = null) {
		
		if (is_null($old)) {
			$old = $table;
		}
		$results = $this->compareArray($new, $old, $keys, $where);
		return $this->executeArray($table, $results);

	}
	
}

?>
