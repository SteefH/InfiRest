<?php

class InfiRest_Application_Resource_Infirest
extends Zend_Application_Resource_ResourceAbstract
{
	public function init() {
		$this->getBootstrap()->bootstrap('frontcontroller');
		$options = $this->getOptions();
		$registry = InfiRest_Registry::getInstance();
		foreach ($options as $module => $config) {
			$api = new InfiRest_Api($module, $config);
			if (array_key_exists('endpoints', $config)) {
				foreach ($config['endpoints'] as $value) {
					$api->registerEndpoint($value);
				}
			}
			$registry->addApi($api);
		}
	}
} 