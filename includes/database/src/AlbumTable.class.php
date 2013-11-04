<?
namespace Database\Src;

class AlbumTable extends \Database\Compilation\TableSrc
{
	public function _construct()
	{

		$this->addAttribute('id', \Database::T_INTEGER, null, true, true, true);
		$this->addAttribute('inode', \Database::T_INTEGER); 
		$this->addAttribute('path', \Database::T_CHAR);
		$this->addAttribute('name', \Database::T_CHAR);

		$this->addLink('album', \Database::R_1_N, 'parent_id', 'parent', 'id', 'children');

		$this->addAttribute('albumsTotal', \Database::T_CHAR);
		$this->addAttribute('mediasTotal', \Database::T_CHAR);

		$this->addAttribute('coverMedias', \Database::T_CHAR);
		$this->addAttribute('coverMediasMd5', \Database::T_CHAR);
		$this->addAttribute('coverMd5', \Database::T_CHAR);
		
		$this->addLink('group', \Database::R_N_N, null, null, null, null, null, null, null, array(
			object(
				'name', 'access',
				'type', \Database::T_INTEGER
			),
			object(
				'name', 'access_inherited',
				'type', \Database::T_INTEGER
			)
		));
		
	}

}
