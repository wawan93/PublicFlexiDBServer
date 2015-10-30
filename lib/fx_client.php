<?php

class FX_API_Client
{
	var $curlObj;
	var $httpHeader;
	var $api_key;
	var $base_api_url;

	function __construct($api_key = '')
	{
		$this -> httpHeader[] = "Cache-Control: max-age=0";
		$this -> httpHeader[] = "Connection: keep-alive";
		$this -> httpHeader[] = "Keep-Alive: 300";
		
		$this -> server_settings = get_fx_option('server_settings');
		$this -> dfx_key = $this -> server_settings['dfx_key'];
		$this -> setBaseAPIUrl($this -> server_settings['fx_api_base_url']);		
	}

	private function initCurlObject()
	{
		$this -> curlObj = curl_init();

		curl_setopt($this -> curlObj, CURLOPT_HEADER, false);
		curl_setopt($this -> curlObj, CURLOPT_AUTOREFERER, true);
		curl_setopt($this -> curlObj, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($this -> curlObj, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this -> curlObj, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($this -> curlObj, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this -> curlObj, CURLOPT_CONNECTTIMEOUT, 120);	
		//curl_setopt($this -> curlObj, CURLOPT_UPLOAD, true);
	}

	public function setBaseAPIUrl($baseUrl)
	{
		$baseUrl = str_replace('\\', '/', $baseUrl);

		if (is_url($baseUrl)) {
			$baseUrl = trim($baseUrl, '/');
			$this -> base_api_url = $baseUrl;
		}
		else {
			return new FX_Error(__METHOD__, _('Invalid URL format'));
		}
	}
	
	public function validate()
	{
		$msg = 'test message';
		$result = $this -> execRequest('validate', 'get', 'request='.$msg);
		return $result === $msg ? true : false;
	}
	
	public function execRequest($endpoint, $httpMethod, $httpData = '')
	{  
		$endpoint = str_replace('\\', '/', $endpoint);
		
		if($httpURL[0] != '/') {
			$endpoint = '/'.$endpoint;
		}
	
		if (!is_url($this->base_api_url)) {
			return new FX_Error(__METHOD__, _('Please set the valid base API url'));
		}
		
		$httpURL = $this->base_api_url.$endpoint;
		
		$httpMethod = strtolower($httpMethod);
		
		if (!in_array($httpMethod, array('get', 'post', 'put', 'delete'))) {
			return new FX_Error(__METHOD__, _('Invalid HTTP method'));
		}

		$this -> initCurlObject();

		curl_setopt($this -> curlObj, CURLOPT_HTTPHEADER, $this -> httpHeader);

		if (!is_array($httpData)) {
			parse_str($httpData, $httpData);
		}

		foreach($httpData as $key => $value) {
			$httpData[$key] = is_array($value) ? json_encode($value) : $value;
		}

		$httpData['dfx_key'] = $this -> dfx_key;

		if ($httpMethod != 'post') {
			$httpData = http_build_query($httpData);
		}

		switch ($httpMethod) {
			case 'get':
				$this -> _get($httpData, $httpURL);			
				break;
			case 'post':
				$this -> _post($httpData);
				break;
			case 'put':
				$this -> _put($httpData);
				break;
			case 'delete':
				$this -> _delete($httpData);
				break;
		}

		curl_setopt($this -> curlObj, CURLOPT_URL, $httpURL);

		$result = json_decode(curl_exec($this -> curlObj),true);

		if ($curl_error = curl_error($this -> curlObj)) {
			add_log_message('curl_error', $curl_error);
			return new FX_Error('curl_error', $curl_error);
			die();
		}

		if (is_array($result) && array_key_exists('errors', $result)) {
			return $this -> _convert_result_to_fx_error($result);
		}

		return $result;
	}

	public function setAcceptType($type)
	{
		// xml  -> text/xml
		// html -> text/html
		// json -> application/json
		// text -> text/plain
		// Else -> whatever was there

		if (is_array($type)) {
			foreach($type as $k => $v) {
				$v = strtolower($v);
				if($v == "xml")
					$type[$k] = "text/xml";
				elseif($v == "html")
					$type[$k] = "text/html";
				elseif($v == "json")
					$type[$k] = "application/json";
				elseif($v == "text")
					$type[$k] = "text/plain";
			}
			$type = implode(",", $type);
		}
		$this -> httpHeader[] = "Accept: ".$type;
	}

	private function _get($data = NULL, &$url)
	{
		curl_setopt($this -> curlObj, CURLOPT_HTTPGET, true);

		if($data != NULL) {
			if(is_array($data)) {
				
				$data = http_build_query($data, 'arg');
			}
			else {
				parse_str($data, $tmp);
				$data = "";
				$first = true;
				foreach($tmp as $k => $v) {
					if(!$first) {
						$data .= "&";
					}
					$data .= $k . "=" . urlencode($v);
					$first = false;
				}
			}
			$url .= "?".$data;
		}
	}
	
	private function _post($data = NULL)
	{
		curl_setopt($this -> curlObj, CURLOPT_POST, true);
		curl_setopt($this -> curlObj, CURLOPT_BINARYTRANSFER, true);			
		curl_setopt($this -> curlObj, CURLOPT_POSTFIELDS, $data);
	}

	private function _put($data = NULL)
	{
		curl_setopt($this -> curlObj, CURLOPT_PUT, true);
		$resource = fopen('php://temp', 'rw');
		$bytes = fwrite($resource, $data);
		rewind($resource);

		if($bytes !== false) {
			curl_setopt($this -> curlObj, CURLOPT_INFILE, $resource);
			curl_setopt($this -> curlObj, CURLOPT_INFILESIZE, $bytes);			
		}
		else {
			throw new Exception('Could not write PUT data to php://temp');
		}
	}

	private function _delete($data = null)
	{
		curl_setopt($this -> curlObj, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($this -> curlObj, CURLOPT_PUT, true);
		
		if($data != null) {
			$resource = fopen('php://temp', 'rw');
			$bytes = fwrite($resource, $data);
			rewind($resource);
			
			if($bytes !== false) {
				curl_setopt($this -> curlObj, CURLOPT_INFILE, $resource);
				curl_setopt($this -> curlObj, CURLOPT_INFILESIZE, $bytes);
			}
			else {
				throw new Exception('Could not write DELETE data to php://temp');
			}
		}
	}
	
	private function _convert_result_to_fx_error($result = array())
	{
		$errors = new FX_Error();

		foreach ($result['errors'] as $code => $messages) {
			for($i = 0; $i < count($messages); $i++) {
				$errors -> add($code, $messages[$i]);
			}
		}

		return $errors;
	}
}