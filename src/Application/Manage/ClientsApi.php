<?php

namespace Wanphp\Plugins\Weixin\Application\Manage;

use Psr\Http\Message\ResponseInterface as Response;
use Wanphp\Libray\Mysql\Database;
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
  private Database $db;

  /**
   * @param ClientRepository $client
   * ContainerInterface $container,
   */
  public function __construct(Database $db, ClientRepository $client)
  {
    $this->client = $client;
    $this->db = $db;
  }

  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case  'POST';
        $data = $this->request->getParsedBody();
        $client_secret = md5(uniqid(rand(), true));
        $data['client_secret'] = password_hash($client_secret, PASSWORD_BCRYPT);
        $data['client_ip'] = explode(',', $data['client_ip']);
        $data['id'] = $this->client->insert($data);
        $data['client_secret'] = $client_secret;
        return $this->respondWithData($data, 201);
      case  'PUT';
        $data = $this->request->getParsedBody();
        $data['client_ip'] = explode(',', $data['client_ip']);
        $num = $this->client->update($data, ['id' => $this->args['id']]);
        return $this->respondWithData(['upNum' => $num], 201);
      case 'PATCH';
        $client_secret = md5(uniqid(rand(), true));
        $this->client->update(['client_secret' => password_hash($client_secret, PASSWORD_BCRYPT)], ['id' => $this->args['id']]);
        $data = $this->client->get('id,name,client_id,redirect_uri,client_ip[JSON],scopes[JSON],confidential', ['id' => $this->args['id']]);
        $data['client_secret'] = $client_secret;
        return $data;
      case  'DELETE';
        $delNum = $this->client->delete(['id' => $this->args['id']]);
        return $this->respondWithData(['delNum' => $delNum]);
      case 'GET';
        if ($this->request->getHeaderLine("X-Requested-With") == "XMLHttpRequest") {
          return $this->respondWithData(['data' => $this->client->select('id,name,client_id,redirect_uri,client_ip[JSON],scopes[JSON],confidential')]);
        } else {
          $data = [
            'title' => '客户端管理',
            'scopes' => $this->db->select('scopes', ['identifier', 'name'])
          ];

          return $this->respondView('@weixin/clients.html', $data);
        }
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }

}
