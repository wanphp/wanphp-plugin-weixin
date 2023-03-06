<?php

namespace Wanphp\Plugins\Weixin\Application\Manage;

use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Plugins\Weixin\Repositories\OAuth2\ClientRepository;

/**
 * Class ClientsApi
 * @title 客户端管理
 * @route /admin/clients
 * @package Wanphp\Plugins\Weixin\Application\Manage
 */
class ClientsApi extends \Wanphp\Plugins\Weixin\Application\Api
{

  private ClientRepository $client;

  /**
   * @param ClientRepository $client
   * ContainerInterface $container,
   */
  public function __construct(ClientRepository $client)
  {
    $this->client = $client;
  }

  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case  'POST';
        $data = $this->request->getParsedBody();
        $data['client_secret'] = md5(uniqid(rand(), true));
        $data['client_ip'] = explode(',', $data['client_ip']);
        $data['id'] = $this->client->insert($data);
        return $this->respondWithData($data, 201);
      case  'PUT';
        $data = $this->request->getParsedBody();
        $data['client_ip'] = explode(',', $data['client_ip']);
        $num = $this->client->update($data, ['id' => $this->args['id']]);
        return $this->respondWithData(['upNum' => $num], 201);
      case  'DELETE';
        $delNum = $this->client->delete(['id' => $this->args['id']]);
        return $this->respondWithData(['delNum' => $delNum]);
      case 'GET';
        if ($this->request->getHeaderLine("X-Requested-With") == "XMLHttpRequest") {
          return $this->respondWithData(['data' => $this->client->select('id,name,client_id,client_secret,redirect_uri,client_ip[JSON],scopes[JSON],confidential')]);
        } else {
          $data = [
            'title' => '客户端管理'
          ];

          return $this->respondView('@oauth2-authorization/clients.html', $data);
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }

}
