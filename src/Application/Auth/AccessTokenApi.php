<?php

namespace Wanphp\Plugins\Weixin\Application\Auth;


use Exception;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Stream;

class AccessTokenApi extends OAuth2Api
{

  /**
   * @return Response
   * @throws Exception
   * @OA\Post(
   *   path="/auth/accessToken",
   *   tags={"Auth"},
   *   summary="客户端通过授权码请求访问令牌",
   *   operationId="userAccessToken",
   *   @OA\RequestBody(
   *     description="获取access_token",
   *     required=true,
   *     @OA\MediaType(
   *       mediaType="application/json",
   *       @OA\Schema(
   *         type="object",
   *         @OA\Property(
   *           property="grant_type",
   *           type="string",
   *           example="authorization_code",
   *           description="授权模式，值固定为：authorization_code"
   *         ),
   *         @OA\Property(
   *           property="client_id",
   *           type="string",
   *           description="客户端ID,由服务端分配"
   *         ),
   *         @OA\Property(
   *           property="client_secret",
   *           type="string",
   *           description="客户端 secret,由服务端分配"
   *         ),
   *         @OA\Property(
   *           property="redirect_uri",
   *           type="string",
   *           description="使用与authorize请求相同的 URI。"
   *         ),
   *         @OA\Property(
   *           property="code",
   *           type="string",
   *           description="Authorize接口请求获取的登录凭证（code）。"
   *         )
   *       )
   *     )
   *   ),
   *   @OA\Response(
   *    response="201",
   *    description="获取AccessToken成功",
   *    @OA\JsonContent(
   *      allOf={
   *       @OA\Schema(ref="#/components/schemas/Success"),
   *       @OA\Schema(
   *         @OA\Property(
   *           property="datas",
   *           @OA\Property(property="token_type",type="string"),
   *           @OA\Property( property="expires_in",type="integer"),
   *           @OA\Property(property="access_token",type="string"),
   *           @OA\Property(property="refresh_token",type="string")
   *        )
   *       )
   *      }
   *    )
   *   ),
   *   @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    try {
      $post = $this->request->getParsedBody();
      $grant_type = $post['grant_type'];
      switch ($grant_type) {
        case 'client_credentials':
          $this->client_credentials();
          break;
        case 'authorization_code':
          $this->authorization_code();
          break;
        case 'refresh_token':
          $this->refresh_token();
          break;
        case 'password':
          $this->password();
          break;
        default:
          throw new Exception('Unsupported grant_type');
      }
      return $this->server->respondToAccessTokenRequest($this->request, $this->response);
    } catch (OAuthServerException $exception) {
      return $exception->generateHttpResponse($this->response);
    } catch (Exception $exception) {
      $body = new Stream(fopen('php://temp', 'r+'));
      $body->write($exception->getMessage());
      return $this->response->withStatus(400)->withBody($body);
    }
  }
}
