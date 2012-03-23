<?php

class InfiRest_Authentication_Zend
implements InfiRest_Authentication_Interface
{
	public function isAuthenticated() {
		return Zend_Auth::getInstance()->hasIdentity();
	};
	public function getIdentity() {
		return Zend_Auth::getInstance()->getIdentity();
	}
}