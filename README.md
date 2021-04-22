# Apisearch Prestashop Plugin

### How to install in Prestashop

1. Download this source code and place it in a folder called 'apisearch'

2. Zip the new folder to a file called apisearch.zip (once you unzip this file, you should find all the source code inside the apisearch folder).

3. Upload the zip file under the modules section in Prestashop's backoffice

### How to configure

Once you've installed the plugin succesfully, it's time to configure.

1. Apisearch Cluster Url

    This is the absolute url to the cluster you'll be pointing to. 
    
    * Note: If you leave it blank it'll point to the default Apisearch Production Cluster 'https://eu1.apisearch.cloud'

2. Apisearch Admin Url

    This is the absolute url you'll be pulling the js files from.
    
    * Note: If you leave it blank it'll point to the default Apisearch Production Admin 'https://apisearch.cloud'

3. Apisearch Api Version

    This is the Api Version for the api calls.
    
    * Note: If you leave it blank it'll point to the default Apisearch Production Version 'v1'

4. App Hash ID

    Unique hash for your application

5. Index Hash ID

    The index your items are indexed. Note that if you've got a multilingual store, you can set a different index per language.

6. Management token Hash ID

    The secret token that will allow indexing your items.
