<?php

namespace zakurdaev\editorjs\assets;

use yii\web\AssetBundle;

/**
 * AssetBundle to work with CDN jsdelivr.net
 *
 * @author Andrey Zakurdaev <andrey@zakurdaev.pro>
 * @link https://github.com/zakurdaev/yii2-editorjs-widget
 * @license https://github.com/zakurdaev/yii2-editorjs-widget/blob/master/LICENSE.md
 */
class EditorJsCdnAsset extends AssetBundle
{
    public $css = [
    ];

    public $js = [
    ];

    public $depends = [
    ];

    public function addBlocks(array $plugins)
    {
        foreach ($plugins as $plugin) {
            $this->js[$plugin] = 'https://cdn.jsdelivr.net/npm/@' . $plugin . '@latest';
        }
    }
}
