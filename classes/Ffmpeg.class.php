<?

include_once(__DIR__ . '/Shell.class.php');

class Ffmpeg
{
	public static function getFrameNumber($file)
	{
	
		$result = Shell::exec('ffmpeg -i ' . Shell::escapeFile($file));
		preg_match('/Duration: ([0-9]{2}):([0-9]{2}):([0-9]{2}\\.[0-9]{2})/', $result, $match);
		$seconds = $match[1] * 3600 + $match[2] * 60 + $match[3];
		preg_match('/, ([0-9]+) fps,/', $result, $match);
		$fps = $match[1];
		return ceil($seconds * $fps);
	
	}
	
	public static function convert($file, $target, $options = array(), $callback = false)
	{
		$frameNumber = self::getFrameNumber($file);

		$passTotal = get($options, k('pass'));
		
		for ($pass = 1; $pass <= $passTotal; $pass++) {
			$cmd = self::getConvertCmd($file, $target, $options, $pass);
			Shell::exec($cmd, function($out) use ($frameNumber, $pass, $callback) {
				$lastFrame = self::getLastFrame($out);
				if ($callback)
					call_user_func($callback, $lastFrame, $frameNumber, $pass);
			});
			if ($callback)
				call_user_func($callback, $frameNumber, $frameNumber, $pass);
		}
		
		@unlink($target . '.fpf-0.log');
		@unlink($target . '.pass1.webm');
	
	}
	
	private static function getConvertCmd($file, $target, $options, $currentPass)
	{
		$cmd = 'ffmpeg -y -i ' . Shell::escapeFile($file);
		$cmdOptions = '';

		foreach ($options as $group => $data) {
			if ($group[0] == '-' and !is_array($data))
				$cmdOptions.= ' ' . $group . ($data === null ? '' : ' ' . $data);
			elseif (is_array($data)) {
				$pass = substr($group, -2, 2);
				if ($pass[0] == ':')
					$pass = (int)$pass[1];
				else
					$pass = 0;
				
				if ($pass == 0 or $pass == $currentPass) {
					foreach ($data as $key => $value) {
						
						$cmdOptions.= ' ' . $key . ($value === null ? '' : ' ' . $value);
						
					}
				}
			}
		}
		
		if ($currentPass == 1)
			$cmdOptions.= ' -pass 1 -passlogfile ' . Shell::escapeFile($target . '.fpf') . ' ' . Shell::escapeFile($target . '.pass1.webm');
		elseif ($currentPass == 2)
			$cmdOptions.= ' -pass 2 -passlogfile ' . Shell::escapeFile($target . '.fpf') . ' ' . Shell::escapeFile($target);
		else
			$cmdOptions.= ' ' . Shell::escapeFile($target);
	
		return $cmd . $cmdOptions;
	}
	
	private static function getLastFrame($out)
	{
		preg_match('/frame=\s+(\d+)\s+fps=.*$/', $out, $match);
		return get($match, k(1));
	}
}

?>
