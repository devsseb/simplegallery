<?

class Book implements \arrayaccess, \iterator, \countable
{

	const CBZ = 'cbz';
	const CBR = 'cbr';

	private static $supported = array(
		'cbz' => self::CBZ,
		'cbr' => self::CBR
	);
	private static $pageSupported = array('jpg', 'jpeg', 'png', 'gif', 'bmp');

	private $type, $file, $archive, $name;
	private $pages = null, $currentPage;
	private $regPageName;

	public static function isSupported($file)
	{
		$extensions = array();
		foreach (self::$supported as $extension => $type)
			$extensions[] = preg_quote($extension);
		$extensions = implode('|', $extensions);
		if (!preg_match('/\.(' . $extensions . ')/i', $file, $match))
			return false;

		return self::$supported[$match[1]];
	}

	public function __construct($file = null)
	{
		$regPageName = array();
		foreach (self::$pageSupported as $extension)
			$regPageName[] = preg_quote($extension);
		$this->regPageName = '/\.(' . implode('|', $regPageName) . ')$/i';

		if ($file)
			$this->open($file);
	}
	
	public function open($file)
	{

		if (!$this->type = self::isSupported($file))
			throw new Exception('File not supported');
		
		$this->file = $file;
		$this->name = pathinfo($file, PATHINFO_FILENAME);
		$this->currentPage = 1;

		switch ($this->getType()) {
			case self::CBZ :
				$this->archive = zip_open($file);
				if ($error = $this->zipError($this->archive))
					throw new Exception($error);
			break;
			case self::CBR :
				$this->archive = RarArchive::open($file);
			break;
		}
	}
	
	private function zipError($num)
	{
		if (!is_int($num) or !is_string($num))
			return false;
	
		$errors = array();
		$error[ZipArchive::ER_OK] = false;
		$error[ZipArchive::ER_MULTIDISK] = 'Multi-disk zip archives not supported';
		$error[ZipArchive::ER_RENAME] = 'Renaming temporary file failed';
		$error[ZipArchive::ER_CLOSE] = 'Closing zip archive failed';
		$error[ZipArchive::ER_SEEK] = 'Seek error';
		$error[ZipArchive::ER_READ] = 'Read error';
		$error[ZipArchive::ER_WRITE] = 'Write error';
		$error[ZipArchive::ER_CRC] = 'CRC error';
		$error[ZipArchive::ER_ZIPCLOSED] = 'Containing zip archive was closed';
		$error[ZipArchive::ER_NOENT] = 'No such file';
		$error[ZipArchive::ER_EXISTS] = 'File already exists';
		$error[ZipArchive::ER_OPEN] = 'Can\'t open file';
		$error[ZipArchive::ER_TMPOPEN] = 'Failure to create temporary file';
		$error[ZipArchive::ER_ZLIB] = 'Zlib error';
		$error[ZipArchive::ER_MEMORY] = 'Memory allocation failure';
		$error[ZipArchive::ER_CHANGED] = 'Entry has been changed';
		$error[ZipArchive::ER_COMPNOTSUPP] = 'Compression method not supported';
		$error[ZipArchive::ER_EOF] = 'Premature EOF';
		$error[ZipArchive::ER_INVAL] = 'Invalid argument';
		$error[ZipArchive::ER_NOZIP] = 'Not a zip archive';
		$error[ZipArchive::ER_INTERNAL] = 'Internal error';
		$error[ZipArchive::ER_INCONS] = 'Zip archive inconsistent';
		$error[ZipArchive::ER_REMOVE] = 'Can\'t remove file';
		$error[ZipArchive::ER_DELETED] = 'Entry has been deleted';

		return get($error, k($num));
	}
	
	public function close()
	{
		switch ($this->getType()) {
			case self::CBZ :
				zip_close($this->archive);
			break;
			case self::CBR :
				$this->archive->close();
			break;
		}
	}
	
