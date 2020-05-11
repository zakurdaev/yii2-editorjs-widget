<?php

namespace zakurdaev\editorjs;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
/**
 * Render blocks for EditorJS widget.
 *
 * @author Andrey Zakurdaev <andrey@zakurdaev.pro>
 * @link https://github.com/zakurdaev/yii2-editorjs-widget
 * @license https://github.com/zakurdaev/yii2-editorjs-widget/blob/master/LICENSE.md
 */
class RenderBlock
{
    public static function header(array $data)
    {
        $level = (int)ArrayHelper::getValue($data, 'level', 2);
        $text = (string)ArrayHelper::getValue($data, 'text', '');

        return Html::tag('h' . $level, $text);
    }

    public static function paragraph(array $data)
    {
        $text = (string)ArrayHelper::getValue($data, 'text', '');

        return Html::tag('p', $text);
    }

    public static function image(array $data)
    {
        $stretched = (boolean)ArrayHelper::getValue($data, 'stretched', false);
        $withBorder = (boolean)ArrayHelper::getValue($data, 'withBorder', false);
        $withBackground = (boolean)ArrayHelper::getValue($data, 'withBackground', false);
        $caption = (string)ArrayHelper::getValue($data, 'caption', '');
        $file = (object)ArrayHelper::getValue($data, 'file', ['url' => '']);

        $options = [
            'class' => 'image',
            'alt' => $caption,
            'title' => $caption
        ];
        if ($stretched && !$withBackground) {
            $options['class'] .= ' stretched';
        }
        if ($withBackground) {
            $options['class'] .= ' with-background';
        }
        if ($withBorder) {
            $options['class'] .= ' with-border';
        }

        if (isset($file->url)) {
            $options['src'] = $file->url;
            return Html::tag('img', '', $options);
        }
        return '';
    }

    public static function list(array $data)
    {
        $style = (string)ArrayHelper::getValue($data, 'style', 'ordered');
        $items = (array)ArrayHelper::getValue($data, 'items', []);
        $tag = $style == 'ordered' ? 'ol' : 'ul';

        $content = '';
        foreach ($items as $item) {
            $content .= Html::tag('li', $item);
        }
        return Html::tag($tag, $content);
    }

    public static function table(array $data)
    {
        $rows = (array)ArrayHelper::getValue($data, 'content', []);

        $content = '';
        foreach ($rows as $row) {
            $temp = '';
            foreach ($row as $cell) {
                $temp .= Html::tag('td', $cell);
            }
            $content .= Html::tag('tr', $temp);
        }
        return Html::tag('table', $content);
    }

    public static function quote(array $data)
    {
        $alignment = (string)ArrayHelper::getValue($data, 'alignment', '');
        $caption = (string)ArrayHelper::getValue($data, 'caption', '');
        $text = (string)ArrayHelper::getValue($data, 'text', '');

        if ($caption) {
            $caption = Html::tag('cite', $caption);
        }
        $class = !empty($alignment) ? 'align-' . $alignment : '';

        return Html::tag('blockquote', $text . $caption, ['class' => $class]);
    }


    public static function warning(array $data)
    {
        $title = (string)ArrayHelper::getValue($data, 'title', '');
        $message = (string)ArrayHelper::getValue($data, 'message', '');

        $message = Html::tag('div', $message, ['class' => 'message']);
        if (!empty($title)) {
            $title = Html::tag('div', $title, ['class' => 'title']);
        }

        return Html::tag('div', $title . $message, ['class' => 'alert']);
    }

    public static function code(array $data)
    {
        $code = (string)ArrayHelper::getValue($data, 'code', '');
        return Html::tag('code', $code);
    }

    public static function embed(array $data)
    {
        $service = (string)ArrayHelper::getValue($data, 'service', '');
        $caption = (string)ArrayHelper::getValue($data, 'caption', '');
        $embed = (string)ArrayHelper::getValue($data, 'embed', '');

        if (!empty($caption)) {
            $caption = Html::tag('div', $caption, ['class' => 'caption']);
        }
        $iframe = Html::tag('iframe', '', [
            'src' => $embed,
            'allow' => 'autoplay',
            'allowfullscreen' => 'allowfullscreen'
        ]);

        return Html::tag('div', $iframe . $caption, ['class' => 'embed ' . $service]);
    }

    public static function delimiter(array $data)
    {
        return Html::tag('div', '', ['class' => 'delimiter']);
    }
}
