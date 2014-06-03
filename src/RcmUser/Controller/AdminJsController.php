<?php
/**
 * AdminJsController.php
 *
 * LongDescHere
 *
 * PHP version 5
 *
 * @category  Reliv
 * @package   RcmUser\Controller
 * @author    James Jervis <jjervis@relivinc.com>
 * @copyright 2014 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 * @link      https://github.com/reliv
 */

namespace RcmUser\Controller;

use Zend\Http\Response;
use Zend\View\Model\ViewModel;


/**
 * Class AdminJsController
 *
 * LongDescHere
 *
 * PHP version 5
 *
 * @category  Reliv
 * @package   RcmUser\Controller
 * @author    James Jervis <jjervis@relivinc.com>
 * @copyright 2014 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: <package_version>
 * @link      https://github.com/reliv
 */
class AdminJsController extends AbstractAdminController
{
    /**
     * adminAclApp
     *
     * @return void
     */
    public function adminAclAction()
    {
        // ACCESS CHECK
        if (!$this->isAllowed('rcmuser-acl-administration', 'read')) {
            return $this->getNotAllowedResponse();
        }

        $aclResourceService = $this->getServiceLocator()->get(
            'RcmUser\Acl\Service\AclResourceService'
        );
        /** @var $aclDataService \RcmUser\Acl\Service\AclDataService */
        $aclDataService = $this->getServiceLocator()->get(
            'RcmUser\Acl\AclDataService'
        );

        $resources = $aclResourceService->getResourcesWithNamespace();
        $roles = $aclDataService->getRulesByRoles();
        $superAdminRoleId = $aclDataService->getSuperAdminRoleId();
        $guestRoleId = $aclDataService->getGuestRoleId();

        $viewModel = new ViewModel(
            array(
                'resources' => $resources,
                'roles' => $roles,
                'superAdminRoleId' => $superAdminRoleId,
                'guestRoleId' => $guestRoleId,
            )
        );
        $viewModel->setTemplate('js/rcmuser.admin.acl.app.js');
        $viewModel->setTerminal(true);

        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_200);
        $response->getHeaders()->addHeaders(
            array(
                'Content-Type' => 'application/javascript'
            )
        );

        return $viewModel;
    }

    /**
     * adminAclApp
     *
     * @return void
     */
    public function adminUsersAction()
    {
        // ACCESS CHECK
        if (!$this->isAllowed('rcmuser-acl-administration', 'read')) {
            return $this->getNotAllowedResponse();
        }

        /** @var \RcmUser\User\Service\UserDataService $userDataService */
        $userDataService = $this->getServiceLocator()->get(
            'RcmUser\User\Service\UserDataService'
        );

        $result = $userDataService->fetchAll();

        $viewModel = new ViewModel(
            array(
                'users' => $result->getData()
            )
        );


        $viewModel->setTemplate('js/rcmuser.admin.users.app.js');
        $viewModel->setTerminal(true);

        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_200);
        $response->getHeaders()->addHeaders(
            array(
                'Content-Type' => 'application/javascript'
            )
        );

        return $viewModel;
    }
} 