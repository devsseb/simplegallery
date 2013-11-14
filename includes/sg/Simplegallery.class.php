<?php

include __DIR__ . '/SG.Fs.class.php';
include __DIR__ . '/SG.Synchronizer.class.php';
include __DIR__ . '/SG.Optimizer.class.php';
include __DIR__ . '/SG.Media.class.php';
include __DIR__ . '/SG.Media.Image.class.php';
include __DIR__ . '/SG.Media.Video.class.php';
include __DIR__ . '/SG.Media.Book.class.php';


class SimpleGallery
{

	private $config, $db;
	private $albumsPath, $thumbsPath;
	
	private $albumFileId = '.id2';
	private $albumCoverMediaMax = 5;
	private $sessionLifetime = 'P60D';
	private $passwordCodeLifetime = 'PT2H';
	private $databaseVersion = 1;

	public function __construct($config)
	{
		$this->config = $config;
		if (count($this->config)) {
			$this->albumsPath = realpath($this->config->albumsPath) . '/';
			$this->thumbsPath = realpath($this->config->thumbsPath) . '/';

			include 'includes/database/Database.class.php';
			$this->db = new Database($config->database);
			$this->checkDatabase();

			include 'includes/Ffmpeg.class.php';
			
		}
		
		
	}
	
	public function checkDatabase()
	{
		$tables = $this->db->getTables();
		if (!$tables)
			return $this->db->compile();

		$databaseVersion = 0;
		if (in_array('parameter', $tables)) {
			$parameter = \Database\ParameterTable::findAll();
			$databaseVersion = $parameter->getDatabaseVersion();
		}
		if ($databaseVersion == $this->databaseVersion)
			return;
		
		$this->db->compile();
		
		while (++$databaseVersion <= $this->databaseVersion) {
			if ($databaseVersion == 1)
				$parameter = new \Database\Parameter();

		}
		$parameter->setDatabaseVersion($this->databaseVersion);
		$parameter->save();
		
	}
	
	public function getAlbumsPath()
	{
		return $this->albumsPath;
	}
	
	public function getThumbsPath($album = null)
	{
		return $this->thumbsPath . ($album ? $album->getId() . '/' : '');
	}
	
