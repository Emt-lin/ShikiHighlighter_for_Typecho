<?php
//define('__TYPECHO_DEBUG__', true);

/**
 * 使用 Shiki 的代码高亮插件，用户可设置不同风格
 *
 * @package ShikiHighlighter
 * @version 1.0.0
 * @author wyh
 * @link https://www.pslanys.com
 */
class ShikiHighlighter_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->header = array(__CLASS__, 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array(__CLASS__, 'footer');
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
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    public static function render($text, $widget, $lastResult)
    {
    }

    /**
     *为header添加css文件
     * @return void
     */
    public static function header()
    {
        $theme = Helper::options()->plugin('ShikiHighlighter')->theme;

        echo <<<EOT
    <style type="text/css">
        .shiki,.shiki-themes {
            margin-bottom: 0!important;;
        }
        
        .shiki,
        .shiki span {
          /*background-color: initial!important;*/
        }
        
        @media (prefers-color-scheme: dark) {
          .shiki,
          .shiki span {
            color: var(--shiki-dark) !important;
            /*background-color: var(--shiki-dark-bg) !important;*/
            /* 可选，用于定义字体样式 */
            font-style: var(--shiki-dark-font-style) !important;
            font-weight: var(--shiki-dark-font-weight) !important;
            text-decoration: var(--shiki-dark-text-decoration) !important;
          }
        }
        html.dark .shiki,
        html.dark .shiki span,
        body.dark-mode .shiki,
        body.dark-mode .shiki span {
          color: var(--shiki-dark) !important;
          /*background-color: var(--shiki-dark-bg) !important;*/
          /* 可选，用于定义字体样式 */
          font-style: var(--shiki-dark-font-style) !important;
          font-weight: var(--shiki-dark-font-weight) !important;
          text-decoration: var(--shiki-dark-text-decoration) !important;
        }
    </style>
    <script type="module">
        // https://esm.run/shiki/themes/ + xxx
        // https://esm.run/shiki/core
        const darkTheme = 'github-dark'
        const [
            {getHighlighterCore},
            getWasm,
            selfTheme,
            dark,
            {bundledLanguages},
        ] = await Promise.all([
            import('https://esm.run/shiki/core'),
            import('https://esm.run/shiki/wasm'),
            import('https://esm.run/shiki/themes/${theme}' ),
            import('https://esm.run/shiki/themes/' + darkTheme),
            import('https://esm.run/shiki/langs')
        ])
            
        const highlighter = await getHighlighterCore({
            themes: [
                selfTheme,
                dark,
            ],
            // 初始不加载任何语言
            langs: [],
            loadWasm: getWasm
        });
    
        // 将 highlighter 和 bundledLanguages 存储在 window 对象中
        window.shikiHighlighter = {
            highlighter,
            bundledLanguages,
            darkTheme,
        }
        // 触发自定义事件，通知初始化完成
        document.dispatchEvent(new Event('shikiInitialized'));
        
    </script>
EOT;
    }

    public static function footer()
    {
        $theme = Helper::options()->plugin('ShikiHighlighter')->theme;

        echo <<<EOT
<style type="text/css">
        .post-content pre.loaded {
            box-shadow: rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0) 0px 0px 0px 0px, rgba(0, 0, 0, 0.1) 0px 1px 3px 0px, rgba(0, 0, 0, 0.1) 0px 1px 2px -1px;
        }
        </style>
<script type="module">
   const highlightCode = () => {
           document.addEventListener("DOMContentLoaded", function () {
               document.addEventListener('shikiInitialized', function() {
                // enable highlighter and bundledLanguages already init
                if (window.shikiHighlighter) {
                    const {highlighter, bundledLanguages, darkTheme} = window.shikiHighlighter
                    
                    document.querySelectorAll('pre code').forEach(async (block) => {
                        const langMatch = block.className.match(/[lang|language]-([\w-]+)/);
                        const lang = langMatch ? langMatch[1].toLowerCase() : 'plaintext';
                       
                        if (!highlighter.getLoadedLanguages().includes(lang)) {
                            const importFn = bundledLanguages[lang]
                            if (!importFn) return
                            await highlighter.loadLanguage(await importFn);
                        }
                        block.innerHTML = highlighter.codeToHtml(block.textContent, {
                            lang, 
                            themes: {
                                light: '${theme}',
                                dark: darkTheme
                            }
                        });
                    });
                } else {
                    console.error("Shiki Highlighter or Bundled Languages are not initialized.");
                }
        });
    });
   }
    highlightCode();
</script>
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
