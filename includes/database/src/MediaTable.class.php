<?
namespace Database\Src;

class MediaTable extends \Database\Compilation\TableSrc
{
	public function _construct()
	{

		$this->addAttribute('id', \Database::T_INTEGER, null, true, true, true);
		$this->addLink('album', \Database::R_1_N);
		$this->addAttribute('path', \Database::T_CHAR);
		$this->addAttribute('inode', \Database::T_INTEGER);
		$this->addAttribute('md5', \Database::T_CHAR);
		$this->addAttribute('type', \Database::T_CHAR);
		
		$this->addAttribute('width', \Database::T_INTEGER);
		$this->addAttribute('height', \Database::T_INTEGER);

		$this->addAttribute('exifOrientation', \Database::T_INTEGER);
		$this->addAttribute('exifDate', \Database::T_DATETIME);
		
		$this->addAttribute('rotation', \Database::T_INTEGER);
		$this->addAttribute('flipHorizontal', \Database::T_BOOL);
		$this->addAttribute('flipVertical', \Database::T_BOOL);
		$this->addAttribute('date', \Database::T_DATETIME);

		$this->addAttribute('thumbBrickMd5', \Database::T_CHAR);
		$this->addAttribute('thumbSlideshowMd5', \Database::T_CHAR);
		
		$this->addAttribute('exifData', \Database::T_BIG_CHAR);

		$this->addAttribute('deleted', \Database::T_BOOL);

	}

}
