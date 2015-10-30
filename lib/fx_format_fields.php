<?php

class FX_Format {
    public static function datetime($field,$type)
    {
        $date_format = get_fx_option('fx_datetime_format', array());

        if($type == 'DATE') $format = FX_DATE_FORMAT;
        elseif($type == 'TIME') $format = FX_TIME_FORMAT;
        else $format = FX_DATE_FORMAT.' '.FX_TIME_FORMAT;

        return $field['value'] ? date($format,$field['value']) : '';
    }

    public static function image($field)
    {
        $img_src_thumb = CONF_UPLOADS_URL . $field['object_type_id'] . '/' . $field['object_id'] . '/thumb_' . $field['value'];
        $img_src = CONF_UPLOADS_URL . $field['object_type_id'] . '/' . $field['object_id'] . '/' . $field['value'];


        return $img_src;
    }

    public static function file($field)
    {
        $file_src = is_url($field['value'])
            ? $field['value']
            : CONF_UPLOADS_URL . $field['object_type_id'] . '/' . $field['object_id'] . '/' . $field['value'];
        return $file_src;
    }

    public static function enum($field,$field_type)
    {
        return get_enum_label(
            $field_type,
            $field['value']
        );
    }

    public static function format($field)
    {
        $field_type = strtoupper($field['type']);
        switch ($field_type) {
            case 'DATETIME':
            case 'DATE':
            case 'TIME':
                return self::datetime($field,$field_type);
            case 'IMAGE':
                return $field['value']; //return self::image($field);
            case 'FILE':
                return self::file($field);
            default:
                if (is_numeric($field_type)) {
                    return self::enum($field,$field_type);
                } else {
                    return $field['value'];
                }
        }
    }

} 