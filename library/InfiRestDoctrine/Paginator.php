<?php

class InfiRestDoctrine_Paginator
extends InfiRest_Paginator {
	
	protected function _getSlice($objects, $offset, $limit) {
		$objects = $objects->setFirstResult($offset);
		if ($limit) {
			$objects = $objects->setMaxResults($limit);
		}
		return $objects;
	}
	
}