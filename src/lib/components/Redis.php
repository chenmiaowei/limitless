<?php
/**
 * Created by PhpStorm.
 * User: 陈妙威
 * Date: 2019/4/17
 * Time: 3:06 PM
 * Email: chenmiaowei0914@gmail.com
 */

namespace orangins\lib\components;

use Predis\Client;
use yii\base\Component;

/**
 * Class Redis
 * @package orangins\lib\components
 * @author 陈妙威
 */
class Redis extends Component
{
    /**
     * @var Client
     */
    public $client;

    /**
     * @var
     */
    public $host;

    /**
     * @var
     */
    public $port = 6379;

    /**
     * Redis constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $client = new Client([
            'scheme' => 'tcp',
            'host'   => $this->host,
            'port'   => $this->port,
        ]);
        $this->client = $client;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     * @return self
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }
}