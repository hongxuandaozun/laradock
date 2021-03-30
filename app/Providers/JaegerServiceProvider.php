<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Jaeger\Config;
use const OpenTracing\Formats\TEXT_MAP;

class JaegerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $config = Config::getInstance();
        try {
            $tracer = $config->initTracer(\config('app.name'),config('services.jaeger.agent'));
        }catch (\Exception $e){
            Log::error('init tracer failed:'.$e->getMessage());
            return;
        }
        $tags = [
            'span.kind' => 'server',
            'type' => 'fpm'
        ];
        $operationName = '';
        if (app()->runningInConsole()){
            $tags['type'] = 'cli';
            $this->registerCommandStartingListener();
        }else{
            $tags['http.url'] = $operationName = request()->getPathInfo();
        }
        // 解析服务器参数是否包含服务追踪信息，如果有,则将其作为当前 Span 的父级，否则将当前 Span 作为 root span
        $spanContext = $tracer->extract(TEXT_MAP,$_SERVER);
        try{
            $span = $tracer->startSpan($operationName,[
                'child_of' => $spanContext,
                'tags' => $tags
            ]);
        }catch (\Exception $e){
            Log::warning("Start span with context failed: " . $e->getMessage());
            // 如果 spanContext 为空，则将当前 span 作为 root span
            $span = $tracer->startSpan($operationName);
        }
        $span->addBaggageItem("version", "2.0.0");

        // 将当前 span 的上下文注入到服务器参数，以便下个追踪节点可以获取到
        $tracer->inject($span->getContext(), TEXT_MAP, $_SERVER);
        // 将以上全局配置注册到服务容器，以便全局获取
        $this->app->instance('jaeger.config', $config);
        $this->app->instance('jaeger.tracer', $tracer);
        $this->app->instance('jaeger.span', $span);
        $this->app->instance('jaeger.flushed', false);

        // 注册相应的监听器，来处理HTTP请求、队列任务、命令行、消息日志的服务追踪
        $this->registerRequestHandledListener();
        $this->registerMessageLoggedListener();
        $this->registerTerminateHandler();
        $this->registerQueueJobProcessListener();
    }

    /**
     * 当处于 cli 模式下运行时, 匹配到 command 之后将 command.name 作为 span name.
     */
    protected function registerCommandStartingListener(){
        Event::listen(CommandStarting::class,function (CommandStarting $event){
            $this->app->get('jaeger.span')->overwriteOperationName($event->command);
            $this->app->get('jaeger.span')->setTag('command.name',$event->command);
        });
    }
    /**
     * 注册请求相关事件, 如果命中路由, 则将路由名作为 spanName.
     */
    protected function registerRequestHandledListener(){
        Event::listen(RouteMatched::class,function (RouteMatched $event){
            $this->app->get('jaeger.span')->overwriteOperationName('/',ltrim($event->request->route()->uri(),'/'));
        });
        Event::listen(RequestHandled::class,function (RequestHandled $event){
            $this->app->get('jaeger.span')->setTag('http.status',$event->response->getStatusCode());
        });
    }
    /**
     * 注册日志记录事件, 通过事件标记 span 失败及记录日志.
     */
    protected function registerMessageLoggedListener(){
        Event::listen(MessageLogged::class,function (MessageLogged $event){
            if ($event->level === 'error'){
                $this->app->get('jaeger.span')->setTag('error',true);
                $this->app->get('jaeger.span')->log((array)$event);
            }
        });
    }
    /**
     * 注册退出 callback.
     */
    protected function registerTerminateHandler(){
        app()->terminating(function(){
            $this->flushJaegerTracer();
            $this->app->instance('jaeger.flushed',true);
        });
        register_shutdown_function(function (){
            if(!$this->app->has('jaeger.flushed') || $this->app->get('jaeger.flushed')){
                return;
            }
            $this->flushJaegerTracer();
        });
    }
    // 请求周期结束后，提交服务追踪记录
    protected function flushJaegerTracer()
    {
        $this->app->get('jaeger.span')->finish();
        $this->app->get('jaeger.config')->flush();
    }
    /**
     * 消息队列消费记录
     * 由于队列使用了异步信号监听, 会导致 register_shutdown_handler() 失效, 所以选择在执行完一个 job 之后 flush 一次
     */
    protected function registerQueueJobProcessListener(){
        $span = null;
        Event::listen(JobProcessing::class,function (JobProcessing $event)use(&$span){
            $tracer = $this->app->get('jaeger.tracer');
            $spanName = sprintf('job.%s',$event->job->resolveName());
            $span = $tracer->startSpan($spanName,[
                'child_of' => $this->app->get('jaeger.span'),
                'tags' => [
                    'span.kind' => 'server',
                    'type' => 'cli',
                    'job.name' => $event->job->getName(),
                    'job.id' => $event->job->getJobId()
                ]
            ]);
            $tracer->inject($span->spanContext,TEXT_MAP,$_SERVER);
        });

        Event::listen(JobProcessed::class,function()use(&$span){
            $span->finish();
            $span = null;
            $this->app->get('jaeger.tracer')->flush();
        });

        $failListener = function ($event)use(&$span){
            $span->setTag('error',true);
            $span->log(['exception'=>$event->exception->getMessage()]);
            $span->flush();
            $span = null;
            $tracer = $this->app->get('jaeger.tracer');
            $tracer->spanThrifts = [];
            $tracer->flush();
        };
        Event::listen(JobFailed::class,$failListener);
        Event::listen(JobExceptionOccurred::class,$failListener);
    }
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
