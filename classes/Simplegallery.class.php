<?

include_once(__DIR__ . '/Ffmpeg.class.php');

class Simplegallery
{
	private $lastId, $passwordLostTimeOut = 'PT2H';

	public function __construct($root)
	{
		$this->root = $root;
		$this->pathConfig = $this->root . 'config/';
		$this->pathAlbums = $this->root . 'albums/';
		$this->pathThumbs = $this->root . 'thumbs/';

		$this->config = object();
		$files = getDir($this->pathConfig);
		foreach ($files as $file) {
			$name = basename($file, '.json');
			$this->config->$name = json_decode(file_get_contents($this->pathConfig . $file));
		}
		$this->locale = new Locale(get($this->config->parameters, k('locale')));
		
		$this->dimensions = object(
			'thumb'		, object('size', 75		, 'type', 'sprite'	),
			'preview'	, object('size', 500	, 'type', 'long'	),
			'slideshow'	, object('size', 1000	, 'type', 'long'	)
		);

	}
	
	public function loadAlbums($dir = null)
	{
		if (exists($this, 'albums'))
			return;

		$this->lastId = 0;
		$albums = $this->getAlbums();
		$this->albums = array();
		foreach ($albums as $album) {
			if (!$album->id) {
				file_put_contents($album->path . '.id', $album->id = ++$this->lastId);
				$album->pathThumbs = $this->pathThumbs . $album->id . '/';
			}
			$this->albums[$album->id] = $album;
		}	

		if (!$this->albums and $this->user->admin)
			success(l('album.message.welcome', $this->pathAlbums));
	}
	
	private function getAlbums($dir = null, $depth = 0, $parentId = 0 ,$parentGroups = array())
	{
		if (!$dir)
			$dir = $this->pathAlbums;

		$albumsInDir = getDir($dir);
		
		$albums = array();
		foreach ($albumsInDir as $album) {
			if (!is_dir($dir . $album))
				continue;
			
			$album = object(
				'name'	, $album,
				'id'	, 0,
				'path'	, $dir . $album . '/',
				'groups', array(),
				'parentId', $parentId,
				'parentGroups', $parentGroups,
				'data'	, null,
				'depth'	, $depth,
				'parent', $parentGroups
			);
			
			if (is_file($album->path . '.id')) {
				$album->id = file_get_contents($album->path . '.id');
				$album->pathThumbs = $this->pathThumbs . $album->id . '/';
				if ($album->id > $this->lastId)
					$this->lastId = $album->id;	
				if (is_file($file = $album->pathThumbs . 'data.json'))
					$album->data = json_decode(file_get_contents($file));
			}

			if (!is_object($album->data))
				$album->data = object();

			if (!exists($album->data->name))
				$album->data->name = $album->name;
			if (!exists($album->data->date))
				$album->data->date = object('start', '', 'end', '');
			if (!exists($album->data->description))
				$album->data->description = '';
			
			$groupsAllow = array('admins');
			foreach ($this->config->groups as $group) {
				if ($group == 'admins')
					continue;
			
				$access = get($album->data, k('groups', $group));
				if ($access !== null)
					$album->groups[$group] = $access;
				else {
					$album->groups[$group] = get($parentGroups, k($group), -2);
					if ($album->groups[$group] >= 0)
						$album->groups[$group]-= 2;
				}
				if ($album->groups[$group] == -1 or $album->groups[$group] == 1)
					$groupsAllow[] = $group;
			}
			
			if (array_intersect($this->user->groups, $groupsAllow))
				$albums[] = $album;
				
			$children = $this->getAlbums($album->path, $depth+1, $album->id, $album->groups);
			foreach ($children as $child)
				$albums[] = $child;

		}

		return $this->albumsSort($albums, $parentId);
	}
	
	private function albumsSort($albums, $parentId)
	{
		$result = $index = array();
		$last = false;
		foreach ($albums as $album) {
			if ($album->parentId != $parentId) {
				if ($last)
					$last->children[] = $album;
				else
					$result[] = $album;
				continue;
			}
				
			
			$start = $album->data->date->start;
			$end = $album->data->date->end;
			if (!$start)
				$start = $end;
			if (!$end)
				$end = $start;
			if (!$start)
				$start = $end = '0000-00-00';
			
			$index[$start . $end . '.' . $album->data->name] = object(
				'album', $album,
				'children', array()
			);
			$last = &$index[$start . $end . '.' . $album->data->name];
		}
		
		ksort($index);
		foreach ($index as $data) {
			$result[] = $data->album;
			foreach ($data->children as $album)
				$result[] = $album;
		}
		return $result;
	}
	
