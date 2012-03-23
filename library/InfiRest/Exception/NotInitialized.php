<?php

class InfiRest_Exception_NotInitialized
extends Exception
{
	public function __construct($name='') {
		parent::__construct('Field is not initialized: "'.$name.'"');
	}
}