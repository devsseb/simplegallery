<?
namespace Database;

include_once '/home/http/derre.fr/ftp/galerie/includes/database/Entity.class.php';
class User extends \Database\Object\Entity
{
	protected $_attributes = array(
		'id' => null,
		'email' => '',
		'name' => '',
		'password' => '',
		'active' => '',
		'activeCode' => '',
		'admin' => '',
		'passwordCode' => '',
		'passwordCodeTime' => '',
		'mailUpdate' => '',
		'mailUpdateCode' => '',
		'mailUpdateCodeTime' => ''
	);
	protected $_attributesFactorized = array(
		'groupCollection' => null,
		'usersessionCollection' => null
	);
	protected $_attributesExtended = array(
		
	);
	
	public function setCryptPassword($password)
	{
		$salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
		$salt = '';
		for($i=0; $i < 22; $i++)
			$salt.= $salt_chars[array_rand($salt_chars)];
		parent::setPassword(crypt($password, sprintf('$2a$%02d$', 7) . $salt));
	}


}
