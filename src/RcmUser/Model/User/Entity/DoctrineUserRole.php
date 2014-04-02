<?php
 /**
 * @category  RCM
 * @author    James Jervis <jjervis@relivinc.com>
 * @copyright 2012 Reliv International
 * @license   License.txt New BSD License
 * @version   GIT: reliv
 * @link      http://ci.reliv.com/confluence
 */

namespace RcmUser\Model\User\Entity;


/**
 * Class DoctrineUser
 *
 * @package RcmUser\Model\User\Entity
 *
 * @ORM\Entity
 * @ORM\Table(name="rcm_user_user")
 */
class DoctrineUserRole extends UserRole {

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer", unique=true, nullable=false)
     */
    protected $id;
    /**
     * @var string
     * @ORM\Column(type="integer",nullable=false)
     */
    protected $userId;
    /**
     * @var string
     * @ORM\Column(type="integer",nullable=false)
     */
    protected $roleId;
} 