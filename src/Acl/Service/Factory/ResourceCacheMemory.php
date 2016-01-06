<?php

namespace RcmUser\Acl\Service\Factory;

use RcmUser\Acl\Cache\ResourceCache;
use Zend\Cache\Storage\Adapter\Memory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ResourceCacheMemory
 *
 * PHP version 5
 *
 * @category  Reliv
 * @package   RcmUser\Acl\Service\Factory
 * @author    James Jervis <jjervis@relivinc.com>
 * @copyright 2016 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: <package_version>
 * @link      https://github.com/reliv
 */
class ResourceCacheMemory implements FactoryInterface
{
    /**
     * createService
     *
     * @param ServiceLocatorInterface $serviceLocator serviceLocator
     *
     * @return mixed|\RcmUser\Config\Config
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {

        $resourceStorage = new Memory();
        $resourceStorage = new \Zend\Cache\Storage\Adapter\Filesystem();
        $resourceStorage->setOptions(['cacheDir' => __DIR__ . '/../../../../../../../data/rcmuser/resources']);

        $providerIndexStorage = new Memory();
        $providerIndexStorage = new \Zend\Cache\Storage\Adapter\Filesystem();
        $providerIndexStorage->setOptions(['cacheDir' => __DIR__ . '/../../../../../../../data/rcmuser/providerindex']);

        /** @var \RcmUser\Acl\Entity\RootAclResource $rootResource */
        $rootResource = $serviceLocator->get(
            'RcmUser\Acl\RootAclResource'
        );

        $resourceBuilder = new \RcmUser\Acl\Builder\AclResourceBuilder(
            $rootResource
        );

        $service = new ResourceCache(
            $resourceStorage,
            $providerIndexStorage,
            $resourceBuilder
        );

        return $service;
    }
}
