# Magento 2 Credorax Payments Module

---

## ✓ Install via composer (recommended)
Run the following command under your Magento 2 root dir:

```
composer require credorax/magento2-module-credorax
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:flush
```

## Install manually under app/code
Download & place the contents of this repository under {YOUR-MAGENTO2-ROOT-DIR}/app/code/Credorax/Credorax  
Then, run the following commands under your Magento 2 root dir:
```
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:flush
```

---

https://www.credorax.com/

© 2019 Credorax.
All rights reserved.

![Credorax Logo](https://www.credorax.com/images/credorax.png)