	// Return album infos
	public function getAlbum($id)
	{
		$id = (int)$id;

		// Test if album exists
		if (!$album = get($this->albums, k($id)))
			error(l('album.message.error'), '?');

		// Retrieve files of album dir ...
		$album->medias = array();
		$mediasOrder = array();
		$medias = getDir($album->path);
		// ... and generate medias list
		foreach ($medias as $i => $media) {
		
			$mediaType = $this->getMediaType($media);
		
			// Delete hiddens files, json files and dir from list
			if (
				$media[0] == '.' or
				$media == 'data.json' or
				!is_file($mediaFile = $album->path . $media) or
				!$mediaType
			) {
				unset($medias[$i]);
				continue;
			}

			$mediaData = get($album->data, k('medias', $media), object());
			if (!exists($mediaData, 'md5'))
				$mediaData->md5 = '';
			if (!exists($mediaData, 'order'))
				$mediaData->order = 0;
			if (!exists($mediaData, 'width'))
				$mediaData->width = 0;
			if (!exists($mediaData, 'height'))
				$mediaData->height = 0;
			if (!exists($mediaData, 'orientation'))
				$mediaData->orientation = 1;
			if (!exists($mediaData, 'rotation'))
				$mediaData->rotation = 0;
			if (!exists($mediaData, 'flip'))
				$mediaData->flip = '';
			
			if (!exists($mediasOrder, $mediaData->order))
				$mediasOrder[$mediaData->order] = array();
			$mediasOrder[$mediaData->order][] = object(
				'name', $media,
				'file', $mediaFile,
				'type', $mediaType,
				'data', $mediaData
			);
		}
		
		ksort($mediasOrder);
		foreach ($mediasOrder as $medias)
			foreach ($medias as $media)
				$album->medias[$media->name] = $media;

		return $album;
	}
	
	public function albumUpdate($id, $data)
	{
		$album = $this->getAlbum($id);

		if ($data['name'] == '' or $album->name == $data['name'])
			unset($album->data->name);
		else
			$album->data->name = $data['name'];
		$album->data->date = object(
			'start', $data['date-start'],
			'end', $data['date-end']
		);
		$album->data->description = $data['description'];
		
		$reorder = $data['reorder'];
		if ($reorder) {
			$reorder = json_decode($reorder);
			foreach ($reorder as $order => $media)
				if (exists($album->data->medias, $media))
					$album->data->medias->$media->order = $order;
		}
		
		$access = $data['access'];
		foreach ($access as $group => $value) {
			if ($value == 0 or $value == 1)
				continue;
			unset($access[$group]);
		}
		$album->data->groups = $access;
		
		$this->albumSaveConfig($album->id);
		
		success(l('album.message.update-success'), '?album&id=' . $album->id);
	}
	
	public function albumSaveConfig($id)
	{
		$album = $this->getAlbum($id);
		if (!is_dir($dir = $this->pathThumbs . $album->id))
			mkdir($dir, 0777, true); 
		file_put_contents($dir . '/data.json', json_encode($album->data));
	}
	
