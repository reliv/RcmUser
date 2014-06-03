<?php
/**
 * UserDataService.php
 *
 * CRUD Operations
 *
 * PHP version 5
 *
 * @category  Reliv
 * @package   RcmUser\User\Service
 * @author    James Jervis <jjervis@relivinc.com>
 * @copyright 2014 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: <git_id>
 * @link      https://github.com/reliv
 */

namespace RcmUser\User\Service;

use RcmUser\Event\EventProvider;
use RcmUser\User\Db\UserDataMapperInterface;
use RcmUser\User\Entity\ReadOnlyUser;
use RcmUser\User\Entity\User;
use RcmUser\User\Result;

/**
 * Class UserDataService
 *
 * CRUD Operations
 *
 * PHP version 5
 *
 * @category  Reliv
 * @package   RcmUser\User\Service
 * @author    James Jervis <jjervis@relivinc.com>
 * @copyright 2014 Reliv International
 * @license   License.txt New BSD License
 * @version   Release: <package_version>
 * @link      https://github.com/reliv
 */
class UserDataService extends EventProvider
{
    /**
     * @var UserDataMapperInterface
     */
    protected $userDataMapper;

    /**
     * setUserDataMapper
     *
     * @param UserDataMapperInterface $userDataMapper userDataMapper
     *
     * @return void
     */
    public function setUserDataMapper(UserDataMapperInterface $userDataMapper)
    {
        $this->userDataMapper = $userDataMapper;
    }

    /**
     * getUserDataMapper
     *
     * @return UserDataMapperInterface
     */
    public function getUserDataMapper()
    {
        return $this->userDataMapper;
    }

    /**
     * fetchAll
     *
     * @param array $params params
     *
     * @return mixed
     */
    public function fetchAll(
        $params = array()
    ) {

        return $this->userDataMapper->fetchAll($params);
    }

    /**
     * fetchById
     *
     * @param mixed $id id
     *
     * @return \RcmUser\User\Result
     */
    public function fetchById(
        $id
    ) {

        return $this->userDataMapper->fetchById($id);
    }

    /**
     * fetchByUsername
     *
     * @param string $username username
     *
     * @return \RcmUser\User\Result
     */
    public function fetchByUsername(
        $username
    ) {

        return $this->userDataMapper->fetchById($username);
    }

    /**
     * buildUser - Allows events listeners to set default values for a new
     * user as needed.  Very helpful for creating guest or ambiguous users
     *
     * @param User $requestUser requestUser
     *
     * @return Result
     */
    public function buildUser(User $requestUser)
    {

        /* + LOW_LEVEL_PREP */
        $responseUser = new User();
        $responseUser->populate($requestUser);

        $requestUser = new ReadOnlyUser($requestUser);
        /* - LOW_LEVEL_PREP */

        /* @event buildUser */
        $results = $this->getEventManager()->trigger(
            'buildUser',
            $this,
            array(
                'requestUser' => $requestUser,
                'responseUser' => $responseUser
            )
        );

        return $results->last();
    }

    /**
     * createUser
     *
     * @param User $requestUser requestUser
     *
     * @return Result
     */
    public function createUser(User $requestUser)
    {
        /* + LOW_LEVEL_PREP */
        $result = $this->readUser($requestUser);

        if ($result->isSuccess()) {

            // ERROR - user exists
            return new Result(null, Result::CODE_FAIL, 'User already exists.');
        }

        $responseUser = new User();
        $responseUser->populate($requestUser);

        $requestUser = new ReadOnlyUser($requestUser);
        /* - LOW_LEVEL_PREP */

        /* @event beforeCreateUser */
        $results = $this->getEventManager()->trigger(
            'beforeCreateUser',
            $this,
            array(
                'requestUser' => $requestUser,
                'responseUser' => $responseUser
            ),
            function ($result) {
                return !$result->isSuccess();
            }
        );

        if ($results->stopped()) {

            return $results->last();
        }

        /* @event createUser */
        $results = $this->getEventManager()->trigger(
            'createUser',
            $this,
            array(
                'requestUser' => $requestUser,
                'responseUser' => $responseUser
            ),
            function ($result) {
                return !$result->isSuccess();
            }
        );

        if ($results->stopped()) {

            $result = $results->last();

            $this->getEventManager()->trigger(
                'createUserFail',
                $this,
                array('result' => $result)
            );

            return $result;
        }

        $result = new Result($responseUser);

        if (!$result->isSuccess()) {
            $this->getEventManager()->trigger(
                'createUserFail',
                $this,
                array('result' => $result)
            );

            return $result;
        }

        /* @event createUserSuccess */
        $this->getEventManager()->trigger(
            'createUserSuccess',
            $this,
            array('result' => $result)
        );

        return $result;
    }

