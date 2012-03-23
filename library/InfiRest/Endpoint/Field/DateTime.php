<?php

class InfiRest_Endpoint_Field_DateTime
extends InfiRest_Endpoint_Field_Abstract
{
	protected $_typeName = 'datetime';

	public function convert($value) {
		if ($value === null) {
			return null;
		}
		if (is_string($value)) {
			$dateTime = DateTime::createFromFormat(DateTime::ISO8601, $value);
		}
		return $value;
	}
	
	public function hydrate($fieldName, $bundle) {
		$value = parent::hydrate($fieldName, $bundle);
		if ($value !== null && !($value instanceof DateTime)) {
			$value = DateTime::createFromFormat(DateTime::ISO8601, $value);
		}
		return $value;
	}
}