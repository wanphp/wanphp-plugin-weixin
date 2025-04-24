<?php
declare(strict_types=1);

use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
  $containerBuilder->addDefinitions([
    Wanphp\Libray\Weixin\MiniProgram::class => \DI\autowire(Wanphp\Libray\Weixin\MiniProgram::class),
    Wanphp\Libray\Weixin\WeChatPay::class => \DI\autowire(Wanphp\Libray\Weixin\WeChatPay::class),
    Wanphp\Libray\Weixin\WeChatBase::class => \DI\autowire(Wanphp\Libray\Weixin\WeChatBase::class),
    \Wanphp\Plugins\Weixin\Domain\CustomMenuInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\CustomMenuRepository::class),
    \Wanphp\Plugins\Weixin\Domain\MiniProgramInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\MiniProgramRepository::class),
    \Wanphp\Plugins\Weixin\Domain\MsgTemplateInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\MsgTemplateRepository::class),
    \Wanphp\Plugins\Weixin\Domain\PublicInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\PublicRepository::class),
    \Wanphp\Plugins\Weixin\Domain\UserInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\UserRepository::class),
    \Wanphp\Plugins\Weixin\Domain\UserLocationInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\UserLocationRepository::class),
    \Wanphp\Plugins\Weixin\Repositories\OAuth2\ClientRepository::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\OAuth2\ClientRepository::class),
    \Wanphp\Plugins\Weixin\Domain\AutoReplyInterface::class => \DI\autowire(\Wanphp\Plugins\Weixin\Repositories\AutoReplyRepository::class)
  ]);
};
