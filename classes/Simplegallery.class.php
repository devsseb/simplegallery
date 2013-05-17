<?

class Simplegallery
{
	private $newAlbums, $lastId, $passwordLostTimeOut = 'PT2H';

	public function __construct($root)
	{
		$this->root = $root;
		$this->pathConfig = $this->root . 'config/';
		$this->pathAlbums = $this->root . 'albums/';
		$this->pathThumbs = $this->root . 'thumbs/';

		$this->config = new StdClass();
		$files = getDir($this->pathConfig);
		foreach ($files as $file) {
			$name = basename($file, '.json');
			$this->config->$name = json_decode(file_get_contents($this->pathConfig . $file));
		}
		$this->locale = new Locale(get($this->config->parameters, k('locale')));
	}
	
	public function loadAlbums($dir = null)
	{
		if (exists($this, 'albums'))
			return;
	
		$this->lastId = 0;
		$this->albums = new StdClass();
		$this->newAlbums = array();
		$this->albums->tree = $this->getAlbumsTree();
		foreach ($this->newAlbums as &$album)
			file_put_contents($album->path . '.id', $album->id = ++$this->lastId);
		unset($album);
		$this->albums->indexed = $this->getAlbumsIndexed();

		unset($this->newAlbums);

		if (!$this->albums->tree and $this->user->admin)
			success(l('album.message.welcome', $this->pathAlbums));
	}
	
	private function getAlbumsTree($dir = null, &$albumParent = null)
	{
		if (!$dir)
			$dir = $this->pathAlbums;
		$albums = getDir($dir);

		foreach ($albums as $i => &$album) {
			if (!is_string($album)) continue;
			if (!is_dir($dir . $album)) {
				unset($albums[$i]);
				continue;
			}
			
			$album = (object)array('name' => $album);
			$album->path = $dir . $album->name . '/';
			if (is_file($album->path . '.id')) {
				$album->id = file_get_contents($album->path . '.id');
				if ($album->id > $this->lastId) $this->lastId = $album->id;
			} else {
				$this->newAlbums[] = &$album;
				$album->id = 0;
			}
			$album->parent = &$albumParent;

			if (is_file($file = $this->pathThumbs . $album->id . '/.config.json'))
				$album->config = json_decode(file_get_contents($file));
			else
				$album->config = new StdClass();

			$album->groups = array();
			$groupsAllow = array('admins');
			foreach ($this->config->groups as $group) {
				if ($group == 'admins')
					continue;
			
				$access = get($album->config, k('groups', $group));
				if ($access !== null)
					$album->groups[$group] = $access;
				else {
					if ($albumParent)
						$album->groups[$group] = $albumParent->groups[$group] < 0 ? $albumParent->groups[$group] : $albumParent->groups[$group] - 2;
					else
						$album->groups[$group] = -2;
				}
				if ($album->groups[$group] == -1 or $album->groups[$group] == 1)
					$groupsAllow[] = $group;
			}
			
			$album->children = array();
			if (array_intersect($this->user->groups, $groupsAllow))
				$album->children = array_merge($album->children, $this->getAlbumsTree($album->path, $album));
			elseif ($albumParent) {
				unset($albums[$i]);
				$albumParent->children = array_merge($albumParent->children,$this->getAlbumsTree($album->path, $albumParent)); 
			} else {
				unset($albums[$i]);
				array_splice($albums, $i, 0, $this->getAlbumsTree($album->path));
			}
				
			
		}
		unset($album);

		return $albums;
	}
	
	private function getAlbumsIndexed($albums = null)
	{
		if (!is_array($albums))
			$albums = $this->albums->tree;
	
		$indexed = array();
		foreach ($albums as &$album) {
			$indexed[$album->id] = &$album;
			$indexed+= $this->getAlbumsIndexed($album->children);
		}
		unset($album);
		return $indexed;
	}
	
	public function getAlbum($id)
	{
		$id = (int)$id;
		
		if (!$album = get($this->albums->indexed, k($id)))
			error(l('album.message.error'), '?');

		$album = &$this->albums->indexed[$id];
		$medias = getDir($album->path);

		foreach ($medias as $i => $media) {
			if ($media[0] == '.' or !is_file($mediaFile = $album->path . $media)) {
				unset($medias[$i]);
				continue;
			}

			if (is_file($fileInfos = $this->pathThumbs . $album->id . '/' . $media . '.json'))
				$infos = json_decode(file_get_contents($fileInfos));
			else
				$infos = new StdClass();
				
			$infos->name = $media;
			$medias[$i] = $infos;
		}

		$album->medias = $medias;
		
		return $album;
	}
	
