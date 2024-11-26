<?php

require_once(__DIR__ . "/database.php");
require_once(__DIR__ . "/controller.php");
require_once(__DIR__ . "/model.php");
require_once(__DIR__ . "/response.php");
require_once(__DIR__ . "/route.php");

use \Api\Response\Response;
use \Api\Response\Status;

class Api {
    protected $urls = array();
    protected $auth = NULL;

    private function build_request() {
        $url_info = parse_url($_SERVER['REQUEST_URI']);

        $request = array(
            'data' => array(),
            'url' => substr($url_info['path'], 1),
            'query' => array(),
            'method' => strtolower($_SERVER["REQUEST_METHOD"]),
            'user' => NULL,
        );
        
        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        if ($authorization != "" && preg_match("|^token (.*)$|Uim", $authorization, $matches)) {
            $token = $matches[1];

            if (!$this->auth || !method_exists($this->auth, 'authenticate')) {
                $this->send_response(new Response(
                    array('error' => 'Must have a valid Auth Model to use Token Authorization.'),
                    Status::HTTP_422_UNPROCESSABLE_ENTITY
                ));
            }
            try {
                $request['user'] = $this->auth->authenticate($token);
            } catch(Error $e) {
                $this->send_response(new Response(
                    array('error' => 'Auth Model with an authenticate method Not Found'), 
                    Status::HTTP_422_UNPROCESSABLE_ENTITY
                ));
            }
        }

        if (isset($url_info['query'])) {
            parse_str($url_info['query'], $request['query']);
        }
        
        try {
            $request['data'] = json_decode(file_get_contents('php://input'), true);
        } catch (Exception $e) {}

        return $request;
    }

    public function load($app) {
        include_once("$app/models.php");

        if (file_exists("$app/controllers.php")) {
            include_once("$app/controllers.php");
        }

        if (file_exists("$app/urls.php")) {
            include_once("$app/urls.php");
            $this->urls = array_merge($this->urls, $urls);
        }
    }

    public function set_auth($auth) {
        $this->auth = $auth;
    }

    public function run() {
        $request = $this->build_request();

        // Get the matching route $controller_name and $url_arguments
        $controller_name = null;
        $url_arguments = array();
        foreach ($this->urls as $single_url) {
            if (preg_match('|^'.$single_url->rule.'$|Uim', $request['url'], $url_arguments)) {
                // $url_arguments[0] is always the requested url, remove it
                array_shift($url_arguments);
                $controller_name = $single_url->controller;
                break;
            }
        }
        
        // Create a new controller $controller_name
        try {
            $c = new $controller_name;    
        } catch (Error $e) {
            $this->send_response(new Response(
                array('error' => 'Controller Not Found'), 
                Status::HTTP_422_UNPROCESSABLE_ENTITY
            ));
        }
        
        // Validate authorization if neeeded
        if ($c->need_authorization() && !$request['user']) {
            $this->send_response(new Response(
                array('error' => 'Provide A Valid Token'), 
                Status::HTTP_401_UNAUTHORIZED
            ));
        }
        
        // Must support the requested method
        if (!method_exists($c, $request['method'])) {
            $this->send_response(new Response(
                array('error' => 'Method Not Supported'), 
                Status::HTTP_422_UNPROCESSABLE_ENTITY
            ));
        }
        
        // Call the requested method.
        // - First argument is always the complete request
        // - Then all the $url_arguments are added
        $response = $c->{$request['method']}(
            $request,
            ...array_values($url_arguments)
        );


        $this->send_response($response);
    }

     public function send_response($response) {
        $http_headers = array(
            'Content-Type: application/json',
            'HTTP/1.1 ' . $response->status,
        );

        header_remove('Set-Cookie');

        if (is_array($http_headers) && count($http_headers) > 0) {
            foreach ($http_headers as $single_http_header) {
                header($single_http_header);
            }
        }

        echo json_encode($response->data);
        exit;
    }   
}
