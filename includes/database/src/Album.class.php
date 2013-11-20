<?
namespace Database\Src;

class Album extends \Database\Compilation\EntitySrc
{
	private static $getNameCallback;

	public static function setGetNameCallback($callback)
	{
		self::$getNameCallback = $callback;
	}
	
	public function getAutoName()
	{
		if ($name = $this->getName())
			return $name;
		if (self::$getNameCallback)
			return call_user_func(self::$getNameCallback, basename($this->getPath()));
		return basename($this->getPath());
	}
}
