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
  private ?int $id;
  /**
   * @DBType({"key":"UNI","type":"varchar(29) NULL DEFAULT NULL"})
   * @var string|null
   * @OA\Property(description="微信unionid")
   */
  private ?string $unionid;
  /**
   * @DBType({"type":"varchar(80) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="微信昵称")
   */
  private string $nickname;
  /**
   * @DBType({"type":"varchar(300) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="微信头像")
   */
  private string $headimgurl;
  /**
   * @DBType({"type":"char(1) NOT NULL DEFAULT '0'"})
   * @var integer
   * @OA\Property(enum={0, 1, 2},description="姓别（1男，2女，0保密）")
   */
  private int $sex;
  /**
   * @DBType({"key":"MUL","type":"varchar(30) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="用户姓名")
   */
  private string $name;
  /**
   * @DBType({"key":"UNI","type":"varchar(30) NULL DEFAULT NULL"})
   * @var string|null
   * @OA\Property(description="用户联系电话")
   */
  private ?string $tel;
  /**
   * @DBType({"type":"varchar(50) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="联系邮箱")
   */
  private string $email;
  /**
   * @DBType({"type":"char(11) NOT NULL DEFAULT ''"})
   * @OA\Property(description="加密密钥")
   * @var string
   */
  private string $salt;
  /**
   * @DBType({"type":"char(32) NOT NULL DEFAULT ''"})
   * @OA\Property(description="密码")
   * @var string
   */
  private string $password;
  /**
   * @DBType({"type":"varchar(200) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="用户默认地址")
   */
  private string $address;
  /**
   * @DBType({"type":"varchar(50) NOT NULL DEFAULT ''"})
   * @var string
   * @OA\Property(description="用户备注")
   */
  private string $remark;
  /**
   * @DBType({"type":"char(6) NOT NULL DEFAULT ''"})
   * @OA\Property(description="邮编")
   * @var string
   */
  private string $fox;
  /**
   * @DBType({"type":"int NOT NULL DEFAULT '0'"})
   * @var integer
   * @OA\Property(description="用户当前可用积分")
   */
  private int $integral;
  /**
   * @DBType({"type":"decimal(15,2) NOT NULL DEFAULT '0'"})
   * @var  float
   * @OA\Property(description="用户当前可提现金额")
   */
  private float $cash_back;
  /**
   * @DBType({"type":"decimal(15,2) NOT NULL DEFAULT '0'"})
   * @var  float
   * @OA\Property(description="用户充值余额")
   */
  private float $money;
  /**
   * @DBType({"type":"char(1) NOT NULL DEFAULT '0'"})
   * @OA\Property(description="用户状态，1为禁登录")
   * @var string
   */
  private string $status;
}
