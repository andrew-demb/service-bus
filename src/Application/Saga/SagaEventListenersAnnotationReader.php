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

namespace Desperado\ConcurrencyFramework\Application\Saga;

use Desperado\ConcurrencyFramework\Application\Saga\Exceptions\EventListenerAnnotationException;
use Desperado\ConcurrencyFramework\Common\Formatter\ThrowableFormatter;
use Desperado\ConcurrencyFramework\Domain\Annotation\AbstractAnnotation;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Annotation\AnnotationReader;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Annotation\SagaListener;
use Psr\Log\LoggerInterface;

/**
 * Annotation reader for saga event listeners
 */
class SagaEventListenersAnnotationReader
{
    /**
     * Annotation reader
     *
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param AnnotationReader $annotationReader
     * @param LoggerInterface  $logger
     */
    public function __construct(AnnotationReader $annotationReader, LoggerInterface $logger)
    {
        $this->annotationReader = $annotationReader;
        $this->logger = $logger;
    }

    /**
     * Extract event listeners annotation data
     *
     * @param string $sagaNamespace
     *
     * @return array
     */
    public function extractEventListenersAnnotation(string $sagaNamespace): array
    {
        $list = [];
        $annotations = $this->annotationReader->loadClassMethodsAnnotation($sagaNamespace);

        foreach($annotations as $annotationData)
        {
            try
            {
                /** @var AbstractAnnotation $annotation */
                $annotation = $annotationData['annotation'];

                self::guardAnnotationType($annotation, $sagaNamespace);

                /** @var SagaListener $annotation */

                self::guardContainingExpectedProperties($annotationData, $sagaNamespace);
                self::guardFirstArgumentIsEvent($sagaNamespace, $annotationData['arguments']);

                $list[] = $annotationData;
            }
            catch(\Throwable $throwable)
            {
                $this->logger->error(ThrowableFormatter::toString($throwable));
            }
        }

        return $list;
    }

    /**
     * Assert correct annotation type
     *
     * @param AbstractAnnotation $annotation
     * @param string             $saga
     *
     * @return void
     *
     * @throws EventListenerAnnotationException
     */
    private static function guardAnnotationType(AbstractAnnotation $annotation, string $saga): void
    {
        if(false === ($annotation instanceof SagaListener))
        {
            throw new EventListenerAnnotationException(
                \sprintf('Unsupported annotation specified ("%s") for saga "%s"', \get_class($annotation), $saga)
            );
        }
    }

    /**
     * Assert correct handler arguments
     *
     * @param string                 $saga
     * @param \ReflectionParameter[] $parameters
     *
     * @return void
     *
     * @throws EventListenerAnnotationException
     */
    private static function guardFirstArgumentIsEvent(string $saga, array $parameters): void
    {
        if(
            false === isset($parameters[0]) ||
            null === $parameters[0]->getClass() ||
            false === $parameters[0]->getClass()->implementsInterface(EventInterface::class)
        )
        {
            throw new EventListenerAnnotationException(
                \sprintf(
                    'The event handler for the saga "%s" should take the first argument to the object '
                    . 'that implements the "%s" interface',
                    $saga, EventInterface::class
                )
            );
        }
    }

    /**
     * Assert expected fields specified
     *
     * @param SagaListener $annotation
     * @param string       $saga
     *
     * @return void
     *
     * @throws EventListenerAnnotationException
     */
    private static function guardContainingExpectedProperties(SagaListener $annotation, string $saga): void
    {
        if('' === (string) $annotation->containingIdentityProperty)
        {
            throw new EventListenerAnnotationException(
                \sprintf(
                    '"containingIdentityProperty" value must be specified for saga "%s"', $saga
                )
            );
        }


        if('' === (string) $annotation->identityNamespace)
        {
            throw new EventListenerAnnotationException(
                \sprintf(
                    '"identityNamespace" value must be specified for saga "%s"', $saga
                )
            );
        }

        if(false === \class_exists($annotation->identityNamespace))
        {
            throw new EventListenerAnnotationException(
                \sprintf(
                    '"identityNamespace" value must be contains exists identity class for saga "%s"', $saga
                )
            );
        }
    }


}