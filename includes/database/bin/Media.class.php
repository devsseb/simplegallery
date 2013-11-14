<?
namespace Database;

include_once '/home/http/essite.net/ftp/simplegallery/includes/database/Entity.class.php';
class Media extends \Database\Object\Entity
{
	protected $_attributes = array(
		'id' => null,
		'album_id' => '',
		'path' => '',
		'inode' => '',
		'md5' => '',
		'type' => '',
		'width' => '',
		'height' => '',
		'exifOrientation' => '',
		'exifDate' => '',
		'rotation' => '',
		'flipHorizontal' => '',
		'flipVertical' => '',
		'date' => '',
		'thumbBrickMd5' => '',
		'thumbSlideshowMd5' => '',
		'exifData' => '',
		'deleted' => ''
	);
	protected $_attributesFactorized = array(
		'album' => null
	);
	protected $_attributesExtended = array(
		
	);
	


}
