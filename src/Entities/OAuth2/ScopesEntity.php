<?php

namespace Wanphp\Plugins\Weixin\Entities\OAuth2;

use Wanphp\Libray\Mysql\EntityTrait;

class ScopesEntity implements \JsonSerializable
{
  use EntityTrait;

  /**
   * @DBType({"key":"PRI","type":"smallint(6) NOT NULL AUTO_INCREMENT"})
   * @OA\Property(format="int32", description="ID")
   * @var integer|null
   */
  private ?int $id;
  /**
   * @DBType({"key":"UNI","type":"varchar(20) DEFAULT null"})
   * @OA\Property(description="权限ID")
   * @var string
   */
  private string $identifier;
  /**
   * @DBType({"type":"varchar(10) NOT NULL DEFAULT ''"})
   * @OA\Property(description="名称")
   * @var string
   */
  private string $name;
  /**
   * @DBType({"type":"varchar(100) NOT NULL DEFAULT ''"})
   * @OA\Property(description="权限说明")
   * @var string
   */
  private string $description;
  /**
   * @DBType({"type":"varchar(5000) NOT NULL DEFAULT '[]'"})
   * @OA\Property(@OA\Items(),description="授权路由")
   * @var array
   */
  private array $scopeRules;
}