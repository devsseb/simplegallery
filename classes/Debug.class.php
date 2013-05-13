<?

class Debug {

	public $colors, $styles, $functions;
	
	public function __construct($enable = null)
	{

		$this->setDefault();
		$this->enable(is_bool($enable) or $enable = get($_SESSION, k('debug')));

		$this->alias = array(
			'trace' => 'trace',
			'tracec' => 'color',
			'color' => 'color',
			'quit' => 'quit',
			'trace_stack' => 'stack'
		);
		$this->alias();
	}

	public function __invoke() {
		call_user_func_array(array($this, 'trace'), func_get_args());
	}

	private function setDefault()
	{
		$this->colors = (object)array('backgroundOdd' => '#ccc', 'backgroundEven' => '#efefef', 'main' => '#335ea8', 'interline' => '#999', 'font' => 'inherit');
		$this->styles = '';
	}

	private function alias()
	{
		foreach ($this->alias as $alias => $function)
			if (!function_exists($alias))
				eval('function ' . $alias . '(){
					$debug = new Debug();
					call_user_func_array(array($debug, \'' . $function . '\'), func_get_args());
				}');

		// create alias of Get class to global namepace
		if (!class_exists('debug')) class_alias('Debug', 'debug');
	}
	
	static public function enable($enable)
	{
		$_SESSION['debug'] = (bool)$enable;
	}
	
	private function html($message)
	{
		$result = '';

		if ($_SESSION['debug'])
			if (is_array($message)) {
				foreach ($message as &$value) {
					$value = $this->html($value);
				}
				unset($value);
				$result = $message;
			} elseif (is_null($message))
				$result = '<span style="font-style:italic;">null</span>';
			elseif ($message === '')
				$result = '<span style="font-style:italic;">empty string</span>';
			elseif (is_string($message))
				$result = print_r(toHtml($message), true);
			elseif (is_bool($message))
				$result = '<span style="font-style:italic;">' . ($message ? 'true' : 'false') . '</span>';
			else
				$result = print_r($message, true);
	
		return $result;
	}
	
	public function trace()
	{
		if ($_SESSION['debug']) {
			$args = func_get_args();
			echo '<div style="border:1px solid ' . $this->colors->main . ';background:' . $this->colors->backgroundOdd . ';text-align:left;margin:1px 0px;overflow:auto;color:' . $this->colors->font . ';font:12px monospace;' . $this->styles . '">';
			foreach ($args as $index => $message) {
				echo '<pre style="margin:0px;' . ($index > 0 ? 'border-top:1px dotted ' . $this->colors->interline . ';' : '') . ($index%2 == 0 ? '' : 'background-color:' . $this->colors->backgroundEven . ';') . '">';

					$message = print_r($this->html($message), true);
					echo preg_replace('/(\\[.*?\\])( => .*?\n\\()/', '<strong>$1</strong>$2', $message);
		
				echo '</pre>';
			}
			echo '</div>';
		}
		
		$this->setDefault();
	}

	public function color()
	{
		$args = func_get_args();
		$this->colors->font = array_shift($args);
	
		call_user_func_array(array($this, 'trace'), $args);
	}

	public function quit()
	{
		if ($_SESSION['debug']){
			$args = func_get_args();
			if (count($args) > 0) {
				call_user_func_array(array($this, 'trace'), $args);
			}
			exit();
		}
	}

	public function stack($return = false)
	{
		$result = array();
		$stack = debug_backtrace();
		array_shift($stack);
		foreach ($stack as $line) {
			$args = array();
			foreach (get($line, k('args'), array()) as $arg) {
				switch (true) {
					case is_array($arg) : 
						$args[] = 'Array(' . count($arg) . ')';
					break;
					case is_null($arg) :
						$args[] = 'null';
					break;
					case is_string($arg) :
						$args[] = '"' . $arg . '"';
					break;
					case is_bool($arg) :
						$args[] = $arg ? 'true' : 'false';
					break;
					case is_object($arg) :
						$name = '';
						if (method_exists($arg, '__toString')) {
							$name = '*' . $arg;
						}					
						$args[] = 'Object(' . get_class($arg) . $name . ')';
					break;
					case is_numeric($arg) :
						$args[] = $arg;
					break;
					default :
						$args[] = '<' . $arg . '>';
				}
			}
			$class = '';
			if (exists($line, 'object')) {
				$class = $line['class'];
				if (method_exists($line['object'], '__toString')) {
					$class.= '*' . $line['object'];
				}
				$class.= $line['type'];
			}
			$result[] = $class . $line['function'] . '(' . implode(',', $args) . ')' . (exists($line, 'file') ? ' in file ' . $line['file'] . '(' . $line['line'] . ')' : '');
		}
		if ($return) return $result;
		$this->trace($result);
	}

}

?>
