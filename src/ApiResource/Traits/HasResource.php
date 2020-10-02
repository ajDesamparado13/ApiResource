<?php

namespace Freedom\ApiResource\Traits;
use Freedom\ApiResource\Contracts\ApiResourceInterface;
use Freedom\ApiResource\Exceptions\ApiControllerException;

trait HasResource {

    /**
     * The resource instance.
     *
     * @var Freedom\ApiResource\Contracts\ApiInterface;
     */
    protected $resource;


    public function makeResource(){
        $resource = $this->resource();

        if(is_string($resource)){
            $resource = app()->make($this->resource());
        }

        if (!( $resource instanceof ApiResourceInterface) ) {
            throw new ApiControllerException("Class ". get_class($resource) . " must be an instance of " . ApiResourceInterface::class);
        }

        return $this->resource = $resource;
    }

    protected function hasResource() : bool
    {
        return !empty($this->resource) &&  $this->resource instanceof ApiResourceInterface;
    }

    abstract public function resource();
}
