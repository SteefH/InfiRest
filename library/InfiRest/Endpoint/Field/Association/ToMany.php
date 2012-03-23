<?php

class InfiRest_Endpoint_Field_Association_ToMany
extends InfiRest_Endpoint_Field_Association_Base
{
	
	public function isM2M() {
		return true;
	}
	
	public function dehydrate($fieldName, $bundle) {
		$obj = $bundle->getObj();
		if ($obj === null) {
			if (!$this->isNullable()) {
				throw new InfiRest_Exception_FieldError(
					"${fieldName} can not be used in a ToMany context"
				);
			}
			return array();
		}
		$theM2Ms = $this->getValueFromBundleObj($obj, $fieldName);
		if ($theM2Ms === null) {
			if (!$this->isNullable()) {
				throw new InfiRest_Exception_FieldError(
					"${fieldName} can not be empty"
				);
			}
			return array();
		}
		$dehydrated = array();
		foreach ($theM2Ms as $relatedInstance) {
			$relatedEndpoint = $this->_getRelatedEndpoint($relatedInstance);
			$relatedBundle = new InfiRest_Bundle($bundle->getRequestData(), $relatedInstance);
			$dehydrated[] = $this->dehydrateRelated($relatedBundle, $relatedEndpoint);
		}
		return $dehydrated;
	}
	
	public function hydrate($fieldName, $bundle) {
		if ($this->isReadOnly()) {
			return null;
		}
		$values = $bundle->getValue($fieldName);
		if ($values === null) {
			if ($this->blankAllowed()) {
				return array();
			}
			if ($this->isNullable()) {
				return array();
			}
			throw new InfiRest_Exception_FieldError(
				"The ${fieldName} has no data and can not be null"
			);
		}
		$hydrated = array();
		foreach ($values as $value) {
			if ($value === null) {
				continue;
			}
			$requestData = $bundle->getRequestData();
			$hydrated[] = $this->buildRelatedResource($value, $requestData);
		}
		return $hydrated;
	}
}