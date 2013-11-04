<?
namespace Database;

include_once '/home/http/derre.fr/ftp/galerie/includes/database/Entity.class.php';
class Group extends \Database\Object\Entity
{
	protected $_attributes = array(
		'id' => null,
		'name' => ''
	);
	protected $_attributesFactorized = array(
		'albumCollection' => null,
		'userCollection' => null
	);
	protected $_attributesExtended = array(
		'access' => null,
		'access_inherited' => null
	);
	


}
