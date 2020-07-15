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

    public function setTable($table){
        $this->table = $table;
    }

    protected function makeMapping(){
        $fields = $this->getFieldsSearchable();
        $mapping = [];
        foreach($fields as $field => $column){
            $mapping[ is_string($field) ? $field : $column ] = $column;
        }
        return $this->mapping = $mapping;
    }

    protected function makeSearchables(){
       return $this->searchables = array_keys( $this->mapping ? $this->mapping : $this->makeMapping() );
    }

    protected function makeSearchData(){
        $input = request()->input('search',[]);
        $search = RequestCriteriaParser::parseField($input,'search');
        $searchables = $this->searchables ? $this->searchables : $this->makeSearchables();
        return Arr::only($search,$searchables);
    }

    protected function getColumn($field)
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
    abstract public function getFieldsSearchable() : array;
}
