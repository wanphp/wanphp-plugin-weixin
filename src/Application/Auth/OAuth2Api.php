<?php

namespace Wanphp\Plugins\Weixin\Application\Auth;


use DateInterval;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Exception;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Libray\Slim\Setting;
use Wanphp\Libray\Slim\WpUserInterface;
use Wanphp\Plugins\Weixin\Application\Api;
use Wanphp\Plugins\Weixin\Domain\AuthCodeStorageInterface;
use Wanphp\Plugins\Weixin\Repositories\OAuth2\AccessTokenRepository;
use Wanphp\Plugins\Weixin\Repositories\OAuth2\AuthCodeRepository;
use Wanphp\Plugins\Weixin\Repositories\OAuth2\ClientRepository;
use Wanphp\Plugins\Weixin\Repositories\OAuth2\RefreshTokenRepository;
use Wanphp\Plugins\Weixin\Repositories\OAuth2\ScopeRepository;
use Wanphp\Plugins\Weixin\Repositories\OAuth2\UserRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Slim\Exception\HttpNotFoundException;

abstract class OAuth2Api extends Api
{
  protected AuthorizationServer $server;
  protected Database $database;
  protected AuthCodeStorageInterface $storage;
  protected WpUserInterface $user;
  protected Key $encryptionKey;

  /**
   * @param Database $database
   * @param AuthCodeStorageInterface $storage
   * @param Setting $setting
   * @param WpUserInterface $user
   * @throws BadFormatException
   * @throws EnvironmentIsBrokenException
   * @throws Exception
   */
  public function __construct(Database $database, AuthCodeStorageInterface $storage, Setting $setting, WpUserInterface $user)
  {
    $this->database = $database;
    $this->storage = $storage;
    $config = $setting->get('oauth2Config');

    $this->user = $user;

    // 初始化存储库
    $clientRepository = new ClientRepository($this->database);
    $scopeRepository = new ScopeRepository();
    $accessTokenRepository = new AccessTokenRepository($this->storage);

    // 私钥与加密密钥
    $privateKey = new CryptKey($config['privateKey'], $config['privateKeyPass'] ?: null); // 如果私钥文件有密码
    $this->encryptionKey = Key::loadFromAsciiSafeString($config['encryptionKey']); //如果通过 generate-defuse-key 脚本生成的字符串，可使用此方法传入

    // 初始化 server
    $this->server = new AuthorizationServer(
      $clientRepository,
      $accessTokenRepository,
      $scopeRepository,
      $privateKey,
      $this->encryptionKey
    );
  }

  protected function implicit()
  {
    $this->server->enableGrantType(
      new ImplicitGrant(new DateInterval('P1D')),
      new DateInterval('P1D') // 设置授权码过期时间为1天
    );
  }

  /**
   * @throws HttpNotFoundException
   */
  protected function authorization_code()
  {
    // 授权码授权类型初始化
    $authCodeRepository = new AuthCodeRepository($this->storage);
    $refreshTokenRepository = new RefreshTokenRepository($this->storage);
    try {
      $grant = new AuthCodeGrant(
        $authCodeRepository,
        $refreshTokenRepository,
        new DateInterval('PT10M') // 设置授权码过期时间为10分钟
      );
    } catch (Exception $e) {
      throw new HttpNotFoundException($this->request, $e->getMessage());
    }

    $grant->setRefreshTokenTTL(new DateInterval('P1M')); // 设置刷新令牌过期时间1个月

    // 将授权码授权类型添加进 server
    $this->server->enableGrantType(
      $grant,
      new DateInterval('PT1H') // 设置访问令牌过期时间1小时
    );
  }

  protected function client_credentials()
  {
    $this->server->enableGrantType(
      new ClientCredentialsGrant(),
      new DateInterval('PT1H') // access tokens will expire after 1 hour
    );
  }

  protected function password()
  {
    $userRepository = new UserRepository($this->user);
    $refreshTokenRepository = new RefreshTokenRepository($this->storage);

    $grant = new PasswordGrant(
      $userRepository,
      $refreshTokenRepository
    );

    $grant->setRefreshTokenTTL(new DateInterval('PT2H')); //两个小时过期 refresh tokens will expire after 1 month P1M

    $this->server->enableGrantType(
      $grant,
      new DateInterval('PT1H') // access tokens will expire after 1 hour
    );
  }

  protected function refresh_token()
  {
    $refreshTokenRepository = new RefreshTokenRepository($this->storage);
    $grant = new RefreshTokenGrant($refreshTokenRepository);
    $grant->setRefreshTokenTTL(new DateInterval('P1M')); // new refresh tokens will expire after 1 month

    $this->server->enableGrantType(
      $grant,
      new DateInterval('PT1H') // new access tokens will expire after an hour
    );
  }
}
