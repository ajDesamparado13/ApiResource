<?php
namespace Freedom\ApiResource\Criterias;

use Freedom\ApiResource\Parsers\RequestCriteriaParser;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class BaseResourceCriteria implements CriteriaInterface
{

    protected $skipOn = [];

    protected $inputs = [];

    protected $mapping = [];

    public function __construct(array $inputs=[])
    {
        $this->setInputs($inputs);
    }

    abstract public function getFields() : array;

    abstract public function getRequestField() : string;

    abstract public function handle($model);

    public function setInputs(array $inputs){
        $this->inputs = $inputs;
        return $this;
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
        $this->makeMapping();
        $this->makeInputs();
        return $this->handle($model);
    }

    protected function shouldApply($model,$repository) : bool
    {
        return true;
    }

    protected function makeInputs()
    {
        $field = $this->getRequestField();
        $inputs = empty($this->inputs) ? (empty($field) ? request()->all() : request()->input($field,[]) ) : $this->inputs;
        $this->setInputs($this->filterInputs(RequestCriteriaParser::parseField($inputs,$field)));
        return $this->inputs;
    }

    protected function filterInputs( array $inputs ) : array {
        if(!$this->shouldFilterInputs()){
            return $inputs;
        }

        $keys = $this->getKeys();
        if(Arr::first($keys) === '*'){
            return $inputs;
        }

        return Arr::where($inputs,function($value,$key) use ($keys){
            $find = !is_numeric($key) ? $key : $value;
            return in_array($find,$keys);
        });
    }

    public function getKeys() : array {
        if(empty($this->mapping)){
            $this->makeMapping();
        }
        return array_keys($this->getMapping());
    }

    protected function shouldFilterInputs() : bool{
        return true;
    }

    public function makeMapping() : array {
        $this->mapping = [];
        if(!$this->shouldCreateMapping() ){
            return $this->mapping = $this->getFields();
        }
        foreach($this->getFields() as $field => $value){
            $key = !is_numeric($field) ? $field : ( is_array($value) ? Arr::get($value,'column',$field) : $value ) ;
            $this->mapping[$key] = $value;
        }
        return $this->mapping;
    }


    protected function shouldCreateMapping() : bool {
        return true;
    }

    public function setMapping(array $mapping){
        $this->mapping = $mapping;
        return $this;
    }

    public function getInputs(){
        return $this->inputs;
    }

    public function getColumn(string $field, string $table=""){
        $map = Arr::get($this->mapping,$field);

        if(empty($map)){
            return $field;
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
        return Arr::get($this->inputs,$field);
    }

    protected function shouldSkipField($field,$value) : bool {
        return in_array($field,$this->skipOn);
    }

    protected function shouldSkipCriteria() : bool{
        return empty($this->inputs);
    }

    public function getMapping() : array {
        return $this->mapping;
    }

    public function __call(string $name, array $args)
    {
        if(empty($name)){
            return;
        }

        if(method_exists($this,$name)){
            return call_user_func_array(array($this,$name),$args);
        }

        $queryMethod = "query" . Str::ucfirst(Str::camel($name));
        $name = method_exists($this,$queryMethod) ? $queryMethod : 'buildDefaultQuery';
        return call_user_func_array(array($this,$name),$args);
    }

}
