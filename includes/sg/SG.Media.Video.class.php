<?php

namespace SG\Media;

class Video extends \SG\Media {

	public function analyze()
	{
		$albumThumbsPath = $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/';
		$basename = basename($this->getDbMedia()->getPath());
		
		$result = array();
		foreach ($this->getDimensions() as $code => $size) {

			$generateThumb = true;

			if ($code == 'brick')
				$fsThumb = $albumThumbsPath . $this->getDbMedia()->getId() . '.' . $code . '.jpg';
			elseif ($code == 'slideshow') {
				if (\SG\Fs::getMediaExtension($basename) == 'webm') {
					$fsThumb = $this->getAlbumsPath() . $this->getDbMedia()->getPath();
					$generateThumb = false;
				} else
					$fsThumb = $albumThumbsPath . $this->getDbMedia()->getId() . '.' . $code . '.webm';
			}

			$state = 'found';
			if ($generateThumb) {

				$fsThumbIntegrate = $albumThumbsPath . $basename . '.' . $code . '.';
				if ($code == 'brick')
					$fsThumbIntegrate.= 'jpg';
				elseif ($code == 'slideshow')
					$fsThumbIntegrate.= 'webm';

				// Thumb integration
				if (is_file($fsThumbIntegrate)) {
					rename($fsThumbIntegrate, $fsThumb);
					$this->getDbMedia()->{'setThumb' . ucfirst($code) . 'Md5'}('');
				}

				if (is_file($fsThumb)) {
					$md5 = md5_file($fsThumb);
					$dbMd5 = $this->getDbMedia()->{'getThumb' . ucfirst($code) . 'Md5'}();
					if ($md5 != $dbMd5) {
						$state = 'update';
						$update = array();
						if (!$dbMd5)
							$update['integrate'] = true;
					}
				
				} else
					$state = 'new';
			}
			
			$media = object(
				'state', $state,
				'code', $code,
				'size', $size,
				'fsMedia', $this->getAlbumsPath() . $this->getDbMedia()->getPath(),
				'fsThumb', $fsThumb
			);
			if ($state == 'update')
				$media->update = $update;

			$result[$code] = $media;
		}
		
		return $result;
		
	}
	
	public function getSize()
	{
		return \Ffmpeg::getSize($this->getAlbumsPath() . $this->getDbMedia()->getPath());
	}

	public function getExif()
	{
	
		
		$orientation = 1;
		$vertical = substr($this->getDbMedia()->getPath(), 0, strlen($this->getDbMedia()->getPath()) - strlen(pathinfo($this->getDbMedia()->getPath(), PATHINFO_EXTENSION)) - 1);
		$vertical = strtolower(pathinfo($vertical, PATHINFO_EXTENSION));
		if ($vertical == 'verticalleft')
			$orientation = 6;
		elseif ($vertical == 'verticalright')
			$orientation = 8;

		$date = '';		

		return object(
			'orientation', $orientation,
			'date', $date,
			'data', array()
		);
	
	}

	public function generate($callback)
	{
		$analyze = $this->analyze();
	
		$albumThumbsPath = $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/';

		if (!is_dir($albumThumbsPath))
			mkdir($albumThumbsPath);

		foreach ($analyze as $result) {

			if ($result->state != 'found') {
				if ($result->state == 'new' or !get($result, k('integrate')))
					$this->{'generate' . ucfirst($result->code) . 'Thumb'}($result->fsMedia, $result->fsThumb, $callback);
			
				$this->getDbMedia()->{'setThumb' . ucfirst($result->code) . 'Md5'}(md5_file($result->fsThumb));
			}
			
			$media = object(
				'state', $result->state,
				'code', $result->code,
				'fs', $this->getDbMedia()->getPath()
			);
			if ($result->state == 'update')
				$media->update = $result->update;

			$this->getDbMedia()->save();

			$callback($media);
		}
	}
	
	public function generateBrickThumb($fsMedia, $fsThumb, $callback = null)
	{

		$image = imagecreatefromstring(\Ffmpeg::capture($fsMedia, 50));
		
		$this->generateThumbFromImage($this->getDimension('brick'), $image, $fsThumb);
	
		imagedestroy($image);
		
	}
	
	public function generateSlideshowThumb($fsMedia, $fsThumb, $callback = null)
	{
	
		if ($this->getDbMedia()->getExifOrientation() == 6)
			$options['video']['-vf'] = '"transpose=1"';
		if ($this->getDbMedia()->getExifOrientation() == 8)
			$options['video']['-vf'] = '"transpose=2"';

		$fileProgress = $fsThumb . '.progress.webm';
	
		$convert = object(
			'state', 'convert',
			'code', 'slideshow',
			'fs', $this->getDbMedia()->getPath(),
			'progress', 0
		);

		$callback($convert);
		try {
			\Ffmpeg::convertToWebm($fsMedia, $fileProgress, function($current, $total, $pass) use ($callback, $convert) {
				$percent = floor($current * 100 / $total);
				if ($percent > $convert->progress)
					$callback($convert);
				$convert->progress = $percent;
			});
			rename($fileProgress, $fsThumb);
		} catch (Exception $exception) {
			$callback($exception->getMessage());
		}
		
	
	}
	
	public function delete($callback)
	{
		
		$albumThumbsPath = $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/';
		$basename = basename($this->getDbMedia()->getPath());
		
		foreach ($this->getDimensions() as $code => $size)

			$ext = $code == 'brick' ? 'jpg' : 'webm';

			if (is_file($fsThumb = $albumThumbsPath . $basename . '.' . $code . '.' . $ext)) {
				unlink($fsThumb);
				
				$media = object(
					'state', 'delete',
					'code', $code,
					'fs', $this->getDbMedia()->getPath()
				);
				$callback($media);
			}
		
	}
	
	public function getCoverImage()
	{
		$fsMedia = $this->getAlbumsPath() . $this->getDbMedia()->getPath();

		$image = imagecreatefromstring(\Ffmpeg::capture($fsMedia, 50));
		
		$cover = $this->getCoverFromImage($image);
	
		imagedestroy($image);
		
		return $cover;
		
	}

	public function getThumbSlideshow($data = null)
	{
		if (\SG\Fs::getMediaExtension($this->getDbMedia()->getPath()) == 'webm')
			return $this->getAlbumsPath() . $this->getDbMedia()->getAlbum()->getPath() . $this->getDbMedia()->getPath();
		else
			return $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/' . $this->getDbMedia()->getId() . '.slideshow.webm';
	}

}
