<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/9
 * Time: 10:49
 */

namespace Wanphp\Plugins\Weixin\Domain;


use Wanphp\Libray\Mysql\BaseInterface;

interface CustomMenuInterface extends BaseInterface
{
  const TABLENAME = "wexin_custommenu";
}
