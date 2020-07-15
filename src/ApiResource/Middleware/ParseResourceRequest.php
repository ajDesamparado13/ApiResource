<?php

namespace Freedom\ApiResource\Middleware;

use Freedom\ApiResource\Parsers\RequestCriteriaParser;
use Closure;

class ParseResourceRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $result = RequestCriteriaParser :: parseRequest($request);
        if($result){
            $request->merge( $result);
        }

        return $next($request);
    }
}
