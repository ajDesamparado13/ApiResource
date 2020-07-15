<?php

namespace Freedom\ApiResource\Criterias;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;
use Illuminate\Support\Str;

/**
 * Class OrderByCriteria.
 *
 * @package namespace App\Criteria;
 */
abstract class OrderByCriteria implements CriteriaInterface
{
    protected $orderBy;
    protected $sortBy;

    public function __construct($orderBy='id',$sortBy="desc")
    {
        $this->setOrderBy($orderBy);
        $this->setSortBy($sortBy);
        
    }

    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
    }

    public function setSortBy(string $sortBy)
    {
        $this->sortBy = $sortBy;
    }

    abstract protected function specialQuery($query,$orderBy,$sortBy);

    abstract protected function defaultOrderBy($query);

    protected function orderByJoin($query,$sortTable,$sortColumn,$sortBy)
    {
        /*
        * ex.
        * products|description -> join products on current_table.product_id = products.id order by description
        *
        * products:custom_id|products.description -> join products on current_table.custom_id = products.id order
        * by products.description (in case both tables have same column name)
        */
        $table = $query->getModel()->getTable();

        $split = explode(':', $sortTable);
        if(count($split) > 1) {
            $sortTable = $split[0];
            $keyName = $table.'.'.$split[1];
        } else {
            /*
            * If you do not define which column to use as a joining column on current table, it will
            * use a singular of a join table appended with _id
            *
            * ex.
            * products -> product_id
            */
            $prefix = Str::singular($sortTable);
            $keyName = $table.'.'.$prefix.'_id';
        }

        $query = $query
            ->leftJoin($sortTable, $keyName, '=', $sortTable.'.id')
            ->orderBy($sortColumn, $sortBy)
            ->addSelect($table.'.*');

        return $query;
    }

    public function handle($model){

        $request = request();
        $orderBy = $request->get('orderBy', null);
        $sortBy = $request->get('sortBy', 'asc');

        if (!isset($orderBy) && empty($orderBy)) {
            return $this->defaultOrderBy($model);
        }

        foreach($orderBy as $column){
            $split = explode('|',$column);

            if(count($split) > 1 ){
                $result =  $this->orderByJoin($model,$split[0],$split[1],$sortBy);
            }else{
                $result = $this->specialQuery($model,$column,$sortBy);
            }
            
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
}
