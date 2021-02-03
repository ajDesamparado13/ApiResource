<?php

namespace Freedom\ApiResource\Parsers;
use \Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RequestCriteriaParser
{

    /*
    * Parse Query String into a array splitted by '&'
    * @param string $str
    * @param string $delimiter
    * return array
    */
    public static function parseQueryString($str,$delimiter = '&')
    {
        if($str[0] == '?'){
            $str = substr($str,1);
        }
        $query_body = [];
        foreach(explode('&',$str) as $q){
            $split_equals = explode('=',$q);
            $value = $split_equals[1];
            $key = $split_equals[0];
            $query_body[$key] = $value;
        }
        return $query_body;
    }

    /*
    * Parse String
    */
    public static function parseString($str,$key = 'search',$delimiter = '&')
    {
        $key_start_index = strpos($str,$key);
        $key_end_index = strpos($str,$delimiter,$key_start_index);

        if($key_end_index === false){
            $key_end_index = strlen($str);
        }else{
            $key_end_index -= 1;
        }

        $search = str_replace("{$key}=",'',substr($str,$key_start_index,$key_end_index));

        return self::parseSearchData($search);
    }

    /*
    * Parse HTTP Request string input $key
    * @param \Illuminate\Http\Request $request
    * @param string $request
    * @return array
    */
    public static function parseRequest(Request $request,$key = 'search')
    {
        $fields = [ 'search','meta','filter','orderBy','with','has'];
        $values = array_filter(Arr::only($request->query(),$fields));

        foreach($values as $key => $value){
            $values[$key] = self::parseField($value,$key);
        }
        return $values;
    }

    public static function parseField($value,$key){

        if(!$value){
            return [];
        }

        if(is_array($value)){
            return $value;
        }

        switch($key){
            case 'orderBy':
                return self::parseOrderByData($value);
            break;
            case 'search':
                return self::parseSearchData($value);
            break;
            case 'field':
                return self::parseByCommaData($value);
            break;
        }

        return array_filter(explode(';',$value));
    }

    public static function parseByCommaData(string $values) : array 
    {
        return !$values ? [] : array_filter(explode(',',$values));
    }

    public static function parseOrderByData(string $orderBy) : array
    {
        if(!$orderBy){
            return [];
        }

        $orderByData = [];

        if (stripos($orderBy, ':')) {
            $fields = explode(';', $orderBy);

            foreach ($fields as $row) {
                try {
                    list($field, $value) = explode(':', $row);
                    $orderByData[$field] = stripos($value,',') ? explode(',',$value) : $value;
                } catch (\Exception $e) {
                    //Surround offset error
                }
            }
        }
        return $orderByData;
    }

    /*
    * Parse $search string into an array splitted by colon (;) with $key : $value pair
    * @param string $search
    * @return array
    */
    public static function parseSearchData(string $search) : array
    {
        if(!$search) return [];

        $searchData = [];

        if (stripos($search, ':')) {
            $fields = explode(';', $search);

            foreach ($fields as $row) {
                try {
                    list($field, $value) = explode(':', $row);
                    $searchData[$field] = $value;
                } catch (\Exception $e) {
                    //Surround offset error
                }
            }
        }

        return $searchData;
    }


    /* TODO:
    * Parse Request with
    * Parse request join
    * Parse Request filter
    */

}
