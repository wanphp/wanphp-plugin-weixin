<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/28
 * Time: 16:35
 */

namespace Wanphp\Plugins\Weixin\Application\Manage;


use Wanphp\Libray\Slim\CacheInterface;
use Wanphp\Libray\Weixin\WeChatBase;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Application\Api;
use Exception;

/**
 * Class TagsApi
 * @title 用户标签
 * @route /admin/weixin/tags
 * @package Wanphp\Plugins\Weixin\Application\Manage
 */
class TagsApi extends Api
{
  private WeChatBase $weChatBase;
  private CacheInterface $cache;

  public function __construct(WeChatBase $weChatBase, CacheInterface $cache)
  {
    $this->weChatBase = $weChatBase;
    $this->cache = $cache;
  }

  /**
   * @return Response
   * @throws Exception
   * @OA\Post(
   *  path="/admin/weixin/tags",
   *  tags={"WeixinTag"},
   *  summary="添加公众号用户标签",
   *  operationId="addWeixinTag",
   *  security={{"bearerAuth":{}}},
   *   @OA\RequestBody(
   *     description="用户标签",
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(type="object",@OA\Property(property="name",type="string"))
   *     )
   *   ),
   *  @OA\Response(response="201",description="添加成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Put(
   *  path="/admin/weixin/tags/{id}",
   *  tags={"WeixinTag"},
   *  summary="修改公众号用户标签",
   *  operationId="editWeixinTag",
   *  security={{"bearerAuth":{}}},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     description="标签ID",
   *     required=true,
   *     @OA\Schema(format="int64",type="integer")
   *   ),
   *   @OA\RequestBody(
   *     description="用户标签",
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(type="object",@OA\Property(property="name",type="string"))
   *     )
   *   ),
   *  @OA\Response(response="201",description="更新成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Delete(
   *  path="/admin/weixin/tags/{id}",
   *  tags={"WeixinTag"},
   *  summary="删除公众号用户标签",
   *  operationId="delWeixinTag",
   *  security={{"bearerAuth":{}}},
   *  @OA\Parameter(
   *    name="id",
   *    in="path",
   *    description="标签ID",
   *    required=true,
   *    @OA\Schema(format="int64",type="integer")
   *  ),
   *  @OA\Response(response="200",description="删除成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Get(
   *  path="/admin/weixin/tags",
   *  tags={"WeixinTag"},
   *  summary="用户角色",
   *  operationId="listWeixinTag",
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
        if ($data['name'] != '') {
          $result = $this->weChatBase->createTag($data['name']);
          return $this->respondWithData($result, 201);
        } else {
          return $this->respondWithError('缺少标签名称');
        }
      case 'PUT':
        $id = $this->args['id'] ?? 0;
        $data = $this->request->getParsedBody();
        if ($id > 0 && $data['name'] != '') {
          $result = $this->weChatBase->updateTag($id, $data['name']);
          return $this->respondWithData($result, 201);
        } else {
          return $this->respondWithError('缺少ID或标签名称');
        }
      case 'DELETE':
        $id = $this->args['id'] ?? 0;
        if ($id > 0) {
          $result = $this->weChatBase->deleteTag($id);
          return $this->respondWithData($result);
        } else {
          return $this->respondWithError('缺少ID');
        }
      case 'GET':
        //公众号粉丝数
        try {
          $user_total = $this->cache->get('wxuser_total');
          if (!$user_total) {
            $list = $this->weChatBase->getUserList();
            $user_total = $list['total'];
            $this->cache->set('wxuser_total', $user_total, 3600);
          }
          $userTags = $this->weChatBase->getTags();
          $data = [
            'title' => '粉丝标签管理',
            'tags' => $userTags['tags'] ?? [],
            'total' => $user_total
          ];

          return $this->respondView('@weixin/tags.html', $data);
        } catch (Exception $exception) {
          return $this->respondView('/admin/error/404.html', ['message' => '错误代码：' . $exception->getMessage()]);
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }
}
