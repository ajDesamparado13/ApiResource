<?php
namespace Freedom\ApiResource\Traits;
use Freedom\ApiResource\Exceptions\ApiControllerException;
use Freedom\ApiResource\Contracts\MetaProviderInterface;

trait HasMetaProvider {

    /**
     * The meta provider instance
     * 
     * @var Freedom\ApiResource\Contracts\MetaProviderInterface;
     */
    protected $metaProvider;


    protected function makeMetaProvider(){
        $metaProvider = $this->metaProvider();

        if(empty($metaProvider)){
            return;
        }

        if(is_string($metaProvider)){
            $metaProvider = app()->make($metaProvider);
        }

        if(!( $metaProvider instanceof  MetaProviderInterface)){
            throw new ApiControllerException("Class " . get_class($metaProvider) . " must be an instance of " . MetaProviderInterface::class);
        }

        return $this->metaProvider = $metaProvider;
    }

    protected function hasMetaProvider() : bool
    {
        return !empty($this->metaProvider) &&  $this->metaProvider instanceof  MetaProviderInterface;
    }

    protected function getMeta($resource,$result)
    {
        return $this->hasMetaProvider() ? $this->metaProvider->make($resource,$result) : [];
    }

    abstract public function metaProvider();
}