    /**
     * readUser
     *
     * @param User $requestUser requestUser
     *
     * @return Result
     */
    public function readUser(User $requestUser)
    {
        $responseUser = new User();
        $responseUser->populate($requestUser);

        $requestUser = new ReadOnlyUser($requestUser);

        /* @event beforeReadUser */
        $results = $this->getEventManager()->trigger(
            'beforeReadUser',
            $this,
            array(
                'requestUser' => $requestUser,
                'responseUser' => $responseUser
            ),
            function ($result) {
                return !$result->isSuccess();
            }
        );

        if ($results->stopped()) {

            return $results->last();
        }

        /* @event readUser */
        $results = $this->getEventManager()->trigger(
            'readUser',
            $this,
            array(
                'requestUser' => $requestUser,
                'responseUser' => $responseUser
            ),
            function ($result) {
                return !$result->isSuccess();
            }
        );

        if ($results->stopped()) {

            $result = $results->last();
            $this->getEventManager()->trigger(
                'readUserFail',
                $this,
                array('result' => $result)
            );

            return $result;
        }

        $result = new Result($responseUser);

        /* @event readUserSuccess */
        $this->getEventManager()->trigger(
            'readUserSuccess',
            $this,
            array('result' => $result)
        );

        return $result;
    }

    /**
     * updateUser
     *
     * @param User $requestUser requestUser
     *
     * @return Result
     */
    public function updateUser(User $requestUser)
    {
        /* + LOW_LEVEL_PREP */
        // require id
        if (empty($requestUser->getId())) {

            return new Result(
                null,
                Result::CODE_FAIL,
                'User Id required for update.'
            );
        }

        // check if exists
        $existingUserResult = $this->readUser($requestUser);

        if (!$existingUserResult->isSuccess()) {

            // ERROR
            return $existingUserResult;
        }

        $existingUser = $existingUserResult->getUser();

        $existingUser = new ReadOnlyUser($existingUser);

        $requestUser->merge($existingUser);

        $responseUser = new User();

        $responseUser->populate($requestUser);

        $requestUser = new ReadOnlyUser($requestUser);

        if (empty($responseUser->getState())) {
            $responseUser->setState($this->getDefaultUserState());
        }
        /* - LOW_LEVEL_PREP */

        /* @event beforeUpdateUser */
        $results = $this->getEventManager()->trigger(
            'beforeUpdateUser',
            $this,
            array(
                'requestUser' => $requestUser,
                'responseUser' => $responseUser,
                'existingUser' => $existingUser
            ),
            function ($result) {
                return !$result->isSuccess();
            }
        );

        if ($results->stopped()) {

            return $results->last();
        }

        /* @event updateUser */
        $results = $this->getEventManager()->trigger(
            'updateUser',
            $this,
            array(
                'requestUser' => $requestUser,
                'responseUser' => $responseUser,
                'existingUser' => $existingUser
            ),
            function ($result) {
                return !$result->isSuccess();
            }
        );

        if ($results->stopped()) {

            $result = $results->last();
            $this->getEventManager()->trigger(
                'updateUserFail',
                $this,
                array('result' => $result)
            );

            return $result;
        }

        $result = new Result($responseUser);

        /* @event updateUser */
        $this->getEventManager()->trigger(
            'updateUserSuccess',
            $this,
            array('result' => $result)
        );

        return $result;
    }

    /**
     * deleteUser
     *
     * @param User $requestUser requestUser
     *
     * @return mixed|Result
     */
    public function deleteUser(User $requestUser)
    {
        /* + LOW_LEVEL_PREP */
        // require id
        if (empty($requestUser->getId())) {

            return new Result(
                null,
                Result::CODE_FAIL,
                'User Id required for update.'
            );
        }

        // check if exists
        $existingUserResult = $this->readUser($requestUser);

        if (!$existingUserResult->isSuccess()) {

            // ERROR
            return $existingUserResult;
        }

        $responseUser = new User();

        $responseUser->populate($existingUserResult->getUser());

        $requestUser = new ReadOnlyUser($requestUser);
        /* - LOW_LEVEL_PREP */

        /* @event beforeDeleteUser */
        $results = $this->getEventManager()->trigger(
            'beforeDeleteUser',
            $this,
            array(
                'requestUser' => $requestUser,
                'responseUser' => $responseUser
            ),
            function ($result) {
                return !$result->isSuccess();
            }
        );

        if ($results->stopped()) {

            return $results->last();
        }

        /* @event deleteUser */
        $results = $this->getEventManager()->trigger(
            'deleteUser',
            $this,
            array(
                'requestUser' => $requestUser,
                'responseUser' => $responseUser
            ),
            function ($result) {
                return !$result->isSuccess();
            }
        );

        if ($results->stopped()) {

            $result = $results->last();
            $this->getEventManager()->trigger(
                'deleteUserFail',
                $this,
                array('result' => $result)
            );

            return $result;
        }

        $result = new Result($responseUser);

        /* @event deleteUserSuccess */
        $this->getEventManager()->trigger(
            'deleteUserSuccess',
            $this,
            array('result' => $result)
        );

        return $result;
    }

    /* USERS ******************* */

} 