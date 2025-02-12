# UnoPim-Bagisto Connector  

The **UnoPim-Bagisto Connector** enables seamless integration between UnoPim and Bagisto, allowing you to synchronize data effortlessly.  

## ✨ Features  
- 📂 **Export Categories**: Export categories from UnoPim as collections in Bagisto.  
- 🛠️ **Attributes Sync**: Transfer attributes from UnoPim to Bagisto with ease.  
- 👨‍👩‍👧 **Export Families**: Export families from UnoPim to Bagisto.  
- 📦 **Product Export**: Export products, including simple and configurable ones, from UnoPim to Bagisto.  
- ⚡ **Bulk API**: Leverage a bulk API for faster product exports.  
- 🖼️ **Media Sync**: Sync product images and videos from UnoPim to Bagisto.  
- ☁️ **AWS S3 Compatibility**: Fully compatible with AWS S3 for image storage and management.  

## 🛠️ Installation  
1. 🗂️ Download and extract the extension package.  
2. 🔗 Merge the `packages` folder into the root directory of your UnoPim project.  

## ⚙️ Configuration  

### 📜 Register the Package Provider  
1. Open the `config/app.php` file.  
2. Add the following line under the `providers` array:  

    ```php
    Webkul\BagistoPlugin\Providers\BagistoPluginServiceProvider::class,
    ```  

3. Open the `composer.json` file.  
4. Add the following line under the `psr-4` section:  

    ```json
    "Webkul\\BagistoPlugin\\": "packages/Webkul/BagistoPlugin/src"
    ```  

### 🧰 Run Setup Commands  
Execute the following commands to complete the setup:  

1. **Dump Composer Autoload**  
    ```bash
    composer dump-autoload
    ```  

2. **Run Database Migrations**  
    ```bash
    php artisan migrate
    ```  

3. **Publish Plugin Assets**  
    ```bash
    php artisan vendor:publish --tag=unopim-bagisto-connector
    ```  

4. **Clear Application Cache**  
    ```bash
    php artisan optimize:clear
    ```  

## 📞 Support  
For any issues or inquiries, please contact our support team.  
 
