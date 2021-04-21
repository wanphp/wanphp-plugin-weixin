<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/9
 * Time: 9:00
 */

namespace Wanphp\Plugins\Weixin\Application\Manage;


use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Application\Api;
use Wanphp\Plugins\Weixin\Domain\CustomMenuInterface;

/**
 * Class CustomMenuApi
 * @title 自定义菜单
 * @route /api/manage/weixin/menu
 * @package Wanphp\Plugins\Weixin\Application\Manage
 */
class CustomMenuApi extends Api
{
  private $customMenu;

  public function __construct(CustomMenuInterface $customMenu)
  {
    $this->customMenu = $customMenu;
  }

  /**
   * @return Response
   * @throws \Exception
   * @OA\Post(
   *  path="/api/manage/weixin/menu",
   *  tags={"WeixinCustomMenu"},
   *  summary="添加公众号自定义菜单",
   *  operationId="addWeixinCustomMenu",
   *  security={{"bearerAuth":{}}},
   *   @OA\RequestBody(
   *     description="自定义菜单",
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",@OA\Schema(ref="#/components/schemas/NewCustomMenu")
   *     )
   *   ),
   *  @OA\Response(response="201",description="添加成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Put(
   *  path="/api/manage/weixin/menu/{id}",
   *  tags={"WeixinCustomMenu"},
   *  summary="修改公众号自定义菜单",
   *  operationId="editWeixinCustomMenu",
   *  security={{"bearerAuth":{}}},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     description="自定义菜单ID",
   *     required=true,
   *     @OA\Schema(format="int64",type="integer")
   *   ),
   *   @OA\RequestBody(
   *     description="自定义菜单",
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",@OA\Schema(ref="#/components/schemas/NewCustomMenu")
   *     )
   *   ),
   *  @OA\Response(response="201",description="更新成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Delete(
   *  path="/api/manage/weixin/menu/{id}",
   *  tags={"WeixinCustomMenu"},
   *  summary="删除公众号自定义菜单",
   *  operationId="delWeixinCustomMenu",
   *  security={{"bearerAuth":{}}},
   *  @OA\Parameter(
   *    name="id",
   *    in="path",
   *    description="自定义菜单ID",
   *    required=true,
   *    @OA\Schema(format="int64",type="integer")
   *  ),
   *  @OA\Response(response="200",description="删除成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Get(
   *  path="/api/manage/weixin/menu",
   *  tags={"WeixinCustomMenu"},
   *  summary="自定义菜单",
   *  operationId="listWeixinCustomMenu",
   *  security={{"bearerAuth":{}}},
   *  @OA\Response(response="200",description="请求成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case 'POST':
        $data = $this->request->getParsedBody();
        $id = $this->customMenu->insert($data);
        return $this->respondWithData(['id' => $id], 201);
      case 'PUT':
        $data = $this->request->getParsedBody();
        $num = $this->customMenu->update($data, ['id' => $this->args['id']]);
        return $this->respondWithData(['up_num' => $num], 201);
      case 'DELETE':
        $delnum = $this->customMenu->delete(['id' => $this->args['id']]);
        $delnum += $this->customMenu->delete(['parent_id' => $this->args['id']]);
        return $this->respondWithData(['del_num' => $delnum], 200);
      case 'GET':
        $params = $this->request->getQueryParams();
        $tag_id = $params['tag_id'] ?? 0;
        $where = ['tag_id' => $tag_id, 'parent_id' => 0, 'ORDER' => ['tag_id' => 'ASC', 'parent_id' => 'ASC', 'sortOrder' => 'ASC']];
        $menus = [];
        foreach ($this->customMenu->select('*', $where) as $item) {
          $where['parent_id'] = $item['id'];
          $item['subBtns'] = $this->customMenu->select('*', $where);
          $menus[] = $item;
        }
        return $this->respondWithData($menus);
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }
}
