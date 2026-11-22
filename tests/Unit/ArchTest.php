<?php

// 基本防屎山：用机器查的几条结构规则（Pest 自带的架构测试）。

arch()->preset()->php();
arch()->preset()->security();

arch('不留调试代码')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

arch('业务写入类不碰请求、会话和登录状态')
    ->expect('App\Actions')
    ->not->toUse(['request', 'session', 'auth', 'Illuminate\Http\Request']);

arch('模型不依赖 HTTP 层')
    ->expect('App\Models')
    ->not->toUse(['App\Http', 'Illuminate\Http', 'request']);

arch('控制器以 Controller 结尾')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');
