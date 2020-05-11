<?php

namespace zakurdaev\editorjs;

use EditorJS\EditorJS;
use EditorJS\EditorJSException;
use Yii;
use yii\base\BaseObject;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * @property array blocks EditorJS blocks
 *
 * @author Andrey Zakurdaev <andrey@zakurdaev.pro>
 * @link https://github.com/zakurdaev/yii2-editorjs-widget
 * @license https://github.com/zakurdaev/yii2-editorjs-widget/blob/master/LICENSE.md
 */
class JsonToHtml extends BaseObject
{
    const RENDER_CLASS = 'zakurdaev\editorjs\RenderBlock';

    /**
     * @var array|string raw data EditorJS blocks
     */
    public $value;

    /**
     * @var array {@link https://github.com/editor-js/editorjs-php EditorJS PHP Configuration} validation rules for different types of Editor.js tools.
     * Set default value in Yii::$app->params['editorjs-widget/rules'] to array in your application config params.php and use global settings
     * If the variable is not set, standard values will be used.
     */
    public $configuration;

    /**
     * @var string Render class name
     */
    public $renderClass = self::RENDER_CLASS;

    public function init()
    {
        if (!is_string($this->value)) {
            $this->value = Json::encode($this->value);
        }
        if (empty($this->configuration)) {
            $this->configuration = ArrayHelper::getValue(Yii::$app->params, 'editorjs-widget/rules');
        }
        if (!is_string($this->configuration) && !is_null($this->configuration)) {
            $this->configuration = Json::encode($this->configuration);
        }
    }

    public function getBlocks()
    {
        if (empty($this->configuration)) {
            return ArrayHelper::getValue(Json::decode($this->value), 'blocks', []);
        }
        return (new EditorJS($this->value, $this->configuration))->getBlocks();
    }

    public function run()
    {
        $render = Yii::$container->get($this->renderClass);

        $html = '';
        foreach ($this->blocks as $block) {
            $type = ArrayHelper::getValue($block, 'type', '');
            $data = ArrayHelper::getValue($block, 'data', []);
            if (!method_exists($render, $type)) {
                continue;
            }

            $html .= $render::{$type}($data);
        }

        return $html;
    }
}
