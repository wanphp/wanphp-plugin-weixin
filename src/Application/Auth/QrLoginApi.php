<?php

namespace Wanphp\Plugins\Weixin\Application\Auth;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Libray\Slim\Setting;
use Wanphp\Libray\Weixin\WeChatBase;
use Wanphp\Plugins\Weixin\Application\Api;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

class QrLoginApi extends Api
{
  private WeChatBase $weChatBase;
  private PublicInterface $public;
  private UserInterface $user;
  private Key $encryptionKey;

  /**
   * @param UserInterface $user
   * @param Setting $setting
   * @param PublicInterface $public
   * @param WeChatBase $weChatBase
   * @throws BadFormatException
   * @throws EnvironmentIsBrokenException
   */
  public function __construct(UserInterface $user, Setting $setting, PublicInterface $public, WeChatBase $weChatBase)
  {
    $this->user = $user;
    $this->public = $public;
    $this->weChatBase = $weChatBase;
    $this->encryptionKey = Key::loadFromAsciiSafeString($setting->get('oauth2Config')['encryptionKey']);
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
      // 检查cookie中是否有记录用户信息，
      // 可能存在问题，用户退出微信后登录其它微信，可能取到的还是前一个微信用户的id，
      // 只给没有网页授权获取用户基本信息的公众号使用
      if (isset($_COOKIE['u_code'])) {
        $user_id = Crypto::decrypt($_COOKIE['u_code'], $this->encryptionKey);
        if ($user_id > 0) return $this->showLoginInfo($user_id);
      }
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

          if ($user_id > 0) return $this->showLoginInfo($user_id);
          else return $this->respondWithError('未知用户！');
        } else {
          return $this->respondWithError('授权验证失败！');
        }
      } else {
        return $this->user->oauthRedirect($this->request, $this->response);
      }
    }
  }

  /**
   * @throws Exception
   */
  private function showLoginInfo($user_id): Response
  {
    $_SESSION['login_user_id'] = $user_id;
    // 检查用户是否已关注公众号
    $subscribe = $this->public->get('subscribe', ['id' => $user_id]);
    if ($subscribe == 0 && $this->weChatBase->uin_base64) { // 未关注
      $url = 'https://mp.weixin.qq.com/mp/profile_ext?action=home&__biz=' . $this->weChatBase->uin_base64 . '&scene=124#wechat_redirect';
      $subscribeHtml = '<br><a href="' . $url . '" class="weui-btn weui-btn_primary">关注我们</a><br>';
    }
      $data = ['title' => '登录成功',
        'msg' => '您已成功授权，详情查看PC端扫码页面！' . ($subscribeHtml ?? ''),
        'icon' => 'weui-icon-success'
      ];
      return $this->respondView('admin/error/wxerror.html', $data);
    }
  }
