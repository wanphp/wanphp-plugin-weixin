<?php

namespace Wanphp\Plugins\Weixin\Application\Manage;

use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Application\Api;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

class SearchUserApi extends Api
{
  private UserInterface $user;

  public function __construct(UserInterface $user)
  {
    $this->user = $user;
  }

  /**
   * @inheritDoc
   * @OA\Get(
   *   path="/api/user/search",
   *   tags={"Client"},
   *   summary="客户端搜索用户",
   *   operationId="SearchUser",
   *   security={{"bearerAuth":{}}},
   *   @OA\Parameter(
   *         name="q",
   *         in="query",
   *         description="搜索关键词",
   *         required=true,
   *         @OA\Schema(type="string")
   *     ),
   *   @OA\Parameter(
   *         name="page",
   *         in="query",
   *         description="分页",
   *         required=false,
   *         @OA\Schema(
   *             type="integer",
   *             format="int32"
   *         )
   *     ),
   *   @OA\Response(response="200",description="用户信息",@OA\JsonContent(ref="#/components/schemas/Success")),
   *   @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    $params = $this->request->getQueryParams();
    if (isset($params['q']) && $params['q'] != '') {
      $keyword = trim($params['q']);
    } else {
      return $this->respondWithError('关键词不能为空！');
    }
    $page = intval($params['page'] ?? 1);

    $data = $this->user->searchUsers($keyword, $page);
    return $this->respondWithData($data);
  }
}
