<?php

class InfiRest_Endpoint_Field_Array
extends InfiRest_Endpoint_Field_Abstract
{
	protected $_typeName = 'array';

	public function convert($value) {
		if ($value === null) {
			if ($this->isNullable()) {
				return null;
			}
			return array();
		}
		return $value;
	}
}