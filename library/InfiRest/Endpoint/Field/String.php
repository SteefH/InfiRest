<?php

class InfiRest_Endpoint_Field_String
extends InfiRest_Endpoint_Field_Abstract
{
	protected $_typeName = 'string';

	public function convert($value) {
		if ($value === null) {
			return null;
		}
		return strval($value);
	}
}