<?php

namespace Freedom\ApiResource\Criterias;

use Illuminate\Support\Arr;

/**
 * Class WithCriteria.
 *
 * @package namespace App\Criteria;
 */
abstract class WithCriteria extends BaseResourceCriteria
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
        foreach($inputs as $index => $field){
            if($this->shouldSkipField($field,$field)){
                continue;
            }
            $result = $this->specialQuery($query,$field);
            $query = $result ??  $query->with($field);
        }
        $query;
    }

    public function getRequestField() : string {
        return 'with';
    }

    protected function shouldApply($model, $repository): bool
    {
        return $repository->getMethod() != 'count';
    }

    abstract public function getFields() : array;

    abstract protected function specialQuery($query,$field);
}
