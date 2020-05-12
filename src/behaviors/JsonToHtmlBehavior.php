<?php

namespace zakurdaev\editorjs\behaviors;

use zakurdaev\editorjs\JsonToHtml;
use yii\db\ActiveRecord;
use yii\base\Behavior;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;
use EditorJS\EditorJSException;

/**
 * JsonToHtmlBehavior
 *
 * @author Andrey Zakurdaev <andrey@zakurdaev.pro>
 * @link https://github.com/zakurdaev/yii2-editorjs-widget
 * @license https://github.com/zakurdaev/yii2-editorjs-widget/blob/master/LICENSE.md
 */
class JsonToHtmlBehavior extends Behavior
{
    /**
     * @var string|array the attribute or list of attributes whose value will be converted into a html
     */
    public $jsonAttribute = 'content_json';

    /**
     * @var string|array the attribute or list attributes that will receive the generated html
     */
    public $htmlAttribute = 'content';

    /**
     * @var array {@link https://github.com/editor-js/editorjs-php EditorJS PHP Configuration} description of plug-in units.
     * Set default value in Yii::$app->params['editorjs-widget/rules'] to array in your application config params.php and use global settings
     * If the variable is not set, standard values will be used.
     */
    public $convertConfiguration;

    /**
     * @var string Render class name
     */
    public $renderClass = JsonToHtml::RENDER_CLASS;

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
        ];
    }

    /**
     * @param $event
     * @throws ServerErrorHttpException
     */
    public function beforeSave($event)
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        foreach ((array)$this->jsonAttribute as $i => $attribute) {
            $targetAttribute = ArrayHelper::getValue((array)$this->htmlAttribute, $i);
            try {
                $converter = new JsonToHtml([
                    'value' => $owner->{$attribute},
                    'configuration' => $this->convertConfiguration,
                    'renderClass' => $this->renderClass
                ]);
                $owner->{$targetAttribute} = $converter->run();
            } catch (EditorJSException $e) {
                throw new ServerErrorHttpException($attribute . ' could not convert before saving');
            }
        }
    }
}
