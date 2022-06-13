<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/17
 * Time: 15:21
 */

namespace Wanphp\Plugins\Weixin\Domain;


use Wanphp\Libray\Mysql\BaseInterface;

interface UserRoleInterface extends BaseInterface
{
  const TABLE_NAME = "user_roles";
}
