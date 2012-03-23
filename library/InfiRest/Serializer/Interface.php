<?php

interface InfiRest_Serializer_Interface {
	function serialize($obj);
	function deserialize($obj);
}