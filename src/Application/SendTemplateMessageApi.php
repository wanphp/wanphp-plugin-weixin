<?php

namespace Wanphp\Plugins\Weixin\Application;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Wanphp\Libray\Weixin\WeChatBase;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;

class SendTemplateMessageApi extends Api
{
  private WeChatBase $weChatBase;
  private PublicInterface $public;
  private LoggerInterface $logger;

  public function __construct(
    WeChatBase      $weChatBase,
    PublicInterface $public,
    LoggerInterface $logger
  )
  {
    $this->weChatBase = $weChatBase;
    $this->public = $public;
    $this->logger = $logger;
  }

  /**
   * @inheritDoc
   */
  protected function action(): Response
  {
    $post = $this->request->getParsedBody();
    if (!isset($post['data'])) return $this->respondWithData(['errCode' => '1', 'msg' => '无模板信息内容']);
    //取用户openid
    if (isset($post['users']) && !empty($post['users'])) {
      $openId = $this->public->get('openid', ['id' => $post['users'], 'subscribe' => 1]);
      if ($openId) {
        if (is_string($openId)) $openId = [$openId];

        $ok = 0;
        $msgData = $post['data'];
        foreach ($openId as $openid) {
          $msgData['touser'] = $openid;
          try {
            $this->weChatBase->sendTemplateMessage($msgData);
            $ok++;
          } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
          }
        }
        return $this->respondWithData(['errCode' => '0', 'ok' => $ok]);
      } else {
        return $this->respondWithData(['errCode' => '1', 'msg' => '用户没有关注公众号']);
      }
    } else {
      return $this->respondWithData(['errCode' => '1', 'msg' => '未检测到用户ID']);
    }
  }
}
