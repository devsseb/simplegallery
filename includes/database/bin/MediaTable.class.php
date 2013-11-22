<?
namespace Database;

include_once '/home/http/essite.net/ftp/simplegallery/includes/database/Table.class.php';
class MediaTable extends \Database\Object\Table
{
	protected static $id = 'id';
	protected static $name = 'media';
	protected static $attributes = array(
		'id',
		'album_id',
		'path',
		'inode',
		'md5',
		'type',
		'width',
		'height',
		'exifOrientation',
		'exifDate',
		'rotation',
		'flipHorizontal',
		'flipVertical',
		'date',
		'thumbBrickMd5',
		'thumbSlideshowMd5',
		'exifData',
		'deleted'
	);
	protected static $attributesFactorized = array(
		'album',
		'commentCollection'
	);
	protected static $attributesExtended = array(
		
	);
	protected static $links = array(
		array(
			'relationship' => \Database::R_1_N,
			'attribute' => 'album_id',
			'attributeFactorized' => 'album',
			'onTableClass' => '\Database\AlbumTable',
			'onAttribute' => 'id',
			'onAttributeFactorized' => 'mediaCollection',
			'nnTable' => '',
			'nnAttribute' => '',
			'nnOnAttribute' => '',
			'nnAttributes' => array()
		),
		array(
			'relationship' => \Database::R_N_1,
			'attribute' => 'id',
			'attributeFactorized' => 'commentCollection',
			'onTableClass' => '\Database\CommentTable',
			'onAttribute' => 'media_id',
			'onAttributeFactorized' => 'media',
			'nnTable' => '',
			'nnAttribute' => '',
			'nnOnAttribute' => '',
			'nnAttributes' => array()
		)
	);
	


}
