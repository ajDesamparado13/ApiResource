<?php
namespace Freedom\ApiResource\Searchables;

use Freedom\ApiResource\Contracts\SearchablesInterface;
use Illuminate\Support\Arr;


class GenericSearchables implements SearchablesInterface{

    protected $mapping;

    protected $searchData;

    protected $table;

    protected function requestKey()
    {
        return 'search';
    }

    public function __construct(array $fieldsSearchables=[])
    {
        $this->mapping = $this->makeMapping(empty($fieldsSearchables) ? $this->getFieldsSearchable() : $fieldsSearchables);
        $this->searchData = $this->makeSearchData();
    }

    public function setTable($table){
        $this->table = $table;
        return $this;
    }

    public function getFieldsSearchable(){
        return [];
    }

    public function resetMapping()
    {
        $this->mapping = $this->makeMapping();
        return $this;
    }

    public function resetSearchData()
    {
        $this->searchData = $this->makeSearchData();
        return $this;
    }

    /*
    * CREATE SEARCH DATA MAPPING  FROM Fields Searchable
    * [ SEARCH_FIELD => DB_COLUMN ]
    * IF NO DB_COLUMN IS SPECIFIED THEN FIELD IS USED
    * @return array
    */
    public function makeMapping(array $fields=[]) : array{
        $mapping = [];
        foreach($fields as $field => $value){
            $key = !is_numeric($field) ? $field : ( is_array($value) ? Arr::get($value,'column',$field) : $value ) ;
            $mapping[$key] = $value;
        }
        return $mapping;
    }

    public function makeSearchData(array $searchData=[]) : array {
        $key = $this->requestKey();
        $input = empty($searchData) ? request()->input($key,[]) : $searchData;
        $search = \Freedom\ApiResource\Parsers\RequestCriteriaParser::parseField( $input ,$key);
        return Arr::only( $search,$this->getFieldKeys() );
    }

    public function mergeSearchData(array $searchData){
        $this->searchData = array_merge($this->searchData, $searchData);
        return $this;
    }

    public function mergeMapping(array $mapping){
        $this->mapping = array_merge($this->mapping,$this->makeMapping($mapping));
        return $this;
    }

    public function getFieldKeys() : array {
        return array_keys($this->mapping);
    }


    public function getSearchData(array $values=null,$fields=null) : array
    {
        $input = empty($values) ? $this->searchData : $values;
        $searchables = empty($fields) ? $this->getFieldKeys() : array_keys($this->makeMapping($fields));
        return Arr::only($input,$searchables);
    }

    public function getMapping() : array {
        return $this->mapping;
    }

    public function getFieldsLabel() : array
    {
        return array_map(function($item,$field){
            return is_array($item) ?  Arr::get($item,'label',$item) : $item;
        },$this->mapping);
    }

    public function getFieldsColumn() : array
    {
        return array_map(function($item,$field){
            return is_array($item) ?  Arr::get($item,'column',$item) : $item;
        },$this->mapping);
    }

    public function getColumn(string $field, string $table=""){
        $map = Arr::get($this->mapping,$field);
        $table = empty($table) ? $this->table : $table;

        if(!$map){
            return null;
        }

        $column =  is_array($map) ? Arr::get($map,'column',$map) : $map;

        if(empty($table)){
            return $column;
        }

        if(is_array($column)){
            return array_map(function($col) use($table){
                return $table.'.'.$col;
            },$column);
        }

        return $table.'.'.$column;
    }

    public function getValue(string $field){
        return Arr::get($this->searchData,$field);
    }

}
