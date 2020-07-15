<?php

namespace Freedom\ApiResource\VO;

class ApiMessage {

    protected $file;
    protected $controller;


    public function __construct(string $file="api",string $controller="default")
    {
        $this->file = $file;
        $this->controller = $controller;
    }

    public function getMessage($method)
    {
        $message = implode('.',array_filter([$this->file, $this->controller,$method ]));
        return trans($message);
    }

}