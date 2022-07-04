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
use Wanphp\Plugins\Weixin\Domain\UserInterface;

class UserApi extends Api
{
  /**
   * @var UserInterface
   */
  protected UserInterface $user;

  /**
   * @param UserInterface $user
   */
  public function __construct(UserInterface $user)
  {
    $this->user = $user;
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
   *      @OA\Schema(@OA\Property(property="up_num",type="integer",description="更新数量"))
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
    $uid = $this->request->getAttribute('oauth_user_id');
    if ($uid < 1) return $this->respondWithError('未知用户', 422);

    switch ($this->request->getMethod()) {
      case 'POST':
        // 客户端添加用户
        $data = $this->request->getParsedBody();
        if (!isset($data['unionid']) || count($data['unionid']) != 28) return $this->respondWithError('unionid不正确');
        $id = $this->user->get('id', ['unionid' => $data['unionid']]);
        if ($id) {
          $this->user->update($data, ['id' => $uid]);
        } else {
          $id = $this->user->insert($data);
        }
        return $this->respondWithData(['uid' => $id], 201);
      case 'PUT':
        // 客户端修改用户
        $data = $this->request->getParsedBody();
        if (isset($data['unionid'])) return $this->respondWithError('不可以修改unionid');
        $id = (int)$this->resolveArg('id');
        if ($id > 0) $upNum = $this->user->update($data, ['id' => $uid]);
        return $this->respondWithData(['upNum' => $upNum ?? 0], 201);
      case 'PATCH':
        // 用户自己修改信息
        $data = $this->request->getParsedBody();
        if (empty($data)) return $this->respondWithError('无用户数据');
        $num = $this->user->update($data, ['id' => $uid]);
        return $this->respondWithData(['up_num' => $num], 201);
      case 'GET':
        //id,openid,sex,role_id,cash_back,money,
        $user = $this->user->get('id,nickname,headimgurl,name,tel,address,remark,integral', ['id' => $uid]);
        if ($user) return $this->respondWithData($user);
        else return $this->respondWithError('用户不存在');
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }
}
