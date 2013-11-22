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
	private $emailUpdateCodeLifetime = 'PT48H';
	private $databaseVersion = 5;

	public function __construct($config)
	{
		$this->config = $config;
		if (count($this->config)) {
			$this->albumsPath = realpath($this->config->albumsPath) . '/';
			$this->thumbsPath = realpath($this->config->thumbsPath) . '/';

			include 'includes/Localization.class.php';
			$this->localization = new Localization();
			$this->localization->storeMessage('locales/');

			include 'includes/database/Database.class.php';
			$this->db = new Database($config->database);
			$this->checkDatabase();

			include 'includes/Ffmpeg.class.php';
			
			\Database\Album::setGetNameCallback(array($this, 'getAlbumName'));
		}
		
		
	}
	
	public function checkDatabase()
	{
		$tables = $this->db->getTables();
		if (!$tables)
			return $this->db->compile();

		$databaseVersion = 0;
		if (in_array('parameter', $tables)) {
			$this->parameters = \Database\ParameterTable::findAll();
			$databaseVersion = $this->parameters->getDatabaseVersion();
		}
		if ($databaseVersion == $this->databaseVersion)
			return;
		
		$this->db->compile();
		
		while (++$databaseVersion <= $this->databaseVersion) {
			if ($databaseVersion == 1)
				$this->parameters = new \Database\Parameter();
			if ($databaseVersion == 4)
				$this->parameters->setGalleryName('SimpleGallery');

		}
		$this->parameters->setDatabaseVersion($this->databaseVersion);
		$this->parameters->save();
		
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
			'structure', object(
				'back', object('enable', false, 'url', '?'),
				'title', ''
			),
			'menu', object(
				'albumconfig', object('enable', false, 'url', '#'),
				'load', object('enable', false, 'url', '?album=loader'),
					'loadAnalyze', object('enable', false, 'url', '?album=analyzer'),
					'loadSynchronize', object('enable', false, 'url', '?album=synchronizer'),
				'users', object('enable', false, 'url', '?user=management'),
				'deleted', object('enable', false, 'url', '#'),
				'parameters', object('enable', false, 'url', '?parameter')
			),
			'js', object()
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
						if (!$album)
							exit('Album not found');

						$coversId = array();
						foreach ($album->getChildren() as $children) {
							$covers = json_decode($children->getCoverMedias());
							if (!is_object($covers))
								continue;
							foreach ($covers as $id => $cover)
								$coversId[] = $id;
						}

						if ($coversId) {
							$q = new \Database\Object\Query();
							$q->from('media')->where('id IN (' . implode(',', $this->db->protectArray($coversId)) . ')');
							$covers = $q->execute()->getData();
							foreach ($covers as $cover)
								$albumCovers[$cover->id] = $cover;
							
						} else
							$albumCovers = array();

						$q = new \Database\Object\Query('media');
						$q->select('IF(media.date="0000-00-00 00:00:00",exifDate,media.date) AS datesort', 'media.*');
						$q->where('album_id = ?', $album->getId());
						$q->orderBy('datesort');
						$medias = $q->execute();

						$q = new \Database\Object\Query('comment');
						$q->select('comment.id AS id', 'media.id AS mediaId', 'comment.date', 'text', 'name AS username');
						$q->leftJoin('media');
						$q->leftJoin('user');
						$q->where('album_id = ?', $album->getId());
						$q->addOrderBy('comment.date');
						$comments = $q->execute();
						$commentsIndexed = array();
						foreach ($comments as $comment)
							$commentsIndexed[$comment->mediaId][] = $comment;
						unset($comments);

						if ($this->user->isAdmin()) {
							if ($album) {
								$response->menu->albumconfig->enable = true;
								$response->menu->albumconfig->url = '?album=config&id=' . $album->getId();
							}
							$response->menu->load->enable = true;
							$response->menu->users->enable = true;
							$response->menu->deleted->enable = true;
							$response->menu->parameters->enable = true;
						}
						if ($album->getParent_id() > -1) {
							$response->structure->back->enable = true;
							$response->structure->back->url = '?album&id=' . $album->getParent_id();
						}

						$albumOrMedia = (count($album->getChildren()) or $medias);

						$response->structure->title = $album->getAutoName();
						$response->data['album'] = $album;
						$response->data['albumCovers'] = $albumCovers;
						$response->data['medias'] = $medias;
						$response->data['comments'] = $commentsIndexed;
						$response->data['albumOrMedia'] = $albumOrMedia;

					break;
				
					case 'timeline' :
					
						if (!$this->user->isAdmin())
							error('Access is forbidden.', '?'); // For dévelopement
							

						$q = new \Database\Object\Query('media');
						$q->select('IF(date="0000-00-00 00:00:00",exifDate,date) AS datesort', 'media.*');
						$q->where('date != "0000-00-00 00:00:00" OR exifDate != "0000-00-00 00:00:00"');
						$q->one()->limit(1);
						
						$qMin = clone $q;
						$qMax = clone $q;
						$qMin->orderBy('datesort', 'ASC');
						$qMax->orderBy('datesort', 'DESC');
						
						$min = $qMin->execute();
						$max = $qMax->execute();
						
						$q = new \Database\Object\Query('media');
						$q->select('SUBSTRING(IF(date="0000-00-00 00:00:00",exifDate,date), 1, 7) as yearmonth', 'COUNT(*)');
						$q->groupBy('yearmonth');
						$group = $q->execute();
						
						trace($min->datesort, $max->datesort, $group);
						quit(Debug::chronoGet('phptime'));
						
						$response->data['medias'] = $medias;

					break;
				
					case 'loader' :

						if (!$this->user->isAdmin())
							error('Access is forbidden.', '?');

						$response->data['albums'] = \Database\AlbumTable::findAllOrderPath();

						$response->structure->back->enable = true;

						$response->menu->loadAnalyze->enable = true;
						$response->menu->loadSynchronize->enable = true;
						$response->menu->users->enable = true;
						$response->menu->parameters->enable = true;
						
						$response->structure->title = 'Manage albums';

					break;
					
					case 'synchronizer' :

						if (!$this->user->isAdmin())
							error('Access is forbidden.', '?');
	
						$response->structure->back->enable = true;

						$response->menu->users->enable = true;
						$response->menu->parameters->enable = true;
						
						$response->structure->title = 'Synchronization albums structures';
					
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

						if (!exists($_POST, 'albums'))
							error('Please select at least one album to analyze', '?album=loader');
					
						$response->data['albums'] = $_POST['albums'];	
						$response->structure->back->enable = true;
						
						$response->menu->users->enable = true;
						$response->menu->parameters->enable = true;
						
						$response->structure->title = 'Medias analyze';
					
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
						
						$response->structure->back->enable = true;
						$response->structure->back->url = '?album&id=' . $album->getId();
					
						$response->menu->load->enable = true;
						$response->menu->users->enable = true;
						$response->menu->parameters->enable = true;
					
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
						
						if (exists($_GET, 'date')) {
							$media->setDate($_GET['date']);
						}
						
						$media->save();
					
						exit(json_encode(array('media-update' => true)));
					
					break;
					
					case 'comment' :
					
						if ($this->parameters->isDisableComments())
							exit('Comments are disable');
					
						if (exists($_POST, 'id') and exists($_POST, 'text')) {
					
							if (\Database\MediaTable::findOneByIdWithAlbum($_POST['id'])->getAlbum()->isDisableComments())
								exit('Comments are disable for this album');
					
							$comment = new \Database\Comment();
							$comment->setDate(date('Y-m-d H:m:i'));
							$comment->setText($_POST['text']);
							$comment->setUser_id($this->user->getId());
							$comment->setMedia_id($_POST['id']);
							$comment->save();

					
							exit(json_encode(array('media-comment' => object(
								'id', $comment->getId(),
								'date', $comment->getDate(),
								'text', $comment->getText()
							))));
						} else if (exists($_POST, 'deleteId')) {

							if (\Database\CommentTable::findOneByIdWithMediaAndAlbum($_POST['deleteId'])->getMedia()->getAlbum()->isDisableComments())
								exit('Comments are disable for this album');

							$comment = new \Database\Comment($_POST['deleteId']);
							$comment->delete();
						
							exit(json_encode(array('comment-delete' => true)));
						
						}

					
					break;
					
				}
			
			break;
			
			case 'user' :
			
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
					
						if ($this->parameters->isDisableRegistration() and !$this->noAdminUser())
							error('Registration is disabled.', '?');
					
						if ($_POST)
							$this->userRegistration($_POST);
							
						if ($code = get($_GET, k('rcode')))
							$this->userRegistration($code);
					
						$response->menu = false;
						$response->structure->back->enable = true;
						
					
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
						$response->structure->back->enable = true;
					
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
						
						$response->structure->back->enable = true;
						
						$response->menu->load->enable = true;
						$response->menu->users->enable = true;
						$response->menu->parameters->enable = true;
						
						$response->structure->title = 'Users management';
					
					break;
					
					case 'profile' :
					
						if ($_POST)
							$this->userUpdate($_POST);
						
						
						if (exists($_GET, 'mcode'))
							$this->userUpdateEmail($_GET['mcode']);
						
						$response->structure->back->enable = true;

						if ($this->user->isAdmin()) {
							$response->menu->load->enable = true;
							$response->menu->users->enable = true;
							$response->menu->deleted->enable = true;
							$response->menu->parameters->enable = true;
						}
					
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
			
			case 'parameter' :

				switch ($action) {
					case 'index' :
					default :

						if (!$this->user->isAdmin())
							exit('Access is forbidden.');

						$response->action = 'index';
						
						$response->structure->back->enable = true;

						$response->menu->load->enable = true;
						$response->menu->users->enable = true;
						$response->menu->deleted->enable = true;

						
						
						if ($_POST)
							$this->setParameters($_POST);
						
					break;
				}
			
			break;
		
		}

		$response->js->{$response->route} = $this->locale($response->route . '.' . $response->action . '.js');

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

			if ($album and $album->getPath() != '') {
				
				// Retrieve album access
				$access = $this->getAlbumAccess($album);
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
				if (!$parent)
					$parent = \Database\AlbumTable::findOneByPathWithGroup('');
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
	
	private function getAlbumAccess(\Database\Album $album)
	{
		
	
		$access = false;
		foreach ($this->user->getGroupCollection() as $userGroup) {
			$albumGroup = $album->getGroupCollection()->findOneById($userGroup->getId());
			if ($albumGroup) {
				
				if ($albumGroup->getAccess() == -1 or $albumGroup->getAccess() == 1) {
					$access = true;
					break;
				}

			} else {
				// If no group has already set by admin for this album
				$q = new \Database\Object\Query('album');
				$q->leftJoin('group');
				$explode = explode('/', $album->getPath());
				array_splice($explode, - 2);
				$where = '(';
				$whereValues = array();
				for ($i = 0; $i < count($explode); $i++) {
					$path = implode('/', array_slice($explode, 0, $i+1)) . '/';
					$where.= ' OR path = ?';
					$whereValues[] = $path;
				}
				$q->where($where . ')', $whereValues);
				
				$q->AndWhere('group_id = ?', $userGroup->getId());
				$q->AndWhere('access IS NOT NULL');
				$q->Orderby('path', 'DESC');
				$parent = $q->one()->execute();
				
				if ($parent and ($parent->getAccess() == -1 or $parent->getAccess() == 1)) {
					$access = true;
					break;
				}
			}
		}
			
		return $access;
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
		$album->setDisableComments(get($config, k('disableComments')));		
		
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
		foreach ($groups as $group)
			if ($group->getAccess_inherited() == null)
				$group->setAccess_inherited(-2);
		
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
	


	public function getMediaTransform($media)
	{
	
		if (is_numeric($media))
			$media = new \Database\Media($media);
	
		$result = object(
			'flip', object(
				'horizontal', (bool)$media->flipHorizontal,
				'vertical', (bool)$media->flipVertical
			),
			'rotation', $media->rotation
		);

		switch ($media->exifOrientation) {
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
	
	public function getAlbumName($name)
	{
		preg_match('/(?:[\d-~]{2,})?\s*(.*)/', $name, $match);
		if ($match and trim($match[1]))
			return trim($match[1]);
		
		return $name;
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
				error('Registration code is invalid.', '?user=registration');
			
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
					error('Lost passord code is invalid or outdated.', '?user=lost-password');
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
			
			error('Lost passord code is invalid or outdated.', '?');
		
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
	
	private function userUpdate($data)
	{
		$this->user->setName($data['name']);
		if (trim($data['password'])) {
			if ($data['password'] != $data['password-verification'])
				error('The two passwords are differents. Please retype your password.', '?user=profile');
			$this->user->setCryptPassword($data['password']);
		}
		
		if ($mailUpdate = $this->user->getEmail() != $data['email']) {
		
			if (\Database\UserTable::findOneByEmail($data['email']))
				error('The mail "' . $data['email'] . '" is already registered.', '?user=profile');
		
			$this->user->setEmailUpdate($data['email']);
			$this->user->setEmailUpdateCode(randomString(12));
			$this->user->setEmailUpdateCodeTime(date('Y-m-d H:i:s'));
			
			$link = httpUrl() . '?user=profile&mcode=' . $this->user->getEmailUpdateCode();
			$this->sendMail(
				$this->user->getEmailUpdate(),
				'Mail update, please valid your mail',
				'Hello ' . toHtml($this->user->getName()) . ',' . chr(10) . chr(10) . 'To activate your email updating, please click on the following link :' . chr(10) . '<a href="' . $link . '">' . $link . '</a>'
			);
			
		}
		
		$this->user->save();
		
		success('Your profile has been updated successfully.' . ($mailUpdate ? chr(10) . 'Please check your mailbox to activate your new mail.' : ''), '?user=profile');
			
	}
	
	private function userUpdateEmail($code)
	{
		$timeMin = new DateTime();
		$timeMin->sub(new DateInterval($this->emailUpdateCodeLifetime));
		$timeMin = $timeMin->format('Y-m-d H:i:s');

		$user = \Database\UserTable::findOneByEmailupdatecodeAndIsActiveAndUpperEmailupdatecode($code, $timeMin);

		if (!$user)
			error('Update email code is invalid or outdated.', '?user=profile');

		$user->setEmail($user->getEmailUpdate());
		$user->setEmailUpdate('');
		$user->setEmailUpdateCode('');
		$user->setEmailUpdateCodeTime('');
		$user->save();
		success('You email has been updated successfully.', '?user=profile');
	}

	private function setParameters($data)
	{
		$this->parameters->setGalleryName($data['galleryName']);
		$this->parameters->setDisableRegistration(get($data, k('disableRegistration')));
		$this->parameters->setDisableComments(get($data, k('disableComments')));
		$this->parameters->save();
		
		success('The parameters have been updated successfully.', '?parameter');
	}

	public function locale()
	{
		$args = func_get_args();
		if (count($args) > 1)
			$args[1] = array_merge(array(null), array_splice($args, 1));
			
		return call_user_func_array(array($this->localization, 'getMessage'), $args);
	}
	
	public function l()
	{
		return toHtml(call_user_func_array(array($this, 'locale'), func_get_args()));
	}

}
