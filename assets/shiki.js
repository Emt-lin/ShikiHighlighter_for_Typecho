/**
 * Shiki Highlighter for Typecho
 * 从 #shiki-config 读取配置，初始化并高亮代码块
 */

// 读取配置
function getConfig() {
    const el = document.getElementById('shiki-config');
    if (!el) throw new Error('shiki-config not found');
    return JSON.parse(el.textContent);
}

// 等待 DOM 就绪
function domReady() {
    return new Promise((resolve) => {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => resolve(), { once: true });
        } else {
            resolve();
        }
    });
}

// 构建 Shiki URL
function shikiUrl(base, pkg, subpath) {
    return base.replace(/\/$/, '') + '/' + pkg + '/' + subpath;
}

// 初始化 Shiki
async function initShiki(config) {
    const { theme, darkTheme, cdn, version } = config;
    const shikiPkg = version ? `shiki@${version}` : 'shiki';

    // 解析 CDN 列表
    const cdnList = [...new Set(
        (cdn || '').split(',').map(s => s.trim()).filter(Boolean)
            .concat(['https://esm.sh', 'https://esm.run'])
    )];

    let lastError;
    for (const base of cdnList) {
        try {
            // Shiki 3.x 需要导入 engine/oniguruma
            const [coreModule, engineModule, lightModule, darkModule, langsModule] = await Promise.all([
                import(shikiUrl(base, shikiPkg, 'core')),
                import(shikiUrl(base, shikiPkg, 'engine/oniguruma')),
                import(shikiUrl(base, shikiPkg, 'themes/' + theme)),
                import(shikiUrl(base, shikiPkg, 'themes/' + darkTheme)),
                import(shikiUrl(base, shikiPkg, 'langs')),
            ]);

            const createHighlighterCore = coreModule?.createHighlighterCore;
            if (typeof createHighlighterCore !== 'function') {
                throw new Error('createHighlighterCore not available');
            }

            // Shiki 3.x 使用 createOnigurumaEngine
            const createOnigurumaEngine = engineModule?.createOnigurumaEngine;
            if (typeof createOnigurumaEngine !== 'function') {
                throw new Error('createOnigurumaEngine not available');
            }

            const highlighter = await createHighlighterCore({
                themes: [lightModule.default || lightModule, darkModule.default || darkModule],
                langs: [],
                engine: createOnigurumaEngine(() => import(shikiUrl(base, shikiPkg, 'wasm')))
            });

            return {
                highlighter,
                bundledLanguages: langsModule?.bundledLanguages || langsModule.default || langsModule,
                themeNames: { light: theme, dark: darkTheme }
            };
        } catch (err) {
            console.warn('[Shiki] CDN ' + base + ' failed:', err.message);
            lastError = err;
        }
    }
    throw lastError || new Error('All CDN failed');
}

// 语言别名
const langAlias = { js: 'javascript', ts: 'typescript', py: 'python', sh: 'bash', yml: 'yaml', md: 'markdown' };

// 并发加载去重
const inFlight = new Map();

// 确保语言已加载
async function ensureLanguage(highlighter, bundledLanguages, lang) {
    let resolved = (lang || '').toLowerCase() || 'plaintext';
    if (!bundledLanguages[resolved] && langAlias[resolved]) resolved = langAlias[resolved];
    if (highlighter.getLoadedLanguages().includes(resolved)) return resolved;

    const loader = bundledLanguages[resolved];
    if (!loader) return 'plaintext';

    if (inFlight.has(resolved)) {
        await inFlight.get(resolved);
        return resolved;
    }

    const promise = (async () => {
        const mod = typeof loader === 'function' ? await loader() : await loader;
        await highlighter.loadLanguage(mod.default || mod);
    })();
    inFlight.set(resolved, promise);
    try { await promise; } finally { inFlight.delete(resolved); }
    return resolved;
}

// 从 class 提取语言
function extractLang(className) {
    const match = String(className || '').match(/[lang|language]-([\w-]+)/);
    return match?.[1]?.toLowerCase() || '';
}

// 高亮所有代码块
async function highlightAll() {
    await domReady();

    let state;
    try {
        const config = getConfig();
        state = await initShiki(config);
    } catch (err) {
        console.error('[Shiki] init failed:', err);
        return;
    }

    const { highlighter, bundledLanguages, themeNames } = state;
    const blocks = document.querySelectorAll('pre > code');

    for (const codeEl of blocks) {
        const preEl = codeEl.parentElement;
        if (!preEl || preEl.classList.contains('shiki') || preEl.classList.contains('shiki-themes')) continue;

        const lang = await ensureLanguage(highlighter, bundledLanguages,
            extractLang(codeEl.className) || extractLang(preEl.className) || 'plaintext');

        try {
            codeEl.innerHTML = highlighter.codeToHtml(codeEl.textContent || '', { lang, themes: themeNames });
            preEl.classList.add('loaded');
        } catch (err) {
            console.error('[Shiki] highlight failed:', err);
        }
    }
}

// 启动
highlightAll();
