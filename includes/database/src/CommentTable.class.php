<?
namespace Database\Src;

class CommentTable extends \Database\Compilation\TableSrc
{
	public function _construct()
	{

		$this->addAttribute('id', \Database::T_INTEGER, null, true, true, true);
		$this->addAttribute('date', \Database::T_CHAR);
		$this->addAttribute('text', \Database::T_BIG_CHAR);

		$this->addLink('user', \Database::R_1_N);
		$this->addLink('media', \Database::R_1_N);

	}

}
