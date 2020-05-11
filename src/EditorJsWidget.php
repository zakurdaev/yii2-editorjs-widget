<?php

namespace zakurdaev\editorjs;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\AssetBundle;
use yii\web\JsExpression;
use yii\web\Request;

/**
 * EditorJS widget.
 *
 * @property array $plugins EditorJS Blocks
 * @property string|null $selector Textarea selector
 * @property string|null $selectorForm Form selector
 *
 * @author Andrey Zakurdaev <andrey@zakurdaev.pro>
 * @link https://github.com/zakurdaev/yii2-editorjs-widget
 * @license https://github.com/zakurdaev/yii2-editorjs-widget/blob/master/LICENSE.md
 */
class EditorJsWidget extends Widget
{
    const ASSET_CLASS = 'zakurdaev\editorjs\assets\EditorJsAsset';
    const ASSET_CDN_CLASS = 'zakurdaev\editorjs\assets\EditorJsCdnAsset';

    /**
     * @var Model|null The data model that this widget is associated with.
     */
    public $model;

    /**
     * @var string|null The model attribute that this widget is associated with.
     */
    public $attribute;

    /**
     * @var string|null The input name. This must be set if `model` and `attribute` are not set.
     */
    public $name;

    /**
     * @var string|null The input value.
     */
    public $value;

    /**
     * @var string|null Selector pointing to textarea to initialize redactor for.
     * Defaults to `null` meaning that textarea does not exist yet and will be rendered by this widget.
     */
    public $selector;

    /**
     * @var string|null Selector Form
     * Defaults to `null` means that the widget is launched outside the form and will have a separate save button
     */
    public $selectorForm;

    /**
     * @var string|null Url to save editor data
     * $endpoints = [
        'saveJson' => '',
        'uploadImageByFile' => '',
        'uploadImageFetchUrl' => ''
        ]
     */
    public $endpoints = [];

    /**
     * @var array The HTML attribute options for the input tag.
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = [];

    /**
     * @var array {@link https://editorjs.io/configuration EditorJS Configuration} description of plug-in units.
     */
    public $plugins = [];

    /**
     * @var array Default plugins that will be merged with {@link $plugins}.
     * Set Yii::$app->params['editorjs-widget/plugins'] to array in your application config params.php.
     * If the variable is not set, standard values will be used.
     */
    protected $defaultPlugins;

    /**
     * @var string Asset class name
     */
    public $assetClass = self::ASSET_CLASS;

    /**
     * @var EditorJsAsset|AssetBundle AssetBundle
     */
    protected $assetBundle;

    /**
     * @var string String used at the beginning of tag classes and identifiers
     */
    public $tag_prefix = 'editorjs-';

    /**
     * @var boolean Whether to render the `textarea` or not.
     */
    protected $renderTextarea = true;

    /**
     * @var object Set of generated HTML tags options
     */
    protected $tags;

