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

    public function handle($model){
        $inputs = $this->makeInputs();
        if($this->shouldSkipCriteria($inputs)){
            return $model;
        }
        return $this->newQuery($model,$inputs);
    }

    protected function newQuery($model,array $inputs){
        return $this->buildQuery($model,$this->getCriterias(),$inputs);
    }

    protected function buildQuery($query,array $criterias,array $inputs){
        foreach($criterias as $criteria){
            $criteria = $this->makeCriteria($criteria,$inputs);
            $query = $this->specialQuery($query,$criteria,$inputs);
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

    protected function specialQuery($query,$criteria,array $inputs){
        return $criteria->handle($query);
    }

}
