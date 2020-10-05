<?php
namespace Freedom\ApiResource\Criterias;

use Freedom\ApiResource\Parsers\RequestCriteriaParser;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Support\Arr;

abstract class BaseResourceCriteria implements CriteriaInterface 
{
    protected $inputs;

    protected $skipOn = [];

    public function __construct(array $inputs=[])
    {
        $this->inputs = $inputs;
    }
    
    public function setInputs(array $inputs){
        $this->inputs = $inputs;
        return $this;
    }

    public function getInputs(){
        return $this->inputs;
    }

    protected function makeInputs()
    {
        $field = $this->getRequestField();
        $input = empty($this->inputs) ? (empty($field) ? request()->all() : request()->input($field,[]) ) : $this->inputs;
        return $this->filterInputs(RequestCriteriaParser::parseField($input,$field));
    }

    public function makeMapping(array $fields=[]) : array {
        if(! $this->shouldCreateMapping()){
            return $fields;
        }
        $mapping = [];
        foreach($fields as $field => $value){
            $key = !is_numeric($field) ? $field : ( is_array($value) ? Arr::get($value,'column',$field) : $value ) ;
            $mapping[$key] = $value;
        }
        return $mapping;
    }

    protected function filterInputs( array $inputs ) : array {
        $keys = $this->getKeys();
        if(Arr::first($keys) === '*'){
            return $inputs;
        }
        return Arr::where($inputs,function($value,$key) use ($keys){
            $find = !is_numeric($key) ? $key : $value;
            return in_array($find,$keys);
        });
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
        if(!$this->shouldApply($model,$repository)){
            return $model;
        }
        return $this->handle($model);
    }

    protected function shouldApply($model,$repository) : bool
    {
        return true;
    }

    protected function shouldSkipField($field,$value) : bool
    {
        return in_array($field,$this->skipOn);
    }

    protected function shouldCreateMapping() : bool {
        return true;
    }

    protected function shouldSkipCriteria( array $inputs){
        return empty($inputs);
    }


    public function getMapping() : array {
        return $this->makeMapping($this->getFields());
    }

    public function getKeys() : array {
        return array_keys($this->getMapping());
    }

    abstract public function getFields() : array;

    abstract public function getRequestField() : string;

    abstract public function handle($model);
}
