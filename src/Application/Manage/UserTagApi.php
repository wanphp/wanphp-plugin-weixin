<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/17
 * Time: 18:08
 */

namespace Wanphp\Plugins\Weixin\Application\Manage;


use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Libray\Weixin\WeChatBase;
use Wanphp\Plugins\Weixin\Application\Api;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

class UserTagApi extends Api
{
  private UserInterface $user;
  private WeChatBase $weChatBase;

  public function __construct(UserInterface $user, WeChatBase $weChatBase)
  {
    $this->user = $user;
    $this->weChatBase = $weChatBase;
  }

  /**
   * @return Response
   * @throws Exception
   * @OA\Patch(
   *  path="/admin/weixin/user/tag",
   *  tags={"WeixinUserTag"},
   *  summary="给粉丝添加标签",
   *  operationId="addWeixinUserTag",
   *  security={{"bearerAuth":{}}},
   *   @OA\RequestBody(
   *     description="用户标签",
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(
   *         type="object",
   *         @OA\Property(
   *           property="uid",
   *           type="string",
   *           description="用户ID"
   *         ),
   *         @OA\Property(
   *           property="tagId",
   *           type="string",
   *           description="标签ID"
   *         )
   *       )
   *     )
   *   ),
   *  @OA\Response(response="201",description="添加成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Delete(
   *  path="/admin/weixin/user/tag",
   *  tags={"WeixinUserTag"},
   *  summary="删除粉丝标签",
   *  operationId="delWeixinUserTag",
   *  security={{"bearerAuth":{}}},
   *  @OA\RequestBody(
   *     description="用户标签",
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(
   *         type="object",
   *         @OA\Property(
   *           property="uid",
   *           type="string",
   *           description="用户ID"
   *         ),
   *         @OA\Property(
   *           property="tagId",
   *           type="string",
   *           description="标签ID"
   *         )
   *       )
   *     )
   *   ),
   *  @OA\Response(response="200",description="删除成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Get(
   *  path="/admin/weixin/user/tag/{openid}",
   *  tags={"WeixinUserTag"},
   *  summary="用户身上的标签",
   *  operationId="UserWeixinTagList",
   *  security={{"bearerAuth":{}}},
   *  @OA\Parameter(
   *    name="openid",
   *    in="path",
   *    description="粉丝OPENID",
   *    required=true,
   *    @OA\Schema(type="string")
   *  ),
   *  @OA\Response(response="200",description="请求成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case 'PATCH':
        $data = $this->request->getParsedBody();
        if ($data['uid'] != '') {
          $result = $this->user->membersTagging($data['uid'], $data['tagId']);
          return $this->respondWithData($result, 201);
        } else {
          return $this->respondWithError('未知用户');
        }
      case 'DELETE':
        $data = $this->request->getParsedBody();
        if ($data['uid'] != '') {
          $result = $this->user->membersUnTagging($data['uid'], $data['tagId']);
          return $this->respondWithData($result, 201);
        } else {
          return $this->respondWithError('未知用户');
        }
      case 'GET':
        $openid = $this->args['openid'] ?? '';
        if ($openid) {
          $result = $this->weChatBase->memberGetidlist($openid);
          return $this->respondWithData($result['tagid_list'] ?? []);
        } else {
          return $this->respondWithError('未知用户');
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }
}
