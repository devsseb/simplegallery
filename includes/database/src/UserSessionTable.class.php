<?
namespace Database\Src;

class UserSessionTable extends \Database\Compilation\TableSrc
{
	public function _construct()
	{

		$this->addAttribute('id', \Database::T_INTEGER, null, true, true, true);
		$this->addAttribute('code', \Database::T_CHAR);
		$this->addLink('user', \Database::R_1_N);
		
		$this->addAttribute('datetime', \Database::T_DATETIME);

	}

}
