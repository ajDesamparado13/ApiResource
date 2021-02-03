<?php

namespace Freedom\ApiResource\Criterias;
use Freedom\ApiResource\Criterias\BaseResourceCriteria;

/**
 * Class WithCriteria.
 *
 * @package namespace App\Criteria;
 */
class FieldCriteria extends BaseResourceCriteria
{
    public function getFields() : array{
        return ['*'];
    }

    public function handle($model)
    {
        if($this->shouldSkipCriteria()){
            return $model;
        }
        return $this->newQuery($model);
    }


    protected function newQuery($model){
        return $this->buildQuery($model);
    }

    protected function buildQuery($query){
        foreach($this->inputs as $field ){
            if($this->shouldSkipField($field,$field)){
                continue;
            }
            $query = $this->$field($query,$field);
        }
        return $query;
    }


    protected function buildDefaultQuery($query,$field){
        return $query->addSelect($field);
    }

    public function getRequestField() : string {
        return 'field';
    }

    protected function shouldApply($model, $repository): bool
    {
        return $repository->getMethod() != 'count';
    }

}
