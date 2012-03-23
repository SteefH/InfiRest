<?php

class InfiRest_Serializer_Xml
implements InfiRest_Serializer_Interface {
	
	protected static function _isAssociative($array) {
		$i = 0;
		foreach($array as $key=>$_) {
			if ($key !== $i) {
				return true;
			}
			$i++;
		}
		return false;
	}
	
	protected static function _prepare($value) {
		$tests = array(
			'integer'=> array(
				'is_int',
				null
			),
			'float'=> array(
				'is_float',
				null
			),
			'boolean'=> array(
				'is_bool',
				function ($v) {
					return $v ? 'true' : 'false';
				}
			),
			'null'=> array(
				'is_null',
				null
			),
			'string'=> array(
				'is_string',
				null
			),
			'datetime'=> array(
				function ($v) {
					return $v instanceof DateTime;
				},
				function ($v) {
					return $v->format(DateTime::ISO8601);
				}
			)
		);
		foreach ($tests as $type => $conversion) {
			list($test, $convert) = $conversion;
			if ($test($value)) {
				return array($type, $convert ? $convert($value) : $value);
			}
		}
		return array(null, $value);
	}
	
	protected function _serializeValue($domDocument, $value, $name=null) {
		if ($value instanceof InfiRest_Bundle) {
			return $this->_serializeValue($domDocument, $value->getData(), $name);
		}
		if ($name !== null) {
			$name = str_replace('/', '&#x2f;', $name);
		}
		if (is_array($value)) {
			if (self::_isAssociative($value)) {
				$element = null;
				if ($name !== null) {
					$element = $domDocument->createElement($name);
				} else {
					$element = $domDocument->createElement('object');
				}
				$attribute = $domDocument->createAttribute('type');
				$attribute->value = 'hash';
				$element->appendChild($attribute);
				foreach ($value as $elementName=>$elementValue) {
					$subElement = $this->_serializeValue(
						$domDocument, $elementValue, $elementName
					);
					if ($subElement !== null) {
						$element->appendChild($subElement);
					}
				}
				return $element;
			}
			$element = null;
			if ($name !== null) {
				$element = $domDocument->createElement($name);
				$attribute = $domDocument->createAttribute('type');
				$attribute->value = 'list';
				$element->appendChild($attribute);
			} else {
				$element = $domDocument->createElement('objects');
			}
			foreach ($value as $elementValue) {
				$subElement = $this->_serializeValue(
					$domDocument, $elementValue
				);
				if ($subElement !== null) {
					$element->appendChild($subElement);
				}
			}
			return $element;
		}
		if ($name === null) {
			$name = 'value';
		}
		list($elementType, $elementValue) = self::_prepare($value);
		
		$element = $domDocument->createElement($name, $elementValue);
		if ($elementType !== null) {
			$attribute = $domDocument->createAttribute('type');
			$attribute->value = $elementType;
			$element->appendChild($attribute);
		}
		return $element;
	}
	
	public function serialize($obj) {
		$domDocument = new DOMDocument();
		$domDocument->appendChild($this->_serializeValue($domDocument, $obj, 'response'));
		return $domDocument->saveXML();
	}
	public function deserialize($obj) {
		
	}
}