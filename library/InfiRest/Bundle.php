<?php

class InfiRest_Bundle
{
	protected $_requestData = null;
	protected $_obj			= null;
	protected $_data		= null;
	protected $_relatedObj	= null;
	protected $_relatedName	= null;
	protected $_errors		= null;

	public function __construct($requestData=null, $obj=null, $data=null, $relatedObj=null, $relatedName=null) {
		$this->_requestData = $requestData;
		$this->_obj			= $obj;
		$this->_data		= $data === null ? array() : $data;
		$this->_relatedObj	= $relatedObj;
		$this->_relatedName	= $relatedName;
		$this->_errors		= array();
	}
	
	public function setError($key, $error) {
		$this->_errors[$key] = $error;
	}
	
	public function isInvalid() {
		return count($this->_errors) !== 0;
	}

	public function setValue($key, $value) {
		$this->_data[$key] = $value;
		return $this;
	}

	public function hasValue($key) {
		return array_key_exists($key, $this->_data);
	}
	
	public function getValue($key) {
		return $this->_data[$key];
	}

	public function getRequestData() {
		return $this->_requestData;
	}

	public function getData() {
		return $this->_data;
	}
	
	public function setData(array $data) {
		if ($data === null) {
			$data = array();
		}
		$this->_data = $data;
	}

	public function getObj() {
		return $this->_obj;
	}
	
	public function setObj($obj) {
		$this->_obj = $obj;
		return $this;
	}
}