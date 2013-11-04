<?php

namespace SG\Media;

class Image extends \SG\Media {

	public function analyze()
	{
		$albumThumbsPath = $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/';
		$basename = basename($this->getDbMedia()->getPath());
		
		$result = array();
		foreach ($this->getDimensions() as $code => $size) {

			$fsThumb = $albumThumbsPath . $this->getDbMedia()->getId() . '.' . $code . '.jpg';

			$state = 'found';
			
			// Thumb integration
			if (is_file($fsThumbIntegrate = $albumThumbsPath . $basename . '.' . $code . '.jpg')) {
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
		return imagesize($this->getAlbumsPath() . $this->getDbMedia()->getPath());
	}

	public function getExif()
	{
		if ($exif = exif_imagetype($this->getAlbumsPath() . $this->getDbMedia()->getPath()) !== false)
			$exif = @exif_read_data($this->getAlbumsPath() . $this->getDbMedia()->getPath());
		if (!$exif)
			$exif = array();
		
		$orientation = (int)geta($exif, k('Orientation'), 1);
		
		$date = geta($exif, k('DateTime'), '');
		if (preg_match('/([0-9]{4}).([0-9]{2}).([0-9]{2}).([0-9]{2}).([0-9]{2}).?([0-9]*)/', $date, $match))
			$date = $match[1] . '-' . $match[2] . '-' . $match[3] . ' ' . $match[4] . ':' . $match[5] . ':' . ($match[6] ? $match[6] : '00');
			
		return object(
			'orientation', $orientation,
			'date', $date,
			'data', $exif
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
			
				if ($result->state == 'new')
					// Set id for save files
					$this->getDbMedia()->save();
				if ($result->state == 'new' or !get($result, k('integrate')))
					$this->{'generate' . ucfirst($result->code) . 'Thumb'}($result->fsMedia, $result->fsThumb);
			
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

		$image = imagecreatefromstring(file_get_contents($fsMedia));
		
		$this->generateThumbFromImage($this->getDimension('brick'), $image, $fsThumb);
	
		imagedestroy($image);
		
	}
	
	public function generateSlideshowThumb($fsMedia, $fsThumb, $callback = null)
	{
	
		$image = imagecreatefromstring(file_get_contents($fsMedia));
	
		$this->generateThumbFromImage($this->getDimension('slideshow'), $image, $fsThumb);

		imagedestroy($image);
	
	}
	
	public function delete($callback)
	{
		
		$albumThumbsPath = $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/';
		$basename = basename($this->getDbMedia()->getPath());
		
		foreach ($this->getDimensions() as $code => $size)

			if (is_file($fsThumb = $albumThumbsPath . $basename . '.' . $code . '.jpg')) {
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

		$image = imagecreatefromstring(file_get_contents($fsMedia));
		
		$cover = $this->getCoverFromImage($image);
	
		imagedestroy($image);
		
		return $cover;
		
	}
	
	public function getThumbSlideshow($data = null)
	{
		return $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/' . $this->getDbMedia()->getId() . '.slideshow.jpg';
	}

}
