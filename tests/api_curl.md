# Testing Azure Search API with cURL
This file contains instructions on how to test the Azure Search API using the cURL command line tool. This allows testing without using Moodle. This is very handy for debugging and developing purposes.

# Azure Search Service Setup

## Service Setup
To setup an Azure Search Service (and optionally a Microsoft Auzre account), follow the intructions at:<br/>
https://docs.microsoft.com/en-au/azure/search/search-create-service-portal

## Get API Credentials
Calls to the Azure Search service require the service URL and an access key on every request. A search service is created with both, so if you added Azure Search to your subscription, follow these steps to get the necessary information:

1. In the Azure portal, open the search service page from the dashboard or find your service in the service list.
2. Get the endpoint at *Overview > Essentials > Url*. An example endpoint might look like `https://my-service-name.search.windows.net`.
3. Get the api-key in *Settings > Keys*. There are two admin keys for redundancy in case you want to roll over keys. Admin keys grant the write permissions on your service, necessary for creating and loading indexes. You can use either the primary or secondary key for write operations.

## Create Index
The following show how to create an index. When the index is created you also provide information about the document structure to use.

Replace the `{index}` variable in the example (including removing the braces) with the actual name you want to use for the index.</br>
Replace the `{key}` variable in the example (including removing the braces) with the actual API key for the service.

<pre><code>
curl -X PUT \
-H "Content-Type: application/json" \
-H "api-key: {key}" \
-d '{
     "name": "{index}",
     "fields": [
       {"name": "id", "type": "Edm.String", "key":true, "searchable": false},
       {"name": "parentid", "type": "Edm.String"},
       {"name": "itemid", "type": "Edm.Int32"},
       {"name": "title", "type": "Edm.String"},
       {"name": "content", "type": "Edm.String"},
       {"name": "contextid", "type": "Edm.Int32"},
       {"name": "areaid", "type": "Edm.String"},
       {"name": "type", "type": "Edm.Int32"},
       {"name": "courseid", "type": "Edm.Int32"},
       {"name": "owneruserid", "type": "Edm.Int32"},
       {"name": "modified", "type": "Edm.DateTimeOffset"}
      ]
     }' \
"https://moodle.search.windows.net/indexes/{index}?api-version=2016-09-01"
</code></pre>
