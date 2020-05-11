# Editor.js Widget for Yii 2

`Editor.js Widget` is a wrapper for [Editor.js](https://github.com/codex-team/editor.js), next generation block styled editor.

## Install

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ php composer.phar require --prefer-dist zakurdaev/yii2-editorjs-widget "*"
```

or add

```json
"zakurdaev/yii2-editorjs-widget": "*"
```

to the `require` section of your `composer.json` file.


## Usage

Once the extension is installed, simply use it in your code:

### Like a widget

```php
echo \zakurdaev\editorjs\EditorJsWidget::widget([
    'selector' => 'redactor'
]);
```

### Like an ActiveForm widget

```php
use \zakurdaev\editorjs\EditorJsWidget;

echo $form->field($model, 'content_json')->widget(EditorJsWidget::class, [
 'selectorForm' => $form->id
])->label();
```
### Supported block
The plugin is able to support all blocks for Editor.js. You can use the standard Asset or use Asset CDN or write your own.

#### EditorJsAsset
Include: 
* editorjs/header v2.4.1
* editorjs/paragraph v2.6.1
* editorjs/image v2.3.4
* editorjs/list v1.4.0
* editorjs/table v1.2.2
* editorjs/quote v2.3.0
* editorjs/warning v1.1.1
* editorjs/code v2.4.1
* editorjs/embed v2.3.1
* editorjs/delimiter v1.1.0
* editorjs/inline-code v1.3.1

#### Custom Asset
```php
use \zakurdaev\editorjs\EditorJsWidget;

echo $form->field($model, 'content_json')->widget(EditorJsWidget::class, [
 'selectorForm' => $form->id,
 'assetClass' => 'YOUR/PATH/TO/ASSET'
])->label();
```


### Upload image by file and url

Widget supports image loading for [Editor.js Image Block](https://github.com/editor-js/image).

```php
// SiteController.php
public function actions()
{
    return [
        'upload-file' => [
            'class' => UploadImageAction::class,
            'mode' => UploadImageAction::MODE_FILE,
            'url' => 'https://example.com/upload_dir/',
            'path' => '@app/web/upload_dir',
            'validatorOptions' => [
                'maxWidth' => 1000,
                'maxHeight' => 1000
            ]
        ],
        'fetch-url' => [
            'class' => UploadImageAction::class,
            'mode' => UploadImageAction::MODE_URL,
            'url' => 'https://example.com/upload_dir/',
            'path' => '@app/web/upload_dir'
        ]
    ];
}

// view.php
echo \zakurdaev\editorjs\EditorJsWidget::widget([
    'selector' => 'redactor',
    'endpoints' => [
        'uploadImageByFile' => Url::to(['/site/upload-file']),
        'uploadImageByUrl' => Url::to(['/site/fetch-url']),
    ],
]);
```

## License
The BSD License (BSD).Please see [License File](LICENSE.md) for more information.