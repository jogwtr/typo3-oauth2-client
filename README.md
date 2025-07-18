# co-stack/typo3-oauth2-client-nuevo

A TYPO3 Frontend and Backend Login extension using OAuth 2.0.

Features:
* **Paranoid Security**
* Connect your existing backend user with an oauth2 provider (e.g. Github, Gitlab, Keycloak, ...)
* Connect an oauth2 provider with multiple backend users and select the backend user to log in.

## Installation

```shell
composer require co-stack/typo3-oauth2-client-nuevo
```

## Usage

Register an oauth provider (like gitlab) in `$GLOBALS` or with one of the provided utility methods:

```php
ProviderRegistrationUtility::registerGitlab(
    'Public Gitlab',
    ProviderScope::BE,
    [
        'clientId' => '[CLIENT_ID]',
        'clientSecret' => '[CLIENT_SECRET]',
    ],
);
```

# Developer Information

Requesting a login with OAuth requires you to lead the user to the oauth provider.
This requires a return URL, where the user is redirected to after.
That redirect URL points to our TYPO3 including an authorization_code.
TYPO3 requires a valid request token, otherwise the login will fail.
Most developers require to cookieSameSite lax, which will send the cookie with the redirect from the oauth provider.
The cookies contain the request token.
You can also redirect the user in TYPO3 using a meta http-refresh tag as a workaround.

* Open TYPO3 Backend Login Screen
* Click "Login with 'Oauth Provider'"
* Redirected to oauth provider
* Login with oauth provider
* Redirected back to TYPO3
* Redirected to TYPO3 again (if same site = strict)
* Validate request token
* Validate login

## Design choices

* Register provider in `$GLOBALS`
  * always accessible
  * mutable
  * no autoload required
  * usable in settings.php, additional.php, and ext_localconf.php
* Handle providers as `Provider`
  * Multiton
  * Immutable

## Terminology
* (OAuth) Provider: A service like github, gitlab, google, ... that provides a OAuth 2.0 Application

# Licenses

List of used assets with source and license

[LICENSE](LICENSE)
* License: Copyright (C) 2007 Free Software Foundation
* Source: https://www.gnu.org/licenses/gpl-3.0.txt

[Resources/Public/Icons/Extension.svg](Resources/Public/Icons/Extension.svg)
* License: CC BY-SA 3.0
* Source: https://wiki.oauth.net/Logo
