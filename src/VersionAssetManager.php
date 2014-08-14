<?php

namespace jhancock\VersionAssetMgr;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\FileHelper;
use yii\web\AssetManager;

/**
* VersionAssetManager manages asset bundles and asset publishing.
*
* AssetManager is a drop-in replacement for the default Yii AssetManager.
*
* Configure your app to use it by adding an array to your application config under `components`
* as it is shown in the following example:
*
* ~~~
* 'assetManager' => [
*  'class' => 'jhancock\VersionAssetMgr\VersionAssetManager',
* ],
* ~~~
*
* All of the standard configuration parameters of AssetManger are also supported.
*
* @author Jason Hancock <jason@jasonhancock.com>
*/

class VersionAssetManager extends AssetManager
{
    /**
     * @var array published assets
     */
    private $_published = [];

    /**
     * @var integer Store the timestamp
     */
    private $_time;

    public function publish($path, $options = [])
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path];
        }

        if (!is_string($path) || ($src = realpath($path)) === false) {
            throw new InvalidParamException("The file or directory to be published does not exist: $path");
        }

        if (is_file($src)) {
            $dir = $this->hash(dirname($src));
            $fileName = basename($src);
            $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
            $dstFile = $dstDir . DIRECTORY_SEPARATOR . $fileName;

            if (!is_dir($dstDir)) {
                FileHelper::createDirectory($dstDir, $this->dirMode, true);
            }

            if ($this->linkAssets) {
                if (!is_file($dstFile)) {
                    symlink($src, $dstFile);
                }
            } elseif (@filemtime($dstFile) < @filemtime($src)) {
                copy($src, $dstFile);
                if ($this->fileMode !== null) {
                    @chmod($dstFile, $this->fileMode);
                }
            }

            return $this->_published[$path] = [$dstFile, $this->baseUrl . "/$dir/$fileName"];
        } else {
            $dir = $this->hash($src);
            $dstDir = $this->basePath . DIRECTORY_SEPARATOR . $dir;
            if ($this->linkAssets) {
                if (!is_dir($dstDir)) {
                    symlink($src, $dstDir);
                }
            } elseif (!is_dir($dstDir) || !empty($options['forceCopy']) || (!isset($options['forceCopy']) && $this->forceCopy)) {
                $opts = [
                    'dirMode' => $this->dirMode,
                    'fileMode' => $this->fileMode,
                ];
                if (isset($options['beforeCopy'])) {
                    $opts['beforeCopy'] = $options['beforeCopy'];
                } elseif ($this->beforeCopy !== null) {
                    $opts['beforeCopy'] = $this->beforeCopy;
                } else {
                    $opts['beforeCopy'] = function ($from, $to) {
                        return strncmp(basename($from), '.', 1) !== 0;
                    };
                }
                if (isset($options['afterCopy'])) {
                    $opts['afterCopy'] = $options['afterCopy'];
                } elseif ($this->afterCopy !== null) {
                    $opts['afterCopy'] = $this->afterCopy;
                }
                FileHelper::copyDirectory($src, $dstDir, $opts);
            }

            return $this->_published[$path] = [$dstDir, $this->baseUrl . '/' . $dir];
        }
    }

   /**
     * @inheritdoc
     */
    public function getPublishedPath($path)
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path][0];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            $base = $this->basePath . DIRECTORY_SEPARATOR;
            if (is_file($path)) {
                return $base . $this->hash(dirname($path)) . DIRECTORY_SEPARATOR . basename($path);
            } else {
                return $base . $this->hash($path);
            }
        } else {
            return false;
        }
    }

   /**
     * @inheritdoc
     */
    public function getPublishedUrl($path)
    {
        $path = Yii::getAlias($path);

        if (isset($this->_published[$path])) {
            return $this->_published[$path][1];
        }
        if (is_string($path) && ($path = realpath($path)) !== false) {
            if (is_file($path)) {
                return $this->baseUrl . '/' . $this->hash(dirname($path)) . '/' . basename($path);
            } else {
                return $this->baseUrl . '/' . $this->hash($path);
            }
        } else {
            return false;
        }
    }

   /**
     * @inheritdoc
     */
    protected function hash($path)
    {
        // If version is development, hash the current timestamp
        // to always cache bust
        $ver = $this->version() == 'Development'
            ? $this->getTime()
            : $this->version();
        return sprintf('%x', crc32($path . Yii::getVersion() . $ver));
    }

    /**
     * Protect against publishing multiple assets for a page view spanning multiple
     * seconds. Only used when version()  == 'Development'
     */
    protected function getTime() {
        if(!isset($this->_time))
            $this->_time = time();

        return $this->_time;
    }

    /**
     * Returns the version as set in the "params" section of the config. If not
     * found, returns "Development"
     * @return string Version number of the application
     */
    protected function version() {
        return isset(Yii::$app->params['version'])
            ? Yii::$app->params['version']
            : 'Development';
    }
}
