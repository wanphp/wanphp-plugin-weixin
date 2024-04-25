<?php

namespace Wanphp\Plugins\Weixin\Repositories\OAuth2;


use Exception;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Wanphp\Libray\Slim\WpUserInterface;
use Wanphp\Plugins\Weixin\Entities\OAuth2\UserEntity;

class UserRepository implements UserRepositoryInterface
{
  private WpUserInterface $user;

  public function __construct(WpUserInterface $user)
  {
    $this->user = $user;
  }

  /**
   * @param string $username
   * @param string $password
   * @param string $grantType
   * @param ClientEntityInterface $clientEntity
   * @return UserEntity|UserEntityInterface|null
   * @throws Exception
   * @throws OAuthServerException
   */
  public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity): UserEntity|UserEntityInterface|null
  {
    // 验证用户时调用此方法
    // 用于验证用户信息是否符合
    // 可以验证是否为用户可使用的授权类型($grantType)与客户端($clientEntity)
    // 验证成功返回 UserEntityInterface 对象

    $res = $this->user->userLogin($username, $password);
    if (is_numeric($res) && $res > 0) {
      $user = new UserEntity();
      $user->setIdentifier($res);
      return $user;
    } else {
      throw new OAuthServerException($res, 3, 'invalid_request', 400);
    }
  }
}
