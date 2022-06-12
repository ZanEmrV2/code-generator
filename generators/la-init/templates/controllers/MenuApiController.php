<?php

/**
 *@license
 */

namespace App\Http\Controllers\API\Setup;

use App\Http\Requests\API\Setup\CreateMenuAPIRequest;
use App\Http\Requests\API\Setup\UpdateMenuAPIRequest;
use App\Models\Setup\Menu;
use App\Repositories\Setup\MenuRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\AppBaseController;
use Response;

/**
 * Class MenuController
 * @package App\Http\Controllers\API\Setup
 */

class MenuApiController extends AppBaseController
{
    /** @var  MenuRepository */
    private $menuRepository;

    public function __construct(MenuRepository $menuRepo)
    {
        $this->menuRepository = $menuRepo;
    }

    /**
     * @param Request $request
     * @return Response
     *
     * @SWG\Get(
     *      path="/menus",
     *      summary="Get a listing of the Menus.",
     *      tags={"Menu"},
     *      description="Get all Menus",
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
     *                  @SWG\Items(ref="#/definitions/Menu")
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
        $search =  $request->except(['per_page', 'page', 'columns', 'skip', 'limit', 'sort','with']);
        $orderBy = $request->get('sort') ? explode(',', $request->get('sort')) : ['id'];
        $with = $request->get('with') ? explode(',', $request->get('with')) : [];

        if (!is_null($request->page) || !is_null($request->per_page)) {
            $menus = $this->menuRepository->paginate(
                $request->get('per_page'),
                $search,
                $columns,
                $with,
                $orderBy
            );
        } else {
            $menus = $this->menuRepository->all(
                $search,
                $columns,
                $with,
                $orderBy,
                $request->get('skip'),
                $request->get('limit'),
            );
        }
        return $this->sendResponse($menus->toArray(), 'Menus retrieved successfully');
    }

    /**
     * @param CreateMenuAPIRequest $request
     * @return Response
     *
     * @SWG\Post(
     *      path="/menus",
     *      summary="Store a newly created Menu in storage",
     *      tags={"Menu"},
     *      description="Store Menu",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Menu that should be stored",
     *          required=false,
     *          @SWG\Schema(ref="#/definitions/Menu")
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
     *                  ref="#/definitions/Menu"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function store(CreateMenuAPIRequest $request)
    {
        $input = $request->all();

        $menu = $this->menuRepository->create($input);

        return $this->sendResponse($menu->toArray(), 'Menu saved successfully');
    }

    /**
     * @param int $id
     * @return Response
     *
     * @SWG\Get(
     *      path="/menus/{id}",
     *      summary="Display the specified Menu",
     *      tags={"Menu"},
     *      description="Get Menu",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Menu",
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
     *                  ref="#/definitions/Menu"
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
        /** @var Menu $menu */
        $menu = $this->menuRepository->find($id);

        if (empty($menu)) {
            return $this->sendError('Menu not found');
        }

        return $this->sendResponse($menu->toArray(), 'Menu retrieved successfully');
    }

    /**
     * @param int $id
     * @param UpdateMenuAPIRequest $request
     * @return Response
     *
     * @SWG\Put(
     *      path="/menus/{id}",
     *      summary="Update the specified Menu in storage",
     *      tags={"Menu"},
     *      description="Update Menu",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Menu",
     *          type="integer",
     *          required=true,
     *          in="path"
     *      ),
     *      @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          description="Menu that should be updated",
     *          required=false,
     *          @SWG\Schema(ref="#/definitions/Menu")
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
     *                  ref="#/definitions/Menu"
     *              ),
     *              @SWG\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */

    public function update($id, UpdateMenuAPIRequest $request)
    {
        $input = $request->all();

        /** @var Menu $menu */
        $menu = $this->menuRepository->find($id);

        if (empty($menu)) {
            return $this->sendError('Menu not found');
        }

        $menu = $this->menuRepository->update($input, $id);

        Cache::forget('menus');
        return $this->sendResponse($menu->toArray(), 'Menu updated successfully');
    }

    /**
     * @param int $id
     * @return Response
     *
     * @SWG\Delete(
     *      path="/menus/{id}",
     *      summary="Remove the specified Menu from storage",
     *      tags={"Menu"},
     *      description="Delete Menu",
     *      produces={"application/json"},
     *      @SWG\Parameter(
     *          name="id",
     *          description="id of Menu",
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
        /** @var Menu $menu */
        $menu = $this->menuRepository->find($id);

        if (empty($menu)) {
            return $this->sendError('Menu not found');
        }

        $menu->delete();

        Cache::forget('menus');
        return $this->sendSuccess('Menu deleted successfully');
    }
    public function currentUser(): JsonResponse
    {
        $menu = $this->menuRepository->currentUserMenu();
        return $this->sendResponse($menu, 'Menu successfully');
    }
}
