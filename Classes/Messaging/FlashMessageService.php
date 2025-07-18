<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Messaging;

use CoStack\Oauth2Client\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

readonly class FlashMessageService
{
    public function __construct(
        protected LanguageService $languageService,
        protected FlashMessageQueue $flashMessageQueue,
    ) {}

    public function enqueue(
        string $title,
        string $message,
        array $messageArguments = [],
        ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::ERROR,
    ): void {
        $title = $this->languageService->tryTranslate($title);
        $message = $this->languageService->tryTranslate($message, $messageArguments);
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            $title,
            $severity,
        );
        $this->flashMessageQueue->enqueue($flashMessage);
    }
}
