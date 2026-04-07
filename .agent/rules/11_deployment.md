# Rule: Deployment & Operations

## Initial Setup Sequence

### 1. Google Cloud Project
```
console.cloud.google.com → Enable APIs:
  - Google Sheets API
  - Google Drive API
  - Google Search Console API
  - Google Slides API
  - Google Docs API
```

### 2. Gemini API Key
```
aistudio.google.com/app/apikey → Create API Key → lưu vào PropertiesService (step 5)
```

### 3. Master Google Sheet
```
Tạo "ToolSEO — Master" → tab _projects (headers theo 07_database_schema.md)
→ Copy Spreadsheet ID từ URL
```

### 4. Deploy Google Apps Script

**Recommended (clasp)**:
```bash
npm install -g @google/clasp
clasp login
clasp create --type webapp --title "ToolSEO"
clasp push
clasp deploy --description "v1.0.0"
```

**Manual (GAS IDE)**:
```
script.google.com → New project → copy/paste .gs files
→ Deploy → New deployment → Web app
   Execute as: Me | Access: Anyone
→ Copy deployment URL
```

### 5. Set Script Properties (Run Once in GAS IDE)
```javascript
function initConfig() {
  PropertiesService.getScriptProperties().setProperties({
    'GEMINI_API_KEY':              'your-key',
    'MASTER_SHEET_ID':             'your-master-sheet-id',
    'REPORT_TEMPLATE_SLIDES_ID':   'your-slides-template-id',
    'REPORT_TEMPLATE_DOCS_ID':     'your-docs-template-id',
    'PROJECT_TEMPLATE_ID':         'your-project-template-sheet-id',
  });
}
```

### 6. Setup Triggers (Run Once)
```javascript
function initTriggers() {
  ScriptApp.getProjectTriggers().forEach(t => ScriptApp.deleteTrigger(t));
  ScriptApp.newTrigger('dailySync').timeBased().atHour(2).everyDays(1).create();
  ScriptApp.newTrigger('weeklyInspection').timeBased().onWeekDay(ScriptApp.WeekDay.SUNDAY).atHour(3).create();
  ScriptApp.newTrigger('dailyRankingUpdate').timeBased().atHour(3).everyDays(1).create();
}
```

### 7. Project Template Sheet
```
"ToolSEO — Project Template" với tabs:
_config, keywords, silo_structure, content_outlines,
technical_audit, onpage_checklist, geo_checklist,
backlinks, rankings, gsc_performance, gsc_index_status,
rate_card (với data mẫu), quotation
→ Copy ID → lưu vào Properties 'PROJECT_TEMPLATE_ID'
```

## Versioning

| Version | Scope |
|---|---|
| v1.0.0 | Core MVP (M1–M4, M11) |
| v1.1.0 | Agency Ops (M5–M7, M12, M13) |
| v2.0.0 | Data & GSC (M8–M10, Advanced Dashboard) |
| v2.1.0 | Polish (Dark mode, White-label, Export) |
| v3.0.0 | WP Bridge Plugin |

## Deploy New Version
```bash
clasp push
clasp deploy --description "v1.1.0 — Agency Ops modules"
```
**Note**: URL deployment không đổi khi deploy version mới (cùng deployment ID).

## Pre-Deploy Checklist
- [ ] Test critical paths trên GAS IDE
- [ ] Kiểm tra quota usage
- [ ] Backup Master Sheet
- [ ] Cập nhật TRACK_CHANGELOG.md
- [ ] Test Chrome + (optional) Firefox
- [ ] Verify min 1280px layout
