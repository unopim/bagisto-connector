# UnoPIM TVC Mall Connector

### Requirements:

* **UnoPIM**: v0.1.6

### Installation Guide

To install the connector, follow these steps:

1. Unzip the respective extension zip file and then merge the "packages" folder into your project's root directory.

2. Open the `composer.json` file and add the following line under the 'psr-4' section:

   ```
   "Webkul\\TVCMall\\": "packages/Webkul/TVCMall/src",
   ```

3. In the `config/app.php` file, add the following line under the 'providers' section:

   ```
   Webkul\TVCMall\Providers\TVCMallServiceProvider::class,
   ```

4. Run the following commands to complete the setup:

   ```
   composer dump-autoload
   ```

   ```
   php artisan migrate --path=packages/Webkul/TVCMall/src/Database/Migrations
   ```

   ```
   php artisan db:seed --class=Webkul\\TVCMall\\Database\\Seeders\\ProductAttributeMappingSeeder
   ```

After following these steps, the connector should be successfully installed and ready for use