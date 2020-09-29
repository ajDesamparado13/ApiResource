<?php

namespace Freedom\ApiResource\Criterias;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Freedom\ApiResource\Contracts\SearchablesInterface;

/**
 * Class JobFreeTextCriteria.
 *
 * @package namespace App\Criteria;
 */
abstract class  SearchCriteria implements CriteriaInterface
{
    protected $table="";

    protected $searchValues;

    protected $skipOn = [ ];

    protected $getFromSingleton=false;

    public function __construct(array $searchValues=[])
    {
        $this->setInput($searchValues);
    }

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
            throw new SearchablesInterface("Class {$this->searchables()} must be an instance of \Freedom\ApiResource\Contracts\SearchablesInterface");
        }
        return  $searchables;
    }

    public function setInput(array $searchValues){
        $this->searchValues = $searchValues;
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
        $searchData = $searchables->getSearchData($this->searchValues);

        if($this->shouldSkipCriteria($searchData)){
            return $model;
        }

        return $this->buildQuery($model,$searchables,$searchData);
    }

    protected function buildQuery($query,$searchables,$searchData){
        foreach($searchData as $field => $value){
            $value = is_string($value) ? trim($value) : $value;
            if($this->shouldSkip($field,$value)){
                continue;
            }
            $column = $searchables->getColumn($field,$this->table);
            $result = $this->specialQuery($query,$value,$field,$column,$searchables);
            $query = $result ?? $query->where($column,$value);
        }
        return $query;
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
        //$table = "";
        //if(is_a($model,'Illuminate\Database\Query\JoinClause')){
        //    $table = $model->table;
        //}else if(is_a($model,'Illuminate\Database\Query\Builder')){
        //    $table =  $model->from;
        //}else{
        //    $table = $model->getModel()->getTable();
        //}
        return $this->handle($model);
    }
    
    abstract protected function specialQuery($query,$value,$field,$column,SearchablesInterface $searchables);

    /*
    * GET THE FIELDS THAT ARE SEARCHABLE IN MODEL
    */
    abstract public function getFieldsSearchable() : array;


    protected function shouldSkip($field,$value) : bool
    {
        return in_array($this->skipOn,$field);
    }

    protected function shouldSkipCriteria($searchData){
        return count($searchData) <= 0;
    }
}
