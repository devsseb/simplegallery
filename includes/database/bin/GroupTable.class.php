<?
namespace Database;

include_once '/home/http/derre.fr/ftp/galerie/includes/database/Table.class.php';
class GroupTable extends \Database\Object\Table
{
	protected static $id = 'id';
	protected static $name = 'group';
	protected static $attributes = array(
		'id',
		'name'
	);
	protected static $attributesFactorized = array(
		'albumCollection',
		'userCollection'
	);
	protected static $attributesExtended = array(
		'access',
		'access_inherited'
	);
	protected static $links = array(
		array(
			'relationship' => \Database::R_N_N,
			'attribute' => 'id',
			'attributeFactorized' => 'albumCollection',
			'onTableClass' => '\Database\AlbumTable',
			'onAttribute' => 'id',
			'onAttributeFactorized' => 'groupCollection',
			'nnTable' => 'album_group',
			'nnAttribute' => 'group_id',
			'nnOnAttribute' => 'album_id',
			'nnAttributes' => array('access','access_inherited')
		),
		array(
			'relationship' => \Database::R_N_N,
			'attribute' => 'id',
			'attributeFactorized' => 'userCollection',
			'onTableClass' => '\Database\UserTable',
			'onAttribute' => 'id',
			'onAttributeFactorized' => 'groupCollection',
			'nnTable' => 'user_group',
			'nnAttribute' => 'group_id',
			'nnOnAttribute' => 'user_id',
			'nnAttributes' => array()
		)
	);
	


}
