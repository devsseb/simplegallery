<?php

namespace SG;

abstract class Media {

	private
		$dimensions = array(
			'brick'		=> 200,
			'slideshow'	=> 1000
		),
		$thumbsPath,
		$dbMedia
	;

	static private $coverDimension = 150;

	public function __construct($albumsPath, $thumbsPath, \Database\Media $dbMedia)
	{
		$this->albumsPath = $albumsPath;
		$this->thumbsPath = $thumbsPath;
		$this->dbMedia = $dbMedia;
		if (method_exists($this, '_contruct'))
			$this->_construct();
	}
	
	protected function getDimensions()
	{
		return $this->dimensions;
	}
	
	protected function getDimension($code)
	{
		return $this->dimensions[$code];
	}
	
	static public function getCoverDimension()
	{
		return self::$coverDimension;
	}

	protected function getAlbumsPath()
	{
		return $this->albumsPath;
	}
	
	protected function getThumbsPath()
	{
		return $this->thumbsPath;
	}
	
	protected function getDbMedia()
	{
		return $this->dbMedia;
	}
	
	abstract public function analyze();
	abstract public function getSize();
	abstract public function getExif();
	abstract public function generate($callback);
	abstract public function generateBrickThumb($fsMedia, $fsThumb, $callback = null);
	abstract public function generateSlideshowThumb($fsMedia, $fsThumb, $callback = null);
	abstract public function delete($callback);
	abstract public function getCoverImage();
	abstract public function getThumbSlideshow($data = null);
	
	protected function generateThumbFromImage($dimension, $image, $fsThumb)
	{
		
		$resize = object(
			'width', $dimension * imagesx($image) / imagesy($image),
			'height', $dimension
		);

		$imageResize = imagecreatetruecolor($resize->width, $resize->height);
		imagecopyresampled($imageResize, $image, 0, 0, 0, 0, $resize->width, $resize->height, imagesx($image), imagesy($image));

		imagejpeg($imageResize, $fsThumb);
		imagedestroy($imageResize);
	}
	
	protected function getCoverFromImage($image)
	{
		$dimension = self::getCoverDimension();
	
		if (imagesx($image) / imagesy($image) > 1)
			$resize = object(
				'width', $dimension,
				'height', ceil($dimension * imagesy($image) / imagesx($image))
			);
		else
			$resize = object(
				'width', ceil($dimension * imagesx($image) / imagesy($image)),
				'height', $dimension
			);

		$imageResize = imagecreatetruecolor($resize->width, $resize->height);
		imagecopyresampled($imageResize, $image, 0, 0, 0, 0, $resize->width, $resize->height, imagesx($image), imagesy($image));

		return $imageResize;
	}

	public function getThumbBrick()
	{
		return $this->getThumbsPath() . $this->getDbMedia()->getAlbum()->getId() . '/' . $this->getDbMedia()->getId() . '.brick.jpg';
	}

}
