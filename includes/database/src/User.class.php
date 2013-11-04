<?
namespace Database\Src;

class User extends \Database\Compilation\EntitySrc
{
	public function setCryptPassword($password)
	{
		$salt_chars = array_merge(range('A','Z'), range('a','z'), range(0,9));
		$salt = '';
		for($i=0; $i < 22; $i++)
			$salt.= $salt_chars[array_rand($salt_chars)];
		parent::setPassword(crypt($password, sprintf('$2a$%02d$', 7) . $salt));
	}
}
