<?php

namespace Freedom\ApiResource\Traits;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;

trait HasSelfUpdate {

    protected $self_field = 'id';

    /*
    * GET AUTHENTICATED USER'S LOCAL/FOREIGN KEY COLUMN
    */
    protected function getSelfField(){
        return $this->self_field ?? 'id';
    }

    /*
    * GET AUTHENTICATED USER'S LOCAL/FOREIGN KEY VALUE
    */
    protected function getSelfKey()
    {
        return Arr::get(\Auth::user(),$this->getSelfField());
    }

    /*
    * GET AUTHENTICATED USER'S ITEM
    */
    protected function _selfShow()
    {
        $self_key = $this->getSelfKey();
        if(!$self_key){
            throw new AuthorizationException();
        }
        return $this->_show($self_key,$this->getSelfField());
    }

    /*
    * Update Authenticated User Item
    */
    public function selfUpdate(){
        $self_key = $this->getSelfKey();
        $input_id = $this->request->input('id');
        if(!$self_key || $self_key != $input_id){
            throw new AuthorizationException();
        }
        return $this->update($input_id);
    }

    /**
     * Show Authenticated User Item
     */
    public function selfShow()
    {
        $item = $this->_selfShow();
        return response()->resource($item,$this->transformer, [ 'message' => $this->getMessage('show')]);
    }


    /**
     * Upload Authenticated User Item
     */
    public function selfUpload()
    {
        if(!$this->hasFileUpload()){
            return response()->json(['data' => 0, 'message' => 'No file uploaded'],201);
        }

        $result = $this->_upload($this->_selfShow());
        return response()->resource($result,$this->transformer,[
            'message' => $this->getMessage('upload'),
        ]);

    }

}
