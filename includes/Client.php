<?php

class Client {
    private $data = [];
    
    public function __call($method, $params) {
        array_push($this->data, [
            "method" => $method,
            "params" => $params
        ]);
    }
    
    public function getData() {
        return $this->data;
    }
}