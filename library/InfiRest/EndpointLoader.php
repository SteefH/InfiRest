<?php

class InfiRest_EndpointLoader {
	
	private $_api			= null;
	private $_endpoints 		= null;
	private $_endpointBaseDir	= null;
	
	public function __construct($api, $endpointBaseDir) {
		$this->_endpoints		= array();
		$this->_api				= $api;
		$this->_endpointBaseDir	= $endpointBaseDir;
	}

	public function getEndpoint($endpointName) {
		if (!array_key_exists($endpointName, $this->_endpoints)) {
			$this->_endpoints[$endpointName] =  $this->_loadEndpoint($endpointName);
		}
		return $this->_endpoints[$endpointName];
	}
	
	protected function _nameToClass($name) {
		$name = str_replace('/', '_', $name);
		$result = implode('', array_map('ucfirst', explode('-', $name)));
		return implode('_', array_map('ucfirst', explode('_', $result)));
	}
	
	protected function _loadEndpoint($endpointName) {
		$endpointClass = $this->_nameToClass($endpointName).'Endpoint';
		$apiName = $this->_api->getName();
		if ($apiName == 'default') {
			$prefix = '';
		} else {
			$prefix = $this->_nameToClass($apiName).'_';
		}
		$className = $prefix.$endpointClass;
		
		if (!class_exists($className, false)) {
			$filename = str_replace('_', DIRECTORY_SEPARATOR, $endpointClass).'.php';
			include_once $this->_endpointBaseDir.DIRECTORY_SEPARATOR.$filename;
		}
		return new $className($endpointName, $this->_api);
		
	}
}