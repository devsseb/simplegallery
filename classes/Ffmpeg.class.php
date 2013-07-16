<?

include_once(__DIR__ . '/Shell.class.php');

class Ffmpeg
{
	public static function getFrameNumber($file)
	{
		$infos = Shell::exec('ffmpeg -i ' . Shell::escapeFile($file));

		preg_match('/Duration: ([0-9\\.:]+)/', $infos, $match);
		$duration = explode(':', $match[1]);
		$duration = $duration[0] * 3600 + $duration[1] * 60 + $duration[2];

		preg_match('/Stream.*Video.*\\s+([0-9]+)\\s+fps/', $infos, $match);
		$fps = gete($match, k(1), 25);
		return ceil($fps * $duration);
		
		return (int)Shell::exec('ffmpeg -i ' . Shell::escapeFile($file) . ' -vcodec copy -an -f null /dev/null 2>&1 | grep \'frame=\' | sed \'s/\s\s*/ /g\' | cut -f 2 -d \' \'');
	}
	
	public static function convert($file, $target, $options = array(), $callback = false)
	{
		$frameNumber = self::getFrameNumber($file);

		if (!$frameNumber)
			$frameNumber = 1;

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
	
	public static function getSize($file)
	{
		$infos = Shell::exec('ffmpeg -i ' . Shell::escapeFile($file));
		preg_match('/Stream.*Video.*\\s([0-9]+)x([0-9]+)/', $infos, $match);
		return object('width', (int)get($match, k(1)), 'height', (int)get($match, k(2)));
	}
	
	public static function capture($file, $percent, $target = null)
	{
	
		$result = shell_exec('ffmpeg -i ' . Shell::escapeFile($file) . ' 2>&1');

		preg_match('/Duration: ([0-9]{2}):([0-9]{2}):([0-9]{2}\\.[0-9]{2})/', $result, $match);

		$time = $match[1] * 3600 + $match[2] * 60 + $match[3];
		$time = round($percent * $time / 100);
		
		$hours = floor($time / 3600);
		$time-= $hours * 3600;
		$minutes = floor($time / 60);
		$time-= $minutes * 60;
		$time = date('H:i:s', mktime($hours, $minutes, $time));
				
		$image = $target ? $target : sys_get_temp_dir() . '/simplegallery_' . uniqid();
		shell_exec('ffmpeg -ss ' . $time . ' -t 1 -i ' . Shell::escapefile($file) . ' -f mjpeg ' . Shell::escapefile($image));
		
		$capture = file_get_contents($image);
		
		if (!$target)
			unlink($image);

		return $capture;
	
	}

}

?>
