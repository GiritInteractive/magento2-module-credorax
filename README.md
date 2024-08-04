# Magento 2 Shift4 Payments Module

---

## ✓ Install via composer (recommended)
Run the following command under your Magento 2 root dir:

```
composer require shift4/magento2-module-shift4
php bin/magento maintenance:enable
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento maintenance:disable
php bin/magento cache:flush
```

## Install manually under app/code
Download & place the contents of this repository under {YOUR-MAGENTO2-ROOT-DIR}/app/code/Shift4/Shift4  
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

https://www.shift4.com/

© 2019 Shift4.
All rights reserved.

![Shift4 Logo](https://www.shift4.com/images/shift4.png)
