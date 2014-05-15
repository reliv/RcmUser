<?php
/**
 * AuthorizeService.php
 *
 * AuthorizeService
 *
 * PHP version 5
 *
 * @category  Reliv
 * @package   RcmUser\Acl\Service
 * @author    James Jervis <jjervis@relivinc.com>
 * @copyright 2014 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 * @link      https://github.com/reliv
 */

namespace RcmUser\Acl\Service;

use RcmUser\Acl\Db\AclRoleDataMapper;
use RcmUser\Acl\Db\AclRoleDataMapperInterface;
use RcmUser\Acl\Db\AclRuleDataMapperInterface;
use RcmUser\Acl\Entity\AclRule;
use RcmUser\Exception\RcmUserException;
use RcmUser\User\Db\UserRolesDataMapper;
use RcmUser\User\Entity\User;
use Zend\Permissions\Acl\Acl;


/**
 * Class AuthorizeService
 *
 * AuthorizeService
 *
 * PHP version 5
 *
 * @category  Reliv
 * @package   RcmUser\Acl\Service
 * @author    James Jervis <jjervis@relivinc.com>
 * @copyright 2014 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: <package_version>
 * @link      https://github.com/reliv
 */
class AuthorizeService extends UserAuthorizeService
{
    /**
     * @var string RESOURCE_DELIMITER
     */
    const RESOURCE_DELIMITER = '.';

    /**
     * @var Acl $acl
     */
    protected $acl;

    /**
     * @var AclResourceService $aclResourceService
     */
    protected $aclResourceService;

    /**
     * @var AclRoleDataMapperInterface $aclRoleDataMapper
     */
    protected $aclRoleDataMapper;

    /**
     * @var AclRuleDataMapperInterface $aclRuleDataMapper
     */
    protected $aclRuleDataMapper;

    /**
     * @var string $superAdminRole
     */
    protected $superAdminRole = null;

    /**
     * setAclResourceService
     *
     * @param $aclResourceService
     *
     * @return void
     */
    public function setAclResourceService(AclResourceService $aclResourceService)
    {
        $this->aclResourceService = $aclResourceService;
    }

    /**
     * getAclResourceService
     *
     * @return AclResourceService
     */
    public function getAclResourceService()
    {
        return $this->aclResourceService;
    }

    /**
     * setAclRoleDataMapper
     *
     * @param AclRoleDataMapperInterface $aclRoleDataMapper
     *
     * @return void
     */
    public function setAclRoleDataMapper(AclRoleDataMapperInterface $aclRoleDataMapper)
    {
        $this->aclRoleDataMapper = $aclRoleDataMapper;
    }

    /**
     * getAclRoleDataMapper
     *
     * @return AclRoleDataMapperInterface
     */
    public function getAclRoleDataMapper()
    {
        return $this->aclRoleDataMapper;
    }

    /**
     * setAclRuleDataMapper
     *
     * @param AclRuleDataMapperInterface $aclRuleDataMapper
     *
     * @return void
     */
    public function setAclRuleDataMapper(AclRuleDataMapperInterface $aclRuleDataMapper)
    {
        $this->aclRuleDataMapper = $aclRuleDataMapper;
    }

    /**
     * getAclRuleDataMapper
     *
     * @return AclRuleDataMapperInterface
     */
    public function getAclRuleDataMapper()
    {
        return $this->aclRuleDataMapper;
    }

    /**
     * setSuperAdminRole
     *
     * @param $superAdminRole
     *
     * @return void
     */
    public function setSuperAdminRole($superAdminRole)
    {
        $this->superAdminRole = $superAdminRole;
    }

    /**
     * getSuperAdminRole
     *
     * @return string
     */
    public function getSuperAdminRole()
    {
        return $this->superAdminRole;
    }

    /**
     * getRoles
     *
     * @return array
     */
    public function getRoles()
    {
        $result = $this->aclRoleDataMapper->fetchAll();

        if (!$result->isSuccess()) {

            // @todo Throw error?
            return array();
        }

        return $result->getData();
    }

