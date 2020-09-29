<?php

namespace Freedom\ApiResource\Criterias;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Support\Arr;

/**
 * Class WithCriteria.
 *
 * @package namespace App\Criteria;
 */
abstract class WithCriteria implements CriteriaInterface
{
    protected $with;

    protected $skipOn = [];

    public function __construct($with=null)
    {
        $this->setWith($with);
    }

    public function setWith($with){
        $this->with = $with;
    }

    public function handle($model)
    {
        $eagerLoadables = $this->makeEagerLoadables();
        if($this->shouldSkipCriteria($eagerLoadables)){
            return $model;
        }
        return $this->newQuery($model,$eagerLoadables);
    }

    protected function makeEagerLoadables() : array
    {
        $input = $this->with ?? request()->input('with');
        $with = array_filter( is_array($input) ? $input : explode(';',$input) );

        return Arr::where($with,function($value){
            return in_array($value,$this->getFieldsEagerLoadable());
        });
    }

    protected function newQuery($model, array $eagerLoadables){
        return $this->buildQuery($model,$eagerLoadables);
    }

    protected function buildQuery($query,array $eagerLoadables){
        foreach($eagerLoadables as $field){
            if($this->shouldSkipField($field)){
                continue;
            }
            $result = $this->specialQuery($query,$field);
            $query = $result ??  $query->with($field);
        }
        $query;
    }

    protected function shouldSkipCriteria(array $eagerLoadables) : bool
    {
        return empty($eagerLoadables); 
    }

    protected function shouldSkipField($field) : bool
    {
        return in_array($field,$this->skipOn);
    }

    /**
     * Apply criteria in query repository
     *
     * @param string              $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        return $this->handle($model);
    }

    abstract public function getFieldsEagerLoadable() : array;

    abstract protected function specialQuery($query,$field);
}
