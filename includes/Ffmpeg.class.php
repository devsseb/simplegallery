<?
include_once(__DIR__ . '/Shell.class.php');

class Ffmpeg
{
	private static $program = 'avconv';

	public static function getProgram()
	{
		return self::$program;
	}

	public static function getInfos($file)
	{
		$shellInfos = Shell::exec(self::getProgram() . ' -i ' . Shell::escapeFile($file));

		preg_match('/Duration: ([0-9\\.:]+)/', $shellInfos, $match);
		$duration = explode(':', $match[1]);
		$duration = $duration[0] * 3600 + $duration[1] * 60 + $duration[2];

		preg_match('/Stream.*Video.*\\s+([0-9]+)\\s+fps/', $shellInfos, $match);
		$fps = gete($match, k(1), 25);
		$frames = ceil($fps * $duration);
		
		return object(
			'duration', $duration,
			'fps', $fps,
			'frames', $frames
		);
	}
	
	public static function convert($file, $target, $options = array(), $callback = false)
	{
		$frameNumber = geta(self::getInfos($file), k('frames'));

		if (!$frameNumber)
			$frameNumber = 1;

		$passTotal = get($options, k('pass'));

		$noPass = false;
		if (!$passTotal) {
			$passTotal = 1;
			$noPass = true;
		}

		for ($pass = 1; $pass <= $passTotal; $pass++) {
			if ($noPass)
				$pass = 0;
			$cmd = self::getConvertCmd($file, $target, $options, $pass);
			Shell::exec($cmd, function($out) use ($frameNumber, $pass, $callback) {
				$lastFrame = self::getLastFrame($out);
				if ($callback)
					call_user_func($callback, $lastFrame, $frameNumber, $pass);
			});
			if ($callback)
				call_user_func($callback, $frameNumber, $frameNumber, $pass);
			if ($noPass)
				break;
		}
		
		@unlink($target . '.fpf-0.log');
		@unlink($target . '.pass1.webm');
	
	}
	
	public static function convertToWebm($file, $target, $callback = false)
	{
		$options = array(
			'video' => array(
				'-codec:v'	=> 'libvpx',
				'-cpu-used'	=> 0,
				'-b:v'		=> '500k',
				'-maxrate'	=> '500k',
				'-bufsize'	=> '1000k',
				'-qmin'		=> 10,
				'-qmax'		=> 42,
				'-vf'		=> 'scale=-1:480',
				'-threads'	=> 4 
			),
			'audio' => array(
				'-codec:a'	=> 'vorbis',
				'-b:a'		=> '128k',
				'-ac'		=> 2
			)
		);

		self::convert($file, $target, $options, $callback);
	}
	
	private static function getConvertCmd($file, $target, $options, $currentPass)
	{
		$cmd = self::getProgram() . ' -y -i ' . Shell::escapeFile($file);
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
						
						if (($value === 'libvorbis' or $value === 'vorbis') and self::getProgram() == 'avconv')
							$value = 'vorbis -strict experimental';
						
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
		self::checkError($out);
		preg_match('/frame=\s+(\d+)\s+fps=.*$/', $out, $match);
		return get($match, k(1));
	}
	
	private static function checkError($out)
	{
		if (!$out)
			return;
			
		preg_match('/^Error.*/m', $out, $match);
		if ($match) {
			$exception = new Exception($match[0]);
			$exception->verbose = $out;
			throw $exception;
		}

	}
	
	public static function getSize($file)
	{
		$infos = Shell::exec(self::getProgram() . ' -i ' . Shell::escapeFile($file));
		preg_match('/Stream.*Video.*\\s([0-9]+)x([0-9]+)/', $infos, $match);
		return object('width', (int)get($match, k(1)), 'height', (int)get($match, k(2)));
	}
	
	public static function capture($file, $percent, $target = null)
	{
	
		$time = round($percent * geta(self::getInfos($file), k('duration')) / 100);
		
		$hours = floor($time / 3600);
		$time-= $hours * 3600;
		$minutes = floor($time / 60);
		$time-= $minutes * 60;
		$time = date('H:i:s', mktime($hours, $minutes, $time));
				
		$outdir = sys_get_temp_dir() . '/simplegallery_' . uniqid() . '/';
		mkdir($outdir);
		$image = $outdir . '00000001.jpg';
//		shell_exec(self::getProgram() . ' -ss ' . $time . ' -t 1 -i ' . Shell::escapefile($file) . ' -vsync 1 -r 1 -an -y ' . Shell::escapefile($image));

		shell_exec('mplayer ' . Shell::escapefile($file) . ' -ss ' . $time . ' -frames 1 -vo jpeg:outdir=' . Shell::escapefile($outdir));
		$capture = file_get_contents($image);
		if ($target)
			rename($image, $target);
		else
			unlink($image);
		rmdir($outdir);

		return $capture;
	
	}

	public static function getExif($file)
	{
		
		$ext = pathinfo(strtolower($file), PATHINFO_EXTENSION);
		
		$orientation = 1;
		$vertical = substr($file, 0, strlen($file) - strlen($ext) - 1);
		$vertical = strtolower(pathinfo($vertical, PATHINFO_EXTENSION));
		if ($vertical == 'verticalleft')
			$orientation = 6;
		elseif ($vertical == 'verticalright')
			$orientation = 8;

		$exif = array(
			'orientation' => $orientation,
			'date' => '0000-00-00 00:00:00'
		);
		
		if ($ext == 'mts') {
		
			$link = tempnam(sys_get_temp_dir(), 'ffmpegexif_');
			unlink($link);
			symlink($file, $link.= '.' . $ext);

			$log = Shell::exec('./includes/avchd2srt-core ' . Shell::escapefile($link));
			
			preg_match('/Output file name: \'(.*?)\'/', $log, $match);
			unlink($link);

			if (!$match)
				throw new Exception($log);
			
			$exifContent = file_get_contents($match[1]);
			unlink($match[1]);
			
			preg_match('/00:00:00,000.*\n[a-zA-Z]+ ([0-9].*) \(/', $exifContent, $match);
			if ($match) {
				$dt = DateTime::createFromFormat('d-M-Y H:i:s', $match[1]);
				$exif['date'] = $dt->format('Y-m-d H:i:s');
			}
		
		}
		
		return $exif;
		
	}

}

?>
