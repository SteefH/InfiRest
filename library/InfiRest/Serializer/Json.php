<?php

class InfiRest_Serializer_Json
extends Zend_Json_Encoder
implements InfiRest_Serializer_Interface
{
	public function __construct() {
		Zend_Json::$useBuiltinEncoderDecoder = false;
		parent::__construct();
		
	}
	
	

	public function serialize($obj) {
		return $this->_encodeValue($obj);
	}

	public function deserialize($obj) {
		$decoder = new Zend_Json();
		return $decoder->decode($obj);
	}
	protected function _encodeValue(&$value) {
		if ($value instanceof InfiRest_Bundle) {
			$value = $value->getData();
		} elseif ($value instanceof DateTime) {
			$value =$value->format(DateTime::ISO8601);
		}
		return parent::_encodeValue($value);
	}
}