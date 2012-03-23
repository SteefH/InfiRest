<?php

class InfiRest_Exception_UnknownField
extends Exception
{
	public function __construct($name='') {
		parent::__construct('Unknown field: "'.$name.'"');
	}
}