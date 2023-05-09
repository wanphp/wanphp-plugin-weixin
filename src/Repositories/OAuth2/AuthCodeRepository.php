<?php

namespace Wanphp\Plugins\Weixin\Repositories\OAuth2;


use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Predis\ClientInterface;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\AuthCodeInterface;
use Wanphp\Plugins\Weixin\Entities\OAuth2\AuthCodeEntity;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
  private ClientInterface|Database $storage;

  public function __construct(ClientInterface|Database $storage)
  {
    $this->storage = $storage;
  }

  /**
   * @return AuthCodeEntityInterface
   */
  public function getNewAuthCode(): AuthCodeEntityInterface
  {
    return new AuthCodeEntity();
  }

  public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
  {
    $data = [
      'id' => $authCodeEntity->getIdentifier(), // 获得授权码唯一标识符
      'type' => 'auth_codes',
      'user_id' => $authCodeEntity->getUserIdentifier(), // 获得用户标识符
      'client_id' => $authCodeEntity->getClient()->getIdentifier(), // 获得客户端标识符
      'scopes[JSON]' => $authCodeEntity->getScopes(), // 获得权限范围
      'expires_at' => $authCodeEntity->getExpiryDateTime()->getTimestamp() // 获得授权码过期时间
    ];

    if ($this->storage instanceof Database === true) $this->storage->insert(AuthCodeInterface::TABLE_NAME, $data);
    if ($this->storage instanceof ClientInterface === true) $this->storage->setex($data['id'], $data['expires_at'] - time(), json_encode($data));
  }

  public function revokeAuthCode($codeId)
  {
    // 当使用授权码获取访问令牌时调用此方法
    // 可以在此时将授权码从持久化数据库中删除
    // 参数为授权码唯一标识符
    if ($this->storage instanceof Database === true) $this->storage->delete(AuthCodeInterface::TABLE_NAME, ['id' => $codeId]);
    if ($this->storage instanceof ClientInterface === true) $this->storage->del($codeId);
  }

  public function isAuthCodeRevoked($codeId): bool
  {
    // 当使用授权码获取访问令牌时调用此方法
    // 用于验证授权码是否已被删除
    // return true 已删除，false 未删除
    $data = '';
    if ($this->storage instanceof Database === true) $data = $this->storage->get(AuthCodeInterface::TABLE_NAME, ['id'], ['id' => $codeId]);
    if ($this->storage instanceof ClientInterface === true) $data = $this->storage->get($codeId);
    return empty($data);
  }
}
