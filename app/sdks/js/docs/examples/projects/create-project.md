let sdk = new Appwrite();

sdk
    .setProject('')
;

let promise = sdk.projects.createProject('[NAME]', '[TEAM_ID]');

promise.then(function (response) {
    console.log(response);
}, function (error) {
    console.log(error);
});