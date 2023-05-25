<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/25
 * Time: 10:48
 */

namespace Wanphp\Plugins\Weixin\Application;


use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

class UserApi extends Api
{
  /**
   * @var UserInterface
   */
  protected UserInterface $user;
  protected PublicInterface $public;

  /**
   * @param UserInterface $user
   * @param PublicInterface $public
   */
  public function __construct(UserInterface $user, PublicInterface $public)
  {
    $this->user = $user;
    $this->public = $public;
  }

  /**
   * @return Response
   * @throws Exception
   * @OA\Post(
   *  path="/api/user",
   *  tags={"Client"},
   *  summary="客户端添加用户，同一开放平台下的其它APP用户、小程序用户",
   *  operationId="clientInsertUser",
   *  security={{"bearerAuth":{}}},
   *  @OA\RequestBody(
   *    description="添加新用户",
   *    required=true,
   *    @OA\MediaType(
   *      mediaType="application/json",
   *      @OA\Schema(ref="#/components/schemas/UserEntity"),
   *      example={"unionid": "", "nickname": "", "headimgurl": "", "sex": "0", "name": "", "tel": null}
   *    )
   *  ),
   *  @OA\Response(
   *    response="201",
   *    description="用户更新成功",
   *  @OA\JsonContent(
   *     allOf={
   *      @OA\Schema(ref="#/components/schemas/Success"),
   *      @OA\Schema(@OA\Property(property="uid",type="integer",description="用户ID"))
   *     }
   *   )
   *  ),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Put(
   *  path="/api/user/{id}",
   *  tags={"Client"},
   *  summary="客户端更新用户",
   *  operationId="clientUpdateUser",
   *  security={{"bearerAuth":{}}},
   *  @OA\Parameter(
   *     name="id",
   *     in="path",
   *     description="用户ID",
   *     required=true,
   *     @OA\Schema(format="int64",type="integer")
   *   ),
   *  @OA\RequestBody(
   *    description="更新用户数据",
   *    required=true,
   *    @OA\MediaType(
   *      mediaType="application/json",
   *      @OA\Schema(ref="#/components/schemas/UserEntity"),
   *      example={"nickname": "", "headimgurl": "", "sex": "0", "name": "", "tel": null}
   *    )
   *  ),
   *  @OA\Response(
   *    response="201",
   *    description="用户更新成功",
   *  @OA\JsonContent(
   *     allOf={
   *      @OA\Schema(ref="#/components/schemas/Success"),
   *      @OA\Schema(@OA\Property(property="upNum",type="integer",description="更新数量"))
   *     }
   *   )
   *  ),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Patch(
   *  path="/api/user",
   *  tags={"User"},
   *  summary="更新用户信息，用户操作",
   *  operationId="UpdateUser",
   *  security={{"bearerAuth":{}}},
   *  @OA\RequestBody(
   *    description="指定更新用户数据",
   *    required=true,
   *    @OA\MediaType(
   *      mediaType="application/json",
   *      @OA\Schema(ref="#/components/schemas/UserEntity"),
   *      example={"name": "", "tel": null, "address": "", "integral": "0", "cash_back": "0.00", "money": "0.00"}
   *    )
   *  ),
   *  @OA\Response(
   *    response="201",
   *    description="用户更新成功",
   *  @OA\JsonContent(
   *     allOf={
   *      @OA\Schema(ref="#/components/schemas/Success"),
   *      @OA\Schema(@OA\Property(property="upNum",type="integer",description="更新数量"))
   *     }
   *   )
   *  ),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Get(
   *   path="/api/user",
   *   tags={"User"},
   *   summary="查看当前用户信息，用户操作",
   *   operationId="ViewUser",
   *   security={{"bearerAuth":{}}},
   *   @OA\Response(
   *    response="200",
   *    description="用户信息",
   *    @OA\JsonContent(
   *      allOf={
   *       @OA\Schema(ref="#/components/schemas/Success"),
   *       @OA\Schema(example={
  "name": "",
  "tel": null,
  "address": "",
  "integral": "0",
  "cash_back": "0.00",
  "money": "0.00"
  })
   *      }
   *    )
   *  ),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case 'POST':
        // 客户端添加用户
        $res = $this->user->addUser($this->request->getParsedBody());
        if (isset($res['errMsg'])) return $this->respondWithError($res['errMsg'] ?? 'error');
        else return $this->respondWithData($res, 201);
      case 'PUT':
        // 客户端修改用户
        $id = (int)$this->resolveArg('id');
        $res = $this->user->updateUser($id, $this->request->getParsedBody());
        if (isset($res['errMsg'])) return $this->respondWithError($res['errMsg'] ?? 'error');
        else return $this->respondWithData($res, 201);
      case 'PATCH':
        $uid = $this->request->getAttribute('oauth_user_id');
        if ($uid < 1) return $this->respondWithError('未知用户', 422);
        // 用户自己修改信息
        $data = $this->request->getParsedBody();
        if (empty($data)) return $this->respondWithError('无用户数据');
        $num = $this->user->update($data, ['id' => $uid]);
        return $this->respondWithData(['upNum' => $num], 201);
      case 'GET':
        // 用户取自己的信息
        $uid = $this->request->getAttribute('oauth_user_id');
        if ($uid < 1) return $this->respondWithError('未知用户', 422);
        //id,openid,sex,role_id,cash_back,money,
        $user = $this->user->get('id,unionid,nickname,headimgurl,name,tel,address,remark', ['id' => $uid]);

        if ($user) {
          $user['tagId'] = $this->public->get('tagid_list[JSON]', ['id' => $uid]);
          return $this->respondWithData($user);
        } else return $this->respondWithError('用户不存在');
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }


}
