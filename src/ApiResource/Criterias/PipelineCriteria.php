<?php

namespace Freedom\ApiResource\Criterias;

use Freedom\ApiResource\Criterias\BaseResourceCriteria;
use Freedom\ApiResource\Exceptions\ResourceCriteriaException;

/**
 * Class RequestCriteria.
 *
 * @package namespace App\Criteria\Tenant;
 */
abstract class PipelineCriteria extends BaseResourceCriteria
{
    abstract public function getCriterias() : array;


    public function getFields() : array {
        return ['*'];
    }

    public function getRequestField() : string {
        return '';
    }

    protected function shouldFilterInputs() : bool{
        return false;
    }

    public function handle($model){
        if($this->shouldSkipCriteria()){
            return $model;
        }
        return $this->newQuery($model);
    }

    protected function newQuery($model){
        return $this->buildQuery($model,$this->getCriterias());
    }

    protected function buildQuery($query,array $criterias){
        foreach($criterias as $value ){
            $criteria = $this->makeCriteria($value,$this->inputs);
            $query = $this->specialQuery($query,$criteria,$this->inputs);
        }
        return $query;
    }

    protected function makeCriteria($criteria,array $inputs){
        if(is_string($criteria)){
            $criteria = app()->makeWith($criteria,[ 'inputs' => $inputs ]);
        }

        if(!$criteria instanceof BaseResourceCriteria){
            throw new ResourceCriteriaException("Class " . get_class($criteria) . " must be an instance of" . BaseResourceCriteria::class );
        }
        return $criteria;
    }

    protected function specialQuery($query,$criteria){
        return $criteria->handle($query);
    }

}
