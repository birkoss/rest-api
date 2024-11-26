<?php

namespace Api\Response;

class Response {
    public $data;
    public $status;

    public function __construct($data, $status) {
        $this->data = $data;
        $this->status = $status;
    }
}

class Status {
    const HTTP_200_OK    = '200 OK';

    const HTTP_400_BAD_REQUEST = '400 Bad request';
    const HTTP_401_UNAUTHORIZED = '401 Unauthorized';
    const HTTP_403_FORBIDDEN = '403 Forbidden';
    const HTTP_422_UNPROCESSABLE_ENTITY = '422 Unprocessable Entity';
}
