# ARCH_PERFORMANCE — Hiệu Suất & GAS Constraints

## GAS Quotas Quan Trọng

| Giới hạn | Giá trị | Cách xử lý |
|---|---|---|
| Execution time | 6 phút/call | Chia batch nhỏ, dùng triggers |
| UrlFetch calls | 20,000/ngày | Cache responses (CacheService) |
| Spreadsheet read | 300 req/phút | Batch read 1 lần toàn sheet |
| Script triggers | 20 triggers total | Tối thiểu hoá số triggers |
| Cache size | 100KB/item | Compress data nếu cần |
| Properties storage | 500KB total | Dùng cho config nhỏ (API key, settings) |

---

## Patterns Tối Ưu Hiệu Suất

### 1. Batch Read — Đọc 1 lần, filter client-side

```javascript
// ❌ Tệ: nhiều lần đọc sheet
function getKeywordsByCluster(spreadsheetId, cluster) {
  // Làm nhiều API calls → chậm và tốn quota
}

// ✅ Tốt: đọc toàn bộ → filter trong JS
function getKeywordsByCluster(spreadsheetId, cluster) {
  const all = SheetDB.getRows(spreadsheetId, 'keywords'); // 1 sheet read
  return all.filter(k => k.cluster_group === cluster);
}
```

### 2. Batch Write — `setValues()` thay vì loop `setValue()`

```javascript
// ❌ Tệ: loop setValue()
rows.forEach((row, i) => {
  sheet.getRange(i + 2, intentColIdx + 1).setValue(row.intent); // N API calls!
});

// ✅ Tốt: 1 lần setValues()
const updates = rows.map(row => [row.intent]);
sheet.getRange(2, intentColIdx + 1, rows.length, 1).setValues(updates); // 1 API call
```

### 3. CacheService — Cache data ít thay đổi

```javascript
const CACHE_TTL = {
  rate_card: 3600,    // 1 giờ
  config: 600,        // 10 phút
  silo_tree: 300,     // 5 phút
  keywords: 60        // 1 phút
};

function getWithCache(key, fetchFn, ttl) {
  const cache = CacheService.getScriptCache();
  const hit = cache.get(key);
  if (hit) return JSON.parse(hit);
  const data = fetchFn();
  if (data) cache.put(key, JSON.stringify(data), ttl);
  return data;
}
```

### 4. Pagination Client-side

Frontend chỉ render 50 rows/page, nhưng đã load toàn bộ data:

```javascript
// Alpine.js
get paginatedKeywords() {
  const start = (this.page - 1) * 50;
  return this.filteredKeywords.slice(start, start + 50);
}
```

### 5. Chunked Processing cho Batch AI Calls

```javascript
// AIService.gs - tránh timeout 6 phút
function classifyIntentBatch(keywords) {
  const CHUNK_SIZE = 50; // Gemini xử lý 50 keywords/prompt
  const chunks = [];
  for (let i = 0; i < keywords.length; i += CHUNK_SIZE) {
    chunks.push(keywords.slice(i, i + CHUNK_SIZE));
  }
  return chunks.flatMap(chunk => callGemini(buildIntentPrompt(chunk)));
}
```

### 6. Lock Service — Tránh concurrent write conflicts

```javascript
// Khi trigger dailySync chạy cùng manual sync
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

---

## Gemini API Rate Limits

| Tier | RPM | Tokens/ngày |
|---|---|---|
| Free (gemini-2.0-flash) | 15 req/phút | 1M tokens |

**Chiến lược**:
- Batch keywords trong 1 prompt (50 keywords/call)
- Cache kết quả classify intent (1 giờ)
- Không gọi AI trong trigger tự động (chỉ gọi khi user trigger)

---

## GSC API Rate Limits

| API | Quota |
|---|---|
| searchAnalytics.query | 1,200 req/ngày/user |
| urlInspection | 2,000 req/ngày/property |

**Chiến lược**:
- Sync 3 ngày data 1 lần (không sync 1 ngày/1 lần)
- URL Inspection: chỉ inspect pillar pages hàng tuần
- Batch 10 URLs/trigger run để tránh timeout

---

## Monitoring

```javascript
// Thêm vào đầu mỗi scheduled trigger
function dailySync() {
  const start = Date.now();
  Logger.log(`[dailySync] Started at ${new Date().toISOString()}`);
  
  // ... sync logic ...
  
  const elapsed = (Date.now() - start) / 1000;
  Logger.log(`[dailySync] Completed in ${elapsed}s`);
  
  // Alert nếu gần timeout
  if (elapsed > 300) {
    Logger.log('[dailySync] WARNING: Near 6-min timeout threshold');
  }
}
```
