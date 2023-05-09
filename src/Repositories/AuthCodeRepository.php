<?php

namespace Wanphp\Plugins\Weixin\Repositories;

use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Entities\AuthCodeEntity;

class AuthCodeRepository extends \Wanphp\Libray\Mysql\BaseRepository implements \Wanphp\Plugins\Weixin\Domain\AuthCodeInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, self::TABLE_NAME, AuthCodeEntity::class);
  }
}
