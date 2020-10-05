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

    protected function filterInputs( array $inputs) : array {
        $fields = $this->getFields();
        if(Arr::first($fields) === '*'){
            return $inputs;
        }
        return Arr::where($inputs,function($value,$key) use ($fields){
            $find = !is_numeric($key) ? $key : $value;
            return in_array($find,$fields);
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
        return $this->handle($model);
    }

    protected function shouldSkipField($field,$value) : bool
    {
        return in_array($field,$this->skipOn);
    }

    protected function shouldSkipCriteria( array $inputs){
        return empty($inputs);
    }

    abstract public function getFields() : array;

    abstract public function getRequestField() : string;

    abstract public function handle($model);
}
