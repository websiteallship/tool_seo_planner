# ARCH_PATTERNS — Design Patterns & Conventions

## 1. Service Pattern (Business Logic Layer)

Mỗi module có 1 Service file độc lập. Service không biết gì về UI.

```javascript
// services/KeywordService.gs
const KeywordService = {
  
  getAll(projectId) {
    const spreadsheetId = ProjectService.getSpreadsheetId(projectId);
    return SheetDB.getRows(spreadsheetId, 'keywords');
  },

  add(projectId, data) {
    const spreadsheetId = ProjectService.getSpreadsheetId(projectId);
    const row = { keyword_id: Utils.uuid(), ...data };
    SheetDB.addRow(spreadsheetId, 'keywords', row);
    return row;
  },

  updateIntent(projectId, keywordId, intent) {
    const spreadsheetId = ProjectService.getSpreadsheetId(projectId);
    SheetDB.updateRow(spreadsheetId, 'keywords', 'keyword_id', keywordId, { intent });
  }
};
```

---

## 2. API Dispatcher Pattern (API.gs)

Single entry point cho tất cả RPC calls từ client.

```javascript
// API.gs
function dispatch(action, params) {
  const handlers = {
    'keyword.getAll':     () => KeywordService.getAll(params.projectId),
    'keyword.add':        () => KeywordService.add(params.projectId, params.data),
    'keyword.classify':   () => AIService.classifyIntentBatch(params.keywords),
    'silo.getTree':       () => SiloService.getTree(params.projectId),
    'outline.generate':   () => OutlineService.generateBatch(params.siloIds),
    // ... 50+ actions
  };

  const handler = handlers[action];
  if (!handler) throw new Error(`Unknown action: ${action}`);
  
  try {
    return { success: true, data: handler() };
  } catch (e) {
    return { success: false, error: e.message };
  }
}
```

---

## 3. SheetDB Generic CRUD Pattern

```javascript
// SheetDB.gs
const SheetDB = {

  getRows(spreadsheetId, sheetName) {
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    const [headers, ...rows] = sheet.getDataRange().getValues();
    return rows.filter(r => r[0]).map(row => 
      Object.fromEntries(headers.map((h, i) => [h, row[i]]))
    );
  },

  addRow(spreadsheetId, sheetName, data) {
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    const row = headers.map(h => data[h] ?? '');
    sheet.appendRow(row);
  },

  updateRow(spreadsheetId, sheetName, pkColumn, pkValue, updates) {
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    const [headers, ...rows] = sheet.getDataRange().getValues();
    const pkIdx = headers.indexOf(pkColumn);
    const rowIdx = rows.findIndex(r => r[pkIdx] === pkValue);
    if (rowIdx === -1) return false;
    // Batch write: đọc toàn row → merge updates → ghi lại 1 lần
    const currentRow = rows[rowIdx];
    const updatedRow = headers.map((h, i) => h in updates ? updates[h] : currentRow[i]);
    sheet.getRange(rowIdx + 2, 1, 1, headers.length).setValues([updatedRow]);
    return true;
  },

  batchAddRows(spreadsheetId, sheetName, dataArray) {
    if (!dataArray.length) return;
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    const rows = dataArray.map(data => headers.map(h => data[h] ?? ''));
    sheet.getRange(sheet.getLastRow() + 1, 1, rows.length, headers.length).setValues(rows);
  },

  deleteRow(spreadsheetId, sheetName, pkColumn, pkValue) {
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    const [headers, ...rows] = sheet.getDataRange().getValues();
    const pkIdx = headers.indexOf(pkColumn);
    const rowIdx = rows.findIndex(r => r[pkIdx] === pkValue);
    if (rowIdx === -1) return false;
    sheet.deleteRow(rowIdx + 2); // +2: skip header, 1-indexed
    return true;
  }
};
```

---

## 4. Alpine.js API Client Pattern

```javascript
// html/js/api-client.html
function callServer(action, params = {}) {
  return new Promise((resolve, reject) => {
    google.script.run
      .withSuccessHandler(result => {
        if (result.success) resolve(result.data);
        else reject(new Error(result.error));
      })
      .withFailureHandler(reject)
      .dispatch(action, params);
  });
}
```

**Sử dụng trong Alpine.js:**
```javascript
// Trong x-data component
async loadKeywords() {
  this.loading = true;
  try {
    this.keywords = await callServer('keyword.getAll', { projectId: this.currentProject });
  } catch (e) {
    this.showToast(e.message, 'danger');
  } finally {
    this.loading = false;
  }
}
```

---

## 5. Template System Pattern

Checklist templates lưu dưới dạng JSON trong `templates/` folder. Load 1 lần khi initialize project.

```javascript
// AuditService.gs
function loadTemplate(projectId) {
  const template = JSON.parse(
    HtmlService.createHtmlOutputFromFile('templates/technical_audit_checklist').getContent()
  );
  const spreadsheetId = ProjectService.getSpreadsheetId(projectId);
  SheetDB.batchAddRows(spreadsheetId, 'technical_audit', template);
}
```

---

## 6. Trigger Pattern (Scheduled Jobs)

```javascript
// Code.gs - Setup triggers (gọi 1 lần trong GAS IDE)
function initTriggers() {
  ScriptApp.newTrigger('dailySync')
    .timeBased().atHour(2).everyDays(1).create();
    
  ScriptApp.newTrigger('weeklyInspection')
    .timeBased().onWeekDay(ScriptApp.WeekDay.SUNDAY).atHour(3).create();
}

function dailySync() {
  const projects = SheetDB.getRows(MASTER_SHEET_ID, '_projects')
    .filter(p => p.status === 'active' && p.gsc_sync_enabled === true);
  
  projects.forEach(p => {
    try { GSCService.syncPerformance(p.project_id); }
    catch (e) { Logger.log(`Sync failed for ${p.domain}: ${e.message}`); }
  });
}
```

---

## 7. Cache Pattern

```javascript
// Cho data ít thay đổi (config, rate card)
function getWithCache(cacheKey, fetchFn, ttl = 300) {
  const cache = CacheService.getScriptCache();
  const cached = cache.get(cacheKey);
  if (cached) return JSON.parse(cached);
  
  const data = fetchFn();
  cache.put(cacheKey, JSON.stringify(data), ttl);
  return data;
}

// Usage
const rateCard = getWithCache(`rateCard_${projectId}`, 
  () => SheetDB.getRows(spreadsheetId, 'rate_card'), 3600);
```

---

## 8. AI Review-Before-Apply Pattern

AI không bao giờ ghi thẳng vào database. Luôn qua preview → confirm.

```javascript
// Alpine.js component
async classifyIntents() {
  const selected = this.keywords.filter(k => k._selected);
  this.aiPreview = await callServer('keyword.classify', { 
    keywords: selected.map(k => k.keyword) 
  });
  this.showAIPreviewModal = true; // User reviews here
},

async applyAIPreview() {
  await callServer('keyword.applyIntents', { updates: this.aiPreview });
  this.keywords = await callServer('keyword.getAll', { projectId: this.currentProject });
  this.showAIPreviewModal = false;
}
```
