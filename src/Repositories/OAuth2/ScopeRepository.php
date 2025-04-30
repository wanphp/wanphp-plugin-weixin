<?php

namespace Wanphp\Plugins\Weixin\Repositories\OAuth2;


use Exception;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\ClientInterface;
use Wanphp\Plugins\Weixin\Domain\PublicInterface;
use Wanphp\Plugins\Weixin\Entities\OAuth2\ScopeEntity;
use Wanphp\Plugins\Weixin\Entities\OAuth2\ScopesEntity;

class ScopeRepository extends BaseRepository implements ScopeRepositoryInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, 'scopes', ScopesEntity::class);
  }

  /**
   * @param $identifier
   * @return ScopeEntityInterface|null
   * @throws Exception
   */
  public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface
  {
    if (!in_array($identifier, ['ADMIN', 'openid', 'profile'])) {
      $identifier = $this->get('identifier', ['identifier' => $identifier]);
      if (!$identifier) return null;
    }
    $scope = new ScopeEntity();
    $scope->setIdentifier($identifier);

    return $scope;
  }

  /**
   * @throws Exception
   */
  public function finalizeScopes(
    array                 $scopes,
                          $grantType,
    ClientEntityInterface $clientEntity,
                          $userIdentifier = null
  ): array
  {
    $finalScopes = [];
    if (!empty($scopes)) {
      // 有 scopes，就逐个检查是否合法
      $allowedScopes = $this->db->get(ClientInterface::TABLE_NAME, 'scopes[JSON]', ['client_id' => $clientEntity->getIdentifier()]);
      if (!empty($allowedScopes)) foreach ($scopes as $scope) {
        if (in_array($scope->getIdentifier(), $allowedScopes, true)) {
          $finalScopes[] = $scope;
        }
      }
    }

    // 根据用户身份动态追加 scope
    if (!empty($finalScopes)) {
      $userTags = $this->db->get(PublicInterface::TABLE_NAME, ['tagid_list[JSON]'], ['id' => $userIdentifier]);
      if (in_array(100, $userTags, true)) {
        $adminScope = $this->getScopeEntityByIdentifier('ADMIN');
        if ($adminScope) {
          $finalScopes[] = $adminScope;
        }
      }
    }

    return $finalScopes;
  }
}
