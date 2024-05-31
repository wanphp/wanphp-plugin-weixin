<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/9
 * Time: 9:00
 */

namespace Wanphp\Plugins\Weixin\Application\Manage;


use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Libray\Weixin\WeChatBase;
use Wanphp\Plugins\Weixin\Application\Api;
use Wanphp\Plugins\Weixin\Domain\CustomMenuInterface;

/**
 * Class CustomMenuApi
 * @title 自定义菜单
 * @route /admin/weixin/menu
 * @package Wanphp\Plugins\Weixin\Application\Manage
 */
class CustomMenuApi extends Api
{
  private CustomMenuInterface $customMenu;
  private WeChatBase $weChatBase;

  public function __construct(CustomMenuInterface $customMenu, WeChatBase $weChatBase)
  {
    $this->customMenu = $customMenu;
    $this->weChatBase = $weChatBase;
  }

  /**
   * @return Response
   * @throws Exception
   * @OA\Post(
   *  path="/admin/weixin/menu",
   *  tags={"WeixinCustomMenu"},
   *  summary="添加公众号自定义菜单",
   *  operationId="addWeixinCustomMenu",
   *  security={{"bearerAuth":{}}},
   *   @OA\RequestBody(
   *     description="自定义菜单",
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",@OA\Schema(ref="#/components/schemas/newCustomMenu")
   *     )
   *   ),
   *  @OA\Response(response="201",description="添加成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Put(
   *  path="/admin/weixin/menu/{id}",
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
   *       mediaType="application/json",@OA\Schema(ref="#/components/schemas/newCustomMenu")
   *     )
   *   ),
   *  @OA\Response(response="201",description="更新成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Delete(
   *  path="/admin/weixin/menu/{id}",
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
   *  path="/admin/weixin/menu",
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
        return $this->respondWithData(['upNum' => $num], 201);
      case 'DELETE':
        $delNum = $this->customMenu->delete(['id' => $this->args['id']]);
        $delNum += $this->customMenu->delete(['parent_id' => $this->args['id']]);
        return $this->respondWithData(['delNum' => $delNum]);
      case 'GET':
        try {
          $userTags = $this->weChatBase->getTags();
          $data = [
            'tags' => $userTags['tags']
          ];
          $data['tag_id'] = intval($this->args['id'] ?? 0);
          $where = ['tag_id' => $data['tag_id'], 'parent_id' => 0, 'ORDER' => ['tag_id' => 'ASC', 'parent_id' => 'ASC', 'sortOrder' => 'ASC']];
          $menus = [];
          foreach ($this->customMenu->select('*', $where) as $item) {
            $where['parent_id'] = $item['id'];
            $item['subBtn'] = $this->customMenu->select('*', $where);
            $menus[] = $item;
          }
          $tags = array_column($data['tags'], 'name', 'id');
          $data['tagTitle'] = $tags[$data['tag_id']] ?? '默认';
          $data['menus'] = $menus;
          $data['menuTitle'] = "添加{$data['tagTitle']}一级菜单";

          return $this->respondView('@weixin/custom-menu.html', $data);
        } catch (Exception $exception) {
          return $this->respondView('/admin/error/404.html', ['message' => '错误代码：' . $exception->getMessage()]);
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }
}
