<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
final class CacheItem implements CacheItemInterface
{
    /**
     * @internal
     */
    const CAST_PREFIX = "\0Symfony\Component\Cache\CacheItem\0";

    private $key;
    private $value;
    private $isHit;
    private $lifetime;
    private $defaultLifetime;

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit()
    {
        return $this->isHit;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration)
    {
        if (null === $expiration) {
            $this->lifetime = $this->defaultLifetime;
        } elseif ($expiration instanceof \DateTimeInterface) {
            $this->lifetime = $expiration->format('U') - time() ?: -1;
        } else {
            throw new InvalidArgumentException(sprintf('Expiration date must implement DateTimeInterface or be null, "%s" given', is_object($expiration) ? get_class($expiration) : gettype($expiration)));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time)
    {
        if (null === $time) {
            $this->lifetime = $this->defaultLifetime;
        } elseif ($time instanceof \DateInterval) {
            $now = time();
            $this->lifetime = \DateTime::createFromFormat('U', $now)->add($time)->format('U') - $now ?: -1;
        } elseif (is_int($time)) {
            $this->lifetime = $time ?: -1;
        } else {
            throw new InvalidArgumentException(sprintf('Expiration date must be an integer, a DateInterval or null, "%s" given', is_object($time) ? get_class($time) : gettype($time)));
        }

        return $this;
    }

    /**
     * Internal logging helper.
     *
     * @internal
     */
    public static function log(LoggerInterface $logger = null, $message, $context = array())
    {
        if ($logger) {
            $logger->warning($message, $context);
        } else {
            $replace = array();
            foreach ($context as $k => $v) {
                if (is_scalar($v)) {
                    $replace['{'.$k.'}'] = $v;
                }
            }
            @trigger_error(strtr($message, $replace), E_USER_WARNING);
        }
    }
}
