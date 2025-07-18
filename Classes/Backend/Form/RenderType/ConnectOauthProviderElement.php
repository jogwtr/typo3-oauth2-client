<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Backend\Form\RenderType;

use CoStack\EasyRequestToken\Security\SecurityObjectsFactory;
use CoStack\Oauth2Client\Controller\Backend\ConnectController;
use CoStack\Oauth2Client\Provider\ProviderCollectionFactory;
use CoStack\Oauth2Client\Provider\ProviderScope;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use function htmlspecialchars;
use function sprintf;

class ConnectOauthProviderElement extends AbstractFormElement
{
    public function __construct(
        protected readonly ProviderCollectionFactory $providerCollectionFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly SecurityObjectsFactory $securityObjectsFactory,
    ) {}

    public function render(): array
    {
        $result = $this->initializeResultArray();

        $row = $this->data['databaseRow'] ?? [];
        $providerIdentifier = $row['provider'][0] ?? null;
        if (empty($providerIdentifier)) {
            return $result;
        }
        $providerCollection = $this->providerCollectionFactory->getProviderCollection(ProviderScope::BE);
        if (!$providerCollection->has($providerIdentifier)) {
            return $result;
        }

        $securityObjects = $this->securityObjectsFactory->create(ConnectController::SCOPE_INIT, [
            'provider_identifier' => $providerIdentifier,
            'oauth_config' => $row['uid'],
        ]);
        $securityObjects->params['state'] = $securityObjects->getSigningIdentifierName();
        $securityObjects = $securityObjects->lock();

        $params = ['providerIdentifier' => $providerIdentifier];
        $params = $securityObjects->addSecurityObjectsToParams($params);
        $url = $this->uriBuilder->buildUriFromRoute('oauth2client_connect_init', $params);

        $result['html'] = sprintf(
            '<button type="button" class="btn btn-default" data-connect-uri="%s">Connect</button>',
            htmlspecialchars((string) $url),
        );
        $result['javaScriptModules'][] = new JavaScriptModuleInstruction(
            '@co-stack/oauth2-client-nuevo/connect.js',
            JavaScriptModuleInstruction::FLAG_LOAD_IMPORTMAP,
        );
        return $result;
    }
}
