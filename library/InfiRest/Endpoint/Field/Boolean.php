<?php

class InfiRest_Endpoint_Field_Boolean
extends InfiRest_Endpoint_Field_Abstract
{
	protected $_typeName = 'boolean';

	public function convert($value) {
		if ($value === null) {
			return null;
		}
		return $value ? true : false;
	}
}