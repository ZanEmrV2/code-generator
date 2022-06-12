<?php
/**
 * @license
 */
namespace App\Http\Controllers\Api\Setup;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\Api\Setup\CreateUserApiRequest;
use App\Http\Requests\Api\Setup\UpdateUserApiRequest;
use App\Models\Setup\User;
use App\Repositories\Setup\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiBaseController;
use Response;

/**
 * Class UserController
 * @package App\Http\Controllers\Api\Setup
 */
class UserApiController extends ApiBaseController {
    /** @var  UserRepository */
    private $userRepository;

    public function __construct(UserRepository $userRepo) {
        $this->userRepository = $userRepo;
    }

    /**
     * @param Request $request
     * @return Response
     *
     * @SWG\Get(
     *      path="/users",
     *      summary="Get a listing of the Users.",
     *      tags={"User"},
     *      description="Get all Users",
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
     *                  @SWG\Items(ref="#/definitions/User")
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

        $search = $request->except(['per_page', 'page', 'columns', 'skip', 'limit', 'sort','with']);
        $columns = $request->get('columns') ? explode(',', $request->get('columns')) : ['*'];
        $with = $request->get('with') ? explode(',', $request->get('with')) : [];
        $orderBy = $request->get('sort') ? explode(',', $request->get('sort')) : ['id'];

        if (!is_null($request->page) || !is_null($request->per_page)) {
            $users = $this->userRepository->paginate(
                $request->get('per_page'),
                $search,
                $columns,
                $with,
                $orderBy,
            );
        } else {
            $users = $this->userRepository->all(
                $search,
                $columns,
                $with,
                $orderBy ,
                $request->get('skip'),
                $request->get('limit'),
            );
        }
        return $this->sendResponse($users->toArray(), 'Users retrieved successfully');
    }

    /**
     * @param CreateUserAPIRequest $request
     * @return JsonResponse
     *
     * @SWG\Post(
     *      path="/users",
     *      summary="Store a newly created User in storage",
     *      tags={"User"},
     *      description="Store User",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="User that should be stored",
     *          required=false,
     *          @SWG\Schema(ref="#/definitions/User")
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
     *                  ref="#/definitions/User"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function store(CreateUserApiRequest $request) {
       
        $input = $request->all();

        try {
            DB::beginTransaction();
                $user = $this->userRepository->create($input);
            DB::commit();
            return $this->sendResponse($user->toArray(), 'User saved successfully');
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th);
            return $this->sendError('Error Creating user',400);
        }
        
    }

    /**
     * @param int $id
     * @return Response
     *
     * @SWG\Get(
     *      path="/users/{id}",
     *      summary="Display the specified User",
     *      tags={"User"},
     *      description="Get User",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of User",
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
     *                  ref="#/definitions/User"
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
        /** @var User $user */
        $user = $this->userRepository->find($id);

        if (empty($user)) {
            return $this->sendError('User not found');
        }

        return $this->sendResponse($user->toArray(), 'User retrieved successfully');
    }

    /**
     * @param int $id
     * @param UpdateUserAPIRequest $request
     * @return Response
     *
     * @SWG\Put(
     *      path="/users/{id}",
     *      summary="Update the specified User in storage",
     *      tags={"User"},
     *      description="Update User",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of User",
     *          type="integer",
     *          required=true,
     *          in="path"
     *      ),
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="User that should be updated",
     *          required=false,
     *          @SWG\Schema(ref="#/definitions/User")
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
     *                  ref="#/definitions/User"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function update($id, UpdateUserApiRequest $request) {

       $input = $request->all();
       try {
            DB::beginTransaction();
                $user = $this->userRepository->update($input, $id);
            DB::commit();
            return $this->sendResponse($user->toArray(), 'User updated successfully');
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
     *      path="/users/{id}",
     *      summary="Remove the specified User from storage",
     *      tags={"User"},
     *      description="Delete User",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of User",
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
            $this->userRepository->delete($id);
            DB::commit();
            return $this->sendSuccess('User deleted successfully');
        } catch (\Throwable $th) {
            DB::rollback();
            Log::error($th);
            return $this->sendError('Error deleting user', 400);
        }
       
    }

}
