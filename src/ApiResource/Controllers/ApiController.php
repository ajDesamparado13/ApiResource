<?php

namespace Freedom\ApiResource\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Freedom\ApiResource\Contracts\JsonResourceInterface;
use Freedom\ApiResource\Exceptions\ApiControllerException;
use Freedom\ApiResource\VO\ApiMessage;
use Freedom\Sanitizer\Traits\WithSanitizer;
use Prettus\Validator\Contracts\ValidatorInterface;

abstract class ApiController extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
    use WithSanitizer;
    use \Freedom\ApiResource\Traits\HasResource;
    use \Freedom\ApiResource\Traits\HasTransformer;
    use \Freedom\ApiResource\Traits\HasMetaProvider;

    /**
     * The prettus-validator instance.
     *
     * @var \Prettus\Validator\LaravelValidator;
     */
    protected $validator = null;


    /**
     * The Transformer instance
     *
     * @var \Freedom\ApiResource\VO\LangObject;
     */
    protected $messageObject;

    /**
     * The Request
     *
     * @var Illuminate\Http\Request
     */
    protected $request;

    abstract public function resource();

    abstract public function transformer();

    public function metaProvider(){
        return null;
    }

    public function sanitizer(){
        return null;
    }

    public function __construct(Request $request)
    {
        $this->makeResource();
        $this->makeTransformer();
        $this->makeMetaProvider();
        $this->makeSanitizer();
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
        $is_paginated = $this->request->query(\Config::get('resource.type','resource-type'),'paginated') != 'collection';
        $result = $is_paginated ? $this->_indexPaginate() : $this->_indexCollection() ;

        $meta = $this->getMeta($this->resource,$result);

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
        return $this->resource->paginate(is_numeric($per_page) ? (int)$per_page : null,$transformer->columns);
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
        if($this->hasSanitizer()){
            $inputs = $this->sanitize($inputs);
        }
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
        if($this->hasSanitizer()){
            $inputs = $this->sanitize($inputs);
        }
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
        return response()->resource($result,$this->transformer,[
            'message' => $this->getMessage('upload'),
        ]);
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

        $file_keys = $request->input('fileKey','file');
        $keys = array_filter(is_array($file_keys) ? $file_keys : explode(',',$file_keys),function($key) use($request){
            return !empty($key) && $request->hasFile($key);
        });

        foreach($keys as $key){
            $this->resource->upload($request->file($key),$model,$key);
            $with[] = $key;
        };
        return count($with) > 0 ? $this->resource->with($with)->find($request->input('id')) : $model;
    }


    /*
    * Set Controller's Validator for store and update methods
    *
    * @return void
    */
    protected function setValidator($validator){
        $validator = is_string($validator) ? app()->make($validator) : $validator;
        if (!$this->validator instanceof ValidatorInterface) {
            throw new  ApiControllerException("Class {$validator} must be an instance of" . ValidatorInterface::class);
        }
        return $this->validator = $validator;
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
