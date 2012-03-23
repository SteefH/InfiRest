<?php

class InfiRest_Endpoint_Field_Association_ToOne
extends InfiRest_Endpoint_Field_Association_Base
{
	public function dehydrate($fieldName, $bundle) {
		$associatedObject = $bundle->getObj();
		if ($associatedObject !== null) {
			$associatedObject = $this->getValueFromBundleObj($associatedObject, $fieldName);
		} else {
			$associatedObject = null;
		}
		
		if ($associatedObject === null) {
			if($this->isNullable()) {
				return null;
			} else {
				$obj = $bundle->getObj();
				if (is_object($obj)) {
					$objRepr = get_class($obj);
				} else {
					$objRepr = $obj;
				}
				throw new InfiRest_Exception_FieldError(
					sprintf(
						'The field "%s" of object "%s" can\'t be null and has no default',
						$fieldName,
						$objRepr
					)
				);
			}
		}
		$relatedEndpoint = $this->_getRelatedEndpoint($associatedObject);
		$relatedBundle = new InfiRest_Bundle($bundle->getRequestData(), $associatedObject);
		return $this->dehydrateRelated($relatedBundle, $relatedEndpoint);
	}
	
	public function hydrate($fieldName, $bundle) {
		$value = parent::hydrate($fieldName, $bundle);
		if ($value === null) {
			return null;
		}
		return $this->buildRelatedResource($value, $bundle->getRequestData());
	}
}