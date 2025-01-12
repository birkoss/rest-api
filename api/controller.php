<?php

namespace Api\Controller;

class BaseController {
    protected $authorization_required = true;

    public function need_authorization() {
        return $this->authorization_required;
    }

    public function format_data($fields, $data) {
        $new_data = array();
        foreach ($data as $field_name => $field_value) {
            $new_data[$field_name] = new \Api\Database\DatabaseParam(
                $fields[$field_name]['type'],
                $field_value
            );
        }
        return $new_data;
    }

    public function validate_data($fields, $data) {
        // Any mandatory field missing ?
        foreach ($fields as $field_name => $field_data) {
            if ($field_data['is_mandatory'] && !isset($data[$field_name])) {
                return false;
            }
        }

        // Any unwanted field ?
        foreach ($data as $field_name => $field_value) {
            if (!isset($fields[$field_name])) {
                return false;
            }
        }

        return true;
    }

    public function verbose_fields($fields) {
        $mandatory = array();
        $optional = array();

        foreach ($fields as $field_name => $field_data) {
            if ($field_data['is_mandatory']) {
                $mandatory[] = $field_name;
            } else {
                $optional[] = $field_name;
            }
        }

        $str = "";
        if (count($mandatory) > 0) {
            $str .= "Mandatory field(s): " . implode(", ", $mandatory);
        }

        if (count($optional) > 0) {
            $str .= ", Optional field(s): " . implode(", ", $optional);
        }

        return trim($str, ", ");
    }
}
