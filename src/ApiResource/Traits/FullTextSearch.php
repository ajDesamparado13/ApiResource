<?php

namespace App\Criteria\Tenant;

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
        $operator = strtolower($operator);
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

        if ($operator == 'exact') {
            return $this->exact_search(trim($term));
        }

        $term = trim(str_replace($reservedSymbols, ' ', $term));
        if ($operator == 'or') {
            return $this->or_search($term);
        }


        return $this->default_search($term);
    }

    private function exact_search($term)
    {
        return '"' . $term . '"';
    }

    private function or_search($expression)
    {
        //replace 2 byte comma character to 1 byte comma character
        $expression = str_replace('，', ',', $expression);
        $expression = str_replace('、', ',', $expression);

        $expressions = explode(',', $expression);

        $searchTerm = [];
        foreach ($expressions as $group) {
            $terms = array_filter(explode(' ', $group));
            $words = [];
            foreach ($terms as $term) {
                if (strlen($term) == 0) {
                    continue;
                }
                /*
                * applying + operator (required word) only big words
                * because smaller ones are not indexed by mysql
                */
                if (strlen($term) >= 3) {
                    $term = '+"' . $term . '"';
                }
                $words[] = $term;
            }
            $expr_string = "(".implode(' ',$words).")";
            $searchTerm[] = $expr_string;
        }
        return implode(' ', $searchTerm);
    }

    private function default_search($term)
    {
        $words = array_filter(explode(' ', $term));
        if (count($words) == 1) {
            return '"' . $term . '"';
        }

        foreach ($words as $key => $word) {
            if (strlen($word) == 0) {
                continue;
            }
            /*
             * applying + operator (required word) only big words
             * because smaller ones are not indexed by mysql
             */
            if (strlen($word) >= 3) {
                $word = '+"' . $word . '"';
            }
            $words[$key] = $word;
        }

        return implode(' ', $words);
    }

    public function fullText($query,$term,$columns,$operator='plus')
    {
        return $query->whereRaw($this->fullTextSql($term,$columns,$operator));
    }

    public function fullTextSql($term,$columns,$operator='plus')
    {
        return Str::replaceArray("?",[
            $columns,
            $this->fullTextWildcards($term,$operator)
        ],"MATCH (?) AGAINST (? IN BOOLEAN MODE)");
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
        $query->addSelect(\DB::raw($this->relevanceSql($name,$term,$columns,$operator)));
        return $query->orderBy($name,$order);
    }

}


