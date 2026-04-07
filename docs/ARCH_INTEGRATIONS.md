# ARCH_INTEGRATIONS — External API Integrations

## 1. Google Search Console API v3

### Setup
```json
// appsscript.json
{
  "oauthScopes": [
    "https://www.googleapis.com/auth/spreadsheets",
    "https://www.googleapis.com/auth/script.external_request",
    "https://www.googleapis.com/auth/webmasters.readonly"
  ]
}
```

### 1.1 Search Analytics (Performance Data)

**Endpoint**: `POST https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/searchAnalytics/query`

```javascript
// GSCClient.gs
function querySearchAnalytics(siteUrl, { startDate, endDate, dimensions, rowLimit = 25000 }) {
  const url = `https://www.googleapis.com/webmasters/v3/sites/${encodeURIComponent(siteUrl)}/searchAnalytics/query`;
  const token = ScriptApp.getOAuthToken();
  
  const payload = {
    startDate, endDate,
    dimensions, // ['query', 'page', 'country', 'device']
    rowLimit,
    startRow: 0
  };
  
  const response = UrlFetchApp.fetch(url, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` },
    contentType: 'application/json',
    payload: JSON.stringify(payload)
  });
  
  return JSON.parse(response.getContentText()).rows || [];
}
```

**Limits**: 25,000 rows/request · Data delay 2-3 ngày · 16 tháng retention

### 1.2 URL Inspection API

**Endpoint**: `POST https://searchconsole.googleapis.com/v1/urlInspection/index:inspect`

```javascript
function inspectUrl(siteUrl, inspectionUrl) {
  const url = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';
  const token = ScriptApp.getOAuthToken();
  
  const response = UrlFetchApp.fetch(url, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` },
    contentType: 'application/json',
    payload: JSON.stringify({ inspectionUrl, siteUrl })
  });
  
  return JSON.parse(response.getContentText()).inspectionResult;
}
```

**Quota**: 2,000 requests/ngày/property · 600 req/phút

### 1.3 Sitemaps API

**Endpoint**: `GET https://www.googleapis.com/webmasters/v3/sites/{siteUrl}/sitemaps`

---

## 2. Gemini AI API

→ Xem chi tiết: `CORE_AI_CONTEXT.md`

**Base URL**: `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent`  
**Auth**: API Key via query param `?key={GEMINI_API_KEY}`  
**Storage**: `PropertiesService.getScriptProperties().getProperty('GEMINI_API_KEY')`

---

## 3. Google Drive API

Dùng built-in GAS utilities (không cần OAuth riêng):

```javascript
// Tạo Google Slides từ template
function createSlidesFromTemplate(templateId, data) {
  const template = DriveApp.getFileById(templateId);
  const copy = template.makeCopy(`SEO Report - ${data.client_name} - ${data.month}`);
  const slides = SlidesApp.openById(copy.getId());
  
  slides.getSlides().forEach(slide => {
    slide.getShapes().forEach(shape => {
      if (shape.getText) {
        let text = shape.getText().asString();
        Object.entries(data).forEach(([key, val]) => {
          text = text.replace(new RegExp(`{{${key}}}`, 'g'), val);
        });
        shape.getText().setText(text);
      }
    });
  });
  
  slides.saveAndClose();
  return copy.getUrl();
}

// Tạo Google Docs
function createDocFromTemplate(templateId, data) {
  const copy = DriveApp.getFileById(templateId).makeCopy(`Status Report - ${data.client_name}`);
  const doc = DocumentApp.openById(copy.getId());
  const body = doc.getBody();
  
  Object.entries(data).forEach(([key, val]) => {
    body.replaceText(`{{${key}}}`, String(val));
  });
  
  doc.saveAndClose();
  return copy.getUrl();
}
```

---

## 4. Google Sheets API (Built-in SpreadsheetApp)

→ Xem chi tiết usage ở `ARCH_PATTERNS.md` - SheetDB Pattern

**Key operations**:
- `SpreadsheetApp.create(name)` — tạo sheet mới khi tạo project
- `SpreadsheetApp.openById(id)` — mở sheet theo ID
- `sheet.getDataRange().getValues()` — batch read toàn bộ
- `sheet.appendRow(rowArray)` — thêm row
- `sheet.getRange(row, col).setValue(val)` — update cell

---

## 5. Scheduled Triggers

| Trigger Function | Frequency | Action |
|---|---|---|
| `dailySync()` | Daily 2:00 AM | Pull GSC performance data (last 3 ngày) |
| `weeklyInspection()` | Sunday 3:00 AM | Batch inspect top 100 URLs via URL Inspection API |
| `weeklySitemapCheck()` | Monday 4:00 AM | Check sitemap status & errors |
| `dailyRankingUpdate()` | Daily 3:00 AM | Compute position changes from gsc_performance |

**Setup**:
```javascript
function initTriggers() {
  // Clear existing
  ScriptApp.getProjectTriggers().forEach(t => ScriptApp.deleteTrigger(t));
  
  ScriptApp.newTrigger('dailySync').timeBased().atHour(2).everyDays(1).create();
  ScriptApp.newTrigger('weeklyInspection').timeBased().onWeekDay(ScriptApp.WeekDay.SUNDAY).create();
  ScriptApp.newTrigger('weeklySitemapCheck').timeBased().onWeekDay(ScriptApp.WeekDay.MONDAY).create();
  ScriptApp.newTrigger('dailyRankingUpdate').timeBased().atHour(3).everyDays(1).create();
}
```
