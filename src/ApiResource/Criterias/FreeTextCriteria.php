<?php

namespace Freedom\ApiResource\Criterias;

use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class JobFreeTextCriteria.
 *
 * @package namespace App\Criteria;
 */
abstract class FreeTextCriteria  implements CriteriaInterface
{
    protected $text;

    public function __construct($text=null)
    {
        $this->setText($text);
    }

    public function setText($text){
        $this->text = $text;
    }

    public function handle($model)
    {
        $input = $this->text ?? request()->input('search.text');
        $text = is_string($input) ? trim($input) : null;

        if(!$text){
            return $model;
        }

        $model = $model->where(function($query) use($text){
            return $this->specialQuery($query,$text);
        });

        return $model;
    }

    abstract protected function specialQuery($query,$text);

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
