<?php

namespace Freedom\ApiResource\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Freedom\ApiResourceContracts\ApiResourceInterface;
use Freedom\ApiResourceContracts\JsonResourceInterface;
use Freedom\ApiResourceContracts\MetaProviderInterface;
use Freedom\ApiResourceExceptions\ApiControllerException;
use Illuminate\Database\Eloquent\Model;
use \Prettus\Validator\Contracts\ValidatorInterface;
use Illuminate\Http\Request;
use App\Subsytem\Api\VO\ApiMessage;

abstract class ApiController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * The prettus-validator instance.
     *
     * @var \Prettus\Validator\LaravelValidator;
     */
    protected $validator = null;

    protected $metaProvider;

    /**
     * The resource instance.
     *
     * @var Freedom\ApiResourceContracts\ApiInterface;
     */
    protected $resource;

    /**
     * The Transformer instance
     *
     * @var \App\Http\Resources\Contracts\JsonResourceInterface;
     */
    protected $transformer;

    /**
     * The Transformer instance
     *
     * @var \Freedom\ApiResourceVO\LangObject;
     */
    protected $messageObject;

    /**
     * The Request
     *
     * @var Illuminate\Http\Request
     */
    protected $request;

    abstract public function resource();

    public function makeResource(){
        $resource = app()->make($this->resource());

        if (!$resource instanceof ApiResourceInterface) {
            throw new ApiControllerException("Class {$this->resource()} must be an instance of Freedom\ApiResourceContracts\ApiResourceInterface");
        }

        return $this->resource = $resource;
    }

    abstract public function transformer();

    public function makeTransformer(){
        $_class = $this->transformer();
        $transformer = app()->make($_class);

        if (!$transformer instanceof JsonResourceInterface) {
            throw new ApiControllerException("Class {$_class} must be an instance of Freedom\ApiResourceContracts\JsonResourceInterface");
        }

        return $this->transformer = $transformer;
    }

    public function metaProvider(){
        return null;
    }

    public function makeMetaProvider(){
        $_class = $this->metaProvider();

        if($_class === null){
            return;
        }

        $metaProvider = app()->make($_class);

        if(!( $metaProvider instanceof  \Freedom\ApiResourceContracts\MetaProviderInterface )){
            throw new ApiControllerException("Class {$_class} must be an instance of Freedom\ApiResourceContracts\MetaProviderInterface ");
        }

        return $this->metaProvider =$metaProvider;
    }

    public function __construct(Request $request)
    {
        $this->makeResource();
        $this->makeTransformer();
        $this->makeMetaProvider();
        $this->request = $request;
        $this->boot();
    }

    public function boot(){
        $this->setLang();
    }

    public function setLang($controller="default",$file="api")
    {
        $this->messageObject = new ApiMessage($file,$controller);
    }

    protected function getMessage(string $method){
        return $this->messageObject->getMessage($method);
    }

    /*
    * API ENDPOINT METHOD FOR RETRIEVING DATA
    * 
    * @return Illuminate\Http\Resources\Json\JsonResource
    * @return \Illuminate\Http\Resources\Json\PaginatedResourceResponse
    * @return Illuminate\Http\Resources\Json\ResourceCollection
    */
    public function index()
    {
        $is_paginated = $this->request->query(\Config::get('resource.request.type'),'paginated') != 'collection';
        $result = $is_paginated ? $this->_indexPaginate() : $this->_indexCollection() ;

        $meta = [];
        if($this->metaProvider){
            $meta = array_merge($meta,$this->metaProvider->make($this->resource,$result));
        }

        return response()->resource($result,$this->transformer,[ 'message' => $this->getMessage('index'), 'meta' => $meta ]);
    }

    /*
    * GET RESOURCE DATA AS COLLECTION OBJECT
    *
    * @return \Illuminate\Database\Eloquent\Collection
    */
    protected function _indexCollection()
    {
        $transformer = $this->transformer;
        $limit = (int)$this->request->query('limit',100);
        if($limit && $limit > 0){
            $this->resource->scopeQuery(function($query) use($limit){
                return $query->limit($limit);
            });
        }

        return $this->resource->get($transformer->columns);
    }

    /*
    * GET RESOURCE DATA AS PAGINATED OBJECT
    *
    * @return \Illuminate\Pagination\LengthAwarePaginator
    */
    protected function _indexPaginate()
    {
        $transformer = $this->transformer;
        $per_page = $this->request->input('per_page',null);
        return $this->resource->paginate(is_int($per_page) ? $per_page : null,$transformer->columns);
    }

    /*
     * API ENDPOINT METHOD FOR ADDING A RECORD
    * @return \Illuminate\Http\Resources\Json\JsonResource
    */
    public function store()
    {
        $result = $this->_store();
        if($this->hasFileUpload()){
            $this->_upload($result);
        }
        return response()->resource($result,$this->transformer,[ 'message' => $this->getMessage('store')]);
    }

    /*
    * Insert data in Resource
    *
    * @return \Illuminate\Database\Eloquent\Model
    */
    protected function _store()
    {
        $inputs = $this->request->all();
        if ($this->validator) {
            $this->validator->with($inputs)->passesOrFail(ValidatorInterface::RULE_CREATE);
        }
        return $this->resource->create($inputs);
    }

    /*
     * API ENDPOINT METHOD FOR RETRIEVING RECORD
     *
     * @param $id
     * @param $primaryKey
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function show($id)
    {
        $key = $this->request->input('primaryKey','id');
        return response()->resource($this->_show($id,$key),$this->transformer, [ 'message' => $this->getMessage('show')]);
    }

    /*
     * Retrieves a specific item in resource
     *
     * @param integer $id
     * @param string $key
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function _show($id,$key="id")
    {
        if ($key != 'id') {
            $this->resource->scopeQuery(function ($query) use ($key, $id) {
                return $query->where($key, $id);
            });
            return $this->resource->first();
        }

        return $this->resource->find($id);;
    }

    /*
     * API ENDPOINT FOR UPDATING RECORD
     *
     * @param $id Model PK Value
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function update($id)
    {
        $result = $this->_update($id);
        if($this->hasFileUpload()){
            $this->_upload($result);
        }
        return response()->resource($result,$this->transformer,[ 'message' => $this->getMessage('update')]);
    }

    /*
     * Update specific item in resource
     *
     * @param $id Model PK Value
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function _update($id)
    {
        $inputs = $this->request->all();
        if ($this->validator) {
            $this->validator->with($inputs)->passesOrFail(ValidatorInterface::RULE_UPDATE);
        }
        return $this->resource->update($inputs,$id);
    }

    /*
     * API ENDPOINT METHOD FOR DELETING DATA
     *
     * @param  int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        return response()->json(['data' => $this->_destroy($id), 'message' => $this->getMessage('destroy')], 200);
    }

    /*
    * DELETE DATA IN resource
    *
    * @param int $id
    * @return number of deleted record Integer
    */
    protected function _destroy($id)
    {
        $ids = explode(',', $id);
        foreach ($ids as $id) {
            $this->resource->delete($id);
        }
        return count($ids);
    }

    /*
    * API ENDPOINT METHOD FOR FILE UPLOAD
    *
    */
    public function upload()
    {
        if(!$this->hasFileUpload()){
            return response()->json(['data' => 0, 'message' => 'No file uploaded'],201);
        }
        $result = $this->_upload($this->resource->find($this->request->input('id')));
        return response()->json(['data' => $result, 'message' => $this->getMessage('upload')],200);
    }

    /*
    * PERFORMS A FILE UPLOADS
    * 
    * @return \Illuminate\Database\Eloquent\Model
    */
    protected function _upload(Model $model)
    {
        $request = $this->request;
        $with = [];
        $keys = array_filter(explode(';',$request->input('fileKey','file')));
        foreach($keys as $key){
            if($request->hasFile($key)){
                $this->resource->upload($request->file($key),$model,$key);
                $with[] = $key;
            }
        };
        return count($with) > 0 ? $this->resource->with($with)->find($request->input('id')) : $model;
    }

    /*
    * Set the Controller and IoC binding for ResourceInterface
    *
    * @return void
    */
    protected function setTransformer(JsonResourceInterface $transformer)
    {
        app()->bind(JsonResourceInterface::class,$transformer);
        $this->transformer = $transformer;
    }

    /*
    * Set Controller's Validator for store and update methods
    *
    * @return void
    */
    protected function setValidator($validator){
        $this->validator = is_string($validator) ? app()->make($validator) : $validator;
        if (!$this->validator instanceof ValidatorInterface) {
            throw new  ApiControllerException("Class {$validator} must be an instance of Prettus\\Validator\\Contracts\\ValidatorInterface");
        }
    }

    /*
    * CHECK IF CONTROLLER resource HAS UPLOAD METHOD
    *
    * @return BOOLEAN
    */
    protected function hasFileUpload()
    {
        return method_exists($this->resource,'upload');
    }

}
