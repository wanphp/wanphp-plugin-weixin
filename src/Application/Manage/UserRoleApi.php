<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/28
 * Time: 16:25
 */

namespace Wanphp\Plugins\Weixin\Application\Manage;


use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Application\Api;
use Wanphp\Plugins\Weixin\Domain\UserRoleInterface;

/**
 * Class UserRoleApi
 * @title 用户角色
 * @route /api/manage/user/role
 * @package App\Application\Api\Manage\Users
 */
class UserRoleApi extends Api
{
  private $userRole;

  public function __construct(UserRoleInterface $userRole)
  {
    $this->userRole = $userRole;
  }

  /**
   * @return Response
   * @throws \Exception
   * @OA\Post(
   *  path="/api/manage/weixin/user/role",
   *  tags={"UserRole"},
   *  summary="添加用户角色",
   *  operationId="addUserRole",
   *  security={{"bearerAuth":{}}},
   *   @OA\RequestBody(
   *     description="用户角色数据",
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(ref="#/components/schemas/UserRoleEntity")
   *     )
   *   ),
   *  @OA\Response(
   *    response="201",
   *    description="添加成功",
   *    @OA\JsonContent(
   *      allOf={
   *       @OA\Schema(ref="#/components/schemas/Success"),
   *       @OA\Schema(
   *         @OA\Property(property="res",@OA\Property(property="id",type="integer"))
   *       )
   *      }
   *    )
   *  ),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Put(
   *  path="/api/manage/weixin/user/role/{ID}",
   *  tags={"UserRole"},
   *  summary="修改用户角色",
   *  operationId="editUserRole",
   *  security={{"bearerAuth":{}}},
   *   @OA\Parameter(
   *     name="ID",
   *     in="path",
   *     description="用户角色ID",
   *     required=true,
   *     @OA\Schema(format="int64",type="integer")
   *   ),
   *   @OA\RequestBody(
   *     description="指定需要更新数据",
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(ref="#/components/schemas/UserRoleEntity")
   *     )
   *   ),
   *  @OA\Response(
   *    response="201",
   *    description="更新成功",
   *    @OA\JsonContent(
   *      allOf={
   *       @OA\Schema(ref="#/components/schemas/Success"),
   *       @OA\Schema(
   *         @OA\Property(property="res",@OA\Property(property="up_num",type="integer"))
   *       )
   *      }
   *    )
   *  ),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Delete(
   *  path="/api/manage/weixin/user/role/{ID}",
   *  tags={"UserRole"},
   *  summary="删除用户角色",
   *  operationId="delUserRole",
   *  security={{"bearerAuth":{}}},
   *  @OA\Parameter(
   *    name="ID",
   *    in="path",
   *    description="用户角色ID",
   *    required=true,
   *    @OA\Schema(format="int64",type="integer")
   *  ),
   *  @OA\Response(
   *    response="200",
   *    description="删除成功",
   *    @OA\JsonContent(
   *      allOf={
   *       @OA\Schema(ref="#/components/schemas/Success"),
   *       @OA\Schema(
   *         @OA\Property(property="res",@OA\Property(property="del_num",type="integer"))
   *       )
   *      }
   *    )
   *  ),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   * @OA\Get(
   *  path="/api/manage/weixin/user/role",
   *  tags={"UserRole"},
   *  summary="用户角色",
   *  operationId="listUserRole",
   *  security={{"bearerAuth":{}}},
   *  @OA\Response(
   *    response="200",
   *    description="请求成功",
   *    @OA\JsonContent(
   *      allOf={
   *       @OA\Schema(ref="#/components/schemas/Success"),
   *       @OA\Schema(
   *         @OA\Property(property="res",type="array",@OA\Items(ref="#/components/schemas/UserRoleEntity"))
   *       )
   *      }
   *    )
   *  ),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case  'POST';
        $data = $this->request->getParsedBody();
        $data['id'] = $this->userRole->insert($data);
        return $this->respondWithData($data, 201);
      case  'PUT';
        $data = $this->request->getParsedBody();
        $num = $this->userRole->update($data, ['id' => $this->args['id']]);
        return $this->respondWithData(['up_num' => $num], 201);
      case  'DELETE';
        $delnum = $this->userRole->delete(['id' => $this->args['id']]);
        return $this->respondWithData(['del_num' => $delnum], 200);
      case 'GET';
        return $this->respondWithData($this->userRole->select('*'));
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }
}
