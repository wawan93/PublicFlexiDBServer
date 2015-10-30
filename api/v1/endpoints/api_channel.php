<?php

class FX_API_Channel extends FX_API
{
	protected function _get()
	{		
		switch ($this -> verb) {
			/**
			* @api {GET} /channel/id
			* @apiVersion 0.1.0
			* @apiGroup Channel
			* @apiName Get Channel ID
			* @apiDescription Get global Channel ID by schema ID of the current FlexiDB server
			*
			* @apiParam {Number*} schema_id Data Schema ID
			*
			* @apiSuccess {Number} Channel ID if specified data schema has channel and "0" otherwise
			*
			* @apiError {error} Unknown Schema ID
			*
			* @apiEnd
			*/ 	
			case 'id':
				if (!(int)$this->args['schema_id']) {
					return new FX_Error(__METHOD__, _('Unknown Schema ID'));
				}
				
				$channel_id = get_object_field(TYPE_DATA_SCHEMA, $this->args['schema_id'], 'channel');
				return is_numeric($channel_id) ? $channel_id : 0;
				
			break;
			default:
				return new FX_Error(__METHOD__, _('Invalid verb value'));
		}
	}	
}