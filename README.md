# UnoPim-Bagisto Connector  

The **UnoPim-Bagisto Connector** enables seamless integration between UnoPim and Bagisto, allowing you to synchronize data effortlessly.  

## ✨ Features  

- 🗂 **Export Categories**: Export categories from UnoPim as collections in Bagisto.  
- 🛠️ **Attributes Sync**: Transfer attributes from UnoPim to Bagisto with ease.  
- 👨‍👩‍👧 **Export Families**: Export families from UnoPim to Bagisto.  
- 📦 **Product Export**: Export products, including simple and configurable ones, from UnoPim to Bagisto.  
- ⚡ **Bulk API**: Leverage a bulk API for faster product exports.  
- 🗄️ **Media Sync**: Sync product images and videos from UnoPim to Bagisto.  
- ☁️ **AWS S3 Compatibility**: Fully compatible with AWS S3 for image storage and management.  

## 🛠️ Installation  

1. 📂 Download and extract the extension package.  
2. 🔗 Merge the `packages` folder into the root directory of your UnoPim project.  

## ⚙️ Configuration  

### 📜 Register the Package Provider  
1. Open the `config/app.php` file.  
2. Add the following line under the `providers` array:  

    ```php  
    Webkul\Bagisto\Providers\BagistoServiceProvider::class,  
    ```  

3. Open the `composer.json` file.  
4. Add the following line under the `psr-4` section:  

    ```json  
    "Webkul\\Bagisto\\": "packages/Webkul/Bagisto/src"  
    ```  

### 🪠 Run Setup Commands  
Execute the following commands to complete the setup:  

1. **🔄 Dump Composer Autoload**  
    ```bash  
    composer dump-autoload  
    ```  

2. **📊 Migrate Tables for the Bagisto Plugin**  
    ```bash  
    php artisan migrate  
    ```  

3. **🔧 Publish Assets for the Bagisto Plugin**  
    ```bash  
    php artisan vendor:publish --tag=unopim-bagisto-connector  
    ```  

4. **🌐 Clear Application Cache**   
    ```bash  
    php artisan optimize:clear  
    ```  

### 🔄 Bagisto Requirement: Installing the REST API  

To install the REST API for Bagisto, follow these steps:  

1. **📖 Reference Repository**  
   Clone or download the REST API package from the official GitHub repository:  
   [GitHub Repository for REST API](https://github.com/unopim/bagisto-rest-api)  

2. **📚 Installation Documentation**  
   Refer to the official documentation for detailed installation instructions:  
   [Bagisto REST API Installation Docs](https://devdocs.bagisto.com/2.2/api/#rest-api)  

3. **⚖️ Ensure Compatibility**  
   - Install the version compatible with your Bagisto setup.  
   - Required version: `2.x.x`  

4. **🛠️ Installation Steps**  
   - Run the necessary commands mentioned in the documentation to integrate the REST API into your Bagisto setup.  
   - Update dependencies and configurations as needed.  

## 📞 Support  
For any issues or inquiries, please contact our support team.
