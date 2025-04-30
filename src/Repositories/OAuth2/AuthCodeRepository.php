<?php

namespace Wanphp\Plugins\Weixin\Repositories\OAuth2;


use Exception;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Wanphp\Plugins\Weixin\Entities\OAuth2\AuthCodeEntity;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
  private CacheItemPoolInterface $storage;

  public function __construct(CacheItemPoolInterface $storage)
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

  /**
   * @throws Exception
   * @throws InvalidArgumentException
   */
  public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
  {
    $data = [
      'type' => 'auth_codes',
      'user_id' => $authCodeEntity->getUserIdentifier(), // 获得用户标识符
      'client_id' => $authCodeEntity->getClient()->getIdentifier(), // 获得客户端标识符
      'scopes' => array_map(function ($scope) {
        return $scope->getIdentifier();
      }, $authCodeEntity->getScopes()) // 获得权限范围
    ];
    $item = $this->storage->getItem($authCodeEntity->getIdentifier());
    $item->set($data)->expiresAfter($authCodeEntity->getExpiryDateTime()->getTimestamp() - time());
    $this->storage->save($item);
  }

  /**
   * @throws Exception
   * @throws InvalidArgumentException
   */
  public function revokeAuthCode($codeId): void
  {
    // 当使用授权码获取访问令牌时调用此方法
    // 可以在此时将授权码从持久化数据库中删除
    // 参数为授权码唯一标识符
    $this->storage->deleteItem($codeId);
  }

  /**
   * @throws Exception
   */
  public function isAuthCodeRevoked($codeId): bool
  {
    // 当使用授权码获取访问令牌时调用此方法
    // 用于验证授权码是否已被删除
    // return true 已删除，false 未删除
    try {
      return !$this->storage->hasItem($codeId);
    } catch (InvalidArgumentException $e) {
      return true;
    }
  }
}
