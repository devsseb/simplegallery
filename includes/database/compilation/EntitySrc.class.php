<?

namespace Database\Compilation;

abstract class EntitySrc
{
	private $table;

	public function __construct($table)
	{
		$this->table = $table;
	
		if (method_exists($this, '_construct'))
			call_user_func_array(array($this, '_construct'), func_get_args());
	}

	public function getUserFunctions()
	{
	
		$class = get_called_class();
		$reflection = new \ReflectionClass($class);
		$source = file($reflection->getFileName());
		$methods = $reflection->getMethods();
		$this->userMethods = array();
		foreach ($methods as $method) {
			if ($method->class != $class or (in_array($method->name, array('_construct', '__construct'))))
				continue;

			$methodSource = implode('', array_slice($source, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));

			$this->userMethods[$method->name] = $methodSource;
		}

		return $this->userMethods;

	}

	public function compile($table)
	{
	
		$replace = array();
	
		$replace['entityPath'] = dirname(dirname(__FILE__)) . '/';
		$replace['className'] = $table->tableInstance->getEntityName();

		$attributes = array();
		$attributesFactorized = array();
		$attributesExtended = array();
		
		foreach ($table->tableInstance->getAttributes() as $attribute) {

			if (null === $default = $attribute->default and $attribute->null)
				$default = 'null';
			else
				$default = '\'' . addslashes($default) . '\'';
			$attributes[] = '\'' . $attribute->name . '\' => ' . $default;
		}
		$replace['attributes'] = implode(',' . chr(10) . chr(9) . chr(9), $attributes);
		
		foreach ($table->links as $link) {
			$attributesFactorized[] = '\'' . $link->attributeFactorized . '\' => null';
			for ($i = 2; $i < count($link->nnAttributes); $i++)
				$attributesExtended[] = '\'' . $link->nnAttributes[$i]->name . '\' => null';
		}

		$replace['attributesFactorized'] = implode(',' . chr(10) . chr(9) . chr(9), $attributesFactorized);
		
		$replace['attributesExtended'] = implode(',' . chr(10) . chr(9) . chr(9), $attributesExtended);
		
		
		$replace['userFunctions'] = implode(chr(10), $this->getUserFunctions());
		
		$return = preg_replace_callback('/\|\w+\|/', function($match) use ($replace) {
			$match = substr($match[0], 1, strlen($match[0]) - 2);
			if (exists($replace, $match))
				return $replace[$match];
		}, file_get_contents(__DIR__ . '/EntityTemplate.class.php'));
		
		return $return;
	}

}
