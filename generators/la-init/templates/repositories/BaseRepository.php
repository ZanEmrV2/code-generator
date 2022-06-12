<?php

namespace App\Repositories;

use Illuminate\Container\Container as Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


abstract class BaseRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     *
     * @throws \Exception
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->makeModel();
    }

    /**
     * Get searchable fields array
     *
     * @return array
     */
    abstract public function getFieldsSearchable();

    /**
     * Configure the Model
     *
     * @return string
     */
    abstract public function model();

    /**
     * Make Model instance
     *
     * @throws \Exception
     *
     * @return Model
     */
    public function makeModel()
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new \Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Paginate records for scaffold.
     *
     * @param int $perPage
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage, $search = [], $columns = ['*'], $with = [],  $orderBy = [])
    {
        $query = $this->allQuery($search, $orderBy);
       
        $query->with($with);
       
        return $query->paginate($perPage, $this->validColumns($columns));
    }

     /**
     * Retrieve all records with given filter criteria
     *
     * @param array $search
     * @param int|null $skip
     * @param int|null $limit
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function all( $search = [], $columns = ['*'], $with = [], $orderBy = [],  $skip = null, $limit = null)
    {        
        $query = $this->allQuery($search, $orderBy);

        $query->with($with);

        if (!is_null($skip)) {
            $query->skip($skip);
        }

        if (!is_null($limit)) {
            $query->limit($limit);
        }

        return $query->get($this->validColumns($columns));
    }


    /**
     * Build a query for retrieving all records.
     *
     * @param array $search
     * @param int|null $skip
     * @param int|null $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function allQuery($search = [], $orderBy = [])
    {
        $query = $this->model->newQuery();

        if (count($search)) {
            foreach ($search as $key => $value) {
                if (in_array($key, $this->getFieldsSearchable())) {
                    if ($value == 'null' || $value == null) {
                        $query->whereNull($key);
                    } else {
                        if ($this->model->getCasts()[$key] == 'string') {
                            $query->where($key, 'ilike', '%' . $value . '%');
                        } else {
                            $query->where($key, '=', $value);
                        }
                    }
                }
            }
        }

         if (count($orderBy)) {
            foreach ($orderBy as $sort) {
                $sortArr = explode(':', $sort);
                $column = $sortArr[0];
                $dir = sizeof($sortArr) == 2 ? $sortArr[1] : 'desc';
                $query->orderBy($column, $dir);
            }
        }

        return $query;
    }

     /**
     * Filter modal columns
     */
    public function validColumns($columns = [])
    {
        $modelColumns = array_keys($this->model->getCasts());
        $cleanColumns = array_intersect($modelColumns, $columns);
        return sizeof($cleanColumns) ? $cleanColumns : ['*'];
    }

   

    /**
     * Create model record
     *
     * @param array $input
     *
     * @return Model
     */
    public function create($input)
    {
        $model = $this->model->newInstance($input);

        $model->save();

        return $model;
    }

     /**
     * Update model record for given id
     *
     * @param array $input
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    public function update($input, $id)
    {
        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        $model->fill($input);

        $model->save();

        return $model;
    }

    /**
     * Find model record for given id
     *
     * @param int $id
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function find($id, $columns = ['*'], $with = [])
    {
        $query = $this->model->newQuery();

        $query->with($with);

        return $query->find($id, $columns);
    }

    /**
     * @param int $id
     *
     * @throws \Exception
     *
     * @return bool|mixed|null
     */
    public function delete($id)
    {
        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        return $model->delete();
    }


    /**
     *  Seach, skip, limit, select columns of a collection;
     */
    public function processCollection($collection, $search = [], $skip = null, $limit = null, $columns = [])
    {

        if (count($search)) {
            foreach ($search as $searchKey => $searchValue) {
                if (in_array($searchKey, $this->getFieldsSearchable())) {
                    if ($this->model->getCasts()[$searchKey] == 'string') {
                        $collection = $collection->filter(function ($value, $key) use ($searchKey, $searchValue) {
                            return Str::contains(Str::lower($value[$searchKey]), Str::lower($searchValue));
                        })->values();
                    } else {
                        $collection = $collection->filter(function ($value, $key) use ($searchKey, $searchValue) {
                            return $value[$searchKey] == $searchValue;
                        })->values();
                    }
                }
            }
        }


        if (!is_null($skip)) {
            $collection =  $collection->skip($skip);
        }

        if (!is_null($limit)) {
            $collection = $collection->limit($limit);
        }

        $columns = $this->cleanColumns($columns);
        if (sizeof($columns) > 0 && !in_array('*', $columns)) {
            $collection = $collection->map(function ($c) use ($columns) {
                return collect($c->toArray())
                    ->only($columns)
                    ->all();
            });
        }
        return $collection;
    }
}
