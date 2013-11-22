<?
namespace Database;

include_once '/home/http/essite.net/ftp/simplegallery/includes/database/Entity.class.php';
class Comment extends \Database\Object\Entity
{
	

	protected $_attributes = array(
		'id' => null,
		'date' => '',
		'text' => '',
		'user_id' => '',
		'media_id' => ''
	);
	protected $_attributesFactorized = array(
		'user' => null,
		'media' => null
	);
	protected $_attributesExtended = array(
		
	);
	


}
