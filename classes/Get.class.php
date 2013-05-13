<?
class Get{

	private $alias;

	const FLAG_KEYS = "\x0b*1"; //'*' . chr(11) . '1'
	const FLAG_REF = "\x0b*2"; //'*' . chr(11) . '2'
		
	public function __construct()
	{
		$this->alias = array(
			'get' => array('name' => '__invoke', 'reference' => true),
			'geta' => array('name' => 'fromArray', 'reference' => false),
			'gete' => array('name' => '_empty', 'reference' => true),
			'getea' => array('name' => 'emptyFromArray', 'reference' => false),
			'exists' => array('name' => 'exists', 'reference' => true),
			'k' => array('name' => 'keys', 'reference' => false),
			'ref' => array('name' => 'ref', 'reference' => true)
		);
		$this->alias();
	}
	
	private function alias()
	{
			foreach ($this->alias as $alias => $function) {
				if (!function_exists($alias)) {
					eval('function ' . $alias . '(' . ($function['reference'] ? '&' : '') . '$var){
						$arguments = ' . __CLASS__ . '::args();
						$arguments[0] = &$var;
						return call_user_func_array(\'Get::' . $function['name'] . '\', $arguments);
					}');
				}
			}
			
			// create alias of Get class to global namepace
//			class_alias('\\kernel\\Get', 'get');
	}

	public static function isFlag($var, $flag)
	{
		return is_array($var) and reset($var) === $flag;
	}

//	get &$var
//	get &$var, $default
//	get &$var, k($keys)
//	get &$var, k($keys), $default
	static public function __invoke(&$var, $keysOrDefault = null, $defaultOrEmpty = null, $empty = false)
	{
		return self::fromArray(self::isFlag($keysOrDefault, self::FLAG_KEYS) ? $var : array(&$var), $keysOrDefault, $defaultOrEmpty, $empty);
	}

	// Return $default if $var !isset or empty
	public static function _empty(&$var, $keysOrDefault = null, $defaultOrEmpty = null)
	{
		return self::emptyFromArray(self::isFlag($keysOrDefault, self::FLAG_KEYS) ? $var : array(&$var), $keysOrDefault, $defaultOrEmpty);
	}
	
	// Return $default if $var !isset or empty (by array)
	public static function emptyFromArray($array, $keysOrDefault = null, $defaultOrEmpty = null, $empty = false)
	{
		if (!self::isFlag($keysOrDefault, self::FLAG_KEYS))
			$defaultOrEmpty = true;
		return self::fromArray($array, $keysOrDefault, $defaultOrEmpty, true);
	}

	static public function keys($args)
	{
		return array_merge(array(self::FLAG_KEYS), is_array($args) ? $args : func_get_args());
	}

	// Remplace isset
	// exists($var [[[, dimension1], dimension2], ...])
	// Vérifie l'existence de $var, ses dimensions si c'est un tableau et d'une méthode ou propriété si c'est un objet
	static public function exists(&$var)
	{
		if ($exists = isset($var) and 1 < $count = count($args = func_get_args())) {

			$_var = &$var;
			for ($i = 1; $i < $count; $i++) {
				if ($exists = (is_object($_var) and (property_exists($_var, $args[$i]) or method_exists($_var, $args[$i])))) {
					$_var = &$_var->$args[$i];
				} elseif ($exists = (is_array($_var) and array_key_exists($args[$i], $_var))) {
					$_var = &$_var[$args[$i]];
				} else {
					break;
				}
		
			}
	
		}
	
		return $exists;
	}

//	fromArray $array, k($keys)
//	fromArray $array, k($keys), $default
//	fromArray $arraykey, null
//	fromArray $arraykey, $default
	public static function fromArray($array, $keysOrDefault = null, $defaultOrEmpty = null, $empty = false)
	{

		if (self::isFlag($keysOrDefault, self::FLAG_KEYS)) {
			$keys = $keysOrDefault;
			$keys[0] = &$array;
			$default = $defaultOrEmpty;
		} else {
			$keys = $array;
			$keys[0] = &$array[0];
			$default = $keysOrDefault;
			$empty = $defaultOrEmpty;
		}

		if (call_user_func_array('self::exists', $keys)) {
			$_var = &$keys[0];
			$count = count($keys);
			for ($i = 1; $i < $count; $i++) {
				if (is_object($_var)) $_var = &$_var->$keys[$i];
				else $_var = &$_var[$keys[$i]];
			}
			if (!$empty or !empty($_var))
				return $_var;
		}
		return $default;
	}

	public static function ref(&$ref) {
		return array(self::FLAG_REF, &$ref);
	}
	
	public static function args($__call = true)
	{
		
		$args = self::fromArray(debug_backtrace(0), self::keys(1));
		if ($__call and $args['function'] == '__call') {
			$args = self::fromArray($args, self::keys('args', 1), array());
		} else {
			$args = self::fromArray($args, self::keys('args'), array());
		}

		$count = count($args);
		for ($i = 0; $i < $count; $i++) {
			if (self::isFlag($args[$i], self::FLAG_REF)) {
					$args[$i] = &$args[$i][1];
			}
		}
		
		return $args;
	}
}

?>
