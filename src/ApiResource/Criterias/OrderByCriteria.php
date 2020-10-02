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
        foreach($orderBy as $key => $value){
            if($this->shouldSkipField($key,$value)){
                continue;
            }

            $column = is_string($key) ? $key : $value;
            $sortBy = is_string($key) ? $value : $this->defaultSortOperation();

            $result = $this->specialQuery($query,$column,$sortBy);
            $query = $result ? $result : $query->orderBy($column,$sortBy);
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

    protected function criteriaField(): string
    {
        return 'orderBy';
    }

    abstract protected function specialQuery($query,$orderByField,$sortBy);

    abstract protected function defaultOrderBy($query);

    abstract public function getFields() : array;
}
