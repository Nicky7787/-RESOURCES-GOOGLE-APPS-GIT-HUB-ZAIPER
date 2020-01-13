const sdk = require('node-appwrite');

// Init SDK
let client = new sdk.Client();

let database = new sdk.Database(client);

client
    .setProject('')
    .setKey('')
;

let promise = database.updateDocument('[COLLECTION_ID]', '[DOCUMENT_ID]', '{}', [], []);

promise.then(function (response) {
    console.log(response);
}, function (error) {
    console.log(error);
});