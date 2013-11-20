<?
namespace Database;

include_once '/home/http/essite.net/ftp/simplegallery/includes/database/Entity.class.php';
class UserSession extends \Database\Object\Entity
{
	

	protected $_attributes = array(
		'id' => null,
		'code' => '',
		'user_id' => '',
		'datetime' => ''
	);
	protected $_attributesFactorized = array(
		'user' => null
	);
	protected $_attributesExtended = array(
		
	);
	


}
