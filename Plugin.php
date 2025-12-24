<?php
//define('__TYPECHO_DEBUG__', true);

/**
 * 使用 Shiki 的代码高亮插件，用户可设置不同风格
 *
 * @package ShikiHighlighter
 * @version 2.0.0
 * @author wyh
 * @link https://www.pslanys.com
 */
class ShikiHighlighter_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->header = array(__CLASS__, 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array(__CLASS__, 'footer');
        Typecho_Plugin::factory('Widget_Archive')->singleHandle = array(__CLASS__, 'singleHandle');
    }

    public static function deactivate()
    {

    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $themes = self::getShikiThemes(); // 示例主题列表

        $theme = new Typecho_Widget_Helper_Form_Element_Select(
            'theme',
            $themes,
            'github-light',
            _t('选择适合的 Shiki 高亮主题')
        );
        $form->addInput($theme->addRule('enum', _t('必须选择配色样式'), array_keys($themes)));

        $cdnDomain = new Typecho_Widget_Helper_Form_Element_Text(
            'cdnDomain',
            NULL,
            'https://esm.sh,https://esm.run',
            _t('CDN 域名（支持逗号分隔，按顺序 fallback）'),
            _t('输入 Shiki 资源的 CDN 域名列表，例如：https://esm.sh,https://esm.run')
        );
        $form->addInput($cdnDomain);

        $shikiVersion = new Typecho_Widget_Helper_Form_Element_Text(
            'shikiVersion',
            NULL,
            '3.0.0',
            _t('Shiki 版本（建议锁定）'),
            _t('例如：3.0.0；留空表示使用 CDN 默认版本（不推荐）')
        );
        $form->addInput($shikiVersion);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    public static function singleHandle($archive)
    {
        $content = $archive->content;
        if (strpos($content, '```') !== false || strpos($content, '<pre><code') !== false) {
            $archive->containsCode = true;
        } else {
            $archive->containsCode = false;
        }
    }


    public static function render($text, $widget, $lastResult)
    {
    }

    /**
     * 为header添加css和配置
     * @return void
     */
    public static function header()
    {
        // 检查当前文章内容是否包含代码块
        $widget = Typecho_Widget::widget('Widget_Archive');
        if (!isset($widget->containsCode) || !$widget->containsCode) {
            return;
        }

        $options = Helper::options();
        $pluginUrl = $options->pluginUrl . '/ShikiHighlighter';
        $version = '1.0.0'; // 用于缓存控制

        $theme = (string) $options->plugin('ShikiHighlighter')->theme;
        $cdnDomain = (string) $options->plugin('ShikiHighlighter')->cdnDomain;
        $shikiVersion = trim((string) $options->plugin('ShikiHighlighter')->shikiVersion);

        // 配置 JSON（防止 </script> 注入）
        $config = json_encode([
            'theme' => $theme,
            'darkTheme' => 'github-dark',
            'cdn' => $cdnDomain,
            'version' => $shikiVersion
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        echo <<<EOT
    <link rel="stylesheet" href="{$pluginUrl}/assets/shiki.css?v={$version}">
    <script type="application/json" id="shiki-config">{$config}</script>
EOT;
    }

    public static function footer()
    {
        // 检查当前文章内容是否包含代码块
        $widget = Typecho_Widget::widget('Widget_Archive');
        if (!isset($widget->containsCode) || !$widget->containsCode) {
            return;
        }

        $options = Helper::options();
        $pluginUrl = $options->pluginUrl . '/ShikiHighlighter';
        $version = '1.0.0';

        echo <<<EOT
<script type="module" src="{$pluginUrl}/assets/shiki.js?v={$version}"></script>
EOT;
    }

    private static function getShikiThemes()
    {
        return array(
            'andromeeda' => 'Andromeeda',
            'aurora-x' => 'Aurora X',
            'ayu-dark' => 'Ayu Dark',
            'catppuccin-frappe' => 'Catppuccin Frappé',
            'catppuccin-latte' => 'Catppuccin Latte',
            'catppuccin-macchiato' => 'Catppuccin Macchiato',
            'catppuccin-mocha' => 'Catppuccin Mocha',
            'dark-plus' => 'Dark Plus',
            'dracula' => 'Dracula',
            'dracula-soft' => 'Dracula Soft',
            'github-dark' => 'GitHub Dark',
            'github-dark-default' => 'GitHub Dark Default',
            'github-dark-dimmed' => 'GitHub Dark Dimmed',
            'github-light' => 'GitHub Light',
            'github-light-default' => 'GitHub Light Default',
            'houston' => 'Houston',
            'light-plus' => 'Light Plus',
            'material-theme' => 'Material Theme',
            'material-theme-darker' => 'Material Theme Darker',
            'material-theme-lighter' => 'Material Theme Lighter',
            'material-theme-ocean' => 'Material Theme Ocean',
            'material-theme-palenight' => 'Material Theme Palenight',
            'min-dark' => 'Min Dark',
            'min-light' => 'Min Light',
            'monokai' => 'Monokai',
            'night-owl' => 'Night Owl',
            'nord' => 'Nord',
            'one-dark-pro' => 'One Dark Pro',
            'one-light' => 'One Light',
            'poimandres' => 'Poimandres',
            'red' => 'Red',
            'rose-pine' => 'Rosé Pine',
            'rose-pine-dawn' => 'Rosé Pine Dawn',
            'rose-pine-moon' => 'Rosé Pine Moon',
            'slack-dark' => 'Slack Dark',
            'slack-ochin' => 'Slack Ochin',
            'snazzy-light' => 'Snazzy Light',
            'solarized-dark' => 'Solarized Dark',
            'solarized-light' => 'Solarized Light',
            'synthwave-84' => 'Synthwave \'84',
            'tokyo-night' => 'Tokyo Night',
            'vesper' => 'Vesper',
            'vitesse-black' => 'Vitesse Black',
            'vitesse-dark' => 'Vitesse Dark',
            'vitesse-light' => 'Vitesse Light'
        );
    }
}
