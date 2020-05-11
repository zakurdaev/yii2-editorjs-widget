<?php

namespace zakurdaev\editorjs\assets;

use yii\web\AssetBundle;

/**
 * AssetBundle
 *
 * @author Andrey Zakurdaev <andrey@zakurdaev.pro>
 * @link https://github.com/zakurdaev/yii2-editorjs-widget
 * @license https://github.com/zakurdaev/yii2-editorjs-widget/blob/master/LICENSE.md
 */
class EditorJsAsset extends AssetBundle
{
    public $sourcePath = __DIR__;

    protected $validPlugins = [
        'editorjs/header',
        'editorjs/paragraph',
        'editorjs/image',
        'editorjs/list',
        'editorjs/table',
        'editorjs/quote',
        'editorjs/warning',
        'editorjs/code',
        'editorjs/embed',
        'editorjs/delimiter',
        'editorjs/inline-code'
    ];

    public $css = [
    ];

    public $js = [
        'editorjs/editor/editor.js',
    ];

    public $depends = [
    ];

    public function addPlugins(array $plugins)
    {
        $plugins = array_intersect($this->validPlugins, $plugins);
        foreach ($plugins as $plugin) {
            $this->js[$plugin] = $plugin . '/bundle.js';
        }
    }
}
