<?
namespace Database;

include_once '/home/http/essite.net/ftp/simplegallery/includes/database/Table.class.php';
class UserTable extends \Database\Object\Table
{
	protected static $id = 'id';
	protected static $name = 'user';
	protected static $attributes = array(
		'id',
		'email',
		'name',
		'password',
		'active',
		'activeCode',
		'admin',
		'passwordCode',
		'passwordCodeTime',
		'emailUpdate',
		'emailUpdateCode',
		'emailUpdateCodeTime'
	);
	protected static $attributesFactorized = array(
		'groupCollection',
		'commentCollection',
		'usersessionCollection'
	);
	protected static $attributesExtended = array(
		
	);
	protected static $links = array(
		array(
			'relationship' => \Database::R_N_N,
			'attribute' => 'id',
			'attributeFactorized' => 'groupCollection',
			'onTableClass' => '\Database\GroupTable',
			'onAttribute' => 'id',
			'onAttributeFactorized' => 'userCollection',
			'nnTable' => 'user_group',
			'nnAttribute' => 'user_id',
			'nnOnAttribute' => 'group_id',
			'nnAttributes' => array()
		),
		array(
			'relationship' => \Database::R_N_1,
			'attribute' => 'id',
			'attributeFactorized' => 'commentCollection',
			'onTableClass' => '\Database\CommentTable',
			'onAttribute' => 'user_id',
			'onAttributeFactorized' => 'user',
			'nnTable' => '',
			'nnAttribute' => '',
			'nnOnAttribute' => '',
			'nnAttributes' => array()
		),
		array(
			'relationship' => \Database::R_N_1,
			'attribute' => 'id',
			'attributeFactorized' => 'usersessionCollection',
			'onTableClass' => '\Database\UserSessionTable',
			'onAttribute' => 'user_id',
			'onAttributeFactorized' => 'user',
			'nnTable' => '',
			'nnAttribute' => '',
			'nnOnAttribute' => '',
			'nnAttributes' => array()
		)
	);
	


}
