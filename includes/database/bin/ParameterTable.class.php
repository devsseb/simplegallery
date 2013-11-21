<?
namespace Database;

include_once '/home/http/essite.net/ftp/simplegallery/includes/database/Table.class.php';
class ParameterTable extends \Database\Object\Table
{
	protected static $id = null;
	protected static $name = 'parameter';
	protected static $attributes = array(
		'databaseVersion',
		'galleryName',
		'disableRegistration'
	);
	protected static $attributesFactorized = array(
		
	);
	protected static $attributesExtended = array(
		
	);
	protected static $links = array(
		
	);
	


}
