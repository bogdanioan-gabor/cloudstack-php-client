<?php namespace MyENA\CloudStackClientGenerator\Generator;

use Http\Client\HttpClient;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class CloudStackConfiguration
 */
class CloudStackConfiguration implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var string */
    protected $apiKey = '';
    /** @var string */
    protected $secretKey = '';
    /** @var string  */
    protected $scheme = 'http';
    /** @var string */
    protected $host = '';
    /** @var int */
    protected $port = null;
    /** @var string */
    protected $pathPrefix = 'client/api';

    /** @var \Http\Client\HttpClient */
    public $HttpClient = null;

    /**
     * CloudStackConfiguration constructor.
     *
     * @param array $config
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(array $config = [], LoggerInterface $logger = null)
    {
        if (null === $logger)
            $this->logger = new NullLogger();
        else
            $this->logger = $logger;

        foreach($config as $k => $v)
        {
            if ('endpoint' === $k)
            {
                $url = parse_url($v);
                if (false === $url || !isset($url['host']))
                    throw new \InvalidArgumentException('"endpoint" is not a valid URL value.');

                $this->setHost($url['host']);

                if (isset($url['scheme']))
                    $this->setScheme($url['scheme']);
                if (isset($url['port']))
                    $this->setPort((int)$url['port']);
                if (isset($url['path']))
                    $this->setPathPrefix($url['path']);
            }
            else if (false === strpos($k, '_'))
            {
                $this->{'set'.ucfirst($k)}($v);
            }
            else
            {
                $this->{'set'.implode('', array_map('ucfirst', explode('_', $k)))}($v);
            }
        }

        $this->postConstructValidation();
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     * @return CloudStackConfiguration
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }

    /**
     * @param string $secretKey
     * @return CloudStackConfiguration
     */
    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @param string $scheme
     * @return CloudStackConfiguration
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return CloudStackConfiguration
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return CloudStackConfiguration
     */
    public function setPort($port)
    {
        $port = intval($port);
        if (0 === $port)
            $this->port = null;
        else
            $this->port = $port;

        return $this;
    }

    /**
     * @return string
     */
    public function getPathPrefix()
    {
        return $this->pathPrefix;
    }

    /**
     * @param string $pathPrefix
     * @return CloudStackConfiguration
     */
    public function setPathPrefix($pathPrefix)
    {
        $this->pathPrefix = trim($pathPrefix, " \t\n\r\0\x0B/");
        return $this;
    }

    /**
     * @return \Http\Client\HttpClient
     */
    public function getHttpClient()
    {
        return $this->HttpClient;
    }

    /**
     * @param \Http\Client\HttpClient $HttpClient
     * @return CloudStackConfiguration
     */
    public function setHttpClient(HttpClient $HttpClient)
    {
        $this->HttpClient = $HttpClient;
        return $this;
    }

    /**
     * @param string $query
     * @return string
     * @throws \Exception
     */
    public function buildSignature($query)
    {
        if ('' === $query)
            throw new \Exception(STRTOSIGN_EMPTY_MSG, STRTOSIGN_EMPTY);

        $hash = @hash_hmac('SHA1', strtolower($query), $this->getSecretKey(), true);
        return urlencode(base64_encode($hash));
    }

    protected function postConstructValidation()
    {
        static $knownClients = array(
            '\\Http\\Client\\Curl\\Client',
            '\\Http\\Adapter\\Guzzle6\\Client',
            '\\Http\\Adapter\\Guzzle5\\Client',
            '\\Http\\Adapter\\Buzz\\Client'
        );

        foreach($knownClients as $clientClass)
        {
            if (class_exists($clientClass, true))
            {
                $this->HttpClient = new $clientClass;
                break;
            }
        }

        if (null === $this->HttpClient)
            throw new \RuntimeException(HTTPCLIENT_EMPTY_MSG, HTTPCLIENT_EMPTY);

        if ('' === $this->host)
            throw new \RuntimeException(ENDPOINT_EMPTY_MSG, ENDPOINT_EMPTY);

        if ('' === $this->apiKey)
            throw new \RuntimeException(APIKEY_EMPTY_MSG, APIKEY_EMPTY);

        if ('' === $this->secretKey)
            throw new \RuntimeException(SECRETKEY_EMPTY_MSG, SECRETKEY_EMPTY);
    }
}