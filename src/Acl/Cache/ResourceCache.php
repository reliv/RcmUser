<?php

namespace RcmUser\Acl\Cache;

use RcmUser\Acl\Builder\AclResourceBuilder;
use RcmUser\Acl\Entity\AclResource;
use Zend\Cache\Storage\StorageInterface;

/**
 * Class ResourceCache
 *
 * PHP version 5
 *
 * @category  Reliv
 * @package   Resource
 * @author    James Jervis <jjervis@relivinc.com>
 * @copyright 2016 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: <package_version>
 * @link      https://github.com/reliv
 */
class ResourceCache
{
    /**
     * ['resourceId': '{AclResource}']
     *
     * @var StorageInterface
     */
    protected $resourceStorage;

    /**
     * ['providerId': ['resourceId']]
     *
     * @var StorageInterface
     */
    protected $providerIndexStorage;

    /**
     * @var AclResourceBuilder
     */
    protected $aclResourceBuilder;

    /**
     * Resource constructor.
     *
     * @param StorageInterface $resourceStorage
     * @param StorageInterface $providerIndexStorage
     */
    public function __construct(
        StorageInterface $resourceStorage,
        StorageInterface $providerIndexStorage,
        AclResourceBuilder $aclResourceBuilder
    ) {
        $this->resourceStorage = $resourceStorage;
        $this->providerIndexStorage = $providerIndexStorage;
        $this->aclResourceBuilder = $aclResourceBuilder;
    }

    /**
     * get
     *
     * @param string $resourceId
     *
     * @return AclResource|null
     */
    public function get($resourceId)
    {
        $resourceId = md5($resourceId);
        $resource = $this->resourceStorage->getItem($resourceId);
        return json_decode($resource, true);
    }

    /**
     * set
     *
     * @param AclResource $resource
     *
     * @return void
     */
    public function set(AclResource $resource)
    {
        $resourceId = $resource->getResourceId();
        $resourceId = md5($resourceId);
        $this->resourceStorage->setItem($resourceId, json_encode($resource));
    }

    /**
     * setProviderResources
     *
     * @param string $providerId
     * @param array  $resources Expects ALL Resource for the provider
     *
     * @return void
     */
    public function setProviderResources($providerId, $resources)
    {
        $providerId = md5($providerId);
        $this->providerIndexStorage->setItem($providerId, json_encode($resources));
    }

    /**
     * getProviderResources
     *
     * @param string $providerId
     *
     * @return array|null
     * @throws \Exception
     */
    public function getProviderResources($providerId)
    {
        $providerId = md5($providerId);
        $resourceIds = $this->providerIndexStorage->getItem($providerId);

        if ($resourceIds === null) {
            return null;
        }

        $resourceIds = json_decode($resourceIds, true);

        $resources = [];

        foreach ($resourceIds as $resourceData) {
            $resource = $this->get($resourceData['resourceId']);
            if ($resource == null) {
                throw new \Exception('Resource could not be found in cache');
            }

            $resources[$resourceData['resourceId']] = $resource;
        }

        return $resources;
    }
}
