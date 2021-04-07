<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2021/3/9
 * Time: 10:50
 */

namespace Wanphp\Plugins\Weixin\Repositories;


use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\CustomMenuInterface;
use Wanphp\Plugins\Weixin\Entities\CustomMenuEntity;

class CustomMenuRepository extends BaseRepository implements CustomMenuInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, self::TABLENAME, CustomMenuEntity::class);
  }
}
