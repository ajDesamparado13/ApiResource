<?php

namespace Freedom\ApiResource\Criterias;

use Freedom\ApiResource\Contracts\SearchablesInterface;

/**
 * Class JobFreeTextCriteria.
 *
 * @package namespace App\Criteria;
 */
abstract class  SearchCriteria extends BaseResourceCriteria
{
    abstract public function getFields() : array;

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
        foreach($this->inputs as $field => $value){
            $value = is_string($value) ? trim($value) : $value;
            if($this->shouldSkipField($field,$value)){
                continue;
            }
            $query = $this->$field($query,$value,$field);
        }
        return $query;
    }

    public function getRequestField() : string {
        return 'search';
    }

    protected function buildDefaultQuery($query,$value,$field){
        return $query->where($this->getColumn($field),$value);
    }

}
