<?php
/**
 * @licence
 */
namespace App\Models\Setup;

use App\Models\AuditingBaseModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @SWG\Definition(
 *      definition="Role",
 *      required={"name"},
 *      @SWG\Property(
 *          property="id",
 *          description="id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="name",
 *          description="name",
 *          type="string"
 *      ),
 *       @SWG\Property(
 *          property="active",
 *          description="active",
 *          type="boolean"
 *      ),
 *      @SWG\Property(
 *          property="created_at",
 *          description="created_at",
 *          type="string",
 *          format="date-time"
 *      ),
 *      @SWG\Property(
 *          property="updated_at",
 *          description="updated_at",
 *          type="string",
 *          format="date-time"
 *      ),
 *      @SWG\Property(
 *          property="created_by",
 *          description="created_by",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="updated_by",
 *          description="updated_by",
 *          type="string"
 *      )
 * )
 */
class Role extends AuditingBaseModel {

    use HasFactory;

    public $table = 'roles';

    public $fillable = [
        'name',
        'active',
        'admin_hierarchy_position',
        'section_position',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'admin_hierarchy_position' => 'integer',
        'section_position' => 'integer',
        'name' => 'string',
        'active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|unique:roles',
        'section_position' => 'required',
    ];

    /**
     * @return BelongsToMany
     **/
    public function permissions(): BelongsToMany {
        return $this->belongsToMany(Permission::class, 'role_permissions')->select(['permission_id', 'display_name', 'name']);
    }

    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class, 'user_roles');
    }

}
