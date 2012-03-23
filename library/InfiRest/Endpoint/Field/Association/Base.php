<?php

class InfiRest_Endpoint_Field_Association_Base
extends InfiRest_Endpoint_Field_Abstract
{
	protected $_relatedEndpointClass	= null;
	protected $_relatedEndpoint		= null;
	
	protected $_full				= false;
	
	public function isRelated() {
		return true;
	}
	
	
	public function __construct($relatedEndpointClass, $options) {
		$defaults = array(
			'nullable'	=> false,
			'blank'		=> false,
			'readOnly'	=> false,
			'unique'	=> false,
			'full'		=> false,
			'helpText'	=> '',
			'relatedEndpointClass' => $relatedEndpointClass
		);
		$options = array_merge($defaults, $options);
		parent::__construct($options);
		$this->_full = $options['full'];
		$this->_relatedEndpointClass = $relatedEndpointClass;
	}
	
	public function convert($value) {
		return $value;
	}
	
	public function isM2M() {
		return false;
	}
	
	protected function _getRelatedEndpoint($relatedObjInstance=null) {
		$class = $this->_relatedEndpointClass;
		$relatedEndpoint = new $class();
		if ($relatedObjInstance) {
			$relatedEndpoint->instance = $relatedObjInstance;
		}
		return $relatedEndpoint;
	}
	
	public function dehydrateRelated($bundle, $relatedEndpoint) {
		if (!$this->_full) {
			return $relatedEndpoint->getResourceUri($bundle);
		}
		$bundle = $relatedEndpoint->buildBundle(
			$bundle->getRequestData(), $relatedEndpoint->instance
		);
		return $relatedEndpoint->fullDehydrate($bundle);
	}
	
	public function resourceFromUri($relatedEndpoint, $uri, $requestParams=null, $relatedObj=null, $relatedName=null) {
		try {
			$obj = $relatedEndpoint->getViaUri($uri, $requestParams);
			$bundle = $relatedEndpoint->buildBundle($requestParams, $obj);
			return $relatedEndpoint->fullDehydrate($bundle);
		} catch (InfiRest_Exception_NotFound $_) {
			throw new InfiRest_Exception_FieldError(
				"Could not find the provided object through ".
				"the resource URI \"${uri}\""
			);
		}
	}
	
	public function resourceFromData($relatedEndpoint, $data, $requestParams=null, $relatedObj=null, $relatedName=null) {
		$relatedBundle = $relatedEndpoint->buildBundle(
			$requestParams, null, $data
		);
		
		if ($relatedObj !== null) {
			$relatedBundle->relatedObj = $relatedObj;
			$relatedBundle->relatedName = $relatedName;
		}
		if (!$relatedEndpoint->canUpdate()) {
			return $relatedEndpoint->fullHydrate($relatedBundle);
		}
			
		try {
			return $relatedEndpoint->objUpdate($relatedBundle, true, $data);
		} catch (InfiRest_Exception_NotFound $_) {
			try {
				$lookupArgs = array();
				foreach($data as $fieldName=>$value) {
					$field = $relatedEndpoint->getField();
					if ($field === null) {
						continue;
					}
					if ($field->isUnique()) {
						$lookupArgs[$fieldName] = $value;
					}
				}
				if (count($lookupArgs) === 0) {
					throw new InfiRest_Exception_NotFound;
				}
				return $relatedEndpoint->objUpdate($relatedBundle, true, $lookupArgs);
			} catch (InfiRest_Exception_NotFound $_) {
				$relatedBundle = $relatedEndpoint->fullHydrate($relatedBundle);
				$relatedEndpoint->isValid($relatedBundle);
				return $relatedBundle;
			}
		} catch (InfiRest_Exception_MultipleFound $_) {
			return $relatedEndpoint->fullHydrate($relatedBundle);
		}
	}
	
	public function resourceFromObj($relatedEndpoint, $obj, $requestParams=null, $relatedObj=null, $relatedName=null) {
		$bundle = $relatedEndpoint->buildBundle($requestParams, $obj);
		return $relatedEndpoint->fullDehydrate($bundle);
	}
	
	public function buildRelatedResource($value, $requestData=null, $relatedObj=null, $relatedName=null) {
		$relatedEndpoint = $this->_getRelatedEndpoint();
		
		if (is_string($value)) {
			// URI
			$method = 'resourceFromUri';
		} elseif (is_array($value)) {
			$method = 'resourceFromData';
		} elseif (is_object($value)) {
			$method = 'resourceFromObj';
		}
		return $this->$method($relatedEndpoint, $value, $requestData, $relatedObj, $relatedName)->getObj();
	}
}