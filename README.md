# Sage Pay Suite integration for Magento 2

[CHANGELOG](https://github.com/ebizmarts/magento2-sage-pay-suite/blob/devel_martin/CHANGELOG.md)

## Installation Instructions

__Requirements__

  - The ZIP file named **Ebizmarts_SagePaySuiteM2-1.1.7.zip**
  - Access to the Magento 2 server via SSH
  - The unzip command should be available, check by running `which unzip`
  - [Composer](https://getcomposer.org/) needs to be installed in the server

__Installation__

  1. Check file integrity by running this command on the terminal
  `php -r "if (hash_file('SHA384', 'Ebizmarts_SagePaySuiteM2-1.1.7.zip') === '9731b4501ba878bf99f60ed15837874459e836f3f8ea267ffb9a609edf42f197ea9a75d9cd64ca29074df612a40aa0f9') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('Ebizmarts_SagePaySuiteM2-1.1.7.zip'); } echo PHP_EOL;"`
  
  2. Upload the ZIP file to the Magento 2 server

  3. Get access to the Magento 2 server

  4. Go to the Magento2 modules folder

    $ cd $MAGENTO_FOLDER$/app/code

  5. Create the directory (if it does not exist) that will hold the module contents
    `$ mkdir -p Ebizmarts/SagePaySuite`
   
  6. Go to the SagePaySuite folder
  
    $ cd $MAGENTO_FOLDER$/app/code/Ebizmarts/SagePaySuite
   
  7. Uncompress Sage Pay Suite package
  
    $ unzip /PATH/TO/PACKAGE/Ebizmarts_SagePaySuiteM2-1.1.7.zip

  8. This will create the following content in $MAGENTO_FOLDER$/app/code
    <pre>
    └── Ebizmarts
        └── SagePaySuite
            ├── Api
            ├── Block
            ├── Controller
            ├── Helper
            ├── Model
            ├── Observer
            ├── Setup
            ├── Test
            ├── etc
            ├── i18n
            └── view
    </pre>
  9. Go to the magento root folder (where composer.json is located)

    $ cd $MAGENTO_FOLDER$

  10. Execute Magento setup upgrade

    $ bin/magento setup:upgrade

  11. Clean cache and generated code

    $ bin/magento cache:clean
    $ rm -rf var/generation/*

  12. Run magento compiler to generate auto-generated classes

    $ bin/magento setup:di:compile

   (this will take some time ...)

__Test__

  You can check if the module was properly installed testing some features introduced by Sage Pay Suite:
  
  1. Get access to the Magento 2 backoffice.

  2. Menu > Stores > Configuration > SALES > Payment Methods
  You should see Sage Pay Suite on the payment methods list.
  3. Enter your Sage Pay vendorname and Ebizmarts license key on the configuration settings.
  4. Enable the integration of your preference.

[![Build Status](https://circleci.com/gh/ebizmarts/magento2-sage-pay-suite.svg?style=shield&circle-token=9d950c73b76af8868862caf8400c549439838d47)](https://circleci.com/gh/ebizmarts/magento2-sage-pay-suite)
