<?php

namespace Freedom\ApiResource\Criterias;

use Freedom\ApiResource\Parsers\RequestCriteriaParser;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Support\Arr;

/**
 * Class JobFreeTextCriteria.
 *
 * @package namespace App\Criteria;
 */
abstract class  SearchCriteria implements CriteriaInterface
{
    protected $table;

    protected $searchables;

    protected $mapping;

    protected $searchValues;

    public function __construct(array $searchValues=[])
    {
        $this->setInput($searchValues);
    }

    public function setInput(array $searchValues){
        $this->searchValues = $searchValues;
    }

    public function setTable($table){
        $this->table = $table;
    }

    /*
    * CREATE SEARCH DATA MAPPING  FROM Fields Searchable
    * [ SEARCH_FIELD => DB_COLUMN ]
    * IF NO DB_COLUMN IS SPECIFIED THEN FIELD IS USED
    * @return array
    */
    protected function makeMapping() : array{
        $fields = $this->getFieldsSearchable();
        $mapping = [];
        foreach($fields as $field => $column){
            $mapping[ is_string($field) ? $field : $column ] = $column;
        }
        return $this->mapping = $mapping;
    }

    /*
    * CREATE SEARCH FIELDS SEARCHABLES
    * GET KEYS IN THE SEARCH DATA MAPPING
    * @return array
    */
    protected function makeSearchables() : array {
       return $this->searchables = array_keys( $this->mapping ? $this->mapping : $this->makeMapping() );
    }

    protected function makeSearchData() : array {
        $input = count($this->searchValues) > 0 ? $this->searchValues : request()->input('search',[]);
        $search = RequestCriteriaParser::parseField($input,'search');
        $searchables = $this->searchables ? $this->searchables : $this->makeSearchables();
        return Arr::only($search,$searchables);
    }

    /*
    * GET SEARCH FIELD RESPECTIVE DB_COLUMN
    * TAKEN FROM THE MAPPING
    * @return string
    */
    protected function getColumn($field) : string
    {
        $column = Arr::get($this->mapping,$field,$field);

        $table = $this->table;
        if(is_array($column)){
            array_walk_recursive($column,function(&$column,$key) use($table){
                return !$table ? $column : $table.'.'.$column;
            });
            return $column;
        }
        return $table ? $table.'.'.$column : $column ;
    }


    /*
    * BUILD THE SEARCH QUERY
    * @return QueryBuilder
    */
    public function handle($model)
    {
        $searches = $this->makeSearchData();

        foreach($searches as $field => $value){
            $column = $this->getColumn($field);
            $value = trim($value);
            $result = $this->specialQuery($model,$value,$field,$column);
            $model = $result ? $result : $model->where($column,$value);
        }

        return $model;
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
    
    abstract protected function specialQuery($query,$value,$field,$column);

    /*
    * GET THE FIELDS THAT ARE SEARCHABLE IN MODEL
    */
    abstract public function getFieldsSearchable() : array;
}
