# Rule: Design Patterns — Core Implementation Patterns

## 1. Service Pattern

```javascript
// services/KeywordService.gs — template chuẩn
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
  update(projectId, keywordId, updates) {
    const spreadsheetId = ProjectService.getSpreadsheetId(projectId);
    return SheetDB.updateRow(spreadsheetId, 'keywords', 'keyword_id', keywordId, updates);
  }
};
```

## 2. API Dispatcher Pattern

```javascript
// API.gs — single entry point
function dispatch(action, params) {
  const handlers = {
    'keyword.getAll':   () => KeywordService.getAll(params.projectId),
    'keyword.add':      () => KeywordService.add(params.projectId, params.data),
    'keyword.classify': () => AIService.classifyIntentBatch(params.keywords),
    'silo.getTree':     () => SiloService.getTree(params.projectId),
    // ... 50+ actions
  };
  const handler = handlers[action];
  if (!handler) throw new Error(`Unknown action: ${action}`);
  try {
    return { success: true, data: handler() };
  } catch (e) {
    Logger.log(`[API] Error in ${action}: ${e.message}`);
    return { success: false, error: e.message };
  }
}
```

## 3. SheetDB CRUD Pattern

```javascript
// SheetDB.gs — generic, không biết gì về business logic
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
    sheet.appendRow(headers.map(h => data[h] ?? ''));
  },
  batchAddRows(spreadsheetId, sheetName, dataArray) {
    if (!dataArray.length) return;
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    const headers = sheet.getRange(1, 1, 1, sheet.getLastColumn()).getValues()[0];
    const rows = dataArray.map(data => headers.map(h => data[h] ?? ''));
    sheet.getRange(sheet.getLastRow() + 1, 1, rows.length, headers.length).setValues(rows);
  },
  updateRow(spreadsheetId, sheetName, pkColumn, pkValue, updates) {
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    const [headers, ...rows] = sheet.getDataRange().getValues();
    const pkIdx = headers.indexOf(pkColumn);
    const rowIdx = rows.findIndex(r => r[pkIdx] === pkValue);
    if (rowIdx === -1) return false;
    const updatedRow = headers.map((h, i) => h in updates ? updates[h] : rows[rowIdx][i]);
    sheet.getRange(rowIdx + 2, 1, 1, headers.length).setValues([updatedRow]);
    return true;
  },
  deleteRow(spreadsheetId, sheetName, pkColumn, pkValue) {
    const sheet = SpreadsheetApp.openById(spreadsheetId).getSheetByName(sheetName);
    const [headers, ...rows] = sheet.getDataRange().getValues();
    const pkIdx = headers.indexOf(pkColumn);
    const rowIdx = rows.findIndex(r => r[pkIdx] === pkValue);
    if (rowIdx === -1) return false;
    sheet.deleteRow(rowIdx + 2);
    return true;
  }
};
```

## 4. API Client Pattern (Frontend)

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

## 5. Cache Pattern

```javascript
const CACHE_TTL = { rate_card: 3600, config: 600, silo_tree: 300, keywords: 60 };

function getWithCache(key, fetchFn, ttl = 300) {
  const cache = CacheService.getScriptCache();
  const hit = cache.get(key);
  if (hit) return JSON.parse(hit);
  const data = fetchFn();
  if (data) cache.put(key, JSON.stringify(data), ttl);
  return data;
}
```

## 6. AI Review-Before-Apply Pattern

**Nguyên tắc bất biến**: AI KHÔNG tự ghi database. Luôn: AI preview → Human review → User confirm → Save.

```javascript
// Alpine.js
async classifyIntents() {
  const selected = this.keywords.filter(k => k._selected);
  this.aiPreview = await callServer('keyword.classify', { keywords: selected.map(k => k.keyword) });
  this.showAIPreviewModal = true; // User reviews here
},
async applyAIPreview() {
  await callServer('keyword.applyIntents', { updates: this.aiPreview });
  this.keywords = await callServer('keyword.getAll', { projectId: this.currentProject });
  this.showAIPreviewModal = false;
}
```

## 7. Trigger Pattern (Scheduled Jobs)

```javascript
function initTriggers() {
  ScriptApp.getProjectTriggers().forEach(t => ScriptApp.deleteTrigger(t));
  ScriptApp.newTrigger('dailySync').timeBased().atHour(2).everyDays(1).create();
  ScriptApp.newTrigger('weeklyInspection').timeBased().onWeekDay(ScriptApp.WeekDay.SUNDAY).atHour(3).create();
  ScriptApp.newTrigger('dailyRankingUpdate').timeBased().atHour(3).everyDays(1).create();
}
```

## 8. Lock Service Pattern (Concurrent Write Safety)

```javascript
function syncWithLock(projectId) {
  const lock = LockService.getScriptLock();
  if (!lock.tryLock(5000)) return { error: 'Another sync is running' };
  try {
    return GSCService.syncPerformance(projectId);
  } finally {
    lock.releaseLock();
  }
}
```
