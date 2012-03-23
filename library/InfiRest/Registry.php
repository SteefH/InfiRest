<?php

class InfiRest_Registry {
	
	private static $_instance = null;
	
	protected $_apis = null;
	
	private function __construct() {
		$this->_apis = array();
		$this->_setupControllerPath();
	}
	
	private function _setupControllerPath() {
		$controllerDirectory = realpath(dirname(__FILE__) . '/Controller');
		Zend_Controller_Front::getInstance()->addControllerDirectory($controllerDirectory, 'infi-rest_controller');
	}
	
	public static function getInstance() {
		if (self::$_instance === null) {
			self::$_instance = new InfiRest_Registry;
		}
		return self::$_instance;
	}
	
	public function addApi($api) {
		$this->_apis[$api->getName()] = $api;
	}
}