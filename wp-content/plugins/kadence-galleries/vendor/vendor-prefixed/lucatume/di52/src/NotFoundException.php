<?php
/**
 * An exception used to signal no binding was found for container ID.
 *
 * @package lucatume\DI52
 *
 * @license GPL-3.0
 * Modified by kadencewp on 08-January-2025 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace KadenceWP\KadenceGalleries\lucatume\DI52;

use KadenceWP\KadenceGalleries\Psr\Container\NotFoundExceptionInterface;

/**
 * Class NotFoundException
 *
 * @package lucatume\DI52
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
