<?php
/**
 * Для простоты оставил всё в одном файле. Я понимаю, что каждый класс должен быть в отдельном сосбственном файле :)
 */

namespace Integration {

    use Psr\Cache\CacheException;
    use Psr\Cache\CacheItemPoolInterface;
    use Psr\Log\LoggerInterface;

    interface DataProviderContract
    {
        /**
         * Get data from the service.
         * @param array $input
         * @return array
         */
        public function get(array $input): array;
    }

    class DataProvider implements DataProviderContract
    {
        /**
         * @var string
         */
        private $host;

        /**
         * @var string
         */
        private $user;

        /**
         * @var string
         */
        private $password;

        /**
         * @param $host
         * @param $user
         * @param $password
         */
        public function __construct(string $host, string $user, string $password)
        {
            $this->host = $host;
            $this->user = $user;
            $this->password = $password;
        }

        /**
         * @inheritdoc
         */
        public function get(array $request): array
        {
            // returns a response from external service
        }
    }

    class DataProviderDecorator
    {
        /**
         * @var DataProviderContract
         */
        private $provider;

        /**
         * @var CacheItemPoolInterface
         */
        private $cache;

        /**
         * @var LoggerInterface
         */
        private $logger;

        /**
         * @param DataProviderContract $provider
         * @param CacheItemPoolInterface $cache
         * @param LoggerInterface $logger
         */
        public function __construct(
            DataProviderContract $provider,
            CacheItemPoolInterface $cache,
            LoggerInterface $logger
        ) {
            $this->provider = $provider;
            $this->cache = $cache;
            $this->logger = $logger;
        }

        /**
         * Get data from data provider using cache.
         * @param array $input
         * @return array
         */
        public function getResponse(array $input): array
        {
            try {
                $cacheKey = $this->getCacheKey($input);
                $cacheItem = $this->cache->getItem($cacheKey);
                if (!$cacheItem->isHit()) {
                    $result = $this->provider->get($input);

                    $cacheItem
                        ->set($result)
                        ->expiresAt(
                            (new DateTime())->modify('+1 day')
                        );
                }
                return $cacheItem->get();
            } catch (CacheException $e) {
                $this->logger->critical('Error');
            }

            return [];
        }

        /**
         * Make a unique cache key for an input data array.
         * @param array $input
         * @return string
         */
        public function getCacheKey(array $input): string
        {
            return crc32(json_encode($input));
        }
    }
}