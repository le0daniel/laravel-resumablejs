# laravel-resumablejs

This laravel Package implements a chunk uploading feature using upload handlers. The idea is, that you have one single Upload route and you pass the handler name to init the Upload to it. This handler then handles the authorization, and file processing. The Upload itself is managed by the Upload Controller globally.

### Installation

The installation is done via composer

```composer require le0daniel/laravel-resumablejs```

As the package comes with some migrations, run them.

```artisan migrate```

The package comes with a config, so you should publish the package using 

```artisan vendor:publish```

### Installation
In the `resumablejs.php` config file, you need to declare Handlers to use this package.
A handler always needs to extend the `le0daniel\LaravelResumableJs\Contracts\UploadHandler` Contract.

The methods to implement are pretty straight forward.

### Javascript

A simple implementation of Resumable.js
Requirements: `axios`,`resumable.js`

It's important to set the forceChunkSize parameter because each chunk is checked to have the exact Chunk size (except if it's the last one).
If not, the chunk is marked as invalid and refused.
Also, the chunkSize must be the same as defined in the laravel config (`resumablejs.php`)

```
const r = new Resumable({
    target:'/upload',
    query: file => {
        if(file.token){
            return {token:file.token};
        }
        return {};
    },
    chunkSize: (10 * 1024 * 1024),
    forceChunkSize: true
});
```

Once a file is added, you need to call the init method with a handler name to get an upload token, which is then used to perform the upload itself.
```
r.on('fileAdded', file => {
    // Pause the file
    file.pause();

    // Make the init call
    axios.post('/upload/init',{
        handler:'basic', // name defined in config
        size: file.size,
        type: file.file.type,
        name: file.fileName
    }).then(response => {
        if(response.data.data.token){
            // Save the token to the file
            file.token = response.data.data.token;
            file.pause(false);
            r.upload();
        }
        else{
            file.cancel();
        }
    }).catch(error => {
        file.cancel();
    });
});
```

As soon as the upload is done, we will call the complete endpoint to process the file
```
r.on('fileSuccess', file => {
    axios.post('/upload/complete',{
        token: file.token
    }).then(response => {
        console.log(response.data);
    })
})
```

Done, you can now upload large files in chunks and process them easily.
