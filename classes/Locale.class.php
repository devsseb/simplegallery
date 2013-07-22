<?

class Locale
{
	private $dir = 'locales/';
	private static $index, $indexDefault;
	public $langs = array();
	private $langDefault = 'en-US';

	public function __construct($lang = null, $dir = null)
	{
		if ($dir)
			$this->dir = $dir;
		if ($lang) {
		
			if (!is_array($lang))
				$langs = array($lang);
			else
				$langs = $lang;
		
		} else {
			$langs = get($_SERVER, k('HTTP_ACCEPT_LANGUAGE'));//fr,fr-fr;q=0.8,en-us;q=0.5,en;q=0.3
			$langs = preg_split('/[,;]/', $langs);
			foreach ($langs as &$lang) {
				$lang = explode('-', $lang);
				if (!exists($lang, 1))
					$lang[1] = $lang[0];
				$lang = strtolower($lang[0]) . '-' . strtoupper($lang[1]);
			}
			unset($lang);
		}

		$this->langs = getDir($this->dir, '^([^.])');
		foreach ($this->langs as &$lang)
			$lang = substr($lang, 0, strlen($lang) - 5);
		unset($lang);

		$this->lang = false;
		foreach ($langs as $lang)
			if (inDir($this->dir, $this->dir . $lang . '.json', true)) {
				$this->lang = $lang;
				break;
			}
		
		if (!$this->lang)
			$this->lang = $this->langDefault;

		$file = $this->dir . $this->lang . '.json';

		if (!self::$index = json_decode(file_get_contents($file)))
			throw new Exception(json_last_error_msg());
		if (!self::$indexDefault = json_decode(file_get_contents($this->dir . $this->langDefault . '.json')))
			throw new Exception(json_last_error_msg());

		if (!function_exists('l'))
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
	
	public static function sdate($date)
	{
		if (!$date)
			return '';
		$date = strtotime($date);
		return ucfirst(self::get('date.week.' . date('N', $date))) . ' ' . date('j', $date) . ' ' . self::get('date.month.' . date('n', $date)) . ' ' . date('Y', $date);
	}
}

?>
