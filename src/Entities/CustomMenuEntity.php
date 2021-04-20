<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/9
 * Time: 9:03
 */

namespace Wanphp\Plugins\Weixin\Entities;


use JsonSerializable;
use Wanphp\Libray\Mysql\EntityTrait;

/**
 * Class CustomMenuEntity
 * @package Wanphp\Plugins\Weixin\Entities
 * @OA\Schema(schema="NewCustomMenu", required={"name","type"})
 */
class CustomMenuEntity implements JsonSerializable
{
  use EntityTrait;
  /**
   * @DBType({"key":"PRI","type":"smallint(6) NOT NULL AUTO_INCREMENT"})
   * @var integer|null
   */
  private $id;
  /**
   * @DBType({"key":"MUL","type":"smallint(6) NOT NULL DEFAULT 0"})
   * @var string
   * @OA\Property(description="微信标签")
   */
  private $tag_id;
  /**
   * @DBType({"type":"smallint(6) NOT NULL DEFAULT 0"})
   * @var integer
   * @OA\Property(description="上级菜单ID")
   */
  private $parent_id;
  /**
   * @DBType({"type":"varchar(20) NOT NULL DEFAULT ''"})
   * @var integer
   * @OA\Property(description="菜单名")
   */
  private $name;
  /**
   * @DBType({"type":"varchar(20) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="事件类型")
   */
  private $type;
  /**
   * @DBType({"type":"varchar(50) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="click等点击类型必须；菜单KEY值，用于消息接口推送")
   */
  private $key;
  /**
   * @DBType({"type":"varchar(100) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="view、miniprogram类型必须，网页 链接，用户点击菜单可打开链接，不超过1024字节。 type为miniprogram时，不支持小程序的老版本客户端将打开本url。")
   */
  private $url;
  /**
   * @DBType({"type":"varchar(20) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="miniprogram类型必须；小程序的appid（仅认证公众号可配置）")
   */
  private $appid;
  /**
   * @DBType({"type":"varchar(50) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="miniprogram类型必须；小程序的页面路径")
   */
  private $pagepath;
  /**
   * @DBType({"type":"tinyint(2) NOT NULL DEFAULT 0"})
   * @var integer
   * @OA\Property(description="排序")
   */
  private $sort_order;

  public function jsonSerialize()
  {
    return get_object_vars($this);
  }
}

/**
 * @OA\Schema(
 *   schema="CustomMenu",
 *   type="object",
 *   allOf={
 *       @OA\Schema(ref="#/components/schemas/NewCustomMenu"),
 *       @OA\Schema(
 *           required={"id"},
 *           @OA\Property(property="id",format="int64", type="integer", description="菜单ID")
 *       )
 *   }
 * )
 */
