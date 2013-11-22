<?
namespace Database;

include_once '/home/http/essite.net/ftp/simplegallery/includes/database/Table.class.php';
class CommentTable extends \Database\Object\Table
{
	protected static $id = 'id';
	protected static $name = 'comment';
	protected static $attributes = array(
		'id',
		'date',
		'text',
		'user_id',
		'media_id'
	);
	protected static $attributesFactorized = array(
		'user',
		'media'
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
			'onAttributeFactorized' => 'commentCollection',
			'nnTable' => '',
			'nnAttribute' => '',
			'nnOnAttribute' => '',
			'nnAttributes' => array()
		),
		array(
			'relationship' => \Database::R_1_N,
			'attribute' => 'media_id',
			'attributeFactorized' => 'media',
			'onTableClass' => '\Database\MediaTable',
			'onAttribute' => 'id',
			'onAttributeFactorized' => 'commentCollection',
			'nnTable' => '',
			'nnAttribute' => '',
			'nnOnAttribute' => '',
			'nnAttributes' => array()
		)
	);
	


}