	public function routing()
	{
		if (count($this->config)) {
		
			$route = geta(array_keys($_GET), k(0));
			$action = get($_GET, k($route));

			if ($this->noAdminUser()) {
			
				$route = 'user';
				$action = 'registration';
			
			} elseif (!$this->loadUserAuthenticated()) {
				$route = 'user';
				if ($action != 'registration' and $action != 'lost-password')
					$action = 'authentication';
		
			}

		} else {
		
			$route = 'install';
			$action = 'index';
		
		}

		$response = object(
			'route', $route,
			'action', $action,
			'data', array(),
			'menu', object(
				'back', object('enable', false, 'url', '#'),
				'albumconfig', object('enable', false, 'url', '#'),
				'load', object('enable', false, 'url', '#'),
				'users', object('enable', false, 'url', '?user=management'),
				'deleted', object('enable', false, 'url', '#')
			)
		);

		switch ($response->route) {

			case 'album' :
			default :

				$response->route = 'album';
			
				switch ($action) {
				
					case 'index' :
					default :
					
						$response->action = 'index';
						
						$album = $this->getAlbum(get($_GET, k('id')));
						if ($album and $album->getParent_id() != -1)
							$response->menu->back->enable = true;
						
						if ($this->user->isAdmin()) {
							if ($album) {
								$response->menu->albumconfig->enable = true;
								$response->menu->albumconfig->url = '?album=config&id=' . $album->getId();
							}
							$response->menu->load->enable = true;
							$response->menu->load->url = '?album=loader';
							$response->menu->users->enable = true;
							$response->menu->deleted->enable = true;
						}
						if ($album) {
							$response->menu->back->enable = true;
							$response->menu->back->url = '?album&id=' . $album->getParent_id();
						}

						$response->data['album'] = $album;

					break;
				
					case 'loader' :

						if (!$this->user->isAdmin())
							error('Access is forbidden.', '?');

						$response->data['albums'] = \Database\AlbumTable::findAllOrderPath();

						$response->menu->back->enable = true;
						$response->menu->back->url = '?';

						$response->menu->users->enable = true;

					break;
					
					case 'synchronizer' :

						if (!$this->user->isAdmin())
							error('Access is forbidden.', '?');
	
						$response->menu->back->enable = true;
						$response->menu->back->url = '?album=loader';

						$response->menu->users->enable = true;
							
					
					break;
					
					case 'synchronize' :
					
						if (!$this->user->isAdmin())
							error('Access is forbidden.', '?');
					
						$synchronizer = new SG\Synchronizer($this->getAlbumsPath(), $this->getThumbsPath());
						
						if ($id = get($_POST, k('id'))) {

							$synchronizer->cover($id, function($album) {
								write(json_encode(array('album' => $album)) . chr(10));
							});
								

						} else
							$synchronizer->albums(function($album) {
				
								write(json_encode(array('album' => $album)) . chr(10));
							
							});
						exit();
					
					break;
				
					case 'update' :
						if ($album = \Database\AlbumTable::findOneById($_POST['id'])) {
							$album->setName($_POST['name']);
							$album->save();
						}
						exit();
					break;
				
					case 'analyzer' :
					
						if (!$this->user->isAdmin())
							error('Access is forbidden.', '?');
					
						$response->data['albums'] = $_POST['albums'];	
						$response->menu->back->enable = true;
						$response->menu->back->url = '?album=loader';
					
					break;
					
					case 'analyze' :
						if (!$this->user->isAdmin())
							exit('Access is forbidden.');
					
						$synchronizer = new SG\Synchronizer($this->getAlbumsPath(), $this->getThumbsPath());
					
						foreach($_POST['albums'] as $albumId)
							$synchronizer->analyzeMedias($albumId, function($media) {

								write(json_encode(array('media' => $media)) . chr(10));
							
							});
						exit();						
					
					break;
				
					case 'cover' :
					
						if ($id = get($_GET, k('id'), 0))
							$album = new \Database\Album($id);

						if (!is_file($file = $this->getThumbsPath($album) . 'cover.jpg')) {
							header('Content-Type: image/png');
							readfile('routes/album/index/blank.png');
							exit();
						}
						
						$etag = md5($time = gmdate('r', filemtime($file)) . $album->getCoverMediasMd5());

						$finfo = finfo_open(FILEINFO_MIME_TYPE);
						$typemime = finfo_file($finfo, $file);
						finfo_close($finfo);

						$expire = new DateTime();
						$expire->add(new DateInterval('P6M'));

						header('Cache-Control:private , max-age=31536000');
						header('ETag:"' . $etag . '"');
						header('Last-Modified:' . $time);
						header('Content-Type: ' . $typemime);
						header('Expires:' . $expire->format('r'));

						if ($time == get($_SERVER, k('HTTP_IF_MODIFIED_SINCE')) or $etag == get($_SERVER, k('HTTP_IF_NONE_MATCH'))) {
							header('HTTP/1.1 304 Not Modified');
							exit();
						}
						
						readfile($file);
						exit();
					
					break;
					
					case 'config' :
						if (!$this->user->isAdmin())
							exit('Access is forbidden.');
						
						if ($_POST)
							$this->setAlbumConfig($_GET['id'], $_POST);
						
						$album = $this->getAlbumConfig(get($_GET, k('id')));
						
						if (!$album)
							go('?');
						
						$response->data['album'] = $album;
						
						$response->menu->back->enable = true;
						$response->menu->back->url = '?album&id=' . $album->getId();
					
					break;
				
				}
			
			break;
			
			case 'media' :
			
				

				switch ($action) {
				
					default :

					break;
					case 'brick' :
					case 'slideshow' :
				
						$optimizer = new SG\Optimizer($this->getAlbumsPath(), $this->getThumbsPath());

						$media = new \Database\Media($_GET['id'], 'album');
						$sgMedia = $optimizer->getSgMedia($media);
						$thumb = $sgMedia->{'getThumb' . ucfirst($action)}(get($_GET, k('data')));
						if (is_string($thumb)) {
							$thumb = object(
								'file', $thumb,
								'mime', '',
								'time', '',
								'md5', '',
								'content', ''
							);
						}

						if (!$thumb->mime) {
							$finfo = finfo_open(FILEINFO_MIME_TYPE);
							$thumb->mime = finfo_file($finfo, $thumb->file);
							finfo_close($finfo);						
						}
						if (!$thumb->time)
							$thumb->time = gmdate('r', filemtime($thumb->file));
						if (!$thumb->md5)
							$thumb->md5 = $media->getMd5();

						$thumb->etag =  md5($thumb->time . $thumb->md5);
						$thumb->expire = getn('DateTime')->add(new DateInterval('P6M'))->format('r');

						header('Cache-Control:private , max-age=31536000');
						header('ETag:' . $thumb->etag);
						header('Last-Modified:' . $thumb->time);
						header('Content-Type:' . $thumb->mime);
						header('Expires:' . $thumb->expire);

						if ($thumb->time == get($_SERVER, k('HTTP_IF_MODIFIED_SINCE')) or $thumb->etag == get($_SERVER, k('HTTP_IF_NONE_MATCH'))) {
							header('HTTP/1.1 304 Not Modified');
							exit();
						}
						
						if ($thumb->content)
							echo $thumb->content;
						else
							readfile($thumb->file);

						exit();

					break;
				
					case 'synchronize' :
						if (!$this->user->isAdmin())
							exit('Access is forbidden.');
					
						$albumId = $_POST['album'];
						$mediaPath = $_POST['media'];
					
						$synchronizer = new SG\Synchronizer($this->getAlbumsPath(), $this->getThumbsPath());
						$synchronizer->media($albumId, $mediaPath, function($media) {

							write(json_encode(array('media' => $media)) . chr(10));
							
						});
						exit();						
					
					break;
					
					case 'update' :
					
						if (!$this->user->isAdmin())
							exit('Access is forbidden.');
							
						$media = new \Database\Media($_GET['id']);
						
						if (exists($_GET, 'direction')) {
							$rotation = $media->getRotation();
							if ($_GET['direction'] == 'left')
								$rotation-= 90;
							else
								$rotation+= 90;
							if ($rotation < 0)
								$rotation = 270;
							elseif ($rotation > 270)
								$rotation = 0;
							
							$media->setRotation($rotation);
						}

						if (exists($_GET, 'delete')) {
							$media->setDeleted(1);
						}
						
						if (exists($_GET, 'restore')) {
							$media->setDeleted(0);
						}
						$media->save();
					
						exit(json_encode(array('media-update' => true)));
					
					break;
				}
			
			break;
			
			case 'user' :
			
				$response->menu->back->url = '?';
				$response->menu->albumconfig->enable = false;
				$response->menu->load->enable = false;
			
				switch ($action) {
				
					default :
					case 'authentication' :
						
						if ($_POST)
							$this->userAuthentication($_POST);
						
						$response->menu = false;
						
					break;
					
					case 'registration' :
					
						if ($_POST)
							$this->userRegistration($_POST);
							
						if ($code = get($_GET, k('rcode')))
							$this->userRegistration($code);
					
						$response->menu = false;
						
					
					break;
					
					case 'logout' :
					
						$this->userLogout();
					
					break;
					
					case 'lost-password' :
					
						if ($_POST)
							$this->userLostPassword($_POST);
							
						if ($code = get($_GET, k('pcode')))
							$response->data['update'] = $this->userLostPassword($code);
							
						$response->menu = false;
					
					break;
					
					case 'management' :
					
						if (!$this->user->isAdmin())
							error('Access is forbidden.', '?');
					
						if (exists($_POST, 'group'))
							$this->addGroup($_POST['group']);
							
						if (exists($_GET, 'groupdelete'))
							$this->deleteGroup($_GET['groupdelete']);

						if (exists($_POST, 'user'))
							$this->usersUpdate($_POST['user']);
							
						if (exists($_POST, 'name'))
							$this->addUser($_POST['name'], $_POST['email']);
					
						if (exists($_GET, 'userdelete'))
							$this->deleteUser($_GET['userdelete']);
					
						$response->data['groups'] = \Database\GroupTable::findAllOrderName();
						$response->data['users'] = \Database\UserTable::findAllWithGroup();
					
					break;
				
				}
				
			break;
		
			case 'install':
			
				if (count($this->config))
					go('?');
			
				if ($_POST) {

					$configContent = '<?
	$config->albumsPath = \'{albumsPath}\';
	$config->thumbsPath = \'{thumbsPath}\';
	$config->database = array(
		\'dsn\' => \'{database_dsn}\',
		\'user\' => \'{database_user}\',
		\'password\' => \'{database_password}\'
	);';
					
					foreach ($_POST as $key => $value)
						$configContent = str_replace('{' . $key . '}', addslashes($value), $configContent);
					
					file_put_contents('config.php', $configContent);
					
					go('?');
				}
			
				$response->menu = false;
				$response->data['config'] = $this->config;
			
			break;
		
		}

		return $response;
		
	}
	
/****************************
*
*	TOOLS
*	
*
****************************/
	
