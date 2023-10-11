# prestashop-accounts-installer

Utility package to install `ps_accounts` module or present data to trigger manual install from psx configuration page.

This module also give you access to `ps_accounts` services through its module service container dealing with the installation status of the module.

### Compatibility Matrix

We aims to follow partially the Prestashop compatibility charts
- [Compatibility Chart Prestashop 1.6 & 1.7](https://devdocs.prestashop.com/1.7/basics/installation/system-requirements/#php-compatibility-chart)
- [Compatibility Chart Prestashop 8](https://devdocs.prestashop.com/8/basics/installation/system-requirements/#php-compatibility-chart)

| ps_account version                      | Prestashop Version   | PHP Version     | Event Bus installation
|-----------------------------------------|----------------------|-----------------|-------------------------
| 6.x                                     | >=8.0.0              | ≥7.2 \|\| ≤8.1  | Yes
| 5.x                                     | >=1.7.0 \|\| <8.0.0  | ≥5.6 \|\| ≤7.4  | Yes
| 5.x                                     | >=1.6.1 \|\| <1.7.0  | ≥5.6 \|\| ≤7.4  | No

## Installation

This package is available on [Packagist](https://packagist.org/packages/prestashop/prestashop-accounts-installer), 
you can install it via [Composer](https://getcomposer.org).

```shell script
composer require prestashop/prestashop-accounts-installer
```

## Register as a service in your PSx container (recommended)

Example :

```yaml
services:
  <your_module>.ps_accounts_installer:
    class: 'PrestaShop\PsAccountsInstaller\Installer\Installer'
    arguments:
      - '5.0.0'

  <your_module>.ps_accounts_facade:
    class: 'PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts'
    arguments:
      - '@<your_module>.ps_accounts_installer'
```

The name under which you register both services in your service container must be unique to avoid collision with other modules including it.

The `5.0.0` specified argument is the minimum required `ps_account` module version. You should modify it if you need another version.

## How to use it 

### Installer

In your module main class `install` method. (Will only do something on PrestaShop 1.7 and above)

```php
    $this->getService('ps_accounts.installer')->install();
```

### Presenter

For example in your main module's class `getContent` method.

```php
    Media::addJsDef([
        'contextPsAccounts' => $this->getService('ps_accounts.facade')
            ->getPsAccountsPresenter()
            ->present($this->name),
    ]);
```

This presenter will serve as default minimal presenter and switch to PsAccountsPresenter data when `ps_accounts` module is installed.

### Accessing PsAccounts Services

Installer class includes accessors to get instances of services from PsAccounts Module :

* getPsAccountsService
* getPsBillingService

The methods above will throw an exception in case `ps_accounts` module is not installed or not in the required version.

Example :

```php
use PrestaShop\PsAccountsInstaller\Installer\Exception\ModuleVersionException;
use PrestaShop\PsAccountsInstaller\Installer\Exception\ModuleNotInstalledException;

try {
    $psAccountsService = $this->getService('ps_accounts.facade')->getPsAccountsService();

    $shopJwt = $psAccountsService->getOrRefreshToken();

    $shopUuid = $psAccountsService->getShopUuid();

    $apiUrl = $psAccountsService->getAdminAjaxUrl();

    // Your code here

} catch (ModuleNotInstalledException $e) {

    // You handle exception here

} catch (ModuleVersionException $e) {

    // You handle exception here
}
```
