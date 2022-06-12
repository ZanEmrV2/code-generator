<?php
/**
 * @licence
 */
namespace App\Models\Setup;

use App\Models\ModelEventObserver;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @SWG\Definition(
 *      definition="User",
 *      required={"first_name", "last_name", "username", "email", "cheque_number", "password_hash", "login_attempts","is_password_expired"},
 *      @SWG\Property(
 *          property="id",
 *          description="id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="first_name",
 *          description="first_name",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="last_name",
 *          description="last_name",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="username",
 *          description="username",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="email",
 *          description="email",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="cheque_number",
 *          description="cheque_number",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="password_hash",
 *          description="password_hash",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="activated",
 *          description="activated",
 *          type="boolean"
 *      ),
 *      @SWG\Property(
 *          property="activation_key",
 *          description="activation_key",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="title",
 *          description="title",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="mobile_number",
 *          description="mobile_number",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="resert_date",
 *          description="resert_date",
 *          type="string",
 *          format="date-time"
 *      ),
 *      @SWG\Property(
 *          property="reset_key",
 *          description="reset_key",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="section_id",
 *          description="section_id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="admin_hierarchy_id",
 *          description="admin_hierarchy_id",
 *          type="integer",
 *          format="int32"
 *      ),
 * 
 *  *      @SWG\Property(
 *          property="geographical_location_id",
 *          description="geographical_location_id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="login_attempts",
 *          description="login_attempts",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="is_facility_user",
 *          description="is_facility_user",
 *          type="boolean"
 *      ),
 *      @SWG\Property(
 *          property="is_super_user",
 *          description="is_super_user",
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
class User extends Authenticatable {

    use HasApiTokens, HasFactory, Notifiable;

    public $table = 'users';

    public static function boot() {
        parent::boot();

        $class = get_called_class();
        $class::observe(new ModelEventObserver());
    }

    public $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'cheque_number',
        'title',
        'mobile_number',
        'resert_date',
        'reset_key',
        'activated',
        'active',
        'created_by',
        'updated_by'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
        'activation_key',
        'reset_key',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'first_name' => 'string',
        'last_name' => 'string',
        'username' => 'string',
        'email' => 'string',
        'cheque_number' => 'string',
        'password_hash' => 'string',
        'activated' => 'boolean',
        'activation_key' => 'string',
        'title' => 'string',
        'mobile_number' => 'string',
        'resert_date' => 'datetime',
        'reset_key' => 'string',
        'login_attempts' => 'integer',
        'created_by' => 'string',
        'updated_by' => 'string',
        'active' => 'boolean',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'section_id' => 'required',
        'geographical_location_id' => 'required',
        'admin_hierarchy_id' => 'required',
        'email' => 'required|unique:users',
        'cheque_number' => 'required|numeric|unique:users',
        'username' => 'required|unique:users',
    ];

    public static $passwordResetRules = [
        'id' => 'required',
        'password' => 'required',
        'passwordConfirmation' => 'required',
    ];

    public static $changePasswordResetRules = [
        'id' => 'required',
        'password' => 'required',
        'passwordConfirmation' => 'required',
        'oldPassword' => 'required',
    ];

    /**
     * @return BelongsToMany
     **/
    public function roles(): BelongsToMany {
        return $this->BelongsToMany(Role::class, 'user_roles');
    }

    public function groups(): BelongsToMany {
        return $this->BelongsToMany(Group::class, 'user_groups');
    }
}
