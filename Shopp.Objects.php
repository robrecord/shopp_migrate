<?php

/**
*
*/
class SM_Shopp_Meta
{
	public int $parent;
	public string $context;
	public string $type;
	public string $name;
	public string $value;

	function __construct($name, $value, $type, $context, $parent, $created = null, $modified = null)
	{
		$this->name = $name;
		$this->value = $value;
		$this->type = $type;
		$this->context = (string) $context;
		$this->parent - (int) $parent;
		if ($created) $this->created = $created;
		if ($modified) $this->modified = $modified;
	}
}
