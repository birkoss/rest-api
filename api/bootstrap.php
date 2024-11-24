<?php

require_once(__DIR__ . "/database.php");
require_once(__DIR__ . "/controller.php");

class Api {
    protected $urls = array();

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

    public function run() {
        $url_info = parse_url($_SERVER['REQUEST_URI']);

        $request = array(
            'data' => array(),
            'url' => substr($url_info['path'], 1),
            'query' => array(),
            'method' => strtolower($_SERVER["REQUEST_METHOD"]),
            'user' => null,
        );
        
        $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        if ($authorization != "" && preg_match("|^token (.*)$|Uim", $authorization, $matches)) {
            $token = $matches[1];

            $model = new \Users\Models\User();
            $users = $model->getUser($token);
            if (count($users) > 0) {
                $request['user'] = array_shift($users);
            }
        }

        if (isset($url_info['query'])) {
            parse_str($url_info['query'], $request['query']);
        }
        
        try {
            $request['data'] = json_decode(file_get_contents('php://input'), true);
        } catch (Exception $e) {}
        
        $controller = null;
        $arguments = array();

        foreach ($this->urls as $single_url) {
        
            if (preg_match('|^'.$single_url[0].'$|Uim', $request['url'], $arguments)) {
                array_shift($arguments);
        
                $controller = $single_url[1];
                break;
            }
        }
        
        try {
            $c = new $controller;    
        } catch (Error $e) {
            $c = new \Api\Controller\BaseController;
            $c->sendOutput(
                array('error' => 'Controller not found'), 
                array('Content-Type: application/json', 'HTTP/1.1 422 Unprocessable Entity')
            );
        }
        
        // Validate authorization
        if ($c->need_authorization() && !$request['user']) {
            $c->sendOutput(
                array('error' => 'Provide a valid token'), 
                array('Content-Type: application/json', 'HTTP/1.1 401 Unauthorized')
            );
        }
        
        if (!method_exists($c, $request['method'])) {
            $c->sendOutput(
                array('error' => 'Method not supported'), 
                array('Content-Type: application/json', 'HTTP/1.1 422 Unprocessable Entity')
            );
        }
        
        $c->{$request['method']}(
            $request,
            ...array_values($arguments)
        );
    }
}
