<?php

namespace SG;

class Synchronizer {

	private
		$albumsPath,
		$thumbsPath,
		$fs,
		$optimizer
	;

	public function __construct($albumsPath, $thumbsPath)
	{
		$this->albumsPath = $albumsPath;
		$this->thumbsPath = $thumbsPath;
		$this->fs = new Fs();
		$this->optimizer = new Optimizer($this->getAlbumsPath(), $this->getThumbsPath());
	}

	public function getAlbumsPath()
	{
		return $this->albumsPath;
	}

	public function getThumbsPath()
	{
		return $this->thumbsPath;
	}

	public function albums($callback, $path = '', &$dbAlbums = null, $parent = null)
	{
		if ($dbAlbums === null)
			$dbAlbums = \Database\AlbumTable::findAll();

		$realpath = $this->getAlbumsPath() . $path;
		$inode = fileinode($realpath);

		if (!$dbAlbum = $dbAlbums->findOneByPath($realpath))
			$dbAlbum = $dbAlbums->findOneByInode($inode);

		$state = 'found';

		if (!$dbAlbum) {
			$state = 'new';
			
			$dbAlbum = new \Database\Album();

		} else {
			
			$dbAlbums->removeOneById($dbAlbum->getId());
		
			$update = array();
			if ($path != $dbAlbum->getPath())
				$update['oldPath'] = $dbAlbum->getPath();
			if ($inode != $dbAlbum->getInode())
				$update['oldInode'] = $dbAlbum->getInode();
			if ($update)
				$state = 'update';
		}

		if ($path == '')
			$dbAlbum->setParent_id(-1);
		else
			$dbAlbum->setParent($parent);
		$dbAlbum->setInode($inode);
		$dbAlbum->setPath($path);
		$dbAlbum->save();
		$album = object(
			'path', $path,
			'state', $state
		);
		if ($state == 'update')
			$album->update = $update;
		
		call_user_func($callback, $album);
	
		$fsChildren = $this->fs->getDir($realpath, null, Fs::ONLY_DIRS);
		foreach ($fsChildren as $fsChild)
			$this->albums($callback, $path . $fsChild . '/', $dbAlbums, $dbAlbum);
			
		if ($path == '') {
			$dbAlbums->delete();
			foreach ($dbAlbums as $dbAlbum)
				call_user_func($callback, object(
					'path', $dbAlbum->getPath(),
					'state', 'delete'
				));
		}
			
				
	}

	public function analyzeMedias(\Database\Album $album, $callback)
	{
		
		$fsMedias = $this->fs->getDir($this->getAlbumsPath() . $album->getPath(), $this->fs->getMediasMask());
		if (is_dir($this->getThumbsPath() . $album->getId()))
			$fsThumbs = $this->fs->getDir($this->getThumbsPath() . $album->getId());
		else
			$fsThumbs = array();

		if (false !== $index = array_search('cover.jpg', $fsThumbs))
			unset($fsThumbs[$index]);

		foreach ($fsMedias as $fsMedia) {
			resetTimeout();
			$media = $this->analyzeMedia($album, $album->getPath() . $fsMedia);
			foreach ($media->thumbs as $thumb) {
				if (false !== $index = array_search(basename($thumb->fsThumb), $fsThumbs))
					unset($fsThumbs[$index]);
				
				if (exists($thumb->fsKeep))
					foreach ($thumb->fsKeep as $fsKeep)
						if (false !== $index = array_search(basename($fsKeep), $fsThumbs))
							unset($fsThumbs[$index]);
			}

			unset($media->thumbs);
			unset($media->db);
			call_user_func($callback, $media);
		}
		foreach ($album->getMediaCollection() as $dbMedia)
			call_user_func($callback, object(
				'path', $dbMedia->getPath(),
				'state', 'delete',
				'album', $album->getId()
			));
			
		foreach($fsThumbs as $fsThumb)
			unlink($this->getThumbsPath() . $album->getId() . '/' . $fsThumb);
	}
	
	public function analyzeMedia($album, $fsMedia)
	{
		
		$searchDbMedia = new \Database\Media();
		$this->populateDbMedia($searchDbMedia, $fsMedia, $album);
	
		if (!$dbMedia = $album->getMediaCollection()->findOneByPath($searchDbMedia->getPath()))
			if (!$dbMedia = $album->getMediaCollection()->findOneByInode($searchDbMedia->getInode()))
				$dbMedia = $album->getMediaCollection()->findOneByMd5($searchDbMedia->getMd5());
				
		if (!$this->fs->inDir($this->getAlbumsPath(), $this->getAlbumsPath() . $fsMedia))
			$state = 'delete';
		else
			$state = 'found';

		if (!$dbMedia and $state == 'found') {
		
			$state = 'new';
			$dbMedia = $searchDbMedia;

		} else {
		
			$album->getMediaCollection()->removeOneById($dbMedia->getId());
	
			if (
				$state == 'delete' or
				(is_file($this->getAlbumsPath() . $searchDbMedia->getPath()) and $this->fs->getMediaType($searchDbMedia->getPath()))
			) {
				$update = array();
				if ($searchDbMedia->getPath() != $dbMedia->getPath()) {
					$update['oldPath'] = $dbMedia->getPath();
					$dbMedia->setPath($searchDbMedia->getPath());
				}
				if ($searchDbMedia->getInode() != $dbMedia->getInode()) {
					$update['oldInode'] = $dbMedia->getInode();
					$dbMedia->setInode($searchDbMedia->getInode());
				}
				if ($searchDbMedia->getMd5() != $dbMedia->getMd5()) {
					$update['oldMd5'] = $dbMedia->getMd5();
					$dbMedia->setMd5($searchDbMedia->getMd5());
				}
				if ($dbMedia->getExifData() == '') {
					$update['oldExifDate'] = '';
				}
			
				if ($update)
					$state = 'update';
			} else
				$state = 'delete';
				
		}

		$media = object(
			'path', $searchDbMedia->getPath(),
			'state', $state,
			'inode', $searchDbMedia->getInode(),
			'md5', $searchDbMedia->getMd5(),
			'album', $album->getId()
		);
		$media->db = $dbMedia;
		if ($state == 'update')
			$media->update = $update;

		if ($media->state != 'delete') {
			$media->thumbs = $this->optimizer->analyze($dbMedia);
			if ($media->state == 'found' and ($media->thumbs['brick']->state != 'found' or $media->thumbs['slideshow']->state != 'found')) {
				$media->state = 'update';
				$media->update = array();
			}
		}
		
		return $media;
		
	}
	
	public function populateDbMedia($dbMedia, $path, $album)
	{
		$realpath = $this->getAlbumsPath() . $path;
		$dbMedia->setAlbum($album);
		$dbMedia->setPath($path);
		$dbMedia->setType($this->fs->getMediaType($path));

		if (is_file($realpath)) {
			$dbMedia->setInode(fileinode($realpath));
			$dbMedia->setMd5(md5_file($realpath));
		}

		return $dbMedia;
	}
	
	public function media(\Database\Album $album, $fsMedia, $callback)
	{

		$media = $this->analyzeMedia($album, $fsMedia);

		if ($media->db)
			$dbMedia = $media->db;
		else
			$dbMedia = new \Database\Media();
		$this->populateDbMedia($dbMedia, $media->path, $album);

		if ($media->state == 'delete')
			$this->optimizer->delete($dbMedia, $callback);
		else {
			$dbMedia->save();
			$this->optimizer->run($dbMedia, $callback);
		}

	}
	
	public function cover(\Database\Album $album, $callback)
	{
		$this->optimizer->cover($album, $callback);
	}

}
