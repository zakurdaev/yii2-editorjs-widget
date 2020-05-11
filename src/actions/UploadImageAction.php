<?php

namespace zakurdaev\editorjs\actions;

use Yii;
use yii\base\Action;
use yii\base\DynamicModel;
use yii\base\Exception;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * UploadImageAction for images and files.
 *
 * Usage:
 *
 * ```php
 * public function actions()
 * {
 *     return [
 *         'upload-file' => [
 *             'class' => UploadImageAction::class,
 *             'mode' => UploadImageAction::MODE_FILE,
 *             'url' => 'https://example.com/upload_dir/',
 *             'path' => '@app/web/upload_dir',
 *             'validatorOptions' => [
 *                 'maxWidth' => 1000,
 *                 'maxHeight' => 1000
 *             ]
 *         ],
 *         'fetch-url' => [
 *             'class' => UploadImageAction::class,
 *             'mode' => UploadImageAction::MODE_URL,
 *             'url' => 'https://example.com/upload_dir/',
 *             'path' => '@app/web/upload_dir',
 *             'validatorOptions' => [
 *                 'maxWidth' => 1000,
 *                 'maxHeight' => 1000
 *             ]
 *         ]
 *     ];
 * }
 * ```
 *
 * @author Andrey Zakurdaev <andrey@zakurdaev.pro>
 * @link https://github.com/zakurdaev/yii2-editorjs-widget
 * @license https://github.com/zakurdaev/yii2-editorjs-widget/blob/master/LICENSE.md
 */
class UploadImageAction extends Action
{
    const MODE_FILE = 'file';
    const MODE_URL = 'url';

    /**
     * @var string Path to directory where files will be uploaded.
     */
    public $path;

    /**
     * @var string URL path to directory where files will be uploaded.
     */
    public $url;

    /**
     * @var string Variable's name that EditorJs sent image upload.
     */
    public $mode = self::MODE_FILE;

    /**
     * @var string Variable's name that EditorJs sent image upload.
     */
    public $param;

    /**
     * @var boolean If `true` unique string will be added to the file name
     */
    public $unique = true;

    /**
     * @var boolean If `true` file will be replaced
     */
    public $replace = false;

    /**
     * @var array Model validator options.
     */
    public $validatorOptions = [];

    /**
     * @inheritdoc
     * @throws InvalidConfigException|Exception
     */
    public function init()
    {
        if ($this->url === null) {
            throw new InvalidConfigException('The "url" attribute must be set.');
        } else {
            $this->url = rtrim($this->url, '/') . '/';
        }
        if ($this->path === null) {
            throw new InvalidConfigException('The "path" attribute must be set.');
        } else {
            $this->path = rtrim(Yii::getAlias($this->path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            if (!FileHelper::createDirectory($this->path)) {
                throw new InvalidCallException("Directory specified in 'path' attribute doesn't exist or cannot be created.");
            }
        }
        if ($this->param === null) {
            $this->param = $this->mode == self::MODE_URL ? 'url' : 'image';
        }
    }

    public function run()
    {
        if (Yii::$app->request->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if ($this->mode == self::MODE_URL) {
                $body = Json::decode(Yii::$app->request->rawBody);
                $file = $this->uploadedFileByUrl(ArrayHelper::getValue($body, $this->param, ''));
            } else {
                $file = UploadedFile::getInstanceByName($this->param);
            }

            $model = new DynamicModel(['file' => $file]);
            $model
                ->addRule('file', 'required')
                ->addRule('file', 'image', $this->validatorOptions)
                ->validate();

            if ($file->hasError) {
                return [
                    'success' => 0,
                    'error' => 'File upload error'
                ];
            } else if ($model->hasErrors()) {
                return [
                    'success' => 0,
                    'error' => $model->getFirstError('file'),
                ];
            } else {
                $originalFileName = $model->file->name;
                if ($model->file->extension) {
                    $model->file->name = Inflector::slug($model->file->baseName) . '.' . $model->file->extension;
                    if ($this->unique === true) {
                        $model->file->name = uniqid() . '-' . $model->file->name;
                    }
                }

                if (file_exists($this->path . $model->file->name) && $this->replace === false) {
                    return [
                        'success' => 0,
                        'error' => 'File already exist'
                    ];
                }

                if ($model->file->saveAs($this->path . $model->file->name)) {
                    return [
                        'success' => 1,
                        'file' => [
                            "url" => $this->url . $model->file->name,
                            'originalFileName' => $originalFileName
                        ]
                    ];
                }
            }

            return [
                'success' => 0,
                'error' => 'File can not upload'
            ];
        } else {
            throw new BadRequestHttpException('Only POST is allowed');
        }
    }

    protected function uploadedFileByUrl($url)
    {
        if (empty($url)) {
            $options['error'] = UPLOAD_ERR_NO_FILE;
            return new UploadedFile($options);
        }

        $parsed_url = parse_url($url);
        $headers = get_headers($url, 1);

        if (!$parsed_url || !$headers || !preg_match('/^(HTTP)(.*)(200)(.*)/i', $headers[0])) {
            $options['error'] = UPLOAD_ERR_NO_FILE;
            return new UploadedFile($options);
        }

        $options['name'] = isset($parsed_url['path']) ? pathinfo($parsed_url['path'], PATHINFO_BASENAME) : '';
        $options['size'] = isset($headers['Content-Length']) ? $headers['Content-Length'] : 0;
        $options['type'] = isset($headers['Content-Type']) ? $headers['Content-Type'] : FileHelper::getMimeTypeByExtension($options['name']);

        $tempName = tempnam(sys_get_temp_dir(), 'php');
        if (!$tempName) {
            $options['error'] = UPLOAD_ERR_NO_TMP_DIR;
            return new UploadedFile($options);
        }
        register_shutdown_function(function () use ($tempName) {
            if (file_exists($tempName)) {
                unlink($tempName);
            }
        });

        $tempResource = fopen($tempName, 'r+');
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_FILE => $tempResource,
            CURLOPT_FAILONERROR => true,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        curl_exec($curl);
        curl_close($curl);

        $options['tempResource'] = $tempResource;
        $options['tempName'] = $tempName;

        return new UploadedFile($options);
    }
}
