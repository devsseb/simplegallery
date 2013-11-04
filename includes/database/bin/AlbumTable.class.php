<?
namespace Database;

include_once '/home/http/derre.fr/ftp/galerie/includes/database/Table.class.php';
class AlbumTable extends \Database\Object\Table
{
	protected static $id = 'id';
	protected static $name = 'album';
	protected static $attributes = array(
		'id',
		'inode',
		'path',
		'name',
		'parent_id',
		'albumsTotal',
		'mediasTotal',
		'coverMedias',
		'coverMediasMd5',
		'coverMd5'
	);
	protected static $attributesFactorized = array(
		'parent',
		'groupCollection',
		'children',
		'mediaCollection'
	);
	protected static $attributesExtended = array(
		'access',
		'access_inherited'
	);
	protected static $links = array(
		array(
			'relationship' => \Database::R_1_N,
			'attribute' => 'parent_id',
			'attributeFactorized' => 'parent',
			'onTableClass' => '\Database\AlbumTable',
			'onAttribute' => 'id',
			'onAttributeFactorized' => 'children',
			'nnTable' => '',
			'nnAttribute' => '',
			'nnOnAttribute' => '',
			'nnAttributes' => array()
		),
		array(
			'relationship' => \Database::R_N_N,
			'attribute' => 'id',
			'attributeFactorized' => 'groupCollection',
			'onTableClass' => '\Database\GroupTable',
			'onAttribute' => 'id',
			'onAttributeFactorized' => 'albumCollection',
			'nnTable' => 'album_group',
			'nnAttribute' => 'album_id',
			'nnOnAttribute' => 'group_id',
			'nnAttributes' => array('access','access_inherited')
		),
		array(
			'relationship' => \Database::R_N_1,
			'attribute' => 'id',
			'attributeFactorized' => 'children',
			'onTableClass' => '\Database\AlbumTable',
			'onAttribute' => 'parent_id',
			'onAttributeFactorized' => 'parent',
			'nnTable' => '',
			'nnAttribute' => '',
			'nnOnAttribute' => '',
			'nnAttributes' => array()
		),
		array(
			'relationship' => \Database::R_N_1,
			'attribute' => 'id',
			'attributeFactorized' => 'mediaCollection',
			'onTableClass' => '\Database\MediaTable',
			'onAttribute' => 'album_id',
			'onAttributeFactorized' => 'album',
			'nnTable' => '',
			'nnAttribute' => '',
			'nnOnAttribute' => '',
			'nnAttributes' => array()
		)
	);
	


}
