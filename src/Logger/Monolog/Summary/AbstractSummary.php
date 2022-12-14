<?php

namespace Tofex\Task\Logger\Monolog\Summary;

use Magento\Framework\Logger\Monolog;
use Monolog\DateTimeImmutable;
use Tofex\Core\Helper\Instances;
use Tofex\Core\Helper\Registry;
use Tofex\Task\Logger\Monolog\Handler\Summary\AbstractHandler;
use Tofex\Task\Logger\Record;

/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2022 Tofex UG (http://www.tofex.de)
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
abstract class AbstractSummary
    extends Monolog
{
    /** @var Instances */
    protected $instanceHelper;

    /** @var Registry */
    protected $registryHelper;

    /** @var AbstractHandler[] */
    private $taskHandlers = [];

    /**
     * @param Instances $instanceHelper
     * @param Registry  $registryHelper
     */
    public function __construct(Instances $instanceHelper, Registry $registryHelper)
    {
        $this->instanceHelper = $instanceHelper;
        $this->registryHelper = $registryHelper;

        parent::__construct($this->getSummaryName());

        $this->registryHelper->register($this->getSummaryName(), $this);
    }

    /**
     * @return string
     */
    abstract protected function getSummaryName(): string;

    /**
     * @return AbstractHandler
     */
    public function prepareTaskHandler(): AbstractHandler
    {
        $taskName = $this->registryHelper->registry('current_task_name');
        $taskId = $this->registryHelper->registry('current_task_id');

        $taskKey = md5(json_encode([$taskName, $taskId]));

        if ( ! array_key_exists($taskKey, $this->taskHandlers)) {
            /** @var AbstractHandler $handler */
            $handler = $this->instanceHelper->getInstance($this->getHandlerClass());

            $this->pushHandler($handler);

            $this->taskHandlers[ $taskKey ] = $handler;
        }

        return $this->taskHandlers[ $taskKey ];
    }

    /**
     * @return string
     */
    abstract protected function getHandlerClass(): string;

    /**
     * @param integer                $level    The logging level
     * @param string                 $message  The log message
     * @param array                  $context  The log context
     * @param DateTimeImmutable|null $datetime Optional log date to log into the past or future
     *
     * @return Boolean Whether the record has been processed
     */
    public function addRecord(
        int $level,
        string $message,
        array $context = [],
        DateTimeImmutable $datetime = null): bool
    {
        $this->prepareTaskHandler();

        return parent::addRecord($level, $message, $context);
    }

    /**
     * @param Record $record
     */
    public function addRecordToTaskHandler(Record $record)
    {
        $handler = $this->prepareTaskHandler();

        if ($handler) {
            $handler->addRecord($record);
        }
    }
}
