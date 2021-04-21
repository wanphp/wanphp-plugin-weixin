<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/16
 * Time: 10:40
 */

namespace Wanphp\Plugins\Weixin\Entities;


use JsonSerializable;
use Wanphp\Libray\Mysql\EntityTrait;

/**
 * Class MiniProgramEntity
 * @package Wanphp\Plugins\Weixin\Entities
 * @OA\Schema(
 *   title="用户小程序关联信息",
 *   description="用户小程序关联信息",
 *   required={"openid","nickname","headimgurl","sex"}
 * )
 */
class MiniProgramEntity implements JsonSerializable
{
  use EntityTrait;
  /**
   * @DBType({"key":"PRI","type":"int NOT NULL"})
   * @var integer|null
   * @OA\Property(format="int64", description="用户ID")
   */
  private $id;
  /**
   * @DBType({"key":"UNI","type":"varchar(29) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="微信openid")
   */
  private $openid;
  /**
   * @DBType({"type":"int NOT NULL DEFAULT '0'"})
   * @var integer
   * @OA\Property(description="推荐用户ID")
   */
  private $parent_id;

  public function jsonSerialize()
  {
    return get_object_vars($this);
  }
}
