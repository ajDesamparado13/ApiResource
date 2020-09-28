<?php

namespace Freedom\ApiResource\Traits;

use Illuminate\Support\Arr;
use Carbon\Carbon;

trait RangeSearch{

    /*
    * Removes non numeric / floating characters in a string
    */
    public function getNumberOnly($val)
    {
        return  floatval(preg_replace('/[^-0-9.]/','',$val));
    }

    /*
    * Creates a Carbon Object from String value
    * @param $value string
    * @param $is_last boolean
    * returns Carbon\Carbon
    */
    public function createFromDate($value,$is_last=false)
    {
        $value = preg_replace("@[/\\\]@","-",$value);
        $value = array_filter(explode('-',$value));
        $year = $value[0];
        $month = isset($value[1]) ? $value[1] : 1;
        $date = null;

        if(count($value) <= 2){
            if($is_last){
                //get the last date of the month or month and date if month is not specified
                $month = count($value) == 1 ? $month = 12 : $month;
                $date = Carbon::createFromDate($year,$month+1,0);
            }else{
                $date = Carbon::createFromDate($year,$month,1);
            }
            return $date;
        }

        $day = isset($value[2]) ? $value[2] : 1;
        $date = Carbon::createFromDate($year,$month,$day);

        return $date;
    }

    /*
    * Builds a Where query with greater than or equal FROM
    * returns Illuminate\Database\Query\Builder
    */
    public function fromWhere($model,$field,$value){
        $value = $this->getNumberOnly( $value );
        $rounded = floor($value);
        if($value != $rounded){
            return $model->where($field,'>=',$value);
        }

        return $model->where($field,'>=',$rounded);
    }

    /*
    * Builds a Where query with greater than or equal to
    * returns Illuminate\Database\Query\Builder
    */
    public function toWhere($model,$field,$value,array $options = []){
        $value = $this->getNumberOnly( $value );
        if($value == 0 || Arr::get($options,'type') == 'percentage'){
            return $model->where($field,'<=',$value);
        }
        $rounded = floor($value);
        if($value != $rounded){
            $decimals = substr($value,strpos($value,'.'),strlen($value));
            $up = (float)(str_pad("0.",strlen($decimals),"0",STR_PAD_RIGHT)."1");
            return $model->where($field,'<',$value+$up);
        }
        return $model->where($field,'<=',$rounded);
        /*
         * if value is greater than 0 add 1
         * if value is lesser than 0 subtract 1
         */
        $up = $value  == 0  ? 0 : $value < 0 ? -1 : 1;
        //$up = $value  == 0  ? 0 : $value < 0 ? -0.9 : 0.9;

        return $model->where($field,'<',$rounded+$up);
    }

    /*
    * Builds a Having query with greater than or equal From
    * returns Illuminate\Database\Query\Builder
    */
    public function fromHaving($model,$field,$value, $raw=false){
        $value = $this->getNumberOnly( $value );
        $rounded = floor($value);

        $value = $value != $rounded ?  $value : $rounded;
        if($raw && !is_array($value)){
            $value = [$value];
        }
        return $raw ? $model->havingRaw($field.' >= ?',$value) : $model->having($field,'>=',$value);
    }

    public function fromHavingRaw($model,$field,$value){
        return $this->fromHaving($model,$field,$value,true);
    }



    /*
    * Builds a Having query with lesser than or equal To
    * returns Illuminate\Database\Query\Builder
    */
    public function toHaving($model,$field,$value,array $options = [], $raw=false){
        $value = $this->getNumberOnly( $value );
        if($value == 0 || Arr::get($options,'type') == 'percentage'){
            return $model->having($field,'<=',$value);
        }
        $rounded = floor($value);
        if($value != $rounded){
            $decimals = substr($value,strpos($value,'.'),strlen($value));
            $up = (float)(str_pad("0.",strlen($decimals),"0",STR_PAD_RIGHT)."1");
            return $model->having($field,'<',$value+$up);
        }
        return $model->having($field,'<=',$rounded);
        /*
         * if value is greater than 0 add 1
         * if value is lesser than 0 subtract 1
         */
        $up = $value  == 0  ? 0 : ( $value < 0 ? -1 : 1 );
        //$up = $value  == 0  ? 0 : $value < 0 ? -0.9 : 0.9;

        return $model->having($field,'<',$rounded+$up);
    }


}
