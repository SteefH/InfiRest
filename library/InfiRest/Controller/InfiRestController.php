<?php

class InfiRest_Controller_InfiRestController
extends Zend_Controller_Action
{
	const RESPONSE_CODE_OK					= 200;
	const RESPONSE_CODE_CREATED				= 201;
	const RESPONSE_CODE_ACCEPTED			= 202;
	const RESPONSE_CODE_NO_CONTENT			= 204;
	const RESPONSE_CODE_MULTIPLE_CHOICES	= 300;
	const RESPONSE_CODE_SEE_OTHER			= 303;
	const RESPONSE_CODE_NOT_MODIFIED		= 304;
	const RESPONSE_CODE_BAD_REQUEST			= 400;
	const RESPONSE_CODE_UNAUTHORIZED		= 401;
	const RESPONSE_CODE_FORBIDDEN			= 403;
	const RESPONSE_CODE_NOT_FOUND			= 404;
	const RESPONSE_CODE_METHOD_NOT_ALLOWED	= 405;
	const RESPONSE_CODE_CONFLICT			= 409;
	const RESPONSE_CODE_GONE				= 410;
	const RESPONSE_CODE_APPLICATION_ERROR	= 500;
	const RESPONSE_CODE_NOT_IMPLEMENTED		= 501;

	protected $_resource = null;
	protected $_serializers = null;
	protected static $_formatMapping = null;


	public function init() {
		$this->_helper->viewRenderer->setNoRender(true);
	}
	
	protected function _initErrorHandler() {
		set_error_handler(
			function($errno, $errstr, $errfile, $errline, $errcontext) {
				throw new Exception($errstr, $errno);
			}
		);
	}

	protected function _handleException($exception) {
		throw $exception;
		try {
			if ($exception instanceof InfiRest_Exception_NotFound) {
				$this->sendResponse(self::RESPONSE_CODE_NOT_FOUND, array(
					'errorMessage' => $exception->getMessage()
				));
			}
			
			if ($exception instanceof InfiRest_Exception_BadRequest) {
				$this->sendResponse(
					self::RESPONSE_CODE_BAD_REQUEST, 
					$exception->getResponseData()
				);
			}

			if ($this->getInvokeArg('displayExceptions') == true) {
				$data = array(
					'errorMessage' => $exception->getMessage(),
					'traceback' => $exception->getTraceAsString(),
					'requestParameters' => $this->getRequest()->getParams(),
				);
				$this->sendResponse(self::RESPONSE_CODE_APPLICATION_ERROR, $data);
			}
			$this->sendResponse(
				self::RESPONSE_CODE_APPLICATION_ERROR,
				array(
					'errorMessage'=>
						"Sorry, this request could not be processed. ".
						"Please try again later."
				)
			);
		} catch (Exception $e) {
			// failed to send error response,
			// let Zend take over as a last resort
			throw $exception;
		}
	}

	public function sendResponse($responseCode, $data=null, $location=null) {
		$response = $this->getResponse()
			->setHttpResponseCode($responseCode)
			->clearHeaders()
			->setHeader('Cache-Control', 'no-cache, must-revalidate')
			->setHeader('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
		if ($location !== null) {
			$response->setHeader('Location', $location);
		}
		if ($data !== null) {
			$format = $this->_getParam('serializeFormat');
			$response
				->setHeader('Content-Type', $format)
				->setBody($this->_getSerializer($format)->serialize($data));
		}
		$response->sendResponse();
		exit;
	}

	protected function _initEndpoint() {
		$request = $this->getRequest();
		
		if ($request->endpoint) {
			$endpointName = $request->endpoint;
			$request->setParam(
				'endpoint', $request->api->getEndpoint($request->endpoint)
			);
			$request->setParam('endpointName', $endpointName);
		}
	}

	protected function _initSet() {
		$request = $this->getRequest();
		if ($request->set) {
			$request->setParam('set', explode(';', $request->set));
		}
	}
	
	
	protected static function _getFormatMimeTypeMapping() {
		if (self::$_formatMapping === null) {
			self::$_formatMapping = array(
				'json' => 'application/json',
				'xml' => 'application/xml'
			);
		}
		return self::$_formatMapping;
	}

	protected static function _getFormatMimeType($format) {
		$mapping = self::_getFormatMimeTypeMapping();
		if (array_key_exists($format, $mapping)) {
			return $mapping[$format];
		}
		return null;
	}

	protected function _getSerializeFormat() {
		$format = $this->_getParam('format');
		if ($format) {
			return self::_getFormatMimeType($format);
		}
		$acceptHeaderFields = Zend_Mime_Decode::splitContentType(
			$this->getRequest()->getHeader('Accept')
		);
		if (array_key_exists('type', $acceptHeaderFields)) {
			$formats = array_map('trim', explode(',', $acceptHeaderFields['type']));
		}
		$weights = array();
		if (array_key_exists('q', $acceptHeaderFields)) {
			$weights = array_map('floatval', explode(',', $acceptHeaderFields['q']));
			// contains a weight field
		}
		$weightedFormats = array();
		foreach($formats as $index=>$format) {
			$weightedFormats[] = array(
				'format'=>$format,
				'weight'=> array_key_exists($index, $weights) ? $weights[$index] : 0
			);
		}
		usort(
			$weightedFormats,
			function ($item1, $item2) {
				$weight1 = $item1['weight'];
				$weight2 = $item2['weight'];
				if ($weight1 > $weight2) {
					return 1;
				}
				if ($weight2 > $weight1) {
					return -1;
				}
				return 0;
			}
		);
		$mapping = self::_getFormatMimeTypeMapping();
		$mimeTypes = array_fill_keys(array_values($mapping), 1);
		foreach ($weightedFormats as $value) {
			$format = $value['format'];
			if (array_key_exists($format, $mimeTypes)) {
				return $format;
			}
		}
		return null;
	}

	protected function _getDeserializeFormat() {
		$mapping = self::_getFormatMimeTypeMapping();
		$mimeTypes = array_fill_keys(array_values($mapping), 1);
		$contentType = $this->getRequest()->getHeader('Content-Type');
		if ($contentType && array_key_exists($contentType, $mimeTypes)) {
			return $contentType;
		}
		return null;
	}

	protected function _initFormat() {
		if (!$this->_hasParam('serializeFormat')) {
			$this->_setParam(
				'serializeFormat',
				$this->_getSerializeFormat()
			);
		}
		if (!$this->_hasParam('deserializeFormat')) {
			$this->_setParam(
				'deserializeFormat',
				$this->_getDeserializeFormat()
			);
		}
	}

	protected function _doForward() {
		$request = $this->getRequest();
		if ($request->restForwarded) {
			return false;
		}
		$action = $this->_getParam('action');
		if ($action === 'root') {
			return false;
		}
		$newAction = strtolower($request->getMethod()).'-'.$action;
		$newActionMethod = strtolower($request->getMethod()).ucfirst($action).'Action';
		if (!method_exists($this, $newActionMethod)) {
			$this->_forward('not-allowed');
		} else {
			$this->_forward($newAction);
		}
		$this->_setParam('restForwarded', true);
		return true;
	}

	protected function _getSerializer($format, $fallback=false) {
		if ($fallback && $format === null) {
			$format = 'application/json';
		}
		if ($this->_serializers === null) {
			$this->_serializers = array();
		}
		if (!array_key_exists($format, $this->_serializers)) {
			if ($format === 'application/json') {
				$this->_serializers[$format] = new InfiRest_Serializer_Json;
			} elseif ($format === 'application/xml') {
				$this->_serializers[$format] = new InfiRest_Serializer_Xml;
			} else {
				throw new InfiRest_Exception_Base("Unknown format \"${format}\"");
			}
		}
		return $this->_serializers[$format];
	}

	protected function _isAuthenticated() {
		$authenticated = true; // TODO
		if ($authenticated) {
			return;
		}
		$this->sendResponse(self::RESPONSE_CODE_UNAUTHORIZED, array());
	}

	protected function _isAuthorized() {
		$authorized = true; // TODO
		if ($authorized) {
			return;
		}
		$this->sendResponse(self::RESPONSE_CODE_UNAUTHORIZED, array());
	}

	protected function _testThrottle() {

	}
	
	protected function _initLookupArgs() {
		$request = $this->getRequest();
		$params = $request->getParams();
		$args = array();
		if (array_key_exists('pk', $params)) {
			$args['pk'] = $params['pk'];
		}
		$this->_setParam('lookupArgs', array_merge($args, $request->getQuery()));
	}

	public function preDispatch() {
		try {
			//$this->_initErrorHandler();
			if (!$this->_doForward()) {
				$this->_initEndpoint();
				$this->_initSet();
				$this->_initFormat();
				$this->_initLookupArgs();
				// testing
				$this->_isAuthenticated();
				$this->_isAuthorized();
				$this->_testThrottle();
			}
			$this->result = null;
		} catch (Exception $e) {
			$this->_handleException($e);
		}
	}


	public function dispatch($action) {
		try {
			return parent::dispatch($action);
		} catch (Exception $e) {
			$this->_handleException($e);
		}
	}
	public function run($request=null, $response=null) {
		return parent::run($request, $response);
	}

	public function rootAction() {
		$api = $this->getRequest()->api;
		$result = array();
		$urlHelper = $this->_helper->url;
		$apiName = $api->getName();
		$listFmt = sprintf('easy-rest-list-%s-%%s', $apiName);
		$schemaFmt = sprintf('easy-rest-schema-%s-%%s', $apiName);
		foreach ($api->getEndpoints() as $endpointName) {
			$urlParams = array('endpoint' => $endpointName);
			$result[$endpointName] = array(
				'listEndpoint' => $urlHelper->url(
					$urlParams, 
					sprintf($listFmt, $endpointName),
					false,
					false
				),
				'schema' => $urlHelper->url(
					$urlParams,
					sprintf($schemaFmt, $endpointName),
					false,
					false
				),
			);
		}
		$this->sendResponse(200, $result);
	}

	public function getListAction() {
		$endpoint = $this->_getParam('endpoint');
		$list = $endpoint->objGetList();
		// TODO sorting
		$requestParams = $this->getRequest()->getParams();
		$paginator = $endpoint->paginateList($requestParams, $list);
		
		$result = $paginator->getPage();
		$bundles = array();
		foreach ($result[$paginator->getCollectionName()] as $obj) {
			$bundle = $endpoint->buildBundle($requestParams, $obj);
			$bundles[] = $endpoint->fullDehydrate($bundle);
		}
		$result[$paginator->getCollectionName()] = $bundles;
		$this->sendResponse(self::RESPONSE_CODE_OK, $result);
	}

	public function putListAction() {
		
	}

	public function postListAction() {
		$endpoint = $this->_getParam('endpoint');
		$request = $this->getRequest();
		$deserialized = $this->_getSerializer(
			$this->_getDeserializeFormat()
		)->deserialize($request->getRawBody());
		
		$bundle = $endpoint->buildBundle(
			$request->getParams(), null, $deserialized
		);
		
		$updatedBundle = $endpoint->objCreate($bundle);
		$endpoint->commit();
		$resourceLocation = $endpoint->getResourceUri($updatedBundle);
		
		if ($endpoint->getAlwaysReturnData()) {
			$updatedBundle = $endpoint->fullDehydrate($updatedBundle);
			return $this->sendResponse(
				self::RESPONSE_CODE_CREATED, $updatedBundle, $resourceLocation
			);
		}
		
		return $this->sendResponse(
			self::RESPONSE_CODE_CREATED, null, $resourceLocation
		);
	}

	public function deleteListAction() {

	}

	public function patchListAction() {

	}

	public function getDetailAction() {
		$endpoint = $this->_getParam('endpoint');
		$obj = $endpoint->objGet($this->_getParam('lookupArgs'));
		$bundle = $endpoint->buildBundle($this->getRequest()->getParams(), $obj);
		$bundle = $endpoint->fullDehydrate($bundle);
		$this->sendResponse(self::RESPONSE_CODE_OK, $bundle);
	}

	public function putDetailAction() {
		$endpoint = $this->_getParam('endpoint');
		$request = $this->getRequest();
		$deserialized = $this->_getSerializer(
			$this->_getDeserializeFormat()
		)->deserialize($request->getRawBody());
		
		$bundle = $endpoint->buildBundle($request->getParams(), null, $deserialized);
		try {
			$updatedBundle = $endpoint->objUpdate($bundle, false, $this->_getParam('lookupArgs'));
			$endpoint->commit();
			if (!$endpoint->getAlwaysReturnData()) {
				$this->sendResponse(self::RESPONSE_CODE_NO_CONTENT);
			} else {
				$updatedBundle = $endpoint->fullDehydrate($updatedBundle);
				$this->sendResponse(self::RESPONSE_CODE_ACCEPTED, $updatedBundle);
			}
			
		} catch(Exception $e) {
			if (!$e instanceof InfiRest_Exception_NotFound) {
				throw $e;
			}
			$updatedBundle = $endpoint->objCreate($bundle);
			$endpoint->commit();
			$resourceLocation = $endpoint->getResourceUri($updatedBundle);
			if ($endpoint->getAlwaysReturnData()) {
				$updatedBundle = $endpoint->fullDehydrate($updatedBundle);
				return $this->sendResponse(
					self::RESPONSE_CODE_CREATED, $updatedBundle, $resourceLocation
				);
			}
			
			return $this->sendResponse(
				self::RESPONSE_CODE_CREATED, null, $resourceLocation
			);
		}
	}

	public function postDetailAction() {
		$this->sendResponse(self::RESPONSE_CODE_NOT_IMPLEMENTED);
	}

	public function deleteDetailAction() {
		try {
			$endpoint = $this->_getParam('endpoint');
			$endpoint->objDelete($this->_getParam('lookupArgs'));
			$endpoint->commit();
			$this->sendResponse(self::RESPONSE_CODE_NO_CONTENT);
		} catch(InfiRest_Exception_NotFound $e) {
			$this->sendResponse(self::RESPONSE_CODE_NOT_FOUND);
		}
	}

	public function patchDetailAction() {

	}

	public function getSchemaAction() {
		$schema = $this->_getParam('endpoint')->getSchema();
		$this->sendResponse(
			self::RESPONSE_CODE_OK,
			$schema
		);
	}

	public function getSetAction() {

	}

	public function notAllowedAction() {
		
	}

}