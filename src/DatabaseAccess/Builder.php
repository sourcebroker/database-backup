<?php

namespace SourceBroker\DatabaseBackup\DatabaseAccess;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Builder
 */
class Builder {

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $settings
     * @param $key
     * @return ProviderInterface
     */
    public function getInstance($settings, $key)
    {
        $object = null;
        switch ($settings['databaseAccess']['type']) {
            case 'env':
                $object = new EnvProvider($settings, $key);
                break;
            case 'xml':
                $object = new XmlProvider($settings, $key);
                break;
            case 'php':
                $object = new PhpProvider($settings, $key);
                break;
            default;
                $object = new DefaultProvider($settings, $key);
        }

        return $object;
    }

}