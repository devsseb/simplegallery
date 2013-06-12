<?

class Shell
{
	public static function exec($cmd, $callback = false)
	{
		if (!$callback)
			return shell_exec($cmd . ' 2>&1');
		
		$timeout = (int)ini_get('max_execution_time');
		$timeStart = chronoStart('shell_verbose');
		$verbose = tempnam(sys_get_temp_dir(), '');
		$contentOld = false;

		shell_exec($cmd . '> ' . $verbose . ' 2>&1 &');

		do {
			$content = file_get_contents($verbose);
			if ($content === false or ($contentOld !== false and $content == $contentOld)) {
				if (chronoGet('shell_verbose') > $timeout)
					break;
				else
					continue;
			}
			call_user_func($callback, substr($content, strlen($contentOld)));
			$contentOld = $content;
			resetTimout();
			$timeStart = chronoStart('shell_verbose');
			sleep(2);
		} while(1);		
		
		unlink($verbose);
		
		return $contentOld;
	}
	
	public static function escapeFile($file)
	{
		return '"' . str_replace('"', '\\"', $file) . '"';
	}
}

?>
