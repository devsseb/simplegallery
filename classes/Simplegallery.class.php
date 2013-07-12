<?

include_once(__DIR__ . '/Ffmpeg.class.php');
include_once(__DIR__ . '/Database.class.php');

class Simplegallery
{
	private $database = 'simplegallery.sqlite';
	private $lastId, $passwordLostTimeOut = 'PT2H';
	
	const ACCESS_FORBIDDEN_INHERITED = -2;
	const ACCESS_GRANTED_INHERITED = -1;
	const ACCESS_FORBIDDEN = 0;
	const ACCESS_GRANTED = 1;
	

	public function __construct($root)
	{
		if (!$root)
			return;
	
		$this->root = $root;
		$this->pathAlbums = $this->root . 'albums/';
		$this->pathThumbs = $this->root . 'thumbs/';

		if ($install = !is_file('./config.php'))
			$this->installCreateConfig($root);
		
		$this->db = new Database($this->root . $this->database);

		if ($install)
			$this->installDatabase();

		$this->parameters = $this->db->execute('
			SELECT
				*
			FROM
				parameters
			;
		', true);

		$this->installUpdate();

		$this->locale = new Locale(get($this->parameters->locale));
		
		$this->dimensions = object(
			'thumb'		, object('size', 75		, 'type', 'sprite'	),
			'preview'	, object('size', 500	, 'type', 'long'	),
			'slideshow'	, object('size', 1000	, 'type', 'long'	)
		);

		$this->getUser();
		$this->getAlbums();

	}
	
	private function installCreateConfig()
	{
		if (substr($this->root, -1) != '/')
			$this->root.= '/';
	
		$content = '<?' . chr(10);
		$content.= '	$config->privatePath = \'' . addslashes($this->root) . '\';' . chr(10);
		$content.= '?>';
		
		file_put_contents('./config.php', $content);

		if (!is_dir($this->pathAlbums))
			mkdir($this->pathAlbums, 0777, true);
		file_put_contents($this->pathAlbums . '.htaccess', 'order deny,allow' . chr(10) . 'deny from all');

	}
	
	private function installDatabase()
	{

		$this->db->execute('
			CREATE TABLE IF NOT EXISTS users (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				mail TEXT,
				name TEXT,
				password TEXT,
				active TEXT,
				admin BOOL,
				passwordCode TEXT,
				passwordCodeTime DATETIME,
				mailUpdate TEXT,
				mailUpdateCode TEXT
			);
		');

		$this->db->execute('
			CREATE TABLE IF NOT EXISTS groups (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				name TEXT
			);
		');
	
		$this->db->execute('
			CREATE TABLE IF NOT EXISTS users_groups (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				user_id INTEGER,
				group_id INTEGER
			);
		');
	
		// Album with nested sets model
		$this->db->execute('
			CREATE TABLE IF NOT EXISTS albums (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				path TEXT,
				name TEXT,
				date_start DATE,
				date_end DATE,
				description TEXT,
				comments_disable BOOL,
				ns_left INTEGER,
				ns_right INTEGER,
				ns_depth INTEGER,
				thumb_md5 TEXT
			);
		');
	
		/* Access :
		 * -2 : Forbidden inherited
		 * -1 : Granted inherited
		 * 0 : Forbidden
		 * 1 : Granted
		*/
		$this->db->execute('
			CREATE TABLE IF NOT EXISTS albums_groups (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				album_id INTEGER,
				group_id INTEGER,
				access INTEGER,
				access_inherited INTEGER
			);
		');
	
		$this->db->execute('
			CREATE TABLE IF NOT EXISTS medias (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				album_id INTEGER,
				file TEXT,
				name TEXT,
				md5 TEXT,
				width INTEGER,
				height INTEGER,
				orientation INTEGER,
				rotation INTEGER,
				flip_horizontal BOOL,
				flip_vertical BOOL,
				description TEXT,
				position INTEGER,
				thumb_index INTEGER,
				preview_md5 INTEGER,
				slideshow_md5 INTEGER,
				type TEXT
			);
		');
	
		$this->db->execute('
			CREATE TABLE IF NOT EXISTS medias_comments (
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				media_id INTEGER,
				user_id INTEGER,
				datetime DATETIME,
				value TEXT
			);
		');
	
		$this->db->execute('
			CREATE TABLE IF NOT EXISTS parameters (
				id INTEGER PRIMARY KEY AUTOINCREMENT, 
				version TEXT,
				name TEXT,
				locale TEXT,
				registration_disable BOOL,
				albums_calendar_disable BOOL,
				albums_comments_disable BOOL,
				albums_tags_disable BOOL
			);
		');
		
		
		if (!geta($this->db->execute('
			SELECT
				COUNT(*) AS total
			FROM
				parameters
			;
		', true), k('total')))
			$this->db->execute('
				INSERT INTO
					parameters
				SET
					version = "0.1",
					name = "SimpleGallery",
					locale = "",
					registration_disable = 0,
					albums_calendar_disable = 0,
					albums_comments_disable = 0,
					albums_tags_disable = 0
				;
			');
		
		success(l('install.message.success'), '?user=registration');
	}
	
	private function installUpdate()
	{
		if ($this->parameters->version == SG_VERSION)
			return;

		while ($this->parameters->version < SG_VERSION) {
			switch ($this->parameters->version) {
				default :
					exit('Unknow version');
				case '0.1' :
					$this->parameters->version = '0.2';
				break;
			}
		}
		
		$this->db->execute('
			UPDATE
				parameters
			SET
				version = ' . $this->db->protect(SG_VERSION) . '
			;
		');
	}
	
//******************************************************************************
//
//	USERS METHODS
//
//******************************************************************************

	public function usersTotal($active = false)
	{
		return geta($this->db->execute('
			SELECT
				COUNT(id) AS total
			FROM
				users
			' . ($active ? 'WHERE active = 1' : '') . '
			;
		', true), k('total'));
	}
	
	public function getUser()
	{
		$this->user = false;

		if ($mail = get($_COOKIE, k('user-mail')))
			$this->userLogin($mail, get($_COOKIE, k('user-password')), true, true);

		if (!$mail = get($_SESSION, k('user')))
			return false;

		$this->user = $this->db->execute('
			SELECT
				*
			FROM
				users
			WHERE
				active = 1 AND
				mail = ' . $this->db->protect($mail) . '
			;
		', true);
		
		if ($this->user)
			$this->user->groups = array_index($this->db->execute('
				SELECT
					groups.*
				FROM
					users_groups
				LEFT join
					groups ON users_groups.group_id = groups.id
				WHERE
					user_id = ' . $this->db->protect($this->user->id) . '
				;
			'), 'id');

		return true;
	}
	
	public function userLogin($mail, $password, $keep, $system = false)
	{
		$mail = trim(strtolower($mail));

		$this->user = $this->db->execute('
			SELECT
				*
			FROM
				users
			WHERE
				active = 1 AND
				mail = ' . $this->db->protect($mail) . '
			;
		', true);

		if (!$this->user) {
			if ($system)
				return;
			error(l('user.message.login-error'), '?');
		}
			
		if ($this->user->active != 1) {
			error(l('user.message.login-error'), '?');
			if ($system)
				return;
		}

		if (!$system)
			$password = crypt($password, $this->user->password);

		if ($this->user->password != $password) {
			if ($system)
				return;
			error(l('user.message.login-error'), '?');
		}

		$_SESSION['user'] = $mail;
		
		$time = time()+60*60*24*30;
		if ($keep) {
			setcookie('user-mail', $mail, $time);
			setcookie('user-password', $password, $time);
		} else {
			setcookie('user-mail', '');
			setcookie('user-password', '');
		}
		
		if ($system)
			return;
		success(l('user.message.login-success', $this->user->name), '?');
	}
	
	public function userLogout()
	{
		unset($_SESSION['user']);
		
		setcookie('user-mail', '');
		setcookie('user-password', '');
		
		success(l('user.message.logout'), '?');
	}
	
	public function userRegistration($name, $mail, $password = null, $passwordCheck = null)
	{
		if (is_null($password)) {
			// Add user from admin
			$errorLink = '?admin';
			$successLink = '?admin';
		} else {
			// Add user from registration
			$errorLink = '?user=registration';
			$successLink = '?';
		}
		
		if (!preg_match('/^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$/', $mail))
			error(l('user.message.mail-update-error-invalid', $mail), $errorLink);
	
		if (geta($this->db->execute('
			SELECT
				COUNT(id) AS total
			FROM
				users
			WHERE
				mail = ' . $this->db->protect($mail) . '
			;
		', true), k('total')) > 0)
			error(l('user.message.mail-update-error', $mail), $errorLink);

		if (!is_null($password) and $password !== $passwordCheck)
			error(l('user.message.password-update-error'), $errorLink);

		$user = object(
			'id', 0,
			'name', $name,
			'mail', $mail,
			'password', $password ? $this->userCryptPassword($password) : null,
			'active', randomString(12),
			'admin', !$this->usersTotal()
		);
		
		$this->db->executeArray('users', $user);

		$code = $password ? 'rcode' : 'rpcode';
		$link = httpUrl() . '?user=registration&' . $code . '=' . $user->active;
		$this->mail(
			$mail,
			l('mail.registration.object', $this->parameters->name),
			l('mail.registration.message', $user->name) . '<a href="' . $link . '">' . $link . '</a>' . l('mail.signature', $this->parameters->name)
		);

		if ($this->usersTotal() > 1)
			foreach ($this->db->execute('
				SELECT
					*
				FROM
					users
				WHERE
					admin = 1
				;
			') as $admin) {

				$this->mail(
					$admin->mail,
					l('mail.registration-admin.object', $this->parameters->name, $user->name, $user->mail),
					l('mail.registration-admin.message', $admin->name) . l('mail.signature', $this->parameters->name)
				);
			}

		if (!is_null($password))
			success(l('user.message.registration-active-link-sent'), $successLink);
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
	
	public function userActive($code, $password = null, $passwordCheck = null)
	{
		if (strlen($code) != 12)
			error(l('user.message.registration-active-invalid-code'), '?user=registration');
	
		$user = $this->db->execute('
			SELECT
				id
			FROM
				users
			WHERE
				active = ' . $this->db->protect($code) . '
			;
		', true);
	
		if (!$user)
			error(l('user.message.registration-active-invalid-code'), '?user=registration');

		if (!is_null($password)) {
			if (trim($password) == '')
				error(l('user.message.password-update-error-empty'), '?user=registration&rpcode=' . $code);

			if ($password !== $passwordCheck)
				error(l('user.message.password-update-error'), '?user=registration&rpcode=' . $code);

			$user->password = $this->userCryptPassword($password);					
		}

		$user->active = true;

		$this->db->executeArray('users', $user);
		
		success(l('user.message.registration-active-success'), '?');
		
	}
	
	public function userUpdate($data)
	{

		if ($data['password'])
			if ($data['password'] !== $data['password-check'])
				error(l('user.message.password-update-error'), '?user=profil');
		
		$mail = $this->user->mail;
		$user = $this->db->execute('
			SELECT
				*
			FROM
				users
			WHERE
				mail = ' . $this->db->protect($mail) . ' AND
				active = 1
			;
		', true);
		if (!$user)
			error(l('user.message.not-found'));

		$user->name = $data['name'];
		
		if ($mailUpdate = $user->mail != $data['mail']) {
			$user->mailUpdate = $data['mail'];
			$user->mailUpdateCode = randomString(12);
			
			$link = httpUrl() . '?user=profil&mcode=' . $user->mailUpdateCode;
			$this->mail(
				$user->mailUpdate,
				l('mail.mail-update.object', $this->parameters->name),
				l('mail.mail-update.message', $user->name) . '<a href="' . $link . '">' . $link . '</a>' . l('mail.signature', $this->parameters->name)
			);
		}
		if ($data['password'])
			$user->password = $this->userCryptPassword($data['password']);
		
		$this->db->executeArray('users', $user);
		
		success(l('user.message.user-update-success') . ($mailUpdate ? '<br />' . l('user.message.mail-update-link-sent') : ''), '?user=profil');
	}
	
	public function userUpdateMail($code)
	{

		if (strlen($code) != 12)
			error(l('user.message.mail-update-invalid-code'), '?');

		$user = $this->db->execute('
			SELECT
				id,
				mail,
				mailUpdate,
				mailUpdateCode
			FROM
				users
			WHERE
				mailUpdateCode = ' . $this->db->protect($code) . ' AND
				active = 1
			;
		', true);
		if (!$user)
			error(l('user.message.mail-update-invalid-code'), '?');
		
		$user->mail = $user->mailUpdate;
		$user->mailUpdate = '';
		$user->mailUpdateCode = '';
		
		$this->db->executeArray('users', $user);
		
		success(l('user.message.mail-update-success'), '?user=profil');

	}
	
	public function userDelete($id)
	{
		$user = $this->db->execute('
			SELECT
				id
			FROM
				users
			WHERE
				id = ' . $this->db->protect($id) . '
			;
		');
		if (!$user)
			error(l('user.message.not-found'), '?admin');
			
		$this->db->execute('
			DELETE FROM
				users
			WHERE
				id = ' . $this->db->protect($id) . '
			;
		');
		
		success(l('admin.message.user-delete-success'), '?admin');
	}
	
	public function userPasswordLost($mail)
	{
		if (!$user = $this->db->execute('
			SELECT
				id,
				mail,
				name
			FROM
				users
			WHERE
				mail = ' . $this->db->protect($mail) . '
			;
		', true))
			error(l('user.message.not-found'), '?user=lost');

		$user->passwordCode = randomString(12);
		$user->passwordCodeTime = time();
		
		$this->db->executeArray('users', $user);
		
		$link = httpUrl() . '?user=lost&pcode=' . $user->passwordCode;

		$this->mail(
			$user->mail,
			l('mail.password-reset.object', $this->parameters->name),
			l('mail.password-reset.message', $user->name) . '<a href="' . $link . '">' . $link . '</a>' . l('mail.password-reset.message2') . l('mail.signature', $this->parameters->name)
		);

		success(l('user.message.password-reset-link-sent'), '?');
		
		
	}
	
	public function userPasswordReset($code, $password, $passwordCheck)
	{
		if (strlen($code) != 12)
			error(l('user.message.password-reset-invalid-code'), '?user=lost');
	
	
		if (!$user = $this->db->execute('
			SELECT
				id,
				passwordCode,
				passwordCodeTime
			FROM
				users
			WHERE
				passwordCode = ' . $this->db->protect($code) . '
			;
		', true))
			error(l('user.message.password-reset-invalid-code'), '?user=lost');
	
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
			$user->passwordCode = '';
			$user->passwordCodeTime = '';
			$this->db->executeArray('users', $user);

			success(l('user.message.password-update-success'), '?');
			
		}

	}
	
	public function getUsers()
	{
		$users = $this->db->execute('
			SELECT
				*
			FROM
				users
			ORDER BY
				name
			;
		');
		foreach ($users as $user)
			$user->groups = array_index($this->db->execute('
				SELECT
					groups.*
				FROM
					users_groups
				LEFT join
					groups ON users_groups.group_id = groups.id
				WHERE
					user_id = ' . $this->db->protect($user->id) . '
				;
			'), 'id');
		
		return $users;
	}
	
	public function getGroups()
	{
		return $this->db->execute('
			SELECT
				*
			FROM
				groups
			ORDER BY
				name
			;
		');
	}
	
//******************************************************************************
//
//	ADMIN METHODS
//
//******************************************************************************
	
	
	public function adminUpdate($from, $data)
	{
	
		if ($from == 'general') {
			$this->parameters->name = $data['name'];
			$this->parameters->locale = $data['locale'];
			$this->parameters->registration_disable = get($data, k('registration-disable'));
			$this->parameters->albums_calendar_disable = get($data, k('albums-calendar-disable'));
			$this->parameters->albums_comments_disable = get($data, k('albums-comments-disable'));
			$this->parameters->albums_tags_disable = get($data, k('albums-tags-disable'));
			
			$this->db->executeArray('parameters', $this->parameters);

			$groups = array();
			foreach (get($data, k('groups'), array()) as $id => $name)
				$groups[] = object(
					'id', trim($name) ? $id : -$id,
					'name', $name
				);
			foreach (get($data, k('groups-new'), array()) as $name)
				if (trim($name))
					$groups[] = object(
						'id', null,
						'name', $name
					);
			$this->db->executeArray('groups', $groups);
			
			$usersGroups = array();
			foreach (get($data, k('usersGroups'), array()) as $userId => $usersGroup)
				foreach ($usersGroup as $groupId => $value)
					$usersGroups[] = object(
						'user_id', $userId,
						'group_id', $groupId
					);
			$this->db->updateArray('users_groups', $usersGroups, null, array('user_id', 'group_id'));

			$user = object(
				'name', get($data, k('user-name')),
				'mail', get($data, k('user-mail'))
			);

			$success = '';
			if ($user->name or $user->mail) {

				$this->userRegistration($user->name, $user->mail);
			
				$success = l('user.message.user-add-success', $user->name);
		
			}
		
			success(l('admin.message.parameters-update-success') . ($success ? '<br />' . $success : ''), '?admin');
		} elseif ($from == 'albums') {

			$albums = array();
			foreach ($data['albums'] as $id => $update) {

				if (!$album = get($this->albums, k($id)))
					continue;

				$album->name = $update['name'];
				$album->date_start = $update['date_start'];
				$album->date_end = $update['date_end'];
				
				unset($album->pathThumbs);
				unset($album->groups);
				unset($album->medias);
				
				$albums[] = $album;
			}
			
			$this->db->executeArray('albums', $albums);

			success(l('admin.message.parameters-update-success'), '?admin=albums');
		}

	}
	
	
//******************************************************************************
//
//	ALBUMS METHODS
//
//******************************************************************************
	
	public function loadAlbums($parent = null, $parentGroups = array(), &$ns = 0)
	{

		if (!$parent)
			$dir = $this->pathAlbums;
		else
			write('<strong>' . basename($dir = $parent->path) . '</strong> ' . l('admin.albums-found') . ' ' . $dir, true);

		$parentGroups = array_index($parentGroups, 'group_id');

		$albumsInDir = getDir($dir);
		$albums = array();
		$albums_groups = array();
		foreach ($albumsInDir as $album) {
			if (!is_dir($dir . $album))
				continue;
			
			$album = object(
				'id'	, 0,
				'path'	, $dir . $album . '/',
				'ns_left', ++$ns,
				'ns_right', 0,
				'ns_depth',  get($parent, k('ns_depth'), -1) + 1
			);
			
			if (is_file($album->path . '.id'))
				$album->id = (int)file_get_contents($album->path . '.id');

			$album_groups = $this->db->execute('
				SELECT
					albums_groups.id,
					' . $this->db->protect($album->id) . ' AS album_id,
					groups.id AS group_id,
					IFNULL(albums_groups.access, -2) AS access
				FROM
					groups
				LEFT JOIN
					albums_groups ON groups.id = albums_groups.group_id AND
					album_id = ' . $this->db->protect($album->id) . '
				ORDER BY
					groups.name ASC
				;
			');

			foreach ($album_groups as $index => $group) {
				$parentAccess = get($parentGroups, k($group->group_id, 'access'), self::ACCESS_FORBIDDEN_INHERITED);
				if ($parentAccess >= 0)
					$parentAccess-= 2;
				$group->access_inherited = $parentAccess;
			
				if ($group->access < 0)
					if (self::ACCESS_FORBIDDEN_INHERITED == $group->access = $group->access_inherited)
						unset($album_groups[$index]);
						
			}
			
			$albums[] = $album;
			$albums_groups = array_merge($albums_groups, $album_groups);
			$children = $this->loadAlbums($album, $albums_groups, $ns);
			$album->ns_right = ++$ns;
			foreach ($children->albums as $child)
				$albums[] = $child;
			foreach ($children->albums_groups as $group)
				$albums_groups[] = $group;
		}
		
		if ($dir == $this->pathAlbums) {

			$this->db->updateArray('albums', $albums);
			$this->db->updateArray('albums_groups', $albums_groups);

			// Save id in album path
			foreach ($albums as $album)
				file_put_contents($album->path . '.id', $album->id);

			success(l('admin.message.albums-reload-success'), '?admin=albums');
		}

		return object('albums', $albums, 'albums_groups', $albums_groups);
	
	}

	private function getAlbums()
	{
		if (!$this->user)
			return;
		
		if (exists($this, 'albums'))
			return $this->albums;

		$this->albums = $this->db->execute('
			SELECT
				*
			FROM
				albums
			ORDER BY
				ns_left ASC
			;
		');

		$this->albumsSort();
		
		$depths = array();
		$ns_right = 0;
		foreach ($this->albums as $index => $album) {
			$this->getAlbum($album->id);
			// Manage access for non admin user
			if (!$this->user->admin) {
				$access = false;
				foreach ($this->user->groups as $userGroup) {
					$groupAccess = get($album->groups, k($userGroup->id, 'access'), -2);
					if ($access = ($groupAccess == -1 or $groupAccess == 1))
						break;
				}

				if ($access) {
					$total = count($depths);
					for ($i = $total - 1; $i >= 0; $i--) {
						if ($album->ns_left > $depths[$i]->ns_right or $album->ns_right < $depths[$i]->ns_left or $album->ns_depth == $depths[$i]->ns_depth) {
							unset($depths[$i]);
						} else {
							$album->ns_depth = $depths[$i]->ns_depth + 1;
							break;
						}
					}
				
					$depths[] = $album;
					$depths = array_values($depths);
				} else {
					unset($this->albums[$index]);
				}
			}
		}

	}

	private function albumsSort($ns = null)
	{
		if (!$ns)
			$ns = object('left', 1, 'right', count($this->albums) * 2, 'depth', 0);

		$albumsSort = array();
		foreach ($this->albums as $album) {

			if ($album->ns_depth == $ns->depth and $album->ns_left >= $ns->left and $album->ns_right <= $ns->right) {
				$start = $album->date_start;
				$end = $album->date_end;
				if (!$start)
					$start = $end;
				if (!$end)
					$end = $start;
				if (!$start)
					$start = $end = '0000-00-00';
				$albumsSort[$start . $end . '.' . (trim($album->name) ? trim($album->name) : basename($album->path))] = $album;
				
			}
		}
		ksort($albumsSort);

		$albums = array();
		foreach ($albumsSort as $album) {
			$albums[$album->id] = $album;
			if ($album->ns_left + 1 < $album->ns_right)
				$albums+= $this->albumsSort(object(
					'left', $album->ns_left + 1,
					'right', $album->ns_right - 1,
					'depth', $album->ns_depth + 1
				));
		}
		
		if ($ns->left == 1)
			$this->albums = $albums;
		
		return $albums;

	}
	
	// Return album infos
	public function getAlbum($id)
	{
		// Test if album exists
		if (!$album = get($this->albums, k($id)))
			error(l('album.message.error'), '?');		

		// Set thumbs path
		$album->pathThumbs = $this->pathThumbs . $album->id . '/';
		
		// Retrieve groups
		$album->groups = array_index($this->getAlbumGroups($album->id), 'id');
			
		// Retrieve medias
		$album->medias = $this->getMedias($album->id);

		return $album;
	}
	
	private function getAlbumGroups($albumId)
	{
		return $this->db->execute('
			SELECT
				groups.*,
				IFNULL(albums_groups.access, -2) AS access,
				IFNULL(albums_groups.access_inherited, -2) AS access_inherited
			FROM
				groups
			LEFT JOIN
				albums_groups ON groups.id = albums_groups.group_id AND
				album_id = ' . $albumId . '
			ORDER BY
				groups.name ASC
			;
		');
	}
	
	public function albumUpdate($id, $data)
	{
		$album = $this->getAlbum($id);

		if ($data['name'] == '' or basename($album->path) == $data['name'])
			$album->name = '';
		else
			$album->name = $data['name'];
		$album->date_start = $data['date-start'];
		$album->date_end = $data['date-end'];
		$album->description = $data['description'];
		$album->comments_disable = get($data, k('comments-disable'));

		unset($album->pathThumbs);
		unset($album->groups);

		if ($reorder = $data['reorder']) {
			$reorder = json_decode($reorder);
			foreach ($reorder as $order => $mediaId)
				if (exists($album->medias, $mediaId))
					$album->medias[$mediaId]->position = $order;
		}
		
		$this->db->executeArray('medias', $album->medias);
		unset($album->medias);
		$this->db->executeArray('albums', $album);
		
		// Update album access
		$this->albumSetAccess($album->id, $data['access']);

		success(l('album.message.update-success'), '?album=admin&id=' . $album->id);
	}
	
	private function albumSetAccess($albumId, $albumAccess, $albumAccessInherited = null)
	{
		$album = $this->getAlbum($albumId);
		
		$album_groups = array();
		foreach ($albumAccess as $group_id => $access) {
			if ($access == Simplegallery::ACCESS_FORBIDDEN_INHERITED)
				continue;

			$album_group = object(
				'album_id', $album->id,
				'group_id', $group_id,
				'access', $access
			);
			if ($albumAccessInherited)
				$album_group->access_inherited = get($albumAccessInherited, k($group_id), -2);
			else
				$album_group->access_inherited = get($album->groups, k($group_id, 'access_inherited'), -2);

			$album_groups[] = $album_group;
			
			if ($access >= 0)
				$albumAccess[$group_id]-= 2;
		}

		$this->db->updateArray('albums_groups', $album_groups, null, 'group_id', 'album_id=' . $this->db->protect($album->id));

		// Update album children access
		$children = $this->db->execute('
			SELECT
				id
			FROM
				albums
			WHERE
				ns_left > ' . $this->db->protect($album->ns_left) . ' AND
				ns_right < ' . $this->db->protect($album->ns_right) . ' AND
				ns_depth = ' . $this->db->protect($album->ns_depth + 1) . '
			;
		');
		foreach ($children as $child) {
		
			$childAccess = array();
			$group = array_index($this->getAlbumGroups($child->id), 'id');
			foreach ($albumAccess as $group_id => $access) {
				
				if ( 0 > $chAccess = get($group, k($group_id, 'access'), -2))
					$chAccess = $access;
					
				$childAccess[$group_id] = $chAccess;
			}
			$this->albumSetAccess($child->id, $childAccess, $albumAccess);
		}
	
	}
	
//******************************************************************************
//
//	MEDIAS METHODS
//
//******************************************************************************
	
	// Return list of medias who need update and files who are not necessary
	// Load medias of album in database too
	public function mediasCheck($albumId)
	{		

		// Retrieve album
		$album = $this->getAlbum($albumId);

		// Retrieve list of files in thumb dir (contains delete list at end of this function)		
		if (is_dir($album->pathThumbs))
			$mediasDir = array_fill_keys(getDir($album->pathThumbs), true);
		else
			$mediasDir = array();
		$update = array();

		$mediasDb = array_index($this->db->execute('
			SELECT
				*
			FROM
				medias
			WHERE
				album_id = ' . $this->db->protect($album->id) . '
			;
		'), 'file');

		$medias = array();
		$files = getDir($album->path);
		$total = count($files);
		foreach ($files as $i => $media) {
			if (
				!is_file($file = $album->path . $media) or
				!$type = $this->getMediaType($file)
			)
				continue;

			resetTimout();

			$size = $type == 'image' ? imagesize($file) : FFmpeg::getSize($file);
			
			$md5 = md5_file($file);
			$basename = $media;
			$media = get($mediasDb, k($basename));
			if ($media)
				unset($mediasDb[$basename]);
			else
				$media = object(
					'id', 0,
					'album_id', $album->id,
					'file', $basename,
					'md5', '',
					'width', $size->width,
					'height', $size->height,
					'orientation', exif_imagetype($file) === false ? 1 : (int)geta(exif_read_data($file), k('Orientation'), 1),
					'rotation', 0,
					'thumb_index', 0,
					'type', $type
				);
			write('<strong>' . round($i * 100 / $total) . '%</strong> - ' . $i . '/' . $total . ' "' . toHtml($media->file) . '" ' . l('admin.media-found') . ' ');

			$videosDeleted = array();
			foreach ($this->dimensions as $key => $dimension) {

				$needUpdate = $media->md5 != $md5;
			
				if ($dimension->type == 'sprite') {

					$thumb = $dimension->size . '-' . $dimension->type . '.jpg';
					
					// Format delete sprite data
					if (exists($mediasDir, $thumb) and !is_object($mediasDir[$thumb])) {
						$totalThumb = imagesize($album->pathThumbs . $thumb)->height / $dimension->size;
						$mediasDir[$thumb] = object(
							'dimension'	, $dimension,
							'medias'	, array_fill(1, $totalThumb, true)
						);
					}

					// If media does not exists in sprite data
					if (!$needUpdate)
						$needUpdate = !in_array($media->thumb_index, get($mediasDir, k($thumb, 'medias'), array()));

					// Delete media from json sprite file list
					if (exists($mediasDir, $thumb, 'medias', $media->thumb_index))
						unset($mediasDir[$thumb]->medias[$media->thumb_index]);

				} else {

					if ($type == 'image') {
						$thumb = $media->file . '.' . $dimension->size . '-' . $dimension->type . '.jpg';
						if (!$needUpdate)
							$needUpdate = !exists($mediasDir, $thumb);

					} else {
						$thumb = $media->file . '.webm';
						if ('webm' == strtolower(pathinfo($media->file, PATHINFO_EXTENSION)))
							$needUpdate = false;
						else {
							$dimension = 'convert';
							if (!$needUpdate and !exists($videosDeleted, $thumb))
								$needUpdate = !exists($mediasDir, $thumb);
						}
					}
				
				}

				if (!get($mediasDir, k($thumb, 'medias'))) {
					unset($mediasDir[$thumb]);
					if ($type == 'video')
						$videosDeleted[$thumb] = true;
				}

				if (!$needUpdate)
					continue;

				if (!exists($update, $media->file))
					$update[$media->file] = object('media', $media, 'dimensions', array());
				if (!in_array($dimension, $update[$media->file]->dimensions))
					$update[$media->file]->dimensions[$key] = $dimension;

			}
			$media->md5 = $md5;
			$this->db->executeArray('medias', $media);
		}
		
		// Delete medias not found from db
		foreach ($mediasDb as $media)
			$media->id*= -1;
		$this->db->executeArray('medias', $mediasDb);

		return object(
			'delete', $mediasDir,
			'update', $update
		);

	}
	
	public function getMediaType($file)
	{
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		if (in_array($ext, array('jpg', 'jpeg', 'png')))
			return 'image';
		elseif (in_array($ext, array('webm', 'mp4', 'mts', 'mpg', 'avi')))
			return 'video';

		return false;
	}
	
	private function getMedias($albumId)
	{
		$album = $this->albums[$albumId];
		$medias = array_index($this->db->execute('
			SELECT
				*
			FROM
				medias
			WHERE
				album_id = ' . $this->db->protect($albumId) . '
			ORDER BY
				position ASC,
				ifnull(nullif(trim(name), ""), file) ASC
			;
		'), 'id');
		foreach ($medias as $media)
			$media->rotation = (int)$media->rotation;

		return $medias;
	}
	
	// Generate missing thumbs for the album $id
	public function mediasGenerate($albumId)
	{
		$check = $this->mediasCheck($albumId);
		$album = $this->getAlbum($albumId);

		// Create thumb dir
		if (!is_dir($album->pathThumbs))
			mkdir($album->pathThumbs, 0777, true);

		// Delete files not required
		write('<h4>' . l('album.generation.delete') . '</h4>');
		if (!$check->delete)
			write(l('album.generation.delete-nothing'));
		foreach ($check->delete as $file => $data) {
			if (is_object($data)) {
				// Delete sprite indexes
				foreach ($data->medias as $index => $null) {
					$this->mediaDeleteFromSprite($album->id, $index);
					write(l('album.generation.delete-sprite', $index, $file), true);
				}
			} else {
				// Delete file
				unlink($album->pathThumbs . $file);
				write(l('album.generation.delete-file', $file), true);
			}
			
		}

		// Update files
		write('<h4>' . l('album.generation.update') . '</h4>');
		if (!$check->update)
			write(l('album.generation.update-nothing'));
		else {
			// Retrieve total medias to update
			$progress = object(
				'total'	, 0,
				'now'	, 0,
				'percent', 0
			);
			foreach ($check->update as $file => $data)
				$progress->total+= count($data->dimensions);
		}
		
		$newSprite = false;
		foreach ($check->update as $data) {
			$album = $this->getAlbum($albumId);
			$media = $data->media;

			write(l('album.generation.update-start', $media->file), true);

			$file = $album->path . $media->file;

			// Load original media
			$imgMedia = imagecreatefromstring($media->type == 'video' ?
				Ffmpeg::capture($file, 50) :
				file_get_contents($file)
			);

			foreach ($data->dimensions as $key => $dimension) {
				
				// Progression
				resetTimout();
				$progress->percent = round(++$progress->now * 100 / $progress->total);
				
				if ($dimension == 'convert') {
					$options = array(
						'-threads'	=> 0,
						'pass' => 2,
						'video' => array(
							'-vcodec'	=> 'libvpx',
							'-b:v'		=> '1500k', 
							'-minrate'	=> 0,
							'-maxrate'	=> '9000k',
							'-qmin'		=> 1,
							'-qmax'		=> 51
						),
						'video-vp8' => array(
							'-rc_lookahead'	=> 16,
							'-keyint_min'	=> 0,
							'-g'		=> 360,
							'-skip_threshold' => 0,
							'-level'	=> 116
						),
						'audio:1' => array( //pass 1
							'-an' => null
						),
						'audio:2' => array( //pass 2
							'-acodec'	=> 'libvorbis',
							'-ab'		=> '192k'
						)
					);

					$fileThumb = $album->pathThumbs . $media->file . '.webm';
					write(chr(9) . chr(9) . l('album.generation.update-video') . ' :');
					$lastPercent = -1;
					Ffmpeg::convert($file, $fileThumb, $options, function($current, $total, $pass) use (&$lastPercent) {
						$current = ($pass - 1) * $total + $current;
						$percent = floor($current * 100 / ($total * 2));
						if ($percent > $lastPercent)
							write(' ' . $percent . '%');
						$lastPercent = $percent;
					});
					write('', true);
					
				} else {

					// Create or load thumb image
					$fileThumb = ($dimension->type == 'sprite' ? '' : $media->file . '.') . $dimension->size . '-' . $dimension->type . '.jpg';
					$fileThumb = $album->pathThumbs . $fileThumb;
					$new = true;
					if ($dimension->type == 'sprite' and !$new = !is_file($fileThumb))
						// Load existant sprite
						$imgThumb = imagecreatefromstring(file_get_contents($fileThumb));
						// Else create spirte after (0x0 size is formidden in image)
					if ($dimension->type == 'sprite' and $new and !$newSprite)
						$newSprite = true;


					// Prepare vars for resize and crop image
					$dstY = $lagX = $lagY = 0;
					if ($dimension->type == 'sprite') {
				
						if ($media->width >= $media->height) {
							$srcWidth = $media->height;
							$srcHeight = $media->height;
							$lagX = ($media->width - $media->height)/2;
						} else {
							$srcWidth = $media->width;
							$srcHeight = $media->width;
							$lagY = ($media->height - $media->width)/2;
						}
						$dstWidth = $dstHeight = $dimension->size;
					
						// Retrieve index for sprite
						if ($new)
							$index = 1;
						else {
							$index = $media->thumb_index ? $media->thumb_index : false;
							$spriteHeight = imagesy($imgThumb);
							$indexMax = $spriteHeight / $dimension->size;
							if ($newSprite or $index === false or $index > $indexMax) {
								$index = $indexMax+1;
								$imgNewThumb = imagecreatetruecolor($dimension->size, $spriteHeight + $dimension->size);
								imagecopy($imgNewThumb, $imgThumb, 0, 0, 0, 0, $dimension->size, $spriteHeight);
								imagedestroy($imgThumb);
								$imgThumb = $imgNewThumb;
							}
						}

						// Save index for thumb sprite
						$media->thumb_index = $index;

						$dstY = ($media->thumb_index - 1) * $dimension->size;
					} else {
						$srcWidth = $media->width;
						$srcHeight = $media->height;
						if ($media->width >= $media->height) {
							$dstWidth = $dimension->size;
							$dstHeight = floor($dimension->size * $media->height / $media->width);
						} else {
							$dstWidth = floor($dimension->size * $media->width / $media->height);
							$dstHeight = $dimension->size;
						}
					}
				
					if ($new)
						$imgThumb = imagecreatetruecolor($dstWidth, $dstHeight);
				
					imagecopyresampled($imgThumb, $imgMedia, 0, $dstY, $lagX, $lagY, $dstWidth, $dstHeight, $srcWidth, $srcHeight);

					imagejpeg($imgThumb, $fileThumb);
					imagedestroy($imgThumb);
				}

				// Save md5 of thumb (for cache management)
				if (get($dimension, k('type')) == 'sprite')
					$album->thumb_md5 = md5_file($fileThumb);
				else
					$media->{$key . '_md5'} = md5_file($fileThumb);
			
				write(chr(9) . '<strong>' .str_pad($progress->percent, 3, ' ', STR_PAD_LEFT) . ' %</strong> - ' . str_pad($progress->now, strlen($progress->total), ' ', STR_PAD_LEFT) . ' / ' . $progress->total . ' - ');
				if ($dimension == 'convert' or $dimension->type != 'sprite')
					write(l('album.generation.update-file', basename($fileThumb)), true);
				else
					write(l('album.generation.update-sprite', basename($fileThumb)), true);

			}

			imagedestroy($imgMedia);
			
			unset($media->type);
			$this->db->executeArray('medias', $media);
			unset($album->pathThumbs);
			unset($album->groups);
			unset($album->medias);
			$this->db->executeArray('albums', $album);
			
		}
		
		exit();
	}
	
	private function mediaDeleteFromSprite($albumId, $index)
	{
		$album = $this->getAlbum($albumId);
		$dimension = $this->dimensions->thumb;

		if (!is_file($fileThumb = $album->pathThumbs . $dimension->size . '-' . $dimension->type . '.jpg'))
			return;

		// Load sprite image
		$imgSprite = imagecreatefromstring(file_get_contents($fileThumb));
		$height = imagesy($imgSprite);
		if ($height > $dimension->size) {
			$imgNewSprite = imagecreatetruecolor($dimension->size, $height - $dimension->size);

			// Retrieve part of sprite before thumbnail to delete
			$heightSrc = ($index - 1) * $dimension->size - 0;
			imagecopy($imgNewSprite, $imgSprite, 0, 0, 0, 0, $dimension->size, $heightSrc);

			// Retrieve part of sprite after thumbnail to delete
			$lastSrc = $heightSrc + $dimension->size;
			imagecopy($imgNewSprite, $imgSprite, 0, $heightSrc, 0, $lastSrc, $dimension->size, $height - $lastSrc);

			// Save sprite
			imagejpeg($imgNewSprite, $fileThumb);
			imagedestroy($imgNewSprite);
		} else {
			unlink($fileThumb);
		}
		imagedestroy($imgSprite);				

		// Update medias after index
		$this->db->execute('
			UPDATE
				medias
			SET
				thumb_index = thumb_index - 1
			WHERE
				album_id = ' . $this->db->protect($album->id) . ' AND
				thumb_index > ' . $this->db->protect($index) . '
			;
		');
				

	}
	
	public function getMedia($albumId, $media = null, $dim = null)
	{
		if (!$album = $this->getAlbum($albumId))
			die(l('album.media.not-found'));

		if ($media and !$media = get($album, k('medias', $media)))
			die(l('album.media.not-found'));

		if ($dim and !$dim = get($this->dimensions, k($dim)))
			die(l('album.media.not-found'));
		
		if ($media and $media->type == 'video' and 'webm' == strtolower(pathinfo($media->file, PATHINFO_EXTENSION)))
			$dim = null;

		if ($dim) {
			if ($dim->type == 'sprite')
				$file = $dim->size . '-' . $dim->type . '.jpg';
			else {
				if ($media->type == 'image')
					$file = $media->file . '.' . $dim->size . '-' . $dim->type . '.jpg';
				else
					$file = $media->file . '.webm';
			}
			$md5 = $album->thumb_md5;
			$file = $album->pathThumbs . $file;
		} else {
			$md5 = $album->medias[$media->id]->md5;
			$file = $album->path . $media->file;
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
				$headerSince == $time or get($headerMatch) == $etag
			)
		) {
			header('HTTP/1.1 304 Not Modified');
			exit();
		}

		readfile($file);
		exit();
		
	}
	
	public function mediaDownload($albumId, $media)
	{
		$album = $this->getAlbum($albumId);
		if (!$media = get($album->medias, k($media)))
			die(l('album.media.not-found'));

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Last-Modified: '.gmdate ('D, d M Y H:i:s', filemtime ($album->path . $media->file)).' GMT');
		header('Cache-Control: private',false);
		header('Content-Disposition: attachment; filename="'.basename($media->file).'"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($album->path . $media->file));
		header('Connection: close');
		
		$this->getMedia($albumId, $media->file);

	}
	
	public function mediaUpdate($albumId, $mediaId, $updates)
	{

		$album = $this->getAlbum($albumId);
		if (!$media = get($album->medias, k($mediaId)))
			die(l('album.media.not-found'));

		foreach ($updates as $update => $value) {
			
			if (!$this->user->admin and $update != 'comments')
				continue;
			
			switch ($update) {
				case 'rotateLeft' :
				case 'rotateRight' :
					$direction = $update == 'rotateLeft' ? -1 : 1;
				
					$media->rotation+= $direction * 90;
					if ($media->rotation < 0)
						$media->rotation = 270;
					elseif ($media->rotation > 270)
						$media->rotation = 0;
						
					$this->db->executeArray('medias', $media);
				break;
				case 'flipVertical' :
				case 'flipHorizontal' :
					$orientation = $update == 'flipVertical' ? 'vertical' : 'horizontal';

					$media->{'flip_' . $orientation} = !$media->{'flip_' . $orientation};
					 
					 $this->db->executeArray('medias', $media);
				break;
				case 'delete' :
					$this->mediaDelete($album->id, $media->id);
				break;
				case 'description' :
					$media->description = $value;
					
					$this->db->executeArray('medias', $media);
				break;
				case 'comments' :
/*
					$value = nl2br(toHtml($value));
					$media->data->{$update}[] = object(
						'date', date('Y-m-d h:i:s'),
						'user', $this->user->mail,
						'text', $value
					);
					$this->albumSaveConfig($album->id);

					// Send mail to administrators
					foreach ($this->config->users as $admin) {

						if (!in_array('admins', $admin->groups))
							continue;

						$this->mail(
							$admin->mail,
							l('mail.media-new-comment.object', $this->parameters->name, $this->user->name, $media->name, $album->data->name),
							l('mail.media-new-comment.message', $admin->name, $this->user->name, $this->user->mail, $media->name, $album->data->name) . $value . l('mail.signature', $this->parameters->name)
						);
					}

				break;
				case 'tags' :
					if ($value['value'] == '')
						unset($media->data->{$update}[$value['x'].'-'.$value['y']]);
					else
						$media->data->{$update}[$value['x'].'-'.$value['y']] = object(
							'x', $value['x'],
							'y', $value['y'],
							'value', $value['value']
						);
					$this->albumSaveConfig($album->id);
*/
				break;
			}
		}
		
		exit(json_encode(array('success' => 'Update success')));
	}

	private function mediaDelete($albumId, $media)
	{
	
		$album = $this->getAlbum($albumId);
		
		if (!$media = get($album->medias, k($media)))
			die(l('album.media.not-found'));

		unlink($album->path . $media->file);

		foreach ($this->dimensions as $dimension)
			if ($dimension->type == 'sprite')
				$this->mediaDeleteFromSprite($albumId, $media->thumb_index);
			else if (is_file($file = $album->pathThumbs . $media->file . '.' . $dimension->size . '-' . $dimension->type . '.jpg'))
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
    	 		'From: ' . $this->parameters->name . ' <noreply@domain.com>'
    	 	))
		);
	}
}

?>
