<?php

namespace Freedom\ApiResource\Traits;
use Freedom\ApiResource\Exceptions\ApiControllerException;
use Prettus\Validator\Contracts\ValidatorInterface;

trait hasValidator {

    /**
     * The prettus-validator instance.
     *
     * @var \Prettus\Validator\LaravelValidator;
     */
    protected $validator = null;

    public function makeValidator(){
        $validator = $this->validator();

        if(empty($validator)){
            return;
        }

        if(is_string($validator)){
            $validator = app()->make($validator);
        }

        if (!( $validator instanceof ValidatorInterface) ) {
            throw new ApiControllerException("Class ". get_class($validator) . " must be an instance of ". ValidatorInterface::class);
        }

        return $this->validator = $validator;
    }

    protected function hasValidator() : bool
    {
        return !empty($this->validator) && $this->validator instanceof ValidatorInterface;
    }

    protected function setValidator($validator){
        return $this->validator = $this->makeValidator($validator);;
    }

    protected function validate($inputs,$rule)
    {
        $this->validator->with($inputs)->passesOrFail($rule);
    }

    public abstract function validator();
}

