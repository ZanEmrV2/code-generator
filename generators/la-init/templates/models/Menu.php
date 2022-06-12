<?php
/**
 * @licence
 */
namespace App\Models\Setup;

use App\Models\AuditingBaseModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @SWG\Definition(
 *      definition="Menu",
 *      required={"label", "router_link", "created_by"},
 *      @SWG\Property(
 *          property="id",
 *          description="id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="label",
 *          description="label",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="icon",
 *          description="icon",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="separator",
 *          description="separator",
 *          type="boolean"
 *      ),
 *      @SWG\Property(
 *          property="router_link",
 *          description="router_link",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="parent_id",
 *          description="parent_id",
 *          type="integer",
 *          format="int32"
 *      ),
 *      @SWG\Property(
 *          property="created_by",
 *          description="created_by",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="created_at",
 *          description="created_at",
 *          type="string",
 *          format="date-time"
 *      ),
 *      @SWG\Property(
 *          property="updated_by",
 *          description="updated_by",
 *          type="string"
 *      ),
 *      @SWG\Property(
 *          property="updated_at",
 *          description="updated_at",
 *          type="string",
 *          format="date-time"
 *      )
 * )
 */
class Menu extends AuditingBaseModel
{

    use HasFactory;

    public $table = 'menus';

    public $fillable = [
        'label',
        'icon',
        'separator',
        'router_link',
        'parent_id',
        'sort_order',
        'code',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'label' => 'string',
        'icon' => 'string',
        'separator' => 'boolean',
        'router_link' => 'string',
        'parent_id' => 'integer',
        'created_by' => 'string',
        'updated_by' => 'string',
        'sort_order' => 'integer',
        'code' => 'string',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'label' => 'required',
    ];


    /**
     * @return BelongsTo
     **/
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id', 'id')->select(['id', 'label', 'parent_id']);
    }

    public function items()
    {
        return $this->hasMany(Menu::class, 'parent_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id', 'id');
    }

    public function scopeByPermission($query, $ids)
    {
        $this->ids = $ids;
        return $query->with(['items' => function ($q2) use ($ids) {
            $q2->byPermission($ids);
        }])->whereIn('id', $ids)->select(['id', 'label', 'icon', 'router_link as routerLink', 'parent_id'])
            ->orderBy('sort_order', 'asc');
    }
}
