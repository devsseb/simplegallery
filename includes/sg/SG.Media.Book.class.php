<?php

namespace SG\Media;

class Book extends \SG\Media {

	public function analyze()
	{
		$albumThumbsPath = $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/';
		$basename = basename($this->getDbMedia()->getPath());
		
		$result = array();
		foreach ($this->getDimensions() as $code => $size) {

			$fsThumb = $albumThumbsPath . $this->getDbMedia()->getId() . '.' . $code . '.jpg';
			$fsKeep = array();
			$totalPages = $this->getTotalPages();
			for ($page = 1; $page <= $totalPages; $page ++)
				$fsKeep[] = $albumThumbsPath . $this->getDbMedia()->getId() . '.' . $code . '.' . $page . '.jpg';

			$state = 'found';
			
			if (is_file($fsThumb)) {
				$md5 = md5_file($fsThumb);
				$dbMd5 = $this->getDbMedia()->{'getThumb' . ucfirst($code) . 'Md5'}();
				if ($md5 != $dbMd5) {
					$state = 'update';
					$update = array();
				}
				
			} else
				$state = 'new';

			
			if ($state == 'found' and $code == 'slideshow') {
				$totalPages = $this->getTotalPages();
				for ($page = 1; $page <= $totalPages; $page ++)
					if (!is_file($albumThumbsPath . $this->getDbMedia()->getId() . '.' . $code . '.' . $page . '.jpg')) {
						$state = 'update';
						$update = array();
						break;
					}
						
						

			}
			
			$media = object(
				'state', $state,
				'code', $code,
				'page', 0,
				'size', $size,
				'fsMedia', $this->getAlbumsPath() . $this->getDbMedia()->getPath(),
				'fsThumb', $fsThumb,
				'fsKeep', $fsKeep
			);
			if ($state == 'update')
				$media->update = $update;
				
			$result[$code] = $media;
			
		}

		return $result;
		
	}
	
	public function getSize()
	{
		$book = new \Book($this->getAlbumsPath() . $this->getDbMedia()->getPath());
		$size = $book->getSize();
		$book->close();
		return $size;
	}

	private function getTotalPages()
	{
		$book = new \Book($this->getAlbumsPath() . $this->getDbMedia()->getPath());
		$totalPage = $book->getTotalPages();
		$book->close();
		
		return $totalPage;
	}

	public function getExif()
	{
		return object(
			'orientation', 1,
			'date', '',
			'data', array(
				'totalPage' => $this->getTotalPages()
			)
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
					$this->{'generate' . ucfirst($result->code) . 'Thumb'}($result->fsMedia, $result->fsThumb, $callback, $result->page);
					
			
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

		$book = new \Book($fsMedia);
		$image = imagecreatefromstring($book->getPage(1)->content);
		$book->close();
		
		$this->generateThumbFromImage($this->getDimension('brick'), $image, $fsThumb);
	
		imagedestroy($image);
		
	}
	
	public function generateSlideshowThumb($fsMedia, $fsThumb, $callback = null)
	{

		$book = new \Book($fsMedia);
	
		$convert = object(
			'state', 'convert',
			'code', 'slideshow',
			'fs', $this->getDbMedia()->getPath(),
			'progress', 0
		);

		$height = 100;

		$thumb = imagecreatetruecolor(1,1);
		$size = object('width', 0, 'height', 0);
		$slideshowSizes = array();

		$callback($convert);
		$totalPages = $book->getTotalPages();
		
		for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++) {
			resetTimeout();
			$page = $book->getPage($pageNumber, null, $height);
			$pageThumb = imagecreatefromstring($page->content);
			$thumbSize = object(
				'width', imagesx($pageThumb),
				'height', imagesy($pageThumb)
			);
			$newSize = object(
				'width', $size->width,
				'height', $size->height + $thumbSize->height
			);
			if ($thumbSize->width > $newSize->width)
				$newSize->width = $thumbSize->width;
		
			$newThumb = imagecreatetruecolor($newSize->width, $newSize->height);
			imagecopy($newThumb, $thumb, 0, 0, 0, 0, $size->width, $size->height);
			imagedestroy($thumb);
			imagecopy($newThumb, $pageThumb, 0, $size->height, 0, 0, $thumbSize->width, $thumbSize->height);
			imagedestroy($pageThumb);
		
			$thumb = $newThumb;
			$size = $newSize;
		
			$slideshowSizes[] = $thumbSize;

			$page = $book->getPage($pageNumber, $this->getDimension('slideshow'), $this->getDimension('slideshow'));
			$pageThumb = imagecreatefromstring($page->content);
			imagejpeg($pageThumb, preg_replace('/\.jpg$/', '.' . $pageNumber . '.jpg', $fsThumb));
			imagedestroy($pageThumb);
		
			$convert->progress = ceil($pageNumber * 100 / $totalPages);
			$callback($convert);
		}
		$book->close();
		
		$exif = json_decode($this->getDbMedia()->getExifData());
		$exif->slideshowSizes = $slideshowSizes;
		$this->getDbMedia()->setExifData(json_encode($exif));
		$this->getDbMedia()->save();
	
		imagejpeg($thumb, $fsThumb);
		imagedestroy($thumb);

	}
	
	public function delete($callback)
	{
		$albumThumbsPath = $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/';
		$basename = basename($this->getDbMedia()->getPath());
		
		if (is_file($fsThumb = $albumThumbsPath . $basename . '.brick.jpg')) {
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

		$book = new \Book($fsMedia);
		$image = imagecreatefromstring($book->getPage(1)->content);
		$book->close();
		
		$cover = $this->getCoverFromImage($image);
	
		imagedestroy($image);
		
		return $cover;
		
	}

	public function getThumbSlideshow($data = null)
	{
		if (exists($data, 'pages'))
			return $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/' . $this->getDbMedia()->getId() . '.slideshow.jpg';
		else
			return $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/' . $this->getDbMedia()->getId() . '.slideshow.' . get($data, k('page'), 1) . '.jpg';

		$page = get($data, k('page'), 1);		
		$fsMedia = $this->getAlbumsPath() . $this->getDbMedia()->getPath();

		$book = new \Book($fsMedia);
		$page = $book->getPage($page);
		$book->close();

		$thumb = object(
			'file', $fsMedia,
			'mime', $page->mime,
			'time', '',
			'md5', $page->md5,
			'content', $page->content
		);
		
		return $thumb;
		
	}

}
