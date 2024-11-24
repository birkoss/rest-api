<?php

namespace Api\Model;

class Model extends \Api\Database\Database {
    public $writable_fields = array();
    public $readable_fields = array();

    public function filters($data) {
        foreach ($data as $index => $single_data) {
            $data[$index] = $this->filter($single_data);
        }
        return $data;
    }

    public function filter($fields) {
        return array_intersect_key($fields, array_flip($this->readable_fields));
    }
}
