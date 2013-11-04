<?
namespace Database;

include_once '/home/http/derre.fr/ftp/galerie/includes/database/Table.class.php';
class UserSessionTable extends \Database\Object\Table
{
	protected static $id = 'id';
	protected static $name = 'usersession';
	protected static $attributes = array(
		'id',
		'code',
		'user_id',
		'datetime'
	);
	protected static $attributesFactorized = array(
		'user'
	);
	protected static $attributesExtended = array(
		
	);
	protected static $links = array(
		array(
			'relationship' => \Database::R_1_N,
			'attribute' => 'user_id',
			'attributeFactorized' => 'user',
			'onTableClass' => '\Database\UserTable',
			'onAttribute' => 'id',
			'onAttributeFactorized' => 'usersessionCollection',
			'nnTable' => '',
			'nnAttribute' => '',
			'nnOnAttribute' => '',
			'nnAttributes' => array()
		)
	);
	


}
