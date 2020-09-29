<?php
namespace Freedom\ApiResource\Contracts;

interface SearchablesInterface{
    public function resetMapping();

    public function resetSearchData();

    public function getFieldKeys() : array ;


    public function getSearchData(array $values=null,$fields=null) : array;
    

    public function getFieldsMapping() : array;

    public function getFieldsLabel() : array;
    

    public function getFieldsColumn() : array;
    

    public function getColumn(string $field);

}