<?php
/**
 * An exception used to signal no binding was found for container ID.
 *
 * @package lucatume\DI52
 *
 * @license GPL-3.0
 * Modified using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace KadenceWP\CloudPages\lucatume\DI52;

use KadenceWP\CloudPages\Psr\Container\NotFoundExceptionInterface;

/**
 * Class NotFoundException
 *
 * @package lucatume\DI52
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
