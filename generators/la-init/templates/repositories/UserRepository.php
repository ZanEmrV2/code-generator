<?php


namespace App\Repositories\Setup;

use App\Models\Setup\User;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class UserRepository extends BaseRepository {
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'first_name',
        'last_name',
        'user_name',
        'email',
        'title',
        'mobile_number',
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable() {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model() {
        return User::class;
    }

    public function create($input) {

        $model = $this->model->newInstance($input);

        $model->password_hash = Hash::make('Secret1234');

        $model->save();

        $this->updateUserRole($model->id, $input['roles'] ?? []);

        $this->updateUserGroup($model->id, $input['groups'] ?? []);
    
        return $model;
    }

    public function update($input, $id) {
       
        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        $model->fill($input);

        $model->save();

        if (isset($input['roles'])) {
            $this->updateUserRole($model->id, $input['roles']);
        }

        return $model;
    }

    private function updateUserRole($userId, $roles) {
        $ids = [];
        foreach ($roles as $role) {
            DB::table('user_roles')->updateOrInsert([
                'user_id' => $userId,
                'role_id' => $role['id']
            ]);
            array_push($ids, $role['id']);
        }
        DB::table('user_roles')->where('user_id', $userId)->whereNotIn('role_id', $ids)->delete();
    }

    private function updateUserGroup($userId, $groups) {
        $ids = [];
        foreach ($groups as $group) {
            DB::table('user_groups')->updateOrInsert([
                'user_id' => $userId,
                'group_id' => $group['id']
            ]);
            array_push($ids, $group['id']);
        }
        DB::table('user_groups')->where('user_id', $userId)->whereNotIn('group_id', $ids)->delete();
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

        DB::table('user_roles')->where('user_id', $id)->delete();
        
        DB::table('user_groups')->where('user_id', $id)->delete();

        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        return $model->delete();
    }
}
