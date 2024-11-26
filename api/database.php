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

    public function select($table, $fields, $where) {
        $params = array();

        $query = "SELECT ".implode(",", $fields)." FROM $table";

        $conditions = array();
        foreach ($where as $field_name => $field_param) {
            $conditions[] = $field_name."=?";
            $params[] = $field_param;
        }

        if (count($conditions) > 0) {
            $query .= " WHERE ".implode(" AND ", $conditions);
        }

        try {
            $stmt = $this->execute($query , $params);
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
            $params[] = new DatabaseParam($field_data->type, $field_data->value);
            $field_names[] = $field_name;
            $types[] = "?";
        }

        try {
            $stmt = $this->execute("INSERT INTO $table (".implode(",", $field_names).") VALUES (".implode(",", $types).")" , $params);
            $insert_id = $stmt->insert_id;
            $stmt->close();

            return $insert_id;
        } catch(\Exception $e) {
            throw New \Exception($e->getMessage());
        }
        return false;
    }

    public function delete($table, $where) {
        $params = array();

        $query = "DELETE FROM $table";

        $conditions = array();
        foreach ($where as $field_name => $field_param) {
            $conditions[] = $field_name."=?";
            $params[] = $field_param;
        }

        if (count($conditions) > 0) {
            $query .= " WHERE ".implode(" AND ", $conditions);
        }

        try {
            $stmt = $this->execute($query, $params);
            $stmt->close();

            return true;
        } catch(\Exception $e) {
            throw New \Exception($e->getMessage());
        }
        return false;
    }

    public function update($table = "" , $fields, $where) {
        $field_types = array();
        $params = array();

        foreach ($fields as $field_name => $field_data) {
            $params[] = new DatabaseParam($field_data->type, $field_data->value);
            $field_types[] = $field_name . " = ?";
        }

        $query = "UPDATE $table SET ".implode(",", $field_types);

        $conditions = array();
        foreach ($where as $field_name => $field_param) {
            $conditions[] = $field_name."=?";
            $params[] = $field_param;
        }

        if (count($conditions) > 0) {
            $query .= " WHERE ".implode(" AND ", $conditions);
        }

        try {
            $stmt = $this->execute($query, $params);
            $stmt->close();

            return true;
        } catch(\Exception $e) {
            throw New \Exception($e->getMessage());
        }
        return false;
    }

    private function execute($query = "" , $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            if($stmt === false) {
                throw New \Exception("Unable to do prepared statement: " . $query);
            }
            if (count($params) > 0) {
                $types = "";
                $var = array();
                foreach ($params as $single_param) {
                    $types .= $single_param->type;
                    $var[] = $single_param->value;
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

class DatabaseParam {
    public $type;
    public $value;

    public function __construct($type, $value) {
        $this->type = $type;
        $this->value = $value;
    }
}
