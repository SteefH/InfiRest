<?php

use \Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\ORM\PersistentCollection;
use \Doctrine\ORM\Mapping\ClassMetadata;

abstract class InfiRestDoctrine_Endpoint
extends InfiRest_Endpoint_Abstract
{
	protected $_paginatorClass = 'InfiRestDoctrine_Paginator';
	
	private $_entityClassMetadata = null;
	private $_pkField = null;
	
	abstract function getEntityManager();
	
	public function __construct() {
		parent::__construct();
		InfiRestDoctrine_EntityEndpointRegistry::registerEndpoint(
			$this->_objectClass, $this
		);
	}
	
	private static $_typemappings = null;
	
	protected static function _dbalTypeToResourceFieldType($dbalType) {
		if (self::$_typemappings === null) {
			self::$_typemappings = array(
				'ArrayType' => 'Array',
				'BigIntType' => 'Integer',
				'BooleanType' => 'Boolean',
				'DateTimeType' => 'DateTime',
				'DateType' => 'DateTime',
				'FloatType' => 'FloatType',
				'IntegerType' => 'Integer',
				'ObjectType' => 'Object',
				'SmallIntType' => 'Integer',
				'StringType' => 'String',
				'TextType' => 'String',
				'TimeType' => null,
				'VarDateTimeType' => 'DateTime'
			);
		}
		$typesMap = \Doctrine\DBAL\Types\Type::getTypesMap();
		foreach(self::$_typemappings as $srcType => $destType) {
			$srcType = 'Doctrine\DBAL\Types\\'.$srcType;
			if ($typesMap[$dbalType] === $srcType) {
				return 'InfiRest_Endpoint_Field_'.$destType;
			}
		}
	}
	
	protected function _getExcludes() {
		return array();
	}
	
	protected function _getIncludes() {
		return null;
	}
	
	protected function _getEntityClassMetadata() {
		if ($this->_entityClassMetadata === null) {
			$em = $this->getEntityManager();
			$mdf = $em->getMetadataFactory();
			$this->_entityClassMetadata = $mdf->getMetadataFor($this->_objectClass);
		}
		return $this->_entityClassMetadata;
	}
	
	protected function getTargetEndpoint($targetEntity) {
		$endpointsForClass = InfiRestDoctrine_EntityEndpointRegistry::getEndpointsForEntityClass($targetEntity);
		if (count($endpointsForClass) > 1) {
			throw new InfiRest_Exception_FieldError(
				sprintf(
					"Cannot resolve endpoint for %s::%s because ".
					"there is more than one endpoint defined for ".
					"the %s entity class",
					get_class($this), $fieldName,
					$mapping['targetEntity']
				)
			);
		}
		if (count($endpointsForClass) === 0) {
			throw new InfiRest_Exception_FieldError(
				sprintf(
					"Cannot resolve endpoint for %s::%s because ".
					"there is no endpoint defined for ".
					"the %s entity class",
					get_class($this), $fieldName,
					$mapping['targetEntity']
				)
			);
		}
		return $endpointsForClass[0];
	}
	
	public function getFields() {
		$metadata = $this->_getEntityClassMetadata();
		
		$excludes = array_fill_keys($this->_getExcludes(), 1);
		$includes = $this->_getIncludes();
		if ($includes !== null) {
			$includes = array_fill_keys($includes, 1);
		}
		$fields = array();
		$associationMapping = $metadata->getAssociationMappings();
		
		foreach ($metadata->getReflectionProperties() as $fieldName => $_) {
			if (
				($includes !== null && !array_key_exists($fieldName, $includes))
				|| array_key_exists($fieldName, $excludes)
			) {
				
				continue;
			}
			if (array_key_exists($fieldName, $associationMapping)) {
				$mapping = $associationMapping[$fieldName];
				$endpoint = $this->getTargetEndpoint($mapping['targetEntity']);
				if (($mapping['type'] & ClassMetadata::TO_ONE)  !== 0) {
					$unique = true;
					$nullable = true;
					foreach ($mapping['joinColumns'] as $column) {
						$unique = $unique && $column['unique'];
						$nullable = $nullable && $column['nullable'];
					}
					$fieldObj = new InfiRest_Endpoint_Field_Association_ToOne(
						$endpoint,
						array(
							'nullable' => $nullable,
							'unique' => $unique,
							'getter' => $this->_createGetter($fieldName, $metadata),
							'setter' => $this->_createSetter($fieldName, $metadata),
						)
					);
				} elseif ($mapping['type'] == ClassMetadata::MANY_TO_MANY) {
					$fieldObj = new InfiRest_Endpoint_Field_Association_ToMany(
						$endpoint,
						array(
							'nullable' => false,
							'unique' => false,
							'getter' => $this->_createGetter($fieldName, $metadata),
							'setter' => $this->_createM2MSetter($fieldName, $metadata),
						)
					);
				} elseif ($mapping['type'] == ClassMetadata::ONE_TO_MANY) {
					$fieldObj = new InfiRestDoctrine_Field_ToMany(
						$endpoint,
						array(
							'nullable' => false,
							'unique' => false,
							'getter' => $this->_createGetter($fieldName, $metadata),
							'setter' => $this->_createO2MSetter($fieldName, $metadata),
						)
					);
				}
			} else {
				$fieldOptions = array(
					'nullable' => $metadata->isNullable($fieldName),
					'unique' => $metadata->isUniqueField($fieldName),
					'getter' => $this->_createGetter($fieldName, $metadata),
					'setter' => $this->_createSetter($fieldName, $metadata),
				);
				
				$fieldClass = self::_dbalTypeToResourceFieldType(
					$metadata->getTypeOfField($fieldName)
				);
				$fieldObj = new $fieldClass($fieldOptions);
			}
			$fields[$fieldName] = $fieldObj;
		}
		return $fields;
	}
	
	protected function _createGetter($fieldName, $metadata) {
		$getter = function ($bundle) use ($fieldName, $metadata) {
			if ($bundle instanceof InfiRest_Bundle) {
				$obj = $bundle->getObj();
			} else {
				$obj = $bundle;
			}
			if ($obj instanceof \Doctrine\ORM\Proxy\Proxy) {
				$obj->__load();
			}
			return $metadata->getFieldValue($obj, $fieldName);
		};
		return $getter;
	}
	
	protected function _createSetter($fieldName, $metadata) {
		$setter = function ($bundle, $value) use ($fieldName, $metadata) {
			if ($bundle instanceof InfiRest_Bundle) {
				$obj = $bundle->getObj();
			} else {
				$obj = $bundle;
			}
			if ($obj instanceof \Doctrine\ORM\Proxy\Proxy) {
				$obj->__load();
			}
			$metadata->setFieldValue($obj, $fieldName, $value);
		};
		return $setter;
	}
	
	protected function _createM2MSetter($fieldName, $metadata) {
		
		$targetField = $metadata->getAssociationMappedByTargetField($fieldName);
		if ($targetField === null) {
			return $this->_createSetter($fieldName, $metadata);
		}
		
		$targetEntity = $metadata->getAssociationTargetClass($fieldName);
		$em = $this->getEntityManager();
		$mdf = $em->getMetadataFactory();
		$targetMeta = $mdf->getMetadataFor($targetEntity);
		
		
		return function ($bundle, $value) use ($fieldName, $metadata, $targetMeta, $targetField) {
			if ($bundle instanceof InfiRest_Bundle) {
				$obj = $bundle->getObj();
			} else {
				$obj = $bundle;
			}
			if ($obj instanceof \Doctrine\ORM\Proxy\Proxy) {
				$obj->__load();
			}
			foreach ($value as $targetItem) {
				// update each association target entity's inverse mapping field
				if ($targetItem instanceof \Doctrine\ORM\Proxy\Proxy) {
					$targetItem->__load();
				}
				$inverseValue = $targetMeta->getFieldValue($targetItem, $targetField);
				
				if (
					!$inverseValue instanceof ArrayCollection
					&& !$inverseValue instanceof PersistentCollection
				) {
					if (is_array($inverseValue)) {
						$inverseValue = new ArrayCollection($inverseValue);
					} else {
						$inverseValue = new ArrayCollection();
					}
				}
				$inverseValue->add($obj);
				$targetMeta->setFieldValue($targetItem, $targetField, $inverseValue);
			}
			$metadata->setFieldValue($obj, $fieldName, $value);
		};
	}

	protected function _createO2MSetter($fieldName, $metadata) {
		
		$targetField = $metadata->getAssociationMappedByTargetField($fieldName);
		if ($targetField === null) {
			return $this->_createSetter($fieldName, $metadata);
		}
		
		$targetEntity = $metadata->getAssociationTargetClass($fieldName);
		$em = $this->getEntityManager();
		$mdf = $em->getMetadataFactory();
		$targetMeta = $mdf->getMetadataFor($targetEntity);
		
		
		return function ($bundle, $value) use ($fieldName, $metadata, $targetMeta, $targetField) {
			if ($bundle instanceof InfiRest_Bundle) {
				$obj = $bundle->getObj();
			} else {
				$obj = $bundle;
			}
			if ($obj instanceof \Doctrine\ORM\Proxy\Proxy) {
				$obj->__load();
			}
			$targetsToRemove = array();
			$oldTargets = $metadata->getFieldValue($obj, $fieldName);
			if ($oldTargets !== null) {
				foreach ($oldTargets as $target) {
					if ($target instanceof \Doctrine\ORM\Proxy\Proxy) {
						$target->__load();
					}
					$targetsToRemove[serialize($targetMeta->getIdentifierValues($target))] = $target;
				}
			}
			foreach ($value as $target) {
				// update each association target entity's inverse mapping field
				if ($target instanceof \Doctrine\ORM\Proxy\Proxy) {
					$target->__load();
				}
				$targetKey = serialize($targetMeta->getIdentifierValues($target));
				if (array_key_exists($targetKey, $targetsToRemove)) {
					// target remains associated
					unset($targetsToRemove[$targetKey]);
					continue;
				}
				$targetMeta->setFieldValue($target, $targetField, $obj);
			}
			foreach ($targetsToRemove as $target) {
				$targetMeta->setFieldValue($target, $targetField, null);
			}
			$metadata->setFieldValue($obj, $fieldName, $value);
		};
	}
	

	protected function _objHasProperty($obj, $property) {
		if ($obj instanceof InfiRest_Bundle) {
			$obj = $obj->getObj();
		}
		return array_key_exists($property, $this->_getFields()) ||
			property_exists($obj, $property);
	}

	protected function _getObjPk($obj) {
		$metadata = $this->_getEntityClassMetadata();
		$pkField = $metadata->getIdentifier();
		$pkField = $pkField[0];
		if ($obj instanceof \Doctrine\ORM\Proxy\Proxy) {
			$obj->__load();
		}
		return $metadata->getFieldValue($obj, $pkField);
	}

	public function commit() {
		$this->getEntityManager()->flush();
	}

	public function objGet($lookupArgs) {
		if (array_key_exists('pk', $lookupArgs)) {
			/*$lookupArgs[$this->_pkField] = $lookupArgs['pk'];
			unset($lookupArgs['pk']);*/
		}
		$obj = $this->getEntityManager()
			->getRepository($this->_objectClass)
			->find($lookupArgs['pk']);
		if ($obj === null) {
			throw new InfiRest_Exception_NotFound();
		}
		return $obj;
	}
	public function objGetList() {
		return new InfiRestDoctrine_QueryBuilderWrapper(
			$this->getEntityManager()
				->getRepository($this->_objectClass)
				->createQueryBuilder('items')
				->select('items')
		);
	} 

	public function objDelete($lookupArgs) {
		$obj = $this->objGet($lookupArgs);
		$this->getEntityManager()->remove($obj);
	}
	public function objDeleteList() {}

	public function objCreate($bundle) {
		$objectClass = $this->_objectClass;
		$obj = new $objectClass();
		$bundle->setObj($obj);
		$bundle = $this->fullHydrate($bundle);
		$this->isValid($bundle);
		if ($bundle->isInvalid()) {
			throw new InfiRest_Exception_BadRequest('', $bundle->getErrors());
		}
		$this->getEntityManager()->persist($bundle->getObj());
		// TODO save m2m
		return $bundle;
	}
	
	public function objUpdate($bundle, $skipErrors=false, array $lookupArgs=null) {
		$obj = $bundle->getObj();
		if ($obj === null || !$this->_getObjPk($obj)) {
			try {
				$objectClass = $this->_objectClass;
				$obj = $this->_createObjectInstance();
				$bundle->setObj($obj);
				
				if ($lookupArgs) {
					$bundle->setData(array_merge($bundle->getData(), $lookupArgs));
					$newLookupArgs = $lookupArgs;
				} else {
					$newLookupArgs = array();
				}
				$bundle = $this->fullHydrate($bundle);
				
				foreach ($lookupArgs as $key => $_) {
					if ($key == 'pk') {
						continue;
					}
					if ($this->_objHasProperty($obj, $key)) {
						$newLookupArgs[$key] = $obj->$key;
					}
				}
			} catch (Exception $e) {
				$newLookupArgs = $lookupArgs;
			}
			$bundle->setObj($this->objGet($newLookupArgs));
		}
		$bundle = $this->fullHydrate($bundle);
		$this->isValid($bundle);
		if ($bundle->isInvalid() && !$skipErrors) {
			throw new InfiRest_Exception_BadRequest('', $bundle->getErrors());
		}
		$this->getEntityManager()->persist($bundle->getObj());
		return $bundle;
	}

}