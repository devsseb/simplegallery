<?

namespace Database\Connector;

class Sqlite extends \Database\Object\Connector
{
	public function getTypes()
	{
		return array(
			\Database::T_INTEGER => array('INTEGER'),
			\Database::T_BIG_INTEGER => array('INTEGER'),
			\Database::T_DECIMAL => array('REAL'),
			\Database::T_FLOAT => array('REAL'),
			\Database::T_BOOL => array('INTEGER'),
			\Database::T_CHAR => array('TEXT'),
			\Database::T_BIG_CHAR => array('TEXT'),
			\Database::T_BIN => array('BLOB'),
			\Database::T_DATE => array('TEXT'),
			\Database::T_TIME => array('TEXT'),
			\Database::T_DATETIME => array('TEXT'),
			\Database::T_TIMESTAMP => array('INTEGER')
		);
	}

	public function getTables()
	{
		$tables = array();
		foreach ($this->execute('SELECT * FROM sqlite_master WHERE type="table";') as $table)
			if ($table->name != 'sqlite_sequence')
				$tables[] = $table->name;
		return $tables;
	}
	
	public function getAttributes($table)
	{
		$attributes = array();
		foreach ($this->execute('pragma table_info(' . $this->protect($table, \Database::FIELD) . ');') as $attribute) {
			$type = $this->getType($attribute->type);
			$attributes[] = object(
				'name', $attribute->name,
				'type', $type->name,
				'size', $type->size,
				'primary_key', (bool)$attribute->pk,
				'auto_increment', $attribute->pk and $attribute->type == 'INTEGER',
				'null', !$attribute->notnull,
				'default', $attribute->dflt_value
			);
		}
		return $attributes;
	}
	
	public function getCreateTable($name, $attributes)
	{
		$types = $this->getTypes();

		$createAttributes = array();
		foreach ($attributes as $attribute) {

			// Name
			$createAttribute = $this->protect($attribute->name, \Database::FIELD);
			// Type
			$createAttribute.= ' ' . $types[$attribute->type][0];
			// Size
			if (!exists($attribute, 'size'))
				$attribute->size = null;
			if ($attribute->size)
				$createAttribute.= '('.$attribute->size.')';
			// Primary key
			if (!exists($attribute, 'primary_key'))
				$attribute->primary_key = false;
			if ($attribute->primary_key)
				$createAttribute.= ' PRIMARY KEY';
			// Auto increment
			if (!exists($attribute, 'auto_increment'))
				$attribute->auto_increment = false;
			if ($attribute->auto_increment)
				$createAttribute.= ' AUTOINCREMENT';
			// Null
			if (!exists($attribute, 'null'))
				$attribute->null = false;
			$createAttribute.= ($attribute->null ? '' : ' NOT') . ' NULL';
			// Default
			if (!exists($attribute, 'default'))
				$attribute->default = null;
			if ($attribute->default)
				$createAttribute.= ' DEFAULT ' . $this->protect($attribute->default);
				
			$createAttributes[] = $createAttribute;
		}
		
		return 'CREATE TABLE ' . $this->protect($name, \Database::FIELD) . ' (' . implode(',',$createAttributes) . ');';
		
	}
	
	public function getAlterTable($name, $attributes)
	{

		foreach ($attributes as $id => $attribute)
			if ($attribute->alter == 'delete')
				unset($attributes[$id]);

		$tmp_name = $name . '_' . uniqid();

		$alter = 'BEGIN TRANSACTION;' . chr(10);
		$alter.= $this->getCreateTable($tmp_name, $attributes) . chr(10);

		$alterAttributes = array();
		foreach ($attributes as $attribute)
			if (substr($attribute->alter, 0, 6) == 'update')
				$alterAttributes[] = $this->protect($attribute->name, \Database::FIELD);

		$alter.= 'INSERT INTO ' . $this->protect($tmp_name, \Database::FIELD) . '(' .implode(',',$alterAttributes) . ') SELECT ' . implode(',',$alterAttributes) . ' FROM ' . $this->protect($name, \Database::FIELD) . ';' . chr(10);
		$alter.= 'DROP TABLE ' . $this->protect($name, \Database::FIELD) . ';' . chr(10);
		$alter.= 'ALTER TABLE ' . $this->protect($tmp_name, \Database::FIELD) . ' RENAME TO ' . $this->protect($name, \Database::FIELD) . ';' . chr(10);
		$alter.= 'COMMIT;';
		
		return $alter;
		
	}

	public function getDropTable($name)
	{
		return 'DROP TABLE ' . $this->protect($name, \Database::FIELD) . ';';
	}

}
