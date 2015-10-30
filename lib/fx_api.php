<?php

abstract class FX_API
{
	/**
	 * Property: method
	 * The HTTP method this request was made in, either GET, POST, PUT or DELETE
	 */
	protected $method = '';
	
	/**
	 * Property: endpoint
	 * The Model requested in the URI. eg: /objects
	 */
	protected $endpoint = '';
	
	/**
	 * Property: verb
	 * An optional additional descriptor about the endpoint, used for things that can
	 * not be handled by the basic methods. eg: /user/schemas
	 */
	protected $verb = '';

	/**
	 * Property: args
	 * Any additional URI components after the endpoint and verb have been removed, in our
	 * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
	 * or /<endpoint>/<arg0>
	 */
	protected $args = array();

	/**
	 * Property: file
	 * Stores the input of the PUT request
	 */
	protected $file = NULL;

	/**
	 * Property: user_instance
	 * Stores User ID, API Key and Subscription ID associated with specified API Key
	 */
    protected $user_instance = array();	

	/**
	 * Property: permission
	 * Determines the availability of the API method in current context 
	 */
    protected $permission = 0;	


	/**
	 * Property: is_admin
	 * Current user is admin
	 */
    protected $is_admin = false;

	/**
	 * Property: schema_id
	 * Schema from which the data is requested. Will be get from type
	 * 0 - system type or error
	 */
    protected $schema_id = 0;

	/**
	 * Property: token
	 * Secret User key using for encrypt/decrypt data
	 */
    protected $token = '';

	protected $object_type_id = 0;
	
	protected $data = 0;
	/**
	 * Constructor: __construct
	 * Allow for CORS, assemble and pre-process the data
	 */
	public function __construct($request)
	{
		/**
		 * Global: exclude_key_check
		 * each API method have to add its name to avoid API Key check
		 */
		global $exclude_key_check;

		list ($this -> endpoint, $this -> verb) = explode('/', trim($request['endpoint'], '/'));

		unset ($request['endpoint']);

		$this -> method = $_SERVER['REQUEST_METHOD'];

		if ($this -> method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
			if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
				$this -> method = 'DELETE';
			}
			else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
				$this -> method = 'PUT';
			}
			else {
				throw new Exception('Unexpected Header');
			}
		}

		switch($this -> method)
		{
			case 'POST':
				$this -> args = $this -> _clean_inputs($_POST);
				break;
			case 'GET':
				$this -> args = $this -> _clean_inputs($_GET);
				break;
			case 'PUT':
			case 'DELETE':			
				$this -> file = file_get_contents("php://input");
				parse_str($this -> file, $this->request);
				$this -> args = $this -> _clean_inputs($this -> request);
				break;
			default:
				$this -> _response('Invalid Method', 405);
				break;
		}

		if (array_key_exists('api_key', $this -> args)) {
			//USER REQUEST
			$api_key = $this -> args['api_key'];
			$user_instance = get_user_subscription($api_key);
			$this->token = $user_instance['secret_key'];
		}
		else {
			//SERVER REQUEST
			$server_settings = get_fx_option('server_settings', array());
			$this->token = $server_settings['dfx_key'];
			$user_instance = array('is_admin' => true);
			$this->is_admin = true;
		}

		//*******************************************************************

		$this->data = $this->args['data'];		

		$data = $this -> _decrypt($this -> args['data'], $this->token);
		$this -> args = json_decode($data, true);
	
		if ($data && $this -> args === NULL) {
			throw new Exception('Invalid data format');
		}

		$this -> args = $this->_clean_inputs($this -> args);
			
		//*******************************************************************

		$this->object_type_id = 0;

		if (isset($this -> args['object_type_id'])) {
			$this->object_type_id = $this -> args['object_type_id'];
		}
		elseif (isset($this -> args['object_type_1_id']) && !is_system_type($this -> args['object_type_1_id'])) {
			$this->object_type_id = $this -> args['object_type_1_id'];
		}
		elseif (isset($this -> args['object_type_2_id']) && !is_system_type($this -> args['object_type_2_id'])) {
			$this->object_type_id = $this -> args['object_type_2_id'];
		}		

		$type_data = get_type($this->object_type_id, 'none');
		
		if (!is_fx_error($type_data) && $type_data) {
			$this -> schema_id = $type_data['schema_id'];
		}

		if (!$this -> schema_id) {
			$this -> schema_id = intval($this -> args['schema_id']);
		}
		
		$this -> permission = isset($user_instance['schema_permissions'][$this->schema_id][$this->object_type_id]) ? $user_instance['schema_permissions'][$this->schema_id][$this->object_type_id] : 0;

		if (is_fx_error($user_instance)) {
			throw new Exception($user_instance -> get_error_message());
		}

		$this -> user_instance = $user_instance;
		$this -> is_admin = $user_instance['is_admin'];
	}

    public function process_API()
	{
		if ($this->verb == 'help') {
			return 'API help for method '.$this->endpoint;
		}
		
		switch($this->method)
		{
			case 'GET':
				if (method_exists($this, '_get')) {
					$result = $this -> _get();
				}				
				break;
			case 'PUT':
				if (method_exists($this, '_put')) {
					$result = $this -> _put();
				}
				break;	
			case 'POST':
				if (method_exists($this, '_post')) {
					$result = $this -> _post();
				}
				break;										
			case 'DELETE':
				if (method_exists($this, '_delete')) {
					$result = $this -> _delete();
				}
				break;
			default:
				return _response('Invalid Method', 405);

		}

		// Perform actual tasks for the current request
		// ********************************************************

		perform_tasks_for_request($this->schema_id, $this -> method, $this -> endpoint, $this -> verb, $this -> args, $result);

		// ********************************************************

		$response = $this -> _response($result);
		
		if (caching_enabled() && $this->method == 'GET') {			
			add_api_cache(
				$this->user_instance['subscription_id'],
				$this->endpoint.'/'.($this->verb ? $this->verb.'/':''),
				$this->data,
				$response,
				$this->object_type_id);
		}
		
		return $response;
    }

    private function _response($data, $status = 200)
	{
        header("HTTP/1.1 ".$status." ".$this -> _request_status($status));
		
		if (is_fx_error($data)) {
			return json_encode($data);
		}
		else {
			return $this->_encrypt(json_encode($data), $this->token);
		}

    }

    private function _clean_inputs($data)
	{
        $clean_input = array();

		foreach ($data as $key => $value) {
			$value = json_decode($value, true);
			if($value !== null && is_array($value)) $data[$key] = $value;
		}

      	if (is_array($data)) {
            foreach ($data as $key => $value) {
                $clean_input[$key] = $this -> _clean_inputs($value);
            }
        }
		else {
            $clean_input = trim(strip_tags($data));
        }

        return $data;
    }

    private function _request_status($code)
	{
        $status = array( 
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported');

        return $status[$code] ? $status[$code] : $status[500];
    }
	
	private function _decrypt($data, $secret_key)
	{
		$data = pack("H*" , $data);
		$data = Blowfish::decrypt($data,  $secret_key, Blowfish::BLOWFISH_MODE_EBC, Blowfish::BLOWFISH_PADDING_RFC);
		$data = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data);

		return $data;
	}

	private function _encrypt($data, $secret_key)
	{
		
		$data = bin2hex(Blowfish::encrypt($data, $secret_key, Blowfish::BLOWFISH_MODE_EBC, Blowfish::BLOWFISH_PADDING_RFC));

		return $data;
	}
}