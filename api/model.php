<?php

namespace Api\Model;

class Model extends \Api\Database\Database {
    public $writable_fields = array();
    public $readable_fields = array();

    public function filter($fields) {
        $new_fields = array();
        foreach ($fields as $field_name => $field_value) {
            if (in_array($field_name, $this->readable_fields)) {
                $new_fields[$field_name] = $field_value;
            }
        }
        return $new_fields;
    }
}
