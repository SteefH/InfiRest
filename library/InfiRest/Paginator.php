<?php

class InfiRest_Paginator {
	private $_requestData;
	private $_objects;
	private $_limit;
	private $_maxLimit;
	private $_offset;
	private $_resourceUri;
	private $_collectionName;
	
	public function __construct(
		$objects, $resourceUri, $limit=null, $offset=0, $maxLimit=1000, $collectionName='objects'
	) {
		$this->_requestData		= $_GET;
		$this->_objects			= $objects;
		$this->_limit			= $limit;
		$this->_maxLimit		= $maxLimit;
		$this->_offset			= $offset;
		$this->_resourceUri		= $resourceUri;
		$this->_collectionName	= $collectionName;
	}
	
	public function getCollectionName() {
		return $this->_collectionName;
	}
	
	protected function _getLimit() {
		$limit = 20;
		if (array_key_exists('limit', $this->_requestData)) {
			$limit = $this->_requestData['limit'];
		} elseif($this->_limit !== null) {
			$limit = $this->_limit;
		}
		
		if (!is_numeric($limit) || ($limit + 0) !== intval($limit) || $limit < 0) {
			throw new InfiRest_Exception_BadRequest('limit must be equal to or greater than zero');
		}
		
		if (($this->_maxLimit !== 0 || $this->_maxLimit !== null) && $limit > $this->_maxLimit) {
			return $this->_maxLimit;
		}
		return intval($limit);
	}
	
	protected function _getOffset() {
		$offset = $this->_offset;
		if (array_key_exists('offset', $this->_requestData)) {
			$offset = $this->_requestData['offset'];
		}
		if (!is_numeric($offset) || ($offset + 0) !== intval($offset) || $offset < 0) {
			throw new InfiRest_Exception_BadRequest('offset must be equal to or greater than zero');
		}
		return intval($offset);
	}
	
	protected function _getSlice($objects, $offset, $limit) {
		if ($limit == 0) {
			return array_slice($objects, $offset);
		}
		return array_slice($objects, $offset, $limit);
	}
	
	protected function _getCount($objects) {
		return count($objects);
	}
	
	
	protected function _generateUri($limit, $offset) {
		if ($this->_resourceUri === null) {
			return null;
		}
		$getParams = $_GET;
		$getParams['limit'] = $limit;
		$getParams['offset'] = $offset;
		return sprintf('%s?%s', $this->_resourceUri, http_build_query($getParams));
	}
	
	protected function _getPrevious($limit, $offset) {
		if ($offset - $limit < 0) {
			return null;
		}
		return $this->_generateUri($limit, $offset - $limit);
	}
	
	protected function _getNext($limit, $offset, $count) {
		if ($offset + $limit >= $count) {
			return null;
		}
		return $this->_generateUri($limit, $offset + $limit);
	}
	
	public function getPage() {
		$limit = $this->_getLimit();
		$offset = $this->_getOffset();
		$count = intval($this->_getCount($this->_objects));
		$objects = $this->_getSlice($this->_objects, $offset, $limit);
		
		$meta = array(
			'offset'		=> $offset,
			'limit'			=> $limit,
			'totalCount'	=> $count
		);
		if ($limit) {
			$meta['previous'] = $this->_getPrevious($limit, $offset);
			$meta['next'] = $this->_getNext($limit, $offset, $count);
		}
		
		$result = array(
			'meta'=>$meta
		);
		$result[$this->_collectionName] = $objects;
		return $result;
	}
	
	
}