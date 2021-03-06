<?php

namespace Bolt\Logger\Handler;

use Bolt\Application;
use Bolt\Helpers\String;
use Bolt\Logger\Formatter\System;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Monolog Database handler for system logging
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SystemHandler extends AbstractProcessingHandler
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var boolean
     */
    private $initialized = false;

    /**
     * @var string
     */
    private $tablename;

    /**
     *
     * @param Application $app
     * @param string      $logger
     * @param integer     $level
     * @param boolean     $bubble
     */
    public function __construct(Application $app, $level = Logger::DEBUG, $bubble = true)
    {
        $this->app = $app;
        parent::__construct($level, $bubble);
    }

    /**
     * Handle
     *
     * @param  array   $record
     * @return boolean
     */
    public function handle(array $record)
    {
        if (!$this->isHandling($record)) {
            return false;
        }

        $record = $this->processRecord($record);
        $record['formatted'] = $this->getFormatter()->format($record);
        $this->write($record);

        return false === $this->bubble;
    }

    protected function write(array $record)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if ($this->app['config']->get('general/debug')) {
            $backtrace = debug_backtrace();
            $filename = str_replace($this->app['resources']->getPath('root'), "", $backtrace[3]['file']);
            $record['message'] .= "\nFile: " . $filename;
            $record['message'] .= "\nLine: " . $backtrace[3]['line'];
        }

        $this->user = $this->app['session']->get('user');
        $username = isset($this->user['username']) ? $this->user['username'] : '';

        try {
            $this->app['db']->insert($this->tablename, array(
                'level'       => $record['level'],
                'date'        => $record['datetime']->format('Y-m-d H:i:s'),
                'message'     => $record['message'],
                'username'    => $username,
                'requesturi'  => $this->app['request']->getRequestUri(),
                'route'       => $this->app['request']->get('_route'),
                'ip'          => $this->app['request']->getClientIp(),
                'context'     => isset($record['context']['event']) ? $record['context']['event'] : ''
            ));
        } catch (\Exception $e) {
            // Nothing..
        }
    }

    /**
     * Processes a record.
     *
     * @param  array $record
     * @return array
     */
    protected function processRecord(array $record)
    {
        if ($this->processors) {
            foreach ($this->processors as $processor) {
                $record = call_user_func($processor, $record);
            }
        }

        return $record;
    }

    /**
     * Initialize
     */
    private function initialize()
    {
        $this->tablename = sprintf("%s%s", $this->app['config']->get('general/database/prefix', "bolt_"), 'log_system');
        $this->initialized = true;
    }
}
