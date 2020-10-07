<?php

namespace Freedom\ApiResource\Criterias;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Support\Arr;

/**
 * Class ChecklistCriteria.
 *
 * @package namespace App\Criteria;
 */
class ChecklistCriteria implements CriteriaInterface
{
    protected $key;

    protected $inputs;

    public function __construct($key="id",array $inputs=[])
    {
        $this->setInputs($inputs);
        $this->setKey($key);
    }

    public function setInputs($inputs){
        $this->inputs = $inputs;
        return $this;
    }

    public function getInputs(){
        return $this->inputs;
    }

    public function setKey($key){
        $this->key = $key;
        return $this;
    }

    public function getKey(){
        return $this->key;
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

    protected function getCheckedAllValue() : bool
    {
        return filter_var($this->getValue($this->getCheckedAllKey()),FILTER_VALIDATE_BOOLEAN);
    }

    protected function getCheckedListValue() : array
    {
        $value = $this->getValue($this->getCheckedListKey());
        return array_filter(is_array($value) ? $value : explode(',',$value));
    }

    protected function getValue($key)
    {
        return  Arr::get($this->inputs, $this->getCheckedAllKey(), request()->get($key));
    }

    public function getCheckedAllKey() : string
    {
        return 'checked_all';
    }

    public function getCheckedListKey() : string
    {
        return 'checked_list';
    }

    public function handle($model)
    {
        $checked_list = $this->getCheckedListValue();

        if(empty($checked_list)){
            return $model;
        }

        if($this->getCheckedAllValue() ){
            return $model->whereNotIn($this->key,$checked_list);
        }

        return $model->whereIn($this->key,$checked_list);
    }
}
