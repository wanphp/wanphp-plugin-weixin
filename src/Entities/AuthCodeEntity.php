<?php

namespace Wanphp\Plugins\Weixin\Entities;

use JsonSerializable;
use Wanphp\Libray\Mysql\EntityTrait;

class AuthCodeEntity implements JsonSerializable
{
  use EntityTrait;

  /**
   * @DBType({"key":"PRI","type":"varchar(100) NOT NULL"})
   * @var string
   * @OA\Property(description="AccessToken")
   */
  private string $id;
  /**
   * @DBType({"key":"MUL","type":"varchar(20) NOT NULL"})
   * @var string
   * @OA\Property(description="类型")
   */
  private string $type;
  /**
   * @DBType({"key":"MUL","type":"varchar(20) NOT NULL"})
   * @var string
   * @OA\Property(description="客户端")
   */
  private string $client_id;
  /**
   * @DBType({"type":"int(11) NOT NULL"})
   * @var string
   * @OA\Property(description="认证用户")
   */
  private string $user_id;
  /**
   * @DBType({"key":"MUL","type":"varchar(50) NOT NULL DEFAULT '[]'"})
   * @var array
   * @OA\Property(@OA\Items(),description="申请权限")
   */
  private array $scopes;
  /**
   * @DBType({"type":"char(10) NOT NULL DEFAULT 0"})
   * @var integer
   * @OA\Property(description="过期时间")
   */
  private int $expires_at;
}
