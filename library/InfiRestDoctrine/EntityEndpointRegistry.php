<?php

class InfiRestDoctrine_EntityEndpointRegistry {
	
	private static $_instance = null;
	
	private static $_endpointsByClass = null;
	
	
	public static function registerEndpoint($entityClassName, $endpoint) {
		if (self::$_endpointsByClass === null) {
			self::$_endpointsByClass = array();
		}
		if (is_object($endpoint)) {
			$endpointClass = get_class($endpoint);
		} else {
			$endpointClass = $endpoint;
		}
		if (!array_key_exists($entityClassName, self::$_endpointsByClass)) {
			self::$_endpointsByClass[$entityClassName] = array();
		}
		self::$_endpointsByClass[$entityClassName][$endpointClass] = $endpointClass;
	}
	
	public static function getEndpointsForEntityClass($entityClassName) {
		if (self::$_endpointsByClass === null) {
			return array();
		}
		if (!array_key_exists($entityClassName, self::$_endpointsByClass)) {
			return array();
		}
		return array_values(self::$_endpointsByClass[$entityClassName]);
	}
}