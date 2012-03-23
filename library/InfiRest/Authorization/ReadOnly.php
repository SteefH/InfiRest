<?php

class InfiRest_Authorization_ReadOnly
implements InfiRest_Authorization_Interface
{
	public function isAuthorized($request, $object=null) {
		return $request->isGet();
	}
}