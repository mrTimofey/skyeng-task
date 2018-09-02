<?php

// src в namespace? Зачем?
namespace src\Integration;

// По-хорошему нужно сделать интерфейс типа DataProviderContract, чтобы DecoratorManager не зависел от этой реализации.
class DataProvider
{
    private $host;
    private $user;
    private $password;

    /**
     * @param $host
     * @param $user
     * @param $password
     */
    public function __construct($host, $user, $password)
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param array $request
     *
     * @return array
     */
    public function get(array $request)
    {
        // returns a response from external service
    }
}


// src в namespace? Зачем?
// И надо как-то приобщить декоратор к DataProvider, чтобы было понятно, что они друг к другу относятся.
namespace src\Decorator;

use DateTime;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use src\Integration\DataProvider;

// Этот класс не должен наследоваться от DataProvider, так как это декоратор (если судить по его названию и по тому,
// что он делает). Он должен получать реализацию DataProviderContract в качестве зависимости через конструктор.
// Название класса может ввести в заблуждение - понятно, что это декоратор (на самом деле нет), но непонятно, что
// означает Manager.
class DecoratorManager extends DataProvider
{
    // Модфикаторы доступа этих полей должны быть protected или вообще private, так как за границей класса
    // или (вероятно) его наследников эти поля не должны использоваться
    public $cache;
    public $logger;

    // Возможно, стоит добавить LoggerInterface как параметр конструктора. Ну и первые 3 параметра заменяются на
    // реализацию DataProviderContract, раз уж это декоратор.
    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param CacheItemPoolInterface $cache
     */
    public function __construct($host, $user, $password, CacheItemPoolInterface $cache)
    {
        parent::__construct($host, $user, $password);
        $this->cache = $cache;
    }

    // Странно, что есть метод setLogger, но нет setCache.
    // Возможно, стоит этот метод вообще убрать и указывать logger только в конструкторе.
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    // @inheritdoc тут не работает, так как у родительского класса нет такого метода.

    /**
     * {@inheritdoc}
     */
    public function getResponse(array $input)
    {
        try {
            $cacheKey = $this->getCacheKey($input);
            $cacheItem = $this->cache->getItem($cacheKey);
            // Можно немного улучшить код, если сделать условие if (!$cacheItem->isHit()) ...
            if ($cacheItem->isHit()) {
                return $cacheItem->get();
            }

            $result = parent::get($input);

            $cacheItem
                ->set($result)
                ->expiresAt(
                    (new DateTime())->modify('+1 day')
                );

            return $result;
        }
        // Стоит специфицировать до \Psr\Cache\CacheException (если предположить, что parent::get() ничего не бросает,
        // тогда нужно и его исключения обрабатывать)
        catch (Exception $e) {
            $this->logger->critical('Error');
        }

        return [];
    }

    public function getCacheKey(array $input)
    {
        // Наверно, лучше из этого хэш взять, чтобы ключ был покороче и быстрее обрабатывался кэш-хранилищем
        return json_encode($input);
    }
}

// В PHP 7.0 есть возвращаемые типы, в PHP 7.1 есть nullable - нужно это использовать по максимуму, чтобы
// избежать ошибок в дальнейшем.