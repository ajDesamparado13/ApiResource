<?php

namespace Freedom\ApiResource\Criterias;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class OrderByCriteria.
 *
 * @package namespace App\Criteria;
 */
abstract class OrderByCriteria implements CriteriaInterface
{
    protected $orderBy;
    protected $skipOn=[];

    public function __construct($orderBy=['id' => 'desc'])
    {
        $this->setOrderBy($orderBy);
    }

    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
    }


    public function handle($model){

        $request = request();
        $orderBy = $request->get('orderBy', null);

        if ($this->shouldSkipCriteria($orderBy)) {
            return $this->defaultOrderBy($model);
        }

        return $this->newQuery($model,$orderBy);
    }

    protected function newQuery($model,$orderBy){
        return $this->buildQuery($model,$orderBy);
    }

    protected function buildQuery($query,$orderBy){
        foreach($orderBy as $key => $value){
            if($this->shouldSkipField($key,$value)){
                continue;
            }

            $column = is_string($key) ? $key : $value;
            $sortBy = is_string($key) ? $value : $this->defaultSortOperation();

            $result = $this->specialQuery($query,$column,$sortBy);
            $query = $result ? $result : $query->orderBy($column,$sortBy);
        }
        return $query;
    }

    protected function shouldSkipCriteria($orderBy) : bool
    {
        return empty($orderBy);
    }

    protected function shouldSkipField($field,$value) : bool {
        return in_array($field,$this->skipOn);
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

    protected function defaultSortOperation()
    {
        return 'desc';
    }

    abstract protected function specialQuery($query,$orderBy,$sortBy);

    abstract protected function defaultOrderBy($query);
}
