<?php

declare(strict_types=1);

/*
 * This file is part of the OAuth2 Client extension for TYPO3
 * - (c) 2021 Waldhacker UG
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Waldhacker\Oauth2Client\Tests\Functional\Framework\RequestHandling;

use Composer\Autoload\ClassLoader;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\AbstractApplication;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

use function fclose;
use function fopen;
use function fwrite;
use function is_dir;
use function is_file;
use function json_encode;
use function ob_get_clean;
use function ob_start;
use function parse_str;
use function parse_url;
use function putenv;
use function serialize;

use const JSON_THROW_ON_ERROR;

abstract class AbstractRequestBootstrap
{
    public function __construct(
        protected readonly string $documentRoot,
        protected readonly ClassLoader $classLoader,
        protected readonly InternalRequestContext $context,
        protected readonly InternalRequest $request,
    ) {}

    public function executeAndOutput(): void
    {
        if (empty($this->documentRoot) || !is_dir($this->documentRoot)) {
            $this->errorExit('No documentRoot given or folder does not exist: ' . $this->documentRoot);
        }
        if (!is_file($this->documentRoot . static::SCRIPT)) {
            $this->errorExit('Index script does not exist in documentRoot: ' . $this->documentRoot . static::SCRIPT);
        }

        ob_start();

        $requestUrlParts = parse_url((string) $this->request->getUri());

        // Populating $_GET and $_REQUEST is query part is set:
        if (isset($requestUrlParts['query'])) {
            parse_str($requestUrlParts['query'], $_GET);
            parse_str($requestUrlParts['query'], $_REQUEST);
        }

        $_POST = $this->request->getParsedBody();
        $_COOKIE = $this->request->getCookieParams();

        // Setting up the server environment
        $_SERVER = [];
        $_SERVER['DOCUMENT_ROOT'] = $this->documentRoot;
        $_SERVER['HTTP_USER_AGENT'] = 'TYPO3 Functional Test Request';
        $_SERVER['HTTP_HOST'] = $_SERVER['SERVER_NAME'] = $requestUrlParts['host'] ?? 'localhost';
        $_SERVER['SERVER_ADDR'] = $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $_SERVER['DOCUMENT_URI'] = static::SCRIPT;
        $_SERVER['SCRIPT_FILENAME'] = $_SERVER['_'] = $_SERVER['PATH_TRANSLATED'] = $this->documentRoot . static::SCRIPT;
        $_SERVER['QUERY_STRING'] = ($requestUrlParts['query'] ?? '');
        $_SERVER['REQUEST_URI'] = $requestUrlParts['path'] . (isset($requestUrlParts['query']) ? '?' . $requestUrlParts['query'] : '');
        $_SERVER['REQUEST_METHOD'] = $this->request->getMethod();

        // Define HTTPS and server port:
        if (isset($requestUrlParts['scheme'])) {
            if ($requestUrlParts['scheme'] === 'https') {
                $_SERVER['HTTPS'] = 'on';
                $_SERVER['SERVER_PORT'] = '443';
            } else {
                $_SERVER['SERVER_PORT'] = '80';
            }
        }

        // Define a port if used in the URL:
        if (isset($requestUrlParts['port'])) {
            $_SERVER['SERVER_PORT'] = $requestUrlParts['port'];
        }

        if (!is_file($_SERVER['SCRIPT_FILENAME'])) {
            die('Script file "' . $_SERVER['SCRIPT_FILENAME'] . '" does not exist');
        }
        putenv('TYPO3_CONTEXT=' . static::TYPO3_CONTEXT);

        $result = [
            'response' => null,
            'exception' => null,
            'unexpectedOutput' => null,
        ];

        try {
            chdir($_SERVER['DOCUMENT_ROOT']);
            SystemEnvironmentBuilder::run(static::ENTRY_LEVEL, static::REQUEST_TYPE);
            $container = Bootstrap::init($this->classLoader);
            /** @var ResponseInterface $response */
            $serverRequest = ServerRequestFactory::fromGlobals();
            /** @var AbstractApplication $application */
            $application = $container->get(static::APPLICATION);
            $response = $application->handle($serverRequest);
            // The body is a stream, therefore not serializable. Save it in an extra entry.
            $body = (string) $response->getBody();
            $result['response'] = serialize($response);
            $result['body'] = $body;
        } catch (Throwable $exception) {
            $result['exception'] = $this->dumpException($exception);
        }

        $unexpectedOutput = ob_get_clean();
        if ('' !== $unexpectedOutput) {
            $result['unexpectedOutput'] = $unexpectedOutput;
        }

        try {
            $output = json_encode($result, JSON_THROW_ON_ERROR);
            echo $output;
        } catch (Throwable $exception) {
            $this->errorExit((string) $exception);
        }
    }

    protected function dumpException(?Throwable $exception): array
    {
        if (null === $exception) {
            return [];
        }
        return [
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            // Use traceAsString to circumvent the possible serialization of closures
            'trace' => $exception->getTraceAsString(),
            'previous' => $this->dumpException($exception->getPrevious()),
        ];
    }

    protected function errorExit(string $message): never
    {
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr, $message);
        fclose($stderr);
        exit(1);
    }
}
