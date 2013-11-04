<?
namespace Database;

include_once '|tablePath|Table.class.php';
class |className| extends \Database\Object\Table
{
	protected static $id = |id|;
	protected static $name = '|tableName|';
	protected static $attributes = array(
		|attributes|
	);
	protected static $attributesFactorized = array(
		|attributesFactorized|
	);
	protected static $attributesExtended = array(
		|attributesExtended|
	);
	protected static $links = array(
		|links|
	);
	
|userFunctions|

}
