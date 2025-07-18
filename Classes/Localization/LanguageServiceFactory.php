<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Localization;

use TYPO3\CMS\Core\Localization\LanguageService as CoreLanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory as CoreLanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LanguageServiceFactory
{
    public function create(): LanguageService
    {
        $languageService = $this->getGlobalLanguageService() ?? $this->createDefaultLanguageService();
        return GeneralUtility::makeInstance(LanguageService::class, $languageService);
    }

    protected function getGlobalLanguageService(): ?CoreLanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }

    protected function createDefaultLanguageService(): CoreLanguageService
    {
        return GeneralUtility::makeInstance(CoreLanguageServiceFactory::class)->create('default');
    }
}
