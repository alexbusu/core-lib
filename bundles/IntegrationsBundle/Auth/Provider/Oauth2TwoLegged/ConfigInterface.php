<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Auth\Provider\Oauth2TwoLegged;

use Mautic\IntegrationsBundle\Auth\Provider\AuthConfigInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\TokenPersistenceInterface;
use Mautic\IntegrationsBundle\Auth\Provider\Oauth2ThreeLegged\TokenSignerInterface;
use Mautic\IntegrationsBundle\Auth\Support\Oauth2\ConfigAccess\ConfigCredentialsSignerInterface;

interface ConfigInterface extends AuthConfigInterface
{
    public function getClientCredentialsSigner(): ConfigCredentialsSignerInterface;

    public function getAccessTokenSigner(): TokenSignerInterface;

    public function getAccessTokenPersistence(): TokenPersistenceInterface;
}
