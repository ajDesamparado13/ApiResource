<?php

namespace Freedom\ApiResource\Resources;

use Illuminate\Http\Resources\Json\JsonResource as BaseJsonResource;
use Freedom\ApiResource\Contracts\JsonResourceInterface;
use Illuminate\Support\Collection;


class JsonResource extends BaseJsonResource implements JsonResourceInterface
{
    public $columns = ['*'];

    /**
     * Collection of Criteria
     *
     * @var Collection
     */
    protected $criteria;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource=null)
    {
        $this->resource = $resource;
        $this->criteria = new Collection();

    }
    /**
     * Create new anonymous resource collection.
     *
     * @param  mixed  $resource
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public static function collection($resource)
    {
        return tap(new AnonymousResourceCollection($resource, static::class), function ($collection) {
            if (property_exists(static::class, 'preserveKeys')) {
                $collection->preserveKeys = (new static([]))->preserveKeys === true;
            }
        });
    }

}
