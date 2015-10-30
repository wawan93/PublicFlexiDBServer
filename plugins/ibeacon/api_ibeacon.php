<?php

class FX_API_Beacons extends FX_API
{
	protected function _get()
	{
		if (!$object_id = intval($this->args['object_id'])) {
			return new FX_Error(__METHOD__, _('Please specify Beacon (Object) ID'));
		}
		
		if (!defined('TYPE_IBEACON_UUID')) {
			return new FX_Error(__METHOD__, _('invalid iBeacon type'));
		}
		
		$UUID_object = get_object(TYPE_IBEACON_UUID, $this->args['object_id']);
		
		if (is_fx_error($UUID_object)) {
			return $UUID_object;
		}
		
		return get_beacons_by_uuid($UUID_object['uuid']);
	}	
}