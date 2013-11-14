<?
namespace Database;

include_once '/home/http/essite.net/ftp/simplegallery/includes/database/Entity.class.php';
class Album extends \Database\Object\Entity
{
	protected $_attributes = array(
		'id' => null,
		'inode' => '',
		'path' => '',
		'name' => '',
		'parent_id' => '',
		'albumsTotal' => '',
		'mediasTotal' => '',
		'coverMedias' => '',
		'coverMediasMd5' => '',
		'coverMd5' => ''
	);
	protected $_attributesFactorized = array(
		'parent' => null,
		'groupCollection' => null,
		'children' => null,
		'mediaCollection' => null
	);
	protected $_attributesExtended = array(
		'access' => null,
		'access_inherited' => null
	);
	


}