	public function getFile()
	{
		return $this->file;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getType()
	{
		return $this->type;
	}
	
	private function pageIsImage($file)
	{
		return (bool)preg_match($this->regPageName, $file);
	}
	
	private function loadPages()
	{
		if (is_array($this->pages))
			return;

		$this->pages = array();
		switch ($this->getType()) {
			case self::CBZ :

				while ($entry = zip_read($this->archive)) {
					if (!$this->pageIsImage($name = zip_entry_name($entry)))
						continue;

					$name = pathinfo(strtolower($name));
					$this->pages[$name['dirname'] . '/' . $name['filename']] = $entry;
				
				}

			break;
			case self::CBR :

				$entries = $this->archive->getEntries();

				foreach ($entries as $entry) {
				
					if (!$this->pageIsImage($name = $entry->getName()))
						continue;

					$name = pathinfo(strtolower($name));
					$this->pages[$name['dirname'] . '/' . $name['filename']] = $entry;

				}

			break;
		}
		
		ksort($this->pages, SORT_STRING);

		$this->pages = array_values($this->pages);
	}
	
	public function getTotalPages()
	{
		$this->loadPages();
		
		return count($this->pages);
	}
	
	public function getPage($num, $width = 0, $height = 0)
	{
		$this->loadPages();
		
		$page = object(
			'num', (int)$num,
			'name', '',
			'type', '',
			'md5', '',
			'mime', '',
			'content', ''
			
		);
		
		$entry = $this->pages[$num - 1];

		if (gettype($entry) == 'resource' or get_class($entry) != 'Object') {

			switch ($this->getType()) {
				case self::CBZ :

					$page->name = zip_entry_name($entry);
					while($read = zip_entry_read($entry))
						$page->content.= $read;

				break;
				case self::CBR :

					$page->name = $entry->getName();
					$stream = $entry->getStream();
					while ($read = fread($stream, 1024))
						$page->content.=$read;
					fclose($stream);

				break;
			}

			$page->total = $this->count();

			preg_match($this->regPageName, $page->name, $match);
			$page->type = $match[1];
		
			$this->pages[$num - 1] = $page;
		} else {
		
			$page = $entry;
			
		}


		if ($page->type == 'jpeg')
			$page->type = 'jpg';

		if ($width or $height) {
		
			$page = clone $page;
		
			$page->content = imagecreatefromstring($page->content);
			$pageSize = imagesize($page->content);
			
			$size = object('width', $pageSize->width, 'height', $pageSize->height);
			if ($width and $size->width > $width) {
				$size->height = floor($width * $size->height / $size->width);
				$size->width = $width;
			}
			if ($height and $size->height > $height) {
				$size->width = floor($height * $size->width / $size->height);
				$size->height = $height;				
			}
				
			$resize = imagecreatetruecolor($size->width, $size->height);
			imagecopyresampled($resize, $page->content, 0, 0, 0, 0, $size->width, $size->height, $pageSize->width, $pageSize->height);
			
			ob_start();
			imagejpeg($resize);
			$page->content = ob_get_contents();
			ob_end_clean();

		}
		
		$page->md5 = md5($page->content);
		
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$page->mime = finfo_buffer($finfo, $page->content);
		finfo_close($finfo);
		
		return $page;
	}
	
	public function getSize()
	{
		$cover = imagecreatefromstring($this->getPage(1)->content);
		return object(
			'width', imagesx($cover),
			'height', imagesy($cover)
		);
	}

	/*
	 * Arrayaccess methods
	 */
	public function offsetExists($page)
	{
		$this->loadPages();
		return array_key_exists($page - 1, $this->pages);
	}
	public function offsetGet($page)
	{
		return $this->getPage($page);
	}
	public function offsetSet($key, $value)
	{
		throw new Exception('Book is readonly');
	}

	public function offsetUnset($key)
	{
		throw new Exception('Book is readonly');
	}

	/*
	 * Iterator methods
	 */
	public function current()
	{
		return $this->offsetGet[$this->currentPage];
	}
	
	public function key()
	{
		return $this->currentPage;
	}
	
	public function next()
	{
		++$this->currentPage;
	}
	
	public function rewind()
	{
		$this->currentPage = 1;
	}
	
	public function valid()
	{
		return $this->offsetExists($this->currentPage);
	}
	
	/*
	 * Countable method
	 */
	public function count()
	{
		return $this->getTotalPages();
	}

}
