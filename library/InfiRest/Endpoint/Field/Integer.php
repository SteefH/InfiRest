<?php

class InfiRest_Endpoint_Field_Integer
extends InfiRest_Endpoint_Field_Abstract
{
	protected $_typeName = 'integer';

	public function convert($value) {
		if ($value === null) {
			return null;
		}
		return intval($value, 10);
	}
}