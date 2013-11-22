<?php

namespace SG;

class Optimizer {

	private
		$albumsPath,
		$thumbsPath,
		$coverMediasMax = 5
	;

	public function __construct($albumsPath, $thumbsPath)
	{
		$this->thumbsPath = $thumbsPath;
		$this->albumsPath = $albumsPath;
	}
	
	public function getThumbsPath()
	{
		return $this->thumbsPath;
	}
	
	public function getAlbumsPath()
	{
		return $this->albumsPath;
	}
	
	private function getCoverMediasMax()
	{
		return $this->coverMediasMax;
	}
	
	public function analyze(\Database\Media $dbMedia)
	{
		return $this->getSgMedia($dbMedia)->analyze();
	}
	
	public function run(\Database\Media $dbMedia, $callback)
	{
		$sgMedia = $this->getSgMedia($dbMedia);

		$size = $sgMedia->getSize();
		$dbMedia->setWidth($size->width);
		$dbMedia->setHeight($size->height);
		
		$exif = $sgMedia->getExif();
		$dbMedia->setExifOrientation($exif->orientation);
		$dbMedia->setExifDate($exif->date);
		toUtf8($exif->data);
		$dbMedia->setExifData(json_encode($exif->data));
		$dbMedia->save();

		$sgMedia->generate($callback);
	}
	
	public function delete(\Database\Media $dbMedia, $callback)
	{
		$sgMedia = $this->getSgMedia($dbMedia);
		if ($sgMedia)
			$sgMedia->delete($callback);
		$dbMedia->delete();
	}
	
	public function getSgMedia(\Database\Media $dbMedia)
	{
		$mediaClass = '\\SG\\Media\\' . ucfirst($dbMedia->getType());
		if (!class_exists($mediaClass))
			return false;
		return new $mediaClass($this->getAlbumsPath(), $this->getThumbsPath(), $dbMedia);
	}
	
	public function cover(\Database\Album $dbAlbum, $callback)
	{
		$dbMedias = \Database\MediaTable::{'findByAlbum_idOrderPathLimit' . $this->getCoverMediasMax()}($dbAlbum->getId());
		if (count($dbMedias) < $this->getCoverMediasMax())
			foreach ($dbAlbum->getChildren() as $child) {
				if ($childCoverMedias = $child->getCoverMedias() and $childCoverMedias = json_decode($childCoverMedias))
						$dbMedias[] = new \Database\Media(key($childCoverMedias));
				
				if (count($dbMedias) == $this->getCoverMediasMax())
					break;
				
			}

		$coverMediaMd5 = Media::getCoverDimension();
		foreach ($dbMedias as $dbMedia)
			$coverMediaMd5.= '_' . $dbMedia->getMd5();

		$thumbPath = $this->getThumbsPath() . $dbAlbum->getId() . '/';
		$coverFile = $thumbPath . 'cover.jpg';

		$state = 'found';
		if ($dbAlbum->getCoverMediasMd5() != $coverMediaMd5 = md5($coverMediaMd5) or !is_file($coverFile)) {
			
			$state = $dbAlbum->getCoverMediasMd5() ? 'update' : 'new';
			
			// Create album cover sprite
			if (!is_dir($thumbPath))
				mkdir($thumbPath, 0777, true);

			$cover = imagecreatetruecolor(1,1);
			$coverSize = object('width', 0, 'height', 0);
			$coverMedias = array();
			foreach ($dbMedias as $dbMedia) {
				$sgMedia = $this->getSgMedia($dbMedia);
				$coverMedia = $sgMedia->getCoverImage();

				$media = imagesize($coverMedia);

				$oldSize = object('width', $coverSize->width, 'height', $coverSize->height);
				if ($coverSize->width < $media->width)
					$coverSize->width = $media->width;
				$coverSize->height+= $media->height;
			
				$newCover = imagecreatetruecolor($coverSize->width, $coverSize->height);
				imagecopy($newCover, $cover, 0, 0, 0, 0, $oldSize->width, $oldSize->height);
				imagecopyresampled($newCover, $coverMedia, 0, $oldSize->height, 0, 0, $media->width, $media->height, $media->width, $media->height);
			
				imagedestroy($cover);
				imagedestroy($coverMedia);
			
				$cover = $newCover;
			
				$coverMedias[$dbMedia->getId()] = $media;

			}

			imagejpeg($cover, $coverFile);
			imagedestroy($cover);

			$dbAlbum->setCoverMedias(json_encode($coverMedias));
			$dbAlbum->setCoverMediasMd5($coverMediaMd5);
			$dbAlbum->setCoverMd5(md5_file($coverFile));
			$dbAlbum->save();

		}
		
		$callback(object('fs', $dbAlbum->getPath(), 'cover', true, 'state', $state));
		
		if ($state != 'found' and $dbAlbum->getParent())
			$this->cover($dbAlbum->getParent(), $callback);
		
	}
}