	public function albumUpdate($id, $data)
	{
		$album = $this->getAlbum($id);
		
		$data['name'] =str_replace(array('/\\:<>?*"|'), '_', trim($data['name']));
		if ($data['name'] and $data['name'] != $album->name)
			rename($album->path, dirname($album->path) . '/' . $data['name']);
		
		$access = $data['access'];
		foreach ($access as $group => $value) {
			if ($value == 0 or $value == 1)
				continue;
			unset($access[$group]);
		}
		$album->config->groups = $access;
		
		$this->albumSaveConfig($album->id);
		
		success(l('album.message.update-success'), '?album&id=' . $album->id);
	}
	
	public function albumSaveConfig($id)
	{
		$album = $this->getAlbum($id);
		if (!is_dir($dir = $this->pathThumbs . $album->id))
			mkdir($dir, 0777, true); 
		file_put_contents($dir . '/.config.json', json_encode($album->config));
	}
	
	public function getMedia($albumId, $media, $dim = null)
	{
		$this->loadAlbums();
		
		$albumId = (int)$albumId;
		if (!$album = get($this->albums->indexed, k($albumId)))
			die('Media not found (1)');
		
		$mediaFile = $album->path . $media;
		if (!in_dir($album->path, $mediaFile, true))
			die('Media not found (2)');
		
		if (!$mediaType = $this->getMediaType($mediaFile))
			die('Media not found (3)');
			
		if ($dim) {
			foreach ($this->config->dimensions as $dimension)
				if ($dimension->size . '-' . $dimension->type == $dim) {
					$dim = $dimension;
					break;
				}
			
			if (is_string($dim))
				die('Media not found (4)');
			
			if (!is_dir($pathThumbs = $this->pathThumbs . $album->id . '/'))
				mkdir($pathThumbs, 0777, true); 
			
			
			if (is_file($fileInfos = $pathThumbs . $media . '.json'))
				$infos = json_decode(file_get_contents($fileInfos));
			else
				$infos = new StdClass();
			
			$overwrite = false;
			$md5 = md5_file($mediaFile);
			if ($overwrite = get($infos, k('md5')) != $md5)
				foreach ($this->config->dimensions as $dimension)
					if (is_file($file = $pathThumbs  . $media . '.' . $dimension->size . '-' . $dimension->type . '.jpg'))
						unlink($file);

			$file = $pathThumbs  . $media . '.' . $dim->size . '-' . $dim->type . '.jpg';
			if (!is_file($file)) {

				if ($mediaType == 'video')
					$mediaFile = $this->videoTakeCapture($mediaFile);
			
				$img = imagecreatefromstring(file_get_contents($mediaFile));
				$geometry = getimagesize($mediaFile);		

				$geometry = array('width' => $geometry[0], 'height' => $geometry[1]);
		
				if ($geometry['width'] >= $geometry['height']) {
					$width = $dim->type == 'long' ? $dim->size : floor($dim->size * $geometry['width'] / $geometry['height']);
					$height = $dim->type == 'long' ? floor($dim->size * $geometry['height'] / $geometry['width']) : $dim->size;
				} else {
					$width = $dim->type == 'long' ? floor($dim->size * $geometry['width'] / $geometry['height']) : $dim->size;
					$height = $dim->type == 'long' ? $dim->size : floor($dim->size * $geometry['height'] / $geometry['width']);
				}
				
				$infos->width = $geometry['width'];
				$infos->height = $geometry['height'];
//				$infos->orientation = geta(exif_read_data($mediaFile), k('Orientation'));

				$thumb = imagecreatetruecolor($width, $height);
				imagecopyresampled($thumb, $img, 0, 0, 0, 0, $width, $height, $geometry['width'], $geometry['height']);
				imagejpeg($thumb, $file);
				imagedestroy($thumb);		
				imagedestroy($img);
				
				if ($mediaType == 'video')
					unlink($mediaFile);

			}

			if ($overwrite) {
				$infos->md5 = $md5;
				file_put_contents($fileInfos, json_encode($infos));
			}
		} else {
			$md5 = md5_file($mediaFile);
			$file = $mediaFile;
		}
		

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$typemime = finfo_file($finfo, $file);
		finfo_close($finfo);

		$etag = md5($time = gmdate('r', filemtime($file)) . $md5);

		header("Cache-Control:private");
		header('ETag:"' . $etag . '"');
		header('Last-Modified:' . $time);
		header('Content-Type: ' . $typemime);

		if (
			(
				$headerSince = get($_SERVER, k('HTTP_IF_MODIFIED_SINCE')) or
				$headerMatch = get($_SERVER, k('HTTP_IF_NONE_MATCH'))
			) and (
				$headerSince == $time or $headerMatch == $etag
			)
		) {
			header('HTTP/1.1 304 Not Modified');
			exit();
		}

		readfile($file);
		exit();
		
	}
	
