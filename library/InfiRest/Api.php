<?php

class InfiRest_Api
{
	private $_name = '';
	private $_baseUrl = '';
	private $_endpoints = null;
	private $_endpointLoader = null;
	private $_endpointNames = null;
	
	
	public function __construct($apiName, $config=null) {
		$this->_name = $apiName;
		$this->_endpoints = array();
		$this->_endpointNames = array();
		$frontController = Zend_Controller_Front::getInstance();
		
		if (array_key_exists('endpointDirectory', $config)) {
			$endpointDirectory = $config['endpointDirectory'];
		} else {
			$endpointDirectory = $frontController->getModuleDirectory($apiName).'/endpoints';
		}
		
		$defaults = array(
			'baseUrl' => $apiName.'/api',
			'endpoints' => array()
		);
		
		if ($config !== null) {
			$config = array_merge($defaults, $config);
		} else {
			$config = $defaults;
		}
		
		$this->_baseUrl = $config['baseUrl'];
		
		if (substr($this->_baseUrl, -1) === '/') {
			$this->_baseUrl = substr($this->_baseUrl, 0, -1);
		}

		$this->_setupRootRoute();
		$this->_setupEndpointLoader($endpointDirectory);
	}
	
	public function getName() {
		return $this->_name;
	}

	protected function _setupRootRoute() {
		$router = Zend_Controller_Front::getInstance()->getRouter();
		$router->addRoute(
			'infi-rest-'.$this->_name,
			new Zend_Controller_Router_Route_Static(
				$this->_baseUrl,
				array(
					'module'		=> 'infi-rest_controller',
					'controller'	=> 'infi-rest',
					'action'		=> 'root',
					'api'			=> $this,
				)
			)
		);
	}
	
	protected function _setupRoutes($endpointName) {
		$baseRouteOptions = array(
			'module'		=> 'infi-rest_controller',
			'api'			=> $this,
			'controller'	=> 'infi-rest',
			'pk'			=> '',
			'set'			=> ''
		);
		
		$routeSettings = array(
			'list' => array(
				're'				=> '/(%s)',
				'name'				=> 'infi-rest-list-%s-%s',
				'action'			=> 'list',
				'reGroups'			=> array(1=>'endpoint'),
				'reverse'			=> '/%s/',
			),
			'detail' => array(
				're'				=> '/(%s)/(\d+)',
				'name'				=> 'infi-rest-detail-%s-%s',
				'action'			=> 'detail',
				'reGroups'			=> array(1=>'endpoint', 2=>'pk'),
				'reverse'			=> '/%s/%%d/',
			),
			'schema' => array(
				're'				=> '/(%s)/schema',
				'name'				=> 'infi-rest-schema-%s-%s',
				'action'			=> 'schema',
				'reGroups'			=> array(1=>'endpoint'),
				'reverse'			=> '/%s/schema/',
			),
			'set' => array(
				're'				=> '/(%s)/set/((?:\d+;)*\d+)',
				'name'				=> 'infi-rest-set-%s-%s',
				'action'			=> 'set',
				'reGroups'			=> array(1=>'endpoint', 2=>'set'),
				'reverse'			=> '/%s/set/%%s/',
			),
		);
		
		$router = Zend_Controller_Front::getInstance()->getRouter();
		foreach ($routeSettings as $action => $settings) {
			$re = $settings['re'];
			$re = $this->_baseUrl.sprintf($re, $endpointName);
			$routeOptions = $baseRouteOptions;
			$routeOptions['action'] = $action;
			$route = new Zend_Controller_Router_Route_Regex(
				$re,
				$routeOptions,
				@$settings['reGroups'],
				$this->_baseUrl.sprintf($settings['reverse'], $endpointName)
			);
			$routeName = sprintf($settings['name'], $this->_name, $endpointName);
			$router->addRoute($routeName, $route);
		}
	}
	
	protected function _setupEndpointLoader($endpointDirectory) {
		$this->_endpointLoader = new InfiRest_EndpointLoader($this, $endpointDirectory);
	}
	
	public function getEndpointLoader() {
		return $this->_endpointLoader;
	}
	
	public function getEndpoint($name) {
		if (array_key_exists($name, $this->_endpoints)) {
			return $this->_endpointLoader->getEndpoint($name);
		}
	}

	public function registerEndpoint($endpointName) {
		if (!array_key_exists($endpointName, $this->_endpoints)) {
			$this->_setupRoutes($endpointName);
		}
		$this->_endpoints[$endpointName] = $endpointName;
		$endpoint = $this->getEndpoint($endpointName);
		$this->_endpointNames[get_class($endpoint)] = $endpointName;
	}

	public function getEndpoints() {
		return array_values($this->_endpoints);
	}
	
	public function getResourceUri($endpoint, $params) {
		$router = Zend_Controller_Front::getInstance()->getRouter();
		
		$routeName = sprintf(
			'infi-rest-detail-%s-%s',
			$this->_name,
			$this->_endpointNames[get_class($endpoint)]
		);
		return $router->assemble(array($params['pk']), $routeName);
	}
	
	public function getResourceListUri($endpoint) {
		$router = Zend_Controller_Front::getInstance()->getRouter();
		$routeName = sprintf(
			'infi-rest-list-%s-%s',
			$this->_name,
			$this->_endpointNames[get_class($endpoint)]
		);
		return $router->assemble(array(), $routeName);
	}
	
}