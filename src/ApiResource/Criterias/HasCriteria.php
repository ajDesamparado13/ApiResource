<?php

namespace Freedom\ApiResource\Criterias;

/**
 * Class WithCriteria.
 *
 * @package namespace App\Criteria;
 */
abstract class HasCriteria extends BaseResourceCriteria
{
    public function handle($model)
    {
        $inputs = $this->makeInputs();
        if($this->shouldSkipCriteria($inputs)){
            return $model;
        }
        return $this->newQuery($model,$inputs);
    }


    protected function newQuery($model, array $inputs){
        return $this->buildQuery($model,$inputs);
    }

    protected function buildQuery($query,array $inputs){
        foreach($inputs as $index => $key){
            $field = is_numeric($index) ? $key : $index;
            if($this->shouldSkipField($field,$key)){
                continue;
            }
            $result = $this->specialQuery($query,$field);
            $query = $result ??  $query->whereHas($field);
        }
        return $query;
    }

    public function getRequestField() : string {
        return 'has';
    }

    protected function shouldApply($model, $repository): bool
    {
        return $repository->getMethod() != 'count';
    }

    abstract public function getFields() : array;

    abstract protected function specialQuery($query,$field);
}
