<?php

class InfiRest_Exception_BadRequest
extends InfiRest_Exception_Base {
	private $_responseData = null;
	public function __construct($message, $errors=null) {
		if ($errors === null) {
			$errors = array();
		} else {
			$errors = array('errors'=>$errors);
		}
		$this->_responseData = array_merge(
			array('errorMessage'=>$message)
			$errors
		);
	}
	
	public function getResponseData() {
		return $this->_responseData;
	}
}