<?php
/**
 * 公众号
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/16
 * Time: 10:19
 */

namespace Wanphp\Plugins\Weixin\Entities;


use JsonSerializable;
use Wanphp\Libray\Mysql\EntityTrait;

/**
 * Class PublicEntity
 * @package Wanphp\Plugins\Weixin\Entities
 * @OA\Schema(
 *   title="用户公众号关联信息",
 *   description="用户公众号关联信息",
 *   required={"openid","nickname","headimgurl","sex"}
 * )
 */
class PublicEntity implements JsonSerializable
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
   * @DBType({"key":"MUL","type":"varchar(50) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="粉丝标签")
   */
  private $tagid_list;
  /**
   * @DBType({"type":"int NOT NULL DEFAULT '0'"})
   * @var integer
   * @OA\Property(description="推荐用户ID")
   */
  private $parent_id;
  /**
   * @DBType({"type":"tinyint(1) NOT NULL DEFAULT '0'"})
   * @var integer
   * @OA\Property(description="是否关注公众号")
   */
  private $subscribe;
  /**
   * @DBType({"type":"char(10) NOT NULL DEFAULT '0'"})
   * @var int
   * @OA\Property(description="关注公众号时间")
   */
  private $subscribe_time;
  /**
   * @DBType({"type":"char(10) NOT NULL DEFAULT '0'"})
   * @var string
   * @OA\Property(description="取消关注公众号时间")
   */
  private $unsubscribe_time;
  /**
   * @DBType({"type":"varchar(30) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="用户关注公众号的渠道来源")
   */
  private $subscribe_scene;
  /**
   * @DBType({"type":"char(10) NOT NULL DEFAULT '0'"})
   * @var string
   * @OA\Property(description="最后来访时间，若在48小时内可以发客服信息")
   */
  private $lastop_time;
}
