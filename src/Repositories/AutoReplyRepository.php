<?php

namespace Wanphp\Plugins\Weixin\Repositories;


use Wanphp\Libray\Mysql\BaseRepository;
use Wanphp\Libray\Mysql\Database;
use Wanphp\Plugins\Weixin\Domain\AutoReplyInterface;
use Wanphp\Plugins\Weixin\Entities\AutoReplyEntity;

class AutoReplyRepository extends BaseRepository implements AutoReplyInterface
{
  public function __construct(Database $database)
  {
    parent::__construct($database, self::TABLE_NAME, AutoReplyEntity::class);
  }
}
