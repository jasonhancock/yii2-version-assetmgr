# yii2-version-assetmgr

A drop in replacement AssetManager for the Yii2 framework. Yii's default
AssetManager hashes assets based on path + Yii version + file modification time,
but if your website is being served behind a load balancer from multiple web
servers and the file mtimes are different, this could lead to serving assets
from different paths.

To combat this, instead the VersionAssetManager hashes path + Yii version +
application version. Application version is read from the `version` key of the
`params` array from the configuration file:

```
'params' => [
    'version' => '1.0.0'
],
```

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist jhancock/yii2-version-assetmgr "*"
```

or add

```
"jhancock/yii2-version-assetmgr": "*"
```

to the require section of your `composer.json` file.

## Configuration

Configure your application to use the VersionAssetManager by adding the
following component configuration:

```
'assetManager' => [
    'class' => 'jhancock\VersionAssetMgr\VersionAssetManager',
],
```
You should then set a `version` parameter in the `params` array when you publish
your application.


In the development environment, either omit setting the `version` key in the
config, set it to `Development`, or omit it altogether and it will default to
`Development`. When the version is `Development`, we instead hash path + Yii
version + time() to guarantee that we always bust cache in the development
environment. This means that every pageload will create new folders under your
`assets` directory and copy lots of files. It is therefore recommended that you
turn on symlinking by setting `linkAssets` to `true` under the `assetManager`
component configuration like so:

```
'assetManager' => [
    'class' => 'jhancock\VersionAssetMgr\VersionAssetManager',
    'linkAssets' => true,
],
```
