<?php

// kernel/0.1/Object.class.php

class Object implements countable
{
	public function __construct($var = null)
	{
		if (is_array($var))
			foreach ($var as $key => $value)
				$this->$key = $value;
		else {

			$args = func_get_args();
			$total = func_num_args();
			for ($i = 0; $i < $total; $i = $i + 2)
				$this->{$args[$i]} = $args[$i + 1];
		
		}
	}

	public function count()
	{
		return count(get_object_vars($this));
	}
}


function object($var = null)
{
	return call_user_func_array(array(new ReflectionClass('Object'), 'newInstance'), func_get_args());
}
