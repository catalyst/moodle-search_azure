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
       {"name": "id", "type": "Edm.String", "retrievable":true, "searchable": true, "key":true, "filterable": false},
       {"name": "parentid", "type": "Edm.String", "retrievable":true, "searchable": false, "filterable": false},
       {"name": "itemid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
       {"name": "title", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
       {"name": "content", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
       {"name": "description1", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
       {"name": "description2", "type": "Edm.String", "retrievable":true, "searchable": true, "filterable": false},
       {"name": "filetext", "type": "Edm.String", "retrievable":false, "searchable": true, "filterable": false},
       {"name": "contextid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": true},
       {"name": "areaid", "type": "Edm.String", "retrievable":true, "searchable": false, "filterable": true},
       {"name": "type", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
       {"name": "courseid", "type": "Edm.String", "retrievable":true, "searchable": false, "filterable": true},
       {"name": "owneruserid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
       {"name": "userid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
       {"name": "groupid", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": false},
       {"name": "modified", "type": "Edm.Int32", "retrievable":true, "searchable": false, "filterable": true}
      ]
     }' \
"https://moodle.search.windows.net/indexes/{index}?api-version=2016-09-01"
</code></pre>

## Get Index
The Get Index checks if an index exists in your Azure Search service.

Replace the `{index}` variable in the example (including removing the braces) with the actual name you want to use for the index.</br>
Replace the `{key}` variable in the example (including removing the braces) with the actual API key for the service.

<pre><code>
curl -X GET \
-H "Content-Type: application/json" \
-H "api-key: {key}" \
"https://moodle.search.windows.net/indexes/{index}?api-version=2016-09-01"
</code></pre>

## Load Documents
The following show how to load documents into the index. You can load multiple documents at once.

Replace the `{index}` variable in the example (including removing the braces) with the actual name you want to use for the index.</br>
Replace the `{key}` variable in the example (including removing the braces) with the actual API key for the service.

<pre><code>
curl -X POST \
-H "Content-Type: application/json" \
-H "api-key: {key}" \
-d ' {
     "value": [
     {
         "@search.action": "mergeOrUpload",
         "id": "core_course-mycourse-4",
         "parentid": "core_course-mycourse-4",
         "title": "search test",
         "content": "search course summary description description",
         "description1": "test",
         "contextid": 202,
         "areaid": "core_course-mycourse",
         "type": 1,
         "courseid": 4,
         "owneruserid": 0,
         "modified": 1499398979
       },
       {
         "@search.action": "mergeOrUpload",
         "id": "mod_resource-activity-10",
         "parentid": "mod_resource-activity-10",
         "title": "search test file",
         "content": "description search test file",
         "contextid": 296,
         "areaid": "mod_resource-activity",
         "type": 1,
         "courseid": 4,
         "owneruserid": 0,
         "modified": 1507008263
       }
      ]
     }
' \
"https://moodle.search.windows.net/indexes/{index}/docs/index?api-version=2016-09-01"
</code></pre>

## Query - Basic
The following show how construct basic Azure Search Queries.

Replace the `{index}` variable in the example (including removing the braces) with the actual name you want to use for the index.</br>
Replace the `{key}` variable in the example (including removing the braces) with the actual API key for the service.

<pre><code>
curl -X POST \
-H "Content-Type: application/json" \
-H "api-key: {key}" \
-d ' {
     "search": "*",
     "searchFields": "id, title, title, content, description1, description2, filetext",
     "top": 100
   }
' \
"https://moodle.search.windows.net/indexes/{index}/docs/search?api-version=2016-09-01"
</code></pre>

## Query - Filter
The following show how construct a filtered Azure Search Query.

Replace the `{index}` variable in the example (including removing the braces) with the actual name you want to use for the index.</br>
Replace the `{key}` variable in the example (including removing the braces) with the actual API key for the service.

The below query selects:
* Any records (*)
* That are in courses with a course id of 1, 2, 3 or 4
* That are assignment or forum activity types
* That have the word 'Forum' in the activity title

<pre><code>
curl -X POST \
-H "Content-Type: application/json" \
-H "api-key: {key}" \
-d ' {
     "search": "*",
     "searchFields": "id, title, title, content, description1, description2, filetext",
     "top": 100,
     "filter": "search.in(courseid, '\''1,2,3,4'\'') and search.in(areaid, '\''mod_assign-activity, mod_forum-activity'\'') and search.ismatch('\''Forum'\'', '\''title'\'')"
   }
' \
"https://moodle.search.windows.net/indexes/{index}/docs/search?api-version=2016-09-01"
</code></pre>

## Query - Date Range
The following show how construct a filtered Azure Search Query that limits results based on a time range.

Replace the `{index}` variable in the example (including removing the braces) with the actual name you want to use for the index.</br>
Replace the `{key}` variable in the example (including removing the braces) with the actual API key for the service.

<pre><code>
curl -X POST \
-H "Content-Type: application/json" \
-H "api-key: {key}" \
-d ' {
     "search": "*",
     "searchFields": "id, title, title, content, description1, description2, filetext",
     "top": 100,
     "filter": "(modified lt 1504505795 and modified ge 1504505792)"
   }' \
"https://moodle.search.windows.net/indexes/{index}/docs/search?api-version=2016-09-01"
</code></pre>

## Delete Index
The Delete Index operation removes an index and associated documents from your Azure Search service.

Replace the `{index}` variable in the example (including removing the braces) with the actual name you want to use for the index.</br>
Replace the `{key}` variable in the example (including removing the braces) with the actual API key for the service.

<pre><code>
curl -X DELETE \
-H "Content-Type: application/json" \
-H "api-key: {key}" \
"https://moodle.search.windows.net/indexes/{index}?api-version=2016-09-01"
</code></pre>

## Delete Document by ID
Delete removes the specified document from the index. Note that any field you specify in a delete operation, other than the key field, will be ignored. If you want to remove an individual field from a document, use merge instead and simply set the field explicitly to null.

Replace the `{index}` variable in the example (including removing the braces) with the actual name you want to use for the index.</br>
Replace the `{key}` variable in the example (including removing the braces) with the actual API key for the service.

<pre><code>
curl -X POST \
-H "Content-Type: application/json" \
-H "api-key: {key}" \
-d ' {
     "value": [
     {
         "@search.action": "delete",
         "id": "core_course-mycourse-4"
       }
      ]
     }
' \
"https://moodle.search.windows.net/indexes/{index}/docs/index?api-version=2016-09-01"
</code></pre>
