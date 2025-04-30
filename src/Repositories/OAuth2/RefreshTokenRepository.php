<?php

namespace Wanphp\Plugins\Weixin\Repositories\OAuth2;


use Exception;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Wanphp\Plugins\Weixin\Entities\OAuth2\RefreshTokenEntity;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
  private CacheItemPoolInterface $storage;

  public function __construct(CacheItemPoolInterface $storage)
  {
    $this->storage = $storage;
  }

  /**
   * @return RefreshTokenEntityInterface
   */
  public function getNewRefreshToken(): RefreshTokenEntityInterface
  {
    return new RefreshTokenEntity();
  }

  /**
   * @throws Exception
   * @throws InvalidArgumentException
   */
  public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
  {
    // 创建新刷新令牌时调用此方法
    // 用于持久化存储授刷新令牌
    // 可以使用参数中的 RefreshTokenEntityInterface 对象，获得有价值的信息：
    // $refreshTokenEntity->getIdentifier(); // 获得刷新令牌唯一标识符
    // $refreshTokenEntity->getExpiryDateTime(); // 获得刷新令牌过期时间
    // $refreshTokenEntity->getAccessToken()->getIdentifier(); // 获得访问令牌标识符
    $data = [
      'type' => 'refresh_token',
      'access_token' => $refreshTokenEntity->getAccessToken()->getIdentifier() // 获得访问令牌标识符
    ];

    $item = $this->storage->getItem($refreshTokenEntity->getIdentifier());
    $item->set($data)->expiresAfter($refreshTokenEntity->getExpiryDateTime()->getTimestamp() - time());
    $this->storage->save($item);
  }

  /**
   * @throws Exception
   * @throws InvalidArgumentException
   */
  public function revokeRefreshToken($tokenId): void
  {
    // 当使用刷新令牌获取访问令牌时调用此方法
    // 原刷新令牌将删除，创建新的刷新令牌
    // 参数为原刷新令牌唯一标识
    // 可在此删除原刷新令牌
    $this->storage->deleteItem($tokenId);
  }

  /**
   * @throws Exception
   */
  public function isRefreshTokenRevoked($tokenId): bool
  {
    // 当使用刷新令牌获取访问令牌时调用此方法
    // 用于验证刷新令牌是否已被删除
    // return true 已删除，false 未删除
    try {
      return !$this->storage->hasItem($tokenId);
    } catch (InvalidArgumentException $e) {
      return true;
    }
  }
}
