<?php

class InfiRest_Authentication_None
implements InfiRest_Authentication_Interface
{	
	public function isAuthenticated() {
		return true;
	}
	
	public function getIdentity() {
		// return hostname + ip
		$r = Zend_Controller_Front::getInstance()->getRequest();
		return $r->getClientIp().'::'.$r->getServer('REMOTE_HOST');
	}
}