<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/8/29
 * Time: 17:09
 */

namespace Wanphp\Plugins\Weixin\Repositories;


use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\UserLocationInterface;
use Wanphp\Plugins\Weixin\Entities\UserLocationEntity;

class UserLocationRepository extends BaseRepository implements UserLocationInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, self::TABLE_NAME, UserLocationEntity::class);
  }
}
