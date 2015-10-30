<?php
 
class FX_API_File extends FX_API
{
	
	/**
	* @api {post} /file
	* @apiVersion 0.1.0
	* @apiName Upload File
	* @apiDescription Upload file or image to the associated FlexiDB server and object field with type File or Image   
	*
	* @apiParam {Number} object_type_id* Object Type ID
	* @apiParam {Number} object_id* Object ID
	* @apiParam {POST file(s)} @[field_name] File to upload
	*
	* @apiSuccess {URL} URL with link to newly uploaded file
	*
	* @apiError {error} Error message
	*
	* @apiEnd
	*/
	protected function _post()
	{
		if (!$this->_check_permissions(U_POST | U_PUT)) {	
			return new FX_Error('access_forbidden', _('You have no proper access rights to perform this action'));
		}

		if (!empty($_FILES))
		{
			$tmp_res = new FX_Temp_Resource($this -> args['object_type_id'], $this -> args['object_id']);

			if ($tmp_res->last_error) {	
				return new FX_Error('fx_tmp_resource', $tmp_res->last_error);
			}

			$tmp_dir =  CONF_UPLOADS_DIR.'/temp/';

			$upload_res = array();

			foreach ($_FILES as $field_name => $tmp_file)
			{
				if (!file_exists($tmp_file["tmp_name"])) {
					return new FX_Error('upload_file', 'Temporary file not found '.$tmp_file["tmp_name"]);
				}

				if (move_uploaded_file($tmp_file["tmp_name"], $tmp_dir.$tmp_file['name']) === true)
				{
					$res = $tmp_res -> add($field_name, $tmp_file['name']);
					$result = $tmp_res -> submit();

					if (is_fx_error($result)) {
						return $result;
					}

					$upload_res[$field_name] = $result;
				}
				else {
					$tmp_res -> remove();
					return new FX_Error('upload_file', 'Unable to upload temporary file for field "'.$field_name.'".');
				}
			}
			
			clear_query_cache_by_type($this -> args['object_type_id']);
			
			return $upload_res;
		}
		else {
			return new FX_Error('upload_file', 'Files not found.');
		}
	}

	/**
	* @api {delete} /file
	* @apiVersion 0.1.0
	* @apiName Delete File
	* @apiDescription Delete file/image associated with some Object field which has type field/image
	*
	* @apiParam {Number} object_type_id* Object Type ID
	* @apiParam {Number} object_id* Object ID
	* @apiParam {String} field* Field name which is associated with file/image
	*
	* @apiSuccess {true} File was successfully deleted
	*
	* @apiError {error} Error message
	*
	* @apiEnd
	*/
	protected function _delete()
	{
		if (!$this->_check_permissions(U_DELETE)) {			
			return new FX_Error('access_forbidden', _('You have no proper access rights to perform this action'));
		}
		
		if (!$this -> args['field']) {
			return new FX_Error(__METHOD__, _('Empty field name'));
		}
		
		$tmp_res = new FX_Temp_Resource($this -> args['object_type_id'], $this -> args['object_id']);

		if (is_fx_error($tmp_res)) {
			return ($tmp_res -> get_error_message());
		}

		$tmp_res -> add($this -> args['field'], '');

		clear_query_cache_by_type($this -> args['object_type_id']);

		$result = $tmp_res -> submit();

		return $result;	
	}
	
	private function _check_permissions ($required_permission)
	{
		$set_id = get_object_field($this -> args['object_type_id'], $this -> args['object_id'], 'set_id');

		if (!is_numeric($set_id)) {
			$set_id = $this -> args['set_id'] ? $this -> args['set_id'] : 0;
		}
		
		if (in_array($set_id, $this->user_instance['sets'])) {
			return true;
		}

		$csp = $this->user_instance['set_permissions'][$set_id][$this -> args['object_type_id']];
		
		if (!$this -> is_admin && !($this -> permission & $required_permission) && !($csp & $required_permission)) {
			return false;
		}
		else {
			return true;
		}
	}
}