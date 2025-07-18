<?php

/**
 * @noinspection PhpUnused ViewHelper used in templates
 */

declare(strict_types=1);

namespace CoStack\Oauth2Client\ViewHelpers\Link;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

final class DeleteRecordViewHelper extends AbstractTagBasedViewHelper
{
    protected $tagName = 'a';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('table', 'string', 'Record table', true);
        $this->registerArgument('uid', 'integer', 'Record uid', true);
    }

    public function render(): string
    {
        $request = $this->renderingContext->getAttribute(ServerRequest::class);
        $returnUrl = $request->getAttribute('normalizedParams')->getRequestUri();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $params = [
            'cmd' => [$this->arguments['table'] => [$this->arguments['uid'] => ['delete' => '1']]],
            'redirect' => $returnUrl,
        ];

        $uri = (string) $uriBuilder->buildUriFromRoute('tce_db', $params);
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
