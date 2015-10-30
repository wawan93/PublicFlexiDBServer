<?php

class FX_API_Version extends FX_API
{

	/**
	* @api {get} /version
	* @apiVersion 0.1.0
	* @apiName Get API Version
	* @apiGroup Help
	* @apiDescription Get current version of the RESR API. This method will be also called on any invalid endpoint request.       
	*
	* @apiSuccess {text} Text data with REST API version number
	*
	* @apiEnd
	*/
	protected function _get()
	{
		return '1.0';
	}
}