    /**
     * getUserRoles
     *
     * @param User $user user
     *
     * @return null
     */
    public function getUserRoles(User $user)
    {
        return $user->getProperty(UserRolesDataMapper::PROPERTY_KEY, array());
    }

    /**
     * getRules
     *
     * @return array
     */
    public function getRules()
    {
        $result = $this->aclRuleDataMapper->fetchAll();

        if (!$result->isSuccess()) {

            // @todo Throw error?
            return array();
        }

        return $result->getData();
    }

    /**
     * getResources
     *
     * @return array
     */
    public function getResources()
    {
        return $this->getAclResourceService()->getResources();
    }

    /**
     * getAcl
     *
     * @return Acl
     */
    public function getAcl()
    {

        if (!isset($this->acl)) {

            $this->buildAcl();
        }

        /* resources privileges
            we load the every time so they maybe updated dynamically
        */
        $resources = $this->getResources();

        foreach ($resources as $resource) {

            if (!$this->acl->hasResource($resource)) {

                $this->acl->addResource($resource, $resource->getParentResource());
            }

            $privileges = $resource->getPrivileges();

            if (!empty($privileges)) {

                foreach ($privileges as $privilege) {
                    if (!$this->acl->hasResource($privilege)) {

                        $this->acl->addResource($privilege, $resource);
                    }
                }
            }
        }

        // rules
        $rules = $this->getRules();

        foreach ($rules as $rule) {

            if ($rule->getRule() == AclRule::RULE_ALLOW) {

                $this->acl->allow(
                    $rule->getRoleId(),
                    $rule->getResource(),
                    $rule->getPrivilege(),
                    $rule->getAssertion()
                );
            } elseif ($rule->getRule() == AclRule::RULE_DENY) {

                $this->acl->deny(
                    $rule->getRoleId(),
                    $rule->getResource(),
                    $rule->getPrivilege(),
                    $rule->getAssertion()
                );
            }
        }

        return $this->acl;
    }

    /**
     * buildAcl
     *
     * @return void
     */
    public function buildAcl()
    {

        $this->acl = new Acl();

        // roles
        $roles = $this->getRoles();

        foreach ($roles as $role) {

            if ($this->acl->hasRole($role)) {
                // @todo throw error?
                continue;
            }

            $this->acl->addRole($role, $role->getParent());
        }
    }

    /**
     * isAllowed
     *
     * @param string $resource  resource
     * @param string $privilege privilege
     * @param User   $user      user
     *
     * @return bool
     */
    public function isAllowed($resource, $privilege = null, $user = null)
    {
        if(!($user instanceof User)){

            return false;
        }

        $userRoles = $this->getUserRoles($user);

        /* Check super admin
            we over-ride everything if user has super admin
        */
        if (!empty($this->superAdminRole)
            && is_array($userRoles)
            && in_array($this->superAdminRole, $userRoles)
        ) {

            return true;
        }

        $resources = $this->parseResource($resource);
        $acl = $this->getAcl();

        foreach ($resources as $res) {
            foreach ($userRoles as $userRole) {
                $result = $acl->isAllowed(
                    $userRole,
                    $res,
                    $privilege
                );

                if ($result) {
                    return $result;
                }
            }
        }

        return false;
    }

    /**
     * parseResource
     * This allows use to parse our dot notation for nested resources
     * which is used when a missing resource can inherit.
     *
     * To do this we need to provide the resource and its parent.
     * We accomplish this by passing 'PAGES.PAGE_X'.
     * Our isAllowed override allows the checking of 'PAGE_X' first and
     * if it is not found, we check 'PAGES'.
     *
     * Example:
     *  If a resource called 'PAGES'
     *  And we want to check if the user has access
     * to a child of 'PAGES' named 'PAGE_X'.
     *  And we know at the time of the ACL check
     * that 'PAGE_X' might not be defined.
     *  If 'PAGE_X' is not defined, then we inherit from from 'PAGES'
     *
     * @param string $resource resource
     *
     * @return array
     */
    public function parseResource($resource)
    {
        if (is_string($resource)) {

            $resources = explode(self::RESOURCE_DELIMITER, $resource);

            $resources = array_reverse($resources);

            return $resources;
        }

        return array($resource);
    }
} 