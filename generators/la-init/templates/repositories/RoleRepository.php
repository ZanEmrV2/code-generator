<?php
/**
 * @license
 */

namespace App\Repositories\Setup;

use App\Models\Setup\Role;
use App\Repositories\BaseRepository;

/**
 * Class RoleRepository
 * @package App\Repositories\Setup
 */
class RoleRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'active',
        'name',
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable(): array {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model() {
        return Role::class;
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

        $this->updatePermissions($model->id, $input['permissions'] ?? []);

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

        $this->updatePermissions($model->id, $input['permissions'] ?? []);

        return $model;
    }

    private function updatePermissions($role_id, $permissions)
    {
        $ids = [];
        foreach ($permissions as $perm) {
            DB::table('role_permissions')->updateOrInsert([
                'role_id' => $role_id,
                'permisiion_id' => $perm['id']
            ]);
            array_push($ids, $perm['id']);
        }
        DB::table('role_permissions')->where('role_id', $role_id)->whereNotIn('permission_id', $ids)->delete();
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

        DB::table('role_permissions')->where('role_id', $id)->delete();

        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        return $model->delete();
    }

}
