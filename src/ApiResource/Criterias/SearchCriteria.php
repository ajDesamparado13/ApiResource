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
    protected $table="";

    protected $getFromSingleton=false;

    public function searchables(){
        return \Freedom\ApiResource\Searchables\GenericSearchables::class;
    }

    /*
    * CREATE SEARCH FIELDS SEARCHABLES
    * GET KEYS IN THE SEARCH DATA MAPPING
    * @return array
    */
    protected function makeSearchables(){
        $searchables = app()->makeWith($this->searchables(),['fieldsSearchables' => $this->getFieldsSearchable()]);

        if (!$searchables instanceof SearchablesInterface) {
            throw new SearchablesInterface("Class {$this->searchables()} must be an instance of". SearchablesInterface::class);
        }
        return  $searchables;
    }

    public function setTable($table){
        $this->table = $table ?? "";
    }

    /*
    * BUILD THE SEARCH QUERY
    * @return QueryBuilder
    */
    public function handle($model)
    {
        $searchables = $this->makeSearchables();
        $searchData = $searchables->getSearchData($this->inputs);

        if($this->shouldSkipCriteria($searchData)){
            return $model;
        }

        return $this->newQuery($model,$searchables,$searchData);
    }

    protected function newQuery($model,SearchablesInterface $searchables, array $searchData){
        return $this->buildQuery($model,$searchables,$searchData);
    }

    protected function buildQuery($query,SearchablesInterface $searchables, array $searchData){
        foreach($searchData as $field => $value){
            $value = is_string($value) ? trim($value) : $value;
            if($this->shouldSkipField($field,$value)){
                continue;
            }
            $column = $searchables->getColumn($field,$this->table);
            $result = $this->specialQuery($query,$value,$field,$column,$searchables);
            $query = $result ?? $query->where($column,$value);
        }
        return $query;
    }

    abstract protected function specialQuery($query,$value,$field,$column, SearchablesInterface $searchables);

    /*
    * GET THE FIELDS THAT ARE SEARCHABLE IN MODEL
    */
    abstract public function getFieldsSearchable() : array;

}
