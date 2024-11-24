<?php

namespace Api\Database;

class Database {
    protected $connection = null;

    public function __construct() {
        try {
            $this->connection = new \mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE_NAME);
    	
            if ( mysqli_connect_errno()) {
                throw new \Exception("Could not connect to database.");   
            }

            $this->connection->set_charset(DB_CHARSET);
            
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());   
        }			
    }
    
    public function get_data($fields, $extra_data) {
        $data = array();
        foreach ($fields as $field_name => $field_data) {
            $data[$field_name] = $field_data['value'];
        }
        if ($extra_data) {
            $data = array_merge($extra_data, $data);
        }

        return $data;
    }

    public function select($query = "" , $params = []) {
        try {
            $stmt = $this->executeStatement($query , $params);
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);				
            $stmt->close();
            return $result;
        } catch(\Exception $e) {
            throw New \Exception($e->getMessage());
        }
        return false;
    }

    public function insert($table = "" , $fields) {
        $field_names = array();
        $types = array();
        $params = array();

        foreach ($fields as $field_name => $field_data) {
            $params[] = array($field_data['type'], $field_data['value']);
            $field_names[] = $field_name;
            $types[] = "?";
        }

        try {
            $stmt = $this->executeStatement("INSERT INTO $table (".implode(",", $field_names).") VALUES (".implode(",", $types).")" , $params);
            $insert_id = $stmt->insert_id;
            $stmt->close();

            return $insert_id;
        } catch(\Exception $e) {
            throw New \Exception($e->getMessage());
        }
        return false;
    }

    public function update($table = "" , $fields, $where) {
        $field_types = array();
        $params = array();

        foreach ($fields as $field_name => $field_data) {
            $params[] = array($field_data['type'], $field_data['value']);
            $field_types[] = $field_name . " = ?";
        }

        try {
            $stmt = $this->executeStatement("UPDATE $table SET ".implode(",", $field_types)." WHERE $where" , $params);
            $insert_id = $stmt->insert_id;
            $stmt->close();

            return array('id' => $insert_id);
        } catch(\Exception $e) {
            throw New \Exception($e->getMessage());
        }
        return false;
    }

    private function executeStatement($query = "" , $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            if($stmt === false) {
                throw New \Exception("Unable to do prepared statement: " . $query);
            }
            if (count($params) > 0) {
                $types = "";
                $var = array();
                foreach ($params as $single_param) {
                    $types .= $single_param[0];
                    $var[] = $single_param[1];
                }
                $stmt->bind_param($types, ...array_values($var));
            }
            $stmt->execute();
            return $stmt;
        } catch(\Exception $e) {
            throw New \Exception($e->getMessage());
        }	
    }
}
