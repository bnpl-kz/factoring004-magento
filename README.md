# Factoring004 Magento Plugin

- [Requirements](#requirements)
- [Installation](#installation)
    * [Via composer](#via-composer)
    * [Manual](#manual)
- [Plugin configuration](#plugin-configuration)

## Requirements

- PHP >=7.3
- Magento >=2.4

## Installation

### Via composer

Coming soon

### Manual

Download a zip archive of the repository and unpack it

```bash
unzip factoring004-magento-main.zip
```

Create module directory into ```<magento-root>/app/code/BnplPartners/``` folder

```bash
mkdir -p <magento-root>/app/code/BnplPartners/
```

Move extracted files to ```<magento-root>/app/code/BnplPartners/Factoring004Magento``` folder

```bash
mv factoring004-magento-main <magento-root>/app/code/BnplPartners/Factoring004Magento
```

Go to ```<magento-root>``` and run commands below

Enable the module

```bash
php bin/magento module:enable BnplPartners_Factoring004Magento
```

Register the Magento module

```bash
php bin/magento setup:upgrade
```

Compile classes used in dependency injections

```bash
php bin/magento setup:di:compile
```

Deploy static view files

```bash
php bin/magento setup:static-content:deploy
```

Clean the cache

```bash
php bin/magento cache:clean
```

Install plugin dependencies

```bash
composer require bnpl-partners/factoring004:dev-legacy
```

Done!

## Plugin configuration

1. Go to ```Admin``` > ```Stores``` > ```Settings``` >```Configuration``` > ```Sales``` > ```Payment Methods``` > ```Factoring 0-0-4```
2. Fill required parameters
3. Enable the payment method
