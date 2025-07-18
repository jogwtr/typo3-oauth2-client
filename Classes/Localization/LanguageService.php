<?php

declare(strict_types=1);

namespace CoStack\Oauth2Client\Localization;

use Psr\Log\LoggerAwareInterface as LoggerAware;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\Localization\LanguageService as CoreLanguageService;
use function vsprintf;

class LanguageService implements LoggerAware
{
    use LoggerAwareTrait;

    public function __construct(protected CoreLanguageService $languageService) {}

    public function tryTranslate(string $label, array $arguments = []): string
    {
        $translatedLabel = $label;
        try {
            $translatedLabel = $this->languageService->sL($label);
        } catch (Throwable $exception) {
            $this->logger->error(
                'Failed to translate label. Using label source as translation result',
                ['label' => $label, 'exception' => $exception],
            );
        }

        $formattedLabel = $translatedLabel;
        if ([] !== $arguments) {
            try {
                $formattedLabel = vsprintf($translatedLabel, $arguments);
            } catch (Throwable $exception) {
                $this->logger->error(
                    'Failed to format label. Using translated label as format result',
                    ['label' => $label, 'arguments' => $arguments, 'exception' => $exception],
                );
            }
        }

        return $formattedLabel;
    }
}
