<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/25
 * Time: 10:48
 */

namespace  Wanphp\Plugins\Weixin\Application;


use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

class UserApi extends Api
{
  /**
   * @var UserInterface
   */
  protected $user;

  /**
   * @param UserInterface $user
   */
  public function __construct(UserInterface $user)
  {
    $this->user = $user;
  }

  /**
   * @return Response
   * @throws \Exception
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
   *      @OA\Schema(
   *        @OA\Property(property="datas", @OA\Property(property="up_num",type="integer",description="更新数量"))
   *      )
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
   *       @OA\Schema(
   *         @OA\Property(property="datas",example={
  "name": "",
  "tel": null,
  "address": "",
  "integral": "0",
  "cash_back": "0.00",
  "money": "0.00"
  })
   *       )
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
      case 'PATCH':
        $data = $this->request->getParsedBody();
        if (empty($data)) return $this->respondWithError('无用户数据');
        $num = $this->user->update($data, ['id' => $uid]);
        return $this->respondWithData(['up_num' => $num], 201);
        break;
      case 'GET':
        //id,openid,sex,role_id,cash_back,money,
        $user = $this->user->get('nickname,headimgurl,name,tel,address,integral', ['id' => $uid]);
        if ($user) return $this->respondWithData($user);
        else return $this->respondWithError('用户不存在');
        break;
      default:
        return $this->respondWithError('禁止访问', 403);
    }


  }
}
