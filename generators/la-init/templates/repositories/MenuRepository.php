<?php
/** 
 * @license
 */
namespace App\Repositories\Setup;

use App\Models\Setup\Menu;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class MenuRepository
 * @package App\Repositories\Setup
 * @version September 27, 2021, 9:40 am EAT
 */
class MenuRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'label',
        'router_link',
        'parent_id',
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable(): array
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model(): string
    {
        return Menu::class;
    }

    /**
     * this function return menus with children by permission ids
     */
    public function menuByPermissions($permissionIds)
    {
        /** Add dummy nun existing id to avoid query error when no permission ids exist */
        array_push($permissionIds, 0);

        /** convert permission array to comma seperated string */
        $permissionIdsString = implode(',', $permissionIds);

        // return  $permissionIdsString;
        /** Get all menu ids with their parent id */
        $menuIdsWithParentIds = DB::select("WITH RECURSIVE parent as (SELECT m.id, m.parent_id FROM menus m join menu_permissions mp on mp.menu_id=m.id  WHERE mp.permission_id IN ({$permissionIdsString}) UNION ALL SELECT c.id, c.parent_id FROM menus c JOIN parent p ON p.parent_id = c.id ) select DISTINCT(id) FROM parent ORDER BY id");

        // return $menuIdsWithParentIds;
        /** Convert result into collection and extract id  */
        $menuIdsWithParentIds = collect($menuIdsWithParentIds)->pluck('id')->toArray();

        /** Return parent menu with recursive items filtered by ids which has user permissions (see scopeByPermission in Menu model) */
        return Menu::byPermission($menuIdsWithParentIds)->whereNull('parent_id')->get()->toArray();
    }

}
