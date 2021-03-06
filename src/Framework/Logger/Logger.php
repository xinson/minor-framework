<?php
namespace Minor\Framework\Logger;

class Logger implements LoggerInterface
{

    const DEBUG = 100;

    const INFO = 200;

    const NOTICE = 250;

    const WARNING = 300;

    const ERROR = 400;

    const CRITICAL = 500;

    const ALERT = 550;

    const EMERGENCY = 600;

    protected static $levels = array(
        self::DEBUG => 'DEBUG',
        self::INFO => 'INFO',
        self::NOTICE => 'NOTICE',
        self::WARNING => 'WARNING',
        self::ERROR => 'ERROR',
        self::CRITICAL => 'CRITICAL',
        self::ALERT => 'ALERT',
        self::EMERGENCY => 'EMERGENCY',
    );

    protected $channel;

    protected $handlers;

    protected $processors;

    public function __construct($channel, $handlers = array(), $processors = array())
    {
        $this->channel = $channel;
        $this->handlers = $handlers;
        $this->processors = $processors;
    }

    /**
     * 加入元素$handlers
     * @param $handler
     * @return $this
     */
    public function pushHandler($handler)
    {
        array_unshift($this->handlers, $handler);
        return $this;
    }

    /**
     * 删除$handlers第一个元素
     * @return mixed
     */
    public function popHandler()
    {
        if (empty($this->handlers)) {
            throw new \LogicException('You tried to pop from an empty handler stack');
        }
        return array_shift($this->handlers);
    }

    /**
     * 设置$handlers
     * @param $handlers
     * @return $this
     */
    public function setHandlers($handlers)
    {
        $this->handlers = array();
        if (is_array($handlers)) {
            foreach (array_reverse($handlers) as $handler) {
                $this->pushHandler($handler);
            }
        }
        return $this;
    }

    /**
     * 获取$handlers
     * @return array
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * 添加处理
     * @param $callback
     * @return $this
     */
    public function pushProcessor($callback)
    {
        if (empty($this->processors)) {
            throw new \InvalidArgumentException('Processors must be valid callables (callback or object with an __invoke method),' . var_export($callback, true) . ' given.');
        }
        array_unshift($this->processors, $callback);
        return $this;
    }

    /**
     * 删除处理
     * @return mixed
     */
    public function popProcessor()
    {
        if (empty($this->processors)) {
            throw new \InvalidArgumentException('You tried to pop from an empty processor stack.');
        }
        return array_shift($this->processors);
    }

    /**
     * 返回处理
     * @return array
     */
    public function getProcessors()
    {
        return $this->processors;
    }

    /**
     * 添加到记录
     * @param integer $level
     * @param $message
     * @param array $context
     */
    public function addRecord($level, $message, array $context = array())
    {
        $handlerkey = null;
        if (is_array($this->handlers)) {
            reset($this->handlers);
            foreach ($this->handlers as $handler){
                if($handler->isHandling(array('level' => $level))){
                    $handlerkey = key($this->handlers);
                }
                break;
            }
        }

        if (null === $handlerkey) {
            return false;
        }

        $levelName = self::$levels[$level];

        $record = array(
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => $levelName,
            'channel' => $this->channel,
            'datetime' => date('Y-m-d H:i:s',time()),
            'extra' => []
        );

        //处理process
        if(is_array($this->processors)) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
        }

        //处理handler
        if(is_array($this->handlers)) {
            foreach ($this->handlers as $handler){
                if (true === $handler->handle($record)) {
                    break;
                }
                next($this->handlers);
            }
        }

        return true;

    }

    /**
     * 验证hand
     * @param $level
     * @return bool
     */
    protected function isHandling($level)
    {
        $record = array('level' => $level);

        if (empty($this->handlers)) {
            foreach ($this->handlers as $handler) {
                if ($handler->isHandling($record)) {
                    return true;
                }
            }
        }
        return false;
    }


    public function emergency($message, array $context = array())
    {
        $this->addRecord(static::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->addRecord(static::ALERT, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->addRecord(static::CRITICAL, $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->addRecord(static::ERROR, $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->addRecord(static::WARNING, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->addRecord(static::NOTICE, $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->addRecord(static::INFO, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->addRecord(static::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = array())
    {}
}