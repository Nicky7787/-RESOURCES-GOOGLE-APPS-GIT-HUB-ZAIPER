let sdk = new Appwrite();

sdk
    .setProject('')
;

/**
 * Will redirect to relevant page
 *  depends on the operation result
 */
sdk.auth.register(
    'email@example.com',
    'password',
    'http://example.com/confirm',
    'http://example.com/success', // required for JS SDK
    'http://example.com/failure' // required for JS SDK
);