<?php

namespace Wanphp\Plugins\Weixin\Application\Manage;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Wanphp\Plugins\Weixin\Domain\AutoReplyInterface;
use Wanphp\Plugins\Weixin\Domain\CustomMenuInterface;

/**
 * Class AutoReplyApi
 * @title 自动回复管理
 * @route /admin/weixin/autoReply
 * @package Wanphp\Plugins\Weixin\Application\Manage
 */
class AutoReplyApi extends \Wanphp\Plugins\Weixin\Application\Api
{

  private AutoReplyInterface $autoReply;
  private CustomMenuInterface $customMenu;

  /**
   * @param AutoReplyInterface $autoReply
   * @param CustomMenuInterface $customMenu
   */
  public function __construct(AutoReplyInterface $autoReply, CustomMenuInterface $customMenu)
  {
    $this->autoReply = $autoReply;
    $this->customMenu = $customMenu;
  }

  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case  'POST';
        $data = $this->getFormData();
        // 检查关键词是否已被使用
        if ($this->autoReply->get('id', ['key' => $data['key']])) return $this->respondWithError('关键词已被添加过,重新换一个');

        $data = $this->getData($data);
        $data['id'] = $this->autoReply->insert($data);
        return $this->respondWithData($data, 201);
      case  'PUT';
        $data = $this->getFormData();
        // 检查关键词是否已被使用
        if ($this->autoReply->get('id', ['key' => $data['key'], 'id[!]' => $this->resolveArg('id')])) return $this->respondWithError('关键词已被添加过,重新换一个');
        $data = $this->getData($data);
        return $this->respondWithData(['upNum' => $this->autoReply->update($data, ['id' => $this->resolveArg('id')])], 201);
      case  'DELETE';
        return $this->respondWithData(['delNum' => $this->autoReply->delete(['id' => $this->resolveArg('id')])]);
      case 'GET';
        if ($this->request->getHeaderLine("X-Requested-With") == "XMLHttpRequest") {
          $params = $this->request->getQueryParams();
          $where = [];
          if (!empty($params['search']['value'])) {
            $keyword = trim($params['search']['value']);
            $keyword = addcslashes($keyword, '*%_');
            $where['account[~]'] = $keyword;
          }

          $recordsFiltered = $this->autoReply->count('id', $where);
          $order = $this->getOrder();
          if ($order) $where['ORDER'] = $order;
          $limit = $this->getLimit();
          if ($limit) $where['LIMIT'] = $limit;

          $data = [
            "draw" => $params['draw'] ?? '',
            "recordsTotal" => $this->autoReply->count('id'),
            "recordsFiltered" => $recordsFiltered,
            'data' => $this->autoReply->select('id,key,msgType,replyType,msgContent[JSON]', $where)
          ];

          return $this->respondWithData($data);
        } else {
          $data = [
            'title' => '自动回复管理'
          ];

          return $this->respondView('@weixin/autoReply.html', $data);
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }

  /**
   * 取菜单自定义菜单事件
   * @throws Exception
   */
  public function getEvent(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $type = $this->resolveArg('type');
    if ($type == 'click') return $this->respondWithData($this->customMenu->select('name,key', ['type' => $type, 'key[!]' => '']));
    if ($type == 'view') return $this->respondWithData($this->customMenu->select('name,url(key)', ['type' => $type, 'url[!]' => '']));
    return $this->respondWithError('不支持事件');
  }

  /**
   * @param array $data
   * @return array
   */
  private function getData(array $data): array
  {
    if ($data['replyType'] == 'media') {// 回复素材
      if (isset($data['msgContent']['Image'])) $data['replyType'] = 'image';
      if (isset($data['msgContent']['Voice'])) $data['replyType'] = 'voice';
      if (isset($data['msgContent']['Video'])) $data['replyType'] = 'video';
    }
    return $data;
  }

}
