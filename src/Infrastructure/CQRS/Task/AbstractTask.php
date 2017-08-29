<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);


namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\Task;

use Desperado\ConcurrencyFramework\Application\Context\KernelContext;
use Desperado\ConcurrencyFramework\Common\Logger\LoggerRegistry;
use Desperado\ConcurrencyFramework\Domain\Context\ContextInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Domain\Task\TaskInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\AbstractExecutionOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\CommandOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\ErrorOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\EventOptions;
use Psr\Log\LoggerInterface;

/**
 * Base task class
 */
abstract class AbstractTask implements TaskInterface
{
    /**
     * Execute options
     *
     * @var AbstractExecutionOptions
     */
    private $options;

    /**
     * @param AbstractExecutionOptions $options
     */
    public function __construct(AbstractExecutionOptions $options)
    {
        $this->options = $options;
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): AbstractExecutionOptions
    {
        return $this->options;
    }

    /**
     * Log income message
     *
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return void
     */
    protected function logMessage(MessageInterface $message, ContextInterface $context): void
    {
        if($context instanceof KernelContext)
        {
            $options = $context->getOptions($message);

            if(null !== $options)
            {
                $logMessage = \sprintf('Start "%s" message execution', \get_class($message));

                if(true === $options->getLogPayloadFlag())
                {
                    $logMessage .= \sprintf(' with payload "%s"', self::getMessagePayloadAsString($message));
                }

                $this->getLogger($message, $context)->debug($logMessage);
            }
        }
    }

    /**
     * Get logger for message
     *
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return LoggerInterface
     */
    protected function getLogger(MessageInterface $message, ContextInterface $context): LoggerInterface
    {
        /** @var KernelContext $context */

        return LoggerRegistry::getLogger($context->getOptions($message)->getLoggerChannel());
    }

    /**
     * Append execution options
     *
     * @param ContextInterface $context
     *
     * @return void
     */
    protected function appendOptions(ContextInterface $context)
    {
        if(false === ($context instanceof KernelContext))
        {
            return;
        }

        /** @var KernelContext $context */

        switch(\get_class($this->options))
        {
            case CommandOptions::class:
                $context->appendCommandExecutionOptions($this->options);
                break;

            case EventOptions::class:
                $context->appendEventExecutionOptions($this->options);
                break;

            case ErrorOptions::class:
                $context->appendErrorHandlerExecutionOptions($this->options);
                break;
        }
    }

    /**
     * Get string message payload representation
     *
     * @param MessageInterface $message
     *
     * @return string
     */
    protected static function getMessagePayloadAsString(MessageInterface $message): string
    {
        return \json_encode(\get_object_vars($message));
    }
}