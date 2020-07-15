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

    public function __construct($with=null)
    {
        $this->setWith($with);
    }

    public function setWith($with){
        $this->with = $with;
    }

    abstract protected function specialQuery($query,$field);


    public function handle($model)
    {
        $input = $this->with ?? request()->input('with');
        $with = array_filter( is_array($input) ? $input : explode(';',$input) );

        $eagerLoadables = Arr::where($with,function($value,$key){
            return in_array($value,$this->getFieldsEagerLoadable());
        });

        foreach($eagerLoadables as $key){
            $result = $this->specialQuery($model,$key);
            $model = $result ? $result : $model->with($key);
        }

        return $model;
    }

    abstract public function getFieldsEagerLoadable() : array;


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
}
