<?
	if (is_file('./config.php'))
		go('?');
	
	new Locale();
	
	$_SESSION = array();
	
	if (exists($_POST, 'privatePath')) {
	
		$path = $_POST['privatePath'];
		
		if (substr($path, -1) != '/')
			$path.= '/';
		
		$content = '<?' . chr(10);
		$content.= '	$config->privatePath = \'' . addslashes($path) . '\';' . chr(10);
		$content.= '?>';
		
		file_put_contents('./config.php', $content);
		
		if (!is_dir($path . 'albums'))
			mkdir($path . 'albums', 0777, true);
		if (!is_dir($path . 'config'))
			mkdir($path . 'config', 0777, true);

		file_put_contents($path . '.htaccess', 'order deny,allow' . chr(10) . 'deny from all');
		
		$pathConfig = $path . 'config/';
		if (!is_file($file = $pathConfig . 'users.json'))
			file_put_contents($file, '{}');
		if (!is_file($file = $pathConfig . 'groups.json'))
			file_put_contents($file, '["admins"]');
		if (!is_file($file = $pathConfig . 'parameters.json'))
			file_put_contents($file, '{}');
		if (!is_file($file = $pathConfig . 'dimensions.json'))
			file_put_contents($file, '[{"size":500	, "type":"long"},{"size":75	, "type":"short"},{"size":1000, "type":"long"}]');
		
	
		success(l('install.message.success'), '?user=registration');
	}
?>
