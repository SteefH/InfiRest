<?php

class InfiRest_Exception_NotFound
extends InfiRest_Exception_Base {
	public function __construct() {
		parent::__construct('Resource could not be found');
	}
}