	private function videoTakeCapture($video)
	{
	
		$result = shell_exec('ffmpeg -i ' . $this->escapefile($video) . ' 2>&1');

		preg_match('/Duration: ([0-9]{2}):([0-9]{2}):([0-9]{2}\\.[0-9]{2})/', $result, $match);

		$time = $match[1] * 3600 + $match[2] * 60 + $match[3];
		
		$hours = floor($time / 3600);
		$time-= $hours * 3600;
		$minutes = floor($time / 60);
		$time-= $minutes * 60;
		$time = date('H:i:s', mktime($hours, $minutes, $time));
				
		$image = sys_get_temp_dir() . '/simplegallery_' . uniqid();
		shell_exec('ffmpeg -ss ' . $time . ' -t 1 -i ' . $this->escapefile($video) . ' -f mjpeg ' . $this->escapefile($image));
		
		return $image;
	
	}
	
	private function escapefile($file)
	{
		return '"' . str_replace('"', '\\"', $file) . '"';
	}
	
	public function getMediaType($media)
	{
		$ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));
		if (in_array($ext, array('jpg', 'jpeg', 'png')))
			return 'image';
		elseif (in_array($ext, array('webm', 'mp4')))
			return 'video';

		return false;
	}
	
	public function getUser()
	{

		if (!$login = get($_SESSION, k('login')))
			return false;

		$this->user = get($this->config->users, k($login));
		$this->user->login = $login;
		if (!exists($this->user, 'groups'))
			$this->user->groups = array();
			
		$this->user->admin = in_array('admins', $this->user->groups);
			
		return true;
	}
	
	public function userLogin($login, $password)
	{
		$login = trim(strtolower($login));
		
		if (!$this->user = get($this->config->users, k($login)))
			error(l('user.message.login-error'), '?');
			
		if ($this->user->active != 1)
			error(l('user.message.login-error'), '?');

		if ($this->user->password != crypt($password, $this->user->password))
			error(l('user.message.login-error'), '?');

		$_SESSION['login'] = $login;
		
		success(l('user.message.login-success', $login), '?');
	}
	
	public function userLogout()
	{
		unset($_SESSION['login']);
		
		success(l('user.message.logout'), '?');
	}
	
	public function userRegistration($name, $mail, $login, $password, $passwordCheck)
	{
		$login = stripAccents(strtolower(trim($login)));
		
		$this->config->users = json_decode(file_get_contents($this->pathConfig . 'users.json'));
		
		if (exists($this->config->users, $login))
			error(l('user.message.login-update-error'), '?user=registration');

		if ($password !== $passwordCheck)
			error(l('user.message.password-update-error'), '?user=registration');
			
		$user = new StdClass();
		$user->login = $login;
		$user->name = $name;
		$user->mail = $mail;
		$user->password = $this->userCryptPassword($password);
		$user->active = randomString(12);
		if (!(bool)(array)$this->config->users)
			$user->groups = array('admins');

		$this->config->users->$login = $user;
		file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));
		
		$link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '&rcode=' . $user->active;
		$this->mail($mail, '[SimpleGallery] Registration, please active your account', 'Please click on this link for activate your account :<br /><a href="' . $link . '">' . $link . '</a>');
	
		success(l('user.message.registration-active-link-sent'), '?');
	}
	
	private function userCryptPassword($password)
	{
		$salt = '';
		$salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
		for($i=0; $i < 22; $i++) {
		  $salt .= $salt_chars[array_rand($salt_chars)];
		}
		return crypt($password, sprintf('$2a$%02d$', 7) . $salt);
	}
	
	public function userActive($code)
	{
		if (strlen($code) != 12)
			error(l('user.message.registration-active-invalid-code'), '?user=registration');
	
		foreach ($this->config->users as &$user) {
			if ((string)$user->active != (string)$code)
				continue;

			$user->active = true;
			file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));
			success(l('user.message.registration-active-success'), '?');
		}
		unset($user);
		
		error(l('user.message.registration-active-invalid-code'), '?user=registration');
	}
	
	public function userUpdate($data)
	{

		if ($data['password'])
			if ($data['password'] !== $data['password-check'])
				error(l('user.message.password-update-error'), '?user=profil');
		
		$login = $this->user->login;
		$user = &$this->config->users->$login;
		$user->name = $data['name'];
		
		if ($mailUpdate = $user->mail != $data['mail']) {
			$user->mailUpdate = $data['mail'];
			$user->mailUpdateCode = randomString(12);
			
			$link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '&mcode=' . $user->mailUpdateCode;
			$this->mail($user->mailUpdate, '[SimpleGallery] Mail update, please valid your mail', 'Please click on this link for activate your mail :<br /><a href="' . $link . '">' . $link . '</a>');
		}
		if ($data['password'])
			$user->password = $this->userCryptPassword($data['password']);
		
		file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));
		success(l('user.message.user-update-success') . ($mailUpdate ? '<br />' . l('user.message.mail-update-link-sent') : ''), '?user=profil');
	}
	
	public function userUpdateMail($code)
	{

		if (strlen($code) != 12)
			error(l('user.message.mail-update-invalid-code'), '?');
	
		foreach ($this->config->users as &$user) {
			if ((string)get($user, k('mailUpdateCode')) != (string)$code)
				continue;

			$user->mail = $user->mailUpdate;
			unset($user->mailUpdate);
			unset($user->mailUpdateCode);
			file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));
			success(l('user.message.mail-update-success'), '?user=profil');
		}
		unset($user);
		
		error(l('user.message.mail-update-invalid-code'), '?');

	}
	
	public function userDelete($login)
	{
		if (!$this->config->users->$login)
			error(l('user.message.no-found'), '?admin');
			
		unset($this->config->users->$login);
		file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));
		
		success(l('admin.message.user-delete-success'), '?admin');
	}
	
	public function adminUpdate($data)
	{
		$this->config->parameters->name = $data['name'];
		$this->config->parameters->locale = $data['locale'];
		file_put_contents($this->pathConfig . 'parameters.json', json_encode($this->config->parameters));
	
		$this->config->groups = preg_split('/\W+/', $data['groups']);
		file_put_contents($this->pathConfig . 'groups.json', json_encode($this->config->groups));
		
		foreach ($data['usersGroups'] as $login => $groups)
			$this->config->users->$login->groups = array_keys($groups);
		file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));
		
		success(l('admin.message.parameters-update-success'), '?admin');
	}
	
	public function mediaUpdate($albumId, $media, $update)
	{
	
		$this->loadAlbums();

		$album = $this->getAlbum($albumId);

		$mediaFile = $album->path . $media;
		if (!in_dir($album->path, $mediaFile, true))
			die('Media not found');
			
		$updated = true;

		switch ($update) {
			case 'rotateLeft' :
			case 'rotateRight' :
				$img = imagecreatefromstring(file_get_contents($mediaFile));		
				$img = imagerotate($img, $update == 'rotateLeft' ? 90 : 270, 0);
				imagejpeg($img, $mediaFile, 85);
			break;
			case 'delete' :
				unlink($mediaFile);
			break;
			case 'download' :
				$updated = false;
				header('Pragma: public'); 	// required
				header('Expires: 0');		// no cache
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Last-Modified: '.gmdate ('D, d M Y H:i:s', filemtime ($mediaFile)).' GMT');
				header('Cache-Control: private',false);
				header('Content-Disposition: attachment; filename="'.basename($mediaFile).'"');
				header('Content-Transfer-Encoding: binary');
				header('Content-Length: '.filesize($mediaFile));	// provide file size
				header('Connection: close');
				$this->getMedia($albumId, $media);
			break;
		}
		
		if ($updated)
			foreach ($this->config->dimensions as $dimension)
				if (is_file($file = $this->pathThumbs . $album->id . '/'  . $media . '.' . $dimension->size . '-' . $dimension->type . '.jpg'))
					unlink($file);

	}
	
	public function albumDownload($id)
	{
		$album = $this->getAlbum($id);

		$zip = new ZipArchive;
		$zipName = sys_get_temp_dir() . '/simplegallery_' . uniqid() . '.zip';

		if ($error = $zip->open($zipName, ZIPARCHIVE::CREATE) !== true) {
			switch ($error) {
				default : $message ='unkown reason';
				case ZIPARCHIVE::ER_EXISTS : $message = 'file already exists'; break;
				case ZIPARCHIVE::ER_INCONS : $message = 'zip archive inconsistent'; break;
				case ZIPARCHIVE::ER_INVAL : $message = 'invalid argument'; break;
				case ZIPARCHIVE::ER_MEMORY : $message = 'malloc failure'; break;
				case ZIPARCHIVE::ER_NOENT : $message = 'no such file'; break;
				case ZIPARCHIVE::ER_NOZIP : $message = 'not a zip archive'; break;
				case ZIPARCHIVE::ER_OPEN : $message = 'can\'t open file'; break;
				case ZIPARCHIVE::ER_READ : $message = 'read error'; break;
				case ZIPARCHIVE::ER_SEEK : $message = 'seek error'; break;
			}
			die(l('album.message.download-error', $message));
		}

		foreach ($album->medias as $media)
			$zip->addFile($album->path . $media->name, $media->name);
		
		$zip->close();

		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="' . $album->name . '.zip"');
		header('Content-Length: ' . filesize($zipName));
		readfile($zipName);
		unlink($zipName);
		exit();
	}
	
	public function userPasswordLost($login)
	{
		if (!$user = get($this->config->users, k($login)))
			error(l('user.message.not-found'), '?user=lost');
			
		$user->passwordCode = randomString(12);
		$user->passwordCodeTime = time();
		file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));
		
		$link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '&pcode=' . $user->passwordCode;
		$this->mail(
			$user->mail,
			'[SimpleGallery] Password reset',
			'A reset password was requested for login "' . toHtml($login) . '".<br />
				To change the password click on the following link:
				<a href="' . $link . '">' . $link . '</a>
				If you are not the author of this request, just ignore this email.'
		);

		success(l('user.message.password-reset-link-sent'), '?');
		
		
	}
	
	public function userPasswordReset($code, $password, $passwordCheck)
	{
		if (strlen($code) != 12)
			error(l('user.message.password-reset-invalid-code'), '?user=lost');
	
		$valid = false;
		foreach ($this->config->users as &$user) {
			if ((string)$user->passwordCode != (string)$code)
				continue;

			$interval = new DateInterval($this->passwordLostTimeOut);
			$passwordTime = new DateTime();
			$passwordTime->setTimestamp($user->passwordCodeTime);
			$time = new DateTime();
			$passwordTime->add($interval);
			if ($time > $passwordTime)
				error(l('user.message.password-reset-invalid-code'), '?user=lost');

			if ($password) {
				if ($password !== $passwordCheck)
					error(l('user.message.password-update-error'), '?user=registration');
					
				$user->password = $this->userCryptPassword($password);
				unset($user->passwordCode);
				unset($user->passwordCodeTime);
				file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));
				
				success(l('user.message.password-update-success'), '?');
				
			}

			$valid = true;
			break;
		}
		unset($user);
		
		if (!$valid)
			error(l('user.message.password-reset-invalid-code'), '?user=lost');
	}
	
	private function mail($to, $subject, $message)
	{
		mail(
			$to,
			$subject,
			'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>' . toHtml($subject) . '</title></head><body>' . 
				$message
				. '</body></html>',
			implode(chr(13).chr(10), array(
				'MIME-Version: 1.0',
    	 		'Content-type: text/html; charset=utf-8',
    	 		'From: ' . get($this->config->parameters, k('name'), 'SimpleGallery') . ' <noreply@domain.com>'
    	 	))
		);
	}
}

?>
