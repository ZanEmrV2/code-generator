<?php
/**
 *@license
 */

namespace App\Http\Controllers\Api\Setup;

use App\Http\Requests\Api\Setup\CreateGroupApiRequest;
use App\Http\Requests\Api\Setup\UpdateGroupApiRequest;
use App\Models\Setup\Group;
use App\Repositories\Setup\GroupRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use Response;

/**
 * Class GroupController
 * @package App\Http\Controllers\API\Setup
 */

class GroupApiController extends AppBaseController
{
    /** @var  GroupRepository */
    private $groupRepository;

    public function __construct(GroupRepository $groupRepo)
    {
        $this->groupRepository = $groupRepo;
    }

    /**
     * @param Request $request
     * @return Response
     *
     * @SWG\Get(
     *      path="/groups",
     *      summary="Get a listing of the Groups.",
     *      tags={"Group"},
     *      description="Get all Groups",
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
     *                  @SWG\Items(ref="#/definitions/Group")
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function index(Request $request)
    {
        $columns = $request->get('columns') ? explode(',', $request->get('columns')) : ['*'];
        $search =  $request->except(['per_page', 'page', 'columns', 'skip', 'limit','sort']);
        $orderBy = $request->get('sort') ? explode(',', $request->get('sort')) : ['id'];
        $with = $request->get('with') ? explode(',', $request->get('with')) : [];

        if (!is_null($request->page) || !is_null($request->per_page)) {
            $groups = $this->groupRepository->paginate(
                $request->get('per_page'),
                $search,
                $columns,
                $with,
                $orderBy
            );
        } else {
            $groups = $this->groupRepository->all(
                $search,
                $columns,
                $with,
                $request->get('skip'),
                $request->get('limit'),
            );
        }
        return $this->sendResponse($groups->toArray(), 'Groups retrieved successfully');
    }

    /**
     * @param CreateGroupAPIRequest $request
     * @return Response
     *
     * @SWG\Post(
     *      path="/groups",
     *      summary="Store a newly created Group in storage",
     *      tags={"Group"},
     *      description="Store Group",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Group that should be stored",
     *          required=false,
     *          @SWG\Schema(ref="#/definitions/Group")
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
     *                  ref="#/definitions/Group"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function store(CreateGroupApiRequest $request)
    {
        $input = $request->all();

        $group = $this->groupRepository->create($input);

        return $this->sendResponse($group->toArray(), 'Group saved successfully');
    }

    /**
     * @param int $id
     * @return Response
     *
     * @SWG\Get(
     *      path="/groups/{id}",
     *      summary="Display the specified Group",
     *      tags={"Group"},
     *      description="Get Group",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Group",
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
     *                  ref="#/definitions/Group"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function show($id)
    {
        /** @var Group $group */
        $group = $this->groupRepository->find($id);

        if (empty($group)) {
            return $this->sendError('Group not found');
        }

        return $this->sendResponse($group->toArray(), 'Group retrieved successfully');
    }

    /**
     * @param int $id
     * @param UpdateGroupAPIRequest $request
     * @return Response
     *
     * @SWG\Put(
     *      path="/groups/{id}",
     *      summary="Update the specified Group in storage",
     *      tags={"Group"},
     *      description="Update Group",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Group",
     *          type="integer",
     *          required=true,
     *          in="path"
     *      ),
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Group that should be updated",
     *          required=false,
     *          @SWG\Schema(ref="#/definitions/Group")
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
     *                  ref="#/definitions/Group"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function update($id, UpdateGroupApiRequest $request)
    {
        $input = $request->all();

        /** @var Group $group */
        $group = $this->groupRepository->find($id);

        if (empty($group)) {
            return $this->sendError('Group not found');
        }

        $group = $this->groupRepository->update($input, $id);

        return $this->sendResponse($group->toArray(), 'Group updated successfully');
    }

    /**
     * @param int $id
     * @return Response
     *
     * @SWG\Delete(
     *      path="/groups/{id}",
     *      summary="Remove the specified Group from storage",
     *      tags={"Group"},
     *      description="Delete Group",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Group",
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

    public function destroy($id)
    {

        try {
            $this->groupRepository->delete($id);
            return $this->sendSuccess('Group deleted successfully');
        } catch (\Throwable $th) {
            Log::error($th);
            return $this->sendError('Error deleting group', 400);
        }

    }
}
