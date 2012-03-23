<?php

class InfiRestDoctrine_QueryBuilderWrapper
extends ArrayObject 
{

	protected $_queryBuilder = null;
	protected $_queryResults = null;

	public function __construct($queryBuilder) {
		parent::__construct();
		$this->_queryBuilder = $queryBuilder;
	}
	
	public function __call($name, $arguments) {
		if (method_exists($this->_queryBuilder, $name)) {
			$result = call_user_func_array(
				array($this->_queryBuilder, $name), $arguments
			);
			if ($result instanceOf \Doctrine\Orm\QueryBuilder) {
				return new InfiRestDoctrine_QueryBuilderWrapper($result);
			}
			return $result;
		}
	}
	
	protected function _materialized() {
		if ($this->_queryResults === null) {
			$this->_queryResults = new ArrayObject(
				$this->_queryBuilder->getQuery()->getResult()
			);
		}
		return $this->_queryResults;
	}
	
	public function append($value) {
		return $this->_materialized()->append($value);
	}

	public function asort() {
		return $this->_materialized()->asort();
	}

	public function count() {
		$clone =  clone $this->_queryBuilder;
		return $clone->select('count(items)')->getQuery()->getSingleScalarResult();
	}

	public function exchangeArray($input) {
		return $this->_materialized()->exchangeArray($input);
	}
		
	public function getArrayCopy() {
		return $this->_materialized()->getArrayCopy();
	}
	
	public function getFlags() {
		return $this->_materialized()->getFlags();
	}
	
	public function getIterator() {
		return $this->_materialized()->getIterator();
	}
	
	public function getIteratorClass() {
		return $this->_materialized()->getIteratorClass();
	}
	
	public function ksort() {
		return $this->_materialized()->ksort();
	}
	
	public function natcasesort() {
		return $this->_materialized()->natcasesort();
	}
	
	public function natsort() {
		return $this->_materialized()->natsort();
	}
	
	public function offsetExists($index) {
		return $this->_materialized()->offsetExists($index);
	}
	
	public function offsetGet($index) {
		return $this->_materialized()->offsetGet();
	}
	
	public function offsetSet($index, $newval) {
		return $this->_materialized()->offsetSet($index);
	}
	
	public function offsetUnset($index) {
		return $this->_materialized()->offsetUnset();
	}
	
	public function serialize() {
		return $this->_materialized()->serialize();
	}
	
	public function setFlags($flags) {
		return $this->_materialized()->setFlags($flags);
	}
	
	public function setIteratorClass($iterator_class) {
		return $this->_materialized()->setIteratorClass($iterator_class);
	}
	
	public function uasort($cmp_function) {
		return $this->_materialized()->uasort($cmp_function);
	}
	
	public function uksort($cmp_function) {
		return $this->_materialized()->uksort($cmp_function);
	}
	
	public function unserialize($serialized) {
		return $this->_materialized()->unserialize($serialized);
	}
	
	
	public function __clone() {
		$this->_queryBuilder = clone $this->_queryBuilder;
		if ($this->_queryResults !== null) {
			$this->_queryResults = clone $this->_queryResults;
		}
	}
}