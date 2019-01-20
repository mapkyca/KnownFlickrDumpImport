Flickr Data dump import for Known 
=================================

This is a Known Console plugin which can be used to import a flickr data export (available for download from your flickr accounts page).

Unlike [previous attempts](https://github.com/mapkyca/KnownFlickrImport) this plugin does not use the flickr api, and so can be done offline.

## Prerequisites

You need the following things to get this going:

* The most [recent version of Known](https://www.marcus-povey.co.uk/known/)
* Both the Photos and Media (aka Audio) plugins switched on
* My [Known transcode plugin](https://github.com/mapkyca/KnownVideoTranscode) to make videos available
* Install PHP's ZipArchive support (```apt-get install php-zip```)

## Installation

* Drop FlickrDumpImport folder into the ConsolePlugins folder of your idno installation.
* Download your flickr data and save it in a directory somewhere (there is no need to
* Execute your import from the console:

```
./known.php import-flickr USERNAME /path/to/flickr/data/
```
## Todo

* [ ] Support permissions 
* [ ] Store additional metadata from Flickr import

## Troubleshooting

### Photos appear to import, but I see a big "?" on my feed

If you're importing on anything other than "localhost" you're likely going have to set the ```KNOWN_DOMAIN``` environment variable before running the import.

Set this to the domain you're site is on, so for http://example.com/, set ```KNOWN_DOMAIN``` as follows:

```
export KNOWN_DOMAIN=example.com
```

### All import's fail with an error that data could not be written

The console plugin will attempt to write to your Known data directory. Unless you're running the import as your web server user, you're going to need to make sure your data directory is writable by the user you're running the import as.

It's also a good plan to check ownership and permissions on all files after running the data import, as you may run into edit problems if the image data isn't owned by the web server's user.


## See
 * Author: Marcus Povey <http://www.marcus-povey.co.uk> 

