<?php

namespace Wanphp\Plugins\Weixin\Repositories\OAuth2;


use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Predis\ClientInterface;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\AuthCodeInterface;
use Wanphp\Plugins\Weixin\Entities\OAuth2\RefreshTokenEntity;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
  private ClientInterface|Database $storage;

  public function __construct(ClientInterface|Database $storage)
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

  public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
  {
    // 创建新刷新令牌时调用此方法
    // 用于持久化存储授刷新令牌
    // 可以使用参数中的 RefreshTokenEntityInterface 对象，获得有价值的信息：
    // $refreshTokenEntity->getIdentifier(); // 获得刷新令牌唯一标识符
    // $refreshTokenEntity->getExpiryDateTime(); // 获得刷新令牌过期时间
    // $refreshTokenEntity->getAccessToken()->getIdentifier(); // 获得访问令牌标识符
    $data = [
      'id' => $refreshTokenEntity->getIdentifier(), // 获得刷新令牌唯一标识符
      'type' => 'refresh_token',
      'expires_at' => $refreshTokenEntity->getExpiryDateTime()->getTimestamp() // 获得刷新令牌过期时间
    ];

    if ($this->storage instanceof Database === true) $this->storage->insert(AuthCodeInterface::TABLE_NAME, $data);
    if ($this->storage instanceof ClientInterface === true) $this->storage->setex($data['id'], $data['expires_at'] - time(), json_encode($data));
  }

  public function revokeRefreshToken($tokenId)
  {
    // 当使用刷新令牌获取访问令牌时调用此方法
    // 原刷新令牌将删除，创建新的刷新令牌
    // 参数为原刷新令牌唯一标识
    // 可在此删除原刷新令牌
    if ($this->storage instanceof Database === true) $this->storage->delete(AuthCodeInterface::TABLE_NAME, ['id' => $tokenId]);
    if ($this->storage instanceof ClientInterface === true) $this->storage->del($tokenId);
  }

  public function isRefreshTokenRevoked($tokenId): bool
  {
    // 当使用刷新令牌获取访问令牌时调用此方法
    // 用于验证刷新令牌是否已被删除
    // return true 已删除，false 未删除
    $data = '';
    if ($this->storage instanceof Database === true) $data = $this->storage->get(AuthCodeInterface::TABLE_NAME, ['id'], ['id' => $tokenId]);
    if ($this->storage instanceof ClientInterface === true) $data = $this->storage->get($tokenId);
    return empty($data);
  }
}
