<?php
/**
* @license
*/

namespace App\Repositories\Setup;

use App\Models\Setup\Group;
use App\Repositories\BaseRepository;

/**
 * Class GroupRepository
 * @package App\Repositories\Setup
*/

class GroupRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'name'
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return Group::class;
    }
}
