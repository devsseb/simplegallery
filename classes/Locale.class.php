<?

class Locale
{
	private $dir = 'locales/';
	private static $index, $indexDefault;
	public $langs = array();

	public function __construct($lang = null, $dir = null)
	{
		if ($dir)
			$this->dir = $dir;
		if (!$lang) {
			$lang = explode('-',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			if (!exists($lang, 1))
				$lang[1] = $lang[0];
			$lang = strtolower($lang[0]) . '-' . strtoupper($lang[1]);
		}
		$this->lang = $lang;

		$this->langs = getDir($this->dir, '^([^.])');
		foreach ($this->langs as &$lang)
			$lang = substr($lang, 0, strlen($lang) - 5);
		unset($lang);

		if (!inDir($this->dir, $file = $this->dir . $this->lang . '.json', true))
			$file = $this->dir . 'en-US.json';

		self::$index = json_decode(file_get_contents($file));
		self::$indexDefault = json_decode(file_get_contents($this->dir . 'en-US.json'));

		eval('function l($key){
			return call_user_func_array(\'Locale::get\', func_get_args());
		}');

	}
	
	public static function get($keys)
	{
		$keys = explode('.', $keys);
		array_unshift($keys, self::$index);
		if (null === $return = geta($keys)) {
			$keys[0] = self::$indexDefault;
			if (null === $return = geta($keys)) {
				array_shift($keys);
				$return = implode('.', $keys);
			}
			
		}

		if (count($args = func_get_args()) > 1)
			$return = preg_replace_callback('/%([0-9]+)/', function($matches) use($args) {
				return get($args, k($matches[1]), $matches[0]);
			}, $return);
		
		return preg_replace('/[\\n\\r]/', '<br />', toHtml($return));
			
	}
}

?>
