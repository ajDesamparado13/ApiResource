<?php

namespace Freedom\ApiResource\Contracts;

interface ApiResourceInterface{

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model();


    /**
     * Alias of All method
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function get($columns = ['*']);

    /**
     * Retrieve all data of resource, paginated
     *
     * @param null|int $limit
     * @param array $columns
     * @param string $method
     *
     * @return mixed
     */
    public function paginate($limit = null, $columns = ['*'], $method = "paginate");


    /**
     * Query Scope
     *
     * @param \Closure $scope
     *
     * @return $this
     */
    public function scopeQuery(\Closure $scope);


    /**
     * Save a new entity in resource
     *
     * @throws ValidatorException
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function create(array $attributes);

    /**
     * Update a entity in repository by id
     *
     * @throws ValidatorException
     *
     * @param array $attributes
     * @param       $id
     *
     * @return mixed
     */
    public function update(array $attributes, $id);
    
    /**
     * Delete a entity in repository by id
     *
     * @param $id
     *
     * @return int
     */
    public function delete($id);

    /**
     * Find data by id
     *
     * @param       $id
     * @param array $columns
     *
     * @return mixed
     */
    public function find($id, $columns = ['*']);

    /**
     * Retrieve first data of repository
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function first($columns = ['*']);
    
    /**
     * Push Criteria for filter the query
     *
     * @param $criteria
     *
     * @return $this
     */
    public function pushCriteria($criteria);

    /**
     * Pop Criteria
     *
     * @param $criteria
     *
     * @return $this
     */
    public function popCriteria($criteria);

    /**
     * Get Collection of Criteria
     *
     * @return Collection
     */
    public function getCriteria();

    /**
     * Skip Criteria
     *
     * @param bool $status
     *
     * @return $this
     */
    public function skipCriteria($status = true);

    /**
     * Reset all Criterias
     *
     * @return $this
     */
    public function resetCriteria();

    /**
     * Create New Instance of Model
     * 
     * @param array $attributes
     *
     * @return Model
     */
    public function newInstance(array $attributes);

    /**
     * Validate attributes
     * 
     * @param array $attributes
     * @param array $type
     *
     * @throws ValidatorException
     * 
     * @return bool
     */
    public function validate(array $attributes,string $type);

    /**
     * Filter attributes into model fillable attributes
     * 
     * @param array $attributes
     *
     * @return $array
     */
    public function fillableAttributes(array $attributes) : array;

}
