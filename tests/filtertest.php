<?php

class testfilter {	
	public $filters;
	
	public function __construct($filters=null)
	{
		if (isset($filters))
		{
			$this->filters = is_array($filters) ? $filters : array($filters);
		}
	}
	
	public function checkUsername($uid)
	{
		if (!isset($this->filters) || count($this->filters) == 0)
		{			
			return isset($uid) && !empty($uid);
		}
		else
		{
			foreach($this->filters as $filter)
			{
				if (preg_match($filter, $uid))
				{
					return true;
				}
			}
		}
	}
}


//$tf = new testfilter('/.*\-dt/i');
$tf = new testfilter();

print($tf->checkUsername("peter-vt") ."\n");
print($tf->checkUsername("peter-ap") ."\n");
print($tf->checkUsername("peter") ."\n");

$tf->filters = array('/.*\-vt/i');

print($tf->checkUsername("peter-vt") ."\n");
print($tf->checkUsername("peter-ap") ."\n");
print($tf->checkUsername("peter") ."\n");

$tf->filters = array('/.*\-vt/i','/.*\-ap/i');

print($tf->checkUsername("peter-vt") ."\n");
print($tf->checkUsername("peter-ap") ."\n");
print($tf->checkUsername("peter") ."\n");