	// Return list of medias who need update and files who are not necessary
	public function albumCheck($id)
	{
	
		// Retrieve album
		$album = $this->getAlbum($id);

		// Retrieve list of files in thumb dir (contains delete list at end of this function)
		
		if (is_dir($album->pathThumbs))
			$delete = array_fill_keys(array_values(getDir($album->pathThumbs)), true);
		else
			$delete = array();

		if (exists($delete, 'data.json'))
			unset($delete['data.json']);

		// Retrieve list of medias files who need update
		$update = array();
		foreach ($album->medias as $media) {

			$needUpdate = get($media->data, k('md5')) != md5_file($media->file);
			$videosDeleted = array();

			foreach ($this->dimensions as $dimension) {
			
				if ($dimension->type == 'sprite') {
			
					$thumb = $dimension->size . '-' . $dimension->type . '.jpg';
					
					// Format delete sprite data
					if (exists($delete, $thumb) and !is_object($delete[$thumb])) {
						$delete[$thumb] = object(
							'dimension'	, $dimension,
							'medias'	, get($album->data, k('thumbs', $thumb, 'index'), array())
						);
					}

					// If media need update or does not exists in sprite data
					if (!$needUpdate)
						$needUpdate = !in_array($media->name, get($delete, k($thumb, 'medias'), array()));

					// Delete media from json sprite file list
					if (false !== $index = array_search($media->name, get($delete, k($thumb, 'medias'), array())))
						unset($delete[$thumb]->medias[$index]);

				} else {

					if ($media->type == 'image') {
						$thumb = $media->name . '.' . $dimension->size . '-' . $dimension->type . '.jpg';
						if (!$needUpdate)
							$needUpdate = !exists($delete, $thumb);
					} else {
						$thumb = $media->name . '.webm';
						if ('webm' == strtolower(pathinfo($media->name, PATHINFO_EXTENSION)))
							$needUpdate = false;
						else {
							$dimension = 'convert';
							if (!$needUpdate and !exists($videosDeleted, $thumb))
								$needUpdate = !exists($delete, $thumb);
						}
					}
				
				}

				if (!get($delete, k($thumb, 'medias'))) {
					unset($delete[$thumb]);
					if ($media->type == 'video')
						$videosDeleted[$thumb] = true;
				}

				if (!$needUpdate)
					continue;

				if (!exists($update, $media->name))
					$update[$media->name] = object('media', $media, 'dimensions', array());
				if (!in_array($dimension, $update[$media->name]->dimensions))
					$update[$media->name]->dimensions[] = $dimension;

			}
		}

		return object(
			'album'	, $album,
			'delete', $delete,
			'update', $update
		);

	}
	
