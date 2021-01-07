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
    abstract protected function defaultOrderBy($query);

    abstract public function getFields() : array;

    public function handle($model){
        if ($this->shouldSkipCriteria()) {
            return $this->shouldOrderByDefault() ? $this->defaultOrderBy($model) : $model;
        }

        return $this->newQuery($model);
    }

    protected function newQuery($model){
        return $this->buildQuery($model);
    }

    protected function buildQuery($query){
        foreach($this->inputs as $field => $value){
            if($this->shouldSkipField($field,$value)){
                continue;
            }
            $sortBy = $value ?? $this->defaultSortOperation();
            $query = $this->$field($query,$field,$sortBy);
        }
        return $query;
    }

    protected function buildDefaultQuery($query,$field,$sortBy){
        return $query->orderBy($this->getColumn($field),$sortBy);
    }

    protected function shouldApply($model, $repository): bool
    {
        return $repository->getMethod() != 'count';
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

}