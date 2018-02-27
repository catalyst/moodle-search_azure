[![Build Status](https://travis-ci.org/catalyst/moodle-search_azure.svg?branch=master)](https://travis-ci.org/catalyst/moodle-search_azure)

# Moodle Global Search - Azure Search Backend

This plugin allows Moodle to use Microsoft Azure Search as the search engine for Moodle's Global Search.

The following features are provided by this plugin:

* File indexing
* Respects Moodle Proxy settings

## Supported Moodle Versions
This plugin currently supports Moodle:

* 3.1
* 3.2
* 3.3
* 3.4

## Azure Search Service Setup

### Service Setup
To setup an Azure Search Service (and optionally a Microsoft Auzre account), follow the intructions at:<br/>
https://docs.microsoft.com/en-au/azure/search/search-create-service-portal

### Get API Credentials
Calls to the Azure Search service require the service URL and an access key on every request. A search service is created with both, so if you added Azure Search to your subscription, follow these steps to get the necessary information:

1. In the Azure portal, open the search service page from the dashboard or find your service in the service list.
2. Get the endpoint at *Overview > Essentials > Url*. An example endpoint might look like `https://my-service-name.search.windows.net`.
3. Get the api-key in *Settings > Keys*. There are two admin keys for redundancy in case you want to roll over keys. Admin keys grant the write permissions on your service, necessary for creating and loading indexes. You can use either the primary or secondary key for write operations.

## Moodle Plugin Installation
Once you have setup an Azure Search service you can now install the Moodle plugin.

To install the plugin in Moodle:

1. Get the code and copy/ install it to: `<moodledir>/search/engine/azure`
2. Run the upgrade: `sudo -u www-data php admin/cli/upgrade` **Note:** the user may be different to www-data on your system.

## Moodle Plugin Setup
Once you have setup an Azure Search service you can now configure the Moodle plugin.


1. Log into Moodle as an administrator
2. Set up the plugin in *Site administration > Plugins > Search > Manage global search* by selecting *azure* as the search engine.
3. Configure the Azure Search plugin at: *Site administration > Plugins > Search > Azure*
4. TODO ....
6. To create the index and populate Azure Searcg with your site's data, run this CLI script. `sudo -u www-data php search/cli/indexer.php --force --reindex`
7. Enable Global search in *Site administration > Advanced features*

## File Indexing Support
This plugin uses [Apache Tika](https://tika.apache.org/) for file indexing support. Tika parses files, extracts the text, and return it via a REST API.

### Tika Setup
Seting up a Tika test service is straight forward. In most cases on a Linux environment, you can simply download the Java JAR then run the service.
<pre><code>
wget http://apache.mirror.amaze.com.au/tika/tika-server-1.16.jar
java -jar tika-server-1.16.jar
</code></pre>

This will start Tika on the host. By default the Tika service is available on: `http://localhost:9998`

### Enabling File indexing support in Moodle
Once a Tika service is available the Azure Search plugin in Moodle needs to be configured for file indexing support.<br/>
Assuming you have already followed the basic installation steps, to enable file indexing support:

1. Configure the Azure Search plugin at: *Site administration > Plugins > Search > Azure*
2. Select the *Enable file indexing* checkbox.
3. Set *Tika hostname* and *Tika port* of your Tika service. If you followed the basic Tika setup instructions the defaults should not need changing.
4. Click the *Save Changes* button.

### What is Tika
From the [Apache Tika](https://tika.apache.org/) website:
<blockquote>
The Apache Tika™ toolkit detects and extracts metadata and text from over a thousand different file types (such as PPT, XLS, and PDF). All of these file types can be parsed through a single interface, making Tika useful for search engine indexing, content analysis, translation, and much more. You can find the latest release on the download page. Please see the Getting Started page for more information on how to start using Tika.
</blockquote>

# Crafted by Catalyst IT

This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

![Catalyst IT](/pix/catalyst-logo.png?raw=true)


# Contributing and Support

Issues, and pull requests using github are welcome and encouraged! 

https://github.com/catalyst/moodle-search_elastic/issues

If you would like commercial support or would like to sponsor additional improvements
to this plugin please contact us:

https://www.catalyst-au.net/contact-us