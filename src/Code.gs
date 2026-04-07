// Code.gs — Entry point của GAS Web App
// Xử lý doGet() và include() để serve SPA

/**
 * Entry point: render index.html khi user truy cập web app URL
 */
function doGet(e) {
  return HtmlService.createTemplateFromFile('html/index')
    .evaluate()
    .setTitle('ToolSEO Planner')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL)
    .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}

/**
 * Helper để include các file html con (CSS, JS, pages)
 * Dùng trong template: <?!= include('html/css/styles') ?>
 */
function include(filename) {
  return HtmlService.createHtmlOutputFromFile(filename).getContent();
}
