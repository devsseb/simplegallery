<?

class SimpleGallery
{

	private $config, $db;
	private $albumsPath, $thumbsPath;
	
	private $albumFileId = '.id2';
	private $albumCoverMediaMax = 5;
	private $sessionLifetime = 'P60D';
	private $passwordCodeLifetime = 'PT2H';
	private $mediasTypes = array(
		'image'	=> array('jpg', 'jpeg', 'png', 'gif', 'bmp'),
		'video'	=> array('avi', 'mts', 'mkv', 'mov', 'mpeg', 'webm'),
		'book'	=> array('cbz', 'cbr', 'pdf')
	);
	private $mediaThumbs = array(
		'album'		=> array('size' => 50	, 'type' => 'sprite', 'width' => 200, 'height' => 180),
		'preview'	=> array('size' => 200	, 'type' => 'height'),
		'slideshow'	=> array('size' => 1000	, 'type' => 'long')
	);

	public function __construct($config)
	{
		$this->config = $config;
		$this->albumsPath = realpath($this->config->albumsPath) . '/';
		$this->thumbsPath = realpath($this->config->thumbsPath) . '/';

		// Prepare regexp for medias search
		$this->mediasMask = array_merge($this->mediasTypes['image'], $this->mediasTypes['video'], $this->mediasTypes['book']);
		array_walk($this->mediasMask, function(&$ext) {
			$ext = preg_quote($ext);
		});
		$this->mediasMask = '/\\.(' . implode('|', $this->mediasMask) . ')$/i';

		include 'includes/database/Database.class.php';
		$this->db = new Database($config->database);
//		quit($this->db->compile());

		include 'includes/Ffmpeg.class.php';
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

		$response = object(
			'route', $route,
			'action', $action,
			'data', array(),
			'menu', object(
				'back', object('enable', true, 'url', '#'),
				'albumconfig', object('enable', true, 'url', '#'),
				'load', object('enable', true, 'url', '#'),
				'console', object('enable', true, 'url', '#'),
				'users', object('enable', true, 'url', '?user=management')
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
						if (!$album->getId()) {
							$response->menu->albumconfig->enable = false;
							$response->menu->back->enable = false;
						}
						
						if ($this->user->isAdmin()) {
							$response->menu->albumconfig->url = '?album=config&id=' . $album->getId();
							$response->menu->load->url = '?album=loader&id=' . $album->getId();
						} else {
							$response->menu->albumconfig->enable = false;
							$response->menu->load->enable = false;
							$response->menu->users->enable = false;
						}
						$response->menu->back->url = '?album&id=' . $album->getParent_id();
						$response->menu->console->enable = false;

						$response->data['album'] = $album;

					break;
				
					case 'loader' :

						if (!$this->user->isAdmin())
							error('Access is forbidden.', '?');
					
						$response->data['album'] = new \Database\Album(get($_GET, k('id'), 0));
						$response->menu->back->url = '?album&id=' . $response->data['album']->getId();
						$response->menu->load->enable = false;
							
					
					break;
				
					case 'load' :
						
						if (!$this->user->isAdmin())
							error('Access is forbidden.', '?');
						
						if ($id = get($_GET, k('id'), 0))
							$total = $this->loadAlbums(new \Database\Album($id));
						else
							$total = $this->loadAlbums();
						exit();
						
					break;

					case 'cover' :
					
						if ($id = get($_GET, k('id'), 0))
							$album = new \Database\Album($id);
					
						if (is_file($file = $this->getThumbsPath($album) . 'cover.jpg')) {
							header('Content-Type: image/jpeg');
							readfile($file);
						} else {
							header('Content-Type: image/png');
							readfile('routes/album/index/blank.png');
						}
						exit();
					
					break;
					
					case 'config' :
						if (!$this->user->isAdmin())
							error('Access is forbidden.', '?');
						
						if ($_POST)
							$this->setAlbumConfig($_GET['id'], $_POST);
						
						$album = $this->getAlbumConfig(get($_GET, k('id')));
						
						if (!$album->getId())
							go('?');
						
						$response->data['album'] = $album;
						
						$response->menu->back->url = '?album&id=' . $album->getId();
						$response->menu->console->enable = false;
					
					break;
				
				}
			
			break;
			
			case 'media' :
			
				$media = new \Database\Media($_GET['id'], 'album');

				switch ($action) {
				
					default :

					break;
					case 'preview' :
					case 'slideshow' :
					
						if ($media->getType() == 'video' and $action == 'slideshow')
							$file = $this->getThumbsPath($media->getAlbum()) . basename($media->getPath()) . '.webm';
						else
							$file = $this->getThumbsPath($media->getAlbum()) . basename($media->getPath()) . '.' . $action . '.jpg';
					
						$etag = md5($time = gmdate('r', filemtime($file)) . $media->getMd5());

						$finfo = finfo_open(FILEINFO_MIME_TYPE);
						$typemime = finfo_file($finfo, $file);
						finfo_close($finfo);

						header("Cache-Control:private");
						header('ETag:"' . $etag . '"');
						header('Last-Modified:' . $time);
						header('Content-Type: ' . $typemime);

						if ($time == get($_SERVER, k('HTTP_IF_MODIFIED_SINCE')) or $etag == get($_SERVER, k('HTTP_IF_NONE_MATCH'))) {
							header('HTTP/1.1 304 Not Modified');
							exit();
						}
						
						readfile($file);
						exit();

					break;
				
				}
			
			break;
			
			case 'user' :
			
				$response->menu->back->url = '?';
				$response->menu->albumconfig->enable = false;
				$response->menu->load->enable = false;
				$response->menu->console->enable = false;
			
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
	
		$album = new \Database\Album($id, 'group');

		if ($this->user->isAdmin()) {
	
			if (!$album->getId())
				$album->setChildren(\Database\AlbumTable::findByParent_idOrderNamePath(0));
			
		} else {

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
		if (!$album->getId())
			error('Album not found.');
			
		$groups = \Database\GroupTable::findAllOrderName();						
		
		foreach ($groups as $group) {
			$groupAlbum = $album->getGroupCollection()->findOneById($group->getId());
			if ($groupAlbum->getId()) {
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
		if (!$album->getId())
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
				if ($groupFound->getId() and $groupFound->getAccess() >= 0) {
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
	
	private function loadAlbums($parent = null)
	{

		if (!$this->user->isAdmin())
			return;

		$parentPath = $this->getAlbumsPath();
		if ($parent)
			$parentPath.= $parent->getPath();

		$mediasTotal = $parent ? $this->loadMedias($parent) : 0;
		$albumsTotal = 0;

		$albumsInDir = getDir($parentPath);
		foreach ($albumsInDir as $path) {

			if (!is_dir($fullPath = $parentPath . $path))
				continue;

			$albumsTotal++;
			$fullPath.= '/';
			$path = substr($fullPath, strlen($this->getAlbumsPath()));

			$inode = fileinode($fullPath);

			$album = \Database\AlbumTable::findOneByInode($inode);
			write('ALBUM ' . ($album->getId() ? 'SCAN' :  'NEW') . ' ' . $path . chr(10));

			$album->setInode($inode);
			$album->setPath($path);

			$album->setParent($parent);

			$album->save();

			$total = $this->loadAlbums($album);

			$album->setAlbumsTotal($total->albums);
			$album->setMediasTotal($total->medias);
			
			$album->save();

		}
		
		if ($parent)
			$this->generateAlbumCover($parent);
		
		return object(
			'albums', $albumsTotal,
			'medias', $mediasTotal
		);
	}
	
	
	private function getMediaType($media)
	{
		$ext = strtolower(pathinfo($media, PATHINFO_EXTENSION));
		if (in_array($ext, $this->mediasTypes['video']))
			return 'video';
		elseif (in_array($ext, $this->mediasTypes['book']))
			return 'book';
		else
			return 'image';
	}
	
	private function loadMedias($album)
	{
		if (!$this->user->isAdmin())
			return;
	
		$mediasLoaded = $album->getMediaCollection();

		$albumPath = $this->getAlbumsPath() . $album->getPath();

		$mediasTotal = count($medias = getDir($albumPath, $this->mediasMask));
		
		write('ALBUM TOTAL(' . $mediasTotal . ') ' . $album->getPath() . chr(10));
		foreach ($medias as $mediaName) {
		
			$md5 = md5_file($fullPath = $albumPath . $mediaName);
			$media = $mediasLoaded->findOneMd5($md5);
			
			resetTimout();
			
			if ($md5 == $media->md5) {
			
				$mediasLoaded->removeOneMd5($md5);
				$thumbs = object(
					'preview', (bool)$media->getThumbPreviewMd5(),
					'slideshow', (bool)$media->getThumbSlideshowMd5()
				);
			
			} else {
				
				if ($exif = exif_imagetype($fullPath) !== false)
					$exif = @exif_read_data($fullPath);
				if (!$exif)
					$exif = array();
				
				if ('video' == $type = $this->getMediaType($fullPath)) {
					$size = Ffmpeg::getSize($fullPath);

					$string = substr($fullPath, 0, strlen($fullPath) - strlen(pathinfo($fullPath, PATHINFO_EXTENSION)) - 1);
					$vertical = strtolower(pathinfo($string, PATHINFO_EXTENSION));
					if ($vertical == 'verticalleft')
						$exif['Orientation'] = 6;
					elseif ($vertical == 'verticalright')
						$exif['Orientation'] = 8;
					
				} elseif ($type == 'book') {
					
					$book = new Book($fullPath);
					$page = imagecreatefromstring($book[1]->content);
					$size = imagesize($page);
					imagedestroy($page);
					
				} else {
					$size = imagesize($fullPath);
				}
				$date = geta($exif, k('DateTime'), '');
				if (preg_match('/([0-9]{4}).([0-9]{2}).([0-9]{2}).([0-9]{2}).([0-9]{2}).?([0-9]*)/', $date, $match))
					$date = $match[1] . '-' . $match[2] . '-' . $match[3] . ' ' . $match[4] . ':' . $match[5] . ':' . ($match[6] ? $match[6] : '00');

			
				$media->setAlbum($album);
				$media->setPath(substr($fullPath, strlen($this->getAlbumsPath())));
				$media->setMd5($md5);
				$media->setType($type);

				$media->setWidth($size->width);
				$media->setHeight($size->height);
			
				$media->setExifOrientation((int)geta($exif, k('Orientation'), 1));
				$media->setExifDate($date);
				
				$media->setExifData(json_encode($exif));
				
				$media->save();
				
				$thumbs = object(
					'preview', false,
					'slideshow', false
				);
				
			}
			
			$save = false;
			foreach($thumbs as $thumbName => $generated)
				if (!$generated) {
					resetTimout();
					if (!$save)
						write('MEDIA THUMB ' . $media->getPath() . chr(10));
					$save = true;
					$media->{'setThumb' . ucfirst($thumbName) . 'Md5'}($this->generateMediaThumb($thumbName, $media));
				}

			if ($save)
				$media->save();
			else
				write('MEDIA FOUND ' . $media->getPath() . chr(10));
			
			unset($media);
		}
		
		foreach ($mediasLoaded as $media) {
			$media->delete();
			write('MEDIA DELETE ' . $media->getPath() . chr(10));
		}
		
		return $mediasTotal;
	}
	
	private function generateAlbumCover($album)
	{

		if (!$this->user->isAdmin())
			return;

		$this->albumCoverMediaMax;

		$medias = \Database\MediaTable::{'findByAlbum_idOrderPathLimit' . $this->albumCoverMediaMax}($album->getId());
		if (count($medias) < $this->albumCoverMediaMax)
			foreach ($album->getChildren() as $child) {
				if ($childCoverMedias = $child->getCoverMedias()) {
					$childCoverMedias = json_decode($childCoverMedias);
					foreach ($childCoverMedias as $mediaId => $coverSize) {
						$medias[] = new \Database\Media($mediaId);
						
						if (count($medias) == $this->albumCoverMediaMax)
							break;
					
					}
				}
				
				if (count($medias) == $this->albumCoverMediaMax)
					break;
				
			}

		$coverMd5 = $this->mediaThumbs['album']['width'] . '_' . $this->mediaThumbs['album']['height'];
		foreach ($medias as $media)
			$coverMd5.= '_' . $media->getMd5() . '_' . $media->getRotation() . '_' . $media->getFlipHorizontal() . '_' . $media->getFlipVertical();
/*
		if ($album->getCoverMd5() == $coverMd5 = md5($coverMd5))
			return;
*/
		write('ALBUM COVER ' . $album->getPath() . chr(10));
		
		// Create album cover sprite
		$thumbPath = $this->getThumbsPath($album);
		if (!is_dir($thumbPath))
			mkdir($thumbPath, 0777, true);

		$coverMedias = array();

		$sizeMax = object('width', 0, 'height', 0);
		foreach ($medias as $media) {
			$transform = $this->getMediaTransform($media);
			if ($media->{'get' . (($transform->rotation == 0 or $transform->rotation == 180) ? 'Width' : 'Height')}() > $sizeMax->width)
				$sizeMax->width = $media->{'get' . (($transform->rotation == 0 or $transform->rotation == 180) ? 'Width' : 'Height')}();
			if ($media->{'get' . (($transform->rotation == 0 or $transform->rotation == 180) ? 'Height' : 'Width')}() > $sizeMax->height)
				$sizeMax->height = $media->{'get' . (($transform->rotation == 0 or $transform->rotation == 180) ? 'Height' : 'Width')}();
		}
		
		$cover = imagecreatetruecolor(1,1);
		
		$coverSize = object('width', 0, 'height', 0);
		foreach ($medias as $media) {
				
			$file = $this->getThumbsPath($media->getAlbum()) . basename($media->getPath()) . '.preview.jpg';			

			$image = imagecreatefromstring(file_get_contents($file));
			
			$transform = $this->getMediaTransform($media);
			if ($transform->rotation)
				$image = imagerotate($image, $transform->rotation, imagecolorallocatealpha($image, 0, 0, 0, 127));
			if ($transform->flip->horizontal)
				imageflip($image, IMG_FLIP_HORIZONTAL);
			if ($transform->flip->vertical)
				imageflip($image, IMG_FLIP_VERTICAL);

			$mediaSize = imagesize($image);
			$size = object(
				'width', $mediaSize->width - $mediaSize->width * 0.2,
				'height', $mediaSize->height - $mediaSize->height * 0.2
			);

			$oldSize = object('width', $coverSize->width, 'height', $coverSize->height);
			if ($coverSize->width < $size->width)
				$coverSize->width = $size->width;
			$coverSize->height+= $size->height;
			
			$newCover = imagecreatetruecolor($coverSize->width, $coverSize->height);
			imagecopy($newCover, $cover, 0, 0, 0, 0, $oldSize->width, $oldSize->height);
			imagecopyresampled($newCover, $image, 0, $oldSize->height, 0, 0, $size->width, $size->height, $mediaSize->width, $mediaSize->height);
			
			imagedestroy($cover);
			imagedestroy($image);
			
			$cover = $newCover;
			
			$coverMedias[$media->getId()] = $size;

		}

		imagejpeg($cover, $thumbPath . 'cover.jpg');
		imagedestroy($cover);

		$album->setCoverMedias(json_encode($coverMedias));
		$album->setCoverMd5($coverMd5);
		$album->save();
	}
	
	private function generateMediaThumb($thumbName, $media)
	{
		if (!$this->user->isAdmin())
			return;
	
		if (!is_dir($thumbPath = $this->getThumbsPath($media->getAlbum())))
			mkdir($thumbPath);

		if ($media->getType() == 'video' and $thumbName == 'slideshow')
			return $this->generateMediaConversion($media);

		$mediaFile = $this->albumsPath . $media->getPath();
		$imageThumb = $thumbPath . basename($mediaFile) . '.' . $thumbName . '.jpg';
		
		if ($media->getType() == 'video')
			$image = imagecreatefromstring(Ffmpeg::capture($mediaFile, 50));
		elseif ($media->getType() == 'book') {
			$book = new Book($mediaFile);
			$image = imagecreatefromstring($book[1]->content);
		} else
			$image = imagecreatefromstring(file_get_contents($mediaFile));
	
		$thumb = $this->mediaThumbs[$thumbName];
		switch ($thumb['type']) {
			case 'width' :
			case 'height' :

				$resize = object(
					'width', $thumb['type'] == 'width' ? $thumb['size'] : $thumb['size'] * $media->getWidth() / $media->getHeight(),
					'height', $thumb['type'] == 'height' ? $thumb['size'] : $thumb['size'] * $media->getHeight() / $media->getWidth()
				);
				
			break;
			case 'long' :
			
				$resize = object(
					'width', $media->getWidth() > $media->getHeight() ? $thumb['size'] : $thumb['size'] * $media->getWidth() / $media->getHeight(),
					'height', $media->getHeight() > $media->getWidth() ? $thumb['size'] : $thumb['size'] * $media->getHeight() / $media->getWidth()
				);
			
			break;
		}

		$imageResize = imagecreatetruecolor($resize->width, $resize->height);
		imagecopyresampled($imageResize, $image, 0, 0, 0, 0, $resize->width, $resize->height, $media->getWidth(), $media->getHeight());
		imagedestroy($image);

		imagejpeg($imageResize, $imageThumb);
		imagedestroy($imageResize);
		
		return md5_file($imageThumb);
	}
	
	public function generateMediaConversion($media)
	{
		$mediaFile = $this->albumsPath . $media->getPath();
		$thumbPath = $this->getThumbsPath($media->getAlbum());
		$file = $thumbPath . basename($mediaFile) . '.webm';

		if (!is_file($file)) {
		
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

			if ($media->getExifOrientation() == 6)
				$options['video']['-vf'] = '"transpose=1"';
			if ($media->getExifOrientation() == 8)
				$options['video']['-vf'] = '"transpose=2"';

			$fileProgress = $thumbPath . basename($mediaFile) . '.progress.webm';
		
			write('MEDIA CONVERT(0) ' . $media->getPath() . chr(10));
			$lastPercent = 0;
			Ffmpeg::convert($mediaFile, $fileProgress, $options, function($current, $total, $pass) use (&$lastPercent, $media) {
				$current = ($pass - 1) * $total + $current;
				$percent = floor($current * 100 / ($total * 2));
				if ($percent > $lastPercent)
					write('MEDIA CONVERT(' . $percent . ') ' . $media->getPath() . chr(10));
				$lastPercent = $percent;
			});
			rename($fileProgress, $file);
		}
		return md5_file($file);
	}
	
	public function getMediaTransform($media)
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
			
			if (\Database\UserTable::findOneByEmail($email)->getId())
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
			if (!$user->getId())
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
			if ($session->getUser()->getId()) {
				$this->user = $session->getUser();
				return true;
			}
		}
		
		return false;
	}	
	
	public function userAuthentication($post)
	{
		
		$user = \Database\UserTable::findOneByEmailAndIsActive(get($post, k('email')));
		if ($user->getId()) {
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
				if ($user->getId()) {
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
				if (!$user->getId())
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
			if ($user->getId())
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
			
		if (\Database\UserTable::findOneByEmail($email)->getId())
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

		information('User "' . $user->getName() . '" was added successfully, an email with an activation link has been sent to him.', '?user=management');
		
			
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
