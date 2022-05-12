<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/8
 * Time: 10:24
 */

namespace Wanphp\Plugins\Weixin\Application;


use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Libray\Weixin\WeChatBase;

class ShareApi extends Api
{
  private WeChatBase $weChatBase;

  public function __construct(WeChatBase $weChatBase)
  {
    $this->weChatBase = $weChatBase;
  }

  /**
   * @return Response
   * @throws \Exception
   * @OA\Post(
   *  path="/getSignPackage",
   *  tags={"Public"},
   *  summary="公众号分享取签名",
   *  operationId="getSignPackage",
   *  @OA\RequestBody(
   *    description="验证地址",
   *    required=true,
   *    @OA\MediaType(
   *      mediaType="application/json",
   *      @OA\Schema(type="object",@OA\Property(property="url",type="string"))
   *    )
   *  ),
   *  @OA\Response(response="200",description="请求成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *  @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    $data = $this->request->getParsedBody();
    return $this->respondWithData($this->weChatBase->getSignPackage($data['url']));
  }
}
