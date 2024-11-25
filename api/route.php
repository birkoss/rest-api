<?php

namespace Api\Route;

class Route {
    public $rule;
    public $controller;

    public function __construct($rule, $controller) {
        $this->rule = $rule;
        $this->controller = $controller;
    }
}
