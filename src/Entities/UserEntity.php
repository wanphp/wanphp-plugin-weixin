<?php
declare(strict_types=1);

namespace Wanphp\Plugins\Weixin\Entities;


use JsonSerializable;
use Wanphp\Libray\Mysql\EntityTrait;

/**
 * Class UserEntity
 * @package Wanphp\Plugins\Weixin\Entities
 * @OA\Schema(
 *   title="用户",
 *   description="用户数据结构",
 *   required={"nickname","headimgurl","sex"}
 * )
 */
class UserEntity implements JsonSerializable
{
  use EntityTrait;

  /**
   * @DBType({"key":"PRI","type":"int NOT NULL AUTO_INCREMENT"})
   * @var integer|null
   * @OA\Property(format="int64", description="用户ID")
   */
  protected ?int $id;
  /**
   * @DBType({"key":"UNI","type":"varchar(29) NULL DEFAULT NULL"})
   * @var string|null
   * @OA\Property(description="微信unionid")
   */
  protected ?string $unionid;
  /**
   * @DBType({"type":"varchar(80) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="微信昵称")
   */
  protected string $nickname;
  /**
   * @DBType({"type":"varchar(300) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="微信头像")
   */
  protected string $headimgurl;
  /**
   * @DBType({"type":"char(1) NOT NULL DEFAULT '0'"})
   * @var integer
   * @OA\Property(enum={0, 1, 2},description="姓别（1男，2女，0保密）")
   */
  protected int $sex;
  /**
   * @DBType({"key":"MUL","type":"varchar(30) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="用户姓名")
   */
  protected string $name;
  /**
   * @DBType({"key":"UNI","type":"varchar(30) NULL DEFAULT NULL"})
   * @var string|null
   * @OA\Property(description="用户联系电话")
   */
  protected ?string $tel;
  /**
   * @DBType({"type":"varchar(200) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="用户默认地址")
   */
  protected string $address;
  /**
   * @DBType({"type":"varchar(50) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="用户备注")
   */
  protected string $remark;
  /**
   * @DBType({"type":"char(1) NOT NULL DEFAULT '0'"})
   * @OA\Property(description="用户状态，1为禁登录,-为用户注销")
   * @var string
   */
  protected string $status;
}
