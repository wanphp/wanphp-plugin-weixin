<?php
declare(strict_types=1);

namespace Wanphp\Plugins\Weixin\Entities;


use JsonSerializable;
use Wanphp\Libray\Mysql\EntityTrait;

/**
 * Class MsgTemplateEntity
 * @package Wanphp\Plugins\Weixin\Entities
 * @OA\Schema(
 *   title="消息模板",
 *   description="微信消息模板",
 *   required={"template_id_short","template_id"}
 * )
 */
class MsgTemplateEntity implements JsonSerializable
{
  use EntityTrait;
  /**
   * @DBType({"key":"PRI","type":"tinyint NOT NULL AUTO_INCREMENT"})
   * @var integer|null
   * @OA\Property(description="ID")
   */
  private $id;
  /**
   * @DBType({"key":"UNI","type":"varchar(30) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="模板消息编号")
   */
  private $template_id_short;
  /**
   * "key":"UNI",
   * @DBType({"type":"varchar(50) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="模板消息ID")
   */
  private $template_id;
  /**
   * @DBType({"type":"tinyint(1) NOT NULL DEFAULT '0'"})
   * @var integer
   * @OA\Property(description="是否可用")
   */
  private $status;
}
