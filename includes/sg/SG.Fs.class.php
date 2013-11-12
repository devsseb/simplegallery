<?php

namespace SG;

class Fs {

	const
		ALL = 0,
		ONLY_FILES = 1,
		ONLY_DIRS = 2
	;

	private
		$mediasTypes = array(
			'image'	=> array('jpg', 'jpeg', 'png', 'gif', 'bmp'),
			'video'	=> array('avi', 'mts', 'm2ts', 'm2t', 'mkv', 'mov', 'mpeg', 'mpg', 'webm', 'mp4', 'asf', 'wmv', 'mp2', 'm2p', 'vob', 'flv', 'dv'),
			'book'	=> array('cbz', 'cbr'/*, 'pdf'*/)
		),
		$mediasMask
	;
	
	public function __construct()
	{
		$this->mediasMask = array();
		foreach ($this->getMediasTypes() as $exts)
			$this->mediasMask = array_merge($this->mediasMask, $exts);
		array_walk($this->mediasMask, function(&$ext) {
			$ext = preg_quote($ext);
		});
		$this->mediasMask = '/\\.(' . implode('|', $this->mediasMask) . ')$/i';

	}
	
	public function getMediasTypes($type = null)
	{
		if ($type)
			return $this->mediasTypes[$type];
		return $this->mediasTypes;
	}
	
	static public function getMediaExtension($fsMedia)
	{
		return strtolower(pathinfo($fsMedia, PATHINFO_EXTENSION));
	}
	
	public function getMediaType($fsMedia)
	{
		$ext = self::getMediaExtension($fsMedia);
		foreach ($this->getMediasTypes() as $type => $exts)
			if (in_array($ext, $exts))
				return $type;
		return null;
	}
	
	public function getMediasMask()
	{
		return $this->mediasMask;
	}
	
	public function getDir($dir, $pattern = null, $flag = self::ALL)
	{
		$files = array_diff(scandir($dir), array('..', '.'));
		if ($pattern)
			$files = preg_grep($pattern, $files);
		if ($flag)
			foreach ($files as $index => $file)
				if (($flag == self::ONLY_FILES and !is_file($dir . $file)) or ($flag == self::ONLY_DIRS and !is_dir($dir . $file)))
					unset($files[$index]);
		return array_values($files);
	}

	public function inDir($dir, $file, $exists = false)
	{
		$dir = realpath($dir);
		$element  = realpath(dirname($file)) . '/' . basename($file);
		$return = strpos($element, $dir) === 0;
		if ($return and $exists)
			$return = is_file($file);
		return $return;
	}


}