    protected function generateDefaultPlugins()
    {
        $plugins = ArrayHelper::getValue(Yii::$app->params, 'editorjs-widget/plugins');
        if (is_array($plugins)) {
            return $plugins;
        }

        return [
            'header' => [
                'repository' => 'editorjs/header',
                'class' => new JsExpression('Header'),
                'inlineToolbar' => true,
                'config' => ['placeholder' => 'Header', 'levels' => [2, 3, 4, 5]],
                'shortcut' => 'CMD+SHIFT+H'
            ],
            'paragraph' => [
                'repository' => 'editorjs/paragraph',
                'class' => new JsExpression('Paragraph'),
                'inlineToolbar' => true,
            ],
            'image' => [
                'repository' => 'editorjs/image',
                'class' => new JsExpression('ImageTool'),
                'config' => [
                    'field' => 'image',
                    'additionalRequestHeaders' => [],
                    'endpoints' => [
                        'byFile' => Url::to(['/editor-js/upload-file']),
                        'byUrl' => Url::to(['/editor-js/fetch-url']),
                    ]
                ]
            ],
            'list' => [
                'repository' => 'editorjs/list',
                'class' => new JsExpression('List'),
                'inlineToolbar' => true,
                'shortcut' => 'CMD+SHIFT+L'
            ],
            'table' => [
                'repository' => 'editorjs/table',
                'class' => new JsExpression('Table'),
                'inlineToolbar' => true,
                'shortcut' => 'CMD+ALT+T'
            ],
            'quote' => [
                'repository' => 'editorjs/quote',
                'class' => new JsExpression('Quote'),
                'inlineToolbar' => true,
                'config' => ['quotePlaceholder' => 'Quote', 'captionPlaceholder' => 'Author'],
                'shortcut' => 'CMD+SHIFT+O'
            ],
            'warning' => [
                'repository' => 'editorjs/warning',
                'class' => new JsExpression('Warning'),
                'inlineToolbar' => true,
                'config' => ['titlePlaceholder' => 'Title', 'messagePlaceholder' => 'Description'],
                'shortcut' => 'CMD+SHIFT+W'
            ],
            'code' => [
                'repository' => 'editorjs/code',
                'class' => new JsExpression('CodeTool'),
                'shortcut' => 'CMD+SHIFT+C'
            ],
            'embed' => [
                'repository' => 'editorjs/embed',
                'class' => new JsExpression('Embed')
            ],
            'delimiter' => [
                'repository' => 'editorjs/delimiter',
                'class' => new JsExpression('Delimiter')
            ],
            'inline-code' => [
                'repository' => 'editorjs/inline-code',
                'class' => new JsExpression('InlineCode'),
                'shortcut' => 'CMD+SHIFT+C'
            ]
        ];
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->hasModel() && $this->selector === null && $this->name === null) {
            throw new InvalidConfigException("Either 'name', or 'selector', or 'model' and 'attribute' properties must be specified.");
        } elseif ($this->hasModel() && ($this->selector !== null || $this->name !== null)) {
            throw new InvalidConfigException("'selector' or 'name' parameters cannot be used with parameters 'model' and 'attribute'");
        } elseif ($this->selector !== null && ($this->hasModel() || $this->name !== null)) {
            throw new InvalidConfigException("'name' or 'model' or 'attribute' parameters cannot be used with parameter 'selector'");
        } elseif ($this->name !== null && ($this->hasModel() || $this->selector !== null)) {
            throw new InvalidConfigException("'selector' or 'model' or 'attribute' parameters cannot be used with parameter 'name'");
        }

        try {
            $this->assetBundle = Yii::$container->get($this->assetClass);
        } catch (InvalidConfigException $e) {
            throw new InvalidConfigException("'assetClass' does not contain a class name");
        }

        $this->renderTextarea = $this->hasModel() || $this->selector === null;

        if ($this->hasModel()) {
            $this->value = JSON::decode(Html::getAttributeValue($this->model, $this->attribute));
            $this->selector = Html::getInputId($this->model, $this->attribute);
        }

        if ($this->selector === null) {
            $this->selector = $this->getId();
        }
        $this->options['id'] = $this->selector;

        $this->defaultPlugins = $this->generateDefaultPlugins();
        if (empty($this->plugins)) {
            $this->plugins = $this->defaultPlugins;
        }
        foreach ($this->plugins as $key => $config) {
            if (is_string($config)) {
                unset($this->plugins[$key]);
                $key = $config;
                $config = [];
            }
            $defaultConfig = ArrayHelper::getValue($this->defaultPlugins, $key, []);
            $this->plugins[$key] = $this->buildPluginConfig($config, $defaultConfig);
        }

