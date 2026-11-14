const {themes} = require('prism-react-renderer');
module.exports = {
  title: 'Asyncara', tagline: 'Build asynchronous PHP applications with explicit, dependable APIs',
  favicon: 'img/logo.svg', url: 'https://asyncara.github.io', baseUrl: '/docs/',
  organizationName: 'asyncara', projectName: 'docs', trailingSlash: true,
  onBrokenLinks: 'throw', markdown: {hooks: {onBrokenMarkdownLinks: 'throw'}},
  i18n: {defaultLocale: 'en', locales: ['en'], localeConfigs: {en: {label: 'English', direction: 'ltr', htmlLang: 'en'}}},
  presets: [['classic', {docs: {routeBasePath: 'guide', sidebarPath: require.resolve('./sidebars.js'), editUrl: 'https://github.com/asyncara/docs/edit/main/'}, blog: false, theme: {customCss: require.resolve('./src/css/custom.css')}}]],
  themes: [['@easyops-cn/docusaurus-search-local', {hashed: true, language: ['en'], docsRouteBasePath: '/guide', indexBlog: false, highlightSearchTermsOnTargetPage: true}]],
  themeConfig: {
    colorMode: {defaultMode: 'dark', respectPrefersColorScheme: true},
    navbar: {title: 'Asyncara', logo: {alt: 'Asyncara', src: 'img/logo.svg'}, items: [
      {to: '/guide/quickstart', label: 'Get started', position: 'left'},
      {to: '/guide/recipes/rest-api', label: 'Recipes', position: 'left'},
      {to: '/guide/reference/overview', label: 'API reference', position: 'left'},
      {href: 'https://github.com/asyncara/asyncara', label: 'GitHub', position: 'right'}]},
    footer: {style: 'dark', links: [
      {title: 'Build with Asyncara', items: [{label: 'Installation', to: '/guide/installation'}, {label: 'HTTP & Hyper', to: '/guide/http/client'}, {label: 'Native Server', to: '/guide/server/routing'}]},
      {title: 'Operate with confidence', items: [{label: 'Compatibility', to: '/guide/operations/compatibility'}, {label: 'Diagnostics', to: '/guide/operations/diagnostics'}, {label: 'Troubleshooting', to: '/guide/operations/troubleshooting'}]},
      {title: 'Project', items: [{label: 'Source code', href: 'https://github.com/asyncara/asyncara'}, {label: 'Runnable examples', href: 'https://github.com/asyncara/docs/tree/main/examples'}, {label: 'License', to: '/guide/operations/license'}]}], copyright: 'Asyncara · Copyright © 2026 Ajmal Alkhaledi · PolyForm Strict 1.0.0'},
    prism: {theme: themes.github, darkTheme: themes.dracula, additionalLanguages: ['php','bash','json','sql','ini','nginx']},
    tableOfContents: {minHeadingLevel: 2, maxHeadingLevel: 3},
    announcementBar: {id: 'experimental-010', content: '0.10.0 is EXPERIMENTAL · Examples use real APIs · Check compatibility before deployment', backgroundColor: '#173b38', textColor: '#d8fff3', isCloseable: true}
  }
};
