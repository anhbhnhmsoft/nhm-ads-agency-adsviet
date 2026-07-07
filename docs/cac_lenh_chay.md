Welcome to Ubuntu 24.04 LTS (GNU/Linux 6.8.0-36-generic x86_64)

 * Documentation:  https://help.ubuntu.com
 * Management:     https://landscape.canonical.com
 * Support:        https://ubuntu.com/pro

 System information as of Tue May 26 12:29:02 PM +07 2026

  System load:  0.03               Processes:             162
  Usage of /:   16.5% of 58.47GB   Users logged in:       0
  Memory usage: 18%                IPv4 address for eth0: 160.30.173.85
  Swap usage:   0%

 * Strictly confined Kubernetes makes edge and IoT secure. Learn how MicroK8s
   just raised the bar for easy, resilient and secure K8s cluster deployment.

   https://ubuntu.com/engage/secure-kubernetes-at-the-edge

Expanded Security Maintenance for Applications is not enabled.

210 updates can be applied immediately.
3 of these updates are standard security updates.
To see these additional updates run: apt list --upgradable

Enable ESM Apps to receive additional future security updates.
See https://ubuntu.com/esm or run: sudo pro status


1 updates could not be installed automatically. For more details,
see /var/log/unattended-upgrades/unattended-upgrades.log

*** System restart required ***
Last login: Mon May 25 10:21:16 2026 from 1.53.44.152
root@2511240624183887hovanvinh:~# cd /var/www/html/nhm-ads-agency-adsviet/
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# php artisan schedule:list

ps aux | grep -E "queue:work|schedule" | grep -v grep

php artisan tinker --execute='dump(DB::table("jobs")->where("queue","meta-api")->count()); dump(DB::table("failed_jobs")->orderByDesc("id")->limit(10)->get(["id","queue","failed_at","exception"]));'

grep -RniE "2026-05-26.*(SyncAllPlatformsJob|SyncMetaPlatformJob|Batch Insights Sync failed|Error sync business-manager ads account insight|Meta Account Permission)" storage/logs | tail -n 200

  */5  * * * *  php artisan transactions:expire ......................................................... Next Due: 47 giây tới
  */30 * * * *  php artisan app:sync-ads-service-user ................................................... Next Due: 47 giây tới
  0    9 * * *  php artisan notifications:wallet-low-balance .............................................. Next Due: 3 giờ tới
  */2  * * * *  php artisan accounts:check-and-auto-pause ............................................... Next Due: 47 giây tới
  0    2 * * *  php artisan services:bill-postpay ........................................................ Next Due: 20 giờ tới
  */30 * * * *  App\Jobs\SyncAllPlatformsJob ............................................................ Next Due: 47 giây tới
  0    1 1 * *  php artisan app:calculate-spending-commission ............................................ Next Due: 5 ngày tới
  0    3 * * *  php artisan app:calculate-cashback ....................................................... Next Due: 21 giờ tới

