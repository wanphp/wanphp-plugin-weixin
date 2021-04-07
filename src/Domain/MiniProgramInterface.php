<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/16
 * Time: 10:50
 */

namespace Wanphp\Plugins\Weixin\Domain;


use Wanphp\Libray\Mysql\BaseInterface;

interface MiniProgramInterface extends BaseInterface
{
  const TABLENAME = "weixin_users_minprogram";
}
