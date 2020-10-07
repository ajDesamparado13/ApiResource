<?php

namespace Freedom\ApiResource\Traits;

use Illuminate\Support\Str;

trait FullTextSearch
{
    /**
     * Replaces spaces with full text search wildcards
     *
     * @param string $term
     * @return string
     */
    protected function fullTextWildcards($term, $operator)
    {
        switch(strtolower($operator))
        {
            case 'exact':
                return  $this->exactSearch($term); break;
            case 'or': 
                return $this->orSearch($this->clearReservedSymbols($term));break;
            default:
                return $this->defaultSearch($this->clearReservedSymbols($term));break;
            break;

        }
    }

    protected function clearReservedSymbols($term)
    {
        // removing symbols used by MySQL
        $reservedSymbols = [
            '-',
            '+',
            '<',
            '>',
            '@',
            '(',
            ')',
            '~',
            '（',
            '）',
            '・',
            '*',
            //'ー',
            //'－',
            '&',
            '＆',
            '　',
        ];
        #$reservedSymbols = ['-', '+', '<', '>', '@', '(', ')', '~','・','*','－'];
        return trim(str_replace($reservedSymbols, ' ', $term));
    }


    protected function orSearch($expression)
    {
        $expressions = array_filter(explode(',', $this->replaceCommaByte($expression)));

        return implode(' ', array_map(function($group){
            $terms = array_filter(explode(' ', $group));
            return $this->enclosed_in_parenthesis($this->makeAgainstWords($terms),"or");
        },$expressions));
    }

    protected function defaultSearch($term)
    {
        $words = array_filter(explode(' ', $term));
        if (count($words) == 1) {
            return $this->enclosed_in_quotes($this->enclosed_in_dquotes($term));
        }
        return $this->enclosed_in_quotes($this->makeAgainstWords($words));
    }

    protected function makeAgainstWords(array $terms,$operator="AND")
    {
        return implode(' ' ,array_map(function($word) use($operator){
            $length_check = strtolower($operator) == 'or' ? strlen($word) >= 3  : strlen($word) > 3;
            return $length_check ? $this->AND($word) : $word;
        },$terms));
    }

    protected function exactSearch($term)
    {
        return $this->enclosed_in_dquotes($term);
    }

    protected function AND($word)
    {
        return '+' . $this->enclosed_in_dquotes($word);
    }

    protected function OR($word)
    {
        return '-' . $this->enclosed_in_dquotes($word);
    }

    protected function enclosed_in_dquotes($word)
    {
        return "\\\"" . trim($word) . "\\\"";
    }

    protected function enclosed_in_quotes($word)
    {
        return "'" . trim($word) . "'";
    }

    protected function enclosed_in_parenthesis($term)
    {
        return "'(" . str_replace("'",'',$term) . ")'";
    }

    /*
    * replace 2 byte comma character to 1 byte comma character
    */
    protected function replaceCommaByte($term)
    {
        return str_replace(['，','、'],',',$term);
    }

    public function fullText($query,$term,$columns,$operator='plus')
    {
        return $query->whereRaw($this->fullTextSql($term,$columns,$operator));
    }

    public function fullTextSql($term,$columns,$operator='plus')
    {
        return Str::replaceArray("?",[
            is_array($columns) ? implode(',',$columns) : $columns,
            $this->fullTextWildcards($term,$operator)
        ],"MATCH (?) AGAINST (? IN BOOLEAN MODE)");
    }

    public function orderByRelevance($query,$name,$term,$columns,$order='desc',$operator='plus')
    {
        $query->addSelect(\DB::raw($this->relevanceSql($name,$term,$columns,$operator)));
        return $query->orderBy($name,$order);
    }

    public function relevanceSql($name, $term, $columns, $operator = 'plus')
    {
        $sql = str_replace('IN BOOLEAN MODE','',$this->fullTextSql($term,$columns,$operator));
        return $sql. " AS " . $name;
    }

    /**
     * Scope a query that matches a full text search of term.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFullText($query, $term, $columns, $operator = 'plus')
    {
        return $this->fullText($query,$columns,$term,$operator);
    }

    public function scopeOrderByRelevance($query,$name,$term,$columns,$order='desc',$operator='plus')
    {
        return $this->orderByRelevance($query,$name,$term,$columns,$order,$operator);
    }

}


