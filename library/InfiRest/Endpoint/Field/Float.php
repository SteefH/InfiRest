<?php

class InfiRest_Endpoint_Field_Float
extends InfiRest_Endpoint_Field_Abstract
{
	protected $_typeName = 'float';

	public function convert($value) {
		if ($value === null) {
			return null;
		}
		return floatval($value);
	}
}