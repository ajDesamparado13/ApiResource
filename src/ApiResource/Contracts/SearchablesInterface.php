<?php
namespace Freedom\ApiResource\Contracts;

interface SearchablesInterface {

    public function getFieldsSearchable();

    public function resetMapping();
    
    public function resetSearchData();
    
    /*
    * CREATE SEARCH DATA MAPPING  FROM Fields Searchable
    * [ SEARCH_FIELD => DB_COLUMN ]
    * IF NO DB_COLUMN IS SPECIFIED THEN FIELD IS USED
    * @return array
    */
    public function makeMapping(array $fields=[]) : array;

    public function makeSearchData(array $searchData=[]) : array ;

    public function mergeSearchData(array $searchData);

    public function mergeMapping(array $mapping);

    public function getFieldKeys() : array ;

    public function getSearchData(array $values=null,$fields=null) : array;

    public function getMapping() : array ;

    public function getFieldsLabel() : array;

    public function getFieldsColumn() : array;
    
    public function getColumn(string $field, string $table="");

    public function getValue(string $field);

}