<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/16
 * Time: 10:47
 */

namespace Wanphp\Plugins\Weixin\Domain;


use Wanphp\Libray\Mysql\BaseInterface;

interface  PublicInterface extends BaseInterface
{
  const TABLE_NAME = "weixin_users_public";
}
