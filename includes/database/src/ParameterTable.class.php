<?
namespace Database\Src;

class ParameterTable extends \Database\Compilation\TableSrc
{
	public function _construct()
	{

		$this->addAttribute('databaseVersion', \Database::T_INTEGER);
		$this->addAttribute('galleryName', \Database::T_CHAR);
		$this->addAttribute('disableRegistration', \Database::T_BOOL);

	}

}
