<?php

namespace Wanphp\Plugins\Weixin\Application\Auth;


use DateInterval;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Exception;
use Psr\SimpleCache\CacheInterface;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Libray\Slim\Setting;
use Wanphp\Libray\Slim\WpUserInterface;
use Wanphp\Plugins\Weixin\Application\Api;
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
  protected CacheInterface $storage;
  protected WpUserInterface $user;
  protected Key $encryptionKey;
  protected string $uin_base64; // 公众号的唯一ID
  protected bool $webAuthorization; // 公众号是否有网页授权获取用户基本信息权限
  protected string $basePath;

  /**
   * @param Database $database
   * @param Setting $setting
   * @param ClientRepository $clientRepository
   * @param WpUserInterface $user
   * @throws BadFormatException
   * @throws EnvironmentIsBrokenException
   * @throws \Psr\SimpleCache\InvalidArgumentException
   */
  public function __construct(
    Database         $database,
    Setting          $setting,
    ClientRepository $clientRepository,
    WpUserInterface  $user
  )
  {
    $this->database = $database;
    $config = $setting->get('oauth2Config');
    $options = $setting->get('wechat.base');
    $this->uin_base64 = $options['uin_base64'] ?? '';
    $this->webAuthorization = $options['webAuthorization'] ?? true;
    $this->basePath = $setting->get('basePath');
    $this->storage = $setting->get('AuthCodeStorage');

    $this->user = $user;
    // 缓存Scopes
    $scopes = $this->storage->get('scopes');
    if (empty($scopes)) {
      $scopes = $database->select('scopes', ['identifier', 'scopeRules[JSON]']);
      $pathScope = [];
      foreach ($scopes as $scope) {
        foreach ($scope['scopeRules'] as $scopeRule) {
          if (preg_match('/^(.*)\[(.*)\]$/', $scopeRule, $matches)) {
            $path = $matches[1];
            $request = $matches[2];
          } else {
            $path = $scopeRule;
            $request = 777;
          }
          $rule_md5 = md5($path);
          if (isset($pathScope[$rule_md5])) {
            $pathScope[$rule_md5]['scopes'][] = $scope['identifier'];
            $a = str_split(str_pad((string)$request, 3, '0', STR_PAD_LEFT));
            $b = str_split(str_pad((string)$pathScope[$rule_md5]['request'], 3, '0', STR_PAD_LEFT));
            $result = '';
            for ($i = 0; $i < 3; $i++) {
              $result .= max($a[$i], $b[$i]);
            }
            $pathScope[$rule_md5]['request'] = $result;
          } else $pathScope[$rule_md5] = ['scopes' => [$scope['identifier']], 'request' => $request];
        }
      }
      $this->storage->set('scopes', $pathScope);
    }

    // 初始化存储库
    $scopeRepository = new ScopeRepository($database);
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

  protected function implicit(): void
  {
    $this->server->enableGrantType(
      new ImplicitGrant(new DateInterval('P1D')),
      new DateInterval('P1D') // 设置授权码过期时间为1天
    );
  }

  /**
   * @throws HttpNotFoundException
   */
  protected function authorization_code(): void
  {
    // 授权码授权类型初始化
    $authCodeRepository = new AuthCodeRepository($this->storage);
    $refreshTokenRepository = new RefreshTokenRepository($this->storage);
    try {
      $grant = new AuthCodeGrant(
        $authCodeRepository,
        $refreshTokenRepository,
        new DateInterval('PT1M') // 设置授权码过期时间为1分钟
      );
    } catch (Exception $e) {
      throw new HttpNotFoundException($this->request, $e->getMessage());
    }

    $grant->setRefreshTokenTTL(new DateInterval('PT2H')); // 设置刷新令牌过期时间2小时

    // 将授权码授权类型添加进 server
    $this->server->enableGrantType(
      $grant,
      new DateInterval('PT1H') // 设置访问令牌过期时间1小时
    );
  }

  protected function client_credentials(): void
  {
    $this->server->enableGrantType(
      new ClientCredentialsGrant(),
      new DateInterval('PT1H') // 设置访问令牌过期时间1小时
    );
  }

  protected function password(): void
  {
    $userRepository = new UserRepository($this->user);
    $refreshTokenRepository = new RefreshTokenRepository($this->storage);

    $grant = new PasswordGrant(
      $userRepository,
      $refreshTokenRepository
    );

    $grant->setRefreshTokenTTL(new DateInterval('PT2H')); // 设置刷新令牌过期时间2小时

    $this->server->enableGrantType(
      $grant,
      new DateInterval('PT1H') // 设置访问令牌过期时间1小时
    );
  }

  protected function refresh_token(): void
  {
    $refreshTokenRepository = new RefreshTokenRepository($this->storage);
    $grant = new RefreshTokenGrant($refreshTokenRepository);
    $grant->setRefreshTokenTTL(new DateInterval('PT2H')); // 设置刷新令牌过期时间2小时

    $this->server->enableGrantType(
      $grant,
      new DateInterval('PT1H') // 设置访问令牌过期时间1小时
    );
  }
}
