<?php

namespace RcmUser\Api\User;

use RcmUser\User\Entity\UserInterface;
use RcmUser\User\Result;

/**
 * @author James Jervis - https://github.com/jerv13
 */
interface UpdateUserResult
{
    /**
     * @param UserInterface $requestUser
     *
     * @return Result
     */
    public function __invoke(
        UserInterface $requestUser
    ): Result;
}
