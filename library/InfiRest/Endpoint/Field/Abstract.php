<?php



abstract class InfiRest_Endpoint_Field_Abstract
{
	
	protected static $_noDefault = null;
	protected $_typeName	= '';
	protected $_default		= null;
	protected $_helpText	= '';
	protected $_attribute	= null;
	protected $_blank		= false;
	protected $_readOnly	= false;
	protected $_unique		= false;
	protected $_nullable	= false;

	protected $_getter		= null;
	protected $_setter		= null;
	
	
	public static function noDefault() {
		if (self::$_noDefault === null) {
			self::$_noDefault = new stdClass;
		}
		return self::$_noDefault;
	}

	public function __construct($options=null) {
		$defaults = array(
			'default'	=> self::noDefault(),
			'attribute'	=> null,
			'nullable'	=> false,
			'blank'		=> false,
			'readOnly'	=> false,
			'unique'	=> false,
			'helpText'	=> '',
			'getter'	=> null,
			'setter'	=> null
		);
		if ($options !== null)  {
			$options = array_merge($defaults, $options);
		} else {
			$options = $defaults;
		}
		foreach ($defaults as $key => $_) {
			$prop = '_'.$key;
			$this->$prop = $options[$key];
		}
	}
	
	public function getDefault() {
		return $this->_default;
	}
	public function hasDefault() {
		return $this->_default !== self::noDefault();
	}

	public function getType() {
		return $this->_typeName;
	}

	public function isNullable() {
		return $this->_nullable;
	}

	public function blankAllowed() {
		return $this->_blank;
	}

	public function isReadOnly() {
		return $this->_readOnly;
	}

	public function getHelpText() {
		return $this->_helpText;
	}

	public function isUnique() {
		return $this->_unique;
	}
	
	public function isRelated() {
		// TODO
		return false;
	}

	public function getValueFromBundleObj($bundle, $fieldName) {
		if ($this->_getter) {
			$getter = $this->_getter;
			return $getter($bundle);
		}
		$obj = $bundle->getObj();
		return $obj->$fieldName;
	}
	
	public function setValueForBundleObj($bundle, $fieldName, $value) {
		if ($this->_setter) {
			$setter = $this->_setter;
			$setter($bundle, $value);
			return;
		}
		$obj = $bundle->getObj();
		$obj->$fieldName = $value;
	}
	

	abstract public function convert($value);

	public function dehydrate($fieldName, $bundle) {
		if ($fieldName !== null) {
			
			$obj = $bundle->getObj();
			if ($obj !== null) {
				
				$value = $this->getValueFromBundleObj($bundle, $fieldName);
				
			} else {
				$value = null;
			}
			if ($value === null) {
				if ($this->hasDefault()) {
					$value = $this->getDefault();
				} elseif($this->isNullable()) {
					$value = null;
				} else {
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
			if (is_callable($value)) {
				$value = $value();
			}
			return $this->convert($value);
		}
		if ($this->hasDefault()) {
			return $this->convert($this->getDefault());
		}
		return null;
	}
	
	public function hydrate($fieldName, $bundle) {
		if ($this->isReadOnly()) {
			return null;
		}
		if (!$bundle->hasValue($fieldName)) {
			if ($this->isRelated()) {
				if (property_exists($bundle, 'relatedObj')) {
					return $bundle->relatedObj;
				}
			}
			
			if ($this->blankAllowed()) {
				return null;
			}
			$value = $this->getValueFromBundleObj($bundle, $fieldName);
			if ($value === null) {
				if ($this->hasDefault()) {
					$value = $this->getDefault();
				} elseif ($this->isNullable()) {
					return null;
				}
				if ($value === null) {
					throw new InfiRest_Exception_FieldError(
					sprintf(
						'The field "%s" of object "%s" can\'t be null and has no default',
						$fieldName,
						$objRepr
					)
				);
				}
			}
			if (is_callable($value)) {
				return $value();
			}
			return $value;
		}
		return $bundle->getValue($fieldName);
	}
}