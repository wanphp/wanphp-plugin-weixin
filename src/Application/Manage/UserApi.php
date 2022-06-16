<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/28
 * Time: 15:48
 */

namespace Wanphp\Plugins\Weixin\Application\Manage;


use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Application\Api;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

/**
 * Class UserApi
 * @title 用户管理
 * @route /admin/weixin/user
 * @package Wanphp\Plugins\Weixin\Application\Manage
 */
class UserApi extends Api
{
  private UserInterface $user;

  public function __construct(UserInterface $user)
  {
    $this->user = $user;
  }

  /**
   * @return Response
   * @throws Exception
   * @OA\Patch(
   *  path="/admin/weixin/user/{id}",
   *  tags={"User"},
   *  summary="更新用户，管理员操作",
   *  operationId="editUser",
   *  security={{"bearerAuth":{}}},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     description="用户ID",
   *     required=true,
   *     @OA\Schema(format="int64",type="integer")
   *   ),
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
   *   path="/admin/weixin/user/{id}",
   *   tags={"User"},
   *   summary="查看用户信息，后台管理员查看",
   *   operationId="getUser",
   *   security={{"bearerAuth":{}}},
   *   @OA\Parameter(
   *     name="id",
   *     in="path",
   *     description="用户ID",
   *     required=true,
   *     @OA\Schema(format="int64",type="integer")
   *   ),
   *   @OA\Response(
   *    response="200",
   *    description="用户信息",
   *    @OA\JsonContent(
   *      allOf={
   *       @OA\Schema(ref="#/components/schemas/Success"),
   *       @OA\Schema(ref="#/components/schemas/UserEntity")
   *      }
   *    )
   *  ),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case 'PATCH':
        $data = $this->request->getParsedBody();
        if (empty($data)) return $this->respondWithError('无用户数据');
        $num = $this->user->update($data, ['id' => $this->args['id']]);
        return $this->respondWithData(['upNum' => $num], 201);
      case 'GET':
        if ($this->request->getHeaderLine("X-Requested-With") == "XMLHttpRequest") {
          $where = [];
          $params = $this->request->getQueryParams();
          if (!empty($params['search']['value'])) {
            $keyword = trim($params['search']['value']);
            $where['OR'] = [
              'u.name[~]' => $keyword,
              'u.nickname[~]' => $keyword,
              'u.tel[~]' => $keyword
            ];
          }

          // 推广用户
          if (isset($params['pid']) && $params['pid'] > 0) {
            $where['p.parent_id'] = intval($params['pid']);
          }

          $recordsFiltered = $this->user->count('id', $where);
          $where['LIMIT'] = [$params['start'], $params['length']];
          $where['ORDER'] = ["u.id" => "DESC"];

          $data = [
            "draw" => $params['draw'],
            "recordsTotal" => $this->user->count('id'),
            "recordsFiltered" => $recordsFiltered,
            'data' => $this->user->getUsers($where)
          ];
          return $this->respondWithData($data);
        } else {
          $data = [
            'title' => '微信用户管理'
          ];

          return $this->respondView('@weixin/user-list.html', $data);
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }
}
