<?php

namespace Wanphp\Plugins\Weixin\Application;

use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

class GetUsersApi extends Api
{
  private UserInterface $user;

  public function __construct(UserInterface $user)
  {
    $this->user = $user;
  }

  /**
   * @inheritDoc
   * @OA\Post(
   *   path="/api/user/get",
   *   tags={"Client"},
   *   summary="客户端通过用户id获取用户",
   *   operationId="clientGetUsers",
   *   security={{"bearerAuth":{}}},
   *   @OA\RequestBody(
   *    description="用户id",
   *    required=true,
   *    @OA\MediaType(
   *      mediaType="application/json",
   *      @OA\Schema(@OA\Property(property="id",type="array",@OA\Items(format="int64",type="integer")))
   *    )
   *   ),
   *   @OA\Response(response="200",description="用户信息",@OA\JsonContent(ref="#/components/schemas/Success")),
   *   @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case 'POST':
        $data = $this->request->getParsedBody();
        if (!isset($data['uid'])) return $this->respondWithError('无用户ID');
        return $this->respondWithData($this->user->getUsers(['u.id' => $data['uid']]));
      case 'GET':
        $id = (int)$this->resolveArg('id');
        if ($id > 0) return $this->user->getUser($id);
        else return $this->respondWithError('用户ID错误！');
      default:
        return $this->respondWithError('非法请求！');
    }
  }
}