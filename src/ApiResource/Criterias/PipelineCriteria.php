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
        foreach($this->getCriterias() as $criteria){
            if(is_string($criteria)){
                $criteria = app()->makeWith($criteria,[ 'inputs' => $inputs ]);
            }

            if(!$criteria instanceof BaseResourceCriteria){
                throw new ResourceCriteriaException("Class " . get_class($criteria) . " must be an instance of" . BaseResourceCriteria::class );
            }

            $model = $criteria->handle($model);
        }
        return $model;
    }

}
