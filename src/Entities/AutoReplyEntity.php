<?php

namespace Wanphp\Plugins\Weixin\Entities;


use JsonSerializable;
use Wanphp\Libray\Mysql\EntityTrait;

/**
 * Class AutoReplyEntity
 * @package Wanphp\Plugins\Weixin\Entities
 * @OA\Schema(
 *   title="自动回复",
 *   description="公众号自动回复",
 *   schema="autoReply",
 *   required={"key","msgType","msgContent"}
 * )
 */
class AutoReplyEntity implements JsonSerializable
{
  use EntityTrait;

  /**
   * @DBType({"key":"PRI","type":"smallint(6) NOT NULL AUTO_INCREMENT"})
   * @var integer|null
   * @OA\Property(description="ID")
   */
  private ?int $id;
  /**
   * @DBType({"key":"UNI","type":"varchar(200) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="关键词")
   */
  private string $key;
  /**
   * @DBType({"type":"varchar(10) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="接收信息类型")
   */
  private string $msgType;
  /**
   * @DBType({"type":"varchar(10) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="回复内容")
   */
  private string $replyType;
  /**
   * @DBType({"type":"varchar(5000) NOT NULL DEFAULT ''"})
   * @var array
   * @OA\Property(@OA\Items(),description="回复内容")
   */
  private array $msgContent;
}
