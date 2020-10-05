<?php

namespace Freedom\ApiResource\Criterias;

use Illuminate\Support\Arr;

/**
 * Class OrderByCriteria.
 *
 * @package namespace App\Criteria;
 */
abstract class OrderByCriteria extends BaseResourceCriteria
{
    public function handle($model){
        $inputs = $this->makeInputs();

        if ($this->shouldSkipCriteria($inputs)) {
            return $this->shouldOrderByDefault() ? $this->defaultOrderBy($model) : $model;
        }

        return $this->newQuery($model,$inputs);
    }

    protected function newQuery($model,$orderBy){
        return $this->buildQuery($model,$orderBy);
    }

    protected function buildQuery($query,$orderBy){
        $mapping = $this->makeMapping($this->getFields());
        foreach($orderBy as $field => $value){
            if($this->shouldSkipField($field,$value)){
                continue;
            }

            $column = $mapping[$field];
            $sortBy = $value ?? $this->defaultSortOperation();
            $query = $this->specialQuery($query,$field,$column,$sortBy) ?? $query->orderBy($column,$sortBy);
        }
        return $query;
    }

    protected function shouldOrderByDefault() : bool
    {
        return true;
    }

    protected function defaultSortOperation()
    {
        return 'desc';
    }

    public function getRequestField(): string
    {
        return 'orderBy';
    }


    abstract protected function specialQuery($query,$field,$column,$sortBy);

    abstract protected function defaultOrderBy($query);

    abstract public function getFields() : array;
}
