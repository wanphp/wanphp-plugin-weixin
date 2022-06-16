<?php
declare(strict_types=1);

use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
  $containerBuilder->addDefinitions([
    \Wanphp\Plugins\Weixin\Domain\CustomMenuInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\CustomMenuRepository::class),
    \Wanphp\Plugins\Weixin\Domain\MiniProgramInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\MiniProgramRepository::class),
    \Wanphp\Plugins\Weixin\Domain\MsgTemplateInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\MsgTemplateRepository::class),
    \Wanphp\Plugins\Weixin\Domain\PublicInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\PublicRepository::class),
    \Wanphp\Plugins\Weixin\Domain\UserInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\UserRepository::class),
    \Wanphp\Plugins\Weixin\Domain\UserLocationInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\UserLocationRepository::class)
  ]);
};
