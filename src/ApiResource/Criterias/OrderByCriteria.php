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

    public function __construct($orderBy=['id' => 'desc'])
    {
        $this->setOrderBy($orderBy);
    }

    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
    }

    abstract protected function specialQuery($query,$orderBy);

    abstract protected function defaultOrderBy($query);

    public function handle($model){

        $request = request();
        $orderBy = $request->get('orderBy', null);

        if (!isset($orderBy) && empty($orderBy)) {
            return $this->defaultOrderBy($model);
        }

        foreach($orderBy as $key => $value){

            $column = is_string($key) ? $key : $value;
            $sortBy = is_string($key) ? $value : $this->defaultSortOperation();

            $result = $this->specialQuery($model,$column,$sortBy);
            $model = $result ? $result : $model->orderBy($column,$sortBy);
        }

        return $model;
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
}
