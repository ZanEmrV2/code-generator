<?php
/**
 *@license
 */

namespace App\Http\Controllers\Api\Setup;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\Api\Setup\CreateRoleApiRequest;
use App\Http\Requests\Api\Setup\UpdateRoleApiRequest;
use App\Models\Setup\Role;
use App\Repositories\Setup\RoleRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use Response;

/**
 * Class RoleController
 * @package App\Http\Controllers\API\Setup
 */
class RoleApiController extends AppBaseController {
    /** @var  RoleRepository */
    private $roleRepository;

    public function __construct(RoleRepository $roleRepo) {
        $this->roleRepository = $roleRepo;
    }

    /**
     * @param Request $request
     * @return Response
     *
     * @SWG\Get(
     *      path="/roles",
     *      summary="Get a listing of the Roles.",
     *      tags={"Role"},
     *      description="Get all Roles",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="page",
     *          in="query",
     *          type="integer",
     *          format="int32",
     *          description="Page number; If not specified all item will be returned"
     *       ),
     *      @SWG\Parameter(
     *          name="per_page",
     *          in="query",
     *          type="integer",
     *          format="int32",
     *          description="Number Items per page"
     *       ),
     *      @SWG\Parameter(
     *          name="columns",
     *          in="query",
     *          type="string",
     *          description="Comma separated columns names e.g id,name; If not specified all column will be returned"
     *       ),
     *      @SWG\Response(
     *          response=200,
     *          description="successful operation",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  type="array",
     *                  @SWG\Items(ref="#/definitions/Role")
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function index(Request $request) {
        $columns = $request->get('columns') ? explode(',', $request->get('columns')) : ['*'];
        $search = $request->except(['per_page', 'page', 'columns', 'skip', 'limit', 'sort']);
        $orderBy = $request->get('sort') ? explode(',', $request->get('sort')) : ['id'];
        $with = $request->get('with') ? explode(',', $request->get('with')) : [];

        if (!is_null($request->page) || !is_null($request->per_page)) {
            $roles = $this->roleRepository->paginate(
                $request->get('per_page'),
                $search,
                $columns,
                $with,
                $orderBy
            );
        } else {
            $roles = $this->roleRepository->all(
                $search,
                $columns,
                $with,
                $orderBy,
                $request->get('skip'),
                $request->get('limit'),
            );
        }
        return $this->sendResponse($roles->toArray(), 'Roles retrieved successfully');
    }

    /**
     * @param CreateRoleAPIRequest $request
     * @return Response
     *
     * @SWG\Post(
     *      path="/roles",
     *      summary="Store a newly created Role in storage",
     *      tags={"Role"},
     *      description="Store Role",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Role that should be stored",
     *          required=false,
     *          @SWG\Schema(ref="#/definitions/Role")
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="successful operation",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  ref="#/definitions/Role"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function store(CreateRoleApiRequest $request) {

        try {
            DB::beginTransaction();
                $input = $request->all();
                $role = $this->roleRepository->create($input);
            DB::commit();
            return $this->sendResponse($role->toArray(), 'Role saved successfully');
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th);
            return $this->sendError('Error Creating role',400);
        }
        
    }

    /**
     * @param int $id
     * @return Response
     *
     * @SWG\Get(
     *      path="/roles/{id}",
     *      summary="Display the specified Role",
     *      tags={"Role"},
     *      description="Get Role",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Role",
     *          type="integer",
     *          required=true,
     *          in="path"
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="successful operation",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  ref="#/definitions/Role"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function show($id) {
        /** @var Role $role */
        $role = $this->roleRepository->find($id);

        if (empty($role)) {
            return $this->sendError('Role not found');
        }

        return $this->sendResponse($role->toArray(), 'Role retrieved successfully');
    }

    /**
     * @param int $id
     * @param UpdateRoleAPIRequest $request
     * @return Response
     *
     * @SWG\Put(
     *      path="/roles/{id}",
     *      summary="Update the specified Role in storage",
     *      tags={"Role"},
     *      description="Update Role",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Role",
     *          type="integer",
     *          required=true,
     *          in="path"
     *      ),
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Role that should be updated",
     *          required=false,
     *          @SWG\Schema(ref="#/definitions/Role")
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="successful operation",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  ref="#/definitions/Role"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function update($id, UpdateRoleApiRequest $request) {
        $input = $request->all();
        try {
            DB::beginTransaction();
                $role = $this->roleRepository->update($input, $id);
            DB::commit();
            return $this->sendResponse($role->toArray(), 'Role updated successfully');
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th);
            return $this->sendError('Error updating user', 400);
        }
    }

    /**
     * @param int $id
     * @return Response
     *
     * @SWG\Delete(
     *      path="/roles/{id}",
     *      summary="Remove the specified Role from storage",
     *      tags={"Role"},
     *      description="Delete Role",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Role",
     *          type="integer",
     *          required=true,
     *          in="path"
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="successful operation",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @SWG\Property(
     *                  property="data",
     *                  type="string"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function destroy($id) {
        try {
            DB::beginTransaction();
            $this->roleRepository->delete($id);
            DB::commit();
            return $this->sendSuccess('Role deleted successfully');
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th);
            return $this->sendError('Error deleting role', 400);
        }
    }

}
