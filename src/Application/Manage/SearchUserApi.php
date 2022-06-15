<?php

namespace Wanphp\Plugins\Weixin\Application\Manage;

use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Libray\Weixin\User\UserInterface;
use Wanphp\Plugins\Weixin\Application\Api;

class SearchUserApi extends Api
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
    $where = [];
    $params = $this->request->getQueryParams();
    if (isset($params['q']) && $params['q'] != '') {
      $keyword = trim($params['q']);
      $where['OR'] = [
        'name[~]' => $keyword,
        'nickname[~]' => $keyword,
        'tel[~]' => $keyword
      ];
    }
    $page = (intval($params['page'] ?? 0) - 1) * 10;
    $where['LIMIT'] = [$page, 10];

    $data = [
      'users' => $this->user->select('id,nickname,headimgurl,name,tel', $where),
      'total' => $this->user->count('id', $where)
    ];
    return $this->respondWithData($data);
  }
}