        $this->tags = (object)[
            'form' => [
                'id' => $this->selectorForm,
                'class' => '',
            ],
            'textarea' => [
                'id' => $this->selector,
                'class' => '',
            ],
            'wrapper' => [
                'id' => $this->tag_prefix . 'wrap-' . $this->selector,
                'class' => $this->tag_prefix . 'wrap',
            ],
            'editor' => [
                'id' => $this->tag_prefix . $this->selector,
                'class' => $this->tag_prefix . 'editor',
            ],
            'saveButton' => [
                'id' => $this->tag_prefix . 'save-' . $this->selector,
                'class' => $this->tag_prefix . 'save',
            ]
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerClientScripts();

        $redactor = Html::tag('div', '', $this->tags->editor);

        if ($this->selectorForm === null) {
            $redactor .= Html::tag('button', 'Save', $this->tags->saveButton);
        }

        $textarea = '';
        if ($this->renderTextarea === true) {
            if ($this->hasModel()) {
                $textarea = Html::activeTextarea($this->model, $this->attribute, $this->options);
            } else {
                $textarea = Html::textarea($this->name, $this->value, $this->options);
            }
            $textarea = Html::tag('div', $textarea, ['style' => 'display:none']);
        }

        return Html::tag('div', $redactor, $this->tags->wrapper) . $textarea;
    }

    /**
     * @return boolean whether this widget is associated with a data model.
     */
    protected function hasModel()
    {
        return $this->model instanceof Model && $this->attribute !== null;
    }

    protected function pluginRepositories(&$plugins, $remove = false)
    {
        $name = 'repository';
        $result = [];
        foreach ($plugins as &$element) {
            $result[] = ArrayHelper::getValue($element, $name);
            if ($remove) {
                unset($element[$name]);
            }
        }

        return $result;
    }

    protected function buildPluginConfig($config, $defaultConfig) {
        $config = ArrayHelper::merge($defaultConfig, $config);
        $repository = ArrayHelper::getValue($config, 'repository');

        if ($repository == 'editorjs/image') {
            if (($request = Yii::$app->getRequest())->enableCsrfValidation) {
                $additionalRequestData = ArrayHelper::getValue($config, ['config', 'additionalRequestHeaders'], []);
                if (!array_key_exists(Request::CSRF_HEADER, $additionalRequestData)) {
                    $config['config']['additionalRequestHeaders'][Request::CSRF_HEADER] = $request->getCsrfToken();
                }
            }
            if ($byFile = ArrayHelper::getValue($this->endpoints, 'uploadImageByFile')) {
                if (!is_array($config['config']['endpoints'])) {
                    $config['config']['endpoints'] = [];
                }
                $config['config']['endpoints']['byFile'] = $byFile;
            }
            if ($byUrl = ArrayHelper::getValue($this->endpoints, 'uploadImageByUrl')) {
                if (!is_array($config['config']['endpoints'])) {
                    $config['config']['endpoints'] = [];
                }
                $config['config']['endpoints']['byFile'] = $byFile;
            }
        }

        return $config;
    }

    /**
     * Register widget asset.
     */
    protected function registerClientScripts()
    {
        $plugins = $this->plugins;
        $repositories = $this->pluginRepositories($plugins, true);

        $view = $this->getView();
        $asset = $this->assetBundle::register($view);
        $asset->addPlugins($repositories);

        $plugins = !empty($this->plugins) ? Json::encode($plugins) : '{}';

        $js = "
        const input = $('#" . $this->tags->textarea['id']. "');
        const val = (input.length === 0 || input.val() === '') ? null : JSON.parse(input.val());
        const editor = new EditorJS({
            holder : '" . $this->tags->editor['id']. "',
            tools: " . $plugins . ",
            data: val
        });";

        // todo: refine the situation with sending data somewhere
        if ($this->selectorForm !== null) {
            $js .= "
            const element = $('#" . $this->tags->form['id'] . "');
            const event = 'beforeValidate';
            ";
        } else {
            $js .= "
            const element = $('" . $this->tags->saveButton['id'] . "');
            const event = 'click';
            ";
        }
        $js .= "
        element.on(event, function (e) {
            return editor.save().then((outputData, valid) => {
                input.val(JSON.stringify(outputData, null));
                return true;
            }).catch((error) => {
                alert('EditorJS error');
                return false;
            });
        })
        ";
        $view->registerJs($js);
    }
}
