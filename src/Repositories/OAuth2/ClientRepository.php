<?php

namespace Wanphp\Plugins\Weixin\Repositories\OAuth2;


use Exception;
use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Wanphp\Plugins\Weixin\Entities\OAuth2\ClientEntity;
use Wanphp\Plugins\Weixin\Entities\OAuth2\ClientsEntity;

class ClientRepository extends BaseRepository implements ClientRepositoryInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, 'clients', ClientsEntity::class);
  }

  /**
   * 获取客户端对象时调用方法，用于验证客户端
   * @param string $clientIdentifier
   * @return ClientEntity|ClientEntityInterface|null
   * @throws Exception
   */
  public function getClientEntity($clientIdentifier): ClientEntityInterface|ClientEntity|null
  {
    $client = $this->get('client_id,name,redirect_uri,confidential', ['client_id' => $clientIdentifier]);
    if (!$client) return null;
    if (isset($_GET['redirect_uri'])) $redirect_uri = $_GET['redirect_uri'];
    if (isset($_SESSION['authQueryParams']['redirect_uri'])) $redirect_uri = $_SESSION['authQueryParams']['redirect_uri'];
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $post = json_decode(file_get_contents('php://input'), true) ?: $_POST;
      if (isset($post['redirect_uri'])) $redirect_uri = $post['redirect_uri'];
    }
    if (isset($redirect_uri) && $redirect_uri) {
      if (str_contains($client['redirect_uri'], ',')) {
        foreach (explode(',', $client['redirect_uri']) as $uri) {
          if (str_starts_with($redirect_uri, $uri)) {
            $client['redirect_uri'] = $redirect_uri;
            break;
          }
        }
      } else {
        if (str_starts_with($redirect_uri, $client['redirect_uri'])) $client['redirect_uri'] = $redirect_uri;
      }
    }
    return new ClientEntity($client);
  }

  /**
   * @param string $clientIdentifier 客户端ID
   * @param string|null $clientSecret 客户端密钥
   * @param string|null $grantType 授权类型
   * @return bool
   * @throws Exception
   */
  public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
  {
    $client_secret = $this->get('client_secret', ['client_id' => $clientIdentifier]);
    if (!$client_secret) return false;
    if (!empty($clientSecret) && $client_secret !== $clientSecret) return false;
    if (!in_array($grantType, ['authorization_code', 'client_credentials', 'password', 'refresh_token'])) return false;
    return true;
  }
}
