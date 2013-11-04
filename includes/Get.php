<?php

define('GET_FLAG_KEYS', "\x0b*1"); //'*' . chr(11) . '1'
define('GET_FLAG_REF', "\x0b*2"); //'*' . chr(11) . '2'

function exists(&$var)
{
	if (1 === $count = func_num_args())
		return isset($var);

	$args = func_get_args();

	$_var = &$var;
	for ($i = 1; $i < $count; $i++)
		if ($exists = (is_object($_var) and (property_exists($_var, $args[$i]) or method_exists($_var, $args[$i]))))
			$_var = &$_var->$args[$i];
		elseif ($exists = (is_array($_var) and array_key_exists($args[$i], $_var)))
			$_var = &$_var[$args[$i]];
		else
			break;

	return $exists;
}

		
function getIsFlag($var, $flag)
{
	return is_array($var) and reset($var) === $flag;
}

function k($args)
{
	return array_merge(array(GET_FLAG_KEYS), is_array($args) ? $args : func_get_args());
}

/*
 * Get from array reference and return reference
 * getar $array, k($keys)
 * getar $array, k($keys), $default
 * getar $arraykey, null
 * getar $arraykey, $default
 *
 */
function &getar(&$array, $keysOrDefault = null, $defaultOrEmpty = null, $empty = false)
{

	if (getIsFlag($keysOrDefault, GET_FLAG_KEYS)) {
		$keys = $keysOrDefault;
		$keys[0] = &$array;
		$default = $defaultOrEmpty;
	} else {
		$keys = $array;
		$keys[0] = &$array[0];
		$default = $keysOrDefault;
		$empty = $defaultOrEmpty;
	}

	if (call_user_func_array('exists', $keys)) {
		$_var = &$keys[0];
		$count = count($keys);
		for ($i = 1; $i < $count; $i++)
			if (is_object($_var)) $_var = &$_var->$keys[$i];
			else $_var = &$_var[$keys[$i]];

		if (!$empty or !empty($_var))
			return $_var;
	}
	return $default;
}

/*
 * get &$var
 * get &$var, $default
 * get &$var, k($keys)
 * get &$var, k($keys), $default
 *
 */
function &get(&$var, $keysOrDefault = null, $default = null)
{
	return getar(getIsFlag($keysOrDefault, GET_FLAG_KEYS) ? $var : array(&$var), $keysOrDefault, $default);
}

/*
 * Get from array
 * geta $array, k($keys)
 * geta $array, k($keys), $default
 * geta $arraykey, null
 * geta $arraykey, $default
 *
 */
function &geta($array, $keysOrDefault = null, $default = null)
{
	return getar($array, $keysOrDefault, $default);
}

/*
 * Get if non empty
 * 
 */
function &gete(&$var, $keysOrDefault = null, $default = null)
{
	if (!getIsFlag($keysOrDefault, GET_FLAG_KEYS))
		$default = true;
	return getar(getIsFlag($keysOrDefault, GET_FLAG_KEYS) ? $var : array(&$var), $keysOrDefault, $default, true);
}

/*
 * Get if non empty from array
 * 
 */
function &getea($array, $keysOrDefault = null, $default = null)
{
	if (!getIsFlag($keysOrDefault, GET_FLAG_KEYS))
		$default = true;
	return getar($array, $keysOrDefault, $default, true);
}

/*
 * Transmit a var by ref in function
 *
 * Usage : myFunction( ref($var) )
 *
 */
function ref(&$ref) {
	return array(GET_FLAG_REF, &$ref);
}

/*
 * Return argument number $num passed with ref() by reference
 *
 * Usage : myFunction( ref($var) )
 *
 */
function &getArg($num)
{
	$arg = &geta(debug_backtrace(0), k(1, 'args', $num));
	if (getIsFlag($arg, GET_FLAG_REF))
		$arg = &$arg[1];

	return $arg;
}

function getn($class)
{
	$args = func_get_args();
	array_shift($args);
	return getna($class, $args);
}

function getna($class, $args)
{
    $class = new ReflectionClass($class);
    return $class->newInstanceArgs($args);
}

?>
