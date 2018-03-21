<?php
/*
 * This file is part of the Sidus/EAVPermissionBundle package.
 *
 * Copyright (c) 2015-2018 Vincent Chalnot
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sidus\EAVPermissionBundle\Voter;

use Sidus\EAVModelBundle\Model\AttributeInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Checks if an attribute is readable or editable
 */
class AttributeVoter implements VoterInterface
{
    public const READ = 'read';
    public const EDIT = 'edit';
    public const ATTRIBUTES = [self::READ, self::EDIT];

    /** @var VoterInterface */
    protected $roleHierarchyVoter;

    /**
     * @param VoterInterface $roleHierarchyVoter
     */
    public function __construct(VoterInterface $roleHierarchyVoter)
    {
        $this->roleHierarchyVoter = $roleHierarchyVoter;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        if (!$object instanceof AttributeInterface) {
            return $result;
        }
        $permissions = $object->getOption('permissions');
        if (empty($permissions)) {
            return VoterInterface::ACCESS_GRANTED; // No permissions means always editable (thus readable)
        }

        if (1 !== \count($attributes)) {
            throw new \UnexpectedValueException('Attribute permission voter only supports one permission at a time');
        }
        $attribute = reset($attributes);
        if (!\in_array($attribute, static::ATTRIBUTES, true)) {
            throw new \UnexpectedValueException('Unsupported Attribute permission type '.$attribute);
        }

        $editable = $this->isPermissionAllowed($token, $permissions, static::EDIT);
        if (static::READ === $attribute) {
            // If you can edit you can read
            $readable = $editable || $this->isPermissionAllowed($token, $permissions, static::READ);

            return $readable ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;
        }

        return $editable ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED;
    }

    /**
     * @param TokenInterface $token
     * @param array          $permissions
     * @param string         $permission
     *
     * @return bool
     */
    protected function isPermissionAllowed(TokenInterface $token, array $permissions, $permission)
    {
        if (!array_key_exists($permission, $permissions)) {
            return false; // If no behavior was defined for specific permission, forbid permission
        }

        $roles = $permissions[$permission];
        foreach ((array) $roles as $role) {
            if (VoterInterface::ACCESS_GRANTED === $this->roleHierarchyVoter->vote($token, null, [$role])) {
                return true;
            }
        }

        return false;
    }
}
