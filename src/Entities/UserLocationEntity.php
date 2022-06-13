<?php

namespace Wanphp\Plugins\Weixin\Entities;

use Wanphp\Libray\Mysql\EntityTrait;

/**
 * Class UserLocationEntity
 * @package Wanphp\Plugins\Weixin\Entities
 * @OA\Schema(
 *   title="用户位置上报",
 *   description="用户位置上报"
 * )
 */
class UserLocationEntity implements \JsonSerializable
{
  use EntityTrait;
  /**
   * @DBType({"key":"PRI","type":"int NOT NULL AUTO_INCREMENT"})
   * @var integer|null
   * @OA\Property(format="int64", description="ID")
   */
  private ?int $id;
  /**
   * @DBType({"type":"int NOT NULL DEFAULT 0"})
   * @var string
   * @OA\Property(description="用户ID")
   */
  private string $uid;
  /**
   * @DBType({"type":"varchar(20) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="纬度")
   */
  private string $lat;
  /**
   * @DBType({"type":"varchar(20) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="经度")
   */
  private string $lng;
  /**
   * @DBType({"type":"varchar(20) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="精度")
   */
  private string $precision;
  /**
   * @DBType({"type":"char(10) NOT NULL DEFAULT '0'"})
   * @var string
   * @OA\Property(description="位置上报时间")
   */
  private string $ctime;
}