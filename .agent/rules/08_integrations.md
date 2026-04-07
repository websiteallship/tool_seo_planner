# Rule: External Integrations

## 1. Google Search Console API v3

### OAuth Scopes (appsscript.json)
```json
{
  "oauthScopes": [
    "https://www.googleapis.com/auth/spreadsheets",
    "https://www.googleapis.com/auth/script.external_request",
    "https://www.googleapis.com/auth/webmasters.readonly"
  ]
}
```

### Search Analytics Query
```javascript
// GSCClient.gs
function querySearchAnalytics(siteUrl, { startDate, endDate, dimensions, rowLimit = 25000 }) {
  const url = `https://www.googleapis.com/webmasters/v3/sites/${encodeURIComponent(siteUrl)}/searchAnalytics/query`;
  const token = ScriptApp.getOAuthToken();
  const response = UrlFetchApp.fetch(url, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` },
    contentType: 'application/json',
    payload: JSON.stringify({ startDate, endDate, dimensions, rowLimit, startRow: 0 })
  });
  return JSON.parse(response.getContentText()).rows || [];
}
```

**Limits**: 25,000 rows/request · Data delay 2-3 ngày · 1,200 req/day/user

### URL Inspection API
```javascript
function inspectUrl(siteUrl, inspectionUrl) {
  const url = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';
  const response = UrlFetchApp.fetch(url, {
    method: 'POST',
    headers: { Authorization: `Bearer ${ScriptApp.getOAuthToken()}` },
    contentType: 'application/json',
    payload: JSON.stringify({ inspectionUrl, siteUrl })
  });
  return JSON.parse(response.getContentText()).inspectionResult;
}
```
**Quota**: 2,000 req/day/property

### GSC Sync Strategy
- Sync 3 ngày data/lần (không sync 1 ngày/lần)
- URL Inspection: chỉ inspect pillar pages, hàng tuần
- Batch 10 URLs/trigger run tránh timeout

## 2. Gemini AI API

**Model**: `gemini-2.0-flash` (free tier)
**Base URL**: `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={key}`
**Auth**: API Key via query param
**Storage**: `PropertiesService.getScriptProperties().getProperty('GEMINI_API_KEY')`

```javascript
// GeminiClient.gs
function callGemini(prompt, schema = null) {
  const apiKey = PropertiesService.getScriptProperties().getProperty('GEMINI_API_KEY');
  const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;
  const payload = {
    contents: [{ parts: [{ text: prompt }] }],
    generationConfig: {
      responseMimeType: 'application/json',
      ...(schema && { responseSchema: schema })
    }
  };
  const response = UrlFetchApp.fetch(url, {
    method: 'POST', contentType: 'application/json',
    payload: JSON.stringify(payload)
  });
  const json = JSON.parse(response.getContentText());
  return JSON.parse(json.candidates[0].content.parts[0].text);
}

function callGeminiSafe(prompt, schema = null) {
  try { return callGemini(prompt, schema); }
  catch (e) { Logger.log('Gemini error: ' + e.message); return null; }
}
```

**Limits**: 15 RPM / 1M tokens/day (free tier)

## 3. Google Drive / Slides / Docs

```javascript
// Tạo Google Slides từ template
function createSlidesFromTemplate(templateId, data) {
  const copy = DriveApp.getFileById(templateId)
    .makeCopy(`SEO Report - ${data.client_name} - ${data.month}`);
  const slides = SlidesApp.openById(copy.getId());
  slides.getSlides().forEach(slide => {
    slide.getShapes().forEach(shape => {
      if (!shape.getText) return;
      let text = shape.getText().asString();
      Object.entries(data).forEach(([k, v]) => {
        text = text.replace(new RegExp(`{{${k}}}`, 'g'), v);
      });
      shape.getText().setText(text);
    });
  });
  slides.saveAndClose();
  return copy.getUrl();
}
```

## 4. Scheduled Triggers

| Function | Schedule | Purpose |
|---|---|---|
| `dailySync()` | Daily 2:00 AM | Pull GSC performance (last 3 days) |
| `weeklyInspection()` | Sunday 3:00 AM | Batch URL Inspection (top 100) |
| `weeklySitemapCheck()` | Monday 4:00 AM | Sitemap status check |
| `dailyRankingUpdate()` | Daily 3:00 AM | Compute position changes |

## 5. appsscript.json Config

```json
{
  "timeZone": "Asia/Ho_Chi_Minh",
  "exceptionLogging": "STACKDRIVER",
  "runtimeVersion": "V8",
  "webapp": { "executeAs": "USER_DEPLOYING", "access": "ANYONE" },
  "oauthScopes": [
    "https://www.googleapis.com/auth/spreadsheets",
    "https://www.googleapis.com/auth/drive",
    "https://www.googleapis.com/auth/script.external_request",
    "https://www.googleapis.com/auth/webmasters.readonly",
    "https://www.googleapis.com/auth/presentations",
    "https://www.googleapis.com/auth/documents"
  ]
}
```
