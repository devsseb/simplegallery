<?
namespace Database\Src;

class UserTable extends \Database\Compilation\TableSrc
{
	public function _construct()
	{

		$this->addAttribute('id', \Database::T_INTEGER, null, true, true, true);
		$this->addAttribute('email', \Database::T_CHAR);
		$this->addAttribute('name', \Database::T_CHAR);
		$this->addAttribute('password', \Database::T_CHAR);
		$this->addAttribute('active', \Database::T_BOOL);
		$this->addAttribute('activeCode', \Database::T_CHAR);
		$this->addAttribute('admin', \Database::T_BOOL);
		$this->addAttribute('passwordCode', \Database::T_CHAR);
		$this->addAttribute('passwordCodeTime', \Database::T_DATETIME);
		$this->addAttribute('emailUpdate', \Database::T_CHAR);
		$this->addAttribute('emailUpdateCode', \Database::T_CHAR);
		$this->addAttribute('emailUpdateCodeTime', \Database::T_CHAR);

		$this->addLink('group', \Database::R_N_N);

	}

}
