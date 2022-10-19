<?php

namespace Wanphp\Plugins\Weixin\Application;

use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Domain\UserInterface;

class SendTemplateMessageApi extends Api
{
  private UserInterface $user;

  public function __construct(UserInterface $user)
  {
    $this->user = $user;
  }

  /**
   * @inheritDoc
   */
  protected function action(): Response
  {
    $post = $this->request->getParsedBody();
    if (!isset($post['users'])) return $this->respondWithData(['errCode' => '1', 'msg' => '未检测到用户ID']);
    if (!isset($post['data'])) return $this->respondWithData(['errCode' => '1', 'msg' => '无模板信息内容']);
    return $this->respondWithData($this->user->sendMessage($post['users'], $post['data']));
  }
}
