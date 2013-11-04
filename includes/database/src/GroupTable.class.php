<?
namespace Database\Src;

class GroupTable extends \Database\Compilation\TableSrc
{
	function _construct()
	{

		$this->addAttribute('id', \Database::T_INTEGER, null, true, true, true);
		$this->addAttribute('name', \Database::T_CHAR);

	}
}
