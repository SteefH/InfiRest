<?php

abstract class InfiRest_Endpoint_Abstract {
	
	protected $_paginatorClass		= 'InfiRest_Paginator';
	protected $_defaultLimit		= 20;
	protected $_maxLimit			= 1000;
	protected $_objectClass			= null;
	protected $_defaultFormat		= 'application/json';
	protected $_includeResourceUri	= true;
	protected $_collectionName		= 'objects';
	
	protected $_alwaysReturnData	= true;
	
	protected $_fields = null;
	
	
	public function __construct() { }

	abstract public function getFields();
	
	abstract protected function _getObjPk($obj);

	protected function _getFields() {
		if ($this->_fields === null) {
			$this->_fields = $this->getFields();
			
			if ($this->_includeResourceUri) {
				$me = $this;
				$this->_fields['resourceUri'] = new InfiRest_Endpoint_Field_String(
					array(
						'getter'=> function ($bundle) use ($me) {
							return $me->getResourceUri($bundle);
						}
					)
				);
				
			}
		}
		return $this->_fields;
	}

	public function getField($fieldName) {
		$fields = $this->_getFields();
		if (array_key_exists($fieldName, $fields)) {
			return $fields[$fieldName];
		}
		return null;
	}

	protected function _createObjectInstance() {
		$class = $this->_objectClass;
		return new $class;
	}

	protected function _getDefaultFormat() {
		return $this->_defaultFormat;
	}

	protected function _getAllowedMethods() {
		return array('get', 'post', 'put', 'delete', 'patch');
	}

	protected function _getAllowedListMethods() {
		return $this->_getAllowedMethods();
	}

	protected function _getAllowedDetailMethods() {
		return $this->_getAllowedMethods();
	}

	protected function _getDefaultLimit() {
		return $this->_defaultLimit;
	}
	
	protected function _getOrdering() {
		return null;
	}

	protected function _getFiltering() {
		return null;
	}
	
	public function getAlwaysReturnData() {
		return $this->_alwaysReturnData;
	}
	
	public function getViaUri($uri) {
		$request = new Zend_Controller_Request_Http();
		$request->setRequestUri($uri);
		Zend_Controller_Front::getInstance()->getRouter()->route($request);
		return $this->objGet($request->getParams());
	}

	public abstract function objGet($lookupArgs);
	public abstract function objGetList();

	public abstract function objDelete($lookupArgs);
	public abstract function objDeleteList();

	public abstract function objCreate($bundle);
	
	public abstract function objUpdate($bundle, $skipErrors, array $lookupArgs);
	
	public function commit() {
		
	}
	public function sortList($list) {
		return $list;
	}
	public function paginateList($requestParams, $list) {
		$paginatorClass = $this->_paginatorClass;
		$api = $requestParams['api'];
		return new $paginatorClass(
			$list,
			$api->getResourceListUri($this),
			$this->_defaultLimit,
			0,
			$this->_maxLimit,
			$this->_collectionName
		);
	}
	
	public function isValid($bundle) {
		return true;
	}
	

	public function getSchema() {
		$result = array(
			'fields' => array(),
			'defaultFormat'				=> $this->_getDefaultFormat(),
			'allowedListHttpMethods'	=> $this->_getAllowedListMethods(),
			'allowedDetailHttpMethods'	=> $this->_getAllowedDetailMethods(),
			'defaultLimit'				=> $this->_getDefaultLimit(),
		);
		$ordering = $this->_getOrdering();
		if ($ordering) {
			$result['ordering'] = $ordering;
		}
		$filtering = $this->_getFiltering();
		if ($filtering) {
			$result['filtering'] = $filtering;
		}

		foreach ($this->_getFields() as $name=>$field) {
			$fieldDef =  array(
				'type'		=> $field->getType(),
				'nullable'	=> $field->isNullable(),
				'blank'		=> $field->blankAllowed(),
				'readonly'	=> $field->isReadOnly(),
				'helpText'	=> $field->getHelpText(),
				'unique'	=> $field->isUnique(),
			);
			if ($field->hasDefault()) {
				$fieldDef['default'] = $field->getDefault();
			}
			$result['fields'][$name] = $fieldDef;
		}
		return $result;
	}

	public function buildBundle($requestData, $obj=null, $data=null) {
		if ($obj === null) {
			$obj = $this->_createObjectInstance();
		}
		return new InfiRest_Bundle($requestData, $obj, $data);
	}

	public function fullDehydrate($bundle) {
		foreach ($this->_getFields() as $fieldName => $field) {
			$value = $field->dehydrate($fieldName, $bundle);
			$bundle->setValue($fieldName, $value);
			$additionalFieldDehydrate = $fieldName.'Dehydrate';
			if (method_exists($this, $additionalFieldDehydrate)) {
				$bundle->setValue(
					$fieldName,
					$this->$additionalFieldDehydrate($bundle)
				);
			}
		}
		$bundle = $this->dehydrate($bundle);
		return $bundle;
	}

	public function dehydrate($bundle) {
		return $bundle;
	}

	public function hydrate($bundle) {
		return $bundle;
	}

	public function fullHydrate($bundle) {
		$obj = $bundle->getObj();
		if ($obj === null) {
			$objectClass = $this->_objectClass;
			$obj = new $objectClass();
			$bundle->setObj($obj);
		}
		$bundle = $this->hydrate($bundle);
		
		foreach ($this->_getFields() as $fieldName => $field) {
			if ($field->isReadOnly()) {
				continue;
			}
			$additionalFieldHydrate = $fieldName.'Hydrate';
			if (method_exists($this, $additionalFieldHydrate)) {
				$bundle = $this->$additionalFieldHydrate($bundle);
			}
			$value = $field->hydrate($fieldName, $bundle);
			if ($value !== null || $field->isNullable()) {
				if (!$field->isRelated()) {
					$field->setValueForBundleObj($bundle, $fieldName, $value);
				} elseif (!$field->isM2M()) {
					if ($value !== null) {
						$field->setValueForBundleObj($bundle, $fieldName, $value);
					} elseif ($field->allowBlank()) {
						continue;
					} elseif ($field->isNullable()) {
						$field->setValueForBundleObj($bundle, $fieldName, $value);
					}
				} else {
					$field->setValueForBundleObj($bundle, $fieldName, $value);
				}
			}
		}
		return $bundle;
	}

	public function getResourceUri($bundle) {
		$requestParams = $bundle->getRequestData();
		$api = $requestParams['api'];
		if ($bundle instanceof InfiRest_Bundle) {
			$obj = $bundle->getObj();
			
			$pk = $this->_getObjPk($obj);
		} else {
			$pk = $this->_getObjPk($bundle);
		}
		return $api->getResourceUri($this, array('pk'=>$pk));
	}
}