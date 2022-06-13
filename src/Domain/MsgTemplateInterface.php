<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/29
 * Time: 15:03
 */

namespace Wanphp\Plugins\Weixin\Domain;


use Wanphp\Libray\Mysql\BaseInterface;

interface MsgTemplateInterface extends BaseInterface
{
  const TABLE_NAME = "msg_template";

  public function getTemplateId(int $id): string;
}
