<?php

namespace Wanphp\Plugins\Weixin\Repositories\OAuth2;


use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Predis\ClientInterface;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\AuthCodeInterface;
use Wanphp\Plugins\Weixin\Entities\OAuth2\AccessTokenEntity;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
  private ClientInterface|Database $storage;

  public function __construct(ClientInterface|Database $storage)
  {
    $this->storage = $storage;
  }


  /**
   * @param ClientEntityInterface $clientEntity
   * @param array $scopes
   * @param null $userIdentifier
   * @return AccessTokenEntityInterface
   */
  public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessTokenEntityInterface
  {
    // 创建新访问令牌时调用方法
    // 需要返回 AccessTokenEntityInterface 对象
    // 需要在返回前，向 AccessTokenEntity 传入参数中对应属性
    // 示例代码：
    $accessToken = new AccessTokenEntity();
    $accessToken->setClient($clientEntity);
    foreach ($scopes as $scope) {
      $accessToken->addScope($scope);
    }
    $accessToken->setUserIdentifier($userIdentifier);

    return $accessToken;
  }

  public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
  {
    $data = [
      'id' => $accessTokenEntity->getIdentifier(), // 获得令牌唯一标识符
      'type' => 'access_token',
      'client_id' => $accessTokenEntity->getClient()->getIdentifier(), // 获得客户端标识符
      'user_id' => $accessTokenEntity->getUserIdentifier(), // 获得用户标识符
      'scopes[JSON]' => $accessTokenEntity->getScopes(), // 获得权限范围
      'expires_at' => $accessTokenEntity->getExpiryDateTime()->getTimestamp() // 获得令牌过期时间
    ];

    if ($this->storage instanceof Database === true) $this->storage->insert(AuthCodeInterface::TABLE_NAME, $data);
    if ($this->storage instanceof ClientInterface === true) $this->storage->setex($data['id'], $data['expires_at'] - time(), json_encode($data));
  }

  public function revokeAccessToken($tokenId)
  {
    // 使用刷新令牌创建新的访问令牌时调用此方法
    // 参数为原访问令牌的唯一标识符
    // 可将其在持久化存储中过期
    if ($this->storage instanceof Database === true) $this->storage->delete(AuthCodeInterface::TABLE_NAME, ['id' => $tokenId]);
    if ($this->storage instanceof ClientInterface === true) $this->storage->del($tokenId);
  }

  public function isAccessTokenRevoked($tokenId): bool
  {
    // 资源服务器验证访问令牌时将调用此方法
    // 用于验证访问令牌是否已被删除
    // return true 已删除，false 未删除
    $data = '';
    if ($this->storage instanceof Database === true) $data = $this->storage->get(AuthCodeInterface::TABLE_NAME, ['id'], ['id' => $tokenId]);
    if ($this->storage instanceof ClientInterface === true) $data = $this->storage->get($tokenId);
    return empty($data);
  }

}
