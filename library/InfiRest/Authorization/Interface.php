<?php

interface InfiRest_Authorization_Interface {
	function isAuthorized($request, $object=null);
}