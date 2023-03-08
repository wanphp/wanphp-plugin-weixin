<?php

namespace Wanphp\Plugins\Weixin\Application\Auth;

use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Libray\Weixin\WeChatBase;
use Wanphp\Plugins\Weixin\Application\Api;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

class QrLoginApi extends Api
{
  private WeChatBase $weChatBase;
  private PublicInterface $public;
  private UserInterface $user;

  public function __construct(UserInterface $user, PublicInterface $public, WeChatBase $weChatBase)
  {
    $this->user = $user;
    $this->public = $public;
    $this->weChatBase = $weChatBase;
  }

  /**
   * @inheritDoc
   */
  protected function action(): Response
  {
    if ($this->request->getMethod() == 'POST') {
      if (isset($_SESSION['login_user_id']) && is_numeric($_SESSION['login_user_id'])) return $this->respondWithData(['res' => 'OK']);
      else return $this->respondWithError('尚未授权！');
    } else {
      $queryParams = $this->request->getQueryParams();
      if (isset($queryParams['code'])) {//微信公众号认证回调
        $access_token = $this->user->getOauthAccessToken($queryParams['code'], '');
        if ($access_token) {
          $user = $this->user->getOauthUserinfo($access_token);
          $user_id = $user['id'];
          $status = $this->user->get('status', ['id' => $user_id]);
          if ($status) {
            return $this->respondWithError('帐号已被锁定,无法认证，请联系管理员！');
          }

          // 检查绑定管理员
          if ($user_id > 0) {
            $_SESSION['login_user_id'] = $user_id;
            // 检查用户是否已关注公众号
            $subscribe = $this->public->get('subscribe', ['id' => $user_id]);
            if ($subscribe == 0) { // 未关注
              // 生成公众号二维码
              $qr = $this->weChatBase->getQRCode('qrLogin', 2, 300);//有效期5分钟
              $qrImage = $this->weChatBase->getQRUrl($qr['ticket']);
              $subscribeHtml = '<br><img src="' . $qrImage . '" style="width:100%" alt="公众号二维码"><br>识别二维码关注我们';
            }
            $data = ['title' => '登录成功',
              'msg' => '您已成功授权，详情查看PC端扫码页面！' . ($subscribeHtml ?? ''),
              'icon' => 'weui-icon-success'
            ];
            return $this->respondView('admin/error/wxerror.html', $data);
          } else {
            return $this->respondWithError('未知用户！');
          }
        } else {
          return $this->respondWithError('授权验证失败！');
        }
      } else {
        return $this->user->oauthRedirect($this->request, $this->response);
      }
    }
  }
}
