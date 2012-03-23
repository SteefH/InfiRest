<?php

interface InfiRest_Authentication_Interface {
	function isAuthenticated();
	function getIdentity();
}