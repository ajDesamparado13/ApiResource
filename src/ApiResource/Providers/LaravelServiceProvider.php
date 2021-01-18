<?php

namespace Freedom\ApiResource\Providers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Freedom\ApiResource\Contracts\JsonResourceInterface;
use Freedom\ApiResource\Middleware\ParseResourceRequest;
use Freedom\ApiResource\Resources\JsonResource;

class LaravelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMiddlewares();
        $this->loadResponseMacro();
        $this->app->bind(JsonResourceInterface::class,JsonResource::class);
    }

    protected function loadMiddlewares()
    {
        $this->app['router']->aliasMiddleware('resource.parse-request',ParseResourceRequest::class);

    }

    protected function loadResponseMacro()
    {
        Response::macro('resource',function($data,JsonResourceInterface $transformer, array $response=[]){
            $transformer->resource = $data;
            $is_resource_collection = is_a($data,'Illuminate\Pagination\LengthAwarePaginator') || is_a($data,'Illuminate\Database\Eloquent\Collection');
            $is_resource_model = is_a($data,'Illuminate\Database\Eloquent\Model');
            if($is_resource_collection || $is_resource_model){
                $transformer->additional($response);
                $result = call_user_func_array(array($transformer,$is_resource_collection ? 'collection' : 'make'),[ $data ]);
                $statusCode = empty($result->response()->getData()->data) ? 204 : 200;
                $result->response()->setStatusCode($statusCode);
                return $result;
            }
            $response['data'] = $data;
            return Response::json($response,empty($data) ? 200 : 204);
        });

        Response::macro('unauthorized', function ($message="Not Authenticated") {
            $message = !is_array($message) ? [$message] : $message;
            $response = array_merge(['type' => 'error'],$message);

            return Response::json([
                'error' => $response
            ], 401);
        });

        Response::macro('error', function ($message, $status = 500) {
            $message = !is_array($message) ? [$message] : $message;
            $response = array_merge(['message' => 'Server Error','type' => 'error'],$message);

            return Response::json([
                'error'  => $response
            ], $status);
        });

    }


}