	// Generate missing thumbs for the album $id
	public function albumGenerate($result)
	{
		if (!is_object($result))
			// Retrieve udpate and delete list
			$result = $this->albumCheck($result);

		$album = $result->album;

		write('<pre>');
		write('<h3>' . l('album.generation._', $album->name) . '</h3>');

		// Create thumb dir
		if (!is_dir($album->pathThumbs))
			mkdir($album->pathThumbs, 0777, true);

		// Create medias data and thumbs
		if (!exists($album->data, 'medias'))
			$album->data->medias = object();
		if (!exists($album->data, 'thumbs'))
			$album->data->thumbs = object();

		// Delete files not required
		write('<h4>' . l('album.generation.delete') . '</h4>');
		if (!$result->delete)
			write(l('album.generation.delete-nothing'));
		foreach ($result->delete as $file => $data) {
			
			if (is_object($data)) {
				foreach ($data->medias as $media) {			
					$this->mediaDelete($album->id, $media, $data->dimension);
					write(l('album.generation.delete-sprite', $media, $file), true);
				}
			} else {
				// Delete file
				unlink($album->pathThumbs . $file);
				write(l('album.generation.delete-file', $file), true);
			}
			
		}

		// Update files
		write('<h4>' . l('album.generation.update') . '</h4>');
		if (!$result->update)
			write(l('album.generation.update-nothing'));
		else {
			// Retrieve total medias to update
			$progress = object(
				'total'	, 0,
				'now'	, 0,
				'percent', 0
			);
			foreach ($result->update as $file => $data)
				$progress->total+= count($data->dimensions);
		}

		foreach ($result->update as $data) {
			$media = $data->media;

			write(l('album.generation.update-start', $media->name), true);

			// Load original media
			$imgMedia = imagecreatefromstring($media->type == 'video' ?
				$this->videoTakeCapture($media->file) :
				file_get_contents($media->file)
			);
			$geometry = imagesize($imgMedia);
			$md5 = md5_file($media->file);

			// Save media data
			$album->data->medias->{$media->name} = $media->data = object(
				'md5'	, $md5,
				'width'	, $geometry->width,
				'height', $geometry->height,
				'orientation', exif_imagetype($media->file) === false ? 1 : (int)geta(exif_read_data($media->file), k('Orientation'), 1),
				'rotation', get($media, k('data', 'rotation'), 0),
				'flip', get($media, k('data', 'flip'), '')
			);

			foreach ($data->dimensions as $dimension) {
				
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

					$file = $media->name . '.webm';
					$fileThumb = $album->pathThumbs . $file;
					write(chr(9) . chr(9) . l('album.generation.update-video') . ' :');
					$lastPercent = -1;
					Ffmpeg::convert($media->file, $fileThumb, $options, function($current, $total, $pass) use (&$lastPercent) {
						$current = ($pass - 1) * $total + $current;
						$percent = floor($current * 100 / ($total * 2));
						if ($percent > $lastPercent)
							write(' ' . $percent . '%');
						$lastPercent = $percent;
					});
					write('', true);
					
					// Create thumbs entry for album data
					if (!exists($album->data->thumbs, $file))
						$album->data->thumbs->$file = object('md5', '');
					
				} else {

					// Create or load thumb image
					$file = ($dimension->type == 'sprite' ? '' : $media->name . '.') . $dimension->size . '-' . $dimension->type . '.jpg';
					$fileThumb = $album->pathThumbs . $file;
					$new = true;
					if ($dimension->type == 'sprite' and !$new = !is_file($fileThumb))
						// Load existant sprite
						$imgThumb = imagecreatefromstring(file_get_contents($fileThumb));

					// Prepare vars for resize and crop image
					$dstY = $lagX = $lagY = 0;
					if ($dimension->type == 'sprite') {
				
						if ($geometry->width >= $geometry->height) {
							$srcWidth = $geometry->height;
							$srcHeight = $geometry->height;
							$lagX = ($geometry->width - $geometry->height)/2;
						} else {
							$srcWidth = $geometry->width;
							$srcHeight = $geometry->width;
							$lagY = ($geometry->height - $geometry->width)/2;
						}
						$dstWidth = $dstHeight = $dimension->size;
					
						if (!exists($album->data->thumbs->$file, 'index'))
							$album->data->thumbs->$file->index = array();
					
						// Retrieve index for sprite
						$index = $new ? 0 : array_search($media->name, $album->data->thumbs->$file->index);
						//Insert
						if ($index === false) {
							$spriteHeight = imagesy($imgThumb);
							$index = $spriteHeight / $dimension->size;
								$imgNewThumb = imagecreatetruecolor($dimension->size, $spriteHeight + $dimension->size);
							imagecopy($imgNewThumb, $imgThumb, 0, 0, 0, 0, $dimension->size, $spriteHeight);
							imagedestroy($imgThumb);
							$imgThumb = $imgNewThumb;
						}
						// Save index for thumb sprite
						$album->data->thumbs->$file->index[$index] = $media->name;

						$dstY = $index * $dimension->size;
					} else {
						$srcWidth = $geometry->width;
						$srcHeight = $geometry->height;
						if ($geometry->width >= $geometry->height) {
							$dstWidth = $dimension->size;
							$dstHeight = floor($dimension->size * $geometry->height / $geometry->width);
						} else {
							$dstWidth = floor($dimension->size * $geometry->width / $geometry->height);
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
				$album->data->thumbs->$file->md5 = md5_file($fileThumb);
			
				write(chr(9) . str_pad($progress->percent, 3, ' ', STR_PAD_LEFT) . ' % - ' . str_pad($progress->now, strlen($progress->total), ' ', STR_PAD_LEFT) . ' / ' . $progress->total . ' - ');
				if ($dimension == 'convert' or $dimension->type != 'sprite')
					write(l('album.generation.update-file', $file), true);
				else
					write(l('album.generation.update-sprite', $file), true);

			}
			
			imagedestroy($imgMedia);
			$this->albumSaveConfig($album->id);
		}
		
		write('</pre>');

		success(l('album.message.generate-success'), '?album&id=' . $album->id);
	}
	
	public function albumGenerateData($id)
	{
		// Retrieve album
		$album = $this->getAlbum($id);

		if (!exists($album->data, 'thumbs'))
			$album->data->thumbs = object();

		foreach ($album->medias as $media) {

			foreach ($this->dimensions as $dimension) {
			
				if ($dimension->type == 'sprite')
					// Can't regenerate index of sprite
					continue;

				if ($media->type == 'image')
					$thumb = $media->name . '.' . $dimension->size . '-' . $dimension->type . '.jpg';
				else {
					if ('webm' == strtolower(pathinfo($media->name, PATHINFO_EXTENSION)))
						// No need thumb file
						continue; 
						
					$thumb = $media->name . '.webm';
				}

				if (!is_file($file = $album->pathThumbs . $thumb))
					continue;
					
					
					
				$album->data->thumbs->$thumb = object('md5', md5_file($file));

			}
		}

		$this->albumSaveConfig($album->id);
		
		success(l('album.message.generate-data-success'), '?album&id=' . $album->id);
	}
	
	public function getMedia($albumId, $media = null, $dim = null)
	{
		$this->loadAlbums();
		
		$albumId = (int)$albumId;
		if (!$album = get($this->albums, k($albumId)))
			die(l('album.media.not-found'));
			
		if ($media and !exists($album, 'data', 'medias', $media))
			die(l('album.media.not-found'));

		if ($dim and !$dim = get($this->dimensions, k($dim)))
			die(l('album.media.not-found'));
			
		$mediaType = $this->getMediaType($media);
		if ($mediaType == 'video' and 'webm' == strtolower(pathinfo($media, PATHINFO_EXTENSION)))
			$dim = null;
			
		if ($dim) {
			if ($dim->type == 'sprite')
				$file = $dim->size . '-' . $dim->type . '.jpg';
			else {
				if ($mediaType == 'image')
					$file = $media . '.' . $dim->size . '-' . $dim->type . '.jpg';
				else
					$file = $media . '.webm';
			}
			$md5 = get($album, k('data', 'thumbs', $file, 'md5'));
			$file = $album->pathThumbs . $file;
		} else {
			$md5 = get($album, k('data', 'medias', $media, 'data', 'md5'));
			$file = $album->path . $media;
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
	
	private function mediaDelete($albumId, $media, $dimension = null)
	{
	
		$album = $this->getAlbum($albumId);
		
		if ($dimension)
			$dimensions = array($dimension);
		else {
			if (!$media = get($album->medias, k($media)))
				die(l('album.media.not-found'));
			unlink($media->file);
			$media = $media->name;
			$dimensions = $this->dimensions;
		}
			
		foreach ($dimensions as $dimension) {
			
			if ($dimension->type == 'sprite') {

				$thumb = $dimension->size . '-' . $dimension->type . '.jpg';
				
				// Retrieve index of thumbnail in sprite
				$thumbs = get($album, k('data', 'thumbs', $thumb, 'index'), array());
				if (false === $index = array_search($media, $thumbs))
					continue;

				// Load sprite image
				$fileThumb = $album->pathThumbs . $thumb;
				$imgSprite = imagecreatefromstring(file_get_contents($fileThumb));
				$height = imagesy($imgSprite);
				$imgNewSprite = imagecreatetruecolor($dimension->size, $height - $dimension->size);
			
				// Retrieve part of sprite before thumbnail to delete
				$heightSrc = $index * $dimension->size - 0;
				imagecopy($imgNewSprite, $imgSprite, 0, 0, 0, 0, $dimension->size, $heightSrc);

				// Retrieve part of sprite after thumbnail to delete
				$lastSrc = $heightSrc + $dimension->size;
				imagecopy($imgNewSprite, $imgSprite, 0, $heightSrc, 0, $lastSrc, $dimension->size, $height - $lastSrc);
		
				// Save sprite
				imagejpeg($imgNewSprite, $fileThumb);
				imagedestroy($imgNewSprite);
				imagedestroy($imgSprite);				
			
				// Delete sprite index
				unset($album->data->thumbs->{$thumb}->index[$index]);
				$album->data->thumbs->{$thumb}->index = array_values($album->data->thumbs->{$thumb}->index);
				$this->albumSaveConfig($album->id);
			} else {
				$fileThumb = $album->pathThumbs . $media . '.' . $dimension->size . '-' . $dimension->type . '.jpg';

				if (inDir($album->pathThumbs, $fileThumb, true))
					unlink($fileThumb);
			}
		}
	
	}
	
	private function videoTakeCapture($video, $file = null)
	{
	
		$result = shell_exec('ffmpeg -i ' . $this->escapefile($video) . ' 2>&1');

		preg_match('/Duration: ([0-9]{2}):([0-9]{2}):([0-9]{2}\\.[0-9]{2})/', $result, $match);

		$time = $match[1] * 3600 + $match[2] * 60 + $match[3];
		
		$hours = floor($time / 3600);
		$time-= $hours * 3600;
		$minutes = floor($time / 60);
		$time-= $minutes * 60;
		$time = date('H:i:s', mktime($hours, $minutes, $time));
				
		$image = $file ? $file : sys_get_temp_dir() . '/simplegallery_' . uniqid();
		shell_exec('ffmpeg -ss ' . $time . ' -t 1 -i ' . $this->escapefile($video) . ' -f mjpeg ' . $this->escapefile($image));
		
		$capture = file_get_contents($image);
		
		if (!$file)
			unlink($image);
			
		return $capture;
	
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
		elseif (in_array($ext, array('webm', 'mp4', 'mts', 'mpg', 'avi')))
			return 'video';

		return false;
	}
	
	public function getUser()
	{

		if (!$mail = get($_SESSION, k('user')))
			return false;

		$this->user = get($this->config->users, k($mail));
		if (!exists($this->user, 'groups'))
			$this->user->groups = array();
			
		$this->user->admin = in_array('admins', $this->user->groups);
			
		return true;
	}
	
	public function userLogin($mail, $password)
	{
		$mail = trim(strtolower($mail));
		
		if (!$this->user = get($this->config->users, k($mail)))
			error(l('user.message.login-error'), '?');
			
		if ($this->user->active != 1)
			error(l('user.message.login-error'), '?');

		if ($this->user->password != crypt($password, $this->user->password))
			error(l('user.message.login-error'), '?');

		$_SESSION['user'] = $mail;
		
		success(l('user.message.login-success', $this->user->name), '?');
	}
	
	public function userLogout()
	{
		unset($_SESSION['user']);
		
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
	
		if (exists($this->config->users, $mail))
			error(l('user.message.mail-update-error', $mail), $errorLink);

		if (!is_null($password) and $password !== $passwordCheck)
			error(l('user.message.password-update-error'), $errorLink);

		$user = object();
		$user->name = $name;
		$user->mail = $mail;
		$user->password = $password ? $this->userCryptPassword($password) : null;
		$user->active = randomString(12);
		$user->groups = array();
		if (!(bool)(array)$this->config->users)
			$user->groups[] = 'admins';

		if (!is_object($this->config->users))
			$this->config->users = object();

		$this->config->users->$mail = $user;
		file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));

		$code = $password ? 'rcode' : 'rpcode';
		$link = httpUrl() . '?user=registration&' . $code . '=' . $user->active;
		$this->mail(
			$mail,
			l('mail.registration.object', $this->config->parameters->name),
			l('mail.registration.message', $user->name) . '<a href="' . $link . '">' . $link . '</a>' . l('mail.signature', $this->config->parameters->name)
		);

		foreach ($this->config->users as $admin) {

			if (!in_array('admins', $admin->groups))
				continue;

			$this->mail(
				$admin->mail,
				l('mail.registration-admin.object', $this->config->parameters->name, $user->name, $user->mail),
				l('mail.registration-admin.message', $admin->name) . l('mail.signature', $this->config->parameters->name)
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
	
		foreach ($this->config->users as &$user) {
			if ((string)$user->active != (string)$code)
				continue;

			// Check password
			if (!is_null($password)) {
				if (trim($password) == '')
					error(l('user.message.password-update-error-empty'), '?user=registration&rpcode=' . $code);

				if ($password !== $passwordCheck)
					error(l('user.message.password-update-error'), '?user=registration&rpcode=' . $code);

				$user->password = $this->userCryptPassword($password);					
			}

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
		
		$mail = $this->user->mail;
		$user = $this->config->users->$mail;
		$user->name = $data['name'];
		
		if ($mailUpdate = $user->mail != $data['mail']) {
			$user->mailUpdate = $data['mail'];
			$user->mailUpdateCode = randomString(12);
			
			$link = httpUrl() . '?user=profil&mcode=' . $user->mailUpdateCode;
			$this->mail(
				$user->mailUpdate,
				l('mail.mail-update.object', $this->config->parameters->name),
				l('mail.mail-update.message', $user->name) . '<a href="' . $link . '">' . $link . '</a>' . l('mail.signature', $this->config->parameters->name)
			);
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
	
	public function userDelete($mail)
	{
		if (!$this->config->users->$mail)
			error(l('user.message.no-found'), '?admin');
			
		unset($this->config->users->$mail);
		file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));
		
		success(l('admin.message.user-delete-success'), '?admin');
	}
	
	public function adminUpdate($data)
	{
		$this->config->parameters->name = $data['name'];
		$this->config->parameters->locale = $data['locale'];
		$this->config->parameters->{'registration-disable'} = get($data, k('registration-disable'));
		file_put_contents($this->pathConfig . 'parameters.json', json_encode($this->config->parameters));
	
		$this->config->groups = preg_split('/\W+/', $data['groups']);
		file_put_contents($this->pathConfig . 'groups.json', json_encode($this->config->groups));
		
		foreach ($data['usersGroups'] as $user => $groups)
			$this->config->users->$user->groups = array_keys($groups);
		file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));
		
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

	}
	
	public function mediaUpdate($albumId, $media, $update)
	{
		$this->loadAlbums();

		$album = $this->getAlbum($albumId);
		if (!$media = get($album->medias, k($media)))
			die(l('album.media.not-found'));
			
		switch ($update) {
			case 'rotateLeft' :
			case 'rotateRight' :
				$direction = $update == 'rotateLeft' ? -1 : 1;
				
				$media->data->rotation+= $direction * 90;
				if ($media->data->rotation < 0)
					$media->data->rotation = 270;
				elseif ($media->data->rotation > 270)
					$media->data->rotation = 0;
				$this->albumSaveConfig($album->id);
			break;
			case 'flipVertical' :
			case 'flipHorizontal' :
				$orientation = $update == 'flipVertical' ? 'vertical' : 'horizontal';
			
				$media->data->flip = explode(' ', $media->data->flip);
				 if (false === $index = in_array($orientation, $media->data->flip))
				 	$media->data->flip[] = $orientation;
				 else
				 	unset($media->data->flip[$index]);
				 $media->data->flip = implode(' ', $media->data->flip);
				 $this->albumSaveConfig($album->id);
			break;
			case 'delete' :
				$this->mediaDelete($album->id, $media->name);
			break;
		}
		
		exit(json_encode(array('success' => 'Update success')));
	}
	
	public function mediaDownload($albumId, $media)
	{
		$this->loadAlbums();
		$album = $this->getAlbum($albumId);
		if (!$media = get($album->medias, k($media)))
			die(l('album.media.not-found'));

		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Last-Modified: '.gmdate ('D, d M Y H:i:s', filemtime ($media->file)).' GMT');
		header('Cache-Control: private',false);
		header('Content-Disposition: attachment; filename="'.basename($media->file).'"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: '.filesize($media->file));
		header('Connection: close');
		$this->getMedia($albumId, $media->name);

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
	
	public function userPasswordLost($mail)
	{
		if (!$user = get($this->config->users, k($mail)))
			error(l('user.message.not-found'), '?user=lost');
			
		$user->passwordCode = randomString(12);
		$user->passwordCodeTime = time();
		file_put_contents($this->pathConfig . 'users.json', json_encode($this->config->users));
		
		$link = httpUrl() . '?user=lost&pcode=' . $user->passwordCode;

		$this->mail(
			$user->mail,
			l('mail.password-reset.object', $this->config->parameters->name),
			l('mail.password-reset.message', $user->name) . '<a href="' . $link . '">' . $link . '</a>' . l('mail.password-reset.message2') . l('mail.signature', $this->config->parameters->name)
		);

		success(l('user.message.password-reset-link-sent'), '?');
		
		
	}
	
	public function userPasswordReset($code, $password, $passwordCheck)
	{
		if (strlen($code) != 12)
			error(l('user.message.password-reset-invalid-code'), '?user=lost');
	
		$valid = false;
		foreach ($this->config->users as &$user) {
			if ((string)get($user, k('passwordCode')) != (string)$code)
				continue;

			$interval = new DateInterval($this->passwordLostTimeOut);
			$passwordTime = new DateTime();
			$passwordTime->setTimestamp(get($user, k('passwordCodeTime')));
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
