# OPS_DEPLOYMENT — Deployment & Setup Guide

## Yêu Cầu Cài Đặt Ban Đầu

### 1. Google Cloud Project Setup
```
1. Vào https://console.cloud.google.com
2. Tạo project mới (hoặc dùng project có sẵn)
3. Enable APIs:
   - Google Sheets API
   - Google Drive API
   - Google Search Console API
   - Google Slides API
   - Google Docs API
4. Lưu Project ID (dùng để link với GAS)
```

### 2. Gemini API Key
```
1. Vào https://aistudio.google.com/app/apikey
2. Create API Key → copy key
3. Sẽ lưu vào PropertiesService (step 6)
```

### 3. Master Google Sheet (Database)
```
1. Tạo 1 Google Sheet mới: "ToolSEO — Master"
2. Tạo tab: _projects (với headers theo CORE_DATABASE.md)
3. Copy Spreadsheet ID từ URL: 
   https://docs.google.com/spreadsheets/d/{SPREADSHEET_ID}/edit
```

### 4. Deploy Google Apps Script
```
Cách 1: clasp (recommended cho dev)
  npm install -g @google/clasp
  clasp login
  clasp create --type webapp --title "ToolSEO"
  clasp push
  clasp deploy --description "v1.0.0"

Cách 2: Manual (GAS IDE)
  → script.google.com → New project
  → Copy/paste tất cả .gs files
  → Deploy → New deployment → Web app
      Execute as: Me
      Access: Anyone
  → Copy URL deployment
```

### 5. Configure appsscript.json
```json
{
  "timeZone": "Asia/Ho_Chi_Minh",
  "dependencies": {},
  "exceptionLogging": "STACKDRIVER",
  "runtimeVersion": "V8",
  "webapp": {
    "executeAs": "USER_DEPLOYING",
    "access": "ANYONE"
  },
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

### 6. Set Script Properties (Config)
```javascript
// Chạy 1 lần trong GAS IDE (Run → initConfig)
function initConfig() {
  const props = PropertiesService.getScriptProperties();
  props.setProperties({
    'GEMINI_API_KEY': 'your-gemini-api-key-here',
    'MASTER_SHEET_ID': 'your-master-spreadsheet-id-here',
    'REPORT_TEMPLATE_SLIDES_ID': 'your-slides-template-id',
    'REPORT_TEMPLATE_DOCS_ID': 'your-docs-template-id',
  });
}
```

### 7. Setup Triggers (Scheduled Jobs)
```javascript
// Chạy 1 lần trong GAS IDE
function initTriggers() {
  ScriptApp.getProjectTriggers().forEach(t => ScriptApp.deleteTrigger(t));
  
  ScriptApp.newTrigger('dailySync')
    .timeBased().atHour(2).everyDays(1).create();
    
  ScriptApp.newTrigger('weeklyInspection')
    .timeBased().onWeekDay(ScriptApp.WeekDay.SUNDAY).atHour(3).create();
    
  ScriptApp.newTrigger('dailyRankingUpdate')
    .timeBased().atHour(3).everyDays(1).create();
}
```

### 8. Project Template Sheet
```
Tạo 1 Google Sheet "ToolSEO — Project Template" với:
  - Tab _config (headers)
  - Tab keywords (headers)
  - Tab silo_structure (headers)
  - Tab content_outlines (headers)
  - Tab technical_audit (headers)
  - Tab onpage_checklist (headers)
  - Tab geo_checklist (headers)
  - Tab backlinks (headers)
  - Tab rankings (headers)
  - Tab gsc_performance (headers)
  - Tab gsc_index_status (headers)
  - Tab rate_card (với data mẫu)
  - Tab quotation (headers)
Copy Template Sheet ID → lưu vào Script Properties: 'PROJECT_TEMPLATE_ID'
```

---

## Versioning

| Version | Description |
|---|---|
| v1.0.0 | Core MVP (M1-M4, M11) |
| v1.1.0 | Agency Ops (M5-M7, M12, M13) |
| v2.0.0 | Data + GSC (M8-M10, Advanced Dashboard) |
| v2.1.0 | Polish (Dark mode, White-label, Export) |
| v3.0.0 | WP Bridge Plugin |

## Deploy New Version
```
clasp push
clasp deploy --description "v1.1.0 — Agency Ops modules"
```
**Lưu ý**: URL deployment không đổi khi deploy version mới (nếu dùng cùng deployment ID).

---

## Checklist Trước Mỗi Deploy

- [ ] Test toàn bộ critical paths trên GAS IDE
- [ ] Kiểm tra quota usage (UrlFetch, Sheets API)
- [ ] Backup Master Sheet
- [ ] Ghi chú version + changes vào TRACK_CHANGELOG.md
- [ ] Test trên browser: Chrome + (optional) Firefox
- [ ] Kiểm tra mobile responsive (min 1280px)
