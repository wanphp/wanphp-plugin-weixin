<?php

namespace Wanphp\Plugins\Weixin\Application\Auth;


use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Defuse\Crypto\Crypto;
use Exception;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Stream;
use Wanphp\Plugins\Weixin\Entities\OAuth2\UserEntity;

class AuthorizeApi extends OAuth2Api
{
  /**
   * @return Response
   * @throws Exception
   * @OA\Get(
   *   path="/auth/authorize",
   *   tags={"Auth"},
   *   summary="公众号用户登录，获取授权码或访问令牌",
   *   operationId="userAuthorize",
   *   @OA\Parameter(
   *    name="response_type",
   *    in="query",
   *    required=true,
   *    description="授权类型，必选项，值固定为：code或token",
   *    @OA\Schema(type="string")
   *   ),
   *   @OA\Parameter(
   *    name="client_id",
   *    in="query",
   *    required=true,
   *    description="客户端ID,由服务端分配",
   *    @OA\Schema(type="string")
   *   ),
   *   @OA\Parameter(
   *    name="redirect_uri",
   *    in="query",
   *    description="重定向URI，可选项，不填写时默认预先注册的重定向URI， 请使用 urlEncode 对链接进行处理",
   *    @OA\Schema(type="string")
   *   ),
   *   @OA\Parameter(
   *    name="scope",
   *    in="query",
   *    description="授权范围，可选项，以空格分隔",
   *    @OA\Schema(type="string")
   *   ),
   *   @OA\Parameter(
   *    name="state",
   *    in="query",
   *    description="CSRF令牌，可选项，但强烈建议使用，应将该值存储与用户会话中，以便在返回时验证.",
   *    @OA\Schema(type="string")
   *   ),
   *   @OA\Response(response="200",description="获取Code成功",@OA\JsonContent(ref="#/components/schemas/Success")),
   *   @OA\Response(response="400",description="请求失败",@OA\JsonContent(ref="#/components/schemas/Error"))
   * )
   */
  protected function action(): Response
  {
    $queryParams = $this->request->getQueryParams();
    $response_type = $queryParams['response_type'] ?? $queryParams['state'] ?? '';
    if ($response_type == 'code') $this->authorization_code();
    if ($response_type == 'token') $this->implicit();
    try {
      //使用微信公众号授权登录
      if (isset($queryParams['state']) &&
        is_string($queryParams['state']) &&
        !in_array($queryParams['state'], ['code', 'token']) &&
        str_contains($this->request->getServerParams()['HTTP_USER_AGENT'], 'MicroMessenger')) {
        // 检查cookie中是否有记录用户信息
        if (isset($_COOKIE['u_code'])) {
          $user_id = Crypto::decrypt($_COOKIE['u_code'], $this->encryptionKey);
        } else {
          // 将 $queryParams 存放在当前会话(session)中，用于验证完回调回来时验证 HTTP 请求
          $_SESSION['authQueryParams'] = $queryParams;

          // 跳转到微信，获取OPENID
          return $this->user->oauthRedirect($this->request, $this->response);
        }
      }
      if (isset($queryParams['code'])) {//微信公众号认证回调
        if ($this->webAuthorization) {
          $access_token = $this->user->getOauthAccessToken($queryParams['code'], '');
          if ($access_token) {
            $user = $this->user->getOauthUserinfo($access_token);
            $user_id = $user['id'];
          }
        } else {
          // 没有网页授权获取用户基本信息，通过，公众号被动回复连接登录
          $user_id = Crypto::decrypt($queryParams['code'], $this->encryptionKey);
          // 如果是后端扫码验证，会在调用oauthRedirect生成跳转时记录oauth_state，state为后端发起时生成的16位随机数或网页上固定写的weixin
          $adminLogin = !empty($_SESSION['oauth_state']) && (strlen($_SESSION['oauth_state']) == 16 || $_SESSION['oauth_state'] === 'weixin');
          unset($_SESSION['oauth_state']);
          if (intval($user_id) > 0
            && str_contains($this->request->getServerParams()['HTTP_USER_AGENT'], 'MicroMessenger')
            && (!isset($_SESSION['authQueryParams']) || $adminLogin)) { // 后端登录验证，客户端验证扫码
            $_SESSION['login_user_id'] = $user_id;
            // 保存到cookie,保存一年
            $expire_time = time() + (365 * 24 * 60 * 60);
            setcookie('u_code', $queryParams['code'], $expire_time, '/', '', true, true);
            if (isset($_SESSION['weixin']) && $_SESSION['weixin'] === 'weixin') {// 服务端后台扫码登录
              return $this->response->withHeader('Location', $this->httpHost() . $this->basePath . '/')->withStatus(301);
            } else {
              $data = ['title' => '授权成功',
                'msg' => '您已成功授权，详情查看客户端扫码页面！',
                'icon' => 'weui-icon-success'
              ];
              return $this->respondView('admin/error/wxerror.html', $data);
            }
          }
        }
      } else {
        //用户自定义登录方式
        switch ($this->request->getMethod()) {
          case  'POST':
            if (isset($_SESSION['login_user_id']) && is_numeric($_SESSION['login_user_id'])) {
              $user_id = $_SESSION['login_user_id'];
              unset($_SESSION['login_user_id']);
            } else {
              $data = $this->getFormData();
              if (!isset($data['account']) || $data['account'] == '') return $this->respondWithError('帐号为绑定手机号或邮箱！');
              if (!isset($data['password']) || $data['password'] == '') return $this->respondWithError('密码不能为空！');

              $res = $this->user->userLogin($data['account'], $data['password']);
              if (is_numeric($res) && $res > 0) {
                $_SESSION['login_user_id'] = $res;
                return $this->respondWithData(['res' => 'OK']);
              } else {
                return $this->respondWithError($res);
              }
            }
            break;
          case 'GET';
            if (!isset($user_id)) {
              // 将 $queryParams 存放在当前会话(session)中，用于POST提交登录验证完成后验证 HTTP 请求
              $_SESSION['authQueryParams'] = $queryParams;

              $code = Crypto::encrypt(session_id(), $this->encryptionKey);
              $renderer = new ImageRenderer(new RendererStyle(480), new SvgImageBackEnd());
              $writer = new Writer($renderer);
              $data['loginQr'] = $writer->writeString($this->request->getUri()->getScheme() . '://' . $this->request->getUri()->getHost() . '/auth/qrLogin?scope=' . ($queryParams['scope'] ?? '') . '&tk=' . $code);
              //return $this->respondView('@weixin/login.html', $data);
              //使用自定义模板
              return $this->respondView('oauth2/login.html', $data);
            }
        }
      }

      // 在会话(session)中取出验证的用户queryParams
      if (isset($_SESSION['authQueryParams'])) {
        $this->request = $this->request->withQueryParams($_SESSION['authQueryParams']);
      }
      $authRequest = $this->server->validateAuthorizationRequest($this->request);

      // 设置用户实体(userEntity)
      if (isset($user_id) && $user_id > 0) {
        $userEntity = new UserEntity();
        $userEntity->setIdentifier($user_id);
        $authRequest->setUser($userEntity);

        // 设置权限范围
        //$authRequest->setScopes(['basic']);
        // true = 批准，false = 拒绝
        $authRequest->setAuthorizationApproved(true);
      } else {
        $authRequest->setAuthorizationApproved(false);
      }

      if (isset($_SESSION['authQueryParams'])) unset($_SESSION['authQueryParams']);
      // 完成后重定向至客户端请求重定向地址
      return $this->server->completeAuthorizationRequest($authRequest, $this->response);
    } catch (OAuthServerException $exception) {
      $data = ['title' => '授权失败！',
        'msg' => '您未授权成功，系统无法识别您的身份，请选择使用完整服务。',
        'icon' => 'weui-icon-warn'
      ];
      return $this->respondView('admin/error/wxerror.html', $data);
      //return $exception->generateHttpResponse($this->response);
    } catch (Exception $exception) {
      $body = new Stream(fopen('php://temp', 'r+'));
      $body->write($exception->getMessage());
      return $this->response->withStatus(400)->withBody($body);
    }
  }
}
