<?php
/** 
 * @license
 */

namespace App\Repositories\Setup;

use App\Models\Setup\Permission;
use App\Models\Setup\Right;
use App\Repositories\BaseRepository;

/**
 * Class RightRepository
 * @package App\Repositories\Setup
 * @version August 15, 2021, 11:59 am UTC
*/

class PermissionRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'display_name',
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
        return Permission::class;
    }
}
