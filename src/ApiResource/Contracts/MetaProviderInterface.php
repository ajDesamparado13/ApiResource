<?php

namespace Freedom\ApiResource\Contracts;

interface MetaProviderInterface
{

    public function make(ApiResourceInterface $resource, $data=[]) : array;

}
