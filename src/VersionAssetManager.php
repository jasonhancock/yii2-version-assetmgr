<?php

namespace jhancock\VersionAssetMgr;
use yii\web\AssetManager;

class VersionAssetManager extends AssetManager
{
    // basic tests
    public function publish($path, $options = [])
    {
        $fh = fopen('/tmp/jason.txt', 'w');
        fwrite($fh, "yuppers\n");
        fclose($fh);
        return parent::publish($path, $options);
    }
}