www-data  750895  0.2  2.0 263784 124864 ?       S    May25   3:23 /usr/bin/php8.3 /var/www/html/nhm-ads-agency-adsviet/artisan queue:work --queue=meta-api --sleep=3 --tries=2 --timeout=120
www-data  750907  0.1  1.5 237732 96464 ?        S    May25   1:44 /usr/bin/php8.3 /var/www/html/nhm-ads-agency-adsviet/artisan queue:work --queue=google-api --sleep=3 --tries=2 --timeout=120
www-data  789476  0.2  1.1 137164 70100 ?        S    12:24   0:00 /usr/bin/php8.3 /var/www/html/nhm-ads-agency-adsviet/artisan queue:work --queue=mail,default --sleep=3 --tries=3 --max-time=3600
www-data  789484  0.2  1.1 137164 69840 ?        S    12:24   0:00 /usr/bin/php8.3 /var/www/html/nhm-ads-agency-adsviet/artisan queue:work --queue=mail,default --sleep=3 --tries=3 --max-time=3600
0 // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:1
Illuminate\Support\Collection^ {#5860
  #items: array:10 [
    0 => {#5861
      +"id": 207
      +"queue": "default"
      +"failed_at": "2026-04-20 11:43:54"
      +"exception": """
        Symfony\Component\Mailer\Exception\TransportException: Failed to authenticate on SMTP server with username "noreplymail.info68@gmail.com" using the following authenticators: "LOGIN", "PLAIN", "XOAUTH2". Authenticator "LOGIN" returned "Expected response code "235" but got code "535", with message "535-5.7.8 Username and Password not accepted. For more information, go to\r\n
        535 5.7.8  https://support.google.com/mail/?p=BadCredentials d9443c01a7336-2b60003ffd7sm85626075ad.80 - gsmtp".". Authenticator "PLAIN" returned "Expected response code "235" but got code "535", with message "535-5.7.8 Username and Password not accepted. For more information, go to\r\n
        535 5.7.8  https://support.google.com/mail/?p=BadCredentials d9443c01a7336-2b60003ffd7sm85626075ad.80 - gsmtp".". Authenticator "XOAUTH2" returned "Expected response code "235" but got code "334", with message "334 eyJzdGF0dXMiOiI0MDAiLCJzY2hlbWVzIjoiQmVhcmVyIiwic2NvcGUiOiJodHRwczovL21haWwuZ29vZ2xlLmNvbS8ifQ==".". in /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/EsmtpTransport.php:269\n
        Stack trace:\n
        #0 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/EsmtpTransport.php(199): Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport->handleAuth()\n
        #1 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/EsmtpTransport.php(150): Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport->doEhloCommand()\n
        #2 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(244): Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport->executeCommand()\n
        #3 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(270): Symfony\Component\Mailer\Transport\Smtp\SmtpTransport->doHeloCommand()\n
        #4 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(200): Symfony\Component\Mailer\Transport\Smtp\SmtpTransport->start()\n
        #5 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/AbstractTransport.php(69): Symfony\Component\Mailer\Transport\Smtp\SmtpTransport->doSend()\n
        #6 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(138): Symfony\Component\Mailer\Transport\AbstractTransport->send()\n
        #7 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(584): Symfony\Component\Mailer\Transport\Smtp\SmtpTransport->send()\n
        #8 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(331): Illuminate\Mail\Mailer->sendSymfonyMessage()\n
        #9 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(207): Illuminate\Mail\Mailer->send()\n
        #10 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Support/Traits/Localizable.php(19): Illuminate\Mail\Mailable->Illuminate\Mail\{closure}()\n
        #11 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(200): Illuminate\Mail\Mailable->withLocale()\n
        #12 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/SendQueuedMailable.php(82): Illuminate\Mail\Mailable->send()\n
        #13 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Mail\SendQueuedMailable->handle()\n
        #14 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #15 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #16 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #17 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #18 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(129): Illuminate\Container\Container->call()\n
        #19 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}()\n
        #20 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #21 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(133): Illuminate\Pipeline\Pipeline->then()\n
        #22 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow()\n
        #23 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}()\n
        #24 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #25 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then()\n
        #26 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware()\n
        #27 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\Queue\CallQueuedHandler->call()\n
        #28 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(469): Illuminate\Queue\Jobs\Job->fire()\n
        #29 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(419): Illuminate\Queue\Worker->process()\n
        #30 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\Queue\Worker->runJob()\n
        #31 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\Queue\Worker->daemon()\n
        #32 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker()\n
        #33 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()\n
        #34 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #35 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #36 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #37 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #38 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\Container\Container->call()\n
        #39 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Command/Command.php(335): Illuminate\Console\Command->execute()\n
        #40 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\Component\Console\Command\Command->run()\n
        #41 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(1103): Illuminate\Console\Command->run()\n
        #42 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(356): Symfony\Component\Console\Application->doRunCommand()\n
        #43 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(195): Symfony\Component\Console\Application->doRun()\n
        #44 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\Component\Console\Application->run()\n
        #45 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\Foundation\Console\Kernel->handle()\n
        #46 /var/www/html/nhm-ads-agency-adsviet/artisan(16): Illuminate\Foundation\Application->handleCommand()\n
        #47 {main}
        """
    }
    1 => {#1769
      +"id": 206
      +"queue": "meta-api"
      +"failed_at": "2026-03-19 07:34:34"
      +"exception": """
        Illuminate\Queue\TimeoutExceededException: App\Jobs\MetaApi\SyncMetaPlatformJob has timed out. in /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/TimeoutExceededException.php:15\n
        Stack trace:\n
        #0 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(846): Illuminate\Queue\TimeoutExceededException::forJob()\n
        #1 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(228): Illuminate\Queue\Worker->timeoutExceededException()\n
        #2 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Adapter/Curl/AbstractCurl.php(89): Illuminate\Queue\Worker->Illuminate\Queue\{closure}()\n
        #3 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Adapter/CurlAdapter.php(183): FacebookAds\Http\Adapter\Curl\AbstractCurl->exec()\n
        #4 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Client.php(215): FacebookAds\Http\Adapter\CurlAdapter->sendRequest()\n
        #5 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Request.php(286): FacebookAds\Http\Client->sendRequest()\n
        #6 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Api.php(152): FacebookAds\Http\Request->execute()\n
        #7 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Api.php(205): FacebookAds\Api->executeRequest()\n
        #8 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaBusinessService.php(393): FacebookAds\Api->call()\n
        #9 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(1280): App\Service\MetaBusinessService->getDetailAdsAccount()\n
        #10 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(803): App\Service\MetaService->syncMetaAccountsFromManagerEdge()\n
        #11 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(874): App\Service\MetaService->syncFromBusinessManagerIdBasic()\n
        #12 /var/www/html/nhm-ads-agency-adsviet/app/Jobs/MetaApi/SyncMetaPlatformJob.php(29): App\Service\MetaService->syncFromBusinessManagerId()\n
        #13 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): App\Jobs\MetaApi\SyncMetaPlatformJob->handle()\n
        #14 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #15 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #16 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #17 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #18 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(129): Illuminate\Container\Container->call()\n
        #19 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}()\n
        #20 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #21 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(133): Illuminate\Pipeline\Pipeline->then()\n
        #22 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow()\n
        #23 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}()\n
        #24 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #25 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then()\n
        #26 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware()\n
        #27 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\Queue\CallQueuedHandler->call()\n
        #28 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(469): Illuminate\Queue\Jobs\Job->fire()\n
        #29 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(419): Illuminate\Queue\Worker->process()\n
        #30 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\Queue\Worker->runJob()\n
        #31 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\Queue\Worker->daemon()\n
        #32 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker()\n
        #33 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()\n
        #34 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #35 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #36 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #37 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #38 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\Container\Container->call()\n
        #39 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Command/Command.php(335): Illuminate\Console\Command->execute()\n
        #40 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\Component\Console\Command\Command->run()\n
        #41 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(1103): Illuminate\Console\Command->run()\n
        #42 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(356): Symfony\Component\Console\Application->doRunCommand()\n
        #43 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(195): Symfony\Component\Console\Application->doRun()\n
        #44 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\Component\Console\Application->run()\n
        #45 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\Foundation\Console\Kernel->handle()\n
        #46 /var/www/html/nhm-ads-agency-adsviet/artisan(16): Illuminate\Foundation\Application->handleCommand()\n
        #47 {main}
        """
    }
    2 => {#5854
      +"id": 205
      +"queue": "meta-api"
      +"failed_at": "2026-03-19 04:19:14"
      +"exception": """
        Illuminate\Queue\TimeoutExceededException: App\Jobs\MetaApi\SyncMetaPlatformJob has timed out. in /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/TimeoutExceededException.php:15\n
        Stack trace:\n
        #0 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(846): Illuminate\Queue\TimeoutExceededException::forJob()\n
        #1 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(228): Illuminate\Queue\Worker->timeoutExceededException()\n
        #2 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Adapter/Curl/AbstractCurl.php(89): Illuminate\Queue\Worker->Illuminate\Queue\{closure}()\n
        #3 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Adapter/CurlAdapter.php(183): FacebookAds\Http\Adapter\Curl\AbstractCurl->exec()\n
        #4 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Client.php(215): FacebookAds\Http\Adapter\CurlAdapter->sendRequest()\n
        #5 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Request.php(286): FacebookAds\Http\Client->sendRequest()\n
        #6 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Api.php(152): FacebookAds\Http\Request->execute()\n
        #7 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Api.php(205): FacebookAds\Api->executeRequest()\n
        #8 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaBusinessService.php(393): FacebookAds\Api->call()\n
        #9 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(1280): App\Service\MetaBusinessService->getDetailAdsAccount()\n
        #10 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(805): App\Service\MetaService->syncMetaAccountsFromManagerEdge()\n
        #11 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(874): App\Service\MetaService->syncFromBusinessManagerIdBasic()\n
        #12 /var/www/html/nhm-ads-agency-adsviet/app/Jobs/MetaApi/SyncMetaPlatformJob.php(29): App\Service\MetaService->syncFromBusinessManagerId()\n
        #13 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): App\Jobs\MetaApi\SyncMetaPlatformJob->handle()\n
        #14 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #15 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #16 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #17 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #18 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(129): Illuminate\Container\Container->call()\n
        #19 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}()\n
        #20 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #21 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(133): Illuminate\Pipeline\Pipeline->then()\n
        #22 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow()\n
        #23 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}()\n
        #24 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #25 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then()\n
        #26 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware()\n
        #27 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\Queue\CallQueuedHandler->call()\n
        #28 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(469): Illuminate\Queue\Jobs\Job->fire()\n
        #29 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(419): Illuminate\Queue\Worker->process()\n
        #30 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\Queue\Worker->runJob()\n
        #31 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\Queue\Worker->daemon()\n
        #32 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker()\n
        #33 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()\n
        #34 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #35 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #36 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #37 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #38 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\Container\Container->call()\n
        #39 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Command/Command.php(335): Illuminate\Console\Command->execute()\n
        #40 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\Component\Console\Command\Command->run()\n
        #41 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(1103): Illuminate\Console\Command->run()\n
        #42 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(356): Symfony\Component\Console\Application->doRunCommand()\n
        #43 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(195): Symfony\Component\Console\Application->doRun()\n
        #44 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\Component\Console\Application->run()\n
        #45 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\Foundation\Console\Kernel->handle()\n
        #46 /var/www/html/nhm-ads-agency-adsviet/artisan(16): Illuminate\Foundation\Application->handleCommand()\n
        #47 {main}
        """
    }
    3 => {#5857
      +"id": 204
      +"queue": "meta-api"
      +"failed_at": "2026-02-27 00:08:42"
      +"exception": """
        Illuminate\Queue\TimeoutExceededException: App\Jobs\MetaApi\SyncMetaJob has timed out. in /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/TimeoutExceededException.php:15\n
        Stack trace:\n
        #0 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(846): Illuminate\Queue\TimeoutExceededException::forJob()\n
        #1 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(228): Illuminate\Queue\Worker->timeoutExceededException()\n
        #2 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Adapter/Curl/AbstractCurl.php(89): Illuminate\Queue\Worker->Illuminate\Queue\{closure}()\n
        #3 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Adapter/CurlAdapter.php(183): FacebookAds\Http\Adapter\Curl\AbstractCurl->exec()\n
        #4 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Client.php(215): FacebookAds\Http\Adapter\CurlAdapter->sendRequest()\n
        #5 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Request.php(286): FacebookAds\Http\Client->sendRequest()\n
        #6 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Api.php(152): FacebookAds\Http\Request->execute()\n
        #7 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Api.php(205): FacebookAds\Api->executeRequest()\n
        #8 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaBusinessService.php(335): FacebookAds\Api->call()\n
        #9 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(1134): App\Service\MetaBusinessService->getClientBusinessesPaginated()\n
        #10 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(1122): App\Service\MetaService->syncBusinessManagersFromEdge()\n
        #11 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(758): App\Service\MetaService->syncBusinessManagers()\n
        #12 /var/www/html/nhm-ads-agency-adsviet/app/Jobs/MetaApi/SyncMetaJob.php(42): App\Service\MetaService->syncMetaAccounts()\n
        #13 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): App\Jobs\MetaApi\SyncMetaJob->handle()\n
        #14 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #15 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #16 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #17 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #18 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(129): Illuminate\Container\Container->call()\n
        #19 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}()\n
        #20 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #21 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(133): Illuminate\Pipeline\Pipeline->then()\n
        #22 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow()\n
        #23 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}()\n
        #24 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #25 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then()\n
        #26 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware()\n
        #27 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\Queue\CallQueuedHandler->call()\n
        #28 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(469): Illuminate\Queue\Jobs\Job->fire()\n
        #29 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(419): Illuminate\Queue\Worker->process()\n
        #30 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\Queue\Worker->runJob()\n
        #31 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\Queue\Worker->daemon()\n
        #32 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker()\n
        #33 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()\n
        #34 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #35 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #36 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #37 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #38 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\Container\Container->call()\n
        #39 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Command/Command.php(335): Illuminate\Console\Command->execute()\n
        #40 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\Component\Console\Command\Command->run()\n
        #41 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(1103): Illuminate\Console\Command->run()\n
        #42 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(356): Symfony\Component\Console\Application->doRunCommand()\n
        #43 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(195): Symfony\Component\Console\Application->doRun()\n
        #44 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\Component\Console\Application->run()\n
        #45 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\Foundation\Console\Kernel->handle()\n
        #46 /var/www/html/nhm-ads-agency-adsviet/artisan(16): Illuminate\Foundation\Application->handleCommand()\n
        #47 {main}
        """
    }
    4 => {#5855
      +"id": 203
      +"queue": "meta-api"
      +"failed_at": "2026-02-27 00:04:37"
      +"exception": """
        Illuminate\Queue\TimeoutExceededException: App\Jobs\MetaApi\SyncMetaJob has timed out. in /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/TimeoutExceededException.php:15\n
        Stack trace:\n
        #0 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(846): Illuminate\Queue\TimeoutExceededException::forJob()\n
        #1 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(228): Illuminate\Queue\Worker->timeoutExceededException()\n
        #2 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Adapter/Curl/AbstractCurl.php(89): Illuminate\Queue\Worker->Illuminate\Queue\{closure}()\n
        #3 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Adapter/CurlAdapter.php(183): FacebookAds\Http\Adapter\Curl\AbstractCurl->exec()\n
        #4 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Client.php(215): FacebookAds\Http\Adapter\CurlAdapter->sendRequest()\n
        #5 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Http/Request.php(286): FacebookAds\Http\Client->sendRequest()\n
        #6 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Api.php(152): FacebookAds\Http\Request->execute()\n
        #7 /var/www/html/nhm-ads-agency-adsviet/vendor/facebook/php-business-sdk/src/FacebookAds/Api.php(205): FacebookAds\Api->executeRequest()\n
        #8 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaBusinessService.php(310): FacebookAds\Api->call()\n
        #9 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(1144): App\Service\MetaBusinessService->getOwnedBusinessesPaginated()\n
        #10 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(1121): App\Service\MetaService->syncBusinessManagersFromEdge()\n
        #11 /var/www/html/nhm-ads-agency-adsviet/app/Service/MetaService.php(758): App\Service\MetaService->syncBusinessManagers()\n
        #12 /var/www/html/nhm-ads-agency-adsviet/app/Jobs/MetaApi/SyncMetaJob.php(42): App\Service\MetaService->syncMetaAccounts()\n
        #13 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): App\Jobs\MetaApi\SyncMetaJob->handle()\n
        #14 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #15 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #16 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #17 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #18 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(129): Illuminate\Container\Container->call()\n
        #19 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}()\n
        #20 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #21 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(133): Illuminate\Pipeline\Pipeline->then()\n
        #22 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow()\n
        #23 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}()\n
        #24 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #25 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then()\n
        #26 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware()\n
        #27 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\Queue\CallQueuedHandler->call()\n
        #28 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(469): Illuminate\Queue\Jobs\Job->fire()\n
        #29 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(419): Illuminate\Queue\Worker->process()\n
        #30 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\Queue\Worker->runJob()\n
        #31 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\Queue\Worker->daemon()\n
        #32 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker()\n
        #33 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()\n
        #34 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #35 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #36 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #37 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #38 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\Container\Container->call()\n
        #39 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Command/Command.php(335): Illuminate\Console\Command->execute()\n
        #40 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\Component\Console\Command\Command->run()\n
        #41 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(1103): Illuminate\Console\Command->run()\n
        #42 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(356): Symfony\Component\Console\Application->doRunCommand()\n
        #43 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(195): Symfony\Component\Console\Application->doRun()\n
        #44 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\Component\Console\Application->run()\n
        #45 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\Foundation\Console\Kernel->handle()\n
        #46 /var/www/html/nhm-ads-agency-adsviet/artisan(16): Illuminate\Foundation\Application->handleCommand()\n
        #47 {main}
        """
    }
    5 => {#5853
      +"id": 202
      +"queue": "default"
      +"failed_at": "2026-02-24 03:30:59"
      +"exception": """
        Symfony\Component\Mailer\Exception\TransportException: Failed to authenticate on SMTP server with username "noreplymail.info68@gmail.com" using the following authenticators: "LOGIN", "PLAIN", "XOAUTH2". Authenticator "LOGIN" returned "Expected response code "235" but got code "535", with message "535-5.7.8 Username and Password not accepted. For more information, go to\r\n
        535 5.7.8  https://support.google.com/mail/?p=BadCredentials d9443c01a7336-2ada77cc13bsm4065955ad.47 - gsmtp".". Authenticator "PLAIN" returned "Expected response code "235" but got code "535", with message "535-5.7.8 Username and Password not accepted. For more information, go to\r\n
        535 5.7.8  https://support.google.com/mail/?p=BadCredentials d9443c01a7336-2ada77cc13bsm4065955ad.47 - gsmtp".". Authenticator "XOAUTH2" returned "Expected response code "235" but got code "334", with message "334 eyJzdGF0dXMiOiI0MDAiLCJzY2hlbWVzIjoiQmVhcmVyIiwic2NvcGUiOiJodHRwczovL21haWwuZ29vZ2xlLmNvbS8ifQ==".". in /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/EsmtpTransport.php:269\n
        Stack trace:\n
        #0 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/EsmtpTransport.php(199): Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport->handleAuth()\n
        #1 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/EsmtpTransport.php(150): Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport->doEhloCommand()\n
        #2 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(244): Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport->executeCommand()\n
        #3 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(270): Symfony\Component\Mailer\Transport\Smtp\SmtpTransport->doHeloCommand()\n
        #4 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(200): Symfony\Component\Mailer\Transport\Smtp\SmtpTransport->start()\n
        #5 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/AbstractTransport.php(69): Symfony\Component\Mailer\Transport\Smtp\SmtpTransport->doSend()\n
        #6 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/mailer/Transport/Smtp/SmtpTransport.php(138): Symfony\Component\Mailer\Transport\AbstractTransport->send()\n
        #7 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(584): Symfony\Component\Mailer\Transport\Smtp\SmtpTransport->send()\n
        #8 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(331): Illuminate\Mail\Mailer->sendSymfonyMessage()\n
        #9 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(207): Illuminate\Mail\Mailer->send()\n
        #10 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Support/Traits/Localizable.php(19): Illuminate\Mail\Mailable->Illuminate\Mail\{closure}()\n
        #11 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(200): Illuminate\Mail\Mailable->withLocale()\n
        #12 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/SendQueuedMailable.php(82): Illuminate\Mail\Mailable->send()\n
        #13 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Mail\SendQueuedMailable->handle()\n
        #14 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #15 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #16 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #17 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #18 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(129): Illuminate\Container\Container->call()\n
        #19 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}()\n
        #20 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #21 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(133): Illuminate\Pipeline\Pipeline->then()\n
        #22 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow()\n
        #23 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}()\n
        #24 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #25 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then()\n
        #26 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware()\n
        #27 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\Queue\CallQueuedHandler->call()\n
        #28 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(469): Illuminate\Queue\Jobs\Job->fire()\n
        #29 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(419): Illuminate\Queue\Worker->process()\n
        #30 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\Queue\Worker->runJob()\n
        #31 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\Queue\Worker->daemon()\n
        #32 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker()\n
        #33 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()\n
        #34 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #35 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #36 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #37 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #38 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\Container\Container->call()\n
        #39 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Command/Command.php(335): Illuminate\Console\Command->execute()\n
        #40 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\Component\Console\Command\Command->run()\n
        #41 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(1103): Illuminate\Console\Command->run()\n
        #42 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(356): Symfony\Component\Console\Application->doRunCommand()\n
        #43 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(195): Symfony\Component\Console\Application->doRun()\n
        #44 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\Component\Console\Application->run()\n
        #45 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\Foundation\Console\Kernel->handle()\n
        #46 /var/www/html/nhm-ads-agency-adsviet/artisan(16): Illuminate\Foundation\Application->handleCommand()\n
        #47 {main}
        """
    }
    6 => {#5852
      +"id": 201
      +"queue": "default"
      +"failed_at": "2025-12-19 03:00:05"
      +"exception": """
        InvalidArgumentException: View [mail.meta-ads-spending-exceeded] not found. in /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php:138\n
        Stack trace:\n
        #0 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php(78): Illuminate\View\FileViewFinder->findInPaths()\n
        #1 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/Factory.php(150): Illuminate\View\FileViewFinder->find()\n
        #2 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Markdown.php(93): Illuminate\View\Factory->make()\n
        #3 [internal function]: Illuminate\Mail\Markdown->Illuminate\Mail\{closure}()\n
        #4 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/Compilers/BladeCompiler.php(1035): call_user_func()\n
        #5 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Markdown.php(75): Illuminate\View\Compilers\BladeCompiler->usingEchoFormat()\n
        #6 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(387): Illuminate\Mail\Markdown->render()\n
        #7 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Collections/helpers.php(266): Illuminate\Mail\Mailable->Illuminate\Mail\{closure}()\n
        #8 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(440): value()\n
        #9 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(419): Illuminate\Mail\Mailer->renderView()\n
        #10 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(312): Illuminate\Mail\Mailer->addContent()\n
        #11 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(207): Illuminate\Mail\Mailer->send()\n
        #12 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Support/Traits/Localizable.php(19): Illuminate\Mail\Mailable->Illuminate\Mail\{closure}()\n
        #13 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(200): Illuminate\Mail\Mailable->withLocale()\n
        #14 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/SendQueuedMailable.php(82): Illuminate\Mail\Mailable->send()\n
        #15 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Mail\SendQueuedMailable->handle()\n
        #16 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #17 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #18 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #19 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #20 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(129): Illuminate\Container\Container->call()\n
        #21 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}()\n
        #22 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #23 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(133): Illuminate\Pipeline\Pipeline->then()\n
        #24 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow()\n
        #25 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}()\n
        #26 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #27 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then()\n
        #28 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware()\n
        #29 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\Queue\CallQueuedHandler->call()\n
        #30 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(469): Illuminate\Queue\Jobs\Job->fire()\n
        #31 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(419): Illuminate\Queue\Worker->process()\n
        #32 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\Queue\Worker->runJob()\n
        #33 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\Queue\Worker->daemon()\n
        #34 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker()\n
        #35 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()\n
        #36 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #37 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #38 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #39 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #40 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\Container\Container->call()\n
        #41 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Command/Command.php(335): Illuminate\Console\Command->execute()\n
        #42 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\Component\Console\Command\Command->run()\n
        #43 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(1103): Illuminate\Console\Command->run()\n
        #44 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(356): Symfony\Component\Console\Application->doRunCommand()\n
        #45 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(195): Symfony\Component\Console\Application->doRun()\n
        #46 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\Component\Console\Application->run()\n
        #47 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\Foundation\Console\Kernel->handle()\n
        #48 /var/www/html/nhm-ads-agency-adsviet/artisan(16): Illuminate\Foundation\Application->handleCommand()\n
        #49 {main}
        """
    }
    7 => {#5851
      +"id": 200
      +"queue": "default"
      +"failed_at": "2025-12-19 03:00:05"
      +"exception": """
        InvalidArgumentException: View [mail.meta-ads-spending-exceeded] not found. in /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php:138\n
        Stack trace:\n
        #0 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php(78): Illuminate\View\FileViewFinder->findInPaths()\n
        #1 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/Factory.php(150): Illuminate\View\FileViewFinder->find()\n
        #2 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Markdown.php(93): Illuminate\View\Factory->make()\n
        #3 [internal function]: Illuminate\Mail\Markdown->Illuminate\Mail\{closure}()\n
        #4 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/Compilers/BladeCompiler.php(1035): call_user_func()\n
        #5 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Markdown.php(75): Illuminate\View\Compilers\BladeCompiler->usingEchoFormat()\n
        #6 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(387): Illuminate\Mail\Markdown->render()\n
        #7 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Collections/helpers.php(266): Illuminate\Mail\Mailable->Illuminate\Mail\{closure}()\n
        #8 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(440): value()\n
        #9 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(419): Illuminate\Mail\Mailer->renderView()\n
        #10 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(312): Illuminate\Mail\Mailer->addContent()\n
        #11 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(207): Illuminate\Mail\Mailer->send()\n
        #12 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Support/Traits/Localizable.php(19): Illuminate\Mail\Mailable->Illuminate\Mail\{closure}()\n
        #13 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(200): Illuminate\Mail\Mailable->withLocale()\n
        #14 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/SendQueuedMailable.php(82): Illuminate\Mail\Mailable->send()\n
        #15 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Mail\SendQueuedMailable->handle()\n
        #16 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #17 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #18 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #19 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #20 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(129): Illuminate\Container\Container->call()\n
        #21 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}()\n
        #22 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #23 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(133): Illuminate\Pipeline\Pipeline->then()\n
        #24 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow()\n
        #25 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}()\n
        #26 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #27 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then()\n
        #28 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware()\n
        #29 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\Queue\CallQueuedHandler->call()\n
        #30 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(469): Illuminate\Queue\Jobs\Job->fire()\n
        #31 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(419): Illuminate\Queue\Worker->process()\n
        #32 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\Queue\Worker->runJob()\n
        #33 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\Queue\Worker->daemon()\n
        #34 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker()\n
        #35 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()\n
        #36 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #37 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #38 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #39 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #40 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\Container\Container->call()\n
        #41 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Command/Command.php(335): Illuminate\Console\Command->execute()\n
        #42 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\Component\Console\Command\Command->run()\n
        #43 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(1103): Illuminate\Console\Command->run()\n
        #44 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(356): Symfony\Component\Console\Application->doRunCommand()\n
        #45 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(195): Symfony\Component\Console\Application->doRun()\n
        #46 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\Component\Console\Application->run()\n
        #47 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\Foundation\Console\Kernel->handle()\n
        #48 /var/www/html/nhm-ads-agency-adsviet/artisan(16): Illuminate\Foundation\Application->handleCommand()\n
        #49 {main}
        """
    }
    8 => {#5850
      +"id": 199
      +"queue": "default"
      +"failed_at": "2025-12-19 03:00:05"
      +"exception": """
        InvalidArgumentException: View [mail.meta-ads-spending-exceeded] not found. in /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php:138\n
        Stack trace:\n
        #0 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php(78): Illuminate\View\FileViewFinder->findInPaths()\n
        #1 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/Factory.php(150): Illuminate\View\FileViewFinder->find()\n
        #2 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Markdown.php(93): Illuminate\View\Factory->make()\n
        #3 [internal function]: Illuminate\Mail\Markdown->Illuminate\Mail\{closure}()\n
        #4 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/Compilers/BladeCompiler.php(1035): call_user_func()\n
        #5 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Markdown.php(75): Illuminate\View\Compilers\BladeCompiler->usingEchoFormat()\n
        #6 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(387): Illuminate\Mail\Markdown->render()\n
        #7 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Collections/helpers.php(266): Illuminate\Mail\Mailable->Illuminate\Mail\{closure}()\n
        #8 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(440): value()\n
        #9 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(419): Illuminate\Mail\Mailer->renderView()\n
        #10 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(312): Illuminate\Mail\Mailer->addContent()\n
        #11 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(207): Illuminate\Mail\Mailer->send()\n
        #12 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Support/Traits/Localizable.php(19): Illuminate\Mail\Mailable->Illuminate\Mail\{closure}()\n
        #13 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(200): Illuminate\Mail\Mailable->withLocale()\n
        #14 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/SendQueuedMailable.php(82): Illuminate\Mail\Mailable->send()\n
        #15 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Mail\SendQueuedMailable->handle()\n
        #16 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #17 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #18 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #19 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #20 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(129): Illuminate\Container\Container->call()\n
        #21 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}()\n
        #22 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #23 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(133): Illuminate\Pipeline\Pipeline->then()\n
        #24 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow()\n
        #25 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}()\n
        #26 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #27 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then()\n
        #28 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware()\n
        #29 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\Queue\CallQueuedHandler->call()\n
        #30 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(469): Illuminate\Queue\Jobs\Job->fire()\n
        #31 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(419): Illuminate\Queue\Worker->process()\n
        #32 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\Queue\Worker->runJob()\n
        #33 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\Queue\Worker->daemon()\n
        #34 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker()\n
        #35 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()\n
        #36 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #37 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #38 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #39 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #40 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\Container\Container->call()\n
        #41 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Command/Command.php(335): Illuminate\Console\Command->execute()\n
        #42 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\Component\Console\Command\Command->run()\n
        #43 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(1103): Illuminate\Console\Command->run()\n
        #44 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(356): Symfony\Component\Console\Application->doRunCommand()\n
        #45 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(195): Symfony\Component\Console\Application->doRun()\n
        #46 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\Component\Console\Application->run()\n
        #47 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\Foundation\Console\Kernel->handle()\n
        #48 /var/www/html/nhm-ads-agency-adsviet/artisan(16): Illuminate\Foundation\Application->handleCommand()\n
        #49 {main}
        """
    }
    9 => {#5849
      +"id": 198
      +"queue": "default"
      +"failed_at": "2025-12-19 02:00:04"
      +"exception": """
        InvalidArgumentException: View [mail.meta-ads-spending-exceeded] not found. in /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php:138\n
        Stack trace:\n
        #0 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php(78): Illuminate\View\FileViewFinder->findInPaths()\n
        #1 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/Factory.php(150): Illuminate\View\FileViewFinder->find()\n
        #2 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Markdown.php(93): Illuminate\View\Factory->make()\n
        #3 [internal function]: Illuminate\Mail\Markdown->Illuminate\Mail\{closure}()\n
        #4 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/View/Compilers/BladeCompiler.php(1035): call_user_func()\n
        #5 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Markdown.php(75): Illuminate\View\Compilers\BladeCompiler->usingEchoFormat()\n
        #6 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(387): Illuminate\Mail\Markdown->render()\n
        #7 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Collections/helpers.php(266): Illuminate\Mail\Mailable->Illuminate\Mail\{closure}()\n
        #8 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(440): value()\n
        #9 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(419): Illuminate\Mail\Mailer->renderView()\n
        #10 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailer.php(312): Illuminate\Mail\Mailer->addContent()\n
        #11 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(207): Illuminate\Mail\Mailer->send()\n
        #12 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Support/Traits/Localizable.php(19): Illuminate\Mail\Mailable->Illuminate\Mail\{closure}()\n
        #13 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/Mailable.php(200): Illuminate\Mail\Mailable->withLocale()\n
        #14 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Mail/SendQueuedMailable.php(82): Illuminate\Mail\Mailable->send()\n
        #15 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Mail\SendQueuedMailable->handle()\n
        #16 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #17 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #18 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #19 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #20 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(129): Illuminate\Container\Container->call()\n
        #21 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Bus\Dispatcher->Illuminate\Bus\{closure}()\n
        #22 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #23 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Bus/Dispatcher.php(133): Illuminate\Pipeline\Pipeline->then()\n
        #24 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(134): Illuminate\Bus\Dispatcher->dispatchNow()\n
        #25 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): Illuminate\Queue\CallQueuedHandler->Illuminate\Queue\{closure}()\n
        #26 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(137): Illuminate\Pipeline\Pipeline->Illuminate\Pipeline\{closure}()\n
        #27 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(127): Illuminate\Pipeline\Pipeline->then()\n
        #28 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/CallQueuedHandler.php(68): Illuminate\Queue\CallQueuedHandler->dispatchThroughMiddleware()\n
        #29 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php(102): Illuminate\Queue\CallQueuedHandler->call()\n
        #30 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(469): Illuminate\Queue\Jobs\Job->fire()\n
        #31 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(419): Illuminate\Queue\Worker->process()\n
        #32 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Worker.php(187): Illuminate\Queue\Worker->runJob()\n
        #33 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(148): Illuminate\Queue\Worker->daemon()\n
        #34 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php(131): Illuminate\Queue\Console\WorkCommand->runWorker()\n
        #35 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(36): Illuminate\Queue\Console\WorkCommand->handle()\n
        #36 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Util.php(43): Illuminate\Container\BoundMethod::Illuminate\Container\{closure}()\n
        #37 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(96): Illuminate\Container\Util::unwrapIfClosure()\n
        #38 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/BoundMethod.php(35): Illuminate\Container\BoundMethod::callBoundMethod()\n
        #39 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Container/Container.php(836): Illuminate\Container\BoundMethod::call()\n
        #40 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(211): Illuminate\Container\Container->call()\n
        #41 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Command/Command.php(335): Illuminate\Console\Command->execute()\n
        #42 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Console/Command.php(180): Symfony\Component\Console\Command\Command->run()\n
        #43 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(1103): Illuminate\Console\Command->run()\n
        #44 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(356): Symfony\Component\Console\Application->doRunCommand()\n
        #45 /var/www/html/nhm-ads-agency-adsviet/vendor/symfony/console/Application.php(195): Symfony\Component\Console\Application->doRun()\n
        #46 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Console/Kernel.php(197): Symfony\Component\Console\Application->run()\n
        #47 /var/www/html/nhm-ads-agency-adsviet/vendor/laravel/framework/src/Illuminate/Foundation/Application.php(1235): Illuminate\Foundation\Console\Kernel->handle()\n
        #48 /var/www/html/nhm-ads-agency-adsviet/artisan(16): Illuminate\Foundation\Application->handleCommand()\n
        #49 {main}
        """
    }
  ]
  #escapeWhenCastingToString: false
} // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:2
storage/logs/worker-meta.log:14261:  2026-05-26 00:00:38 App\Jobs\SyncAllPlatformsJob ................... RUNNING
storage/logs/worker-meta.log:14262:  2026-05-26 00:00:38 App\Jobs\SyncAllPlatformsJob .............. 19.76ms DONE
storage/logs/worker-meta.log:14263:  2026-05-26 00:00:38 App\Jobs\MetaApi\SyncMetaPlatformJob ........... RUNNING
storage/logs/worker-meta.log:14264:  2026-05-26 00:01:37 App\Jobs\MetaApi\SyncMetaPlatformJob ...... 58 giây DONE
storage/logs/worker-meta.log:14267:  2026-05-26 00:30:39 App\Jobs\SyncAllPlatformsJob ................... RUNNING
storage/logs/worker-meta.log:14268:  2026-05-26 00:30:39 App\Jobs\SyncAllPlatformsJob .............. 14.45ms DONE
storage/logs/worker-meta.log:14269:  2026-05-26 00:30:39 App\Jobs\MetaApi\SyncMetaPlatformJob ........... RUNNING
storage/logs/worker-meta.log:14270:  2026-05-26 00:31:33 App\Jobs\MetaApi\SyncMetaPlatformJob ...... 53 giây DONE
storage/logs/worker-meta.log:14273:  2026-05-26 01:00:38 App\Jobs\SyncAllPlatformsJob ................... RUNNING
storage/logs/worker-meta.log:14274:  2026-05-26 01:00:38 App\Jobs\SyncAllPlatformsJob .............. 18.06ms DONE
storage/logs/worker-meta.log:14275:  2026-05-26 01:00:38 App\Jobs\MetaApi\SyncMetaPlatformJob ........... RUNNING
storage/logs/worker-meta.log:14276:  2026-05-26 01:01:26 App\Jobs\MetaApi\SyncMetaPlatformJob ...... 48 giây DONE
storage/logs/worker-meta.log:14279:  2026-05-26 01:30:39 App\Jobs\SyncAllPlatformsJob ................... RUNNING
storage/logs/worker-meta.log:14280:  2026-05-26 01:30:39 App\Jobs\SyncAllPlatformsJob .............. 17.60ms DONE
storage/logs/worker-meta.log:14281:  2026-05-26 01:30:39 App\Jobs\MetaApi\SyncMetaPlatformJob ........... RUNNING
storage/logs/worker-meta.log:14282:  2026-05-26 01:31:39 App\Jobs\MetaApi\SyncMetaPlatformJob ....... 1 phút DONE
storage/logs/worker-meta.log:14285:  2026-05-26 02:00:42 App\Jobs\SyncAllPlatformsJob ................... RUNNING
storage/logs/worker-meta.log:14286:  2026-05-26 02:00:42 App\Jobs\SyncAllPlatformsJob .............. 17.41ms DONE
storage/logs/worker-meta.log:14287:  2026-05-26 02:00:42 App\Jobs\MetaApi\SyncMetaPlatformJob ........... RUNNING
storage/logs/worker-meta.log:14288:  2026-05-26 02:01:42 App\Jobs\MetaApi\SyncMetaPlatformJob ....... 1 phút DONE
storage/logs/worker-meta.log:14291:  2026-05-26 02:30:34 App\Jobs\SyncAllPlatformsJob ................... RUNNING
storage/logs/worker-meta.log:14292:  2026-05-26 02:30:34 App\Jobs\SyncAllPlatformsJob .............. 14.44ms DONE
storage/logs/worker-meta.log:14293:  2026-05-26 02:30:34 App\Jobs\MetaApi\SyncMetaPlatformJob ........... RUNNING
storage/logs/worker-meta.log:14294:  2026-05-26 02:31:30 App\Jobs\MetaApi\SyncMetaPlatformJob ...... 55 giây DONE
storage/logs/worker-meta.log:14297:  2026-05-26 03:00:33 App\Jobs\SyncAllPlatformsJob ................... RUNNING
storage/logs/worker-meta.log:14298:  2026-05-26 03:00:33 App\Jobs\SyncAllPlatformsJob .............. 13.99ms DONE
storage/logs/worker-meta.log:14299:  2026-05-26 03:00:33 App\Jobs\MetaApi\SyncMetaPlatformJob ........... RUNNING
storage/logs/worker-meta.log:14300:  2026-05-26 03:01:25 App\Jobs\MetaApi\SyncMetaPlatformJob ...... 51 giây DONE
storage/logs/worker-meta.log:14303:  2026-05-26 03:30:35 App\Jobs\SyncAllPlatformsJob ................... RUNNING
storage/logs/worker-meta.log:14304:  2026-05-26 03:30:35 App\Jobs\SyncAllPlatformsJob .............. 21.58ms DONE
storage/logs/worker-meta.log:14305:  2026-05-26 03:30:35 App\Jobs\MetaApi\SyncMetaPlatformJob ........... RUNNING
storage/logs/worker-meta.log:14306:  2026-05-26 03:31:41 App\Jobs\MetaApi\SyncMetaPlatformJob  1 phút 5 giây DONE
storage/logs/worker-meta.log:14309:  2026-05-26 04:00:44 App\Jobs\SyncAllPlatformsJob ................... RUNNING
storage/logs/worker-meta.log:14310:  2026-05-26 04:00:44 App\Jobs\SyncAllPlatformsJob .............. 20.40ms DONE
storage/logs/worker-meta.log:14311:  2026-05-26 04:00:44 App\Jobs\MetaApi\SyncMetaPlatformJob ........... RUNNING
storage/logs/worker-meta.log:14312:  2026-05-26 04:01:35 App\Jobs\MetaApi\SyncMetaPlatformJob ...... 51 giây DONE
storage/logs/worker-meta.log:14315:  2026-05-26 04:30:39 App\Jobs\SyncAllPlatformsJob ................... RUNNING
storage/logs/worker-meta.log:14316:  2026-05-26 04:30:39 App\Jobs\SyncAllPlatformsJob .............. 11.87ms DONE
storage/logs/worker-meta.log:14317:  2026-05-26 04:30:39 App\Jobs\MetaApi\SyncMetaPlatformJob ........... RUNNING
storage/logs/worker-meta.log:14318:  2026-05-26 04:31:30 App\Jobs\MetaApi\SyncMetaPlatformJob ...... 50 giây DONE
storage/logs/worker-meta.log:14321:  2026-05-26 05:00:41 App\Jobs\SyncAllPlatformsJob ................... RUNNING
storage/logs/worker-meta.log:14322:  2026-05-26 05:00:41 App\Jobs\SyncAllPlatformsJob .............. 18.62ms DONE
storage/logs/worker-meta.log:14323:  2026-05-26 05:00:41 App\Jobs\MetaApi\SyncMetaPlatformJob ........... RUNNING
storage/logs/worker-meta.log:14324:  2026-05-26 05:01:29 App\Jobs\MetaApi\SyncMetaPlatformJob ...... 48 giây DONE
storage/logs/actions/action-2026-05-26.log:45:[2026-05-26 00:00:38] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Starting global synchronization {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:46:[2026-05-26 00:00:38] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Meta sync for setting ID 67447573506426862 {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:47:[2026-05-26 00:00:38] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Google sync for MCC 1480510811 (Setting ID: 67447548202190464) {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:48:[2026-05-26 00:00:38] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Global synchronization dispatched successfully {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:577:[2026-05-26 00:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Starting global synchronization {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:578:[2026-05-26 00:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Meta sync for setting ID 67447573506426862 {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:579:[2026-05-26 00:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Google sync for MCC 1480510811 (Setting ID: 67447548202190464) {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:580:[2026-05-26 00:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Global synchronization dispatched successfully {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:1109:[2026-05-26 01:00:38] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Starting global synchronization {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:1110:[2026-05-26 01:00:38] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Meta sync for setting ID 67447573506426862 {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:1111:[2026-05-26 01:00:38] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Google sync for MCC 1480510811 (Setting ID: 67447548202190464) {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:1112:[2026-05-26 01:00:38] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Global synchronization dispatched successfully {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:1641:[2026-05-26 01:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Starting global synchronization {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:1642:[2026-05-26 01:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Meta sync for setting ID 67447573506426862 {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:1643:[2026-05-26 01:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Google sync for MCC 1480510811 (Setting ID: 67447548202190464) {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:1644:[2026-05-26 01:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Global synchronization dispatched successfully {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:2173:[2026-05-26 02:00:42] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Starting global synchronization {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:2174:[2026-05-26 02:00:42] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Meta sync for setting ID 67447573506426862 {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:2175:[2026-05-26 02:00:42] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Google sync for MCC 1480510811 (Setting ID: 67447548202190464) {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:2176:[2026-05-26 02:00:42] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Global synchronization dispatched successfully {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:2705:[2026-05-26 02:30:34] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Starting global synchronization {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:2706:[2026-05-26 02:30:34] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Meta sync for setting ID 67447573506426862 {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:2707:[2026-05-26 02:30:34] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Google sync for MCC 1480510811 (Setting ID: 67447548202190464) {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:2708:[2026-05-26 02:30:34] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Global synchronization dispatched successfully {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:3237:[2026-05-26 03:00:33] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Starting global synchronization {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:3238:[2026-05-26 03:00:33] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Meta sync for setting ID 67447573506426862 {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:3239:[2026-05-26 03:00:33] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Google sync for MCC 1480510811 (Setting ID: 67447548202190464) {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:3240:[2026-05-26 03:00:33] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Global synchronization dispatched successfully {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:3769:[2026-05-26 03:30:35] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Starting global synchronization {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:3770:[2026-05-26 03:30:35] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Meta sync for setting ID 67447573506426862 {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:3771:[2026-05-26 03:30:35] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Google sync for MCC 1480510811 (Setting ID: 67447548202190464) {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:3772:[2026-05-26 03:30:35] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Global synchronization dispatched successfully {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:4301:[2026-05-26 04:00:44] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Starting global synchronization {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:4302:[2026-05-26 04:00:44] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Meta sync for setting ID 67447573506426862 {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:4303:[2026-05-26 04:00:44] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Google sync for MCC 1480510811 (Setting ID: 67447548202190464) {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:4304:[2026-05-26 04:00:44] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Global synchronization dispatched successfully {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:4863:[2026-05-26 04:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Starting global synchronization {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:4864:[2026-05-26 04:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Meta sync for setting ID 67447573506426862 {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:4865:[2026-05-26 04:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Google sync for MCC 1480510811 (Setting ID: 67447548202190464) {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:4866:[2026-05-26 04:30:39] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Global synchronization dispatched successfully {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:5425:[2026-05-26 05:00:41] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Starting global synchronization {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:5426:[2026-05-26 05:00:41] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Meta sync for setting ID 67447573506426862 {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:5427:[2026-05-26 05:00:41] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Dispatched Google sync for MCC 1480510811 (Setting ID: 67447548202190464) {"ip":"127.0.0.1"} 
storage/logs/actions/action-2026-05-26.log:5428:[2026-05-26 05:00:41] local.INFO: IP 127.0.0.1: SyncAllPlatformsJob: Global synchronization dispatched successfully {"ip":"127.0.0.1"} 
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# php artisan tinker --execute='$ids=["act_978273446145835","act_732035308183982","act_349118277112451"]; $rows=App\Models\MetaAccount::whereIn("account_id",$ids)->get(["id","account_id","account_name"]); foreach($rows as $a){dump([$a->account_id,$a->account_name,DB::table("meta_ads_account_insights")->where("meta_account_id",$a->id)->where("date","2026-05-25")->get(["date","spend","reach","last_synced_at","updated_at"])->toArray()]);}'
array:3 [
  0 => "act_732035308183982"
  1 => "SC27-(GMT+7)-Adpro-010"
  2 => array:1 [
    0 => {#6305
      +"date": "2026-05-25"
      +"spend": "30609"
      +"reach": "569"
      +"last_synced_at": "2026-05-26 05:01:20"
      +"updated_at": "2026-05-26 05:01:20"
    }
  ]
] // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:4
array:3 [
  0 => "act_349118277112451"
  1 => "SC27-(GMT+7)-Adpro-12"
  2 => array:1 [
    0 => {#6301
      +"date": "2026-05-25"
      +"spend": "4673"
      +"reach": "123"
      +"last_synced_at": "2026-05-26 05:01:20"
      +"updated_at": "2026-05-26 05:01:20"
    }
  ]
] // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:4
array:3 [
  0 => "act_978273446145835"
  1 => "SC27-(GMT+7)-Adpro-05"
  2 => array:1 [
    0 => {#6305
      +"date": "2026-05-25"
      +"spend": "10048753"
      +"reach": "5697"
      +"last_synced_at": "2026-05-26 05:01:20"
      +"updated_at": "2026-05-26 05:01:20"
    }
  ]
] // vendor/psy/psysh/src/ExecutionClosure.php(41) : eval()'d code:4
root@2511240624183887hovanvinh:/var/www/html/nhm-ads-agency-adsviet# 