	private function sendMail($to, $subject, $message)
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
    	 		'From: ' . 'SimpleGallery2' . /*$this->parameters->name . */' <noreply@domain.com>'
    	 	))
		);
	}
	
/****************************
*
*	ALBUMS
*	MEDIAS
*
****************************/
	
	private function getAlbum($id)
	{
		if ($id)
			$album = new \Database\Album($id, 'group');
		else
			$album = \Database\AlbumTable::findOneByPathWithGroup('');

		if (!$this->user->isAdmin()) {

			if ($id) {
				
				// Retrieve album access
				$access = false;
				foreach ($this->user->getGroupCollection() as $userGroup) {
					$albumGroupAccess = $album->getGroupCollection()->findOneById($userGroup->getId())->getAccess();
					if ($access = ($albumGroupAccess == -1 or $albumGroupAccess == 1))
						break;
				}
				if (!$access)
					error('Access is forbidden', '?');
					
				// Retrieve parent album
				$q = new \Database\Object\Query('album');
				$q->leftJoin('group');
				$explode = explode('/', $album->getPath());
				array_splice($explode, - 2);
				for ($i = 0; $i < count($explode); $i++) {
					$path = implode('/', array_slice($explode, 0, $i+1));
				
					$q->OrWhere('path = ?', $path);
				}
				$q->AndWhere('(access = ? OR access = ?)', -1, 1);
				$q->Orderby('path', 'DESC');
				$parent = $q->one()->execute();
				$album->setParent($parent);
			}
			
			// Retrieve children access
			$q = new \Database\Object\Query('album');
			$q->leftJoin('group');
			$q->where('path LIKE ?', $album->getPath() . '%');
			$q->AndWhere('album.id != ?', $album->getId()?:0);
			$q->AndWhere('(access = ? OR access = ?)', -1, 1);
			$q->leftJoin('user');
			$q->AndWhere('user.id = ?', $this->user->getId());
			$q->orderBy('path');
			
			$allChildren = $q->execute();
			$children = new \Database\Object\Collection('album');
			$roots = array();
			
			foreach ($allChildren as $child) {

				$already = false;
				foreach ($roots as $root)
					if ($already = strpos($child->getPath(), $root) === 0)
						break;
				
				if (!$already) {
					$children[] = $child;
					$roots[] = $child->getPath();
				}
			
			}

			$album->setChildren($children);
			
		}
			
		return $album;
	
	}
	
	private function getAlbumConfig($id)
	{
		if (!$this->user->isAdmin())
			return;

		$album = new \Database\Album($id, 'group');
		if (!$album)
			error('Album not found.');
			
		$groups = \Database\GroupTable::findAllOrderName();						
		
		foreach ($groups as $group) {
			$groupAlbum = $album->getGroupCollection()->findOneById($group->getId());
			if ($groupAlbum) {
				$group->setAccess($groupAlbum->getAccess());
				$group->setAccess_inherited($groupAlbum->getAccess_inherited());
			} else {
				$group->setAccess(-2);
				$group->setAccess_inherited(-2);
			}
		}
		
		$album->setGroupCollection($groups);
		
		return $album;
	}
	
	private function setAlbumConfig($id, $config)
	{
		if (!$this->user->isAdmin())
			return;

		$album = new \Database\Album($id);
		if (!$album)
			error('Album not found.');
			
		$album->setName($config['name']);
		
		$groupCollection = new \Database\Object\Collection('group');
		foreach (get($config, k('groups'), array()) as $groupId => $access) {
			$group = new \Database\Group($groupId);
			$group->setAccess($access);
			$groupCollection[] = $group;
		}

		$this->setAlbumAccess($album, $groupCollection);
		
		success('Album updated successfully.', '?album=config&id=' . $album->getId());
	}
	
	private function setAlbumAccess($album, $groups)
	{
		$album->setGroupCollection($groups);
		$album->save();
		$children = \Database\AlbumTable::findByParent_idWithGroup($album->getId());
		foreach ($album->getChildren() as $child) {
			$groupsAlbum = new \Database\Object\Collection('group');
			foreach ($groups as $group) {
				$groupAlbum = new \Database\Group($group);
				$groupFound = $child->getGroupCollection()->findOneById($group->getId());
				if ($groupFound and $groupFound->getAccess() >= 0) {
					$groupAlbum->setAccess($groupFound->getAccess());
				} elseif ($group->getAccess() >= 0) {
					$groupAlbum->setAccess($group->getAccess() - 2);
				} else {
					$groupAlbum->setAccess($group->getAccess());
				}
				
				$groupAlbum->setAccess_inherited($group->getAccess() - ($group->getAccess() >= 0 ? 2 : 0));
				
				$groupsAlbum[] = $groupAlbum;
			}
			
			$this->setAlbumAccess($child, $groupsAlbum);
			
		}
		
	}
	


	public function getMediaTransform(\Database\Media $media)
	{
		$result = object(
			'flip', object(
				'horizontal', $media->isFlipHorizontal(),
				'vertical', $media->isFlipVertical()
			),
			'rotation', $media->getRotation()
		);

		switch ($media->getExifOrientation()) {
			case 2 :
				$result->flip->horizontal = !$result->flip->horizontal;
			break;
			case 3 :
				$result->rotation+= 180;
			break;
			case 4 :
				$result->flip->vertical = !$result->flip->vertical;
			break;
			case 5 :
				$result->rotation+= 90;
				$result->flip->vertical = !$result->flip->vertical;
			break;
			case 6 :
				$result->rotation+= 90;
			break;
			case 7 :
				$result->rotation+= 270;
				$result->flip->vertical = !$result->flip->vertical;
			break;
			case 8 :
				$result->rotation+= 270;
			break;
		}

		if ($result->rotation > 270)
			$result->rotation = ($result->rotation / 90)%4 * 90;

		return $result;
	}
	
	public function getMediaCssTransform($media)
	{
	
			$styles = '';
			$transform = $this->getMediaTransform($media);
			foreach (array('', '-webkit-', '-moz-', '-o-', '-ms-', 'ms') as $cssPrefix)
				$styles.= $cssPrefix . 'transform:rotate(' . $transform->rotation . 'deg) scaleX(' . ($transform->flip->horizontal ? '-1' : '1') . ') scaleY(' . ($transform->flip->vertical ? '-1' : '1') . ');';
				
			return $styles;
	
	}
	
	public function getAntiMediaCssTransform($media)
	{
	
			$styles = '';
			$transform = $this->getMediaTransform($media);
			foreach (array('', '-webkit-', '-moz-', '-o-', '-ms-', 'ms') as $cssPrefix)
				$styles.= $cssPrefix . 'transform:rotate(-' . $transform->rotation . 'deg) scaleX(' . ($transform->flip->horizontal ? '-1' : '1') . ') scaleY(' . ($transform->flip->vertical ? '-1' : '1') . ');';
				
			return $styles;
	
	}
	
