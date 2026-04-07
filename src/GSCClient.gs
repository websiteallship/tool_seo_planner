// GSCClient.gs — Wrapper cho Google Search Console API v3
// Dùng OAuth token từ ScriptApp (không cần API key riêng)

/**
 * Query Search Analytics — lấy performance data từ GSC
 * @param {string} siteUrl — URL property (e.g. "https://example.com/")
 * @param {Object} options
 * @param {string} options.startDate — YYYY-MM-DD
 * @param {string} options.endDate — YYYY-MM-DD
 * @param {string[]} options.dimensions — ['query', 'page', 'date', 'device', 'country']
 * @param {number} [options.rowLimit=25000]
 * @returns {Object[]} rows
 */
function querySearchAnalytics(siteUrl, { startDate, endDate, dimensions, rowLimit = 25000 }) {
  const url = `https://www.googleapis.com/webmasters/v3/sites/${encodeURIComponent(siteUrl)}/searchAnalytics/query`;
  const token = ScriptApp.getOAuthToken();

  const response = UrlFetchApp.fetch(url, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` },
    contentType: 'application/json',
    payload: JSON.stringify({ startDate, endDate, dimensions, rowLimit, startRow: 0 }),
    muteHttpExceptions: true
  });

  if (response.getResponseCode() !== 200) {
    throw new Error(`GSC API error ${response.getResponseCode()}: ${response.getContentText()}`);
  }

  const json = JSON.parse(response.getContentText());
  return json.rows || [];
}

/**
 * URL Inspection API — kiểm tra trạng thái index của 1 URL
 * @param {string} siteUrl — URL property
 * @param {string} inspectionUrl — URL cụ thể cần inspect
 * @returns {Object} inspectionResult
 */
function inspectUrl(siteUrl, inspectionUrl) {
  const url = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';

  const response = UrlFetchApp.fetch(url, {
    method: 'POST',
    headers: { Authorization: `Bearer ${ScriptApp.getOAuthToken()}` },
    contentType: 'application/json',
    payload: JSON.stringify({ inspectionUrl, siteUrl }),
    muteHttpExceptions: true
  });

  if (response.getResponseCode() !== 200) {
    throw new Error(`URL Inspection error ${response.getResponseCode()}: ${response.getContentText()}`);
  }

  return JSON.parse(response.getContentText()).inspectionResult;
}

/**
 * Lấy danh sách tất cả GSC properties của user
 * @returns {string[]} siteUrls
 */
function listGSCProperties() {
  const url = 'https://www.googleapis.com/webmasters/v3/sites';
  const response = UrlFetchApp.fetch(url, {
    headers: { Authorization: `Bearer ${ScriptApp.getOAuthToken()}` },
    muteHttpExceptions: true
  });
  const json = JSON.parse(response.getContentText());
  return (json.siteEntry || []).map(s => s.siteUrl);
}
