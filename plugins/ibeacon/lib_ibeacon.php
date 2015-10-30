<?php

function _proximity_convert($value)
{
	$value = (array)json_decode($value, true);
	
	switch ($value['content_type']) {
		case 'static':
			$res = array('type'=>'text', 'data'=>$value['data']);
		break;
		case 'app':
			$res = array('type'=>'app', 'data'=>$value['data']['app_page']);
		break;
		case 'field':
			$field = get_object_field($value['data']['type'], $value['data']['object'], $value['data']['object_field'], true);
			
			if (is_fx_error($field)) {
				$res = '';
				break;
			}

			$type = 'text';

			switch ($field['type']) {
				case 'image':
					$type = 'image';
					if ($field['value']) {
						$field['value'] = URL.$value['data']['type'].'/'.$value['data']['object'].'/thumb_'.$field['value'];
					}
				break;
				case is_numeric($field['type']):
					$field['value'] = get_enum_label($field['type'], $field['value']);
				break;
				case 'datetime':
					$field['value'] = $field['value'] ? date(FX_DATE_FORMAT.' '.FX_TIME_FORMAT, $field['value']) : NULL;
				break;
				case 'date':
					$field['value'] = $field['value'] ? date(FX_DATE_FORMAT, $field['value']) : NULL;
				break;
				case 'time':
					$field['value'] = $field['value'] ? date(FX_TIME_FORMAT, $field['value']) : NULL;
				break;
			}
		
			$res = array('type'=>$type, 'data'=>$field['value']);
		break;
		case 'empty':
		default:
			$res = NULL;
	}

	return json_encode($res);
}

function get_beacons_by_uuid($uuid)
{
	if (!$uuid) {
		return new FX_Error(__FUNCTION__, _('Empty UUID'));
	}
	
	global $fx_db;

	$query = "SELECT * FROM ".DB_TABLE_PREFIX."object_type_".TYPE_IBEACON." WHERE uuid=:uuid";

	$pdo = $fx_db -> prepare($query);
	$pdo -> bindValue(":uuid", $uuid, PDO::PARAM_STR);
	
	if ($pdo -> execute()) {
		
		$result = array();
		
		foreach((array)$pdo->fetchAll() as $row) {
/*			$result[$row['major']][$row['minor']]['uuid'] = $row['uuid'];
			$result[$row['major']][$row['minor']]['name'] = $row['name'];
			$result[$row['major']][$row['minor']]['major'] = $row['major'];
			$result[$row['major']][$row['minor']]['minor'] = $row['minor'];
			$result[$row['major']][$row['minor']]['proximity_immediate'] = _proximity_convert($row['proximity_immediate']);
			$result[$row['major']][$row['minor']]['proximity_near'] = _proximity_convert($row['proximity_near']);
			$result[$row['major']][$row['minor']]['proximity_far'] = _proximity_convert($row['proximity_far']);
			$result[$row['major']][$row['minor']]['proximity_unknown'] = _proximity_convert($row['proximity_unknown']);*/
			
			$beacon = array(
				'uuid' => $row['uuid'],
				'name' => $row['name'],
				'display_name' => $row['display_name'],
				'major' => $row['major'],
				'minor' => $row['minor'],
				'proximity_immediate' => _proximity_convert($row['proximity_immediate']),
				'proximity_near' => _proximity_convert($row['proximity_near']),
				'proximity_far' => _proximity_convert($row['proximity_far']),
				'proximity_unknown' => _proximity_convert($row['proximity_unknown'])
			);
			
			$result[] = $beacon;
		}
		
		return $result;
	}

	return new FX_Error(__FUNCTION__, _('SQL Error'));
}

function _ibeacon_insert_types()
{
	$type_ibeacon_uuid = get_type_id_by_name(0, 'ibeacon_uuid');

	if (!is_numeric($type_ibeacon_uuid)) {

		$fields = array(array('name'=>'uuid', 'caption'=>'UUID', 'description' => 'iBeacon UUID', 'type'=>'VARCHAR'),
						array('name'=>'description', 'caption' => 'Description', 'description' => '', 'type'=>'TEXT'));

		$type_array = array('system' => 1, 'name' => 'ibeacon_uuid',
							'display_name' => 'iBeacon UUID',
							'description' => 'This type was inserted by FlexiDB iBeacon Plugin',
							'prefix' => '',
							'fields' => $fields);

		add_type($type_array);
	}
	
	$type_ibeacon = get_type_id_by_name(0, 'ibeacon');

    if (!is_numeric($type_ibeacon)) {

        $fields = array(
			array('name'=>'uuid', 'caption'=>'UUID', 'description' => 'iBeacon UUID', 'type'=>'VARCHAR', 'mandatory'=>1),
			array('name'=>'major', 'caption'=>'Major Version', 'description'=> 'iBeacon Major Version', 'type'=>'INT', 'mandatory'=>1),
			array('name'=>'minor', 'caption'=>'Minor Version', 'description'=> 'iBeacon Major Version', 'type'=>'INT', 'mandatory'=>1),
			array('name'=>'description', 'caption' => 'Description', 'description' => '', 'type'=>'TEXT'),
          	array('name'=>'proximity_immediate', 'caption'=>'Proximity Immediate', 'description'=>'Value for when proximity = ProximityImmediate', 'type'=>'TEXT'),
          	array('name'=>'proximity_near', 'caption'=>'Proximity Near', 'description'=>'Value for when proximity = ProximityNear', 'type'=>'TEXT'),
			array('name'=>'proximity_far', 'caption'=>'Proximity Far', 'description'=>'Value for when proximity = ProximityFar', 'type'=>'TEXT'),
			array('name'=>'proximity_unknown', 'caption'=>'Proximity Unknown', 'description'=>'Value for when proximity = ProximityUnknown', 'type'=>'TEXT'));

        $type_array = array('system' => 1,
           'name' => 'ibeacon',
           'display_name' => 'iBeacon',
           'description' => 'This type was inserted by FlexiDB iBeacon Plugin',
           'prefix' => '',
           'fields' => $fields);

        add_type($type_array);
    }
}