/****************************
*
*	USERS
*	GROUPS
*
****************************/

	public function noAdminUser()
	{
		return !(bool)\Database\UserTable::countIsAdmin();
	}

	public function userRegistration($data)
	{
		if (is_array($data)) {
		
			if (!mailCheck($email = trim(get($data, k('email')))))
				return error('Invalid email.');

			if (\Database\UserTable::findOneByEmail($email))
				return error('The mail "' . $email . '" is already registered.');
			
			if (!$name = trim(get($data, k('name'))))
				return error('Name can\'t be empty.');
			
			if (!$password = trim(get($data, k('password'))))
				return error('Password can\'t be empty.');

			if ($password != trim(get($data, k('password-verification'))))
				return error('The two passwords are differents. Please retype your password.');

			$user = new \Database\User();
			$user->setEmail($email);
			$user->setName($name);
			$user->setAdmin($this->noAdminUser());
			$user->setCryptPassword($password);
			$user->setActive(false);
			$user->setActiveCode(randomString(12));

			$user->save();
		
			$link = httpUrl() . '?user=registration&rcode=' . $user->getActiveCode();
			$this->sendMail(
				$email,
				'Registration, please active your account',
				'Hello ' . toHtml($name) . ',' . chr(10) . chr(10) . 'To activate your account, please click on the following link :' . chr(10) . '<a href="' . $link . '">' . $link . '</a>'
			);

			foreach (\Database\UserTable::findIsAdmin() as $admin)
				$this->sendMail(
					$admin->getEmail(),
					'New registration of ' . $user->getName() . ' (' . $user->getEmail() . ')',
					'Hello ' . $admin->getName() .',' . chr(10) . chr(10) . 'A new account has been registered. Now you can assign groups in the administration.'
				);

			information('Please check your mailbox to complete the account creation.', '?');
			
		} else {
			
			$user = \Database\UserTable::findOneByActivecode($data);
			if (!$user)
				error('Code is invalid.', '?user=registration');
			
			$user->setActiveCode(false);
			$user->setActive(true);
			$user->save();
			
			success('Your account is now active. You can now log in with your password.', '?');
			
		}
	}
	
	public function loadUserAuthenticated()
	{
		$sessionMin = new DateTime();
		$sessionMin->sub(new DateInterval($this->sessionLifetime));
		$sessionMin = $sessionMin->format('Y-m-d H:i:s');

		\Database\UserSessionTable::findLowerDatetime($sessionMin)->delete();
		
		if (!$session_code = get($_COOKIE, k('session_code')))
			$session_code = get($_SESSION, k('session_code'));
		
		if ($session_code) {
			$session = \Database\UserSessionTable::findOneByCodeWithUser($session_code);
			if ($session) {
				$this->user = $session->getUser();
				return true;
			}
		}
		
		return false;
	}	
	
	public function userAuthentication($post)
	{
		$user = \Database\UserTable::findOneByEmailAndIsActive(get($post, k('email')));
		if ($user) {
			if (crypt($post['password'], $user->getPassword()) === $user->getPassword()) {
			
				$session = new \Database\UserSession();
				$session->setCode(randomString(30));
				$session->setUser($user);
				$session->setDatetime(date('Y-m-d H:i:s'));
				$session->save();
				
				if (get($post, k('keep-connection'))) {
					$time = new DateTime();
					$time->add(new DateInterval($this->sessionLifetime));
					setcookie('session_code', $session->getCode(), $time->getTimestamp());
				} else
					setcookie('session_code', '');
				$_SESSION['session_code'] = $session->getCode();
				
				$this->user = $user;
			
				go('?');
			
			}
		}
		
		error('Incorrect email or password.');
	}
	
	public function userLogout()
	{
		setcookie('session_code', '');
		unset($_SESSION['session_code']);
		
		go('?');
	}
	
	public function userLostPassword($data)
	{
		
		if (is_array($data)) {
		
			if (exists($data, 'email')) {
		
				$user = \Database\UserTable::findOneByEmailAndIsActive(get($data, k('email')));
				if ($user) {
					$user->setPasswordCode(randomString(12));
					$user->setPasswordCodeTime(date('Y-m-d H:i:s'));
			
					$user->save();
			
					$link = httpUrl() . '?user=lost-password&pcode=' . $user->getPasswordCode();

					$this->sendMail(
						$user->getEmail(),
						'Lost password',
						'Hello ' . $user->getName() . ',' . chr(10) . chr(10) . 'A reset password was requested for your mail adress.' . chr(10) . 'To update your password, please click on the following link :' . chr(10) . '<a href="' . $link . '">' . $link . '</a>' .
							chr(10) . chr(10) . 'If you are not the author of this request, just ignore this email.'
					);

					success('An mail with a link to reset your password has been sent.', '?');
			
				}
		
				error('User not found');
				
			} else {
			
				$timeMin = new DateTime();
				$timeMin->sub(new DateInterval($this->passwordCodeLifetime));
				$timeMin = $timeMin->format('Y-m-d H:i:s');

				$user = \Database\UserTable::findOneByPasswordcodeAndIsActiveAndUpperPasswordcodetime($data['pcode'], $timeMin);
				if (!$user)
					error('Lost passord code is invalid.', '?user=lost-password');
				if ($data['password'] != $data['password-verification'])
					error('The two passwords are differents. Please retype your password.', '?user=lost-password&pcode=' . $data['pcode']);
				if (trim($data['password']) == '')
					error('The password can\'t be empty.', '?user=lost-password&pcode=' . $data['pcode']);

				$user->setCryptPassword($data['password']);
				$user->setPasswordCode('');
				$user->setPasswordCodeTime('');
				$user->save();
				success('You password has been updated successfully.', '?');

			}
			
		} else {

			$timeMin = new DateTime();
			$timeMin->sub(new DateInterval($this->passwordCodeLifetime));
			$timeMin = $timeMin->format('Y-m-d H:i:s');

			$user = \Database\UserTable::findOneByPasswordcodeAndIsActiveAndUpperPasswordcodetime($data, $timeMin);
			if ($user)
				return $user->getPasswordCode();
			
			error('Lost passord code is invalid.', '?');
		
		}
		
	}
	
	public function addGroup($name)
	{

		if (!$this->user->isAdmin())
			return;
	
		if (trim($name) == '')
			return error('Group name can\'t be empty');

	
		$group = new \Database\Group();
		$group->setName($name);
		$group->save();
		
		success('Group "' . $group->getName() . '" has been added successfully.', '?user=management');
	
	}

	public function deleteGroup($id)
	{

		if (!$this->user->isAdmin())
			return;
	
		$group = new \Database\Group($id);
		$group->delete();
	
		success('Group "' . $group->getName() . '" has been deleted successfully.', '?user=management');
	
	}
	
	public function usersUpdate($users)
	{
		if (!$this->user->isAdmin())
			return;
	
		foreach ($users as $id => $data) {
			
			$user = new \Database\User($id);
			$user->setAdmin(get($data, k('admin')));
			$groupCollection = new \Database\Object\Collection('group');
			foreach (get($data, k('groups'), array()) as $groupId => $null)
				$groupCollection[] = new \Database\Group($groupId);
				
			$user->setGroupCollection($groupCollection);

			$user->save();	
		}
		
		success('Users has been updated successfully.', '?user=management');
	}
	
	public function addUser($name, $email)
	{
		if (trim($name) == '')
			return error('User name can\'t be empty');
			
		if (trim($email) == '')
			return error('User email can\'t be empty');
			
		if (\Database\UserTable::findOneByEmail($email))
			return error('The mail "' . $email . '" is already registered.');
			
		$user = new \Database\User();
		$user->setName($name);
		$user->setEmail($email);
		$user->setActive(false);
		$user->setActiveCode(randomString(12));

		$user->save();
	
		$link = httpUrl() . '?user=registration&rcode=' . $user->getActiveCode();
		$this->sendMail(
			$user->getEmail(),
			'Registration, please active your account',
			'Hello ' . toHtml($user->getName()) . ',' . chr(10) . chr(10) . 'To activate your account, please click on the following link :' . chr(10) . '<a href="' . $link . '">' . $link . '</a>'
		);

		success('User "' . $user->getName() . '" was added successfully, an email with an activation link has been sent to him.', '?user=management');
		
			
	}

	public function deleteUser($id)
	{

		if (!$this->user->isAdmin())
			return;
	
		$user = new \Database\User($id);
		$user->delete();
	
		success('User "' . $user->getName() . '" has been deleted successfully.', '?user=management');
	
	}

}
